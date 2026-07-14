"use client";
import { useRouter } from "next/navigation";
import { useState } from "react";
import { adminFetch } from "../../../lib/admin";

export default function AdminLogin() {
  const [u, setU] = useState("");
  const [p, setP] = useState("");
  const [err, setErr] = useState("");
  const [busy, setBusy] = useState(false);
  const router = useRouter();

  async function submit(e: React.FormEvent) {
    e.preventDefault();
    setBusy(true);
    setErr("");
    try {
      const r = await adminFetch("/api/admin/login", {
        method: "POST",
        body: JSON.stringify({ username: u, password: p }),
      });
      if (r.ok) { router.push("/admin"); return; }
      setErr(r.status === 401 ? "Неверный логин или пароль" : `Ошибка входа (${r.status})`);
    } catch {
      setErr("Не удалось связаться с сервером");
    } finally {
      setBusy(false);
    }
  }

  return (
    <main className="container" style={{ maxWidth: 380 }}>
      <form className="panel" onSubmit={submit} style={{ marginTop: 60 }}>
        <h1 className="section" style={{ marginTop: 0 }}>Вход в админку</h1>
        <input className="fld" placeholder="Логин" value={u} autoFocus
          onChange={(e) => setU(e.target.value)} />
        <input className="fld" placeholder="Пароль" type="password" value={p}
          onChange={(e) => setP(e.target.value)} />
        {err && <p style={{ color: "#ff3b30", fontSize: 13, margin: "4px 0" }}>{err}</p>}
        <button className="btn-primary" disabled={busy} type="submit" style={{ width: "100%" }}>
          {busy ? "Вход…" : "Войти"}
        </button>
      </form>
    </main>
  );
}
