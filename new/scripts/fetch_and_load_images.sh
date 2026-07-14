#!/usr/bin/env bash
# Локальный оркестратор переноса изображений со старого сервера в новый media-контейнер.
# 1) строит манифест нужных файлов из Postgres (товары + галерея + категории),
# 2) заливает манифест+сборщик, готовит на сервере СИМЛИНК-стадию полноразмеров,
# 3) СТРИМИТ tar (-hcz, следуя симлинкам) прямо к нам — БЕЗ большого архива на сервере,
# 4) распаковывает в docker-том mediadata (его отдаёт nginx-сервис media),
# 5) чистит стадию на сервере.
#
# Такой подход не переполняет /tmp сервера (архив originals весит гигабайты) и
# тянет ПОЛНОРАЗМЕРНЫЕ изображения (сборщик выбирает самый большой файл среди бакетов).
#
# Требует: поднятый postgres нового портала, SSH-ключ к серверу.
set -euo pipefail
cd "$(dirname "$0")/.."

SSH_KEY="${SSH_KEY:-$HOME/.ssh/id_ed25519_pazdnikoff}"
SSH_HOST="${SSH_HOST:-c26864@80.87.104.50}"
SSH="ssh -o BatchMode=yes -i $SSH_KEY $SSH_HOST"
VOLUME="${VOLUME:-new_mediadata}"
STAGE="/tmp/imgstage"

echo "[1/5] Манифест нужных изображений из Postgres…"
docker compose exec -T postgres psql -U specinter -d specinter -t -A -c "
  SELECT DISTINCT primary_image FROM products WHERE primary_image IS NOT NULL AND primary_image<>''
  UNION
  SELECT DISTINCT url FROM product_images WHERE url IS NOT NULL AND url<>''
  UNION
  SELECT DISTINCT image FROM categories WHERE image IS NOT NULL AND image<>'';" > scripts/needed_images.txt
echo "    позиций: $(grep -c . scripts/needed_images.txt)"

echo "[2/5] Заливаю манифест и сборщик, готовлю симлинк-стадию полноразмеров…"
scp -o BatchMode=yes -i "$SSH_KEY" scripts/needed_images.txt "$SSH_HOST":/tmp/needed_images.txt
scp -o BatchMode=yes -i "$SSH_KEY" scripts/collect_images_remote.sh "$SSH_HOST":/tmp/collect_images_remote.sh
$SSH "bash /tmp/collect_images_remote.sh /tmp/needed_images.txt $STAGE"

echo "[3/5] Стримлю полноразмерный tar к нам (без архива на сервере)…"
$SSH "tar -hczf - -C $STAGE ." > scripts/specinter_full.tar.gz
gzip -t scripts/specinter_full.tar.gz || { echo "архив битый — прерываю" >&2; exit 1; }
echo "    размер: $(du -h scripts/specinter_full.tar.gz | cut -f1)"

echo "[4/5] Распаковываю в docker-том ${VOLUME} (с перезаписью)…"
docker volume create "$VOLUME" >/dev/null
docker run --rm -v "$VOLUME":/data -v "$PWD/scripts":/src alpine \
  sh -c 'tar -xzf /src/specinter_full.tar.gz -C /data && chmod -R a+rX /data && echo "файлов в томе: $(ls /data | wc -l)"'

echo "[5/5] Чищу стадию на сервере…"
$SSH "rm -rf $STAGE"

echo "Готово. Проверьте media: http://localhost:8081/<имя_файла>"
