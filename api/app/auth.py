"""Простая авторизация админки: один логин/пароль из env, cookie-сессия.
Токен = HMAC(логин:пароль, секрет) — без хранения сессий в БД."""
import hashlib
import hmac
import os

from fastapi import HTTPException, Request, Response

from . import settings as app_settings

ADMIN_USER = os.environ.get("ADMIN_USER", "admin")
ADMIN_PASSWORD = os.environ.get("ADMIN_PASSWORD", "specinter")
ADMIN_SECRET = os.environ.get("ADMIN_SECRET", "specinter-dev-secret")
COOKIE = "admin_session"
MAX_AGE = 7 * 24 * 3600


def _token() -> str:
    return hmac.new(ADMIN_SECRET.encode(),
                    f"{ADMIN_USER}:{ADMIN_PASSWORD}".encode(),
                    hashlib.sha256).hexdigest()


def login(username: str, password: str, response: Response) -> bool:
    ok = (hmac.compare_digest(username or "", ADMIN_USER)
          and hmac.compare_digest(password or "", ADMIN_PASSWORD))
    if ok:
        response.set_cookie(COOKIE, _token(), httponly=True, samesite="lax",
                            max_age=MAX_AGE, path="/")
    return ok


def logout(response: Response):
    response.delete_cookie(COOKIE, path="/")


def _admin_api_keys() -> set[str]:
    raw = app_settings.get("AGENT_ADMIN_API_KEYS", "")
    return {k.strip() for k in raw.split(",") if k.strip()}


def require_admin(request: Request):
    """FastAPI-зависимость: пускает по cookie-сессии (веб-админка) ИЛИ по
    привилегированному заголовку X-Admin-Key (бот/агент; ключи в AGENT_ADMIN_API_KEYS).
    Через X-Admin-Key доступно ВСЁ управление сайтом — ключ держать в секрете."""
    key = request.headers.get("x-admin-key", "")
    if key:
        keys = _admin_api_keys()
        if keys and any(hmac.compare_digest(key, k) for k in keys):
            return True
        raise HTTPException(401, "неверный X-Admin-Key")
    tok = request.cookies.get(COOKIE, "")
    if not tok or not hmac.compare_digest(tok, _token()):
        raise HTTPException(401, "Требуется вход администратора (cookie или X-Admin-Key)")
    return True
