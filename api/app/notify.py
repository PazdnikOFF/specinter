"""Уведомления «товар поступил в наличие» (перенос legacy it_b_notify + notifyletter.php).

Клиент подписывается на карточке товара (POST /api/products/{id}/stock-alert),
здесь мы находим подписки, по товарам которых появилось предложение в наличии,
шлём письмо и закрываем подписку (notified_at).

SMTP не настроен → функция ничего не шлёт и молча ждёт настроек, как imap_worker.
"""
import os
import smtplib
from email.message import EmailMessage

from . import db

SMTP_HOST = os.environ.get("SMTP_HOST")
SMTP_PORT = int(os.environ.get("SMTP_PORT", "465"))
SMTP_USER = os.environ.get("SMTP_USER")
SMTP_PASSWORD = os.environ.get("SMTP_PASSWORD")
SMTP_FROM = os.environ.get("SMTP_FROM") or SMTP_USER
SITE_URL = os.environ.get("SITE_URL", "https://specinter.ru").rstrip("/")

# Разом за один проход, чтобы не упереться в лимиты отправки почтового провайдера.
BATCH = int(os.environ.get("STOCK_ALERT_BATCH", "50"))


def configured() -> bool:
    return bool(SMTP_HOST and SMTP_USER and SMTP_PASSWORD)


def _send(to: str, subject: str, body: str) -> None:
    msg = EmailMessage()
    msg["From"] = SMTP_FROM
    msg["To"] = to
    msg["Subject"] = subject
    msg.set_content(body)
    if SMTP_PORT == 465:
        with smtplib.SMTP_SSL(SMTP_HOST, SMTP_PORT, timeout=30) as s:
            s.login(SMTP_USER, SMTP_PASSWORD)
            s.send_message(msg)
    else:
        with smtplib.SMTP(SMTP_HOST, SMTP_PORT, timeout=30) as s:
            s.starttls()
            s.login(SMTP_USER, SMTP_PASSWORD)
            s.send_message(msg)


async def dispatch_stock_alerts() -> int:
    """Разослать письма по подпискам, товары которых появились в наличии.

    Возвращает число отправленных писем. Подписка закрывается ТОЛЬКО после
    успешной отправки — при сбое SMTP она останется и уйдёт в следующий проход.
    """
    if not configured():
        return 0
    rows = await db.fetch(
        """SELECT sa.id, sa.email, sa.name, p.id AS product_id,
                  p.manufacturer_article, p.name AS product_name,
                  (SELECT MIN(o.price) FROM offers o
                     WHERE o.product_id = p.id AND o.price IS NOT NULL) AS price
           FROM stock_alerts sa
           JOIN products p ON p.id = sa.product_id AND p.visible
           WHERE sa.notified_at IS NULL
             AND EXISTS (SELECT 1 FROM offers o
                          WHERE o.product_id = p.id AND o.in_stock)
           ORDER BY sa.created_at
           LIMIT %s""",
        (BATCH,))
    sent = 0
    for r in rows:
        title = " ".join(x for x in (r["manufacturer_article"], r["product_name"]) if x)
        price = f"\nЦена: {round(float(r['price']))} ₽" if r["price"] is not None else ""
        greeting = f"{r['name']}, здравствуйте!" if r["name"] else "Здравствуйте!"
        body = (
            f"{greeting}\n\n"
            f"Товар, на который вы подписались, появился в наличии:\n\n"
            f"{title}{price}\n\n"
            f"Карточка товара: {SITE_URL}/product/{r['product_id']}\n\n"
            f"С уважением, СПЕЦИНТЕР"
        )
        try:
            _send(r["email"], f"{title} — в наличии", body)
        except Exception as e:
            print(f"[notify] не удалось отправить на {r['email']}: {e}")
            continue
        await db.fetchone(
            "UPDATE stock_alerts SET notified_at = now() WHERE id = %s RETURNING id", (r["id"],))
        sent += 1
    if sent:
        print(f"[notify] отправлено уведомлений о поступлении: {sent}")
    return sent
