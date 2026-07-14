"""Канал WhatsApp. Два транспорта на выбор (WA_TRANSPORT):
  * cloud   — официальный WhatsApp Cloud API (webhook /webhook/whatsapp);
  * bridge  — мост WhatsApp Web (Baileys) во внешнем сервисе, шлёт сюда вебхуком.
Ядро-агент одно; здесь только транспорт. Env-gated → dry-run без токена."""
import os
import httpx
from ..agent import reply

WA_TRANSPORT = os.environ.get("WA_TRANSPORT", "cloud")
WA_TOKEN = os.environ.get("WA_TOKEN", "")
WA_PHONE_ID = os.environ.get("WA_PHONE_ID", "")
WA_BRIDGE_URL = os.environ.get("WA_BRIDGE_URL", "")  # для transport=bridge (Baileys)
GRAPH = os.environ.get("WA_GRAPH_URL", "https://graph.facebook.com/v21.0")


async def handle_message(from_id: str, text: str):
    answer = await reply(f"wa-{from_id}", text)
    if not (WA_TOKEN or WA_BRIDGE_URL):
        print(f"[whatsapp] DRY-RUN → {from_id}: {answer[:80]}")
        return
    async with httpx.AsyncClient(timeout=20) as client:
        if WA_TRANSPORT == "bridge" and WA_BRIDGE_URL:
            await client.post(f"{WA_BRIDGE_URL}/send", json={"to": from_id, "text": answer})
        else:
            await client.post(
                f"{GRAPH}/{WA_PHONE_ID}/messages",
                params={"access_token": WA_TOKEN},
                json={"messaging_product": "whatsapp", "to": from_id,
                      "type": "text", "text": {"body": answer}})
