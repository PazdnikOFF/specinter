"use client";
import { useRouter, useSearchParams } from "next/navigation";

const SORTS = [
  ["default", "Сначала в наличии"],
  ["price_asc", "Дешевле"],
  ["price_desc", "Дороже"],
  ["name", "По названию"],
];

export default function CatalogFilters({ total }: { total: number }) {
  const router = useRouter();
  const sp = useSearchParams();
  const sort = sp.get("sort") || "default";
  const stock = sp.get("stock") === "1";

  const patch = (kv: Record<string, string | null>) => {
    const q = new URLSearchParams(sp.toString());
    for (const [k, v] of Object.entries(kv)) v === null ? q.delete(k) : q.set(k, v);
    q.delete("page");
    router.push(`/catalog?${q}`);
  };

  return (
    <div className="filters">
      <span className="filters-count">{total.toLocaleString("ru-RU")} позиций</span>
      <label className="chk">
        <input type="checkbox" checked={stock}
          onChange={(e) => patch({ stock: e.target.checked ? "1" : null })} />
        Только в наличии
      </label>
      <select className="sortsel" value={sort} onChange={(e) => patch({ sort: e.target.value })}>
        {SORTS.map(([v, l]) => <option key={v} value={v}>{l}</option>)}
      </select>
    </div>
  );
}
