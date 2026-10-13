import { NextRequest, NextResponse } from "next/server";
import { LaravelApiError, laravelFetch, setAdminToken } from "@/lib/laravel";

export async function POST(request: NextRequest) {
  const { username, password } = await request.json();

  if (!username || !password) {
    return NextResponse.json({ message: "Username and password are required." }, { status: 422 });
  }

  try {
    const data = await laravelFetch<{
      token: string;
      admin: { id: number; name: string; username: string; role: string };
    }>("/admin/auth/login", {
      method: "POST",
      body: JSON.stringify({ username, password }),
    });

    await setAdminToken(data.token);

    return NextResponse.json({ admin: data.admin });
  } catch (err) {
    if (err instanceof LaravelApiError) {
      const message = (err.body as { message?: string } | null)?.message ?? "Login failed.";
      return NextResponse.json({ message }, { status: err.status });
    }
    return NextResponse.json({ message: "Login failed." }, { status: 500 });
  }
}
