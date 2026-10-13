"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useState } from "react";
import { apiDelete } from "@/lib/clientApi";
import type { AdminBase } from "@/lib/types";

export default function BasesPageClient({ initialBases }: { initialBases: AdminBase[] }) {
  const router = useRouter();
  const [bases, setBases] = useState(initialBases);
  const [showAdd, setShowAdd] = useState(false);
  const [baseName, setBaseName] = useState("");
  const [orgName, setOrgName] = useState("");
  const [active, setActive] = useState(true);
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState<{ kind: "ok" | "error"; text: string } | null>(null);

  async function handleAdd(e: React.FormEvent) {
    e.preventDefault();
    setSaving(true);
    setMessage(null);
    try {
      const res = await fetch("/api/admin/bases", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ base: baseName, orgName, active }),
      });
      const data = await res.json();
      if (!res.ok) {
        setMessage({ kind: "error", text: data.message ?? "Could not create the base." });
        return;
      }
      setBaseName("");
      setOrgName("");
      setActive(true);
      setShowAdd(false);
      setMessage({ kind: "ok", text: data.message });
      router.refresh();
      const refreshed = await fetch("/api/admin/bases").then((r) => r.json());
      setBases(refreshed.bases ?? []);
    } finally {
      setSaving(false);
    }
  }

  async function handleDelete(base: AdminBase) {
    if (!confirm(`Delete base "${base.base}"? Its units will keep existing but lose this base.`)) {
      return;
    }
    const res = await apiDelete(`/api/admin/bases/${base.id}`);
    if (res.ok) {
      setBases((prev) => prev.filter((b) => b.id !== base.id));
    }
  }

  return (
    <div className="rounded-lg border border-black/10 bg-white p-6">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-semibold">Bases</h1>
        <button
          onClick={() => setShowAdd((v) => !v)}
          className="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white"
        >
          {showAdd ? "Cancel" : "Add Base"}
        </button>
      </div>

      {showAdd && (
        <form onSubmit={handleAdd} className="mt-4 flex flex-wrap items-end gap-3 rounded-md border border-black/10 p-4">
          <div>
            <label className="block text-sm font-medium">Base name</label>
            <input
              type="text"
              required
              maxLength={255}
              value={baseName}
              onChange={(e) => setBaseName(e.target.value)}
              className="mt-1 rounded-md border border-black/15 px-3 py-2 text-sm"
            />
          </div>
          <div>
            <label className="block text-sm font-medium">Org name</label>
            <input
              type="text"
              maxLength={255}
              value={orgName}
              onChange={(e) => setOrgName(e.target.value)}
              className="mt-1 rounded-md border border-black/15 px-3 py-2 text-sm"
            />
          </div>
          <label className="flex items-center gap-2 text-sm">
            <input type="checkbox" checked={active} onChange={(e) => setActive(e.target.checked)} />
            Active
          </label>
          <button
            type="submit"
            disabled={saving}
            className="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
          >
            {saving ? "Saving..." : "Save"}
          </button>
        </form>
      )}

      {message && (
        <p className={message.kind === "ok" ? "mt-3 text-sm text-green-700" : "mt-3 text-sm text-red-600"}>
          {message.text}
        </p>
      )}

      <table className="mt-4 w-full text-left text-sm">
        <thead>
          <tr className="border-b border-black/10 text-black/50">
            <th className="py-2">Base</th>
            <th className="py-2">Org Name</th>
            <th className="py-2">Status</th>
            <th className="py-2">Units</th>
            <th className="py-2"></th>
          </tr>
        </thead>
        <tbody>
          {bases.map((base) => (
            <tr key={base.id} className="border-b border-black/5">
              <td className="py-2">{base.base}</td>
              <td className="py-2">{base.orgName ?? "-"}</td>
              <td className="py-2">{base.active ? "Active" : "Inactive"}</td>
              <td className="py-2">{base.unitCount}</td>
              <td className="py-2 flex gap-3">
                <Link href={`/admin/bases/${base.id}`} className="text-blue-600 hover:underline">
                  Edit
                </Link>
                <button onClick={() => handleDelete(base)} className="text-red-600 hover:underline">
                  Delete
                </button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
