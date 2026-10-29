import { adminProxy } from "@/lib/proxy";

export function GET() {
  return adminProxy("/admin/uniforms", "Could not load uniforms.");
}
