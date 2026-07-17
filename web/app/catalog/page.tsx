import Link from "next/link";
import CatalogCard from "../CatalogCard";
import PositionCard from "../PositionCard";
import CatalogFilters from "../CatalogFilters";
import ZoomImage from "../ZoomImage";
import BackButton from "../BackButton";
import Breadcrumbs from "../Breadcrumbs";
import type { Metadata } from "next";
import { apiCatalogRoots, apiCatalogBrowse, imgUrl, thumbUrl } from "../../lib/api";

type SP = { cat?: string; sort?: string; stock?: string; page?: string; q?: string };

// SEO: заголовок из названия узла; canonical на ЧИСТУЮ категорию; фильтры/сортировки/
// страницы >1 — noindex (не плодить near-duplicate URL, не жечь краул-бюджет).
export async function generateMetadata({ searchParams }: { searchParams: SP }): Promise<Metadata> {
  const filtered = !!(searchParams.q || searchParams.stock === "1"
    || (searchParams.sort && searchParams.sort !== "default")
    || (searchParams.page && searchParams.page !== "1"));
  const robots = filtered ? { index: false, follow: true } : undefined;
  const cat = searchParams.cat;
  if (!cat) {
    return {
      title: "Каталог запчастей спецтехники — СПЕЦИНТЕР",
      description: "Запчасти китайской спецтехники: бульдозеры, погрузчики, экскаваторы, грузовики. Подбор по модели, узлу и артикулу.",
      alternates: { canonical: "/catalog" }, robots,
    };
  }
  let name = "Каталог";
  try { const d = await apiCatalogBrowse({ category: cat, per_page: 1 }); name = d?.category?.name || name; } catch {}
  return {
    title: `${name} — запчасти | СПЕЦИНТЕР`,
    description: `${name}: запчасти в наличии и под заказ, цены и сроки поставки. Подбор по артикулу и аналогам.`,
    alternates: { canonical: `/catalog?cat=${cat}` }, robots,
  };
}

// Разбиваем длинное имя узла на заголовок и уточнение из скобок.
function splitName(name: string): { main: string; sub?: string } {
  const m = name.match(/^(.*?)\s*\((.*)\)\s*$/);
  if (m) return { main: m[1].trim(), sub: m[2].trim() };
  return { main: name.trim() };
}

function GroupTile({ id, name, count, image }: { id: number; name: string; count: number; image?: string | null }) {
  const { main, sub } = splitName(name);
  return (
    <Link href={`/catalog?cat=${id}`} className="group-tile">
      <span className="group-thumb">
        {image ? <img src={thumbUrl(image)!} alt="" loading="lazy" decoding="async" /> : <span className="thumb-ph">нет фото</span>}
      </span>
      <span className="group-name">{main}</span>
      {sub && <span className="group-sub">{sub}</span>}
      <span className="group-count">{count.toLocaleString("ru-RU")} позиций</span>
    </Link>
  );
}

// Группировка по позиции на схеме: повторяющиеся позиции (>1 товара) — в одну карточку.
function groupByPosition(products: any[]) {
  const out: any[] = [];
  let i = 0;
  while (i < products.length) {
    const pos = products[i].position;
    if (pos == null || pos === "") { out.push({ single: products[i] }); i++; continue; }
    const grp = [products[i]];
    let j = i + 1;
    while (j < products.length && products[j].position === pos) { grp.push(products[j]); j++; }
    out.push(grp.length > 1 ? { position: pos, products: grp } : { single: grp[0] });
    i = j;
  }
  return out;
}

const SECTIONS: [string, string][] = [
  ["models", "Модели техники"],
  ["engines", "Двигатели"],
  ["brands", "Каталоги производителей"],
];

