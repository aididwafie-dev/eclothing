"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useState } from "react";
import { apiPost, apiPut } from "@/lib/clientApi";
import type { AdminTred } from "@/lib/types";

/** addTred.blade.php / editTred.blade.php. */
export default function TredForm({ tred }: { tred?: AdminTred }) {
  const router = useRouter();
  const [value, setValue] = useState(tred?.value ?? "");
  const [officerRecruit, setOfficerRecruit] = useState(String(tred?.officerRecruit ?? 1));
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setSaving(true);
    setError(null);
    try {
      const body = { value, officerRecruit: Number(officerRecruit) };
      const res = tred ? await apiPut(`/api/admin/treds/${tred.id}`, body) : await apiPost("/api/admin/treds", body);
      const data = await res.json();
      if (!res.ok) {
        setError(data.message ?? "Could not save the tred.");
        return;
      }
      router.push("/admin/tred");
      router.refresh();
    } catch {
      setError("Could not reach the server.");
    } finally {
      setSaving(false);
    }
  }

  return (
    <form autoComplete="off" onSubmit={handleSubmit}>
      <label className="label_">Tred name</label>
      <input className="form-control" type="text" required value={value} onChange={(e) => setValue(e.target.value)} />
      <br />
      <label className="label_">Officer recruit</label>
      <select className="form-control" value={officerRecruit} onChange={(e) => setOfficerRecruit(e.target.value)}>
        <option value="1">Officer</option>
        <option value="2">Other rank</option>
        <option value="3">{tred ? "Both" : "Both officer & other rank"}</option>
      </select>

      {error && <p className="text-danger">{error}</p>}

      <div className="subBtn text-center">
        <br />
        <button className="btn btn-primary" type="submit" disabled={saving}>
          {saving ? "Saving..." : tred ? "SAVE CHANGES" : "SAVE TRED"}
        </button>{" "}
        <Link href="/admin/tred" className="btn btn-default">
          {" "}
          CANCEL
        </Link>
      </div>
    </form>
  );
}
