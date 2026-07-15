# specinter — новый портал

Автономный продающий портал запчастей китайской спецтехники. Docker.
См. проектные документы в [`docs/`](docs/): анализ legacy и архитектура.

## Статус

Этап 1 — **ETL + доменное ядро** ✅
- `db/schema.sql` — доменная схема Postgres (каталог, аналоги, поставщики, прайсы, клиенты, заказы).
- `etl/` — миграция данных из legacy MariaDB → Postgres.
- `docker-compose.yml` — Postgres + MariaDB (авто-загрузка `../legacy/specinter.dump.sql`) + ETL.

Этап 2 — **API + мгновенный поиск** ✅
- `api/` — FastAPI поверх доменного ядра.
- Meilisearch — индекс каталога (поиск по артикулу/аналогу/названию, опечатки).
- Эндпоинты: `GET /api/search`, `GET /api/products/{id}`, `GET /api/products/by-article/{art}`,
  `GET /api/categories`, `POST /api/admin/reindex`, `GET /health`. Docs: `http://localhost:8010/docs`.

```bash
docker compose up -d postgres meilisearch api
curl -X POST localhost:8010/api/admin/reindex          # построить индекс
curl -G localhost:8010/api/search --data-urlencode q=612600061489
```

Этап 3 — **Витрина (Next.js, Apple-UI)** ✅
- `web/` — search-first лендинг → результаты → карточка товара, серверный рендер из API (SEO).
- `docker compose up -d web` → `http://localhost:3000`.

Этап 3.1 — **Изображения каталога со старого сайта** ✅
- Только используемые (по позициям БД, а не все 12 ГБ). Перенесено 3086/3090.
- `scripts/collect_images_remote.sh` — выполняется на сервере: по манифесту находит файлы
  в бакетах `files/{2,0,1,3}` и пакует только нужные.
- `scripts/fetch_and_load_images.sh` — локально: строит манифест из Postgres → запускает
  сборщик на сервере → скачивает архив → распаковывает в docker-том `mediadata`.
- Сервис `media` (nginx) отдаёт изображения; web берёт их по `NEXT_PUBLIC_MEDIA_URL`.

```bash
SSH_KEY=~/.ssh/id_ed25519_pazdnikoff bash scripts/fetch_and_load_images.sh
```

Этап 4 — **Конвейер прайсов** (замена `xml.php`) ✅
- `api/app/prices.py` — парсинг XLSX по профилю поставщика → `supplier_prices` (**с историей**,
  без TRUNCATE) → матчинг **по артикулу** (exact → analog → fuzzy `pg_trgm`) → пересчёт `offers`
  с наценкой поставщика.
- `api/app/routers/admin_prices.py` — поставщики/профили, ручная загрузка прайса, очередь модерации.
- `api/app/imap_worker.py` + сервис `worker` — приём прайсов с почты (IMAP), сопоставление
  отправителя с поставщиком. Env-gated: без IMAP-кредов простаивает.
- Профиль колонок по умолчанию — из legacy (`0=maker,1=name,2=article,4=price,5=qty,6=code`, 7 строк шапки).

```bash
# создать поставщика (профиль колонок создаётся автоматически)
curl -X POST "localhost:8010/api/admin/suppliers?name=Supplier&city=Москва&markup_percent=15"
# загрузить прайс вручную (основной путь — приём с почты воркером)
curl -X POST "localhost:8010/api/admin/prices/upload?supplier_id=1" -F file=@price.xlsx
# очередь модерации нечётких/несопоставленных
curl localhost:8010/api/admin/prices/unmatched
```

Приём с почты включается заполнением `IMAP_*` в `.env` и указанием `sender_email` у поставщика.

Этап 5 — **Прямая интеграция 1С:УНФ** (без файлов) ✅
- `api/app/unf.py` — коннектор: OData стандартного интерфейса (справочники/документы) +
  HTTP-сервисы расширения (проведение, печатная форма ПФ в PDF). Всё через env.
- `api/app/routers/orders.py` — оформление заказа → автосоздание в УНФ **Контрагента, Заказа
  покупателя, Счёта** → получение **ПФ (PDF)** → сохранение в таблицу `documents`.
