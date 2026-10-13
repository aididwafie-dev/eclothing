import { cookies } from "next/headers";

/**
 * Server-only. Calls the Laravel JSON API (the same bearer-token API the
 * plas-mobile Flutter app uses) and never runs in the browser -- the token
 * lives in an httpOnly cookie that client-side JS can't read, so every
 * Laravel call is made from a Server Component or a Route Handler, never
 * from the client.
 */

const LARAVEL_API_URL = process.env.LARAVEL_API_URL ?? "http://127.0.0.1/api";
const TOKEN_COOKIE = "plas_token";

export class LaravelApiError extends Error {
  constructor(public status: number, public body: unknown) {
    super(`Laravel API request failed with ${status}`);
  }
}

export async function getToken(): Promise<string | undefined> {
  const store = await cookies();
  return store.get(TOKEN_COOKIE)?.value;
}

export async function setToken(token: string): Promise<void> {
  const store = await cookies();
  store.set(TOKEN_COOKIE, token, {
    httpOnly: true,
    sameSite: "lax",
    secure: process.env.NODE_ENV === "production",
    path: "/",
    maxAge: 60 * 60 * 24 * 30,
  });
}

export async function clearToken(): Promise<void> {
  const store = await cookies();
  store.delete(TOKEN_COOKIE);
}

/** Unauthenticated call -- login, register, etc. */
export async function laravelFetch<T>(path: string, init?: RequestInit): Promise<T> {
  const res = await fetch(`${LARAVEL_API_URL}${path}`, {
    ...init,
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
      ...init?.headers,
    },
    cache: "no-store",
  });

  const body = await res.json().catch(() => null);
  if (!res.ok) {
    throw new LaravelApiError(res.status, body);
  }
  return body as T;
}

/** Authenticated call -- attaches the member's bearer token from the cookie. */
export async function laravelFetchAuthed<T>(path: string, init?: RequestInit): Promise<T> {
  const token = await getToken();
  if (!token) {
    throw new LaravelApiError(401, { message: "Not signed in." });
  }

  return laravelFetch<T>(path, {
    ...init,
    headers: {
      Authorization: `Bearer ${token}`,
      ...init?.headers,
    },
  });
}
