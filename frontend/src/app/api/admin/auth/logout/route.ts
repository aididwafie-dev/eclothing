import { NextResponse } from "next/server";
import { clearAdminToken, getAdminToken, laravelFetch } from "@/lib/laravel";

export async function POST() {
  const token = await getAdminToken();

  if (token) {
    try {
      await laravelFetch("/admin/auth/logout", {
        method: "POST",
        headers: { Authorization: `Bearer ${token}` },
      });
    } catch {
      // The token may already be invalid server-side; the cookie still
      // needs clearing either way.
    }
  }

  await clearAdminToken();

  return NextResponse.json({ ok: true });
}
