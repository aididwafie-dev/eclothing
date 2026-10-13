"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useState } from "react";
import BodyClass from "@/components/BodyClass";

export default function LoginPage() {
  const router = useRouter();
  const [sId, setSId] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setSubmitting(true);
    setError(null);

    try {
      const res = await fetch("/api/auth/login", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ sId, password }),
      });
      const data = await res.json();

      if (!res.ok) {
        setError(data.message ?? "Login failed.");
        return;
      }

      router.push("/personal-details");
      router.refresh();
    } catch {
      setError("Could not reach the server. Please try again.");
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <>
      <BodyClass name="auth" />
      <div className="banner">
        <div className="content content-log">
          <img src="/front_end/images/logo.png" className="img-responsive center-block" alt="logo" />
          <h2 className="text-center">e-Clothing</h2>
          <br />

          <form onSubmit={handleSubmit} className="login-form" autoComplete="off">
            <div className="form-group col-sm-6">
              <label className="label_">Service ID</label>
              <input
                className="form-control"
                type="text"
                required
                placeholder="Enter your Service ID"
                value={sId}
                onChange={(e) => setSId(e.target.value)}
              />
            </div>

            <div className="form-group col-sm-6">
              <label className="label_">Password</label>
              <input
                className="form-control"
                type="password"
                required
                placeholder="Enter your password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
              />
            </div>

            {error && (
              <div className="form-group col-sm-12">
                <p className="text-danger">{error}</p>
              </div>
            )}

            <div className="form-group text-center">
              <input
                className="btn btn-success btn-lg"
                type="submit"
                value={submitting ? "Signing in..." : "Sign in"}
                disabled={submitting}
              />
              <br />
              <Link href="/forgot-password" className="redirect-link">
                Forgot Password?
              </Link>
            </div>
          </form>

          <div className="log-btns text-center">
            <small>Not registered yet?</small>
            <Link href="/register" className="btn btn-info btn-block btn-log">
              Click here to register now
            </Link>
            <Link href="/admin/login" className="btn btn-sm btn-default btn-block btn-log">
              Admin Login
            </Link>
          </div>
        </div>
      </div>
    </>
  );
}
