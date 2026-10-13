"use client";

import { useRouter, useSearchParams } from "next/navigation";

const MONTHS = [
  "January", "February", "March", "April", "May", "June",
  "July", "August", "September", "October", "November", "December",
];

export default function InventoryDetailFilters({
  filters,
}: {
  filters: { month: string; year: string; yearOptions: number[] };
}) {
  const router = useRouter();
  const searchParams = useSearchParams();

  function updateParam(name: string, value: string) {
    const next = new URLSearchParams(searchParams.toString());
    if (value && value !== "all") {
      next.set(name, value);
    } else {
      next.delete(name);
    }
    router.push(`?${next}`);
  }

  return (
    <div className="mt-4 flex gap-4">
      <div>
        <label className="block text-sm font-medium">Month</label>
        <select
          value={filters.month}
          onChange={(e) => updateParam("month", e.target.value)}
          className="mt-1 rounded-md border border-black/15 px-3 py-2 text-sm"
        >
          <option value="all">All Months</option>
          {MONTHS.map((m, i) => (
            <option key={i + 1} value={i + 1}>
              {m}
            </option>
          ))}
        </select>
      </div>
      <div>
        <label className="block text-sm font-medium">Year</label>
        <select
          value={filters.year}
          onChange={(e) => updateParam("year", e.target.value)}
          className="mt-1 rounded-md border border-black/15 px-3 py-2 text-sm"
        >
          <option value="all">All Years</option>
          {filters.yearOptions.map((y) => (
            <option key={y} value={y}>
              {y}
            </option>
          ))}
        </select>
      </div>
    </div>
  );
}
