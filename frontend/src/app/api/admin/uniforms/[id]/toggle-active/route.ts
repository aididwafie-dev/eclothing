import { adminProxy } from "@/lib/proxy";

export async function POST(request: Request, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  return adminProxy(`/admin/uniforms/${id}/toggle-active`, "Could not change the uniform.", { method: "POST" });
}
