"""Простая авторизация админки: один логин/пароль из env, cookie-сессия.
Токен = HMAC(логин:пароль, секрет) — без хранения сессий в БД."""
import hashlib
import hmac
import os

from fastapi import HTTPException, Request, Response

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


def require_admin(request: Request):
    """FastAPI-зависимость: пускает только с валидной cookie-сессией."""
    tok = request.cookies.get(COOKIE, "")
    if not tok or not hmac.compare_digest(tok, _token()):
        raise HTTPException(401, "Требуется вход администратора")
    return True
