import { NextRequest, NextResponse } from "next/server";
import { LaravelApiError, laravelFetchAdminAuthed } from "@/lib/laravel";

export async function GET() {
  try {
    const data = await laravelFetchAdminAuthed("/admin/system-settings/site");
    return NextResponse.json(data);
  } catch (err) {
    if (err instanceof LaravelApiError) return NextResponse.json(err.body, { status: err.status });
    return NextResponse.json({ message: "Could not load site settings." }, { status: 500 });
  }
}

export async function PUT(request: NextRequest) {
  const body = await request.json();
  try {
    const data = await laravelFetchAdminAuthed("/admin/system-settings/site", {
      method: "PUT",
      body: JSON.stringify(body),
    });
    return NextResponse.json(data);
  } catch (err) {
    if (err instanceof LaravelApiError) return NextResponse.json(err.body, { status: err.status });
    return NextResponse.json({ message: "Could not save the site title." }, { status: 500 });
  }
}
