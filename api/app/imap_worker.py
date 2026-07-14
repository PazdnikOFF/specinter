"""Приём прайсов с почты. Опрашивает IMAP-ящик, сопоставляет отправителя с
поставщиком (suppliers.sender_email), парсит XLSX-вложения и прогоняет через
конвейер prices.ingest. Env-gated: без IMAP-кредов просто простаивает.

Запуск: python -m app.imap_worker  (docker-сервис worker).
"""
import asyncio
import email
import imaplib
import os
from email.header import decode_header

from . import db, prices, search

IMAP_HOST = os.environ.get("IMAP_HOST")
IMAP_USER = os.environ.get("IMAP_USER")
IMAP_PASSWORD = os.environ.get("IMAP_PASSWORD")
IMAP_FOLDER = os.environ.get("IMAP_FOLDER", "INBOX")
POLL_INTERVAL = int(os.environ.get("POLL_INTERVAL", "300"))


def _sender(msg) -> str:
    frm = msg.get("From", "")
    if "<" in frm and ">" in frm:
        frm = frm.split("<", 1)[1].split(">", 1)[0]
    return frm.strip().lower()


def _fname(part) -> str:
    name = part.get_filename() or ""
    if name:
        dh = decode_header(name)[0]
        if isinstance(dh[0], bytes):
            name = dh[0].decode(dh[1] or "utf-8", "ignore")
    return name


async def process_once():
    if not (IMAP_HOST and IMAP_USER and IMAP_PASSWORD):
        return  # не сконфигурировано — тихо простаиваем
    imap = imaplib.IMAP4_SSL(IMAP_HOST)
    imap.login(IMAP_USER, IMAP_PASSWORD)
    imap.select(IMAP_FOLDER)
    typ, data = imap.search(None, "UNSEEN")
    if typ != "OK":
        imap.logout()
        return
    for num in data[0].split():
        typ, msg_data = imap.fetch(num, "(RFC822)")
        if typ != "OK":
            continue
        msg = email.message_from_bytes(msg_data[0][1])
        sender = _sender(msg)
        sup = await db.fetchone(
            "SELECT id FROM suppliers WHERE lower(sender_email)=%s AND active", (sender,))
        if not sup:
            print(f"[imap] отправитель без поставщика: {sender} — пропуск")
            continue
        profile = await db.fetchone(
            "SELECT * FROM supplier_price_profiles WHERE supplier_id=%s ORDER BY id LIMIT 1",
            (sup["id"],))
        for part in msg.walk():
            name = _fname(part)
            if name.lower().endswith((".xlsx", ".xls")):
                payload = part.get_payload(decode=True)
                try:
                    rows = prices.parse(payload, name, profile or {})
                    res = await prices.ingest(sup["id"], rows)
                    await prices.backfill_brands()
                    await prices.backfill_dimensions()
                    try:
                        await search.reindex()
                    except Exception as e:
                        print(f"[imap] reindex failed: {e}")
                    print(f"[imap] {sender} → supplier {sup['id']}: {res}")
                except Exception as e:
                    print(f"[imap] ошибка разбора {name}: {e}")
        imap.store(num, "+FLAGS", "\\Seen")
    imap.logout()


async def main():
    await db.pool.open()
    configured = bool(IMAP_HOST and IMAP_USER and IMAP_PASSWORD)
    print(f"[imap] worker запущен. IMAP {'настроен' if configured else 'НЕ настроен (простой)'}, "
          f"интервал {POLL_INTERVAL}s")
    while True:
        try:
            await process_once()
        except Exception as e:
            print(f"[imap] цикл: {e}")
        await asyncio.sleep(POLL_INTERVAL)


if __name__ == "__main__":
    asyncio.run(main())
