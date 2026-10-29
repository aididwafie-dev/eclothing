import { adminLoad } from "@/lib/adminPage";
import type { AdminUnitDetailResponse } from "@/lib/types";
import UnitForm from "../UnitForm";

export default async function EditUnitPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const data = await adminLoad<AdminUnitDetailResponse>(`/admin/units/${id}`);

  return (
    <>
      <br />
      <div className="title">
        <i className="fa fa-pencil-square" aria-hidden="true"></i> Edit Unit
      </div>
      <hr />
      <div className="containerMain">
        <div className="content">
          <b>Edit unit details - {data.unit.value}</b>
          <hr />
          <UnitForm unit={data.unit} bases={data.bases} />
        </div>
      </div>
    </>
  );
}
