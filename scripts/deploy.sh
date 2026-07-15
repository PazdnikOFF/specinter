#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# Обновление прода Specinter.
#
# Запускать ЛОКАЛЬНО из каталога new/ (артефакт деплоя = отдельный репо new/,
# ветка main → GitHub PazdnikOFF/specinter → clone в /opt/specinter на сервере).
#
# Что делает:
#   1) коммитит незакоммиченные правки (сообщение — из -m или по умолчанию) и
#      пушит в origin/main;
#   2) по SSH на прод-сервере подтягивает main и применяет изменения:
#      - код web/api живёт в bind-mount (npm run dev / uvicorn --reload) →
#        пересборка образа НЕ нужна, достаточно рестарта;
#      - образ пересобирается ТОЛЬКО если сменились зависимости/Dockerfile
#        (package*.json, requirements*.txt, Dockerfile).
#   Тома БД (pgdata) и медиа (mediadata) при этом сохраняются.
#
# Использование:
#   ./scripts/deploy.sh                      # закоммитить (если грязно) и выкатить
#   ./scripts/deploy.sh -m "фикс аналогов"   # со своим сообщением коммита
#   ./scripts/deploy.sh --no-commit          # только выкатить уже запушенный main
#
# Переопределяемые переменные окружения:
#   SSH_HOST (deploy-хост, деф. 192.168.1.212)   APP_DIR (деф. /opt/specinter)
# ---------------------------------------------------------------------------
set -euo pipefail

SSH_HOST="${SSH_HOST:-192.168.1.212}"
APP_DIR="${APP_DIR:-/opt/specinter}"

# --- разбор аргументов ---
COMMIT_MSG=""
DO_COMMIT=1
while [ $# -gt 0 ]; do
  case "$1" in
    -m) COMMIT_MSG="${2:-}"; shift 2 ;;
    --no-commit) DO_COMMIT=0; shift ;;
    -h|--help) sed -n '2,30p' "$0"; exit 0 ;;
    *) echo "Неизвестный аргумент: $1" >&2; exit 2 ;;
  esac
done

# --- перейти в корень репо new/ ---
cd "$(cd "$(dirname "$0")/.." && pwd)"

branch=$(git rev-parse --abbrev-ref HEAD)
if [ "$branch" != "main" ]; then
  echo "✗ Ожидается ветка main, а сейчас '$branch'. Прод собирается из main." >&2
  exit 1
fi

# --- 1) коммит + пуш ---
if [ "$DO_COMMIT" = 1 ] && [ -n "$(git status --porcelain)" ]; then
  [ -n "$COMMIT_MSG" ] || COMMIT_MSG="deploy: обновление $(date '+%Y-%m-%d %H:%M')"
  echo "→ Коммит локальных правок: $COMMIT_MSG"
  git add -A
  git commit -m "$COMMIT_MSG"
fi

echo "→ Пуш в origin/main"
git push origin main

# --- 2) обновление на сервере ---
echo "→ Обновление на $SSH_HOST:$APP_DIR"
ssh "$SSH_HOST" "APP_DIR='$APP_DIR' bash -s" <<'REMOTE'
set -euo pipefail
cd "$APP_DIR"

before=$(git rev-parse HEAD)
git fetch --quiet origin main
git reset --hard origin/main
after=$(git rev-parse HEAD)

if [ "$before" = "$after" ]; then
  echo "  main уже актуален ($after) — пересобирать нечего, но применю рестарт."
  changed=""
else
  echo "  $before → $after"
  changed=$(git diff --name-only "$before" "$after")
fi

# Прод-overlay (nginx/HTTPS) — только если в .env задан домен.
COMPOSE="-f docker-compose.yml"
if [ -f .env ] && grep -qE '^DOMAIN=.+' .env; then
  COMPOSE="$COMPOSE -f docker-compose.prod.yml"
  echo "  compose: base + prod (HTTPS overlay)"
else
  echo "  compose: base (LAN/IP)"
fi

# Нужна ли пересборка образов? Только при смене зависимостей / Dockerfile.
rebuild=""
if echo "$changed" | grep -qE '^web/(package\.json|package-lock\.json|Dockerfile)$'; then
  rebuild="$rebuild web"
fi
if echo "$changed" | grep -qE '^(api|bot)/(requirements.*\.txt|Dockerfile)$'; then
  rebuild="$rebuild api worker bot"
fi

if [ -n "$rebuild" ]; then
  echo "→ Пересборка образов:$rebuild (сменились зависимости)"
  docker compose $COMPOSE build $rebuild
  docker compose $COMPOSE up -d
else
  echo "→ Код в bind-mount: применяю compose и рестартую web/api"
  docker compose $COMPOSE up -d            # подхватить возможные правки compose/.env
  docker compose $COMPOSE restart web api  # пересборка не нужна — next dev/uvicorn перечитают исходники
fi

docker image prune -f >/dev/null 2>&1 || true

echo "→ Статус контейнеров:"
docker compose $COMPOSE ps
REMOTE

echo "✓ Готово. Проверьте витрину (по IP сервера :3000 или по домену, если включён HTTPS)."
