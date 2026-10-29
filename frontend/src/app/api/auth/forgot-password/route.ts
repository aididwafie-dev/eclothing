import { NextRequest, NextResponse } from "next/server";
import { LaravelApiError, laravelFetch } from "@/lib/laravel";

export async function POST(request: NextRequest) {
  const { email } = await request.json();

  try {
    const data = await laravelFetch("/auth/forgot-password", { method: "POST", body: JSON.stringify({ email }) });
    return NextResponse.json(data);
  } catch (err) {
    if (err instanceof LaravelApiError) {
      return NextResponse.json(err.body, { status: err.status });
    }
    return NextResponse.json({ message: "Email could not be sent right now. Please contact the administrator." }, { status: 500 });
  }
}
