import { NextRequest, NextResponse } from "next/server";
import { LaravelApiError, laravelFetchAdminAuthed } from "@/lib/laravel";

export async function GET() {
  try {
    const data = await laravelFetchAdminAuthed("/admin/bases");
    return NextResponse.json(data);
  } catch (err) {
    if (err instanceof LaravelApiError) {
      return NextResponse.json(err.body, { status: err.status });
    }
    return NextResponse.json({ message: "Could not load bases." }, { status: 500 });
  }
}

export async function POST(request: NextRequest) {
  const body = await request.json();

  try {
    const data = await laravelFetchAdminAuthed("/admin/bases", {
      method: "POST",
      body: JSON.stringify(body),
    });
    return NextResponse.json(data);
  } catch (err) {
    if (err instanceof LaravelApiError) {
      return NextResponse.json(err.body, { status: err.status });
    }
    return NextResponse.json({ message: "Could not create the base." }, { status: 500 });
  }
}
