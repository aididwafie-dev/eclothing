"use client";

import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { useState } from "react";
import { apiDelete, apiPost } from "@/lib/clientApi";
import type { AdminUserFilter, AdminUsersResponse } from "@/lib/types";

const FILTERS: { value: AdminUserFilter | ""; label: string }[] = [
  { value: "", label: "All Members" },
  { value: "incomplete_rank", label: "Missing Rank" },
  { value: "incomplete_name", label: "Missing Name" },
  { value: "incomplete_unit", label: "Missing Unit" },
  { value: "incomplete_personal", label: "Personal Details Incomplete" },
  { value: "incomplete_uniform", label: "No Orders Yet" },
  { value: "complete_all", label: "Fully Complete" },
];

export default function UsersFilters({
  data,
  currentSearch,
}: {
  data: AdminUsersResponse;
  currentSearch: string;
}) {
  const router = useRouter();
  const searchParams = useSearchParams();
  const [search, setSearch] = useState(currentSearch);
  const [users, setUsers] = useState(data.users);
  const [busy, setBusy] = useState<string | null>(null);

  function updateParam(name: string, value: string) {
    const next = new URLSearchParams(searchParams.toString());
    if (value) {
      next.set(name, value);
    } else {
      next.delete(name);
    }
    next.delete("page");
    router.push(`/admin/all-users?${next}`);
  }

  function handleSearchSubmit(e: React.FormEvent) {
    e.preventDefault();
    updateParam("search", search);
  }

  async function toggleBlock(id: string) {
    setBusy(id);
    try {
      await apiPost(`/api/admin/users/${id}/toggle-status`);
      setUsers((prev) => prev.map((u) => (u.id === id ? { ...u, blocked: !u.blocked } : u)));
    } finally {
      setBusy(null);
    }
  }

  async function deleteUser(id: string, label: string) {
    if (!confirm(`Permanently delete member "${label}"? This cannot be undone.`)) return;
    setBusy(id);
    try {
      const res = await apiDelete(`/api/admin/users/${id}`);
      if (res.ok) {
        setUsers((prev) => prev.filter((u) => u.id !== id));
      }
    } finally {
      setBusy(null);
    }
  }

  async function resendActivation(id: string) {
    setBusy(id);
    try {
      const res = await apiPost(`/api/admin/users/${id}/resend-activation`);
      const data = await res.json();
      alert(data.message);
    } finally {
      setBusy(null);
    }
  }

  async function blockAll() {
    if (!confirm("Block every currently active user? You will lose record of who was blocked before this.")) return;
    await apiPost("/api/admin/users/block-all");
    router.refresh();
  }

  async function unblockAll() {
    if (!confirm("Unblock every currently blocked user? You will lose record of who was unblocked before this.")) return;
    await apiPost("/api/admin/users/unblock-all");
    router.refresh();
  }

  const totalPages = Math.max(1, Math.ceil(data.recordsFiltered / data.perPage));

  return (
    <div className="mt-4">
      <div className="flex flex-wrap items-end gap-4">
        <div>
          <label className="block text-sm font-medium">Filter</label>
          <select
            value={data.filter ?? ""}
            onChange={(e) => updateParam("filter", e.target.value)}
            className="mt-1 rounded-md border border-black/15 px-3 py-2 text-sm"
          >
            {FILTERS.map((f) => (
              <option key={f.value} value={f.value}>
                {f.label}
              </option>
            ))}
          </select>
        </div>
        <form onSubmit={handleSearchSubmit} className="flex items-end gap-2">
          <div>
            <label className="block text-sm font-medium">Search</label>
            <input
              type="text"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="Service ID, name, unit, rank..."
              className="mt-1 w-64 rounded-md border border-black/15 px-3 py-2 text-sm"
            />
          </div>
          <button type="submit" className="rounded-md bg-slate-900 px-4 py-2 text-sm text-white">
            Search
          </button>
        </form>
        <div className="ml-auto flex gap-2">
          <button onClick={blockAll} className="rounded-md border border-black/15 px-3 py-2 text-sm">
            Block All
          </button>
          <button onClick={unblockAll} className="rounded-md border border-black/15 px-3 py-2 text-sm">
            Unblock All
          </button>
        </div>
      </div>

      <div className="mt-6 overflow-x-auto">
        <table className="w-full text-left text-sm">
          <thead>
            <tr className="border-b border-black/10 text-black/50">
              <th className="py-2">Service ID</th>
              <th className="py-2">Rank</th>
              <th className="py-2">Name</th>
              <th className="py-2">Unit</th>
              <th className="py-2"></th>
            </tr>
          </thead>
          <tbody>
            {users.length === 0 && (
              <tr>
                <td colSpan={5} className="py-6 text-center text-black/50">
                  No members match this filter.
                </td>
              </tr>
            )}
            {users.map((user) => (
              <tr key={user.id} className="border-b border-black/5">
                <td className="py-2" title={user.hasOrdered ? "Has placed orders" : "No orders yet"}>
                  {user.sId ?? "-"}
                </td>
                <td className="py-2">{user.rankName ?? "-"}</td>
                <td className="py-2">{user.name ?? "-"}</td>
                <td className="py-2">{user.unitName ?? "-"}</td>
                <td className="py-2">
                  <div className="flex flex-wrap items-center gap-2">
                    <Link href={`/admin/all-users/${user.id}/basic`} className="text-blue-600 hover:underline">
                      Basic
                    </Link>
                    <Link href={`/admin/all-users/${user.id}/personal`} className="text-blue-600 hover:underline">
                      Personal
                    </Link>
                    {user.sId && (
                      <Link href={`/admin/orders?search=${encodeURIComponent(user.sId)}&status=all`} className="text-blue-600 hover:underline">
                        Orders
                      </Link>
                    )}
                    <button
                      disabled={busy === user.id}
                      onClick={() => toggleBlock(user.id)}
                      className="text-amber-600 hover:underline disabled:opacity-50"
                    >
                      {user.blocked ? "Unblock" : "Block"}
                    </button>
                    <button
                      disabled={busy === user.id}
                      onClick={() => deleteUser(user.id, user.name ?? user.sId ?? user.id)}
                      className="text-red-600 hover:underline disabled:opacity-50"
                    >
                      Delete
                    </button>
                    {!user.activated && (
                      <button
                        disabled={busy === user.id}
                        onClick={() => resendActivation(user.id)}
                        className="text-green-700 hover:underline disabled:opacity-50"
                      >
                        Resend Activation
                      </button>
                    )}
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {totalPages > 1 && (
        <div className="mt-4 flex items-center gap-2 text-sm">
          {Array.from({ length: totalPages }, (_, i) => i + 1).map((p) => (
            <button
              key={p}
              onClick={() => updateParam("page", String(p))}
              className={`rounded px-2 py-1 ${p === data.page ? "bg-slate-900 text-white" : "hover:bg-black/5"}`}
            >
              {p}
            </button>
          ))}
        </div>
      )}
    </div>
  );
}
