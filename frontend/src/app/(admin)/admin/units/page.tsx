import { adminLoad } from "@/lib/adminPage";
import type { AdminUnitsResponse } from "@/lib/types";
import UnitsPageClient from "./UnitsPageClient";

export default async function AdminUnitsPage() {
  const data = await adminLoad<AdminUnitsResponse>("/admin/units");

  return (
    <>
      <br />
      <div className="title">
        <i className="fa fa-shirtsinbulk" aria-hidden="true"></i> All Units
      </div>
      <hr />
      <UnitsPageClient initialUnits={data.units} />
    </>
  );
}
