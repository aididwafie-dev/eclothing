import { NextRequest, NextResponse } from "next/server";
import { LaravelApiError, laravelFetchAdminAuthed } from "@/lib/laravel";

export async function PUT(request: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const body = await request.json();
  try {
    const data = await laravelFetchAdminAuthed(`/admin/system-settings/uniform-items/${id}`, {
      method: "PUT",
      body: JSON.stringify(body),
    });
    return NextResponse.json(data);
  } catch (err) {
    if (err instanceof LaravelApiError) return NextResponse.json(err.body, { status: err.status });
    return NextResponse.json({ message: "Could not save the item." }, { status: 500 });
  }
}
