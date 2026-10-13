import { NextRequest, NextResponse } from "next/server";
import { LaravelApiError, laravelFetchAdminAuthed } from "@/lib/laravel";

export async function GET(request: Request, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {
    const data = await laravelFetchAdminAuthed(`/admin/bases/${id}`);
    return NextResponse.json(data);
  } catch (err) {
    if (err instanceof LaravelApiError) {
      return NextResponse.json(err.body, { status: err.status });
    }
    return NextResponse.json({ message: "Could not load the base." }, { status: 500 });
  }
}

export async function PUT(request: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const body = await request.json();

  try {
    const data = await laravelFetchAdminAuthed(`/admin/bases/${id}`, {
      method: "PUT",
      body: JSON.stringify(body),
    });
    return NextResponse.json(data);
  } catch (err) {
    if (err instanceof LaravelApiError) {
      return NextResponse.json(err.body, { status: err.status });
    }
    return NextResponse.json({ message: "Could not save the base." }, { status: 500 });
  }
}

export async function DELETE(request: Request, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {
    const data = await laravelFetchAdminAuthed(`/admin/bases/${id}`, { method: "DELETE" });
    return NextResponse.json(data);
  } catch (err) {
    if (err instanceof LaravelApiError) {
      return NextResponse.json(err.body, { status: err.status });
    }
    return NextResponse.json({ message: "Could not delete the base." }, { status: 500 });
  }
}
