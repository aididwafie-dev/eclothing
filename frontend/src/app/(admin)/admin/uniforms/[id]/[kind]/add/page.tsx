import { notFound } from "next/navigation";
import { adminLoad } from "@/lib/adminPage";
import type { AdminUniform, UniformItemOptions } from "@/lib/types";
import UniformItemForm from "../../../UniformItemForm";

export default async function AddUniformItemPage({ params }: { params: Promise<{ id: string; kind: string }> }) {
  const { id, kind } = await params;
  if (kind !== "clothes" && kind !== "accessories") notFound();
  const accessory = kind === "accessories";

  const [{ uniform }, options] = await Promise.all([
    adminLoad<{ uniform: AdminUniform }>(`/admin/uniforms/${id}`),
    adminLoad<UniformItemOptions>("/admin/uniform-clothes/options"),
  ]);

  return (
    <>
      <br />
      <div className="title">
        {accessory ? (
          <>
            <i className="fa fa-shirtsinbulk" aria-hidden="true"></i> Add accessory for Uniform{" "}
          </>
        ) : (
          <>
            <i className="fa fa-plus-square" aria-hidden="true"></i> Add New Cloth to uniform{" "}
          </>
        )}
        <b>{uniform.uniformType}</b>
      </div>
      <hr />
      <div className="containerMain">
        <div className="content">
          <i className="fa fa-plus-circle" aria-hidden="true"></i>{" "}
          <b>{accessory ? "New accessory details:" : "New Cloth details:"}</b>
          <hr />
          <UniformItemForm uniformId={id} accessory={accessory} options={options} />
        </div>
      </div>
    </>
  );
}
