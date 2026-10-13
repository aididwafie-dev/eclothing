import { NextResponse } from "next/server";
import { LaravelApiError, laravelFetchAuthed } from "@/lib/laravel";

export async function GET(
  request: Request,
  { params }: { params: Promise<{ id: string }> },
) {
  const { id } = await params;

  try {
    const data = await laravelFetchAuthed(`/uniforms/${id}/clothes`);
    return NextResponse.json(data);
  } catch (err) {
    if (err instanceof LaravelApiError) {
      return NextResponse.json(err.body, { status: err.status });
    }
    return NextResponse.json({ message: "Could not load clothing items." }, { status: 500 });
  }
}
