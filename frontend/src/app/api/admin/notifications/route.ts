import { NextRequest } from "next/server";
import { adminProxy } from "@/lib/proxy";

export function GET(request: NextRequest) {
  const limit = request.nextUrl.searchParams.get("limit") ?? "20";
  return adminProxy(`/admin/notifications?limit=${encodeURIComponent(limit)}`, "Could not load notifications.");
}
