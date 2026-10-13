import Link from "next/link";

const links = [
  { href: "/admin/orders", label: "Uniform Orders" },
  { href: "/admin/all-users", label: "Members" },
  { href: "/admin/personal-inventory", label: "Personal Inventory" },
  { href: "/admin/bases", label: "Bases" },
  { href: "/admin/announcements", label: "Announcements" },
  { href: "/admin/system-settings", label: "System Settings" },
];

export default function AdminNav() {
  return (
    <header className="border-b border-black/10 bg-slate-900 text-white">
      <nav className="mx-auto flex max-w-6xl items-center gap-6 px-4 py-3 text-sm">
        <Link href="/admin/orders" className="font-semibold">
          e-Clothing Admin
        </Link>
        {links.map((link) => (
          <Link key={link.href} href={link.href} className="text-white/70 hover:text-white">
            {link.label}
          </Link>
        ))}
      </nav>
    </header>
  );
}
