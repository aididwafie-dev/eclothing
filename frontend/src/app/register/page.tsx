"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import BodyClass from "@/components/BodyClass";
import { apiUrl } from "@/lib/clientApi";

type Done = { mailSent: boolean; activationUrl: string | null };

/** Blade's keyup check against /value-exist, debounced. */
function useTaken(field: "email" | "s_id", value: string, message: string) {
  const [taken, setTaken] = useState("");

  useEffect(() => {
    if (!value) {
      setTaken("");
      return;
    }
    const timer = setTimeout(() => {
      const query = new URLSearchParams({ field, value });
      void fetch(apiUrl(`/api/auth/check-availability?${query}`))
        .then((res) => res.json())
        .then((data) => setTaken(data.available === false ? message : ""))
        .catch(() => setTaken(""));
    }, 300);
    return () => clearTimeout(timer);
  }, [field, value, message]);

  return taken;
}

/** register.blade.php, then register_done.blade.php once signed up. */
export default function RegisterPage() {
  const [email, setEmail] = useState("");
  const [sId, setSId] = useState("");
  const [password, setPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const [done, setDone] = useState<Done | null>(null);

  const emailTaken = useTaken("email", email, "Your email address already exists");
  const sIdTaken = useTaken("s_id", sId, "Your service id is already exist");

  function reset() {
    setEmail("");
    setSId("");
    setPassword("");
    setConfirmPassword("");
    setError(null);
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (!/^\d{1,7}$/.test(sId)) return setError("Put your valid service ID");
    if (password.length < 8) return setError("Put a password which is a minimum length of 8");
    if (password !== confirmPassword) return setError("Please enter the same password in both boxes");

    setSubmitting(true);
    setError(null);
    try {
      const res = await fetch(apiUrl("/api/auth/register"), {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email, sId, password, confirmPassword }),
      });
      const data = await res.json();
      if (!res.ok) {
        setError(data.message ?? "Could not register right now.");
        return;
      }
      setDone({ mailSent: data.mailSent !== false, activationUrl: data.activationUrl ?? null });
    } catch {
      setError("Could not reach the server. Please try again.");
    } finally {
      setSubmitting(false);
    }
  }

  if (done) {
    return (
      <>
        <BodyClass name="auth" />
        <div className="banner">
          <div className="content content-log">
            <h2 className="text-center">
              Congratulations!
              <br />
              <br />
              You have registered successfully. <br />
              <br />
              {done.mailSent ? (
                "Please check your email to verify your account."
              ) : (
                <>
                  Activation email could not be sent. Please use this link to activate your account:
                  <br />
                  <a href={done.activationUrl ?? "#"}>{done.activationUrl}</a>
                </>
              )}
            </h2>
            <br />
            <br />
            {done.mailSent && (
              <h4 className="text-center">Please also check your Spam / Junk folder for the activation email.</h4>
            )}
          </div>
        </div>
      </>
    );
  }

  return (
    <>
      <BodyClass name="auth" />
      <div className="banner">
        <div className="content content-log">
          <img src="/front_end/images/logo.png" className="img-responsive center-block" alt="logo" />
          <div className="title">Registration for new user</div>
          <hr />
          <form autoComplete="off" onSubmit={handleSubmit} onReset={reset}>
            <div className="form-group">
              <label className="label_">E-mail</label>
              <input
                className="form-control"
                type="email"
                required
                placeholder="Enter your e-mail address"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
              />
              <div>{emailTaken}</div>
            </div>
            <div className="form-group">
              <label className="label_">Service ID</label>
              <input
                className="form-control"
                type="text"
                required
                maxLength={7}
                placeholder="Enter your Service ID"
                value={sId}
                onChange={(e) => setSId(e.target.value)}
              />
              <div>{sIdTaken}</div>
            </div>
            <div className="form-group">
              <label className="label_">Password</label>
              <input
                className="form-control"
                type="password"
                required
                placeholder="Create a password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
              />
            </div>
            <div className="form-group">
              <label className="label_">Confirm Password:</label>
              <input
                className="form-control"
                type="password"
                required
                placeholder="Confirm your password"
                value={confirmPassword}
                onChange={(e) => setConfirmPassword(e.target.value)}
              />
            </div>

            {error && <p className="text-danger">{error}</p>}

            <div className="subBtn">
              <input
                className="btn btn-default"
                type="submit"
                value={submitting ? "Registering..." : "Confirm"}
                disabled={submitting || !!emailTaken || !!sIdTaken}
              />{" "}
              <input className="btn btn-default" type="reset" value="Reset" />
            </div>
          </form>
          <span>
            Already registered?{" "}
            <Link href="/login" className="redirect-link">
              Click to Login.
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
