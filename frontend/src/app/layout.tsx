import type { Metadata } from "next";
import { Geist, Geist_Mono } from "next/font/google";
import "./globals.css";

const geistSans = Geist({
  variable: "--font-geist-sans",
  subsets: ["latin"],
});

const geistMono = Geist_Mono({
  variable: "--font-geist-mono",
  subsets: ["latin"],
});

export const metadata: Metadata = {
  title: "RMAF e-Clothing",
  description: "RMAF e-Clothing member and admin portal",
};

export default function RootLayout({ children }: LayoutProps<"/">) {
  return (
    <html
      lang="en"
      className={`${geistSans.variable} ${geistMono.variable} h-full antialiased`}
    >
      <head>
        {/* The real app's own stylesheets -- served by the same IIS site
            straight out of the Laravel app's public/front_end folder, not
            duplicated into this app, so there is only ever one copy to
            keep in sync. Root-relative on purpose: this path is not a
            Next.js route, so it is never under the /next-preview prefix
            even once basePath is set. */}
        <link rel="stylesheet" href="/front_end/bootstrap-3.3.7-dist/css/bootstrap.min.css" />
        <link rel="stylesheet" href="/front_end/font-awesome-4.7.0/css/font-awesome.min.css" />
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="/front_end/css/style.css" />
      </head>
      <body className="min-h-full flex flex-col">{children}</body>
    </html>
  );
}
