"""Канал MAX (российский мессенджер, botapi.max.ru) — long polling.
Env-gated: без MAX_TOKEN выключен. API близок к Telegram по модели."""
import asyncio
import os
import httpx
from ..agent import reply

MAX_TOKEN = os.environ.get("MAX_TOKEN", "")
BASE = os.environ.get("MAX_API_URL", "https://botapi.max.ru")


async def poll():
    if not MAX_TOKEN:
        print("[max] MAX_TOKEN не задан — канал выключен")
        return
    print("[max] канал запущен (long polling)")
    marker = None
    async with httpx.AsyncClient(timeout=40, base_url=BASE) as client:
        while True:
            try:
                params = {"access_token": MAX_TOKEN, "timeout": 30}
                if marker:
                    params["marker"] = marker
                r = await client.get("/updates", params=params)
                data = r.json()
                marker = data.get("marker", marker)
                for u in data.get("updates", []):
                    if u.get("update_type") != "message_created":
                        continue
                    msg = u.get("message", {})
                    text = (msg.get("body") or {}).get("text")
                    chat_id = (msg.get("recipient") or {}).get("chat_id") \
                        or (msg.get("sender") or {}).get("user_id")
                    if not text or chat_id is None:
                        continue
                    answer = await reply(f"max-{chat_id}", text)
                    await client.post("/messages",
                                      params={"access_token": MAX_TOKEN, "chat_id": chat_id},
                                      json={"text": answer})
            except Exception as e:
                print(f"[max] poll error: {e}")
                await asyncio.sleep(3)
