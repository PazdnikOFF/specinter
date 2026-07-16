/** @type {import('next').NextConfig} */
const API_INTERNAL = process.env.API_INTERNAL_URL || "http://api:8000";
const MEDIA_INTERNAL = process.env.MEDIA_INTERNAL_URL || "http://media:80";

module.exports = {
  reactStrictMode: true,
  // Прод-сборка: standalone-вывод (.next/standalone/server.js) тянет только нужные
  // зависимости — рантайм в разы легче dev-режима. См. web/Dockerfile (stage prod).
  output: "standalone",
  env: {
    API_INTERNAL_URL: API_INTERNAL,
  },
  // Прокси через сам Next.js → всё с ТОГО ЖЕ origin (работает по LAN/интернету,
  // без зависимости от localhost). /api/* → бэкенд, /media/* → контейнер изображений.
  async rewrites() {
    return [
      { source: "/api/:path*", destination: `${API_INTERNAL}/api/:path*` },
      { source: "/media/:path*", destination: `${MEDIA_INTERNAL}/:path*` },
    ];
  },
};
