"""Внешние интеграции для оформления: DaData (реквизиты по ИНН), геолокация города,
расчёт доставки Деловыми Линиями по весу/объёму. Всё через API (не напрямую в БД)."""
import os
from fastapi import APIRouter, Body, Query
from .. import assistant, dadata, delovie, db, settings as app_settings

router = APIRouter(prefix="/api", tags=["integrations"])

# Дефолтные вес/объём позиции, если не заданы у товара (чтобы расчёт не был нулевым).
DEFAULT_WEIGHT_KG = float(os.environ.get("DEFAULT_ITEM_WEIGHT_KG", "2"))
DEFAULT_VOLUME_M3 = float(os.environ.get("DEFAULT_ITEM_VOLUME_M3", "0.005"))


@router.get("/dadata/party")
async def dadata_party(inn: str = Query(..., min_length=8)):
    """Реквизиты компании по ИНН (все КПП/филиалы, адрес, руководитель-подписант)."""
    return {"inn": inn, "suggestions": await dadata.party_by_inn(inn),
            "dry_run": dadata.is_dry_run()}


@router.get("/geo/city")
async def geo_city(lat: float | None = None, lon: float | None = None):
    """Город клиента по геолокации браузера (или дефолт, если не передана)."""
    return await dadata.city_by_geo(lat, lon)


@router.get("/site-config")
async def site_config():
    """Публичная конфигурация витрины (несекретные ссылки соцсетей и т.п.)."""
    return {
        "instagram_url": app_settings.get("INSTAGRAM_URL"),
        "youtube_url": app_settings.get("YOUTUBE_URL"),
    }


@router.post("/assistant/chat")
async def assistant_chat(body: dict = Body(...)):
    """Клиентский ИИ-консультант витрины (строгие рамки: только продажа запчастей)."""
    msgs = body.get("messages") or []
    return await assistant.chat("customer", msgs[-12:])   # ограничиваем контекст


@router.post("/delivery/estimate")
async def delivery_estimate(body: dict = Body(...)):
    """Срок и стоимость доставки Деловыми Линиями по составу корзины (вес+объём)."""
    to_city = (body.get("to_city") or "").strip()
    to_kladr = body.get("to_kladr")
    items = body.get("items") or []
    total_w = total_v = 0.0
    for it in items:
        pid, qty = it.get("product_id"), float(it.get("qty") or 1)
        w = v = None
        if pid:
            p = await db.fetchone("SELECT weight_kg, volume_m3 FROM products WHERE id=%s", (pid,))
            if p:
                w = p["weight_kg"]; v = p["volume_m3"]
        total_w += float(w if w is not None else DEFAULT_WEIGHT_KG) * qty
        total_v += float(v if v is not None else DEFAULT_VOLUME_M3) * qty
    if not to_city:
        return {"error": "укажите город доставки"}
    return await delovie.estimate(to_city, total_w, total_v, to_kladr)
