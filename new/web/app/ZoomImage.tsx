"use client";
import { useState } from "react";
import Lightbox from "./Lightbox";

// Кликабельное изображение: миниатюра → открывает полноразмер во фрейме-оверлее.
export default function ZoomImage({ thumb, full, alt, className }: {
  thumb: string; full: string; alt?: string; className?: string;
}) {
  const [open, setOpen] = useState(false);
  return (
    <>
      <img src={thumb} alt={alt || ""} className={className} loading="lazy"
        style={{ cursor: "zoom-in" }} onClick={() => setOpen(true)} />
      {open && <Lightbox src={full} alt={alt} onClose={() => setOpen(false)} />}
    </>
  );
}
