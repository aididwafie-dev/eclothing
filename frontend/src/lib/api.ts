/**
 * Talks to the Laravel backend's JSON API. Not wired up yet -- the member
 * and admin pages in this scaffold render static placeholders until the
 * matching Laravel endpoints exist and this calls them for real.
 *
 * Auth will reuse the mobile app's bearer-token system
 * (mobile_api_tokens + EnsureMobileTokenIsAuthenticated) rather than PHP
 * sessions, since Node can't read those directly. The token will be kept in
 * an httpOnly cookie set by a Next.js route handler on login.
 */

const API_BASE_URL = process.env.NEXT_PUBLIC_API_BASE_URL ?? "http://127.0.0.1:8000";

export class ApiError extends Error {
  constructor(public status: number, message: string) {
    super(message);
    this.name = "ApiError";
  }
}

export async function apiFetch<T>(path: string, init?: RequestInit): Promise<T> {
  const res = await fetch(`${API_BASE_URL}${path}`, {
    ...init,
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
      ...init?.headers,
    },
  });

  if (!res.ok) {
    throw new ApiError(res.status, `Request to ${path} failed with ${res.status}`);
  }

  return res.json() as Promise<T>;
}
