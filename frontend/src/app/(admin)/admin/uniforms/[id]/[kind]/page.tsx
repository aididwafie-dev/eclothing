import { notFound } from "next/navigation";
import { adminLoad } from "@/lib/adminPage";
import type { AdminUniformItemsResponse } from "@/lib/types";
import UniformItemsList from "../../UniformItemsList";

export default async function UniformItemsPage({ params }: { params: Promise<{ id: string; kind: string }> }) {
  const { id, kind } = await params;
  if (kind !== "clothes" && kind !== "accessories") notFound();

  const data = await adminLoad<AdminUniformItemsResponse>(`/admin/uniforms/${id}/items?kind=${kind}`);

  return (
    <>
      <br />
      <div className="title">
        <i className="fa fa-shirtsinbulk" aria-hidden="true"></i>{" "}
        {kind === "clothes" ? "All Clothes of Uniform" : "All accessories of Uniform"} <b>{data.uniform.uniformType}</b>
      </div>
      <hr />
      <UniformItemsList uniformId={id} kind={kind} initialItems={data.items} />
    </>
  );
}
