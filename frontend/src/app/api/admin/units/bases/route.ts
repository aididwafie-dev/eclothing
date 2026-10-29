import { adminProxy } from "@/lib/proxy";

export function GET() {
  return adminProxy("/admin/units/bases", "Could not load bases.");
}
