#!/usr/bin/env bash
# Выполняется НА СТАРОМ СЕРВЕРЕ (c26864@80.87.104.50).
# Берёт манифест нужных имён файлов (из БД нового портала), находит их в
# бакетах files/{2,0,1,3} (2 — оригинал, дальше ресайзы) и пакует ТОЛЬКО их.
#
# Использование:
#   ./collect_images_remote.sh /tmp/needed_images.txt /tmp/specinter_images.tar.gz
set -euo pipefail

MANIFEST="${1:-/tmp/needed_images.txt}"
OUT="${2:-/tmp/specinter_images.tar.gz}"
WWW="/home/c26864/specinter.ru/www"
BUCKETS=(2 0 1 3)                 # порядок предпочтения: сначала оригинал
STAGE="$(mktemp -d)"

[ -f "$MANIFEST" ] || { echo "нет манифеста: $MANIFEST" >&2; exit 1; }
cd "$WWW/files"

found=0; missing=0
: > /tmp/images_missing.txt
while IFS= read -r name; do
  [ -z "$name" ] && continue
  src=""
  for b in "${BUCKETS[@]}"; do
    if [ -f "$b/$name" ]; then src="$b/$name"; break; fi
  done
  if [ -n "$src" ]; then
    cp -n "$src" "$STAGE/$name"
    found=$((found+1))
  else
    echo "$name" >> /tmp/images_missing.txt
    missing=$((missing+1))
  fi
done < "$MANIFEST"

echo "найдено: $found, не найдено: $missing (список: /tmp/images_missing.txt)"
tar -czf "$OUT" -C "$STAGE" .
echo "архив: $OUT ($(du -h "$OUT" | cut -f1))"
rm -rf "$STAGE"
