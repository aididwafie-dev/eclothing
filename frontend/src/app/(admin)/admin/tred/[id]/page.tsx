import { adminLoad } from "@/lib/adminPage";
import type { AdminTredDetailResponse } from "@/lib/types";
import TredForm from "../TredForm";

export default async function EditTredPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const data = await adminLoad<AdminTredDetailResponse>(`/admin/treds/${id}`);

  return (
    <>
      <br />
      <div className="title">
        <i className="fa fa-pencil-square" aria-hidden="true"></i> Edit Tred
      </div>
      <hr />
      <div className="containerMain">
        <div className="content">
          <b>Edit tred details - {data.tred.value}</b>
          <hr />
          <TredForm tred={data.tred} />
        </div>
      </div>
    </>
  );
}
