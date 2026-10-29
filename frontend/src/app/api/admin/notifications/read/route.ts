import { adminProxyJson } from "@/lib/proxy";

export function POST(request: Request) {
  return adminProxyJson(request, "/admin/notifications/read", "POST", "Could not update notifications.");
}
