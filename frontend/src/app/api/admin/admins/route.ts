import { adminProxy, adminProxyJson } from "@/lib/proxy";

export function GET() {
  return adminProxy("/admin/admins", "Could not load admins.");
}

export function POST(request: Request) {
  return adminProxyJson(request, "/admin/admins", "POST", "Could not add the admin.");
}
