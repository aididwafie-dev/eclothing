import { NextRequest, NextResponse } from "next/server";
import { LaravelApiError, laravelFetchAuthed } from "@/lib/laravel";

export async function POST(request: NextRequest) {
  const body = await request.json();

  try {
    const data = await laravelFetchAuthed("/cart/load-from-order", {
      method: "POST",
      body: JSON.stringify(body),
    });
    return NextResponse.json(data);
  } catch (err) {
    if (err instanceof LaravelApiError) {
      return NextResponse.json(err.body, { status: err.status });
    }
    return NextResponse.json({ message: "Could not load this order into the cart." }, { status: 500 });
  }
}
