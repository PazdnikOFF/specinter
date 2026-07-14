#!/usr/bin/env bash
# Локальный оркестратор переноса изображений со старого сервера в новый media-контейнер.
# 1) строит манифест нужных файлов из Postgres,
# 2) заливает манифест+сборщик на сервер, запускает сборщик (пакует только нужные),
# 3) скачивает архив,
# 4) распаковывает в docker-том mediadata (его отдаёт nginx-сервис media).
#
# Требует: поднятый postgres нового портала, SSH-ключ к серверу.
set -euo pipefail
cd "$(dirname "$0")/.."

SSH_KEY="${SSH_KEY:-$HOME/.ssh/id_ed25519_pazdnikoff}"
SSH_HOST="${SSH_HOST:-c26864@80.87.104.50}"
SSH="ssh -o BatchMode=yes -i $SSH_KEY $SSH_HOST"
VOLUME="${VOLUME:-new_mediadata}"

echo "[1/5] Манифест нужных изображений из Postgres…"
docker compose exec -T postgres psql -U specinter -d specinter -t -A -c "
  SELECT DISTINCT primary_image FROM products WHERE primary_image IS NOT NULL AND primary_image<>''
  UNION
  SELECT DISTINCT url FROM product_images WHERE url IS NOT NULL AND url<>'';" > scripts/needed_images.txt
echo "    позиций: $(wc -l < scripts/needed_images.txt)"

echo "[2/5] Заливаю манифест и сборщик на сервер…"
scp -o BatchMode=yes -i "$SSH_KEY" scripts/needed_images.txt "$SSH_HOST":/tmp/needed_images.txt
scp -o BatchMode=yes -i "$SSH_KEY" scripts/collect_images_remote.sh "$SSH_HOST":/tmp/collect_images_remote.sh

echo "[3/5] Запускаю сборщик на сервере (пакуются только нужные файлы)…"
$SSH 'bash /tmp/collect_images_remote.sh /tmp/needed_images.txt /tmp/specinter_images.tar.gz'

echo "[4/5] Скачиваю архив…"
scp -o BatchMode=yes -i "$SSH_KEY" "$SSH_HOST":/tmp/specinter_images.tar.gz scripts/specinter_images.tar.gz
echo "    размер: $(du -h scripts/specinter_images.tar.gz | cut -f1)"

echo "[5/5] Распаковываю в docker-том ${VOLUME} ..."
docker volume create "$VOLUME" >/dev/null
docker run --rm -v "$VOLUME":/data -v "$PWD/scripts":/src alpine \
  sh -c 'tar -xzf /src/specinter_images.tar.gz -C /data && chmod -R a+rX /data && echo "файлов в томе: $(ls /data | wc -l)"'

echo "Готово. Проверьте media: http://localhost:8081/<имя_файла>"
