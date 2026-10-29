import { adminProxy, adminProxyJson } from "@/lib/proxy";

type Params = { params: Promise<{ id: string }> };

export async function GET(request: Request, { params }: Params) {
  const { id } = await params;
  return adminProxy(`/admin/admins/${id}`, "Could not load the admin.");
}

export async function PUT(request: Request, { params }: Params) {
  const { id } = await params;
  return adminProxyJson(request, `/admin/admins/${id}`, "PUT", "Could not save the admin.");
}

export async function DELETE(request: Request, { params }: Params) {
  const { id } = await params;
  return adminProxy(`/admin/admins/${id}`, "Could not delete the admin.", { method: "DELETE" });
}
