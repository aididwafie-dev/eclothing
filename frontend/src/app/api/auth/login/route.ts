import { NextRequest, NextResponse } from "next/server";
import { LaravelApiError, laravelFetch, setToken } from "@/lib/laravel";

export async function POST(request: NextRequest) {
  const { sId, password } = await request.json();

  if (!sId || !password) {
    return NextResponse.json({ message: "Service ID and password are required." }, { status: 422 });
  }

  try {
    const data = await laravelFetch<{ token: string; user: { id: number; s_id: string; email: string } }>(
      "/auth/login",
      {
        method: "POST",
        body: JSON.stringify({ s_id: sId, password }),
      },
    );

    await setToken(data.token);

    return NextResponse.json({ user: data.user });
  } catch (err) {
    if (err instanceof LaravelApiError) {
      const message = (err.body as { message?: string } | null)?.message ?? "Login failed.";
      return NextResponse.json({ message }, { status: err.status });
    }
    return NextResponse.json({ message: "Login failed." }, { status: 500 });
  }
}
