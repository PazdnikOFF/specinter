import os

PG_DSN = os.environ.get("PG_DSN", "postgresql://specinter:specinter@localhost:5432/specinter")
MEILI_URL = os.environ.get("MEILI_URL", "http://localhost:7700")
MEILI_KEY = os.environ.get("MEILI_KEY", "specinter_dev_key")
PRODUCTS_INDEX = "products"

# Корень витринного каталога (узел «КАТАЛОГИ» в дереве категорий).
# Его дети — марки/модели техники и двигатели, глубже — узлы и группы деталей.
CATALOG_ROOT_ID = int(os.environ.get("CATALOG_ROOT_ID", "40"))
