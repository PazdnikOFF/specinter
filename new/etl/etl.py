#!/usr/bin/env python3
"""
ETL: legacy MariaDB (specinter.dump.sql) -> Postgres доменное ядро.

Переносит: категории (it_tree), товары-артикулы (it_b_catitem), связки категорий
(it_b_ablock), аналоги (it_b_aarts), торговые предложения (it_b_variant),
клиентов (it_b_user3), изображения (it_b_goodimage) и разобранные прайсы (it_suppliers).

Запуск: docker compose --profile etl run --rm etl
Локально: PG_DSN=... MY_HOST=... python etl.py
"""
import os
import sys
import html
import phpserialize
import pymysql
import psycopg
from psycopg.rows import dict_row


def my_conn():
    return pymysql.connect(
        host=os.environ.get("MY_HOST", "127.0.0.1"),
        port=int(os.environ.get("MY_PORT", 3306)),
        user=os.environ.get("MY_USER", "root"),
        password=os.environ.get("MY_PASSWORD", "root"),
        database=os.environ.get("MY_DB", "specinter_legacy"),
        charset="utf8mb4",
        cursorclass=pymysql.cursors.DictCursor,
    )


def clean(v):
    if v is None:
        return None
    if isinstance(v, str):
        return html.unescape(v).strip() or None
    return v


def main():
    my = my_conn()
    pg = psycopg.connect(os.environ["PG_DSN"], row_factory=dict_row, autocommit=False)
    stats = {}
    try:
        # ETL_ONLY=images — перенести ТОЛЬКО изображения (категории + галерея товаров)
        # поверх уже наполненной БД, не трогая analogs/offers/prices (там INSERT без
        # ON CONFLICT — повторный полный прогон их бы задублировал). Идемпотентно.
        only = os.environ.get("ETL_ONLY", "").strip().lower()
        steps = {s.strip() for s in only.split(",") if s.strip()}
        with my.cursor() as mc, pg.cursor() as pc:
            if steps:
                # Идемпотентные шаги, безопасные для повторного прогона поверх наполненной БД
                # (в отличие от analogs/supplier_prices, где INSERT без ON CONFLICT).
                if "images" in steps:
                    migrate_category_images(mc, pc, stats)
                    migrate_images(mc, pc, stats)
                if "links" in steps:
                    migrate_product_categories(mc, pc, stats)
                pc.execute("INSERT INTO etl_runs (finished_at, stats) VALUES (now(), %s)",
                           (psycopg.types.json.Json(stats),))
                pg.commit()
                print(f"ETL ({only}) OK:", stats)
                return
            migrate_categories(mc, pc, stats)
            migrate_category_images(mc, pc, stats)
            migrate_products(mc, pc, stats)
            migrate_product_categories(mc, pc, stats)
            migrate_analogs(mc, pc, stats)
            migrate_offers(mc, pc, stats)
            migrate_customers(mc, pc, stats)
            migrate_images(mc, pc, stats)
            migrate_supplier_prices(mc, pc, stats)
            pc.execute("INSERT INTO etl_runs (finished_at, stats) VALUES (now(), %s)",
                       (psycopg.types.json.Json(stats),))
        pg.commit()
        print("ETL OK:", stats)
    except Exception:
        pg.rollback()
        raise
    finally:
        my.close()
        pg.close()


# ---------------------------------------------------------------------------
def migrate_categories(mc, pc, stats):
    mc.execute("SELECT id,parent,level,sort,visible,name,`key` AS slug FROM it_tree ORDER BY level,id")
    rows = mc.fetchall()
    # 1) вставляем без FK-родителя, 2) проставляем parent_id и путь
    for r in rows:
        pc.execute(
            """INSERT INTO categories (id,legacy_id,name,slug,level,sort,visible)
               VALUES (%s,%s,%s,%s,%s,%s,%s)
               ON CONFLICT (id) DO NOTHING""",
            (r["id"], r["id"], clean(r["name"]), clean(r["slug"]),
             r["level"], r["sort"], bool(r["visible"])),
        )
    for r in rows:
        parent = r["parent"] if r["parent"] else None
        pc.execute("UPDATE categories SET parent_id=%s WHERE id=%s", (parent, r["id"]))
    # материализованный путь
    pc.execute("""
        WITH RECURSIVE t AS (
          SELECT id, id::text AS path FROM categories WHERE parent_id IS NULL
          UNION ALL
          SELECT c.id, t.path || '/' || c.id FROM categories c JOIN t ON c.parent_id=t.id
        )
        UPDATE categories c SET path='/'||t.path FROM t WHERE c.id=t.id""")
    stats["categories"] = len(rows)


def migrate_products(mc, pc, stats):
    mc.execute("""SELECT id,art,name_rus,name_eng,html,d_html,uurl,img,
                         utitle,udescription,ukeywords,visible FROM it_b_catitem""")
    n = 0
    for r in mc.fetchall():
        pc.execute(
            """INSERT INTO products
               (legacy_id,manufacturer_article,name,name_en,description_html,slug,
                primary_image,seo_title,seo_description,seo_keywords,visible)
               VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)
               ON CONFLICT (legacy_id) DO NOTHING""",
            (r["id"], clean(r["art"]), clean(r["name_rus"]), clean(r["name_eng"]),
             r["html"] or r["d_html"], clean(r["uurl"]), clean(r["img"]),
             clean(r["utitle"]), clean(r["udescription"]), clean(r["ukeywords"]),
             bool(r["visible"])),
        )
        n += 1
    stats["products"] = n


