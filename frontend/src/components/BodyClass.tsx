"use client";

import { useEffect } from "react";

/**
 * Some style.css rules key off a class on <body> itself (e.g. the
 * mobile-only `body.flag` padding tweak), not just a wrapping div, so
 * matching Blade's per-layout body class (`flag` for the signed-in
 * shell, `auth` for the login screens) needs this rather than just a
 * wrapper element.
 */
export default function BodyClass({ name }: { name: string }) {
  useEffect(() => {
    document.body.classList.add(name);
    return () => document.body.classList.remove(name);
  }, [name]);

  return null;
}