- Без `UNF_BASE_URL` работает в **dry-run** (весь поток заказ→счёт→ПФ тестируется локально);
  при заполнении `UNF_*` те же вызовы уходят в боевую УНФ.
- `documents.edo_status` — задел под ЭДО Диадок (следующий модуль).

```bash
curl localhost:8010/api/unf/status
curl -X POST localhost:8010/api/orders -H 'Content-Type: application/json' -d '{
  "customer":{"name":"...","kind":"legal","inn":"...","org_name":"..."},
  "items":[{"product_id":36036,"qty":2}]}'
# → счёт (ПФ):  GET /api/orders/{id}/invoice.pdf
```

Этап 6 — **ИИ-оператор** (LangGraph, Grok/Hermes) ✅
- `bot/` — ядро-агент на LangGraph (`assistant ↔ tools`, память диалога), LLM провайдер-агностично
  через OpenAI-совместимый API (Grok по умолчанию, Hermes через OpenRouter/vLLM).
- **Скиллы** бьют в наш API и **вычищают конфиденциальное** (поставщики/закупка наружу не уходят):
  `search_catalog`, `find_by_article`, `product_details`, `delivery_estimate`, `create_order`, `request_operator`.
- **Строгие инструкции** (`bot/app/prompts.py`): только продажа запчастей, отказ на посторонние темы,
  без выдумок, без конфиденциальных данных, заказ — только с подтверждением, эскалация в крайних случаях.
- **Каналы**: Telegram, MAX (РФ), Instagram Direct, WhatsApp (Cloud API/мост Baileys). Одно ядро — 4 транспорта.
- **Автопостинг Instagram**: бот сам постит рилсы/фото-промо из каталога (`marketing.py` + `publishers/`);
  реклама — Marketing API (заготовка, нужен рекламный кабинет).
- Подробно — `bot/SKILLS.md`. Включение: `LLM_API_KEY` + токен канала в `.env`.

```bash
docker compose exec bot python -m app.selfcheck   # проверка скиллов без LLM
curl localhost:8090/health
```

Этап 7 — **Платежи физлиц** (ЮKassa / Т-Банк) ✅
- `api/app/payments.py` — провайдер-агностично: карты + СБП. При оформлении заказа физлицом
  создаётся платёж и возвращается ссылка оплаты; вебхуки провайдеров → заказ `paid`.
- Юрлицам оплата не через эквайринг, а по счёту из 1С:УНФ.
- Без кредов — dry-run (эмуляция колбэка: `POST /api/payments/{ext}/simulate`).

Этап 8 — **ЭДО Контур.Диадок** ✅
- `api/app/diadoc.py` — получение закрывающих документов (УПД/накладные), привязка к заказу по ИНН,
  сохранение в `documents` (`edo_status`). `GET /api/edo/status`, `POST /api/edo/sync`. Без кредов — dry-run.

Этап 9 — **Логистика (сроки доставки)** ✅
- `api/app/logistics.py` — пополнение (город поставщика → склад Екб) + отгрузка (склад → город клиента),
  базовая матрица транзита; API перевозчиков (СДЭК/ПЭК) подключается через env.
- `GET /api/products/{id}/eta?city=...` — «в наличии → N дн.» против «под заказ → пополнение + отгрузка».
- Скилл бота `delivery_estimate` теперь считает реальный срок.

Этап 10 — **Админка метрик** ✅
- `GET /api/admin/metrics` — каталог, предложения, покрытие матчинга прайсов, заказы/выручка/средний чек,
  каналы, топ позиций, последние заказы.
- Страница `/admin` (Apple-UI): KPI-тайлы, meter покрытия матчинга, топ-бары, таблица заказов.

Этап 11 — **Корзина и оформление на витрине** ✅
- Клиентская корзина (localStorage), кнопка «В корзину» на карточке, страница `/cart` с оформлением
  (физлицо → оплата, юрлицо → счёт), страница `/order/success`. CORS для браузерных запросов.
- Авто-переиндексация каталога после загрузки прайса — поиск сразу видит новые цены/наличие.

