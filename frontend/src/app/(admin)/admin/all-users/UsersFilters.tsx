"use client";

import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { useState } from "react";
import { apiDelete, apiPost } from "@/lib/clientApi";
import type { AdminUserFilter, AdminUsersResponse } from "@/lib/types";

const FILTERS: { value: AdminUserFilter | ""; label: string }[] = [
  { value: "", label: "All" },
  { value: "incomplete_rank", label: "Incomplete: Rank" },
  { value: "incomplete_name", label: "Incomplete: Name" },
  { value: "incomplete_unit", label: "Incomplete: Unit" },
  { value: "incomplete_personal", label: "Incomplete: Personal Detail" },
  { value: "incomplete_uniform", label: "Incomplete: Uniform Detail" },
  { value: "complete_all", label: "Complete: All Details" },
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
    updateParam("search", search.trim());
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

  async function deleteUser(id: string) {
    if (!confirm("Are you sure you want to remove this user from the site permanently?")) return;
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
      const result = await res.json();
      alert(result.message);
    } finally {
      setBusy(null);
    }
  }

  async function blockAll() {
    if (
      !confirm(
        "Are you sure you want to block all users from the site permanently? Do note, you will lose records of which users actually were unblocked before this.",
      )
    )
      return;
    await apiPost("/api/admin/users/block-all");
    router.refresh();
  }

  async function unblockAll() {
    if (
      !confirm(
        "Are you sure you want to unblock all users from the site permanently? Do note, you will lose records of which users actually were blocked before this.",
      )
    )
      return;
    await apiPost("/api/admin/users/unblock-all");
    router.refresh();
  }

  const totalPages = Math.max(1, Math.ceil(data.recordsFiltered / data.perPage));

  return (
    <>
      <div className="form-group" style={{ maxWidth: 320 }}>
        <select className="form-control" value={data.filter ?? ""} onChange={(e) => updateParam("filter", e.target.value)}>
          {FILTERS.map((f) => (
            <option key={f.value} value={f.value}>
              {f.label}
            </option>
          ))}
        </select>
      </div>

      <form onSubmit={handleSearchSubmit} className="orders-search" role="search">
        <div className="orders-search-field">
          <i className="fa fa-search" aria-hidden="true"></i>
          <input
            type="text"
            className="form-control"
            placeholder="Service ID, name, unit, rank...."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            autoComplete="off"
          />
        </div>
        {currentSearch.trim() !== "" && (
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

      <div className="content full table-responsive">
        <table className="display table" style={{ width: "100%" }}>
          <thead>
            <tr>
              <th>#</th>
              <th>Service ID</th>
              <th>Rank</th>
              <th>Name</th>
              <th>Unit</th>
              <th>Basic Details</th>
              <th>Personal Details</th>
              <th>Uniform Details</th>
              <th>Status</th>
              <th>Delete</th>
              <th>Created</th>
              <th>Updated</th>
              <th>Re-send mail</th>
            </tr>
          </thead>
          <tbody>
            {users.length === 0 && (
              <tr>
                <td colSpan={13} className="text-center">
                  No members match this filter.
                </td>
              </tr>
            )}
            {users.map((user, i) => (
              <tr key={user.id}>
                <td data-label="#">{(data.page - 1) * data.perPage + i + 1}</td>
                <td data-label="Service ID">
                  <span className={`cell-id ${user.hasOrdered ? "cell-id-ordered" : "cell-id-none"}`} title={user.hasOrdered ? "Has placed orders" : "No orders yet"}>
                    {user.sId ?? "-"}
                  </span>
                </td>
                <td data-label="Rank">{user.rankName ?? ""}</td>
                <td data-label="Name">{user.name}</td>
                <td data-label="Unit">{user.unitName ?? ""}</td>
                <td data-label="Basic Details">
                  <Link href={`/admin/all-users/${user.id}/basic`} className="btn btn-sm btn-primary">
                    <span className="glyphicon glyphicon-pencil"></span>
                  </Link>
                </td>
                <td data-label="Personal Details">
                  <Link href={`/admin/all-users/${user.id}/personal`} className="btn btn-sm btn-primary">
                    <span className="glyphicon glyphicon-pencil"></span>
                  </Link>
                </td>
                <td data-label="Uniform Details">
                  <Link href={`/admin/all-users/${user.id}/uniform`} className="btn btn-sm btn-info">
                    <span className="glyphicon glyphicon-folder-open"></span> Show
                  </Link>
                </td>
                <td data-label="Status">
                  <button
                    type="button"
                    disabled={busy === user.id}
                    onClick={() => toggleBlock(user.id)}
                    className="btn btn-sm btn-warning"
                  >
                    <i className={`fa ${user.blocked ? "fa-unlock-alt" : "fa-lock"}`} aria-hidden="true"></i>{" "}
                    {user.blocked ? "Unblock" : "Block"}
                  </button>
                </td>
                <td data-label="Delete">
                  <button
                    type="button"
                    disabled={busy === user.id}
                    onClick={() => deleteUser(user.id)}
                    className="btn btn-sm btn-danger"
                  >
                    <span className="glyphicon glyphicon-trash"></span>
                  </button>
                </td>
                <td data-label="Created">{user.createdAt ?? "-"}</td>
                <td data-label="Updated">{user.updatedAt ?? "-"}</td>
                <td data-label="Re-send mail">
                  {user.activated ? (
                    "Already active"
                  ) : (
                    <button
                      type="button"
                      disabled={busy === user.id}
                      onClick={() => resendActivation(user.id)}
                      className="btn btn-sm btn-success"
                    >
                      <i className="fa fa-share" aria-hidden="true"></i>
                    </button>
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>

        <a onClick={blockAll} className="block_all btn btn-sm btn-warning" style={{ cursor: "pointer" }}>
          <i className="fa fa-lock" aria-hidden="true"></i> Block all users
        </a>{" "}
        <a onClick={unblockAll} className="unblock_all btn btn-sm btn-success" style={{ cursor: "pointer" }}>
          <i className="fa fa-lock" aria-hidden="true"></i> Unblock all users
        </a>
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
