import PagePlaceholder from "@/components/PagePlaceholder";

export default function PersonalDetailsPage() {
  return (
    <PagePlaceholder
      title="Personal Details"
      replaces="resources/views/personal_details.blade.php"
    >
      <p className="text-sm text-black/60">
        Two tabs to port: Personal Details (service info, address, next of kin) and Clothing
        Sizes (per-uniform size picker).
      </p>
    </PagePlaceholder>
  );
}
