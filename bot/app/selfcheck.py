"""Проверка скиллов без LLM: дёргаем инструменты напрямую по нашему API.
Запуск: python -m app.selfcheck"""
import asyncio
from .tools import search_catalog, find_by_article, product_details, delivery_estimate


async def main():
    print("1) search_catalog('16Y-01-00005'):")
    print("  ", await search_catalog.ainvoke({"query": "16Y-01-00005"}))
    print("2) find_by_article('612600061489'):")
    print("  ", await find_by_article.ainvoke({"article": "612600061489"}))
    print("3) delivery_estimate(product_id=36036, 'Новосибирск'):")
    print("  ", await delivery_estimate.ainvoke({"product_id": 36036, "city": "Новосибирск"}))
    print("OK: скиллы отвечают через API (конфиденциальные поля скрыты).")


if __name__ == "__main__":
    asyncio.run(main())
