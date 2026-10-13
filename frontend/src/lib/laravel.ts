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
// A separate cookie from the member token -- a member and an admin can be
// signed in in the same browser at once, same as the session-based web app
// (UserController::userLogout / AdminController::adminLogout only forget
// their own key).
const ADMIN_TOKEN_COOKIE = "plas_admin_token";

export class LaravelApiError extends Error {
  constructor(public status: number, public body: unknown) {
    super(`Laravel API request failed with ${status}`);
  }
}

async function getCookieToken(name: string): Promise<string | undefined> {
  const store = await cookies();
  return store.get(name)?.value;
}

async function setCookieToken(name: string, token: string): Promise<void> {
  const store = await cookies();
  store.set(name, token, {
    httpOnly: true,
    sameSite: "lax",
    secure: process.env.NODE_ENV === "production",
    path: "/",
    maxAge: 60 * 60 * 24 * 30,
  });
}

async function clearCookieToken(name: string): Promise<void> {
  const store = await cookies();
  store.delete(name);
}

export const getToken = () => getCookieToken(TOKEN_COOKIE);
export const setToken = (token: string) => setCookieToken(TOKEN_COOKIE, token);
export const clearToken = () => clearCookieToken(TOKEN_COOKIE);

export const getAdminToken = () => getCookieToken(ADMIN_TOKEN_COOKIE);
export const setAdminToken = (token: string) => setCookieToken(ADMIN_TOKEN_COOKIE, token);
export const clearAdminToken = () => clearCookieToken(ADMIN_TOKEN_COOKIE);

/** Unauthenticated call -- login, register, etc. */
export async function laravelFetch<T>(path: string, init?: RequestInit): Promise<T> {
  const method = (init?.method ?? "GET").toUpperCase();
  // IIS/FastCGI rejects a POST/PUT with neither a body nor a Content-Length
  // (411 Length Required) -- undici's fetch doesn't send one for a
  // bodyless request, so a write with nothing to say (logout, checkout)
  // still needs an explicit empty body.
  const needsBody = ["POST", "PUT", "PATCH", "DELETE"].includes(method) && init?.body === undefined;

  // A FormData body (file uploads) needs fetch to set its own
  // multipart/form-data Content-Type with the boundary -- setting it
  // ourselves would drop the boundary and Laravel couldn't parse the body.
  const isFormData = typeof FormData !== "undefined" && init?.body instanceof FormData;

  const res = await fetch(`${LARAVEL_API_URL}${path}`, {
    ...init,
    body: needsBody ? "{}" : init?.body,
    headers: {
      Accept: "application/json",
      ...(isFormData ? {} : { "Content-Type": "application/json" }),
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

/** Authenticated call -- attaches the admin's bearer token from the cookie. */
export async function laravelFetchAdminAuthed<T>(path: string, init?: RequestInit): Promise<T> {
  const token = await getAdminToken();
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
