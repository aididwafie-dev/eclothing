import { NextRequest, NextResponse } from "next/server";
import { LaravelApiError, laravelFetchAuthedRaw } from "@/lib/laravel";

export async function GET(request: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;

  try {
    const res = await laravelFetchAuthedRaw(`/orders/${id}/kew-ps8`);
    if (!res.ok) {
      return NextResponse.json({ message: "Could not generate this report." }, { status: res.status });
    }

    const body = await res.arrayBuffer();
    return new NextResponse(body, {
      headers: {
        "Content-Type": res.headers.get("Content-Type") ?? "application/pdf",
        "Content-Disposition": res.headers.get("Content-Disposition") ?? "attachment",
      },
    });
  } catch (err) {
    if (err instanceof LaravelApiError) {
      return NextResponse.json(err.body, { status: err.status });
    }
    return NextResponse.json({ message: "Could not generate this report." }, { status: 500 });
  }
}
