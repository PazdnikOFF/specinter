import Link from "next/link";
import SearchBox from "../SearchBox";
import CatalogCard from "../CatalogCard";
import CatalogFilters from "../CatalogFilters";
import ZoomImage from "../ZoomImage";
import { apiCatalogRoots, apiCatalogBrowse, imgUrl, thumbUrl } from "../../lib/api";

export const metadata = { title: "Каталог запчастей — СПЕЦИНТЕР" };

type SP = { cat?: string; sort?: string; stock?: string; page?: string };

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
            Выберите модель техники, двигатель или производителя — либо найдите деталь по артикулу.
          </p>
          <div style={{ maxWidth: 560, margin: "20px 0 8px" }}><SearchBox /></div>
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
  const data = await apiCatalogBrowse({ category: cat, sort, stock, page, per_page });

  if (!data) {
    return <main className="container"><div className="empty">Категория не найдена. <Link className="link" href="/catalog">← в каталог</Link></div></main>;
  }

  const pages = Math.max(1, Math.ceil(data.total / per_page));
  const qs = (p: Record<string, string | number>) => {
    const q = new URLSearchParams({ cat: cat!, sort, ...(stock ? { stock: "1" } : {}) });
    for (const [k, v] of Object.entries(p)) q.set(k, String(v));
    return `/catalog?${q}`;
  };
  const hasGroups = data.children.length > 0;
  const hasProducts = data.products.length > 0;

  return (
    <main className="container">
      <nav className="crumbs" style={{ display: "flex", flexWrap: "wrap", gap: 6 }}>
        <Link href="/catalog">Каталог</Link>
        {data.breadcrumbs.map((b: any, i: number) => (
          <span key={b.id}>
            {" / "}
            {i === data.breadcrumbs.length - 1
              ? <span style={{ color: "var(--text)" }}>{splitName(b.name).main}</span>
              : <Link href={`/catalog?cat=${b.id}`}>{splitName(b.name).main}</Link>}
          </span>
        ))}
      </nav>
      <h1 className="cat-title">{splitName(data.category.name).main}</h1>

      {data.category.image && (
        <span className="node-scheme" title="Схема узла — увеличить">
          <ZoomImage thumb={imgUrl(data.category.image)!} full={imgUrl(data.category.image)!}
            alt={`Схема — ${data.category.name}`} />
        </span>
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
            {data.products.map((p: any) => <CatalogCard key={p.id} p={p} />)}
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
