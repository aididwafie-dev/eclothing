import Link from "next/link";
import { notFound } from "next/navigation";
import { adminLoad } from "@/lib/adminPage";
import { BASE_PATH } from "@/lib/basePath";
import type { DropdownOption } from "@/lib/types";
import ReportFilterForm, { type FilterField } from "../ReportFilterForm";

type SizeRow = { clothes: string; size: string; count: number };

/** Which select*_report.blade.php form and result view each report maps to. */
const REPORTS: Record<string, { heading: string; fields: FilterField[]; unitLabel?: string }> = {
  "uniform-users": { heading: "Generate Report With User Details: ", fields: ["uniformId"] },
  "unit-users": { heading: "Generate Report With User Details: ", fields: ["unitId"], unitLabel: "Select Unit Type" },
  uniform: { heading: "Generate Report Uniform wise: ", fields: ["uniformId"] },
  unit: { heading: "Generate Report Unit wise: ", fields: ["unitId"] },
  "uniform-unit": { heading: "Generate Report Uniform wise: ", fields: ["uniformId", "unitId"] },
  cloth: { heading: "Generate Report Cloth wise: ", fields: ["uniformId", "slug"] },
};

function excelHref(type: string, query: Record<string, string>) {
  return `${BASE_PATH}/api/admin/reports/excel/${type}?${new URLSearchParams(query)}`;
}

function DownloadExcel({ href }: { href: string }) {
  return (
    <a href={href}>
      <button type="button" className="btn">
        <i className="fa fa-download" aria-hidden="true"></i> Download Excel File
      </button>
    </a>
  );
}

