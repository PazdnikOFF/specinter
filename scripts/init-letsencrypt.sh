#!/usr/bin/env bash
# Первичный выпуск сертификата Let's Encrypt для прод-сайта.
# Запускать НА ХОСТЕ из каталога проекта (где docker-compose.yml):
#   ./scripts/init-letsencrypt.sh
#
# Логика (канонический паттерн nginx + certbot):
#   1) кладём временный самоподписанный «заглушечный» сертификат — чтобы nginx стартовал;
#   2) поднимаем proxy (nginx отвечает на ACME-челлендж по /.well-known/acme-challenge);
#   3) удаляем заглушку и запрашиваем НАСТОЯЩИЙ сертификат у Let's Encrypt (webroot);
#   4) перезагружаем nginx.
#
# ТРЕБОВАНИЯ: DOMAIN резолвится на этот сервер; порты 80/443 доступны из интернета.
set -euo pipefail
cd "$(dirname "$0")/.."

COMPOSE="docker compose -f docker-compose.yml -f docker-compose.prod.yml"

# читаем DOMAIN / LETSENCRYPT_EMAIL из .env
set -a; [ -f .env ] && . ./.env; set +a
DOMAIN="${DOMAIN:?задайте DOMAIN в .env}"
EMAIL="${LETSENCRYPT_EMAIL:?задайте LETSENCRYPT_EMAIL в .env}"
STAGING="${LETSENCRYPT_STAGING:-0}"   # 1 = тестовый CA (без лимитов), для отладки

cert_path="/etc/letsencrypt/live/$DOMAIN"
echo "### Домен: $DOMAIN, e-mail: $EMAIL, staging: $STAGING"

echo "### 1/4 Заглушечный сертификат (чтобы nginx стартовал)…"
$COMPOSE run --rm --entrypoint "sh -c '
  mkdir -p $cert_path &&
  openssl req -x509 -nodes -newkey rsa:2048 -days 1 \
    -keyout $cert_path/privkey.pem -out $cert_path/fullchain.pem -subj \"/CN=$DOMAIN\"
'" certbot

echo "### 2/4 Поднимаю proxy…"
$COMPOSE up -d proxy

echo "### 3/4 Удаляю заглушку и запрашиваю настоящий сертификат…"
$COMPOSE run --rm --entrypoint "sh -c 'rm -rf /etc/letsencrypt/live/$DOMAIN /etc/letsencrypt/archive/$DOMAIN /etc/letsencrypt/renewal/$DOMAIN.conf'" certbot
staging_arg=""; [ "$STAGING" != "0" ] && staging_arg="--staging"
$COMPOSE run --rm --entrypoint "certbot certonly --webroot -w /var/www/certbot \
  $staging_arg --email $EMAIL --agree-tos --no-eff-email --non-interactive \
  -d $DOMAIN" certbot

echo "### 4/4 Перезагружаю nginx…"
$COMPOSE exec proxy nginx -s reload || $COMPOSE up -d proxy

echo "### Готово. Проверьте: https://$DOMAIN"
