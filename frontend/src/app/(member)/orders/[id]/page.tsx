import PagePlaceholder from "@/components/PagePlaceholder";

export default async function OrderDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  return (
    <PagePlaceholder title={`Order #${id}`} replaces="order detail row in uniform_ordered_byUser.blade.php" />
  );
}
