"""Скиллы ИИ-оператора. Каждый скилл — обёртка над нашим API с ВЫЧИСТКОЙ
конфиденциальных полей (поставщики, закупочные данные наружу не уходят)."""
import httpx
from langchain_core.tools import tool
from .config import BOT_API_URL

_client = httpx.AsyncClient(base_url=BOT_API_URL, timeout=20)


def _public_offer(price, in_stock):
    """Наружу — только розничная цена, наличие и срок. Ни поставщика, ни закупки."""
    return {
        "price_rub": round(price) if price else None,
        "availability": "в наличии" if in_stock else "под заказ",
    }


@tool
async def search_catalog(query: str) -> list[dict]:
    """Поиск запчастей на сайте по артикулу производителя, аналогу или названию.
    Возвращает список товаров: id, артикул, название, розничная цена, наличие.
    Всегда используй этот скилл, прежде чем говорить о наличии или цене."""
    r = await _client.get("/api/search", params={"q": query, "limit": 8})
    if r.status_code != 200:
        return [{"error": "поиск временно недоступен"}]
    out = []
    for h in r.json().get("hits", []):
        out.append({
            "product_id": h["id"],
            "article": h.get("manufacturer_article"),
            "name": h.get("name"),
            **_public_offer(h.get("min_price"), h.get("in_stock")),
        })
    return out or [{"note": "ничего не найдено — предложи оставить заявку или связать с оператором"}]


@tool
async def find_by_article(article: str) -> list[dict]:
    """Точный подбор товара по артикулу производителя, включая аналоги (кросс-номера).
    Используй, когда клиент дал конкретный артикул."""
    r = await _client.get(f"/api/products/by-article/{article}")
    if r.status_code != 200:
        return [{"error": "подбор недоступен"}]
    res = []
    for x in r.json().get("results", []):
        res.append({"product_id": x["id"], "article": x["manufacturer_article"],
                    "name": x["name"], "match": x["match"]})
    return res or [{"note": "по артикулу ничего не найдено"}]


@tool
async def product_details(product_id: int) -> dict:
    """Карточка товара: название, артикул, розничная цена, наличие, список аналогов.
    Конфиденциальные данные (поставщики, закупка) не возвращаются."""
    r = await _client.get(f"/api/products/{product_id}")
    if r.status_code != 200:
        return {"error": "товар не найден"}
    p = r.json()
    offers = p.get("offers", [])
    best = None
    for o in offers:
        if o.get("price") is not None and (best is None or o["price"] < best["price"]):
            best = o
    return {
        "product_id": p["id"],
        "article": p.get("manufacturer_article"),
        "name": p.get("name"),
        **_public_offer(best["price"] if best else None, best["in_stock"] if best else False),
        "analogs": [a.get("analog_article") for a in p.get("analogs", [])][:10],
    }


@tool
async def delivery_estimate(product_id: int, city: str) -> dict:
    """Срок доставки конкретного товара до города клиента (учитывает наличие: в наличии —
    только отгрузка; под заказ — пополнение со склада поставщика + отгрузка)."""
    r = await _client.get(f"/api/products/{product_id}/eta", params={"city": city})
    if r.status_code != 200:
        return {"note": "срок уточняется менеджером"}
    d = r.json()
    return {"city": city, "eta": d.get("eta_label"), "stage": d.get("stage"),
            "note": "ориентировочно, рабочих дней"}


@tool
async def create_order(product_id: int, quantity: int, customer_name: str,
                       phone: str, is_legal: bool = False, inn: str = "",
                       org_name: str = "", channel: str = "telegram", contact_ref: str = "") -> dict:
    """Оформить заказ. ВЫЗЫВАЙ ТОЛЬКО ПОСЛЕ явного подтверждения клиентом состава и суммы.
    Для юрлица укажи is_legal=true, inn и org_name (для счёта из 1С). channel/contact_ref —
    канал связи клиента (telegram|whatsapp|max|email|phone) для обратной связи."""
    payload = {
        "customer": {"name": customer_name, "phone": phone,
                     "kind": "legal" if is_legal else "person",
                     "inn": inn or None, "org_name": org_name or None,
                     "contact_channel": channel, "contact_ref": contact_ref or phone},
        "channel": channel,
        "items": [{"product_id": product_id, "qty": quantity}],
    }
    r = await _client.post("/api/orders", json=payload)
    if r.status_code != 200:
        return {"error": "не удалось оформить заказ, предложи связать с оператором"}
    d = r.json()
    return {"order_id": d["order_id"], "total_rub": round(d["total"]),
            "status": "оформлен", "invoice": "счёт сформирован" if is_legal else None}


@tool
async def request_quote(query: str, customer_name: str, phone: str, comment: str = "",
                        channel: str = "telegram", contact_ref: str = "") -> dict:
    """Оформить заявку на подбор/запрос цены (позиции нет в каталоге ИЛИ нет цены).
    Заявка обрабатывается АВТОМАТИЧЕСКИ: возвращается готовый ответ клиенту с наличием,
    ценой и сроком (если нашли) — сразу озвучь его клиенту. contact_ref — ник/номер канала."""
    r = await _client.post("/api/quote-requests", json={
        "query": query, "name": customer_name, "phone": phone, "comment": comment,
        "channel": channel, "contact_ref": contact_ref or phone, "auto_process": True})
    if r.status_code != 200:
        return {"error": "не удалось оформить заявку"}
    d = r.json()
    return {"accepted": True, "quote_id": d.get("id"), "status": d.get("status"),
            "reply_to_client": d.get("response") or "Заявка принята — подберём и свяжемся с вами."}


@tool
async def process_quote(quote_id: int) -> dict:
    """Пересчитать/обновить ответ по ранее созданной заявке (актуальные наличие/цена/срок).
    Возвращает текст для клиента."""
    r = await _client.post(f"/api/quote-requests/{quote_id}/process")
    if r.status_code != 200:
        return {"error": "заявка не найдена"}
    d = r.json()
    return {"quote_id": quote_id, "status": d.get("status"), "reply_to_client": d.get("response")}


@tool
async def request_operator(reason: str) -> dict:
    """Передать диалог живому оператору. Используй при явной просьбе клиента, нестандартной
    ситуации или если не уверен. Оператор подключается только в крайних случаях."""
    # TODO: интеграция с очередью операторов/уведомлением. Пока — маркер эскалации.
    return {"handoff": True, "reason": reason,
            "message": "Передаю ваш вопрос менеджеру, он скоро подключится."}


ALL_TOOLS = [search_catalog, find_by_article, product_details,
             delivery_estimate, create_order, request_quote, process_quote, request_operator]
