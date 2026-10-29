import { redirect } from "next/navigation";
import { getAdminToken } from "@/lib/laravel";
import UniformForm from "../UniformForm";

export default async function AddUniformPage() {
  if (!(await getAdminToken())) {
    redirect("/admin/login");
  }

  return (
    <>
      <br />
      <div className="title">
        <i className="fa fa-plus-square" aria-hidden="true"></i> Add new uniform
      </div>
      <hr />
      <div className="containerMain">
        <div className="content">
          <i className="fa fa-plus-circle" aria-hidden="true"></i> <b>New uniform</b>
          <hr />
          <UniformForm />
        </div>
      </div>
    </>
  );
}
