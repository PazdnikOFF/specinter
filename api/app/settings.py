"""Настройки портала в БД (токены интеграций и т.п.), чтобы админ/ИИ мог их задавать
без правки .env и перезапуска. Значение берётся из БД, иначе — из окружения (fallback)."""
import os
from . import db

MIGRATION = """
CREATE TABLE IF NOT EXISTS app_settings (
  key        text PRIMARY KEY,
  value      text,
  updated_at timestamptz DEFAULT now()
);
"""

# Известные ключи: (описание, чувствительный?)
KNOWN = {
    "DADATA_TOKEN":   ("DaData: токен API (реквизиты по ИНН, геолокация)", True),
    "DADATA_SECRET":  ("DaData: секретный ключ (для части методов)", True),
    "DELLIN_APPKEY":  ("Деловые Линии: appkey из личного кабинета", True),
    "TELEGRAM_TOKEN": ("Telegram: токен бота от @BotFather", True),
    "WA_TOKEN":       ("WhatsApp Cloud API: токен доступа", True),
    "WA_PHONE_ID":    ("WhatsApp Cloud API: phone number id", False),
    "MAX_TOKEN":      ("MAX (РФ): токен бота", True),
    "IG_ACCESS_TOKEN": ("Instagram: access token (Direct + автопостинг)", True),
    "IG_USER_ID":     ("Instagram: id бизнес-аккаунта (для публикаций)", False),
    "INSTAGRAM_URL":  ("Instagram: ссылка на профиль (для сайта)", False),
    "YOUTUBE_API_KEY": ("YouTube: API key / OAuth токен", True),
    "YOUTUBE_CHANNEL_ID": ("YouTube: id канала", False),
    "YOUTUBE_URL":    ("YouTube: ссылка на канал (для сайта)", False),
    "LLM_API_KEY":    ("ИИ-оператор: ключ LLM (Grok/Hermes/OpenAI-совм.)", True),
    "LLM_BASE_URL":   ("ИИ: базовый URL OpenAI-совместимого API", False),
    "LLM_MODEL":      ("ИИ: имя модели", False),
    "AGENT_API_KEYS": ("Внешний ИИ-агент: ключи X-API-Key для шлюза /agent (через запятую)", True),
    "AGENT_ADMIN_API_KEYS": ("Бот/агент-АДМИН: привилегированные ключи X-Admin-Key (полное управление, через запятую)", True),
}

_cache: dict[str, str] = {}


async def ensure_migrated():
    async with db.pool.connection() as conn:
        await conn.execute(MIGRATION)


async def load():
    """Читает все настройки из БД в кэш (вызывается на старте и после изменения)."""
    global _cache
    try:
        rows = await db.fetch("SELECT key, value FROM app_settings")
        _cache = {r["key"]: r["value"] for r in rows if r["value"]}
    except Exception:
        _cache = {}


def get(key: str, default: str = "") -> str:
    """Значение: БД (кэш) → окружение → default."""
    return _cache.get(key) or os.environ.get(key, "") or default


async def set_value(key: str, value: str | None):
    await db.fetchone(
        """INSERT INTO app_settings (key, value, updated_at) VALUES (%s,%s,now())
           ON CONFLICT (key) DO UPDATE SET value=EXCLUDED.value, updated_at=now()
           RETURNING key""", (key, value))
    await load()


def public_status() -> list[dict]:
    """Список известных ключей со статусом (без раскрытия секретов)."""
    out = []
    for k, (desc, secret) in KNOWN.items():
        val = get(k)
        out.append({"key": k, "description": desc, "secret": secret,
                    "configured": bool(val),
                    "preview": ("•••" + val[-4:]) if (val and secret) else val})
    return out
