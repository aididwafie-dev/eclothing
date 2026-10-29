import { NextRequest, NextResponse } from "next/server";
import { LaravelApiError, laravelFetchAdminAuthed } from "@/lib/laravel";

export async function PUT(request: NextRequest) {
  const body = await request.json();

  try {
    const data = await laravelFetchAdminAuthed("/admin/auth/password", {
      method: "PUT",
      body: JSON.stringify(body),
    });
    return NextResponse.json(data);
  } catch (err) {
    if (err instanceof LaravelApiError) {
      return NextResponse.json(err.body, { status: err.status });
    }
    return NextResponse.json({ message: "Could not update your password." }, { status: 500 });
  }
}
