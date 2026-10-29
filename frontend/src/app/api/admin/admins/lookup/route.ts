import { NextRequest } from "next/server";
import { adminProxy } from "@/lib/proxy";

export function GET(request: NextRequest) {
  const query = new URLSearchParams({ sId: request.nextUrl.searchParams.get("sId") ?? "" });
  return adminProxy(`/admin/admins/lookup?${query}`, "Could not look up the service ID.");
}
