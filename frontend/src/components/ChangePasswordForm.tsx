"use client";

import { useState } from "react";
import { apiPut } from "@/lib/clientApi";

/**
 * The old/new/confirm password form shared by the member
 * (edit_password.blade.php) and admin (passwordChange_admin.blade.php)
 * pages -- identical markup, only the endpoint and the member side's
 * 8-character minimum differ.
 */
export default function ChangePasswordForm({ endpoint, minLength }: { endpoint: string; minLength?: number }) {
  const [oldPassword, setOldPassword] = useState("");
  const [newPassword, setNewPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState<{ kind: "ok" | "error"; text: string } | null>(null);

  function reset() {
    setOldPassword("");
    setNewPassword("");
    setConfirmPassword("");
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (minLength && newPassword.length < minLength) {
      setMessage({ kind: "error", text: `Provide a valid password with a minimum length of ${minLength}` });
      return;
    }
    if (newPassword !== confirmPassword) {
      setMessage({ kind: "error", text: "Please put the same password as you provided in the box above" });
      return;
    }

    setSaving(true);
    setMessage(null);
    try {
      const res = await apiPut(endpoint, { oldPassword, newPassword });
      const data = await res.json();
      if (!res.ok) {
        setMessage({ kind: "error", text: data.message ?? "Could not update your password." });
        return;
      }
      setMessage({ kind: "ok", text: data.message ?? "You have successfully updated your password." });
      reset();
    } catch {
      setMessage({ kind: "error", text: "Could not reach the server." });
    } finally {
      setSaving(false);
    }
  }

  return (
    <form autoComplete="off" onSubmit={handleSubmit} onReset={() => { reset(); setMessage(null); }}>
      <div className="form-group">
        <label className="label_">Old Password:</label>
        <input
          className="form-control"
          type="password"
          required
          placeholder="Enter your old password...."
          value={oldPassword}
          onChange={(e) => setOldPassword(e.target.value)}
        />
      </div>

      <div className="form-group">
        <label className="label_">New Password:</label>
        <input
          className="form-control"
          type="password"
          required
          placeholder="Enter your new password...."
          value={newPassword}
          onChange={(e) => setNewPassword(e.target.value)}
        />
      </div>

      <div className="form-group">
        <label className="label_">Confirm Password:</label>
        <input
          className="form-control"
          type="password"
          required
          placeholder="Confirm your password...."
          value={confirmPassword}
          onChange={(e) => setConfirmPassword(e.target.value)}
        />
      </div>

      {message && <p className={message.kind === "ok" ? "text-success" : "text-danger"}>{message.text}</p>}

      <div className="subBtn">
        <input className="btn btn-default" type="submit" value={saving ? "Saving..." : "Confirm"} disabled={saving} />{" "}
        <input className="btn btn-default" type="reset" value="Reset" />
      </div>
    </form>
  );
}
