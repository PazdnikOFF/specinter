"""Расчёт доставки «Деловые Линии» по весу и объёму груза. Env-gated: без DELLIN_APPKEY —
dry-run (срок из матрицы транзита logistics + ориентировочная стоимость по весу/объёму)."""
import math
import os
import httpx

from . import logistics, settings

DELLIN_URL = "https://api.dellin.ru/v2/calculator.json"
FROM_CITY = os.environ.get("WAREHOUSE_CITY", "Екатеринбург")


def _appkey() -> str:
    return settings.get("DELLIN_APPKEY")


def is_dry_run() -> bool:
    return not bool(_appkey())

# Ориентировочные тарифы для dry-run (межгород, автоперевозка, руб.)
_BASE = 350.0
_PER_KG = 11.0
_PER_M3 = 3800.0


def _estimate_local(to_city: str, weight_kg: float, volume_m3: float) -> dict:
    days = logistics.transit(to_city)          # (min,max) раб. дней Екб→город
    weight_kg = max(weight_kg or 0, 1)
    volume_m3 = max(volume_m3 or 0, 0.01)
    # платный вес: max(факт, объёмный 250кг/м3) — как у большинства ТК
    chargeable = max(weight_kg, volume_m3 * 250)
    dist_factor = 1 + (days[1] - 1) * 0.12      # дальше — дороже
    cost = (_BASE + _PER_KG * chargeable + _PER_M3 * volume_m3) * dist_factor
    cost = math.ceil(cost / 10) * 10
    return {
        "carrier": "Деловые Линии",
        "mode": "dry-run" if is_dry_run() else "live",
        "from_city": FROM_CITY, "to_city": to_city,
        "weight_kg": round(weight_kg, 2), "volume_m3": round(volume_m3, 3),
        "cost_rub": cost,
        "term_days": list(days),
        "term_label": f"{days[0]}–{days[1]} раб. дн." if days[0] != days[1] else f"~{days[0]} раб. дн.",
    }


async def estimate(to_city: str, weight_kg: float, volume_m3: float,
                   to_kladr: str | None = None) -> dict:
    """Срок и стоимость доставки до города клиента по весу/объёму."""
    if is_dry_run():
        return _estimate_local(to_city, weight_kg, volume_m3)
    payload = {
        "appkey": _appkey(),
        "delivery": {
            "deliveryType": {"type": "auto"},
            "derival": {"variant": "terminal", "city": FROM_CITY},
            "arrival": {"variant": "terminal", "city": to_kladr or to_city},
            "cargo": {"totalWeight": max(weight_kg or 0, 1),
                      "totalVolume": max(volume_m3 or 0, 0.01)},
        },
    }
    try:
        async with httpx.AsyncClient(timeout=20) as c:
            r = await c.post(DELLIN_URL, json=payload)
        if r.status_code != 200:
            return {**_estimate_local(to_city, weight_kg, volume_m3), "note": "fallback (API error)"}
        d = r.json().get("data", {})
        price = d.get("price") or d.get("price_min")
        days = d.get("orderDates", {}).get("giveoutFromOsg") or None
        out = _estimate_local(to_city, weight_kg, volume_m3)
        if price:
            out["cost_rub"] = round(float(price))
            out["mode"] = "live"
        if days:
            out["term_label"] = f"к {days}"
        return out
    except httpx.HTTPError:
        return {**_estimate_local(to_city, weight_kg, volume_m3), "note": "fallback (network)"}