export default async function OrderReportPage({
  params,
  searchParams,
}: {
  params: Promise<{ report: string }>;
  searchParams: Promise<Record<string, string | undefined>>;
}) {
  const { report } = await params;
  const config = REPORTS[report];
  if (!config) notFound();

  const query = await searchParams;
  const selected = Object.fromEntries(config.fields.map((f) => [f, query[f] ?? ""])) as Record<FilterField, string>;
  const ready = config.fields.every((f) => selected[f]);
  const basePath = `/admin/orders-report/${report}`;

  const title = (
    <>
      <br />
      <div className="title">
        <i className="fa fa-file-excel-o" aria-hidden="true"></i> Order Report
      </div>
      <hr />
    </>
  );

  if (!ready) {
    const options = await adminLoad<{ uniforms: DropdownOption[]; units: DropdownOption[] }>("/admin/reports/options");
    return (
      <>
        {title}
        <div className="containerMain">
          <div className="content">
            <Link className="back-btn" href="/admin/orders-report">
              <i className="fa fa-arrow-left" aria-hidden="true"></i> Back
            </Link>
            <br />
            <ReportFilterForm
              heading={config.heading}
              fields={config.fields}
              uniforms={options.uniforms}
              units={options.units}
              unitLabel={config.unitLabel}
              basePath={basePath}
            />
          </div>
        </div>
      </>
    );
  }

  const data = await adminLoad<Record<string, unknown>>(
    `/admin/reports/orders/${report}?${new URLSearchParams(selected)}`,
  );
  const back = (
    <>
      <Link className="back-btn" href={basePath}>
        <i className="fa fa-arrow-left" aria-hidden="true"></i> Back
      </Link>
      <br />
    </>
  );

  if (report === "uniform-users") {
    const d = data as { uniform: string; clothes: string[]; rows: { sId: string; rank: string; name: string; unit: string; sizes: string[] }[] };
    return (
      <>
        {title}
        <div className="containerMain other">
          <div className="content full clearfix">
            {back}
            <i className="fa fa-line-chart" aria-hidden="true"></i> <b>Report With User Details of Uniform Type: {d.uniform}</b>
            <br />
            <br />
            {d.rows.length === 0 ? (
              <b>This Uniform is not ordered by anyone.</b>
            ) : (
              <>
                <DownloadExcel href={excelHref("uniform-users", { id: selected.uniformId })} />
                <br />
                <br />
                <div className="table-responsive">
                  <table className="table table-bordered">
                    <thead>
                      <tr>
                        <th>#</th>
                        <th>SERVICE ID</th>
                        <th>RANK</th>
                        <th>NAME</th>
                        <th>UNIT</th>
                        {d.clothes.map((c, i) => (
                          <th key={i}>{c}</th>
                        ))}
                      </tr>
                    </thead>
                    <tbody>
                      {d.rows.map((r, i) => (
                        <tr key={i}>
                          <td>{i + 1}</td>
                          <td>{r.sId}</td>
                          <td>{r.rank}</td>
                          <td>{r.name}</td>
                          <td>{r.unit}</td>
                          {r.sizes.map((s, j) => (
                            <td key={j}>{s}</td>
                          ))}
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </>
            )}
          </div>
        </div>
      </>
    );
  }

  if (report === "unit-users") {
    const d = data as { unit: string; uniforms: string[]; rows: { sId: string; rank: string; name: string; ordered: boolean[] }[] };
    return (
      <>
        {title}
        <div className="containerMain other">
          <div className="content full clearfix">
            {back}
            <i className="fa fa-line-chart" aria-hidden="true"></i> <b>Report With User Details of Unit: {d.unit}</b>
            <br />
            <br />
            {d.rows.length === 0 ? (
              <b>This Uniform is not ordered by anyone.</b>
            ) : (
              <>
                <div className="table-responsive">
                  <table className="table table-bordered">
                    <thead>
                      <tr>
                        <th>#</th>
                        <th>SERVICE ID</th>
                        <th>RANK</th>
                        <th>NAME</th>
                        {d.uniforms.map((u, i) => (
                          <th key={i}>{u}</th>
                        ))}
                      </tr>
                    </thead>
                    <tbody>
                      {d.rows.map((r, i) => (
                        <tr key={i}>
                          <td>{i + 1}</td>
                          <td>{r.sId}</td>
                          <td>{r.rank}</td>
                          <td>{r.name}</td>
                          {r.ordered.map((o, j) => (
                            <td key={j}>{o ? "✓" : ""}</td>
                          ))}
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
                <br />
                <DownloadExcel href={excelHref("unit-users", { id: selected.unitId })} />
              </>
            )}
          </div>
        </div>
      </>
    );
  }

  if (report === "unit") {
    const d = data as { unit: string; rows: { uniform: string; count: number }[]; total: number };
    return (
      <>
        {title}
        <div className="containerMain">
          <div className="content table_center">
            {back}
            {d.total === 0 ? (
              <b>No uniforms ordered.</b>
            ) : (
              <>
                <i className="fa fa-line-chart" aria-hidden="true"></i> <b>Report of Uniform orders for unit {d.unit}</b>
                <hr />
                <table className="table">
                  <tbody>
                    <tr>
                      <th>Uniform type</th>
                      <th>Quantity</th>
                    </tr>
                    {d.rows.map((r, i) => (
                      <tr key={i}>
                        <td>{r.uniform}</td>
                        <td>{r.count}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
                <hr />
                <b>
                  Total orders for unit {d.unit} =&gt; {d.total}
                </b>
              </>
            )}
          </div>
        </div>
      </>
    );
  }

  if (report === "cloth") {
    const d = data as { uniform: string; uniformType: string; clothes: string | null; rows: { size: string; count: number }[]; total: number };
    return (
      <>
        {title}
        <div className="containerMain">
          <div className="content table_center">
            {back}
            {d.rows.length === 0 ? (
              <b>Any Cloth of Uniform type {d.uniformType} is not ordered.</b>
            ) : (
              <>
                <i className="fa fa-line-chart" aria-hidden="true"></i>{" "}
                <b>
                  Report of {d.clothes} of Uniform type {d.uniform}
                </b>
                <hr />
                <DownloadExcel href={excelHref("cloth", { id: selected.uniformId, slug: selected.slug })} />
                <br />
                <br />
                <table className="table">
                  <tbody>
                    <tr>
                      <th>Clothes Size</th>
                      <th>Quantity</th>
                    </tr>
                    {d.rows.map((r, i) => (
                      <tr key={i}>
                        <td>{r.size}</td>
                        <td>{r.count}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
                <hr />
                <b>
                  Total order of <i>{d.clothes}</i> of <i>Uniform {d.uniformType}</i> =&gt; {d.total}
                </b>
              </>
            )}
          </div>
        </div>
      </>
    );
  }

  // "uniform" and "uniform-unit" share uniformwise_orderReport's size table.
  const d = data as { uniform: string; uniformType: string; unit?: string; clothes: number; totalOrders: number; rows: SizeRow[] };
  const subject = d.unit ? `Uniform ${d.uniformType} & Unit type ${d.unit}` : `Uniform ${d.uniform}`;
  return (
    <>
      {title}
      <div className="containerMain">
        <div className="content table_center">
          {back}
          <i className="fa fa-line-chart" aria-hidden="true"></i>{" "}
          <b>
            Report of Uniform Type: {d.unit ? `${d.uniformType} & Unit type: ${d.unit}` : d.uniform}
          </b>
          <br />
          <br />
          {d.rows.length === 0 ? (
            <b>This Uniform is not ordered by anyone.</b>
          ) : (
            <>
              <table className="table">
                <tbody>
                  <tr>
                    <th>Clothes Type</th>
                    <th>Clothes Size</th>
                    <th>Quantity</th>
                  </tr>
                  {d.rows.map((r, i) => (
                    <tr key={i}>
                      <td>{r.clothes}</td>
                      <td>{r.size}</td>
                      <td>{r.count}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
              <hr />
              <b>
                Total clothes for <i>{subject}</i> =&gt; {d.clothes}
              </b>
              <br />
              <b>
                Total order of <i>{subject}</i> =&gt; {d.totalOrders}
              </b>
              {!d.unit && (
                <>
                  <br />
                  <br />
                  <DownloadExcel href={excelHref("uniform", { id: selected.uniformId })} />
                </>
              )}
            </>
          )}
        </div>
      </div>
    </>
  );
}
