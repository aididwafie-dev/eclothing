import { NextResponse } from "next/server";
import { LaravelApiError, laravelFetchAdminAuthed } from "./laravel";

/**
 * The body of nearly every admin Route Handler: forward to Laravel with the
 * admin token, pass Laravel's own error status/body straight through, and
 * fall back to a generic 500 when Laravel couldn't be reached at all.
 */
export async function adminProxy(path: string, failMessage: string, init?: RequestInit) {
  try {
    const data = await laravelFetchAdminAuthed(path, init);
    return NextResponse.json(data);
  } catch (err) {
    if (err instanceof LaravelApiError) {
      return NextResponse.json(err.body, { status: err.status });
    }
    return NextResponse.json({ message: failMessage }, { status: 500 });
  }
}

/** A write whose JSON request body is passed through as-is. */
export async function adminProxyJson(request: Request, path: string, method: string, failMessage: string) {
  const body = await request.json().catch(() => ({}));
  return adminProxy(path, failMessage, { method, body: JSON.stringify(body) });
}
