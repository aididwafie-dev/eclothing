import PagePlaceholder from "@/components/PagePlaceholder";

export default async function AdminOrderDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  return (
    <PagePlaceholder
      title={`Order #${id}`}
      replaces="resources/views/admin/uniform_order_detail.blade.php"
    />
  );
}
