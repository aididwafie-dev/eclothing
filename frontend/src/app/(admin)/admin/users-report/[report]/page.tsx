import Link from "next/link";
import { notFound } from "next/navigation";
import SearchableTable from "@/components/SearchableTable";
import { adminLoad } from "@/lib/adminPage";
import { BASE_PATH } from "@/lib/basePath";
import type { DropdownOption } from "@/lib/types";
import UnitFilterForm from "../UnitFilterForm";

type StrengthRow = { unit: string; manOfficer: number; womanOfficer: number; manOther: number; womanOther: number };

const TITLES: Record<string, string> = {
  units: "User Reports",
  "uniform-tags": "Users Uniform Tag",
  "without-orders": "Users without Orders Reports",
  strength: "Users strength by Unit",
};

function DownloadExcel({ type }: { type: string }) {
  return (
    <a href={`${BASE_PATH}/api/admin/reports/excel/${type}`}>
      <button type="button" className="btn">
        <i className="fa fa-download" aria-hidden="true"></i> Download Excel File
      </button>
    </a>
  );
}

export default async function UserReportPage({
  params,
  searchParams,
}: {
  params: Promise<{ report: string }>;
  searchParams: Promise<{ unitId?: string; officerRecruit?: string }>;
}) {
  const { report } = await params;
  if (!TITLES[report]) notFound();
  const { unitId = "", officerRecruit = "" } = await searchParams;

  const header = (
    <>
      <br />
      <div className="title">
        <i className="fa fa-file-excel-o" aria-hidden="true"></i> {TITLES[report]}
      </div>
      <hr />
    </>
  );
  const back = (
    <>
      <Link className="back-btn" href="/admin/users-report">
        <i className="fa fa-arrow-left" aria-hidden="true"></i> Back
      </Link>
      <br />
    </>
  );

  if (report === "strength") {
    const { rows } = await adminLoad<{ rows: StrengthRow[] }>("/admin/reports/users/strength");
    const overall = rows.reduce((sum, r) => sum + r.manOfficer + r.womanOfficer + r.manOther + r.womanOther, 0);
    return (
      <>
        {header}
        <div className="containerMain">
          <div className="content table_center">
            {back}
            {rows.length === 0 ? (
              <b>No users found.</b>
            ) : (
              <>
                <i className="fa fa-line-chart" aria-hidden="true"></i> <b>Report of Users strength</b>
                <hr />
                <DownloadExcel type="users-strength" />
                <br />
                <br />
                <table className="table">
                  <tbody>
                    <tr>
                      <th rowSpan={2}>Unit</th>
                      <th colSpan={2} className="text-center">
                        Officer
                      </th>
                      <th rowSpan={2}>Total</th>
                      <th colSpan={2} className="text-center">
                        Other rank
                      </th>
                      <th rowSpan={2}>Total</th>
                      <th rowSpan={2}>Overall</th>
                    </tr>
                    <tr>
                      <th>Men</th>
                      <th>Women</th>
                      <th>Men</th>
                      <th>Women</th>
                    </tr>
                    {rows.map((r, i) => (
                      <tr key={i}>
                        <td>{r.unit}</td>
                        <td>{r.manOfficer}</td>
                        <td>{r.womanOfficer}</td>
                        <td>{r.manOfficer + r.womanOfficer}</td>
                        <td>{r.manOther}</td>
                        <td>{r.womanOther}</td>
                        <td>{r.manOther + r.womanOther}</td>
                        <td>{r.manOfficer + r.womanOfficer + r.manOther + r.womanOther}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
                <hr />
                <b>Total users {overall}</b>
              </>
            )}
          </div>
        </div>
      </>
    );
  }

  if (report === "units") {
    const { rows } = await adminLoad<{ rows: { unit: string; withOrder: number; withoutOrder: number }[] }>(
      "/admin/reports/users/units",
    );
    // DataTables opened this sorted on "Users with order", highest first.
    const sorted = [...rows].sort((a, b) => b.withOrder - a.withOrder);
    return (
      <>
        {header}
        <div className="containerMain">
          <div className="content full table-responsive">
            {back}
            {rows.length === 0 ? (
              <b>No users found.</b>
            ) : (
              <>
                <i className="fa fa-line-chart" aria-hidden="true"></i> <b>Report of Users in each unit</b>
                <hr />
                <DownloadExcel type="users-units" />
                <br />
                <br />
                <SearchableTable
                  numbered
                  columns={["Unit name", "Users with order", "Users without order"]}
                  rows={sorted.map((r) => [r.unit, r.withOrder, r.withoutOrder])}
                />
                <hr />
              </>
            )}
          </div>
        </div>
      </>
    );
  }

  const query = new URLSearchParams();
  if (unitId) query.set("unitId", unitId);
  if (report === "uniform-tags" && officerRecruit) query.set("officerRecruit", officerRecruit);

  const [{ rows }, { units }] = await Promise.all([
    adminLoad<{ rows: Record<string, string | null>[] }>(`/admin/reports/users/${report}?${query}`),
    adminLoad<{ units: DropdownOption[] }>("/admin/reports/options"),
  ]);

  const isTags = report === "uniform-tags";
  return (
    <>
      {header}
      <div className="containerMain">
        <div className="content full table-responsive">
          {back}
          {rows.length === 0 && !unitId && !officerRecruit ? (
            <b>No users found.</b>
          ) : (
            <>
              <i className="fa fa-line-chart" aria-hidden="true"></i>{" "}
              <b>{isTags ? "Report of Users Uniform Tags" : "Report of Users without orders"}</b>
              <hr />
              <UnitFilterForm
                basePath={`/admin/users-report/${report}`}
                units={units}
                unitId={unitId}
                officerRecruit={isTags ? officerRecruit : undefined}
              />
              <div className="clearfix"></div>
              <DownloadExcel type={isTags ? "users-uniform-tags" : "users-without-orders"} />{" "}
              {!isTags && (
                <Link href="/admin/announcements">
                  <button type="button" className="btn">
                    <i className="fa fa-envelope" aria-hidden="true"></i> Send announcement
                  </button>
                </Link>
              )}
              <br />
              <br />
              {isTags ? (
                <SearchableTable
                  columns={["Service ID", "Rank", "Name", "Unit", "Uniform Tag"]}
                  rows={rows.map((r) => [r.sId, r.rank, r.name, r.unit, r.nameTag])}
                />
              ) : (
                <SearchableTable
                  columns={["Service ID", "Name", "Telephone", "Unit", "Created", "Updated"]}
                  rows={rows.map((r) => [r.sId, r.name, r.telephone, r.unit, r.createdAt, r.updatedAt])}
                />
              )}
              <hr />
              <b>Total users {rows.length}</b>
            </>
          )}
        </div>
      </div>
    </>
  );
}
