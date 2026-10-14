"use client";

import Link from "next/link";
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
    } catch {
      setMessage({ kind: "error", text: "Could not reach the server." });
    } finally {
      setSaving(false);
    }
  }

  return (
    <>
      <div className="title">
        <i className="fa fa-pencil-square" aria-hidden="true"></i> Edit Base
      </div>
      <hr />
      <div className="containerMain">
        <div className="content">
          <b>Edit base details - {data.base.base}</b>
          <hr />
          <form onSubmit={handleSubmit} autoComplete="off">
            <label className="label_">Base name</label>
            <input
              type="text"
              className="form-control"
              maxLength={255}
              required
              value={baseName}
              onChange={(e) => setBaseName(e.target.value)}
            />
            <br />
            <label className="label_">Org name</label>
            <input
              type="text"
              className="form-control"
              maxLength={255}
              value={orgName}
              onChange={(e) => setOrgName(e.target.value)}
            />
            <br />
            <label className="label_">Status</label>
            <select
              className="form-control"
              value={active ? "1" : "0"}
              onChange={(e) => setActive(e.target.value === "1")}
            >
              <option value="1">Active</option>
              <option value="0">Inactive</option>
            </select>
            <br />

            <label className="label_">Units under this base</label>
            <div className="shop-subtitle">
              Tick every unit that belongs to this base. Unticking one detaches it (it stays a unit, just
              without a base).
            </div>
            <div
              className="table-responsive"
              style={{ maxHeight: 320, overflowY: "auto", border: "1px solid #e5e5e5", padding: 8 }}
            >
              {data.units.map((unit) => (
                <div key={unit.id} className="checkbox">
                  <label>
                    <input
                      type="checkbox"
                      checked={unitIds.includes(unit.id)}
                      onChange={() => toggleUnit(unit.id)}
                    />{" "}
                    {unit.value}
                    {unit.baseHq && !unit.assignedHere && (
                      <span className="text-muted"> (currently under another base)</span>
                    )}
                  </label>
                </div>
              ))}
            </div>

            {message && (
              <p className={message.kind === "ok" ? "text-success" : "text-danger"}>{message.text}</p>
            )}

            <div className="subBtn text-center">
              <br />
              <button className="btn btn-primary" type="submit" disabled={saving}>
                {saving ? "Saving..." : "SAVE CHANGES"}
              </button>
              <Link href="/admin/bases" className="btn btn-default">
                {" "}
                CANCEL
              </Link>
            </div>
          </form>
        </div>
      </div>
    </>
  );
}
