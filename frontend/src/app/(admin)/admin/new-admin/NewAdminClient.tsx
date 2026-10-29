"use client";

import { useState } from "react";
import { apiPost, apiUrl } from "@/lib/clientApi";
import type { AdminCandidateResponse, AdminIdentity } from "@/lib/types";
import AdminIdentityFields from "../all-admins/AdminIdentityFields";

type Message = { kind: "ok" | "error"; text: string } | null;

/**
 * add_new_admin.blade.php (enter a service ID) followed by
 * viewDetails_before_add.blade.php (confirm and set the approver identity),
 * as two steps of one page instead of a POST round trip between them.
 */
export default function NewAdminClient() {
  const [sId, setSId] = useState("");
  const [candidate, setCandidate] = useState<AdminCandidateResponse | null>(null);
  const [identity, setIdentity] = useState<AdminIdentity | null>(null);
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState<Message>(null);

  async function handleLookup(e: React.FormEvent) {
    e.preventDefault();
    if (!/^\d{1,7}$/.test(sId.trim())) {
      setMessage({ kind: "error", text: "Put your valid service ID who is already registered" });
      return;
    }

    setBusy(true);
    setMessage(null);
    try {
      const res = await fetch(apiUrl(`/api/admin/admins/lookup?${new URLSearchParams({ sId: sId.trim() })}`));
      const data = await res.json();
      if (!res.ok) {
        setMessage({ kind: "error", text: data.message ?? "Could not look up the service ID." });
        return;
      }
      setCandidate(data);
      setIdentity({
        sId: data.candidate.username,
        pangkatId: data.candidate.pangkatId,
        jawatan: "",
        role: data.roles[0]?.id ?? "superadmin",
        baseId: "",
      });
    } catch {
      setMessage({ kind: "error", text: "Could not reach the server." });
    } finally {
      setBusy(false);
    }
  }

  async function handleAdd(e: React.FormEvent) {
    e.preventDefault();
    if (!candidate || !identity) return;
    if (!confirm("Are you sure you want to add this user as a admin?")) return;

    setBusy(true);
    setMessage(null);
    try {
      const res = await apiPost("/api/admin/admins", { username: candidate.candidate.username, ...identity });
      const data = await res.json();
      if (!res.ok) {
        setMessage({ kind: "error", text: data.message ?? "Could not add the admin." });
        return;
      }
      setMessage({ kind: "ok", text: data.message });
      setCandidate(null);
      setIdentity(null);
      setSId("");
    } catch {
      setMessage({ kind: "error", text: "Could not reach the server." });
    } finally {
      setBusy(false);
    }
  }

  function cancel() {
    setCandidate(null);
    setIdentity(null);
    setMessage(null);
  }

  const alert = message && (
    <div className={`alert ${message.kind === "ok" ? "alert-success" : "alert-danger"}`}>{message.text}</div>
  );

  if (candidate && identity) {
    return (
      <>
        <br />
        <div className="title">
          <i className="fa fa-user-plus" aria-hidden="true"></i> Add New Admin
        </div>
        <hr />
        {alert}
        <div className="containerMain">
          <div className="content">
            <div className="title">
              <i className="fa fa-user-circle" aria-hidden="true"></i> Details of New Admin:
            </div>
            <hr />
            <form autoComplete="off" onSubmit={handleAdd}>
              <div className="form-group">
                <label className="label_">Name:</label>
                <input className="form-control" type="text" value={candidate.candidate.name} readOnly />
              </div>
              <div className="form-group">
                <label className="label_">Email:</label>
                <input className="form-control" type="text" value={candidate.candidate.email} readOnly />
              </div>
              <div className="form-group">
                <label className="label_">Username:</label>
                <input className="form-control" type="text" value={candidate.candidate.username} readOnly />
              </div>
              <AdminIdentityFields
                value={identity}
                onChange={setIdentity}
                options={candidate}
                labels={{
                  sId: "Service ID / No. Tentera (for approver signature):",
                  sIdPlaceholder: `e.g. ${candidate.candidate.username}`,
                  pangkat: "Pangkat (Rank for Pegawai Pelulus signatory):",
                  jawatan: "Jawatan (Position for Pegawai Pelulus):",
                  jawatanPlaceholder: "e.g. PEGAWAI TADBIR GRED W29",
                }}
              />
              <div className="subBtn">
                <input className="btn btn-default" type="submit" value={busy ? "Adding..." : "ADD"} disabled={busy} />{" "}
                <button type="button" className="btn btn-default" onClick={cancel}>
                  {" "}
                  CANCEL
                </button>
              </div>
            </form>
          </div>
        </div>
      </>
    );
  }

  return (
    <>
      <br />
      <div className="title">
        <i className="fa fa-user-plus" aria-hidden="true"></i> Add new Admin user
      </div>
      <hr />
      {alert}
      <div className="containerMain">
        <div className="content">
          <div className="title">
            <i className="fa fa-user-circle" aria-hidden="true"></i> Service ID of new admin user
          </div>
          <hr />
          <form autoComplete="off" onSubmit={handleLookup} onReset={() => { setSId(""); setMessage(null); }}>
            <div className="form-group">
              <label className="label_">Service ID</label>
              <input
                className="form-control"
                type="text"
                placeholder="Enter the service ID of the new admin user"
                value={sId}
                onChange={(e) => setSId(e.target.value)}
              />
            </div>
            <div className="subBtn">
              <input className="btn btn-default" type="submit" value={busy ? "Checking..." : "Set as admin"} disabled={busy} />{" "}
              <input className="btn btn-default" type="reset" value="Reset" />
            </div>
          </form>
        </div>
      </div>
    </>
  );
}