def migrate_product_categories(mc, pc, stats):
    # it_b_ablock: good_id -> catitem, parent -> категория; num -> позиция на схеме.
    # На случай уже поднятой БД без новых колонок.
    pc.execute("ALTER TABLE product_categories ADD COLUMN IF NOT EXISTS position text")
    pc.execute("ALTER TABLE product_categories ADD COLUMN IF NOT EXISTS sort int DEFAULT 0")
    # На пару (good_id,parent) может быть несколько строк — берём с минимальным sort.
    mc.execute("""SELECT good_id, parent, num, sort FROM it_b_ablock
                  WHERE good_id IS NOT NULL ORDER BY sort""")
    seen = {}
    for r in mc.fetchall():
        key = (r["good_id"], r["parent"])
        if key not in seen:
            seen[key] = r
    n = 0
    for (good_id, parent), r in seen.items():
        pos = clean(r["num"])
        if pos in ("0", ""):
            pos = None
        pc.execute("""INSERT INTO product_categories (product_id, category_id, position, sort)
                      SELECT p.id, c.id, %s, %s FROM products p, categories c
                      WHERE p.legacy_id=%s AND c.legacy_id=%s
                      ON CONFLICT (product_id, category_id)
                      DO UPDATE SET position=EXCLUDED.position, sort=EXCLUDED.sort""",
                   (pos, r["sort"] or 0, good_id, parent))
        n += pc.rowcount
    stats["product_categories"] = n


def migrate_analogs(mc, pc, stats):
    # it_b_aarts: blockparent -> товар-владелец, good_id_arts -> карточка-аналог
    mc.execute("SELECT blockparent, art, name, good_id_arts FROM it_b_aarts WHERE art IS NOT NULL")
    n = 0
    for r in mc.fetchall():
        art = clean(r["art"])
        if not art:                     # пропускаем пустые/пробельные артикулы
            continue
        linked = r["good_id_arts"]
        linked = int(linked) if (linked and str(linked).isdigit()) else None
        pc.execute("""INSERT INTO analogs (product_id, analog_article, analog_name, linked_product_id, source)
                      SELECT p.id, %s, %s, lp.id, 'legacy'
                      FROM products p
                      LEFT JOIN products lp ON lp.legacy_id=%s
                      WHERE p.legacy_id=%s""",
                   (art, clean(r["name"]), linked, r["blockparent"]))
        n += pc.rowcount
    stats["analogs"] = n


def migrate_offers(mc, pc, stats):
    # it_b_variant: blockparent -> товар
    mc.execute("""SELECT id, blockparent, name, art, xlsx_id, price, quantity
                  FROM it_b_variant""")
    n = 0
    for r in mc.fetchall():
        price = _num(r["price"])
        qty = _num(r["quantity"])
        pc.execute("""INSERT INTO offers
                      (legacy_id, product_id, article, external_code, price, qty, in_stock)
                      SELECT %s, p.id, %s, %s, %s, %s, %s
                      FROM products p WHERE p.legacy_id=%s
                      ON CONFLICT (legacy_id) DO NOTHING""",
                   (r["id"], clean(r["art"]), clean(r["xlsx_id"]), price, qty,
                    bool(qty and qty > 0), r["blockparent"]))
        n += pc.rowcount
    stats["offers"] = n


def migrate_customers(mc, pc, stats):
    mc.execute("""SELECT id,name,phone,email,adres,organization,inn,kpp,
                         uladress,bank,bik,rschet,korschet,code,type FROM it_b_user3""")
    n = 0
    for r in mc.fetchall():
        kind = "legal" if (r["inn"] and r["inn"] != "main") else "person"
        pc.execute("""INSERT INTO customers
            (legacy_id,kind,name,phone,email,address,org_name,inn,kpp,bank,bik,rschet,korschet,code_1c)
            VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)
            ON CONFLICT (legacy_id) DO NOTHING""",
            (r["id"], kind, clean(r["name"]), clean(r["phone"]), clean(r["email"]),
             clean(r["adres"]), clean(r["organization"]), _req(r["inn"]), _req(r["kpp"]),
             _req(r["bank"]), _req(r["bik"]), _req(r["rschet"]), _req(r["korschet"]),
             clean(r["code"])))
        n += 1
    stats["customers"] = n