Этап 12 — **Заявки на подбор (RFQ)** ✅
- Если детали нет в каталоге — не теряем клиента: `POST /api/quote-requests`, `GET /api/admin/quote-requests`.
- Форма заявки в пустой выдаче поиска; у бота — скилл `request_quote`.

### Порты (host)
| Сервис | Порт | Примечание |
|---|---|---|
| web | 3000 | витрина |
| api | **8010** | перенесён с 8000 (был занят) — меняется через `API_PORT` в `.env` |
| bot | 8090 | ИИ-оператор + вебхуки каналов |
| media | 8081 | nginx, изображения каталога |
| meilisearch | 7700 | |
| postgres | 5432 | |
| mariadb-legacy | 3307 | **только под `--profile etl`** (анализ/миграция) |

Каждый сервис ограничен `mem_limit` (см. compose). Прод-стек (7 сервисов без legacy-БД) —
~400 МБ суммарно. «8 ГБ» ранее — это потолок ВМ Docker Desktop; при желании уменьшите его
в Docker Desktop → Settings → Resources → Memory (напр. до 4 ГБ).

Внутри Docker-сети web ходит в API по `http://api:8000` (порт контейнера), поэтому смена
host-порта API на витрину не влияет.

## Запуск

```bash
cd new
cp .env.example .env

# 1. Поднять доменную БД
docker compose up -d postgres

# 2. Миграция из legacy (профиль etl сам поднимет MariaDB с дампом)
docker compose --profile etl run --rm --build etl
#    ⚠️ mariadb-legacy запускается ТОЛЬКО под профилем etl — в обычном
#    `docker compose up` не поднимается (не занимает память в проде).

# 3. Проверить результат
docker compose exec postgres psql -U specinter -d specinter -c \
  "SELECT 'products' t, count(*) FROM products
   UNION ALL SELECT 'analogs', count(*) FROM analogs
   UNION ALL SELECT 'offers', count(*) FROM offers
   UNION ALL SELECT 'categories', count(*) FROM categories;"
```

Legacy-БД остаётся доступной на порту `${LEGACY_PORT}` (по умолчанию 3307) для анализа.

## Прод: HTTPS + nginx

В проде поверх базового compose накладывается `docker-compose.prod.yml` — reverse-proxy
**nginx** (единственная публичная точка входа, порты 80/443) + **certbot**
(автовыпуск/автопродление сертификатов Let's Encrypt). TLS терминируется на прокси,
весь трафик уходит в `web:3000`, который сам маршрутизирует `/api/*` и `/media/*`.

Выход на боевой домен — когда DNS домена уже резолвится на сервер и порты 80/443
доступны из интернета:

```bash
# 1. В .env задать домен, e-mail и включить прод-безопасность:
#    DOMAIN=specinter.ru
#    LETSENCRYPT_EMAIL=admin@specinter.ru
#    CORS_ORIGINS=https://specinter.ru
#    BIND_ADDR=127.0.0.1:      # порты приложения — только localhost, наружу лишь прокси

# 2. Первичный выпуск сертификата (ставит заглушку, поднимает nginx, берёт cert):
./scripts/init-letsencrypt.sh

# 3. Поднять прод-стек (база + прокси + certbot):
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

Продление сертификата — автоматически (certbot renew каждые 12 ч, nginx reload каждые 6 ч).
Для отладки без rate-limit: `LETSENCRYPT_STAGING=1`. Пока домен не переключён —
шаги 2–3 не выполняются, стек работает по HTTP (LAN, `BIND_ADDR` пустой).

## Ключевые решения

- **Матчинг по артикулу производителя** (`norm_article()` — нормализация регистра/пробелов/разделителей),
  затем через таблицу аналогов, затем нечёткий `pg_trgm`. В отличие от legacy, где матчинг шёл по коду 1С.
- Прайсы хранятся **с историей** (`supplier_prices`), без TRUNCATE.
- Профили парсинга **на каждого поставщика** (`supplier_price_profiles`).

## Дальше (по плану `docs/02-architecture.md`)

api (FastAPI) · web (Next.js, Apple-UI) · meilisearch · конвейер прайсов с почты ·
unf-connector (прямая 1С:УНФ) · bot-gateway (Telegram/WhatsApp/MAX) · платежи ЮKassa/Т-Банк · ЭДО Диадок.
