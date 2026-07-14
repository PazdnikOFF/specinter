import Link from "next/link";
import SearchBox from "../SearchBox";
import { apiSearch, imgUrl } from "../../lib/api";
import QuoteForm from "../QuoteForm";

export default async function SearchPage({
  searchParams,
}: {
  searchParams: { q?: string };
}) {
  const q = searchParams.q ?? "";
  const data = q ? await apiSearch(q) : { total: 0, hits: [] };
  const hits = data.hits ?? [];

  return (
    <main className="container">
      <div style={{ padding: "36px 0 0" }}>
        <SearchBox initial={q} />
      </div>
      {q && (
        <p className="hint" style={{ textAlign: "center" }}>
          Найдено {data.total ?? hits.length} по запросу «{q}»
        </p>
      )}

      {hits.length === 0 ? (
        <div style={{ padding: "40px 0" }}>
          {q && <div className="empty" style={{ paddingBottom: 24 }}>Ничего не найдено по «{q}». Уточните артикул или оставьте заявку.</div>}
          {q && <QuoteForm query={q} />}
        </div>
      ) : (
        <div className="grid">
          {hits.map((h: any) => (
            <Link key={h.id} href={`/product/${h.id}`} className="card">
              <div className="thumb">
                {h.primary_image ? <img src={imgUrl(h.primary_image)!} alt={h.name} /> : <span>нет фото</span>}
              </div>
              <div className="art">{h.manufacturer_article || "—"}</div>
              <div className="name">{h.name || "Без названия"}</div>
              <div className="meta">
                <span className="price">
                  {h.min_price ? `${Math.round(h.min_price).toLocaleString("ru-RU")} ₽` : "Цена по запросу"}
                </span>
                <span className={`badge ${h.in_stock ? "in" : "out"}`}>
                  {h.in_stock ? "в наличии" : "под заказ"}
                </span>
              </div>
            </Link>
          ))}
        </div>
      )}
    </main>
  );
}
