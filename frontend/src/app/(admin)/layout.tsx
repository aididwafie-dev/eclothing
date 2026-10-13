import AdminSidebar from "@/components/AdminSidebar";
import AppHeader from "@/components/AppHeader";
import AppShell from "@/components/AppShell";
import BodyClass from "@/components/BodyClass";

export default function AdminLayout({ children }: { children: React.ReactNode }) {
  return (
    <>
      <BodyClass name="flag" />
      <AppShell header={<AppHeader notifContext="admin" />} sidebar={<AdminSidebar />}>
        {children}
      </AppShell>
    </>
  );
}
