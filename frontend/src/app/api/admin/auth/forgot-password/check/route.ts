import { NextRequest, NextResponse } from "next/server";
import { laravelFetch } from "@/lib/laravel";

export async function GET(request: NextRequest) {
  const query = new URLSearchParams({ email: request.nextUrl.searchParams.get("email") ?? "" });
  try {
    return NextResponse.json(await laravelFetch<{ valid: boolean }>(`/admin/auth/check-email?${query}`));
  } catch {
    return NextResponse.json({ valid: false });
  }
}
