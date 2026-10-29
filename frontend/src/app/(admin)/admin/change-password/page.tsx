import { redirect } from "next/navigation";
import { getAdminToken } from "@/lib/laravel";
import ChangePasswordForm from "@/components/ChangePasswordForm";

export default async function AdminChangePasswordPage() {
  if (!(await getAdminToken())) {
    redirect("/admin/login");
  }

  return (
    <>
      <br />
      <div className="title">
        <i className="fa fa-lock" aria-hidden="true"></i> Change password
      </div>
      <hr />
      <div className="containerMain">
        <div className="content">
          <div className="title">
            <i className="fa fa-key" aria-hidden="true"></i> Set new password:
          </div>
          <hr />
          <ChangePasswordForm endpoint="/api/admin/auth/password" />
        </div>
      </div>
    </>
  );
}
