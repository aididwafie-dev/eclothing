import { adminProxy, adminProxyJson } from "@/lib/proxy";

type Params = { params: Promise<{ id: string }> };

export async function GET(request: Request, { params }: Params) {
  const { id } = await params;
  return adminProxy(`/admin/uniform-clothes/${id}`, "Could not load the item.");
}

export async function PUT(request: Request, { params }: Params) {
  const { id } = await params;
  return adminProxyJson(request, `/admin/uniform-clothes/${id}`, "PUT", "Could not save the item.");
}

export async function DELETE(request: Request, { params }: Params) {
  const { id } = await params;
  return adminProxy(`/admin/uniform-clothes/${id}`, "Could not delete the item.", { method: "DELETE" });
}
