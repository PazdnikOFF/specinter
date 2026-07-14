"use client";
import { useEffect } from "react";

// Оверлей-фрейм для просмотра изображения в увеличенном виде.
export default function Lightbox({ src, alt, onClose }: { src: string; alt?: string; onClose: () => void }) {
  useEffect(() => {
    const onKey = (e: KeyboardEvent) => { if (e.key === "Escape") onClose(); };
    document.addEventListener("keydown", onKey);
    const prev = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    return () => { document.removeEventListener("keydown", onKey); document.body.style.overflow = prev; };
  }, [onClose]);

  return (
    <div className="lightbox" onClick={onClose} role="dialog" aria-modal="true">
      <button className="lb-close" aria-label="Закрыть" onClick={onClose}>×</button>
      <img src={src} alt={alt || ""} onClick={(e) => e.stopPropagation()} />
    </div>
  );
}
