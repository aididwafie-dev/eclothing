import { redirect } from "next/navigation";
import ReportMenu, { type ReportCard } from "@/components/ReportMenu";
import { getAdminToken } from "@/lib/laravel";

const CARDS: ReportCard[] = [
  {
    href: "/admin/users-report/units",
    icon: "fa-sitemap",
    iconClass: "report-icon-2",
    title: "Users Report By Units",
    subtitle: "List users grouped by unit",
    description: "View users grouped by their unit.",
  },
  {
    href: "/admin/users-report/uniform-tags",
    icon: "fa-tags",
    iconClass: "report-icon-6",
    title: "Users Report of Uniform Tags",
    subtitle: "Uniform tag usage & counts",
    description: "View report by uniform tags (uniform name / tag usage).",
  },
  {
    href: "/admin/users-report/without-orders",
    icon: "fa-user-times",
    iconClass: "report-icon-4",
    title: "Users Report without Orders",
    subtitle: "Users with no order record",
    description: "View users who do not have any orders.",
  },
  {
    href: "/admin/users-report/strength",
    icon: "fa-bar-chart",
    iconClass: "report-icon-1",
    title: "Users Report for Strength by Units",
    subtitle: "Strength summary by unit",
    description: "View user strength summary grouped by unit.",
  },
];

export default async function UserReportsPage() {
  if (!(await getAdminToken())) {
    redirect("/admin/login");
  }

  return (
    <>
      <br />
      <div className="title">
        <i className="fa fa-file-excel-o" aria-hidden="true"></i> User Reports
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
