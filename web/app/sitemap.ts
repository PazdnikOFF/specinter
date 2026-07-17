import type { MetadataRoute } from "next";

const SITE = process.env.NEXT_PUBLIC_SITE_URL || "";
const API = process.env.API_INTERNAL_URL || "http://api:8000";

// Обновляем карту раз в сутки (не на каждый запрос — 29k товаров).
export const revalidate = 86400;

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const base = SITE;
  const staticUrls: MetadataRoute.Sitemap = ["", "/catalog", "/bystro", "/oplata", "/o-nas"]
    .map((u) => ({ url: `${base}${u || "/"}`, changeFrequency: "weekly" }));
  try {
    const r = await fetch(`${API}/api/sitemap`, { next: { revalidate: 86400 } });
    const d = await r.json();
    const cats: MetadataRoute.Sitemap = (d.categories || []).map((id: number) => ({
      url: `${base}/catalog?cat=${id}`, changeFrequency: "weekly",
    }));
    const prods: MetadataRoute.Sitemap = (d.products || []).map((p: any) => ({
      url: `${base}/product/${p.id}`,
      lastModified: p.ts ? new Date(p.ts * 1000) : undefined,
      changeFrequency: "monthly",
    }));
    return [...staticUrls, ...cats, ...prods];
  } catch {
    return staticUrls;
  }
}
