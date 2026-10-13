/**
 * The path prefix this app is currently served under (see
 * next.config.ts's basePath and public/web.config's proxy rule).
 * Files under /public aren't automatically prefixed by Next.js the way
 * page routes and _next/static assets are, so anything referencing one
 * directly -- the Bootstrap/custom stylesheets loaded in the root
 * layout -- needs this prepended by hand.
 *
 * Centralized here so moving off /next-preview at the real cutover is
 * a one-line change instead of a search-and-replace.
 */
export const BASE_PATH = "/next-preview";
