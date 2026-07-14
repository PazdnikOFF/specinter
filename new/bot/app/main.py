"""Точка входа bot-gateway. Один процесс: FastAPI + фоновые поллеры каналов + маркетинг.
Polling-каналы (Telegram, MAX) и автопостинг — фоновые задачи; webhook-каналы
(Instagram Direct, WhatsApp Cloud) — HTTP-роуты."""
import asyncio
import os
from contextlib import asynccontextmanager
from fastapi import FastAPI, Request, Response

from . import agent
from .channels import telegram, max_ru, instagram, whatsapp
from . import marketing

VERIFY_TOKEN = os.environ.get("WEBHOOK_VERIFY_TOKEN", "specinter")
_tasks = []


@asynccontextmanager
async def lifespan(app: FastAPI):
    _tasks.append(asyncio.create_task(telegram.poll()))
    _tasks.append(asyncio.create_task(max_ru.poll()))
    _tasks.append(asyncio.create_task(marketing.scheduler()))
    print(f"[bot] запущен. Агент готов: {agent.is_ready()}")
    yield
    for t in _tasks:
        t.cancel()


app = FastAPI(title="specinter bot-gateway", lifespan=lifespan)


@app.get("/health")
async def health():
    return {"status": "ok", "agent_ready": agent.is_ready()}


# --- Instagram Direct webhook (Messenger/Instagram Graph API) ---------------
@app.get("/webhook/instagram")
async def ig_verify(request: Request):
    p = request.query_params
    if p.get("hub.verify_token") == VERIFY_TOKEN:
        return Response(content=p.get("hub.challenge", ""))
    return Response(status_code=403)


@app.post("/webhook/instagram")
async def ig_webhook(request: Request):
    data = await request.json()
    for entry in data.get("entry", []):
        for m in entry.get("messaging", []):
            sender = (m.get("sender") or {}).get("id")
            text = (m.get("message") or {}).get("text")
            if sender and text:
                await instagram.handle_dm(sender, text)
    return {"ok": True}


# --- WhatsApp webhook (Cloud API или мост Baileys) --------------------------
@app.get("/webhook/whatsapp")
async def wa_verify(request: Request):
    p = request.query_params
    if p.get("hub.verify_token") == VERIFY_TOKEN:
        return Response(content=p.get("hub.challenge", ""))
    return Response(status_code=403)


@app.post("/webhook/whatsapp")
async def wa_webhook(request: Request):
    data = await request.json()
    # мост Baileys шлёт простой {from, text}; Cloud API — вложенную структуру
    if "from" in data and "text" in data:
        await whatsapp.handle_message(str(data["from"]), str(data["text"]))
        return {"ok": True}
    for entry in data.get("entry", []):
        for ch in entry.get("changes", []):
            for msg in (ch.get("value", {}) or {}).get("messages", []):
                if msg.get("type") == "text":
                    await whatsapp.handle_message(msg["from"], msg["text"]["body"])
    return {"ok": True}
