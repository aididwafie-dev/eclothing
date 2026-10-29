import { adminLoad } from "@/lib/adminPage";
import type { AdminAccountRow } from "@/lib/types";
import AllAdminsClient from "./AllAdminsClient";

export default async function AllAdminsPage() {
  const data = await adminLoad<{ admins: AdminAccountRow[] }>("/admin/admins");

  return (
    <>
      <br />
      <div className="title">
        <i className="fa fa-user-circle" aria-hidden="true"></i> All Admins
      </div>
      <hr />
      <AllAdminsClient initialAdmins={data.admins} />
    </>
  );
}
