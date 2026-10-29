import { redirect } from "next/navigation";
import { LaravelApiError, laravelFetchAdminAuthed } from "./laravel";

/** Server Component data load for an admin page -- a 401 means back to the admin login. */
export async function adminLoad<T>(path: string): Promise<T> {
  try {
    return await laravelFetchAdminAuthed<T>(path);
  } catch (err) {
    if (err instanceof LaravelApiError && err.status === 401) {
      redirect("/admin/login");
    }
    throw err;
  }
}
