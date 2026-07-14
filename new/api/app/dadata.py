"""Интеграция DaData: реквизиты компании по ИНН (все КПП/филиалы, адрес, подписант)
и определение города по геолокации/IP. Env-gated: без DADATA_TOKEN — dry-run с образцом."""
import httpx
from . import settings

SUGGEST_URL = "https://suggestions.dadata.ru/suggestions/api/4_1/rs"


def _token() -> str:
    return settings.get("DADATA_TOKEN")


def is_dry_run() -> bool:
    return not bool(_token())


def _party(data: dict) -> dict:
    """Разбор объекта party DaData в плоские поля для формы/отображения."""
    d = data or {}
    mgmt = d.get("management") or {}
    opf = d.get("opf") or {}
    addr = d.get("address") or {}
    name = d.get("name") or {}
    return {
        "inn": d.get("inn"),
        "kpp": d.get("kpp"),
        "ogrn": d.get("ogrn"),
        "okpo": d.get("okpo"),
        "name_short": name.get("short_with_opf") or name.get("short"),
        "name_full": name.get("full_with_opf") or name.get("full"),
        "opf": opf.get("short"),
        "management_name": mgmt.get("name"),      # ФИО руководителя (подписант)
        "management_post": mgmt.get("post"),      # должность
        "address": addr.get("unrestricted_value") or addr.get("value"),
        "postal_code": (addr.get("data") or {}).get("postal_code"),
        "status": (d.get("state") or {}).get("status"),
        "kladr_id": (addr.get("data") or {}).get("kladr_id"),
        "city": (addr.get("data") or {}).get("city") or (addr.get("data") or {}).get("region"),
    }


_SAMPLE = {
    "inn": "6658000000", "kpp": "665801001", "ogrn": "1069658000000", "okpo": "00000000",
    "name_short": 'ООО "СПЕЦИНТЕР"', "name_full": 'ОБЩЕСТВО С ОГРАНИЧЕННОЙ ОТВЕТСТВЕННОСТЬЮ "СПЕЦИНТЕР"',
    "opf": "ООО", "management_name": "Иванов Иван Иванович", "management_post": "Директор",
    "address": "620000, г Екатеринбург, ул Ленина, д 1", "postal_code": "620000",
    "status": "ACTIVE", "kladr_id": "6600000100000", "city": "Екатеринбург",
}


async def party_by_inn(inn: str) -> list[dict]:
    """Все организации/филиалы по ИНН (разные КПП). Dry-run → образец."""
    inn = (inn or "").strip()
    if is_dry_run():
        return [{**_SAMPLE, "inn": inn or _SAMPLE["inn"]}] if len(inn) >= 10 else []
    async with httpx.AsyncClient(timeout=15) as c:
        r = await c.post(f"{SUGGEST_URL}/findById/party",
                         headers={"Authorization": f"Token {_token()}",
                                  "Content-Type": "application/json", "Accept": "application/json"},
                         json={"query": inn, "count": 20})
    if r.status_code != 200:
        return []
    return [_party(s.get("data")) for s in r.json().get("suggestions", [])]


async def city_by_geo(lat: float | None, lon: float | None) -> dict:
    """Город по координатам браузера (geolocate). Dry-run → образец."""
    if is_dry_run() or lat is None or lon is None:
        return {"city": "Екатеринбург", "kladr_id": "6600000100000", "source": "default"}
    async with httpx.AsyncClient(timeout=15) as c:
        r = await c.get(f"{SUGGEST_URL}/geolocate/address",
                        headers={"Authorization": f"Token {_token()}", "Accept": "application/json"},
                        params={"lat": lat, "lon": lon, "count": 1})
    if r.status_code != 200:
        return {"city": None, "source": "error"}
    sug = r.json().get("suggestions") or []
    if not sug:
        return {"city": None, "source": "empty"}
    data = sug[0].get("data") or {}
    return {"city": data.get("city") or data.get("region"),
            "kladr_id": data.get("city_kladr_id") or data.get("kladr_id"), "source": "geo"}
