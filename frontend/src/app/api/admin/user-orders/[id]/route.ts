import { adminProxy, adminProxyJson } from "@/lib/proxy";

type Params = { params: Promise<{ id: string }> };

export async function GET(request: Request, { params }: Params) {
  const { id } = await params;
  return adminProxy(`/admin/user-orders/${id}`, "Could not load the order.");
}

export async function PUT(request: Request, { params }: Params) {
  const { id } = await params;
  return adminProxyJson(request, `/admin/user-orders/${id}`, "PUT", "Could not save the order.");
}

export async function DELETE(request: Request, { params }: Params) {
  const { id } = await params;
  return adminProxy(`/admin/user-orders/${id}`, "Could not delete the order.", { method: "DELETE" });
}
