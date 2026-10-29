"use client";

import { useEffect, useState } from "react";
import { apiPut, apiUrl } from "@/lib/clientApi";

export default function ChangeEmailForm() {
  const [email, setEmail] = useState("");
  const [taken, setTaken] = useState("");
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState<{ kind: "ok" | "error"; text: string } | null>(null);

  // Blade's keyup check against /if-already-exists -- disables Confirm
  // while the typed address belongs to another account.
  useEffect(() => {
    if (!email) {
      setTaken("");
      return;
    }
    const timer = setTimeout(() => {
      const query = new URLSearchParams({ field: "email", value: email });
      void fetch(apiUrl(`/api/auth/check-availability?${query}`))
        .then((res) => res.json())
        .then((data) => setTaken(data.available === false ? "This email already exists" : ""))
        .catch(() => setTaken(""));
    }, 300);
    return () => clearTimeout(timer);
  }, [email]);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setSaving(true);
    setMessage(null);
    try {
      const res = await apiPut("/api/account/email", { newEmail: email });
      const data = await res.json();
      if (!res.ok) {
        setMessage({ kind: "error", text: data.message ?? "Could not update your email." });
        return;
      }
      setMessage({ kind: "ok", text: data.message ?? "You have successfully updated your email id." });
      setEmail("");
    } catch {
      setMessage({ kind: "error", text: "Could not reach the server." });
    } finally {
      setSaving(false);
    }
  }

  return (
    <form autoComplete="off" onSubmit={handleSubmit} onReset={() => { setEmail(""); setMessage(null); }}>
      <div className="form-group">
        <label className="label_">New email address</label>
        <input
          className="form-control"
          type="email"
          required
          placeholder="Enter your new email address"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
        />
        <div>{taken}</div>
      </div>

      {message && <p className={message.kind === "ok" ? "text-success" : "text-danger"}>{message.text}</p>}

      <div className="subBtn">
        <input className="btn btn-default" type="submit" value={saving ? "Saving..." : "Confirm"} disabled={saving || !!taken} />{" "}
        <input className="btn btn-default" type="reset" value="Reset" />
      </div>
    </form>
  );
}
