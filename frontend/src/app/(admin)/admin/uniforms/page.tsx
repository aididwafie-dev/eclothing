import { adminLoad } from "@/lib/adminPage";
import type { AdminUniform } from "@/lib/types";
import UniformsPageClient from "./UniformsPageClient";

export default async function AdminUniformsPage() {
  const data = await adminLoad<{ uniforms: AdminUniform[] }>("/admin/uniforms");

  return (
    <>
      <br />
      <div className="title">
        <i className="fa fa-shirtsinbulk" aria-hidden="true"></i> All Uniforms
      </div>
      <hr />
      <UniformsPageClient initialUniforms={data.uniforms} />
    </>
  );
}
