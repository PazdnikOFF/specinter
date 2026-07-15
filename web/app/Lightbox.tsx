"use client";
import { useEffect, useState } from "react";
import { createPortal } from "react-dom";

// Оверлей-фрейм для просмотра изображения в увеличенном виде.
// ВАЖНО: рендерим через ПОРТАЛ в document.body, чтобы лайтбокс НЕ был потомком
// обёрток-миниатюр (.node-scheme / .scheme-img и т.п.) — иначе их правило `<wrapper> img`
// (та же специфичность, но объявлено позже) перебивало `.lightbox img` и ужимало картинку.
// В body ничего не мешает: object-fit заполняет кадр 96vw×96vh, overlay кроет весь экран.
export default function Lightbox({ src, alt, onClose }: { src: string; alt?: string; onClose: () => void }) {
  const [mounted, setMounted] = useState(false);
  useEffect(() => { setMounted(true); }, []);
  useEffect(() => {
    const onKey = (e: KeyboardEvent) => { if (e.key === "Escape") onClose(); };
    document.addEventListener("keydown", onKey);
    const prev = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    return () => { document.removeEventListener("keydown", onKey); document.body.style.overflow = prev; };
  }, [onClose]);

  if (!mounted) return null;
  return createPortal(
    <div className="lightbox" onClick={onClose} role="dialog" aria-modal="true">
      <button className="lb-close" aria-label="Закрыть" onClick={onClose}>×</button>
      <img src={src} alt={alt || ""} onClick={(e) => e.stopPropagation()} />
    </div>,
    document.body,
  );
}
