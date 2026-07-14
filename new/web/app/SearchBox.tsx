"use client";
import { useRouter } from "next/navigation";
import { useState } from "react";

export default function SearchBox({ initial = "" }: { initial?: string }) {
  const [q, setQ] = useState(initial);
  const router = useRouter();
  return (
    <form
      className="searchbar"
      onSubmit={(e) => {
        e.preventDefault();
        if (q.trim()) router.push(`/search?q=${encodeURIComponent(q.trim())}`);
      }}
    >
      <input
        autoFocus
        value={q}
        onChange={(e) => setQ(e.target.value)}
        placeholder="Артикул, аналог или название детали…"
        aria-label="Поиск"
      />
      <button type="submit">Найти</button>
    </form>
  );
}
