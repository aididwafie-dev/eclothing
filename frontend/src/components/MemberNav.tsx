import Link from "next/link";

const links = [
  { href: "/personal-details", label: "Personal Details" },
  { href: "/cart", label: "Order Uniform" },
  { href: "/orders", label: "My Orders" },
  { href: "/notifications", label: "Notifications" },
];

export default function MemberNav() {
  return (
    <header className="border-b border-black/10 bg-white">
      <nav className="mx-auto flex max-w-5xl items-center gap-6 px-4 py-3 text-sm">
        <Link href="/" className="font-semibold">
          e-Clothing
        </Link>
        {links.map((link) => (
          <Link key={link.href} href={link.href} className="text-black/70 hover:text-black">
            {link.label}
          </Link>
        ))}
      </nav>
    </header>
  );
}
