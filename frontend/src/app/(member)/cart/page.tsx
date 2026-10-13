import PagePlaceholder from "@/components/PagePlaceholder";

export default function CartPage() {
  return (
    <PagePlaceholder
      title="Order Uniform"
      replaces="resources/views/uniform_selection.blade.php"
    >
      <p className="text-sm text-black/60">
        Uniform/clothing catalog, size selection per item, cart, and checkout.
      </p>
    </PagePlaceholder>
  );
}
