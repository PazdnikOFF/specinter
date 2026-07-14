/** @type {import('next').NextConfig} */
const API_INTERNAL = process.env.API_INTERNAL_URL || "http://api:8000";

module.exports = {
  reactStrictMode: true,
  // Внутренний URL API (server components) и публичный (браузер)
  env: {
    API_INTERNAL_URL: API_INTERNAL,
  },
  // Прокси админ-запросов через сам Next.js → тот же origin, cookie-сессия
  // становится first-party (надёжнее межоригинных cookie localhost:3000↔:8010).
  // Публичные вызовы витрины идут по абсолютному NEXT_PUBLIC_API_URL и сюда не попадают.
  async rewrites() {
    return [{ source: "/api/:path*", destination: `${API_INTERNAL}/api/:path*` }];
  },
};
