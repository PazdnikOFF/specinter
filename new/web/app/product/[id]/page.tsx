import Link from "next/link";
import { apiProduct, imgUrl, thumbUrl } from "../../../lib/api";
import CartStepper from "../../CartStepper";
import ProductGallery from "./ProductGallery";
import ZoomImage from "../../ZoomImage";
import BackButton from "../../BackButton";
import Breadcrumbs from "../../Breadcrumbs";

export default async function ProductPage({ params }: { params: { id: string } }) {
  const p = await apiProduct(params.id);
  if (!p) {
    return <main className="container"><div className="empty">Товар не найден.</div></main>;
  }
  const trail = p.applicability?.[0]?.trail || [];

  return (
    <main className="container">
      <div className="navrow">
        <BackButton />
        <Breadcrumbs items={[
          { name: "Каталог", href: "/catalog" },
          ...trail.map((t: any) => ({ name: t.name, href: `/catalog?cat=${t.id}` })),
          { name: p.name || p.manufacturer_article },
        ]} />
      </div>
      <div className="pwrap">
        <ProductGallery primary={p.primary_image} images={p.images} name={p.name} />
        <div>
          <div className="art" style={{ color: "var(--accent)", fontWeight: 600 }}>
            {p.manufacturer_article}
          </div>
          <h1 style={{ fontSize: 30, fontWeight: 600, letterSpacing: "-0.02em", margin: "8px 0" }}>
            {p.name}
          </h1>
          {p.brand && <span className="tag">{p.brand}</span>}

          {(() => {
            const priced = (p.offers || []).filter((o: any) => o.price != null)
              .sort((a: any, b: any) => a.price - b.price);
            const min = priced[0];
            return (
              <div style={{ margin: "16px 0" }}>
                <span className="price" style={{ fontSize: 26 }}>
                  {min
                    ? `${priced.length > 1 ? "от " : ""}${Math.round(min.price).toLocaleString("ru-RU")} ₽`
                    : "Цена по запросу"}
                </span>
              </div>
            );
          })()}

          <h2 className="section">Предложения</h2>
          {p.offers?.length ? (
            p.offers.map((o: any) => {
              const delivery = o.delivery_note
                || (o.delivery_days != null ? `срок поставки ~${o.delivery_days} раб. дн.` : "срок уточняется");
              return (
                <div className="offer" key={o.id}>
                  <div className="offer-info">
                    <span className={`badge ${o.in_stock ? "in" : "out"}`}>
                      {o.in_stock ? "в наличии" : "под заказ"}
                    </span>
                    <span className="muted offer-delivery">{delivery}</span>
                  </div>
                  <div className="offer-buy">
                    <span className="price">
                      {o.price ? `${Math.round(o.price).toLocaleString("ru-RU")} ₽` : "по запросу"}
                    </span>
                    <span className="offer-cart">
                      {o.price != null
                        ? <CartStepper product={{ product_id: p.id, article: p.manufacturer_article,
                            name: p.name, price: Math.round(o.price) }} />
                        : <CartStepper product={{ product_id: p.id, article: p.manufacturer_article,
                            name: p.name, price: 0 }} quote />}
                    </span>
                  </div>
                </div>
              );
            })
          ) : (
            <div className="offer">
              <span className="muted">Цена по запросу — укажите количество и запросите:</span>
              <span className="offer-cart">
                <CartStepper product={{ product_id: p.id, article: p.manufacturer_article,
                  name: p.name, price: 0 }} quote />
              </span>
            </div>
          )}

          {p.schemes?.length > 0 && (
            <>
              <h2 className="section">Схема узла</h2>
              <div className="schemes">
                {p.schemes.map((s: any) => (
                  <div className="scheme" key={s.category_id}>
                    <span className="scheme-img">
                      <ZoomImage thumb={thumbUrl(s.scheme_image)!} full={imgUrl(s.scheme_image)!} alt={`Схема — ${s.name}`} />
                    </span>
                    <div className="scheme-cap">
                      {s.position && <span className="pos">Позиция на схеме: {s.position}</span>}
                      <Link href={`/catalog?cat=${s.category_id}`} className="link" style={{ display: "block", marginTop: 4 }}>
                        {s.name} →
                      </Link>
                    </div>
                  </div>
                ))}
              </div>
            </>
          )}

          {p.applicability?.length > 0 && (
            <>
              <h2 className="section">Применимость ({p.applicability.length})</h2>
              <div className="applic">
                {p.applicability.map((a: any) => (
                  <div className="applic-row" key={a.category_id}>
                    {a.position && <span className="pos" title="Позиция на схеме">поз. {a.position}</span>}
                    <span className="applic-trail">
                      {a.trail.map((t: any, i: number) => (
                        <span key={t.id}>
                          {i > 0 && <span className="sep"> › </span>}
                          <Link href={`/catalog?cat=${t.id}`}>{t.name}</Link>
                        </span>
                      ))}
                    </span>
                  </div>
                ))}
              </div>
            </>
          )}

          {p.analogs?.length > 0 && (
            <>
              <h2 className="section">Аналоги ({p.analogs.length})</h2>
              {p.analogs.map((a: any, i: number) => (
                <div className="analog" key={i}>
                  <span className="analog-info">
                    {a.linked_product_id
                      ? <Link href={`/product/${a.linked_product_id}`} className="art" style={{ textDecoration: "none" }}>{a.analog_article}</Link>
                      : <span className="art">{a.analog_article}</span>}
                    {a.analog_name && <span className="analog-name">{a.analog_name}</span>}
                    <span className="analog-meta">
                      {a.brand && <span className="tag">{a.brand}</span>}
                      {a.group_name && <span className="muted">{a.group_name}</span>}
                      {!a.linked_product_id && <span className="muted">кросс-номер</span>}
                    </span>
                  </span>
                  {a.min_price != null && (
                    <span className="offer-buy" style={{ gap: 10 }}>
                      {a.eta_days != null && <span className="muted" style={{ fontSize: 12 }}>~{a.eta_days} дн.</span>}
                      <span className={`badge ${a.in_stock ? "in" : "out"}`}>{a.in_stock ? "в наличии" : "под заказ"}</span>
                      <span className="price">от {Math.round(a.min_price).toLocaleString("ru-RU")} ₽</span>
                    </span>
                  )}
                </div>
              ))}
            </>
          )}
        </div>
      </div>
    </main>
  );
}
