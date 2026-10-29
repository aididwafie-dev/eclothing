import { NextRequest, NextResponse } from "next/server";
import { laravelFetch } from "@/lib/laravel";

/** Blade's /if-valid -- an email is valid here when it already belongs to an account. */
export async function GET(request: NextRequest) {
  const query = new URLSearchParams({ field: "email", value: request.nextUrl.searchParams.get("email") ?? "" });
  try {
    const data = await laravelFetch<{ available: boolean }>(`/auth/check-availability?${query}`);
    return NextResponse.json({ valid: data.available === false });
  } catch {
    return NextResponse.json({ valid: false });
  }
}
