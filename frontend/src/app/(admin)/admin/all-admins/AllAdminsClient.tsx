"use client";

import Link from "next/link";
import { useState } from "react";
import { apiDelete, apiPost } from "@/lib/clientApi";
import type { AdminAccountRow } from "@/lib/types";

export default function AllAdminsClient({ initialAdmins }: { initialAdmins: AdminAccountRow[] }) {
  const [admins, setAdmins] = useState(initialAdmins);
  const [search, setSearch] = useState("");
  const [error, setError] = useState<string | null>(null);

  // The DataTables search box in List_ofAdmins.blade.php matched on
  // name, email and username.
  const needle = search.trim().toLowerCase();
  const visible = needle
    ? admins.filter((a) => [a.name, a.email, a.serviceId].some((v) => v?.toLowerCase().includes(needle)))
    : admins;

  async function toggleStatus(admin: AdminAccountRow) {
    if (!confirm("Are you sure?")) return;
    setError(null);
    const res = await apiPost(`/api/admin/admins/${admin.id}/toggle-status`);
    const data = await res.json();
    if (!res.ok) {
      setError(data.message ?? "Could not change the status.");
      return;
    }
    setAdmins((prev) => prev.map((a) => (a.id === admin.id ? { ...a, active: data.active } : a)));
  }

  async function handleDelete(admin: AdminAccountRow) {
    if (!confirm("Are you sure you want to remove this user as an admin?")) return;
    setError(null);
    const res = await apiDelete(`/api/admin/admins/${admin.id}`);
    const data = await res.json();
    if (!res.ok) {
      setError(data.message ?? "Could not remove the admin.");
      return;
    }
    setAdmins((prev) => prev.filter((a) => a.id !== admin.id));
  }

  return (
    <div className="content full table-responsive">
      {error && <div className="alert alert-danger">{error}</div>}
      <div className="text-right">
        <label>
          Search:{" "}
          <input type="search" className="form-control input-sm" style={{ display: "inline-block", width: "auto" }} value={search} onChange={(e) => setSearch(e.target.value)} />
        </label>
      </div>
      <table className="table table-orders" style={{ width: "100%" }}>
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email ID</th>
            <th>Service ID</th>
            <th>Jawatan / Rank</th>
            <th>Access</th>
            <th>Status</th>
            <th>Edit</th>
            <th>Delete</th>
          </tr>
        </thead>
        <tbody>
          {visible.map((admin, i) => (
            <tr key={admin.id}>
              <td data-label="ID">{i + 1}</td>
              <td data-label="Name">{admin.name}</td>
              <td data-label="Email ID">{admin.email}</td>
              <td data-label="Service ID">{admin.serviceId}</td>
              <td data-label="Jawatan / Rank">{admin.jawatanRank}</td>
              <td data-label="Access">
                {admin.role === "orders" ? (
                  <span className="label label-info">Uniform Orders</span>
                ) : (
                  <span className="label label-primary">Superadmin</span>
                )}
              </td>
              <td data-label="Status">
                <button type="button" className="btn btn-sm btn-warning" onClick={() => toggleStatus(admin)}>
                  {admin.active ? (
                    <>
                      <i className="fa fa-lock" aria-hidden="true"></i> Active
                    </>
                  ) : (
                    <>
                      <i className="fa fa-unlock-alt" aria-hidden="true"></i> Inactive
                    </>
                  )}
                </button>
              </td>
              <td data-label="Edit">
                <Link href={`/admin/all-admins/${admin.id}`} className="btn btn-sm btn-info">
                  <i className="fa fa-pencil" aria-hidden="true"></i>
                </Link>
              </td>
              <td data-label="Delete">
                <button type="button" className="btn btn-sm btn-danger" onClick={() => handleDelete(admin)}>
                  <span className="glyphicon glyphicon-trash"></span>
                </button>
              </td>
            </tr>
          ))}
          {visible.length === 0 && (
            <tr>
              <td colSpan={9}>No matching records found</td>
            </tr>
          )}
        </tbody>
      </table>
    </div>
  );
}
