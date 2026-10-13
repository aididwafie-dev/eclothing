import { NextRequest, NextResponse } from "next/server";
import { LaravelApiError, laravelFetchAdminAuthed } from "@/lib/laravel";

export async function POST(request: NextRequest) {
  const formData = await request.formData();
  try {
    const data = await laravelFetchAdminAuthed("/admin/system-settings/site/logo", {
      method: "POST",
      body: formData,
    });
    return NextResponse.json(data);
  } catch (err) {
    if (err instanceof LaravelApiError) return NextResponse.json(err.body, { status: err.status });
    return NextResponse.json({ message: "Could not upload the logo." }, { status: 500 });
  }
}
