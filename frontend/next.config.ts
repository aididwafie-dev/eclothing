import type { NextConfig } from "next";
import { BASE_PATH } from "./src/lib/basePath";

const nextConfig: NextConfig = {
  /* config options here */
  // Reachable at /next-preview/* behind IIS/ARR while it's still a
  // sandbox separate from the real site. Without this, every asset link
  // and every <Link>/router.push href comes out root-relative, which
  // escapes the proxy prefix entirely -- the browser requests them
  // directly against the real site's PHP app instead of this one.
  basePath: BASE_PATH,
  turbopack: {
    rules: {
      "*.css": {
        loaders: ["@tailwindcss/turbopack"],
        as: "*.css",
      },
    },
  },
};

export default nextConfig;
