import { NextRequest, NextResponse } from "next/server";
import { LaravelApiError, laravelFetchAdminAuthed } from "@/lib/laravel";

export async function GET(request: NextRequest) {
  const qs = request.nextUrl.search;
  try {
    const data = await laravelFetchAdminAuthed(`/admin/users/ranks${qs}`);
    return NextResponse.json(data);
  } catch (err) {
    if (err instanceof LaravelApiError) {
      return NextResponse.json(err.body, { status: err.status });
    }
    return NextResponse.json({ ranks: [] }, { status: 500 });
  }
}
