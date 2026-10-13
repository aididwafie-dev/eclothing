import { NextRequest, NextResponse } from "next/server";
import { LaravelApiError, laravelFetchAdminAuthed } from "@/lib/laravel";

export async function POST(request: NextRequest) {
  const body = await request.json();

  try {
    const data = await laravelFetchAdminAuthed("/admin/announcements/send", {
      method: "POST",
      body: JSON.stringify(body),
    });
    return NextResponse.json(data);
  } catch (err) {
    if (err instanceof LaravelApiError) {
      return NextResponse.json(err.body, { status: err.status });
    }
    return NextResponse.json({ message: "Could not send the announcement." }, { status: 500 });
  }
}
