"""Канал Instagram Direct — через Messenger/Instagram Graph API (webhook).
Входящие DM приходят на вебхук (см. main.py: /webhook/instagram), исходящие шлём в Graph API.
Env-gated: без IG_ACCESS_TOKEN отправка в dry-run."""
import os
import httpx
from ..agent import reply

IG_ACCESS_TOKEN = os.environ.get("IG_ACCESS_TOKEN", "")
GRAPH = os.environ.get("IG_GRAPH_URL", "https://graph.facebook.com/v21.0")


async def handle_dm(sender_id: str, text: str):
    """Обработать входящее сообщение Instagram Direct и ответить."""
    answer = await reply(f"ig-{sender_id}", text)
    if not IG_ACCESS_TOKEN:
        print(f"[instagram] DRY-RUN → {sender_id}: {answer[:80]}")
        return
    async with httpx.AsyncClient(timeout=20) as client:
        await client.post(
            f"{GRAPH}/me/messages",
            params={"access_token": IG_ACCESS_TOKEN},
            json={"recipient": {"id": sender_id}, "message": {"text": answer}})
