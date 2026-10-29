"use client";

import Link from "next/link";
import { useState } from "react";
import { apiPost } from "@/lib/clientApi";
import type { AdminUniform } from "@/lib/types";

/** uniformList.blade.php sorted on the numeric part of the type code, then the name. */
function sortKey(u: AdminUniform) {
  const n = parseInt(u.uniformType, 10);
  return [Number.isNaN(n) ? Infinity : n, u.uniformName ?? ""] as const;
}

export default function UniformsPageClient({ initialUniforms }: { initialUniforms: AdminUniform[] }) {
  const [uniforms, setUniforms] = useState(() =>
    [...initialUniforms].sort((a, b) => {
      const [an, aName] = sortKey(a);
      const [bn, bName] = sortKey(b);
      return an - bn || aName.localeCompare(bName);
    }),
  );

  async function toggleActive(uniform: AdminUniform) {
    if (!confirm("Are you sure?")) return;
    const res = await apiPost(`/api/admin/uniforms/${uniform.id}/toggle-active`);
    if (!res.ok) return;
    const data = await res.json();
    setUniforms((prev) => prev.map((u) => (u.id === uniform.id ? { ...u, active: data.active } : u)));
  }

  return (
    <>
      <div className="text-center">
        <Link href="/admin/uniforms/add" className="btn btn-primary">
          <i className="fa fa-plus" aria-hidden="true"></i> ADD NEW UNIFORM TYPE
        </Link>
      </div>
      <div className="content full table-responsive">
        <table className="table table-orders" style={{ width: "100%" }}>
          <thead>
            <tr>
              <th>Uniform type</th>
              <th>Uniform name</th>
              <th>Edit</th>
              <th>Clothes &amp; accessories</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            {uniforms.map((u) => (
              <tr key={u.id}>
                <td data-label="Uniform type">{u.uniformType}</td>
                <td data-label="Uniform name">{u.uniformName}</td>
                <td data-label="Edit">
                  <Link href={`/admin/uniforms/${u.id}/edit`} className="btn btn-sm btn-primary">
                    <i className="glyphicon glyphicon-edit"></i>
                  </Link>
                </td>
                <td data-label="Clothes & accessories">
                  <Link href={`/admin/uniforms/${u.id}/clothes`} className="btn btn-sm btn-info">
                    <i className="glyphicon glyphicon-folder-open"></i> &nbsp;Clothes
                  </Link>{" "}
                  <Link href={`/admin/uniforms/${u.id}/accessories`} className="btn btn-sm btn-info">
                    <i className="glyphicon glyphicon-paperclip"></i> &nbsp;Accessories
                  </Link>
                </td>
                <td data-label="Actions">
                  {u.active ? (
                    <button type="button" className="btn btn-sm btn-warning" onClick={() => toggleActive(u)}>
                      <i className="fa fa-lock" aria-hidden="true"></i> Disable
                    </button>
                  ) : (
                    <button type="button" className="btn btn-sm btn-primary" onClick={() => toggleActive(u)}>
                      <i className="fa fa-unlock" aria-hidden="true"></i> Enable
                    </button>
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </>
  );
}
