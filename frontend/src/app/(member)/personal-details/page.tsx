import { redirect } from "next/navigation";
import { LaravelApiError, laravelFetchAuthed } from "@/lib/laravel";
import type { ClothingSizesResponse, ProfileResponse } from "@/lib/types";
import PersonalDetailsForm from "./PersonalDetailsForm";

export default async function PersonalDetailsPage({
  searchParams,
}: {
  searchParams: Promise<{ tab?: string }>;
}) {
  const { tab } = await searchParams;

  let profile: ProfileResponse;
  let clothingSizes: ClothingSizesResponse;
  try {
    [profile, clothingSizes] = await Promise.all([
      laravelFetchAuthed<ProfileResponse>("/profile"),
      laravelFetchAuthed<ClothingSizesResponse>("/profile/clothing-sizes"),
    ]);
  } catch (err) {
    if (err instanceof LaravelApiError && err.status === 401) {
      redirect("/login");
    }
    throw err;
  }

  return (
    <PersonalDetailsForm
      initialTab={tab === "sizes" ? "sizes" : "details"}
      profile={profile}
      clothingSizes={clothingSizes}
    />
  );
}
