"""Канал Telegram — long polling через Bot API (без внешних зависимостей, httpx)."""
import asyncio
import httpx
from ..agent import reply
from ..config import TELEGRAM_TOKEN

API = f"https://api.telegram.org/bot{TELEGRAM_TOKEN}"


async def _send(client, chat_id, text):
    await client.post(f"{API}/sendMessage", json={"chat_id": chat_id, "text": text})


async def poll():
    if not TELEGRAM_TOKEN:
        print("[telegram] TELEGRAM_TOKEN не задан — канал выключен")
        return
    print("[telegram] канал запущен (long polling)")
    offset = 0
    async with httpx.AsyncClient(timeout=40) as client:
        while True:
            try:
                r = await client.get(f"{API}/getUpdates",
                                     params={"offset": offset, "timeout": 30})
                for u in r.json().get("result", []):
                    offset = u["update_id"] + 1
                    msg = u.get("message") or {}
                    text = msg.get("text")
                    chat_id = (msg.get("chat") or {}).get("id")
                    if not text or chat_id is None:
                        continue
                    try:
                        answer = await reply(f"tg-{chat_id}", text)
                    except Exception as e:
                        answer = "Извините, произошла ошибка. Попробуйте ещё раз."
                        print(f"[telegram] agent error: {e}")
                    await _send(client, chat_id, answer)
            except Exception as e:
                print(f"[telegram] poll error: {e}")
                await asyncio.sleep(3)
