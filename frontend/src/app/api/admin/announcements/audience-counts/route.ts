import { NextResponse } from "next/server";
import { LaravelApiError, laravelFetchAdminAuthed } from "@/lib/laravel";

export async function GET() {
  try {
    const data = await laravelFetchAdminAuthed("/admin/announcements/audience-counts");
    return NextResponse.json(data);
  } catch (err) {
    if (err instanceof LaravelApiError) {
      return NextResponse.json(err.body, { status: err.status });
    }
    return NextResponse.json({ message: "Could not load audience counts." }, { status: 500 });
  }
}
