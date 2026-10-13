import { NextResponse } from "next/server";
import { clearToken, getToken, laravelFetch } from "@/lib/laravel";

export async function POST() {
  const token = await getToken();

  if (token) {
    try {
      await laravelFetch("/auth/logout", {
        method: "POST",
        headers: { Authorization: `Bearer ${token}` },
      });
    } catch {
      // The token may already be invalid server-side; the cookie still
      // needs clearing either way.
    }
  }

  await clearToken();

  return NextResponse.json({ ok: true });
}