def migrate_category_images(mc, pc, stats):
    # Изображения категорий (групп/подгрупп) лежат в it_c_catalog.img
    # (+ additional_images как fallback), связь it_c_catalog.parent = it_tree.id.
    pc.execute("ALTER TABLE categories ADD COLUMN IF NOT EXISTS image text")
    mc.execute("""SELECT parent, img, additional_images FROM it_c_catalog
                  WHERE (img IS NOT NULL AND img<>'')
                     OR (additional_images IS NOT NULL AND additional_images<>'')""")
    n = 0
    for r in mc.fetchall():
        # additional_images может быть CSV — берём первый файл как fallback
        img = clean(r["img"]) or clean((r["additional_images"] or "").split(",")[0])
        if not img or not r["parent"]:
            continue
        pc.execute("""UPDATE categories SET image=%s
                      WHERE legacy_id=%s AND (image IS NULL OR image='')""",
                   (img, r["parent"]))
        n += pc.rowcount
    stats["category_images"] = n


def migrate_images(mc, pc, stats):
    # Карта товаров: legacy_id -> {id, уже присвоенные url} (primary + галерея),
    # чтобы не плодить дубли между источниками и внутри CSV-поля images.
    pc.execute("SELECT id, legacy_id, primary_image FROM products WHERE legacy_id IS NOT NULL")
    prod = {}
    for row in pc.fetchall():
        urls = set()
        pi = clean(row["primary_image"])
        if pi:
            urls.add(pi)
        prod[row["legacy_id"]] = {"id": row["id"], "urls": urls}

    # Учитываем уже вставленные картинки, чтобы повторный прогон был идемпотентным.
    id_to_legacy = {info["id"]: lid for lid, info in prod.items()}
    pc.execute("SELECT product_id, url FROM product_images")
    for row in pc.fetchall():
        lid = id_to_legacy.get(row["product_id"])
        if lid is not None:
            prod[lid]["urls"].add(clean(row["url"]))

    def add(legacy_id, url, sort):
        info = prod.get(legacy_id)
        if not info:
            return 0
        u = clean(url)
        if not u or u in info["urls"]:
            return 0
        info["urls"].add(u)
        pc.execute("INSERT INTO product_images (product_id, url, sort) VALUES (%s,%s,%s)",
                   (info["id"], u, sort))
        return 1

    n = 0
    # 1) галерея из it_b_goodimage (главный источник, идёт первым)
    mc.execute("SELECT blockparent, img, sort FROM it_b_goodimage WHERE img IS NOT NULL")
    for r in mc.fetchall():
        n += add(r["blockparent"], r["img"], r["sort"] or 0)

    # 2) доп. фото из самой карточки: img_1..img_4 и CSV-поле images
    mc.execute("SELECT id, img_1, img_2, img_3, img_4, images FROM it_b_catitem")
    for r in mc.fetchall():
        extras = [r["img_1"], r["img_2"], r["img_3"], r["img_4"]]
        if r["images"]:
            extras += str(r["images"]).split(",")
        for i, u in enumerate(extras):
            n += add(r["id"], u, 100 + i)

    stats["product_images"] = n


def migrate_supplier_prices(mc, pc, stats):
    # it_suppliers.data — сериализованный PHP-массив
    mc.execute("SELECT supplier, data FROM it_suppliers")
    suppliers = {}
    n = 0
    for r in mc.fetchall():
        sup = clean(r["supplier"]) or "unknown"
        if sup not in suppliers:
            pc.execute("""INSERT INTO suppliers (name) VALUES (%s)
                          ON CONFLICT DO NOTHING RETURNING id""", (sup,))
            row = pc.fetchone()
            if row:
                suppliers[sup] = row["id"]
            else:
                pc.execute("SELECT id FROM suppliers WHERE name=%s", (sup,))
                suppliers[sup] = pc.fetchone()["id"]
        try:
            d = phpserialize.loads(r["data"].encode() if isinstance(r["data"], str) else r["data"],
                                   decode_strings=True)
        except Exception:
            continue
        pc.execute("""INSERT INTO supplier_prices
            (supplier_id, article, external_code, name, maker, price, qty)
            VALUES (%s,%s,%s,%s,%s,%s,%s)""",
            (suppliers[sup], clean(d.get("art")), clean(d.get("code")),
             clean(d.get("name")), clean(d.get("maker")), _num(d.get("price")),
             _num(d.get("quantity"))))
        n += 1
    # матчинг прайса к товарам по нормализованному артикулу
    pc.execute("""UPDATE supplier_prices sp SET matched_product_id=p.id,
                    match_method='exact_article', match_confidence=1.0
                  FROM products p
                  WHERE sp.matched_product_id IS NULL
                    AND sp.normalized_article IS NOT NULL
                    AND p.normalized_article=sp.normalized_article""")
    # запасной матчинг через аналоги
    pc.execute("""UPDATE supplier_prices sp SET matched_product_id=a.product_id,
                    match_method='analog', match_confidence=0.8
                  FROM analogs a
                  WHERE sp.matched_product_id IS NULL
                    AND sp.normalized_article IS NOT NULL
                    AND a.normalized_article=sp.normalized_article""")
    stats["supplier_prices"] = n


def _num(v):
    if v in (None, "", "N"):
        return None
    try:
        return float(str(v).replace(",", ".").replace(" ", ""))
    except (ValueError, TypeError):
        return None


def _req(v):
    v = clean(v)
    return None if v in (None, "main") else v


if __name__ == "__main__":
    sys.exit(main())
