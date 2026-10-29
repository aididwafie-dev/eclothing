import { adminLoad } from "@/lib/adminPage";
import type { AdminUniform } from "@/lib/types";
import UniformForm from "../../UniformForm";

export default async function EditUniformPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const { uniform } = await adminLoad<{ uniform: AdminUniform }>(`/admin/uniforms/${id}`);

  return (
    <>
      <br />
      <div className="title">
        <i className="fa fa-pencil-square" aria-hidden="true"></i> Edit Uniform type
      </div>
      <hr />
      <div className="containerMain">
        <div className="content">
          <UniformForm uniform={uniform} />
        </div>
      </div>
    </>
  );
}
