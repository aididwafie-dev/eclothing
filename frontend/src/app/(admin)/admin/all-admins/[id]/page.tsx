import { adminLoad } from "@/lib/adminPage";
import type { AdminAccountDetailResponse } from "@/lib/types";
import EditAdminForm from "./EditAdminForm";

export default async function EditAdminPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const data = await adminLoad<AdminAccountDetailResponse>(`/admin/admins/${id}`);

  return (
    <>
      <br />
      <div className="title">
        <i className="fa fa-user-circle" aria-hidden="true"></i> Edit Admin Details (Pegawai Pelulus Identity)
      </div>
      <hr />
      <div className="containerMain">
        <div className="content">
          <div className="title">
            <i className="fa fa-user-circle" aria-hidden="true"></i> Admin Identity:
          </div>
          <hr />
          <EditAdminForm data={data} />
        </div>
      </div>
    </>
  );
}
