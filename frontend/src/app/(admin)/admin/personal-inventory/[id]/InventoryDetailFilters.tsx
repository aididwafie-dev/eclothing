"use client";

import Link from "next/link";
import { usePathname, useRouter, useSearchParams } from "next/navigation";

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
  const pathname = usePathname();
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

  const hasActiveFilter = filters.month !== "all" || filters.year !== "all";

  return (
    <form className="orders-search">
      <div className="orders-filter-field">
        <label className="orders-filter-label">Month</label>
        <select className="form-control" value={filters.month} onChange={(e) => updateParam("month", e.target.value)}>
          <option value="all">All months</option>
          {MONTHS.map((m, i) => (
            <option key={i + 1} value={i + 1}>
              {m}
            </option>
          ))}
        </select>
      </div>
      <div className="orders-filter-field">
        <label className="orders-filter-label">Year</label>
        <select className="form-control" value={filters.year} onChange={(e) => updateParam("year", e.target.value)}>
          <option value="all">All years</option>
          {filters.yearOptions.map((y) => (
            <option key={y} value={y}>
              {y}
            </option>
          ))}
        </select>
      </div>
      {hasActiveFilter && (
        <Link href={pathname} className="btn btn-default">
          <i className="fa fa-times" aria-hidden="true"></i> Clear
        </Link>
      )}
      <Link href="/admin/personal-inventory" className="btn btn-default">
        <i className="fa fa-list" aria-hidden="true"></i> Back to list
      </Link>
    </form>
  );
}
