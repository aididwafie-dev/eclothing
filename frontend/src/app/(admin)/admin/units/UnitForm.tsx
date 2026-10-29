"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useState } from "react";
import { apiPost, apiPut } from "@/lib/clientApi";
import type { AdminUnit, DropdownOption } from "@/lib/types";

/** addUnit.blade.php / editUnit.blade.php -- same fields, different labels and submit target. */
export default function UnitForm({ unit, bases }: { unit?: AdminUnit; bases: DropdownOption[] }) {
  const router = useRouter();
  const [value, setValue] = useState(unit?.value ?? "");
  const [baseHq, setBaseHq] = useState(unit?.baseHq ?? "");
  const [org, setOrg] = useState(unit?.org ?? "");
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setSaving(true);
    setError(null);
    try {
      const body = { value, baseHq, org };
      const res = unit ? await apiPut(`/api/admin/units/${unit.id}`, body) : await apiPost("/api/admin/units", body);
      const data = await res.json();
      if (!res.ok) {
        setError(data.message ?? "Could not save the unit.");
        return;
      }
      router.push("/admin/units");
      router.refresh();
    } catch {
      setError("Could not reach the server.");
    } finally {
      setSaving(false);
    }
  }

  return (
    <form autoComplete="off" onSubmit={handleSubmit}>
      <label className="label_">{unit ? "Unit name" : "Unit name:"}</label>
      <input className="form-control" type="text" required value={value} onChange={(e) => setValue(e.target.value)} />
      <br />
      <label className="label_">{unit ? "BASE/HQ" : "BASE/HQ:"}</label>
      <select className="form-control" value={baseHq} onChange={(e) => setBaseHq(e.target.value)}>
        <option value="">--NONE--</option>
        {bases.map((b) => (
          <option key={b.id} value={b.id}>
            {b.value}
          </option>
        ))}
      </select>
      <br />
      <label className="label_">{unit ? "ORG" : "ORG:"}</label>
      <input className="form-control" type="text" maxLength={255} value={org} onChange={(e) => setOrg(e.target.value)} />

      {error && <p className="text-danger">{error}</p>}

      <div className="subBtn text-center">
        <br />
        <button className="btn btn-primary" type="submit" disabled={saving}>
          {saving ? "Saving..." : unit ? "SAVE CHANGES" : "SAVE UNIT"}
        </button>{" "}
        <Link href="/admin/units" className="btn btn-default">
          {" "}
          CANCEL
        </Link>
      </div>
    </form>
  );
}
