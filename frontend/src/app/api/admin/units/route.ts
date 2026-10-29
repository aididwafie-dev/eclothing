import { adminProxy, adminProxyJson } from "@/lib/proxy";

export function GET() {
  return adminProxy("/admin/units", "Could not load units.");
}

export function POST(request: Request) {
  return adminProxyJson(request, "/admin/units", "POST", "Could not create the unit.");
}
