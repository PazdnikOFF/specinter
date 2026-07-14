from fastapi import APIRouter, HTTPException, Response, Request
from pydantic import BaseModel
from .. import db, unf, payments

router = APIRouter(prefix="/api", tags=["orders + 1С:УНФ"])


class OrderItemIn(BaseModel):
    product_id: int
    qty: float = 1


class CustomerIn(BaseModel):
    name: str
    phone: str | None = None
    email: str | None = None
    kind: str = "person"           # person | legal
    org_name: str | None = None
    inn: str | None = None
    kpp: str | None = None


class OrderIn(BaseModel):
    customer: CustomerIn
    items: list[OrderItemIn]
    channel: str = "web"           # web | telegram | whatsapp | max
    comment: str | None = None


@router.get("/unf/status")
async def unf_status():
    return await unf.status()


@router.post("/orders")
async def create_order(payload: OrderIn):
    """Создаёт заказ, контрагента/Заказ/Счёт в 1С:УНФ (прямая интеграция) и печатную форму."""
    if not payload.items:
        raise HTTPException(400, "empty order")
    await unf.ensure_migrated()

    # --- позиции + цены из offers ---
    items, total = [], 0.0
    for it in payload.items:
        p = await db.fetchone(
            """SELECT p.id, p.manufacturer_article AS article, p.name,
                      (SELECT price FROM offers o WHERE o.product_id=p.id AND o.price IS NOT NULL
                       ORDER BY o.in_stock DESC, o.price ASC LIMIT 1) AS price
               FROM products p WHERE p.id=%s""", (it.product_id,))
        if not p:
            raise HTTPException(404, f"product {it.product_id} not found")
        price = float(p["price"]) if p["price"] is not None else 0.0
        total += price * it.qty
        items.append({"product_id": p["id"], "article": p["article"], "name": p["name"],
                      "qty": it.qty, "price": price})

    # --- клиент ---
    c = payload.customer
    cust = await db.fetchone(
        """INSERT INTO customers (kind, name, phone, email, org_name, inn, kpp)
           VALUES (%s,%s,%s,%s,%s,%s,%s) RETURNING *""",
        (c.kind, c.name, c.phone, c.email, c.org_name, c.inn, c.kpp))

    # --- заказ ---
    order = await db.fetchone(
        """INSERT INTO orders (customer_id, status, total, channel)
           VALUES (%s,'new',%s,%s) RETURNING *""",
        (cust["id"], total, payload.channel))
    async with db.pool.connection() as conn:
        for i in items:
            await conn.execute(
                """INSERT INTO order_items (order_id, product_id, article, name, qty, price)
                   VALUES (%s,%s,%s,%s,%s,%s)""",
                (order["id"], i["product_id"], i["article"], i["name"], i["qty"], i["price"]))

    # --- прямая интеграция 1С:УНФ ---
    cp_ref = await unf.upsert_counterparty(dict(cust))
    unf_order = await unf.create_customer_order(dict(order), items, cp_ref)
    invoice = await unf.create_invoice(dict(order), unf_order["ref"])
    pf = await unf.get_printed_form(invoice["ref"], "СчетНаОплату")

    docs = []
    for kind, ref in (("order", unf_order), ("invoice", invoice)):
        pdf = pf if kind == "invoice" else None
        d = await db.fetchone(
            """INSERT INTO documents (order_id, kind, unf_ref, unf_number, pf_pdf, status)
               VALUES (%s,%s,%s,%s,%s,'created') RETURNING id, kind, unf_number""",
            (order["id"], kind, ref["ref"], ref["number"], pdf))
        docs.append(d)

    await db.fetchone("UPDATE orders SET order_1c_id=%s WHERE id=%s RETURNING id",
                      (unf_order["ref"], order["id"]))

    # --- оплата: физлицам — эквайринг (ЮKassa/Т-Банк), юрлицам — счёт из 1С ---
    payment = None
    if c.kind == "person":
        payment = await payments.create_payment(order["id"], total, f"Заказ №{order['id']} СПЕЦИНТЕР")

    return {
        "order_id": order["id"], "total": total, "channel": payload.channel,
        "unf": {"mode": "dry-run" if unf.DRY_RUN else "live",
                "order_number": unf_order["number"], "invoice_number": invoice["number"]},
        "documents": docs,
        "invoice_pdf_url": f"/api/orders/{order['id']}/invoice.pdf",
        "payment": payment,
    }


@router.get("/orders/{order_id}")
async def get_order(order_id: int):
    o = await db.fetchone("SELECT * FROM orders WHERE id=%s", (order_id,))
    if not o:
        raise HTTPException(404, "order not found")
    o["items"] = await db.fetch("SELECT article, name, qty, price FROM order_items WHERE order_id=%s", (order_id,))
    o["documents"] = await db.fetch(
        "SELECT id, kind, unf_ref, unf_number, status, edo_status FROM documents WHERE order_id=%s", (order_id,))
    o["payments"] = await db.fetch(
        "SELECT id, provider, external_id, amount, status, confirmation_url FROM payments WHERE order_id=%s", (order_id,))
    return o


@router.post("/webhook/payment/yookassa")
async def yookassa_webhook(request: Request):
    data = await request.json()
    obj = data.get("object", {})
    ext, st = obj.get("id"), obj.get("status")
    mapping = {"succeeded": "succeeded", "canceled": "canceled", "waiting_for_capture": "pending"}
    if ext and st:
        await payments.set_status(ext, mapping.get(st, "pending"))
    return {"ok": True}


@router.post("/webhook/payment/tinkoff")
async def tinkoff_webhook(request: Request):
    data = await request.json()
    ext, st = str(data.get("PaymentId", "")), data.get("Status")
    mapping = {"CONFIRMED": "succeeded", "AUTHORIZED": "succeeded",
               "REJECTED": "canceled", "CANCELED": "canceled"}
    if ext and st:
        await payments.set_status(ext, mapping.get(st, "pending"))
    return "OK"


@router.post("/payments/{external_id}/simulate")
async def simulate_payment(external_id: str, status: str = "succeeded"):
    """Только для dry-run/тестов: эмулирует колбэк провайдера."""
    await payments.set_status(external_id, status)
    return {"external_id": external_id, "status": status}


@router.get("/orders/{order_id}/invoice.pdf")
async def order_invoice_pdf(order_id: int):
    d = await db.fetchone(
        """SELECT pf_pdf FROM documents WHERE order_id=%s AND kind='invoice' AND pf_pdf IS NOT NULL
           ORDER BY id DESC LIMIT 1""", (order_id,))
    if not d or d["pf_pdf"] is None:
        raise HTTPException(404, "печатная форма недоступна")
    return Response(content=bytes(d["pf_pdf"]), media_type="application/pdf",
                    headers={"Content-Disposition": f'inline; filename="schet-{order_id}.pdf"'})
