import BodyClass from "@/components/BodyClass";
import { laravelFetch } from "@/lib/laravel";
import RedirectAfter from "./RedirectAfter";

/** activation.blade.php -- the link in the registration email. */
export default async function VerifyAccountPage({ params }: { params: Promise<{ token: string }> }) {
  const { token } = await params;

  let activated = false;
  try {
    const data = await laravelFetch<{ success: boolean }>(`/auth/activate/${encodeURIComponent(token)}`, {
      method: "POST",
    });
    activated = data.success;
  } catch {
    activated = false;
  }

  return (
    <>
      <BodyClass name="auth" />
      <div className="banner">
        <div className="content content-log">
          {activated ? (
            <>
              <h2 className="text-center">
                Your account is activated successfully. You are being redirected to the login page.
              </h2>
              <RedirectAfter href="/login" ms={3000} />
            </>
          ) : (
            <h2 className="text-center">Sorry! The URL you clicked does not exist. Please try again.</h2>
          )}
        </div>
      </div>
    </>
  );
}
