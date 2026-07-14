import Link from "next/link";
import { apiProduct, imgUrl } from "../../../lib/api";
import AddToCart from "../../AddToCart";

export default async function ProductPage({ params }: { params: { id: string } }) {
  const p = await apiProduct(params.id);
  if (!p) {
    return <main className="container"><div className="empty">Товар не найден.</div></main>;
  }
  const crumbs = p.categories?.map((c: any) => c.name).join(" · ");

  return (
    <main className="container">
      <div className="crumbs">
        <Link href="/catalog">Каталог</Link>{crumbs ? ` · ${crumbs}` : ""}
      </div>
      <div className="pwrap">
        <div className="pimg">
          {p.primary_image ? <img src={imgUrl(p.primary_image)!} alt={p.name} /> : "нет фото"}
        </div>
        <div>
          <div className="art" style={{ color: "var(--accent)", fontWeight: 600 }}>
            {p.manufacturer_article}
          </div>
          <h1 style={{ fontSize: 30, fontWeight: 600, letterSpacing: "-0.02em", margin: "8px 0" }}>
            {p.name}
          </h1>
          {p.brand && <span className="tag">{p.brand}</span>}

          {(() => {
            const best = (p.offers || []).filter((o: any) => o.price != null)
              .sort((a: any, b: any) => a.price - b.price)[0];
            return (
              <div style={{ display: "flex", alignItems: "center", gap: 16, margin: "16px 0" }}>
                <span className="price" style={{ fontSize: 24 }}>
                  {best ? `${Math.round(best.price).toLocaleString("ru-RU")} ₽` : "Цена по запросу"}
                </span>
                <AddToCart product={{ product_id: p.id, article: p.manufacturer_article,
                  name: p.name, price: best ? Math.round(best.price) : 0 }} />
              </div>
            );
          })()}

          <h2 className="section">Предложения</h2>
          {p.offers?.length ? (
            p.offers.map((o: any) => (
              <div className="offer" key={o.id}>
                <span>
                  {o.supplier || "Поставщик"}{o.city ? `, ${o.city}` : ""}
                  <span className={`badge ${o.in_stock ? "in" : "out"}`} style={{ marginLeft: 10 }}>
                    {o.in_stock ? "в наличии" : "под заказ"}
                  </span>
                </span>
                <span className="price">
                  {o.price ? `${Math.round(o.price).toLocaleString("ru-RU")} ₽` : "по запросу"}
                </span>
              </div>
            ))
          ) : (
            <p className="muted">Цена по запросу — уточните у оператора.</p>
          )}

          {p.analogs?.length > 0 && (
            <>
              <h2 className="section">Аналоги ({p.analogs.length})</h2>
              {p.analogs.map((a: any, i: number) => (
                <div className="analog" key={i}>
                  <span>
                    {a.linked_product_id
                      ? <Link href={`/product/${a.linked_product_id}`} style={{ color: "var(--accent)" }}>{a.analog_article}</Link>
                      : <span className="art">{a.analog_article}</span>}
                  </span>
                  <span className="muted">{a.analog_name}</span>
                </div>
              ))}
            </>
          )}
        </div>
      </div>
    </main>
  );
}
