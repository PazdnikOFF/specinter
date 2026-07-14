const BASE = process.env.API_INTERNAL_URL || "http://api:8000";
export const MEDIA = process.env.NEXT_PUBLIC_MEDIA_URL || "http://localhost:8081";
// Клиентские запросы идут относительным путём — Next.js проксирует /api/* на бэкенд
// (next.config.js). Работает при любом адресе доступа, без CORS. "" = тот же origin.
export const API_PUBLIC = "";
export const imgUrl = (name?: string | null) => (name ? `${MEDIA}/${name}` : null);
// Миниатюра (≤400px) для сеток/плиток каталога; если её нет — nginx отдаёт оригинал.
// На карточке товара (крупное фото) используем полноразмерный imgUrl.
export const thumbUrl = (name?: string | null) => (name ? `${MEDIA}/thumbs/${name}` : null);

export async function apiSearch(q: string, limit = 24, offset = 0) {
  const url = `${BASE}/api/search?q=${encodeURIComponent(q)}&limit=${limit}&offset=${offset}`;
  const r = await fetch(url, { cache: "no-store" });
  if (!r.ok) return { query: q, total: 0, hits: [] };
  return r.json();
}

export async function apiProduct(id: string) {
  const r = await fetch(`${BASE}/api/products/${id}`, { cache: "no-store" });
  if (!r.ok) return null;
  return r.json();
}

export async function apiCategories() {
  const r = await fetch(`${BASE}/api/categories`, { cache: "no-store" });
  if (!r.ok) return [];
  return r.json();
}

export async function apiCatalogRoots() {
  const r = await fetch(`${BASE}/api/catalog/roots`, { cache: "no-store" });
  if (!r.ok) return { root_id: null, items: [] };
  return r.json();
}

export async function apiCatalogBrowse(params: {
  category: string | number;
  sort?: string;
  stock?: boolean;
  page?: number;
  per_page?: number;
}) {
  const qs = new URLSearchParams({
    category: String(params.category),
    sort: params.sort || "default",
    stock: params.stock ? "true" : "false",
    page: String(params.page || 1),
    per_page: String(params.per_page || 24),
  });
  const r = await fetch(`${BASE}/api/catalog/browse?${qs}`, { cache: "no-store" });
  if (!r.ok) return null;
  return r.json();
}

export async function apiMetrics() {
  const r = await fetch(`${BASE}/api/admin/metrics`, { cache: "no-store" });
  if (!r.ok) return null;
  return r.json();
}
