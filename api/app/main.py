import os
from contextlib import asynccontextmanager
from fastapi import Depends, FastAPI
from fastapi.middleware.cors import CORSMiddleware
from . import auth, db, search, prices, unf, payments, settings as app_settings
from .routers import catalog, admin_prices, orders, edo, admin_metrics, quotes, integrations
from .routers import admin as admin_router
from .routers import agent as agent_router
from .routers import admin_catalog


@asynccontextmanager
async def lifespan(app: FastAPI):
    await db.pool.open()
    try:
        await prices.ensure_migrated()
        await unf.ensure_migrated()
        await payments.ensure_migrated()
        await quotes.ensure_migrated()
        await app_settings.ensure_migrated()
        await app_settings.load()
    except Exception as e:
        print("migration warning:", e)
    try:
        search.ensure_index()
    except Exception as e:  # meili может ещё подниматься — не блокируем старт
        print("meili init warning:", e)
    yield
    await db.pool.close()


app = FastAPI(title="specinter API", version="0.1.0", lifespan=lifespan)
# С cookie-сессией админки нужен конкретный origin (не '*') и allow_credentials.
app.add_middleware(
    CORSMiddleware,
    allow_origins=os.environ.get("CORS_ORIGINS", "http://localhost:3000").split(","),
    allow_credentials=True,
    allow_methods=["*"], allow_headers=["*"],
)
app.include_router(catalog.router)
app.include_router(orders.router)
app.include_router(edo.router)
app.include_router(quotes.router)
app.include_router(integrations.router)
# Внешний ИИ-агент: шлюз /agent/* под X-API-Key (см. agent_auth). Отдельно от /api.
app.include_router(agent_router.router)
# Админка: логин/логаут открыты, остальные админ-роуты — под require_admin.
app.include_router(admin_router.auth_router)
app.include_router(admin_router.router)
app.include_router(admin_prices.router, dependencies=[Depends(auth.require_admin)])
app.include_router(admin_metrics.router, dependencies=[Depends(auth.require_admin)])
app.include_router(admin_catalog.router, dependencies=[Depends(auth.require_admin)])


@app.get("/health")
async def health():
    row = await db.fetchone("SELECT count(*) AS n FROM products")
    return {"status": "ok", "products": row["n"]}


@app.post("/api/admin/reindex", tags=["admin"])
async def reindex(_: bool = Depends(auth.require_admin)):
    n = await search.reindex()
    return {"indexed": n}
