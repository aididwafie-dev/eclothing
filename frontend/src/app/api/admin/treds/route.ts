import { adminProxy, adminProxyJson } from "@/lib/proxy";

export function GET() {
  return adminProxy("/admin/treds", "Could not load treds.");
}

export function POST(request: Request) {
  return adminProxyJson(request, "/admin/treds", "POST", "Could not create the tred.");
}
