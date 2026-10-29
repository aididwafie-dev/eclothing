import { redirect } from "next/navigation";
import { getAdminToken } from "@/lib/laravel";
import NewAdminClient from "./NewAdminClient";

export default async function NewAdminPage() {
  if (!(await getAdminToken())) {
    redirect("/admin/login");
  }

  return <NewAdminClient />;
}
