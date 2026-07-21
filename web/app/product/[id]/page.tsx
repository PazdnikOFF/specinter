import Link from "next/link";
import type { Metadata } from "next";
import { apiProduct, imgUrl, thumbUrl } from "../../../lib/api";

// SEO: заголовок/описание из артикула+названия+бренда (длинный хвост по номерам).
export async function generateMetadata({ params }: { params: { id: string } }): Promise<Metadata> {
  const p = await apiProduct(params.id);
  if (!p) return { title: "Товар не найден — СПЕЦИНТЕР" };
  const art = p.manufacturer_article || "";
  const title = `${art}${p.name ? " " + p.name : ""} — купить, цена | СПЕЦИНТЕР`.trim();
  const description =
    `${p.name || art}${p.brand ? ", " + p.brand : ""} — цена, наличие, срок поставки. `
    + `Артикул ${art}. Доставка по РФ, самовывоз Екатеринбург.`;
  return { title, description, alternates: { canonical: `/product/${params.id}` } };
}
import CartStepper from "../../CartStepper";
import ProductGallery from "./ProductGallery";
import ZoomImage from "../../ZoomImage";
import BackButton from "../../BackButton";
import Breadcrumbs from "../../Breadcrumbs";
import StockAlert from "./StockAlert";

// Строка «аналог / деталь на позиции»: артикул, наименование, наличие, цена «от N ₽».
function AnalogRow({ a }: { a: any }) {
  const info = (
    <>
      <span className="art">{a.analog_article}</span>
      {a.analog_name && <span className="analog-name">{a.analog_name}</span>}
      <span className="analog-meta">
        {a.brand && <span className="tag">{a.brand}</span>}
        {a.group_name && <span className="muted">{a.group_name}</span>}
        {!a.linked_product_id && <span className="muted">кросс-номер</span>}
      </span>
    </>
  );
  return (
    <div className="analog">
      {a.linked_product_id
        ? <Link href={`/product/${a.linked_product_id}`} className="analog-info analog-link">{info}</Link>
        : <span className="analog-info">{info}</span>}
      <span className="offer-buy" style={{ gap: 10 }}>
        {a.eta_days != null && <span className="muted" style={{ fontSize: 12 }}>~{a.eta_days} дн.</span>}
        {a.min_price != null ? (
          <>
            <span className={`badge ${a.in_stock ? "in" : "out"}`}>{a.in_stock ? "в наличии" : "под заказ"}</span>
            <span className="price">от {Math.round(a.min_price).toLocaleString("ru-RU")} ₽</span>
          </>
        ) : (
          <span className="badge out">под заказ</span>
        )}
      </span>
    </div>
  );
}

