"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useState } from "react";
import { apiPut } from "@/lib/clientApi";
import type { AdminAccountDetailResponse, AdminIdentity } from "@/lib/types";
import AdminIdentityFields from "../AdminIdentityFields";

export default function EditAdminForm({ data }: { data: AdminAccountDetailResponse }) {
  const router = useRouter();
  const { admin } = data;
  const [identity, setIdentity] = useState<AdminIdentity>({
    sId: admin.sId,
    pangkatId: admin.pangkatId,
    jawatan: admin.jawatan,
    role: admin.role,
    baseId: admin.baseId,
  });
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setSaving(true);
    setError(null);
    try {
      const res = await apiPut(`/api/admin/admins/${admin.id}`, identity);
      const body = await res.json();
      if (!res.ok) {
        setError(body.message ?? "Could not save the admin.");
        return;
      }
      router.push("/admin/all-admins");
      router.refresh();
    } catch {
      setError("Could not reach the server.");
    } finally {
      setSaving(false);
    }
  }

  return (
    <form autoComplete="off" onSubmit={handleSubmit}>
      <div className="form-group">
        <label className="label_">Name:</label>
        <input className="form-control" type="text" value={admin.name} readOnly />
      </div>
      <div className="form-group">
        <label className="label_">Email:</label>
        <input className="form-control" type="text" value={admin.email} readOnly />
      </div>
      <AdminIdentityFields
        value={identity}
        onChange={setIdentity}
        options={data}
        labels={{
          sId: "Service ID / No. Tentera:",
          sIdPlaceholder: "e.g. 375292",
          pangkat: "Pangkat (Rank):",
          jawatan: "Jawatan (Position):",
          jawatanPlaceholder: "e.g. KETUA STOR PERBENDAHARAAN",
        }}
      />

      {error && <p className="text-danger">{error}</p>}

      <div className="subBtn">
        <input className="btn btn-default" type="submit" value={saving ? "Saving..." : "SIMPAN (Save)"} disabled={saving} />{" "}
        <Link href="/admin/all-admins" className="btn btn-default">
          {" "}
          KEMBALI (Back)
        </Link>
      </div>
    </form>
  );
}
