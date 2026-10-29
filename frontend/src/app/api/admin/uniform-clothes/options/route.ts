import { adminProxy } from "@/lib/proxy";

export function GET() {
  return adminProxy("/admin/uniform-clothes/options", "Could not load the options.");
}
