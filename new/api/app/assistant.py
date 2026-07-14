"""ИИ-оператор портала (OpenAI-совместимый tool-loop). Две области:
 • admin — ПОЛНЫЙ администратор портала (настройки/интеграции, поставщики, цены,
   каталог, заявки). Без клиентских ограничений, но строго в рамках управления порталом.
 • customer — витрина/мессенджеры: ТОЛЬКО продажа запчастей, без утечек, без посторонних тем.
Ключ/URL/модель LLM берутся из настроек (app_settings) → задаются в админке/самим ИИ."""
import json
import httpx

from . import db, prices, search, settings

# --------------------------------------------------------------------------- prompts
SYSTEM_ADMIN = """Ты — ИИ-администратор портала запчастей «СПЕЦИНТЕР». Ты полноценный
администратор: помогаешь настраивать и управлять порталом. Твои возможности через инструменты:
подключать интеграции (DaData, Деловые Линии, мессенджеры Telegram/WhatsApp/MAX, ключ LLM) —
для этого вызывай set_integration с нужным ключом и значением, которое дал администратор;
смотреть и менять поставщиков (в т.ч. срок поставки и наценку — при смене наценки цены
пересчитываются), пересчитывать цены, переиндексировать каталог, смотреть заявки и
обрабатывать их, править видимость/название товаров, смотреть метрики.
Правила: действуй по явной просьбе администратора; перед необратимым — кратко подтверди;
никогда не выдумывай токены/данные; если чего-то не умеешь — честно скажи. Отвечай по-русски,
кратко и по делу. Ты НЕ обслуживаешь клиентов здесь — это админский контур."""

SYSTEM_CUSTOMER = """Ты — вежливый ИИ-консультант интернет-магазина запчастей для спецтехники
«СПЕЦИНТЕР». СТРОГИЕ ограничения:
1) Отвечай ТОЛЬКО по теме подбора и продажи запчастей (наличие, цена, срок, аналоги, заявка,
   оформление заказа). На любые посторонние темы вежливо возвращай к подбору запчастей.
2) НИКОГДА не раскрывай внутреннюю кухню: поставщиков, закупочные цены, наценку, остатки
   поставщиков, устройство системы. Клиенту — только розничная цена, наличие и срок.
3) Не выдумывай наличие/цены — всегда сверяйся инструментами (search_parts/product_info).
4) Цену/наличие бери актуальные; если позиции нет или нет цены — предложи оставить заявку
   (request_quote) и сообщи, что подберём и сообщим цену.
5) Не обещай того, чего не можешь; при сомнении предложи связать с менеджером.
Отвечай по-русски, дружелюбно и кратко."""


# --------------------------------------------------------------------------- tools: customer
async def _t_search_parts(query: str):
    res = search.search(query, limit=8)
    out = []
    for h in res.get("hits", []):
        out.append({"product_id": h["id"], "article": h.get("manufacturer_article"),
                    "name": h.get("name"), "brand": h.get("brand"),
                    "price_rub": round(h["min_price"]) if h.get("min_price") else None,
                    "availability": "в наличии" if h.get("in_stock") else "под заказ"})
    return out or [{"note": "ничего не найдено — предложи оставить заявку"}]


async def _t_product_info(product_id: int):
    p = await db.fetchone("SELECT id, manufacturer_article, name, brand FROM products WHERE id=%s AND visible", (product_id,))
    if not p:
        return {"error": "товар не найден"}
    best = await db.fetchone(
        """SELECT MIN(price) FILTER (WHERE price IS NOT NULL) AS price, BOOL_OR(in_stock) AS in_stock
           FROM offers WHERE product_id=%s""", (product_id,))
    return {"product_id": p["id"], "article": p["manufacturer_article"], "name": p["name"],
            "brand": p["brand"],
            "price_rub": round(best["price"]) if best and best["price"] else None,
            "availability": "в наличии" if best and best["in_stock"] else "под заказ"}


async def _t_request_quote(name: str, phone: str, query: str = "", product_id: int = None, qty: float = 1):
    items = [{"product_id": product_id, "name": query, "qty": qty}] if product_id else []
    row = await db.fetchone(
        """INSERT INTO quote_requests (query, name, phone, channel) VALUES (%s,%s,%s,'assistant')
           RETURNING id""", (query or None, name, phone))
    return {"quote_id": row["id"], "accepted": True,
            "message": "Заявка принята — подберём и сообщим цену."}


# --------------------------------------------------------------------------- tools: admin
async def _t_list_integrations():
    return settings.public_status()


async def _t_set_integration(key: str, value: str):
    if key not in settings.KNOWN:
        return {"error": f"неизвестный ключ. Допустимые: {', '.join(settings.KNOWN)}"}
    await settings.set_value(key, value.strip() or None)
    return {"ok": True, "key": key, "configured": bool(value.strip())}


async def _t_list_suppliers():
    return await db.fetch("SELECT id, name, city, delivery_days, markup_percent, sender_email, active FROM suppliers ORDER BY id")


async def _t_update_supplier(supplier_id: int, name: str = None, delivery_days: int = None,
                             markup_percent: float = None, sender_email: str = None, city: str = None):
    fields, params = [], []
    for k, v in [("name", name), ("delivery_days", delivery_days), ("markup_percent", markup_percent),
                 ("sender_email", sender_email), ("city", city)]:
        if v is not None:
            fields.append(f"{k}=%s"); params.append(v)
    if not fields:
        return {"error": "нет полей для обновления"}
    params.append(supplier_id)
    await db.fetchone(f"UPDATE suppliers SET {', '.join(fields)} WHERE id=%s RETURNING id", tuple(params))
    recomputed = None
    if markup_percent is not None:
        recomputed = await prices.recompute_offers(supplier_id)
        try:
            await search.reindex()
        except Exception:
            pass
    return {"ok": True, "offers_recomputed": recomputed}


