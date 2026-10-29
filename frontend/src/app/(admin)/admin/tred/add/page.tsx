import { redirect } from "next/navigation";
import { getAdminToken } from "@/lib/laravel";
import TredForm from "../TredForm";

export default async function AddTredPage() {
  if (!(await getAdminToken())) {
    redirect("/admin/login");
  }

  return (
    <>
      <br />
      <div className="title">
        <i className="fa fa-plus-square" aria-hidden="true"></i> Add new tred
      </div>
      <hr />
      <div className="containerMain">
        <div className="content">
          <i className="fa fa-plus-circle" aria-hidden="true"></i> <b>New tred</b>
          <hr />
          <TredForm />
        </div>
      </div>
    </>
  );
}