export default async function CatalogPage({ searchParams }: { searchParams: SP }) {
  const cat = searchParams.cat;

  // --- Лендинг каталога: секции по типу (модель / двигатель / производитель) ---
  if (!cat) {
    const { items } = await apiCatalogRoots();
    return (
      <main className="container">
        <div style={{ padding: "36px 0 4px" }}>
          <h1 className="cat-title">Каталог запчастей</h1>
          <p className="hint" style={{ textAlign: "left", marginTop: 6 }}>
            Выберите модель техники, двигатель или производителя — либо найдите деталь по артикулу через поиск сверху.
          </p>
        </div>
        {SECTIONS.map(([key, label]) => {
          const list = items.filter((i: any) => i.group === key);
          if (!list.length) return null;
          return (
            <section key={key} style={{ marginTop: 28 }}>
              <h2 className="sec-head">{label}</h2>
              <div className="tiles">
                {list.map((c: any) => (
                  <Link key={c.id} href={`/catalog?cat=${c.id}`} className="tile-cat">
                    <span className="tile-thumb">
                      {c.image ? <img src={thumbUrl(c.image)!} alt="" loading="lazy" decoding="async" /> : <span className="thumb-ph">нет фото</span>}
                    </span>
                    <span className="tile-name">{splitName(c.name).main}</span>
                    <span className="tile-count">{c.product_count.toLocaleString("ru-RU")} позиций</span>
                  </Link>
                ))}
              </div>
            </section>
          );
        })}
      </main>
    );
  }

  // --- Просмотр узла: подгруппы плитками + товары этого уровня ---
  const sort = searchParams.sort || "default";
  const stock = searchParams.stock === "1";
  const page = Math.max(1, parseInt(searchParams.page || "1") || 1);
  const per_page = 30;
  const gq = (searchParams.q || "").trim();
  const data = await apiCatalogBrowse({ category: cat, sort, stock, page, per_page, q: gq });

  if (!data) {
    return <main className="container"><div className="empty">Категория не найдена. <Link className="link" href="/catalog">← в каталог</Link></div></main>;
  }

  const pages = Math.max(1, Math.ceil(data.total / per_page));
  const qs = (p: Record<string, string | number>) => {
    const q = new URLSearchParams({ cat: cat!, sort, ...(stock ? { stock: "1" } : {}), ...(gq ? { q: gq } : {}) });
    for (const [k, v] of Object.entries(p)) q.set(k, String(v));
    return `/catalog?${q}`;
  };
  const nodeName = splitName(data.category.name).main;
  const hasGroups = data.children.length > 0 && !gq;   // при поиске в группе подгруппы прячем
  const hasProducts = data.products.length > 0;

  return (
    <main className="container">
      <div className="navrow">
        <BackButton />
        <Breadcrumbs items={[
          { name: "Каталог", href: "/catalog" },
          ...data.breadcrumbs.map((b: any) => ({ name: splitName(b.name).main, href: `/catalog?cat=${b.id}` })),
        ]} />
      </div>
      <h1 className="cat-title">{nodeName}</h1>

      {/* Схема узла (крупно в лайтбоксе) */}
      {!gq && data.category.image && (
        <span className="node-scheme" title="Схема узла — увеличить">
          <ZoomImage thumb={thumbUrl(data.category.image)!} full={imgUrl(data.category.image)!}
            alt={`Схема — ${data.category.name}`} />
        </span>
      )}

      {gq && (
        <p className="muted" style={{ margin: "12px 0 4px" }}>
          Найдено в разделе «{nodeName}»: {data.total.toLocaleString("ru-RU")} · <Link className="link" href={`/catalog?cat=${cat}`}>сбросить</Link>
        </p>
      )}

      {hasGroups && (
        <>
          <h2 className="sec-head">Узлы и группы</h2>
          <div className="tiles">
            {data.children.map((c: any) => (
              <GroupTile key={c.id} id={c.id} name={c.name} count={c.product_count} image={c.image} />
            ))}
          </div>
        </>
      )}

      {hasProducts && (
        <>
          {hasGroups && <h2 className="sec-head" style={{ marginTop: 34 }}>Позиции в этом разделе</h2>}
          <CatalogFilters total={data.total} />
          <div className="grid">
            {groupByPosition(data.products).map((g: any, idx: number) =>
              g.products
                ? <PositionCard key={`pos-${g.position}-${idx}`} position={g.position} products={g.products} />
                : <CatalogCard key={g.single.id} p={g.single} />
            )}
          </div>
          {pages > 1 && (
            <div className="pager">
              {page > 1 && <Link className="pg" href={qs({ page: page - 1 })}>← Назад</Link>}
              <span className="pg-info">Стр. {page} из {pages}</span>
              {page < pages && <Link className="pg" href={qs({ page: page + 1 })}>Вперёд →</Link>}
            </div>
          )}
        </>
      )}

      {!hasGroups && !hasProducts && (
        <div className="empty">В этом разделе пока нет позиций{stock ? " в наличии" : ""}.</div>
      )}
    </main>
  );
}
