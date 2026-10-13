import { NextRequest, NextResponse } from "next/server";
import { LaravelApiError, laravelFetchAdminAuthed } from "@/lib/laravel";

export async function GET(
  request: NextRequest,
  { params }: { params: Promise<{ sId: string }> },
) {
  const { sId } = await params;
  const qs = request.nextUrl.search;
  try {
    const data = await laravelFetchAdminAuthed(`/admin/personal-inventory/${sId}${qs}`);
    return NextResponse.json(data);
  } catch (err) {
    if (err instanceof LaravelApiError) {
      return NextResponse.json(err.body, { status: err.status });
    }
    return NextResponse.json({ message: "Could not load the member's inventory." }, { status: 500 });
  }
}
