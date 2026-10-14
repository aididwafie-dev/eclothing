"use client";

import Link from "next/link";
import { useState } from "react";
import { apiPut } from "@/lib/clientApi";

export default function SiteTab({
  initialSiteTitle,
  initialLogoUrl,
}: {
  initialSiteTitle: string;
  initialLogoUrl: string;
}) {
  const [siteTitle, setSiteTitle] = useState(initialSiteTitle);
  const [logoUrl, setLogoUrl] = useState(initialLogoUrl);
  const [logoFile, setLogoFile] = useState<File | null>(null);
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState<{ kind: "ok" | "error"; text: string } | null>(null);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setSaving(true);
    setMessage(null);
    try {
      const titleRes = await apiPut("/api/admin/system-settings/site", { siteTitle });
      const titleData = await titleRes.json();
      if (!titleRes.ok) {
        setMessage({ kind: "error", text: titleData.message ?? "Could not save." });
        return;
      }

      if (logoFile) {
        const formData = new FormData();
        formData.append("logo", logoFile);
        const logoRes = await fetch("/api/admin/system-settings/site/logo", { method: "POST", body: formData });
        const logoData = await logoRes.json();
        if (!logoRes.ok) {
          setMessage({ kind: "error", text: logoData.message ?? "Could not save." });
          return;
        }
        setLogoUrl(`${logoUrl.split("?")[0]}?v=${Date.now()}`);
        setLogoFile(null);
      }

      setMessage({ kind: "ok", text: titleData.message });
    } catch {
      setMessage({ kind: "error", text: "Could not reach the server." });
    } finally {
      setSaving(false);
    }
  }

  return (
    <>
      <div className="shop-subtitle">System settings (title and logo).</div>
      <br />

      <form onSubmit={handleSubmit} autoComplete="off">
        <div className="form-group">
          <label className="label_">Tajuk Sistem</label>
          <input
            className="form-control"
            type="text"
            required
            value={siteTitle}
            onChange={(e) => setSiteTitle(e.target.value)}
          />
        </div>

        <div className="form-group">
          <label className="label_">Logo Sistem (PNG/JPG)</label>
          <input
            className="form-control"
            type="file"
            accept="image/png,image/jpeg"
            onChange={(e) => setLogoFile(e.target.files?.[0] ?? null)}
          />
        </div>

        <div className="form-group">
          <label className="label_">Logo Semasa</label>
          <br />
          <img src={logoUrl} className="img-responsive" alt="logo" style={{ maxHeight: 140 }} />
        </div>

        {message && (
          <p className={message.kind === "ok" ? "text-success" : "text-danger"}>{message.text}</p>
        )}

        <div className="subBtn">
          <input className="btn btn-default text-uppercase" type="submit" value={saving ? "Menyimpan..." : "Simpan"} disabled={saving} />
          <Link href="/admin/orders" className="btn btn-default">
            Kembali
          </Link>
        </div>
      </form>
    </>
  );
}
