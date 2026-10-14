import { NextResponse } from "next/server";
import { LaravelApiError, laravelFetchAuthed } from "@/lib/laravel";

export async function POST() {
  try {
    const data = await laravelFetchAuthed("/orders/email-details", { method: "POST" });
    return NextResponse.json(data);
  } catch (err) {
    if (err instanceof LaravelApiError) {
      return NextResponse.json(err.body, { status: err.status });
    }
    return NextResponse.json({ message: "Could not email your order details." }, { status: 500 });
  }
}
