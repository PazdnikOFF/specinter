"""Модуль логистики: сроки доставки.
  * пополнение: город поставщика → наш склад (Екатеринбург);
  * отгрузка: склад → город клиента (если геолокация подтверждена).
Базовая матрица транзита (наземка) — как fallback; реальные API перевозчиков
(СДЭК/ПЭК/Деловые линии) подключаются через env (заготовка `carrier_eta`)."""
import os
import unicodedata

WAREHOUSE_CITY = os.environ.get("WAREHOUSE_CITY", "Екатеринбург")
DEFAULT_DAYS = (5, 9)

# Ориентировочные сроки наземной доставки Екатеринбург ↔ город (рабочих дней).
TRANSIT_DAYS = {
    "екатеринбург": (0, 1), "челябинск": (1, 2), "пермь": (1, 2), "тюмень": (1, 2),
    "уфа": (2, 3), "курган": (1, 2), "магнитогорск": (2, 3), "нижнийновгород": (3, 4),
    "казань": (2, 4), "самара": (3, 4), "москва": (3, 5), "санктпетербург": (4, 6),
    "новосибирск": (3, 5), "омск": (2, 4), "красноярск": (4, 6), "ростовнадону": (5, 7),
    "краснодар": (5, 8), "воронеж": (4, 6), "волгоград": (4, 6), "иркутск": (6, 8),
    "хабаровск": (8, 12), "владивосток": (9, 13), "калининград": (7, 10),
}


def _norm(city: str) -> str:
    s = unicodedata.normalize("NFKC", city or "").lower()
    return "".join(ch for ch in s if ch.isalnum())


def transit(city: str) -> tuple[int, int]:
    return TRANSIT_DAYS.get(_norm(city), DEFAULT_DAYS)


def replenishment_days(supplier_city: str | None) -> tuple[int, int]:
    """Поставщик → наш склад."""
    if not supplier_city:
        return DEFAULT_DAYS
    return transit(supplier_city)


def delivery_days(client_city: str | None) -> tuple[int, int]:
    """Склад → клиент."""
    if not client_city:
        return (None, None)
    return transit(client_city)


def carrier_eta(from_city: str, to_city: str):
    """Заготовка под реальный API перевозчика (СДЭК и т.п.). Включается через env."""
    if not os.environ.get("CDEK_ACCOUNT"):
        return None
    # TODO: запрос к API перевозчика
    return None


def estimate(in_stock: bool, supplier_city: str | None, client_city: str | None) -> dict:
    """Итоговая оценка: в наличии → только отгрузка; под заказ → пополнение + отгрузка."""
    ship_min, ship_max = delivery_days(client_city)
    if in_stock:
        total = (ship_min, ship_max)
        breakdown = {"stage": "в наличии", "shipping_days": [ship_min, ship_max]}
    else:
        rep_min, rep_max = replenishment_days(supplier_city)
        total_min = rep_min + (ship_min or 0)
        total_max = rep_max + (ship_max or 0)
        total = (total_min, total_max)
        breakdown = {"stage": "под заказ",
                     "replenishment_days": [rep_min, rep_max],
                     "shipping_days": [ship_min, ship_max]}
    label = None
    if total[0] is not None:
        label = (f"~{total[0]} дн." if total[0] == total[1]
                 else f"{total[0]}–{total[1]} раб. дн.")
    return {"warehouse": WAREHOUSE_CITY, "client_city": client_city,
            "eta_days": list(total), "eta_label": label, **breakdown}
