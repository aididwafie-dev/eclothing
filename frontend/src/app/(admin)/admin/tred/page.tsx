import { adminLoad } from "@/lib/adminPage";
import type { AdminTredsResponse } from "@/lib/types";
import TredPageClient from "./TredPageClient";

export default async function AdminTredPage() {
  const data = await adminLoad<AdminTredsResponse>("/admin/treds");

  return (
    <>
      <br />
      <div className="title">
        <i className="fa fa-shirtsinbulk" aria-hidden="true"></i> All Treds
      </div>
      <hr />
      <TredPageClient initialTreds={data.treds} />
    </>
  );
}
