import os

# LLM — провайдер-агностично через OpenAI-совместимый API.
#   Grok:   LLM_BASE_URL=https://api.x.ai/v1        LLM_MODEL=grok-4
#   Hermes: LLM_BASE_URL=<openrouter/vllm URL>      LLM_MODEL=nousresearch/hermes-4-...
LLM_BASE_URL = os.environ.get("LLM_BASE_URL", "https://api.x.ai/v1")
LLM_MODEL = os.environ.get("LLM_MODEL", "grok-4")
LLM_API_KEY = os.environ.get("LLM_API_KEY", "")
LLM_TEMPERATURE = float(os.environ.get("LLM_TEMPERATURE", "0.2"))

# Наш backend API (скиллы бота бьют сюда).
BOT_API_URL = os.environ.get("BOT_API_URL", "http://api:8000").rstrip("/")

# Telegram
TELEGRAM_TOKEN = os.environ.get("TELEGRAM_TOKEN", "")

LLM_CONFIGURED = bool(LLM_API_KEY)
