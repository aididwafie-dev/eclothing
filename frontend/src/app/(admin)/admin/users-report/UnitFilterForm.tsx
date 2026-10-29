"use client";

import { useRouter } from "next/navigation";
import { useState } from "react";
import type { DropdownOption } from "@/lib/types";

/** The "Filter by unit" (and, for uniform tags, "Officer recruit") form on the user report pages. */
export default function UnitFilterForm({
  basePath,
  units,
  unitId,
  officerRecruit,
}: {
  basePath: string;
  units: DropdownOption[];
  unitId: string;
  officerRecruit?: string;
}) {
  const router = useRouter();
  const [unit, setUnit] = useState(unitId);
  const [recruit, setRecruit] = useState(officerRecruit ?? "");

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    const query = new URLSearchParams();
    if (unit) query.set("unitId", unit);
    if (officerRecruit !== undefined && recruit) query.set("officerRecruit", recruit);
    router.push(query.size ? `${basePath}?${query}` : basePath);
  }

  return (
    <form autoComplete="off" onSubmit={handleSubmit}>
      <div className="form-group col-sm-4">
        <label className="label_">Filter by unit</label>
        <select className="form-control" value={unit} onChange={(e) => setUnit(e.target.value)}>
          <option value="">All units</option>
          {units.map((u) => (
            <option key={u.id} value={u.id}>
              {u.value}
            </option>
          ))}
        </select>
      </div>
      {officerRecruit !== undefined && (
        <div className="form-group col-sm-4">
          <label className="label_">Officer recruit</label>
          <select className="form-control" value={recruit} onChange={(e) => setRecruit(e.target.value)}>
            <option value="">No filter</option>
            <option value="1">Officer</option>
            <option value="2">Other rank</option>
            <option value="3">Both officer &amp; other rank</option>
          </select>
        </div>
      )}
      <div className="subBtn col-sm-4">
        <br />
        <button className="btn btn-warning" type="submit">
          Filter results
        </button>
      </div>
    </form>
  );
}
