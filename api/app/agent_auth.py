"""Аутентификация внешнего ИИ-агента по заголовку X-API-Key.

Витринный /api — ПУБЛИЧНАЯ поверхность магазина (аноним и так ей пользуется:
поиск, каталог, оформление). Спрятать её ключом нельзя — браузер не хранит секрет.
Поэтому для агента заведён ОТДЕЛЬНЫЙ шлюз /agent/*, закрытый ключом на уровне
бэкенда: даже через веб-прокси без валидного ключа = 401.

Ключи хранятся в app_settings (AGENT_API_KEYS, через запятую) → ротация без рестарта
(после смены значения вызвать settings.load(), см. админ-эндпоинт настроек).
"""
import hmac
import time
from collections import defaultdict, deque

from fastapi import Header, HTTPException

from . import settings as app_settings


def _valid_keys() -> set[str]:
    raw = app_settings.get("AGENT_API_KEYS", "")
    return {k.strip() for k in raw.split(",") if k.strip()}


def _match(candidate: str, keys: set[str]) -> bool:
    """Сравнение в постоянном времени (защита от timing-атак)."""
    ok = False
    for k in keys:
        if hmac.compare_digest(candidate, k):
            ok = True
    return ok


# Простой rate-limit: не более _RATE запросов за _WINDOW секунд на ключ.
# api крутится одним процессом uvicorn (без --workers) → in-memory счётчик корректен.
_RATE = 120
_WINDOW = 60
_hits: dict[str, deque] = defaultdict(deque)


def _rate_ok(key: str) -> bool:
    now = time.monotonic()
    dq = _hits[key]
    while dq and dq[0] <= now - _WINDOW:
        dq.popleft()
    if len(dq) >= _RATE:
        return False
    dq.append(now)
    return True


async def require_agent_key(x_api_key: str = Header(default="")):
    """Зависимость: пускает только с валидным X-API-Key и в пределах rate-limit."""
    keys = _valid_keys()
    if not keys:
        # Ключи не заданы → шлюз намеренно ВЫКЛЮЧЕН (не открываем по умолчанию).
        raise HTTPException(503, "agent API disabled: no AGENT_API_KEYS configured")
    if not x_api_key or not _match(x_api_key, keys):
        raise HTTPException(401, "invalid or missing X-API-Key")
    if not _rate_ok(x_api_key):
        raise HTTPException(429, f"rate limit: max {_RATE} req / {_WINDOW}s")
    return True