export default async function ProductPage({ params }: { params: { id: string } }) {
  const p = await apiProduct(params.id);
  if (!p) {
    return <main className="container"><div className="empty">Товар не найден.</div></main>;
  }
  // Для крошек берём САМУЮ ГЛУБОКУЮ применимость (полный путь до позиции в каталоге),
  // приоритет — записям с позицией на схеме.
  const bestAppl = (p.applicability || []).slice().sort((a: any, b: any) =>
    (b.position ? 1 : 0) - (a.position ? 1 : 0) || (b.trail?.length || 0) - (a.trail?.length || 0))[0];
  const trail = bestAppl?.trail || [];

  // --- SEO: Product + BreadcrumbList JSON-LD (rich-сниппеты цены/наличия в Google) ---
  // Абсолютные URL — если задан NEXT_PUBLIC_SITE_URL (после go-live домена); иначе относительные.
  const SITE = process.env.NEXT_PUBLIC_SITE_URL || "";
  const abs = (u?: string | null) => (u ? (SITE ? `${SITE}${u}` : u) : undefined);
  const ldPriced = (p.offers || []).filter((o: any) => o.price != null).sort((a: any, b: any) => a.price - b.price);
  const ldInStock = (p.offers || []).some((o: any) => o.in_stock);
  const productLd: any = {
    "@context": "https://schema.org/", "@type": "Product",
    name: p.name || p.manufacturer_article,
    sku: p.manufacturer_article, mpn: p.manufacturer_article,
    ...(p.brand ? { brand: { "@type": "Brand", name: p.brand } } : {}),
    ...(p.primary_image ? { image: [abs(imgUrl(p.primary_image))] } : {}),
    ...(p.name ? { description: p.name } : {}),
  };
  if (ldPriced[0]) {
    productLd.offers = {
      "@type": "Offer", priceCurrency: "RUB", price: Math.round(ldPriced[0].price),
      availability: ldInStock ? "https://schema.org/InStock" : "https://schema.org/PreOrder",
      ...(SITE ? { url: `${SITE}/product/${p.id}` } : {}),
    };
  }
  const ldCrumbs = [{ name: "Каталог", href: "/catalog" },
    ...trail.map((t: any) => ({ name: t.name, href: `/catalog?cat=${t.id}` })),
    { name: p.name || p.manufacturer_article, href: `/product/${p.id}` }];
  const breadcrumbLd = {
    "@context": "https://schema.org/", "@type": "BreadcrumbList",
    itemListElement: ldCrumbs.map((c, i) => ({
      "@type": "ListItem", position: i + 1, name: c.name,
      ...(SITE ? { item: `${SITE}${c.href}` } : {}),
    })),
  };

  return (
    <main className="container">
      <script type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(productLd) }} />
      <script type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />
      <div className="navrow">
        <BackButton />
        <Breadcrumbs items={[
          { name: "Каталог", href: "/catalog" },
          ...trail.map((t: any) => ({ name: t.name, href: `/catalog?cat=${t.id}` })),
          { name: p.name || p.manufacturer_article },
        ]} />
      </div>
      <div className="pwrap">
        <div className="pcol-left">
          <ProductGallery primary={p.primary_image} images={p.images} name={p.name} />
          {p.schemes?.length > 0 && (
            <div style={{ marginTop: 20 }}>
              <h2 className="section" style={{ marginTop: 0 }}>Схема узла</h2>
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
            </div>
          )}
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
            const priced = (p.offers || []).filter((o: any) => o.price != null)
              .sort((a: any, b: any) => a.price - b.price);
            const min = priced[0];
            // Своей цены нет — показываем минимум по группе взаимозаменяемости
            // (товар + аналоги), как это делал старый сайт. Помечаем «по аналогу»,
            // чтобы клиент понимал, что цена не самой этой позиции.
            const byGroup = !min && p.group_min_price != null;
            return (
              <div style={{ margin: "16px 0" }}>
                <span className="price" style={{ fontSize: 26 }}>
                  {min
                    ? `${priced.length > 1 ? "от " : ""}${Math.round(min.price).toLocaleString("ru-RU")} ₽`
                    : byGroup
                      ? `от ${Math.round(p.group_min_price).toLocaleString("ru-RU")} ₽`
                      : "Цена по запросу"}
                </span>
                {byGroup && (
                  <span className="muted" style={{ marginLeft: 8, fontSize: 13 }}>по аналогу</span>
                )}
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
          ) : p.offers_via_analogs?.length ? (
            /* Своих предложений нет — показываем предложения аналогов (как старый сайт).
               В корзину кладём ИМЕННО аналог: article/name/id берём из via_*. */
            <>
              <p className="muted" style={{ margin: "-4px 0 8px", fontSize: 13 }}>
                По этой позиции своих предложений нет — показаны предложения аналогов.
                В заказ добавится аналог, его артикул указан в строке.
              </p>
              {p.offers_via_analogs.map((o: any) => {
                const delivery = o.delivery_note
                  || (o.delivery_days != null ? `срок поставки ~${o.delivery_days} раб. дн.` : "срок уточняется");
                return (
                  <div className="offer" key={`via-${o.id}`}>
                    <div className="offer-info">
                      <span className={`badge ${o.in_stock ? "in" : "out"}`}>
                        {o.in_stock ? "в наличии" : "под заказ"}
                      </span>
                      <Link href={`/product/${o.via_product_id}`} className="analog-link art">
                        {o.via_article}
                      </Link>
                      <span className="muted offer-delivery">{delivery}</span>
                    </div>
                    <div className="offer-buy">
                      <span className="price">
                        {o.price ? `${Math.round(o.price).toLocaleString("ru-RU")} ₽` : "по запросу"}
                      </span>
                      <span className="offer-cart">
                        <CartStepper
                          product={{ product_id: o.via_product_id, article: o.via_article,
                            name: o.via_name, price: o.price ? Math.round(o.price) : 0 }}
                          quote={o.price == null} />
                      </span>
                    </div>
                  </div>
                );
              })}
            </>
          ) : (
            <div className="offer offer-request">
              <span className="muted" style={{ flex: 1, minWidth: 0 }}>Цена по запросу — укажите количество и запросите:</span>
              <span className="offer-cart">
                <CartStepper product={{ product_id: p.id, article: p.manufacturer_article,
                  name: p.name, price: 0 }} quote />
              </span>
            </div>
          )}

          {/* Подписка на поступление — если товара нет в наличии ни по одному предложению. */}
          {!(p.offers || []).some((o: any) => o.in_stock) && (
            <StockAlert productId={p.id} />
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
              {p.analogs.map((a: any, i: number) => <AnalogRow a={a} key={i} />)}
            </>
          )}

          {p.install_options?.length > 0 && (
            <>
              <h2 className="section">Возможные варианты установки ({p.install_options.length})</h2>
              <p className="muted" style={{ margin: "-4px 0 8px", fontSize: 13 }}>
                Детали, которые встают на это место как вариант, но не являются прямой
                заменой — уточните применимость перед заказом.
              </p>
              {p.install_options.map((a: any, i: number) => <AnalogRow a={a} key={i} />)}
            </>
          )}

          {p.position_variants?.length > 0 && (
            <>
              <h2 className="section">На этой позиции схемы ({p.position_variants.length})</h2>
              <p className="muted" style={{ margin: "-4px 0 8px", fontSize: 13 }}>
                Другие детали, стоящие на том же номере позиции (напр. левого/правого исполнения) — сверьте наименование.
              </p>
              {p.position_variants.map((a: any, i: number) => <AnalogRow a={a} key={i} />)}
            </>
          )}
        </div>
      </div>
    </main>
  );
}
