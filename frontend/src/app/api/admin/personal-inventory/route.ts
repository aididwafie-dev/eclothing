import { NextRequest, NextResponse } from "next/server";
import { LaravelApiError, laravelFetchAdminAuthed } from "@/lib/laravel";

export async function GET(request: NextRequest) {
  const qs = request.nextUrl.search;
  try {
    const data = await laravelFetchAdminAuthed(`/admin/personal-inventory${qs}`);
    return NextResponse.json(data);
  } catch (err) {
    if (err instanceof LaravelApiError) {
      return NextResponse.json(err.body, { status: err.status });
    }
    return NextResponse.json({ message: "Could not load personal inventory." }, { status: 500 });
  }
}
