import { NextResponse } from "next/server";
import { LaravelApiError, laravelFetchAdminAuthed } from "@/lib/laravel";

export async function POST() {
  try {
    const data = await laravelFetchAdminAuthed("/admin/users/block-all", { method: "POST" });
    return NextResponse.json(data);
  } catch (err) {
    if (err instanceof LaravelApiError) {
      return NextResponse.json(err.body, { status: err.status });
    }
    return NextResponse.json({ message: "Could not block all users." }, { status: 500 });
  }
}
