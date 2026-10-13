import { NextRequest, NextResponse } from "next/server";
import { LaravelApiError, laravelFetchAuthed } from "@/lib/laravel";

export async function GET(request: NextRequest) {
  const piliihAngkatan = request.nextUrl.searchParams.get("piliihAngkatan") ?? "";
  const ketukanganType = request.nextUrl.searchParams.get("ketukanganType") ?? "";

  try {
    const data = await laravelFetchAuthed(
      `/profile/ranks?piliihAngkatan=${encodeURIComponent(piliihAngkatan)}&ketukanganType=${encodeURIComponent(ketukanganType)}`,
    );
    return NextResponse.json(data);
  } catch (err) {
    if (err instanceof LaravelApiError) {
      return NextResponse.json(err.body, { status: err.status });
    }
    return NextResponse.json({ ranks: [] }, { status: 500 });
  }
}
