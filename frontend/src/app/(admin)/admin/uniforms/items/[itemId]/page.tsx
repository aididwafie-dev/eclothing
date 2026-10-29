import { adminLoad } from "@/lib/adminPage";
import type { AdminUniformItemDetailResponse } from "@/lib/types";
import UniformItemForm from "../../UniformItemForm";

export default async function EditUniformItemPage({ params }: { params: Promise<{ itemId: string }> }) {
  const { itemId } = await params;
  const data = await adminLoad<AdminUniformItemDetailResponse>(`/admin/uniform-clothes/${itemId}`);
  const { item } = data;

  return (
    <>
      <br />
      <div className="title">
        <i className="fa fa-pencil-square" aria-hidden="true"></i>{" "}
        {item.accessory ? (
          <>
            Edit accessory <b>{item.clothesType}</b> for Uniform
          </>
        ) : (
          "Edit Clothes details of Uniform"
        )}{" "}
        <b>{data.uniform?.uniformType}</b>
      </div>
      <hr />
      <div className="containerMain">
        <div className="content">
          <b>{item.accessory ? "Edit accessory details" : "Edit cloth details"}</b>
          <hr />
          <UniformItemForm uniformId={item.uniformId} accessory={item.accessory} item={item} options={data} />
        </div>
      </div>
    </>
  );
}
