import { redirect } from "next/navigation";
import { getAdminToken } from "@/lib/laravel";
import AddBaseForm from "./AddBaseForm";

export default async function AddBasePage() {
  if (!(await getAdminToken())) {
    redirect("/admin/login");
  }

  return (
    <>
      <div className="title">
        <i className="fa fa-plus-square" aria-hidden="true"></i> Add new base
      </div>
      <hr />
      <div className="containerMain">
        <div className="content">
          <i className="fa fa-plus-circle" aria-hidden="true"></i> <b>New base</b>
          <hr />
          <AddBaseForm />
        </div>
      </div>
    </>
  );
}
