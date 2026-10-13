"use client";

import { useState } from "react";
import { apiPut } from "@/lib/clientApi";

export default function SiteTab({ initialSiteTitle }: { initialSiteTitle: string }) {
  const [siteTitle, setSiteTitle] = useState(initialSiteTitle);
  const [logoFile, setLogoFile] = useState<File | null>(null);
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState<{ kind: "ok" | "error"; text: string } | null>(null);

  async function handleSaveTitle(e: React.FormEvent) {
    e.preventDefault();
    setSaving(true);
    setMessage(null);
    try {
      const res = await apiPut("/api/admin/system-settings/site", { siteTitle });
      const data = await res.json();
      setMessage({ kind: res.ok ? "ok" : "error", text: data.message });
    } finally {
      setSaving(false);
    }
  }

  async function handleUploadLogo(e: React.FormEvent) {
    e.preventDefault();
    if (!logoFile) return;
    setSaving(true);
    setMessage(null);
    try {
      const formData = new FormData();
      formData.append("logo", logoFile);
      const res = await fetch("/api/admin/system-settings/site/logo", { method: "POST", body: formData });
      const data = await res.json();
      setMessage({ kind: res.ok ? "ok" : "error", text: data.message });
    } finally {
      setSaving(false);
    }
  }

  return (
    <div className="flex flex-col gap-8">
      <form onSubmit={handleSaveTitle} className="flex items-end gap-3">
        <div>
          <label className="block text-sm font-medium">Site Title</label>
          <input
            type="text"
            required
            maxLength={255}
            value={siteTitle}
            onChange={(e) => setSiteTitle(e.target.value)}
            className="mt-1 rounded-md border border-black/15 px-3 py-2 text-sm"
          />
        </div>
        <button type="submit" disabled={saving} className="rounded-md bg-slate-900 px-4 py-2 text-sm text-white disabled:opacity-50">
          Save
        </button>
      </form>

      <form onSubmit={handleUploadLogo} className="flex items-end gap-3">
        <div>
          <label className="block text-sm font-medium">Site Logo (PNG/JPG, max 5MB)</label>
          <input
            type="file"
            accept=".png,.jpg,.jpeg"
            onChange={(e) => setLogoFile(e.target.files?.[0] ?? null)}
            className="mt-1 text-sm"
          />
        </div>
        <button
          type="submit"
          disabled={saving || !logoFile}
          className="rounded-md bg-slate-900 px-4 py-2 text-sm text-white disabled:opacity-50"
        >
          Upload
        </button>
      </form>

      {message && (
        <p className={message.kind === "ok" ? "text-sm text-green-700" : "text-sm text-red-600"}>{message.text}</p>
      )}
    </div>
  );
}
