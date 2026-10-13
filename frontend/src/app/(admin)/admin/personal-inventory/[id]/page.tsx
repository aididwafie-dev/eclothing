import PagePlaceholder from "@/components/PagePlaceholder";

export default async function AdminPersonalInventoryDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  return (
    <PagePlaceholder
      title={`Personal Inventory #${id}`}
      replaces="resources/views/admin/personal_inventory_detail.blade.php"
    />
  );
}
