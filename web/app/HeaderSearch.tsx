"use client";
import { Suspense } from "react";
import { usePathname } from "next/navigation";
import SearchBox from "./SearchBox";

// Глобальный умный поиск, закреплённый под хидером (sticky). Скрыт в админке.
export default function HeaderSearch() {
  const path = usePathname();
  if (path?.startsWith("/admin")) return null;
  return (
    <div className="topsearch">
      <div className="container">
        <Suspense fallback={null}><SearchBox wide /></Suspense>
      </div>
    </div>
  );
}
