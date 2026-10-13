import Link from "next/link";

export default function Home() {
  return (
    <div className="flex flex-1 flex-col items-center justify-center bg-zinc-50 px-6">
      <div className="w-full max-w-md rounded-lg border border-black/10 bg-white p-8 text-center">
        <h1 className="text-xl font-semibold">RMAF e-Clothing</h1>
        <p className="mt-1 text-sm text-black/50">Next.js UI scaffold</p>
        <div className="mt-6 flex flex-col gap-3">
          <Link
            href="/login"
            className="rounded-md bg-black px-4 py-2 text-sm font-medium text-white hover:bg-black/80"
          >
            Member Portal
          </Link>
          <Link
            href="/admin/login"
            className="rounded-md border border-black/15 px-4 py-2 text-sm font-medium hover:bg-black/5"
          >
            Admin Portal
          </Link>
        </div>
      </div>
    </div>
  );
}
