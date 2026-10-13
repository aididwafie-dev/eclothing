import { NextRequest, NextResponse } from "next/server";
import { LaravelApiError, laravelFetchAuthed } from "@/lib/laravel";

export async function POST(request: NextRequest) {
  const body = await request.json().catch(() => ({}));

  try {
    const data = await laravelFetchAuthed("/notifications/read", {
      method: "POST",
      body: JSON.stringify(body),
    });
    return NextResponse.json(data);
  } catch (err) {
    if (err instanceof LaravelApiError) {
      return NextResponse.json(err.body, { status: err.status });
    }
    return NextResponse.json({ message: "Could not update notifications." }, { status: 500 });
  }
}
