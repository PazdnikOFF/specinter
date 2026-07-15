import Link from "next/link";
import { apiSearch, thumbUrl } from "../../lib/api";
import QuoteForm from "../QuoteForm";

const PER_PAGE = 24;

export default async function SearchPage({
  searchParams,
}: {
  searchParams: { q?: string; page?: string };
}) {
  const q = searchParams.q ?? "";
  const page = Math.max(1, parseInt(searchParams.page || "1") || 1);
  const data = q
    ? await apiSearch(q, PER_PAGE, (page - 1) * PER_PAGE)
    : { total: 0, hits: [] };
  const hits = data.hits ?? [];
  const total = data.total ?? hits.length;
  const pages = Math.max(1, Math.ceil(total / PER_PAGE));

  const pageHref = (p: number) => `/search?q=${encodeURIComponent(q)}&page=${p}`;

  return (
    <main className="container">
      <div style={{ padding: "36px 0 0" }}>
        <SearchBox initial={q} />
      </div>
      {q && (
        <p className="hint" style={{ textAlign: "center" }}>
          Найдено {total.toLocaleString("ru-RU")} по запросу «{q}»
          {pages > 1 ? ` · стр. ${page} из ${pages}` : ""}
        </p>
      )}

      {hits.length === 0 ? (
        <div style={{ padding: "40px 0" }}>
          {q && <div className="empty" style={{ paddingBottom: 24 }}>Ничего не найдено по «{q}». Уточните артикул или оставьте заявку.</div>}
          {q && <QuoteForm query={q} />}
        </div>
      ) : (
        <>
          <div className="grid">
            {hits.map((h: any) => (
              <Link key={h.id} href={`/product/${h.id}`} className="card">
                <div className="thumb">
                  {h.primary_image ? <img src={thumbUrl(h.primary_image)!} alt={h.name} loading="lazy" decoding="async" /> : <span>нет фото</span>}
                </div>
                <div className="art">{h.manufacturer_article || "—"}</div>
                <div className="name">{h.name || "Без названия"}</div>
                {h.brand && <div className="muted" style={{ fontSize: 12 }}>{h.brand}</div>}
                <div className="meta">
                  {h.min_price ? (
                    <span className="price">{Math.round(h.min_price).toLocaleString("ru-RU")} ₽</span>
                  ) : null}
                  <span className={`badge ${h.in_stock ? "in" : "out"}`}>
                    {h.in_stock ? "в наличии" : "под заказ"}
                  </span>
                </div>
              </Link>
            ))}
          </div>
          {pages > 1 && (
            <div className="pager">
              {page > 1 && <Link className="pg" href={pageHref(page - 1)}>← Назад</Link>}
              <span className="pg-info">Стр. {page} из {pages}</span>
              {page < pages && <Link className="pg" href={pageHref(page + 1)}>Вперёд →</Link>}
            </div>
          )}
        </>
      )}
    </main>
  );
}
