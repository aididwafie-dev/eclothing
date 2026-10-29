import { redirect } from "next/navigation";
import ReportMenu, { type ReportCard } from "@/components/ReportMenu";
import { getAdminToken } from "@/lib/laravel";

const CARDS: ReportCard[] = [
  {
    href: "/admin/orders-report/uniform-users",
    icon: "fa-users",
    iconClass: "report-icon-1",
    title: "Order Report With User Details For Each Uniform",
    subtitle: "Uniform → Users → Sizes",
    description: "Select a uniform and generate the report with user details and ordered sizes.",
  },
  {
    href: "/admin/orders-report/unit-users",
    icon: "fa-building",
    iconClass: "report-icon-2",
    title: "Order Report With User Details For Each Unit",
    subtitle: "Unit → Users → Orders",
    description: "Select a unit and view orders by users within that unit.",
  },
  {
    href: "/admin/orders-report/uniform",
    icon: "fa-shirtsinbulk",
    iconClass: "report-icon-3",
    title: "Order Report For Each Uniform",
    subtitle: "Uniform → Size summary",
    description: "Select a uniform and generate a size summary report.",
  },
  {
    href: "/admin/orders-report/unit",
    icon: "fa-sitemap",
    iconClass: "report-icon-4",
    title: "Order Report For Each Unit",
    subtitle: "Unit → Uniform totals",
    description: "Select a unit and view totals grouped by uniform type.",
  },
  {
    href: "/admin/orders-report/uniform-unit",
    icon: "fa-exchange",
    iconClass: "report-icon-5",
    title: "Order Report For Each Uniform and Unit",
    subtitle: "Uniform + Unit breakdown",
    description: "Select a uniform and a unit to generate a combined breakdown report.",
  },
  {
    href: "/admin/orders-report/cloth",
    icon: "fa-tags",
    iconClass: "report-icon-6",
    title: "Order Report For Each Cloth",
    subtitle: "Cloth tag → Size summary",
    description: "Select a uniform and cloth tag to generate a size summary for that cloth.",
  },
];

export default async function OrderReportsPage() {
  if (!(await getAdminToken())) {
    redirect("/admin/login");
  }

  return (
    <>
      <br />
      <div className="title">
        <i className="fa fa-file-excel-o" aria-hidden="true"></i> Order Report
      </div>
      <hr />
      <div className="containerMain">
        <div className="content">
          <ReportMenu cards={CARDS} />
        </div>
      </div>
    </>
  );
}
