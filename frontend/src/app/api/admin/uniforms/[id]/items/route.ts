import { NextRequest } from "next/server";
import { adminProxy } from "@/lib/proxy";

export async function GET(request: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const kind = request.nextUrl.searchParams.get("kind") === "accessories" ? "accessories" : "clothes";
  return adminProxy(`/admin/uniforms/${id}/items?kind=${kind}`, "Could not load the items.");
}
