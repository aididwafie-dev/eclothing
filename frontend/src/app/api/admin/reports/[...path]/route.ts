import { NextRequest, NextResponse } from "next/server";
import { laravelFetchAdminAuthedRaw } from "@/lib/laravel";
import { adminProxy } from "@/lib/proxy";

/**
 * Every Order/User Reports read -- /api/admin/reports/<path>?<query> maps
 * straight onto Laravel's /admin/reports/<path>. excel/<type> is the
 * spreadsheet download and is passed through as binary.
 */
export async function GET(request: NextRequest, { params }: { params: Promise<{ path: string[] }> }) {
  const { path } = await params;
  const target = `/admin/reports/${path.map(encodeURIComponent).join("/")}${request.nextUrl.search}`;

  if (path[0] !== "excel") {
    return adminProxy(target, "Could not load the report.");
  }

  try {
    const res = await laravelFetchAdminAuthedRaw(target);
    if (!res.ok) {
      return NextResponse.json({ message: "Could not generate the Excel file." }, { status: res.status });
    }
    return new NextResponse(await res.arrayBuffer(), {
      headers: {
        "Content-Type":
          res.headers.get("Content-Type") ?? "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        "Content-Disposition": res.headers.get("Content-Disposition") ?? "attachment",
      },
    });
  } catch {
    return NextResponse.json({ message: "Could not generate the Excel file." }, { status: 500 });
  }
}
