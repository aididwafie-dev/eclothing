"use client";

import { useRouter } from "next/navigation";
import { useState } from "react";
import type { AdminBaseDetailResponse } from "@/lib/types";

export default function BaseEditForm({ data }: { data: AdminBaseDetailResponse }) {
  const router = useRouter();
  const [baseName, setBaseName] = useState(data.base.base);
  const [orgName, setOrgName] = useState(data.base.orgName ?? "");
  const [active, setActive] = useState(data.base.active);
  const [unitIds, setUnitIds] = useState<string[]>(data.units.filter((u) => u.assignedHere).map((u) => u.id));
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState<{ kind: "ok" | "error"; text: string } | null>(null);

  function toggleUnit(id: string) {
    setUnitIds((prev) => (prev.includes(id) ? prev.filter((u) => u !== id) : [...prev, id]));
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setSaving(true);
    setMessage(null);
    try {
      const res = await fetch(`/api/admin/bases/${data.base.id}`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ base: baseName, orgName, active, unitIds }),
      });
      const result = await res.json();
      if (!res.ok) {
        setMessage({ kind: "error", text: result.message ?? "Could not save the base." });
        return;
      }
      setMessage({ kind: "ok", text: result.message });
      router.refresh();
    } finally {
      setSaving(false);
    }
  }

  return (
    <form onSubmit={handleSubmit} className="rounded-lg border border-black/10 bg-white p-6">
      <h1 className="text-xl font-semibold">Edit Base</h1>
      <div className="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <label className="block text-sm font-medium">Base name</label>
          <input
            type="text"
            required
            maxLength={255}
            value={baseName}
            onChange={(e) => setBaseName(e.target.value)}
            className="mt-1 w-full rounded-md border border-black/15 px-3 py-2 text-sm"
          />
        </div>
        <div>
          <label className="block text-sm font-medium">Org name</label>
          <input
            type="text"
            maxLength={255}
            value={orgName}
            onChange={(e) => setOrgName(e.target.value)}
            className="mt-1 w-full rounded-md border border-black/15 px-3 py-2 text-sm"
          />
        </div>
        <label className="flex items-center gap-2 text-sm">
          <input type="checkbox" checked={active} onChange={(e) => setActive(e.target.checked)} />
          Active
        </label>
      </div>

      <h2 className="mt-6 font-semibold">Units</h2>
      <p className="text-sm text-black/50">
        Units ticked here are assigned to this base. Unticking a unit detaches it; saving also detaches
        any unit not ticked that currently belongs here.
      </p>
      <div className="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3">
        {data.units.map((unit) => (
          <label key={unit.id} className="flex items-center gap-2 text-sm">
            <input type="checkbox" checked={unitIds.includes(unit.id)} onChange={() => toggleUnit(unit.id)} />
            {unit.value}
            {unit.baseHq && !unit.assignedHere && (
              <span className="text-xs text-amber-600">(currently under another base)</span>
            )}
          </label>
        ))}
      </div>

      <div className="mt-6 flex items-center gap-4">
        <button
          type="submit"
          disabled={saving}
          className="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
        >
          {saving ? "Saving..." : "Save"}
        </button>
        {message && (
          <span className={message.kind === "ok" ? "text-sm text-green-700" : "text-sm text-red-600"}>
            {message.text}
          </span>
        )}
      </div>
    </form>
  );
}
