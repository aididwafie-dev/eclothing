import { adminProxy, adminProxyJson } from "@/lib/proxy";

type Params = { params: Promise<{ id: string }> };

export async function GET(request: Request, { params }: Params) {
  const { id } = await params;
  return adminProxy(`/admin/treds/${id}`, "Could not load the tred.");
}

export async function PUT(request: Request, { params }: Params) {
  const { id } = await params;
  return adminProxyJson(request, `/admin/treds/${id}`, "PUT", "Could not save the tred.");
}

export async function DELETE(request: Request, { params }: Params) {
  const { id } = await params;
  return adminProxy(`/admin/treds/${id}`, "Could not delete the tred.", { method: "DELETE" });
}
