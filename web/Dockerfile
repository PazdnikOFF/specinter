# Мультистейдж: dev (hot-reload) и prod (standalone) из одного файла.
#   dev  — next dev, исходники подмонтированы bind-mount'ом (см. docker-compose.override.yml).
#   prod — next build + output:standalone → node server.js. Образ и потребление RAM
#          в разы меньше dev-режима (нет HMR/source-maps/файлового watcher'а).

FROM node:20-alpine AS base
WORKDIR /app
ENV NEXT_TELEMETRY_DISABLED=1

# --- Зависимости (общий слой) ------------------------------------------------
FROM base AS deps
COPY package.json ./
RUN npm install

# --- Dev: next dev (используется через docker-compose.override.yml) -----------
FROM base AS dev
ENV NODE_ENV=development
COPY --from=deps /app/node_modules ./node_modules
COPY . .
EXPOSE 3000
CMD ["npm", "run", "dev"]

# --- Сборка прод-бандла -------------------------------------------------------
FROM base AS builder
ENV NODE_ENV=production
COPY --from=deps /app/node_modules ./node_modules
COPY . .
# Публичные NEXT_PUBLIC_* инлайнятся на этапе сборки → пробрасываем как build-args.
ARG NEXT_PUBLIC_TG_BOT=""
ARG NEXT_PUBLIC_WA_NUMBER=""
ARG NEXT_PUBLIC_MAX_BOT=""
ENV NEXT_PUBLIC_TG_BOT=$NEXT_PUBLIC_TG_BOT \
    NEXT_PUBLIC_WA_NUMBER=$NEXT_PUBLIC_WA_NUMBER \
    NEXT_PUBLIC_MAX_BOT=$NEXT_PUBLIC_MAX_BOT
RUN npm run build

# --- Prod: минимальный рантайм на standalone-выводе ---------------------------
FROM base AS prod
ENV NODE_ENV=production
# standalone тащит только реально используемые зависимости + сервер.
COPY --from=builder /app/public ./public
COPY --from=builder /app/.next/standalone ./
COPY --from=builder /app/.next/static ./.next/static
EXPOSE 3000
CMD ["node", "server.js"]