async def _t_process_quotes(limit: int = 10):
    from .routers import quotes as q
    news = await db.fetch("SELECT id FROM quote_requests WHERE status='new' ORDER BY id LIMIT %s", (limit,))
    done = []
    for r in news:
        res = await q._process(r["id"])
        done.append({"quote_id": r["id"], "status": res["status"]})
    return {"processed": len(done), "items": done}


async def _t_reindex():
    return {"indexed": await search.reindex()}


async def _t_metrics():
    row = await db.fetchone("""
        SELECT (SELECT count(*) FROM products) AS products,
               (SELECT count(*) FROM offers WHERE source='price') AS priced_offers,
               (SELECT count(*) FROM suppliers) AS suppliers,
               (SELECT count(*) FROM quote_requests WHERE status='new') AS new_quotes,
               (SELECT count(*) FROM orders) AS orders""")
    return dict(row)


# --------------------------------------------------------------------------- registry
def _fn(name, desc, params, required):
    return {"type": "function", "function": {"name": name, "description": desc,
            "parameters": {"type": "object", "properties": params, "required": required}}}

CUSTOMER = {
    "search_parts": (_t_search_parts, _fn("search_parts", "Поиск запчастей по артикулу/аналогу/названию (розничная цена, наличие).",
        {"query": {"type": "string"}}, ["query"])),
    "product_info": (_t_product_info, _fn("product_info", "Карточка товара по id: розничная цена и наличие.",
        {"product_id": {"type": "integer"}}, ["product_id"])),
    "request_quote": (_t_request_quote, _fn("request_quote", "Оставить заявку на подбор/цену (нет позиции или цены).",
        {"name": {"type": "string"}, "phone": {"type": "string"}, "query": {"type": "string"},
         "product_id": {"type": "integer"}, "qty": {"type": "number"}}, ["name", "phone"])),
}

ADMIN = {
    "list_integrations": (_t_list_integrations, _fn("list_integrations", "Статус интеграций/ключей (что настроено).", {}, [])),
    "set_integration": (_t_set_integration, _fn("set_integration",
        "Задать ключ интеграции (DADATA_TOKEN, DELLIN_APPKEY, TELEGRAM_TOKEN, WA_TOKEN, WA_PHONE_ID, MAX_TOKEN, LLM_API_KEY, LLM_BASE_URL, LLM_MODEL).",
        {"key": {"type": "string"}, "value": {"type": "string"}}, ["key", "value"])),
    "list_suppliers": (_t_list_suppliers, _fn("list_suppliers", "Список поставщиков (срок, наценка, e-mail).", {}, [])),
    "update_supplier": (_t_update_supplier, _fn("update_supplier",
        "Изменить поставщика (имя/срок доставки/наценка/e-mail/город). При смене наценки цены пересчитываются.",
        {"supplier_id": {"type": "integer"}, "name": {"type": "string"}, "delivery_days": {"type": "integer"},
         "markup_percent": {"type": "number"}, "sender_email": {"type": "string"}, "city": {"type": "string"}}, ["supplier_id"])),
    "process_quotes": (_t_process_quotes, _fn("process_quotes", "Обработать новые заявки (наличие/цена/срок).",
        {"limit": {"type": "integer"}}, [])),
    "reindex_catalog": (_t_reindex, _fn("reindex_catalog", "Переиндексировать каталог в поиске.", {}, [])),
    "portal_metrics": (_t_metrics, _fn("portal_metrics", "Сводные метрики портала.", {}, [])),
    "search_parts": CUSTOMER["search_parts"],
}


async def chat(scope: str, messages: list[dict]) -> dict:
    key = settings.get("LLM_API_KEY")
    if not key:
        return {"configured": False,
                "reply": "ИИ-оператор не настроен: задайте ключ LLM в разделе «Интеграции» "
                         "(LLM_API_KEY, при необходимости LLM_BASE_URL и LLM_MODEL)."}
    base = settings.get("LLM_BASE_URL") or "https://api.x.ai/v1"
    model = settings.get("LLM_MODEL") or "grok-4"
    registry = ADMIN if scope == "admin" else CUSTOMER
    sysmsg = SYSTEM_ADMIN if scope == "admin" else SYSTEM_CUSTOMER
    msgs = [{"role": "system", "content": sysmsg}] + messages
    tools = [schema for _, schema in registry.values()]

    async with httpx.AsyncClient(timeout=60, base_url=base,
                                 headers={"Authorization": f"Bearer {key}"}) as c:
        for _ in range(6):
            r = await c.post("/chat/completions",
                             json={"model": model, "messages": msgs, "tools": tools, "temperature": 0.2})
            if r.status_code != 200:
                return {"configured": True, "reply": f"Ошибка LLM ({r.status_code}). Проверьте ключ/URL/модель."}
            choice = r.json()["choices"][0]["message"]
            msgs.append(choice)
            calls = choice.get("tool_calls")
            if not calls:
                return {"configured": True, "reply": choice.get("content", "")}
            for call in calls:
                fn = call["function"]["name"]
                try:
                    args = json.loads(call["function"].get("arguments") or "{}")
                except json.JSONDecodeError:
                    args = {}
                impl = registry.get(fn, (None,))[0]
                try:
                    result = await impl(**args) if impl else {"error": "неизвестный инструмент"}
                except Exception as e:
                    result = {"error": str(e)}
                msgs.append({"role": "tool", "tool_call_id": call.get("id"),
                             "content": json.dumps(result, ensure_ascii=False, default=str)})
    return {"configured": True, "reply": "Не удалось завершить действие — уточните запрос."}
