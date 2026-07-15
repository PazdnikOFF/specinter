"use client";
import { useState } from "react";
import { imgUrl, thumbUrl } from "../../../lib/api";
import Lightbox from "../../Lightbox";

type Img = { url: string; sort?: number };

export default function ProductGallery({
  primary,
  images,
  name,
}: {
  primary?: string | null;
  images?: Img[];
  name?: string;
}) {
  // Главное фото + галерея (img_1..4 / images / goodimage). Дубли уже вычищены в ETL.
  const all = [
    ...(primary ? [primary] : []),
    ...(images || []).map((i) => i.url).filter(Boolean),
  ];
  const [active, setActive] = useState(0);
  const [zoom, setZoom] = useState(false);

  if (all.length === 0) {
    return <div className="pimg">нет фото</div>;
  }

  return (
    <div>
      <div className="pimg" onClick={() => setZoom(true)} style={{ cursor: "zoom-in" }} title="Увеличить">
        <img src={imgUrl(all[active])!} alt={name || ""} />
      </div>
      {zoom && <Lightbox src={imgUrl(all[active])!} alt={name} onClose={() => setZoom(false)} />}
      {all.length > 1 && (
        <div className="pthumbs">
          {all.map((u, i) => (
            <button
              key={i}
              type="button"
              className={`pthumb${i === active ? " active" : ""}`}
              onClick={() => setActive(i)}
              aria-label={`Фото ${i + 1}`}
            >
              <img src={thumbUrl(u)!} alt="" loading="lazy" />
            </button>
          ))}
        </div>
      )}
    </div>
  );
}
