#!/usr/bin/env bash
# Выполняется НА СТАРОМ СЕРВЕРЕ (c26864@80.87.104.50).
# Готовит СИМЛИНК-стадию нужных изображений в ПОЛНОМ размере, НЕ копируя файлы
# (архив потом стримится наружу через `tar -hcz`, см. fetch_and_load_images.sh).
#
# ВАЖНО про бакеты files/{0,1,2,3}: одно имя лежит во всех бакетах РАЗНЫМИ размерами.
# Порядок бакетов НЕ единообразен: полноразмер бывает в 0 или 3, миниатюра — в 2 и т.д.
#
# ПОЧЕМУ по ПИКСЕЛЯМ, а не по байтам: даунскейл-копия, пересохранённая с quality=100,
# ВЕСИТ БОЛЬШЕ настоящего оригинала (напр. 560x504@q100 = 144943Б > 1240x1116@default = 144705Б).
# Выбор «самого тяжёлого файла» тянул именно такие уменьшенные копии (в выборке 90% имён!).
# Поэтому выбираем файл с НАИБОЛЬШЕЙ ПЛОЩАДЬЮ В ПИКСЕЛЯХ (W*H) — это истинный оригинал;
# при равных пикселях берём более тяжёлый (меньше артефактов пересжатия).
#
# Использование:
#   ./collect_images_remote.sh /tmp/needed_images.txt /tmp/imgstage
set -euo pipefail

MANIFEST="${1:-/tmp/needed_images.txt}"
STAGE="${2:-/tmp/imgstage}"
WWW="/home/c26864/specinter.ru/www/files"
BUCKETS=(0 1 2 3)

[ -f "$MANIFEST" ] || { echo "нет манифеста: $MANIFEST" >&2; exit 1; }
command -v identify >/dev/null || { echo "нет ImageMagick identify" >&2; exit 1; }
rm -rf "$STAGE"; mkdir -p "$STAGE"

found=0; missing=0
: > /tmp/images_missing.txt
while IFS= read -r name; do
  [ -z "$name" ] && continue
  best=""; bestpx=-1; bestsz=-1
  for b in "${BUCKETS[@]}"; do
    f="$WWW/$b/$name"
    [ -f "$f" ] || continue
    dim=$(identify -format '%w %h' "$f[0]" 2>/dev/null) || continue   # [0] — первый кадр, безопасно
    [ -n "$dim" ] || continue
    px=$(( ${dim% *} * ${dim#* } ))
    sz=$(stat -c%s "$f" 2>/dev/null || wc -c < "$f")
    # больше пикселей — лучше; при равных пикселях — больше байт
    if [ "$px" -gt "$bestpx" ] || { [ "$px" -eq "$bestpx" ] && [ "$sz" -gt "$bestsz" ]; }; then
      bestpx=$px; bestsz=$sz; best="$f"
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
