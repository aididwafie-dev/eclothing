"use client";

/**
 * Thin wrappers around fetch for client components calling our own Route
 * Handlers. Every write always carries a real body -- IIS/ARR rejects a
 * POST/PUT/DELETE that reaches the Next.js upstream with neither a body
 * nor a Content-Length (411 Length Required; see lib/laravel.ts's
 * laravelFetch for the same fix on the Next.js -> Laravel hop). A plain
 * `fetch(url, { method: "POST" })` with no body does not reliably avoid
 * this, so every call here sends one.
 */

async function send(url: string, method: string, body?: unknown): Promise<Response> {
  return fetch(url, {
    method,
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(body ?? {}),
  });
}

export const apiPost = (url: string, body?: unknown) => send(url, "POST", body);
export const apiPut = (url: string, body?: unknown) => send(url, "PUT", body);
export const apiDelete = (url: string, body?: unknown) => send(url, "DELETE", body);
