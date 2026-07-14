import Link from "next/link";

export type Crumb = { name: string; href?: string };

// Хлебные крошки в стиле Apple: минимализм, разделитель «›», последний — текущий.
export default function Breadcrumbs({ items }: { items: Crumb[] }) {
  const clean = items.filter((c) => c && c.name);
  if (clean.length === 0) return null;
  return (
    <nav className="bcrumbs" aria-label="Хлебные крошки">
      {clean.map((c, i) => {
        const last = i === clean.length - 1;
        return (
          <span className="bcrumb" key={i}>
            {i > 0 && <span className="bc-sep" aria-hidden="true">›</span>}
            {c.href && !last
              ? <Link href={c.href}>{c.name}</Link>
              : <span className="bc-current" aria-current={last ? "page" : undefined}>{c.name}</span>}
          </span>
        );
      })}
    </nav>
  );
}
