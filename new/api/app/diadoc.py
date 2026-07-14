"""ЭДО Контур.Диадок — получение закрывающих документов (УПД/накладные/акты).
Env-gated: без DIADOC_* работает в DRY-RUN. Полученные документы сохраняются в `documents`
(kind='upd', edo_status), при совпадении ИНН контрагента привязываются к заказу."""
import os
import httpx
from . import db

BASE = os.environ.get("DIADOC_BASE", "https://diadoc-api.kontur.ru")
API_CLIENT_ID = os.environ.get("DIADOC_API_CLIENT_ID", "")
TOKEN = os.environ.get("DIADOC_TOKEN", "")          # выданный авторизационный токен
BOX_ID = os.environ.get("DIADOC_BOX_ID", "")        # ящик организации

DRY_RUN = not (API_CLIENT_ID and TOKEN and BOX_ID)


def _headers():
    return {"Authorization": f"DiadocAuth ddauth_api_client_id={API_CLIENT_ID}, ddauth_token={TOKEN}",
            "Accept": "application/json"}


async def status() -> dict:
    if DRY_RUN:
        return {"configured": False, "mode": "dry-run",
                "hint": "заполните DIADOC_API_CLIENT_ID/DIADOC_TOKEN/DIADOC_BOX_ID"}
    async with httpx.AsyncClient(timeout=30) as c:
        r = await c.get(f"{BASE}/GetMyOrganizations", headers=_headers())
        return {"configured": True, "mode": "live", "status": r.status_code}


async def fetch_new() -> list[dict]:
    """Список новых входящих документов (закрывающих) из Диадок."""
    if DRY_RUN:
        return []
    async with httpx.AsyncClient(timeout=60) as c:
        r = await c.get(f"{BASE}/V3/GetNewEvents",
                        params={"boxId": BOX_ID}, headers=_headers())
        r.raise_for_status()
        docs = []
        for ev in r.json().get("Events", []):
            msg = ev.get("Message", {})
            for entity in msg.get("Entities", []):
                if entity.get("EntityType") == "Attachment":
                    docs.append({
                        "message_id": msg.get("MessageId"),
                        "entity_id": entity.get("EntityId"),
                        "type": entity.get("AttachmentType"),
                        "counterparty_inn": (msg.get("FromBoxId") or ""),
                        "number": (entity.get("DocumentInfo") or {}).get("DocumentNumber"),
                    })
        return docs


async def sync() -> dict:
    """Забирает новые закрывающие документы и сохраняет в `documents`."""
    await _ensure_documents()
    new = await fetch_new()
    saved = 0
    for d in new:
        # привязка к заказу по ИНН контрагента (если найдётся)
        order = await db.fetchone(
            """SELECT o.id FROM orders o JOIN customers c ON c.id=o.customer_id
               WHERE c.inn = %s ORDER BY o.created_at DESC LIMIT 1""",
            (d.get("counterparty_inn"),))
        await db.fetchone(
            """INSERT INTO documents (order_id, kind, unf_number, status, edo_status)
               VALUES (%s,'upd',%s,'received','received') RETURNING id""",
            (order["id"] if order else None, d.get("number")))
        saved += 1
    return {"fetched": len(new), "saved": saved, "mode": "dry-run" if DRY_RUN else "live"}


async def _ensure_documents():
    # таблица documents создаётся модулем unf; на всякий случай гарантируем наличие
    from . import unf
    await unf.ensure_migrated()
