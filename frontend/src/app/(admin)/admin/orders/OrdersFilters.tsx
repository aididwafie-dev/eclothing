"use client";

import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { useState } from "react";
import type { AdminOrdersResponse } from "@/lib/types";

function formatOrderedAt(value: string | null): string {
  if (!value) return "-";
  const d = new Date(value.replace(" ", "T"));
  if (isNaN(d.getTime())) return value;
  const day = String(d.getDate()).padStart(2, "0");
  const month = d.toLocaleDateString("en-GB", { month: "short" });
  const time = d.toLocaleTimeString("en-US", { hour: "2-digit", minute: "2-digit", hour12: true });
  return `${day} ${month} ${d.getFullYear()} ${time}`;
}

function formatUpdatedAt(value: string | null): string {
  if (!value) return "-";
  const d = new Date(value.replace(" ", "T"));
  if (isNaN(d.getTime())) return value;
  const date = d.toLocaleDateString("en-GB", { day: "2-digit", month: "2-digit", year: "2-digit" });
  const time = d.toLocaleTimeString("en-US", { hour: "2-digit", minute: "2-digit", hour12: true });
  return `${date} ${time}`;
}

export default function OrdersFilters({
  data,
  currentSearch,
}: {
  data: AdminOrdersResponse;
  currentSearch: string;
}) {
  const router = useRouter();
  const searchParams = useSearchParams();
  const [search, setSearch] = useState(currentSearch);

  const searching = currentSearch.trim() !== "";

  function updateParam(name: string, value: string) {
    const next = new URLSearchParams(searchParams.toString());
    if (value) {
      next.set(name, value);
    } else {
      next.delete(name);
    }
    next.delete("page");
    router.push(`/admin/orders?${next}`);
  }

  function handleSearchSubmit(e: React.FormEvent) {
    e.preventDefault();
    updateParam("search", search.trim());
  }

  const totalPages = Math.max(1, Math.ceil(data.recordsFiltered / data.perPage));

  return (
    <>
      <form className="orders-search" role="search" onSubmit={handleSearchSubmit}>
        <div className="orders-search-field">
          <i className="fa fa-search" aria-hidden="true"></i>
          <input
            type="text"
            className="form-control"
            placeholder="Order ID, service ID, name, unit or uniform"
            aria-label="Search orders"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            autoComplete="off"
          />
        </div>
        <div className="orders-filter-field">
          <label className="orders-filter-label">Status</label>
          <select
            className="form-control"
            value={data.filters.status}
            disabled={searching}
            onChange={(e) => updateParam("status", e.target.value)}
          >
            {data.filters.statusOptions.map((opt) => (
              <option key={opt.key} value={opt.key}>
                {opt.label}
              </option>
            ))}
            <option value="all">All statuses</option>
          </select>
        </div>
        <div className="orders-filter-field">
          <label className="orders-filter-label">Unit</label>
          <select
            className="form-control"
            value={data.filters.unit ?? "0"}
            onChange={(e) => updateParam("unit", e.target.value)}
          >
            <option value="0">All units</option>
            {data.filters.unitOptions.map((opt) => (
              <option key={opt.id} value={opt.id}>
                {opt.value}
              </option>
            ))}
          </select>
        </div>
        {searching && (
          <button
            type="button"
            className="btn btn-default"
            onClick={() => {
              setSearch("");
              updateParam("search", "");
            }}
          >
            <i className="fa fa-times" aria-hidden="true"></i> Clear
          </button>
        )}
      </form>
      <div className="orders-search-result">
        {searching ? (
          <>
            Showing results for <strong>{currentSearch}</strong>, across every status.
          </>
        ) : (
          <>
            Showing{" "}
            <strong>
              {data.filters.statusOptions.find((o) => o.key === data.filters.status)?.label ?? "all"}
            </strong>{" "}
            orders.
          </>
        )}
      </div>

      <div className="table-responsive">
        <table className="table table-orders table-orders-wide" style={{ width: "100%" }}>
          <thead>
            <tr>
              <th>Order</th>
              <th>Service ID</th>
              <th>Name</th>
              <th>Unit</th>
              <th>Uniform</th>
              <th>Items</th>
              <th>Status</th>
              <th>Ordered At</th>
              <th>Last Updated</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            {data.orders.length === 0 && (
              <tr>
                <td colSpan={10} className="text-center">
                  No orders match.
                </td>
              </tr>
            )}
            {data.orders.map((order) => (
              <tr key={order.id}>
                <td data-label="Order">#{order.id}</td>
                <td data-label="Service ID">{order.sId ?? "-"}</td>
                <td data-label="Name">{order.name ?? "N/A"}</td>
                <td data-label="Unit">{order.unitName ?? "N/A"}</td>
                <td data-label="Uniform">{order.uniformLabel}</td>
                <td data-label="Items">{order.itemCount}</td>
                <td data-label="Status">
                  <span className={`status-badge status-${order.status}`}>{order.statusLabel}</span>
                </td>
                <td data-label="Ordered At">{formatOrderedAt(order.createdAt)}</td>
                <td data-label="Last Updated">{formatUpdatedAt(order.updatedAt)}</td>
                <td data-label="Action">
                  <Link href={`/admin/orders/${order.id}`} className="btn btn-sm btn-default">
                    <i className="fa fa-eye" aria-hidden="true"></i> View Detail
                  </Link>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {totalPages > 1 && (
        <ul className="pagination">
          {Array.from({ length: totalPages }, (_, i) => i + 1).map((p) => (
            <li key={p} className={p === data.page ? "active" : ""}>
              <a onClick={() => updateParam("page", String(p))} style={{ cursor: "pointer" }}>
                {p}
              </a>
            </li>
          ))}
        </ul>
      )}
    </>
  );
}
