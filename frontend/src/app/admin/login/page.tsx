"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useState } from "react";
import BodyClass from "@/components/BodyClass";

export default function AdminLoginPage() {
  const router = useRouter();
  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setSubmitting(true);
    setError(null);

    try {
      const res = await fetch("/api/admin/auth/login", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ username, password }),
      });
      const data = await res.json();

      if (!res.ok) {
        setError(data.message ?? "Login failed.");
        return;
      }

      router.push("/admin/orders");
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
          <div className="title">Login for admin</div>
          <hr />

          <form onSubmit={handleSubmit} autoComplete="off">
            <div className="form-group">
              <label className="label_">Username:</label>
              <input
                className="form-control"
                type="text"
                placeholder="Enter your username...."
                value={username}
                onChange={(e) => setUsername(e.target.value)}
              />
            </div>

            <div className="form-group">
              <label className="label_">Password:</label>
              <input
                className="form-control"
                type="password"
                placeholder="Enter your password...."
                value={password}
                onChange={(e) => setPassword(e.target.value)}
              />
            </div>

            {error && <p className="text-danger">{error}</p>}

            <div className="subBtn">
              <input className="btn btn-default" type="submit" value={submitting ? "Signing in..." : "Sign in"} disabled={submitting} />
              <input
                className="btn btn-default"
                type="reset"
                value="Reset"
                onClick={() => {
                  setUsername("");
                  setPassword("");
                }}
              />
            </div>
          </form>
          <span>
            <Link href="/admin/forgot-password" className="redirect-link">
              Forgot Password?
            </Link>
          </span>
          <br />
          <Link className="back-btn" href="/login">
            <i className="fa fa-long-arrow-left" aria-hidden="true"></i> Home
          </Link>
        </div>
      </div>
    </>
  );
}
