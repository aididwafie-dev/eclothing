import PagePlaceholder from "@/components/PagePlaceholder";

export default function AdminOrdersPage() {
  return (
    <PagePlaceholder
      title="Uniform Orders"
      replaces="resources/views/admin/uniform_orders_list.blade.php"
    >
      <p className="text-sm text-black/60">
        Unit/base dropdown filter (PU Subang-style scoping), status filter, search.
      </p>
    </PagePlaceholder>
  );
}
