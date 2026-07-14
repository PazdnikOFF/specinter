"use client";

// Админ-запросы идут на ТОТ ЖЕ origin (относительный путь): Next.js проксирует
// /api/* на бэкенд (см. next.config.js). Cookie-сессия становится first-party —
// это надёжнее межоригинных cookie (localhost:3000 ↔ :8010).
export async function adminFetch(path: string, opts: RequestInit = {}) {
  const isForm = opts.body instanceof FormData;
  const headers: Record<string, string> = { ...(opts.headers as Record<string, string>) };
  if (opts.body && !isForm && !headers["Content-Type"]) {
    headers["Content-Type"] = "application/json";
  }
  return fetch(path, { ...opts, credentials: "same-origin", headers });
}

export async function adminJson<T = any>(path: string, opts: RequestInit = {}): Promise<T | null> {
  const r = await adminFetch(path, opts);
  if (!r.ok) return null;
  return r.json();
}
