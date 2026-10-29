import { NextRequest, NextResponse } from "next/server";
import { LaravelApiError, laravelFetch } from "@/lib/laravel";

export async function GET(request: NextRequest) {
  const params = request.nextUrl.searchParams;
  const query = new URLSearchParams({
    field: params.get("field") ?? "",
    value: params.get("value") ?? "",
  });

  try {
    const data = await laravelFetch(`/auth/check-availability?${query}`);
    return NextResponse.json(data);
  } catch (err) {
    if (err instanceof LaravelApiError) {
      return NextResponse.json(err.body, { status: err.status });
    }
    return NextResponse.json({ available: true, message: "" }, { status: 500 });
  }
}
