import { redirect } from "next/navigation";
import { getToken } from "@/lib/laravel";
import ChangePasswordForm from "@/components/ChangePasswordForm";

export default async function ChangePasswordPage() {
  if (!(await getToken())) {
    redirect("/login");
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
          <ChangePasswordForm endpoint="/api/account/password" minLength={8} />
        </div>
      </div>
    </>
  );
}
