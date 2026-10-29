import { adminProxy } from "@/lib/proxy";

export async function POST(request: Request, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  return adminProxy(`/admin/admins/${id}/toggle-status`, "Could not change the status.", { method: "POST" });
}
