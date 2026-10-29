import { adminProxy, adminProxyJson } from "@/lib/proxy";

type Params = { params: Promise<{ id: string }> };

export async function GET(request: Request, { params }: Params) {
  const { id } = await params;
  return adminProxy(`/admin/units/${id}`, "Could not load the unit.");
}

export async function PUT(request: Request, { params }: Params) {
  const { id } = await params;
  return adminProxyJson(request, `/admin/units/${id}`, "PUT", "Could not save the unit.");
}

export async function DELETE(request: Request, { params }: Params) {
  const { id } = await params;
  return adminProxy(`/admin/units/${id}`, "Could not delete the unit.", { method: "DELETE" });
}
