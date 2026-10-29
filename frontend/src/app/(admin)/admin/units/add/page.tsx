import { adminLoad } from "@/lib/adminPage";
import type { DropdownOption } from "@/lib/types";
import UnitForm from "../UnitForm";

export default async function AddUnitPage() {
  const bases = await adminLoad<DropdownOption[]>("/admin/units/bases");

  return (
    <>
      <br />
      <div className="title">
        <i className="fa fa-plus-square" aria-hidden="true"></i> Add new unit
      </div>
      <hr />
      <div className="containerMain">
        <div className="content">
          <i className="fa fa-plus-circle" aria-hidden="true"></i> <b>New unit name</b>
          <hr />
          <UnitForm bases={bases} />
        </div>
      </div>
    </>
  );
}
