"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import BodyClass from "@/components/BodyClass";
import { apiPost, apiUrl } from "@/lib/clientApi";

/**
 * forgot_password.blade.php (members) and forgotPassword_admin.blade.php
 * (admins): type the account's email -- SEND stays disabled until it is
 * one we know -- and a new password is emailed to it.
 */
export default function ForgotPasswordForm({
  checkUrl,
  sendUrl,
  backHref,
  backLabel,
}: {
  /** Returns { valid: boolean } for ?email=. */
  checkUrl: string;
  sendUrl: string;
  backHref: string;
  backLabel: string;
}) {
  const [email, setEmail] = useState("");
  const [valid, setValid] = useState(false);
  const [sending, setSending] = useState(false);
  const [result, setResult] = useState<{ kind: "ok" | "error"; text: string } | null>(null);

  useEffect(() => {
    setValid(false);
    if (!email) return;
    const timer = setTimeout(() => {
      void fetch(apiUrl(`${checkUrl}?${new URLSearchParams({ email })}`))
        .then((res) => res.json())
        .then((data) => setValid(data.valid === true))
        .catch(() => setValid(false));
    }, 300);
    return () => clearTimeout(timer);
  }, [checkUrl, email]);

  async function handleSend() {
    setSending(true);
    setResult(null);
    try {
      const res = await apiPost(sendUrl, { email });
      const data = await res.json();
      setResult({ kind: res.ok ? "ok" : "error", text: data.message ?? "Email could not be sent right now." });
      if (res.ok) setEmail("");
    } catch {
      setResult({ kind: "error", text: "Could not reach the server. Please try again." });
    } finally {
      setSending(false);
    }
  }

  return (
    <>
      <BodyClass name="auth" />
      <div className="banner">
        <div className="content content-log">
          <img src="/front_end/images/logo.png" className="img-responsive center-block" alt="logo" />
          <div className="title">Reset Password</div>
          <hr />
          <div className="form-group">
            <label className="label_">E-mail</label>
            <input
              className="form-control"
              autoComplete="off"
              type="email"
              placeholder="Enter your E-mail address"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
            />
            <div>{email && !valid ? "Not a valid email" : ""}</div>
          </div>
          <div className="subBtn">
            <button className="btn btn-default" type="button" disabled={!valid || sending} onClick={handleSend}>
              SEND
            </button>
            {sending && (
              <div>
                <i className="fa fa-spinner fa-pulse fa-3x fa-fw"></i>
                <span>Loading...</span>
              </div>
            )}
          </div>
          <br />
          <Link className="back-btn" href={backHref}>
            <i className="fa fa-long-arrow-left" aria-hidden="true"></i> {backLabel}
          </Link>
          {result && <p className={result.kind === "ok" ? "text-success" : "text-danger"}>{result.text}</p>}
        </div>
      </div>
    </>
  );
}
