/** @type {import('next').NextConfig} */
module.exports = {
  reactStrictMode: true,
  // Внутренний URL API (server components) и публичный (браузер)
  env: {
    API_INTERNAL_URL: process.env.API_INTERNAL_URL || "http://api:8000",
  },
};
