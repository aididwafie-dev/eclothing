"use client";

import { useRouter } from "next/navigation";
import { useEffect } from "react";

export default function RedirectAfter({ href, ms }: { href: string; ms: number }) {
  const router = useRouter();

  useEffect(() => {
    const timer = setTimeout(() => router.push(href), ms);
    return () => clearTimeout(timer);
  }, [router, href, ms]);

  return null;
}
