import { NextRequest, NextResponse } from "next/server";
import { LaravelApiError, laravelFetch } from "@/lib/laravel";

export async function POST(request: NextRequest) {
  const { email, sId, password, confirmPassword } = await request.json();

  try {
    const data = await laravelFetch("/auth/register", {
      method: "POST",
      body: JSON.stringify({ email, s_id: sId, password, confirm_password: confirmPassword }),
    });
    return NextResponse.json(data);
  } catch (err) {
    if (err instanceof LaravelApiError) {
      return NextResponse.json(err.body, { status: err.status });
    }
    return NextResponse.json({ message: "Could not register right now." }, { status: 500 });
  }
}
