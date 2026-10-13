import { redirect } from "next/navigation";

// The real app's `/` and `/home` both just serve the member login
// screen (HomeController::index renders home.blade.php for either) --
// there's no separate portal-chooser page.
export default function Home() {
  redirect("/login");
}
