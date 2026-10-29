import { adminProxyJson } from "@/lib/proxy";

export function POST(request: Request) {
  return adminProxyJson(request, "/admin/uniform-clothes", "POST", "Could not add the item.");
}
