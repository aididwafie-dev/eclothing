import { redirect } from "next/navigation";
import { getToken } from "@/lib/laravel";
import ChangeEmailForm from "./ChangeEmailForm";

export default async function ChangeEmailPage() {
  if (!(await getToken())) {
    redirect("/login");
  }

  return (
    <>
      <br />
      <div className="title">
        <i className="fa fa-envelope-square" aria-hidden="true"></i> Change Email address
      </div>
      <hr />
      <div className="containerMain">
        <div className="content">
          <div className="title">
            <i className="fa fa-envelope" aria-hidden="true"></i> Set new email address
          </div>
          <hr />
          <ChangeEmailForm />
        </div>
      </div>
    </>
  );
}
