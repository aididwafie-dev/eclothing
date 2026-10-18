"use client";

import { BASE_PATH } from "./basePath";

/**
 * Thin wrappers around fetch for client components calling our own Route
 * Handlers. Every write always carries a real body -- IIS/ARR rejects a
 * POST/PUT/DELETE that reaches the Next.js upstream with neither a body
 * nor a Content-Length (411 Length Required; see lib/laravel.ts's
 * laravelFetch for the same fix on the Next.js -> Laravel hop). A plain
 * `fetch(url, { method: "POST" })` with no body does not reliably avoid
 * this, so every call here sends one.
 */

/**
 * basePath only rewrites Next's own page/asset routing (Link, the router,
 * _next/static) -- it does nothing for a plain fetch() call, which is a
 * raw browser API with no idea this app lives under /next-preview. Hit a
 * bare "/api/..." path from the client and IIS's rewrite rule (which only
 * matches "^next-preview(/.*)?$") never forwards it to this app at all;
 * it falls straight through to the real Laravel site's own /api route
 * instead -- wrong backend, no admin/member bearer token attached, and a
 * 401 that looks like "logged out" even though login just succeeded.
 * Every client-side call to one of our own Route Handlers must go through
 * this (directly or via apiPost/apiPut/apiDelete below).
 */
export function apiUrl(path: string): string {
  return `${BASE_PATH}${path}`;
}

async function send(url: string, method: string, body?: unknown): Promise<Response> {
  return fetch(apiUrl(url), {
    method,
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(body ?? {}),
  });
}

export const apiPost = (url: string, body?: unknown) => send(url, "POST", body);
export const apiPut = (url: string, body?: unknown) => send(url, "PUT", body);
export const apiDelete = (url: string, body?: unknown) => send(url, "DELETE", body);
