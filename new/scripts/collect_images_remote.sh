#!/usr/bin/env bash
# Выполняется НА СТАРОМ СЕРВЕРЕ (c26864@80.87.104.50).
# Готовит СИМЛИНК-стадию нужных изображений в ПОЛНОМ размере, НЕ копируя файлы
# (экономит диск: архив потом стримится наружу через `tar -hcz`, см. fetch_and_load_images.sh).
#
# ВАЖНО про бакеты files/{0,1,2,3}: одно имя лежит во всех бакетах РАЗНЫМИ размерами.
# Бакет 2 — МИНИАТЮРА (напр. 41x56!), полноразмер обычно в 0 или 3, порядок НЕ единообразен.
# Поэтому выбираем НАИБОЛЬШИЙ по размеру файл — это гарантированно оригинал/полноразмер.
#
# Использование:
#   ./collect_images_remote.sh /tmp/needed_images.txt /tmp/imgstage
set -euo pipefail

MANIFEST="${1:-/tmp/needed_images.txt}"
STAGE="${2:-/tmp/imgstage}"
WWW="/home/c26864/specinter.ru/www/files"
BUCKETS=(0 1 2 3)

[ -f "$MANIFEST" ] || { echo "нет манифеста: $MANIFEST" >&2; exit 1; }
rm -rf "$STAGE"; mkdir -p "$STAGE"

found=0; missing=0
: > /tmp/images_missing.txt
while IFS= read -r name; do
  [ -z "$name" ] && continue
  best=""; bestsize=0
  for b in "${BUCKETS[@]}"; do
    f="$WWW/$b/$name"
    if [ -f "$f" ]; then
      sz=$(stat -c%s "$f" 2>/dev/null || wc -c < "$f")
      if [ "$sz" -gt "$bestsize" ]; then bestsize=$sz; best="$f"; fi
    fi
  done
  if [ -n "$best" ]; then
    ln -sf "$best" "$STAGE/$name"          # симлинк, не копия
    found=$((found+1))
  else
    echo "$name" >> /tmp/images_missing.txt
    missing=$((missing+1))
  fi
done < "$MANIFEST"

echo "staged: $found, не найдено: $missing (список: /tmp/images_missing.txt)"
echo "стадия: $STAGE ($(du -sh "$STAGE" | cut -f1) симлинков)"
