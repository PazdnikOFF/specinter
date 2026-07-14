import os
from contextlib import asynccontextmanager
from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from . import db, search, prices, unf, payments
from .routers import catalog, admin_prices, orders, edo, admin_metrics, quotes


@asynccontextmanager
async def lifespan(app: FastAPI):
    await db.pool.open()
    try:
        await prices.ensure_migrated()
        await unf.ensure_migrated()
        await payments.ensure_migrated()
        await quotes.ensure_migrated()
    except Exception as e:
        print("migration warning:", e)
    try:
        search.ensure_index()
    except Exception as e:  # meili может ещё подниматься — не блокируем старт
        print("meili init warning:", e)
    yield
    await db.pool.close()


app = FastAPI(title="specinter API", version="0.1.0", lifespan=lifespan)
app.add_middleware(
    CORSMiddleware,
    allow_origins=os.environ.get("CORS_ORIGINS", "*").split(","),
    allow_methods=["*"], allow_headers=["*"],
)
app.include_router(catalog.router)
app.include_router(admin_prices.router)
app.include_router(orders.router)
app.include_router(edo.router)
app.include_router(admin_metrics.router)
app.include_router(quotes.router)


@app.get("/health")
async def health():
    row = await db.fetchone("SELECT count(*) AS n FROM products")
    return {"status": "ok", "products": row["n"]}


@app.post("/api/admin/reindex", tags=["admin"])
async def reindex():
    n = await search.reindex()
    return {"indexed": n}
