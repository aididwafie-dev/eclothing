import AppHeader from "@/components/AppHeader";
import AppShell from "@/components/AppShell";
import BodyClass from "@/components/BodyClass";
import MemberSidebar from "@/components/MemberSidebar";

export default function MemberLayout({ children }: { children: React.ReactNode }) {
  return (
    <>
      <BodyClass name="flag" />
      <AppShell header={<AppHeader notifContext="member" />} sidebar={<MemberSidebar />}>
        {children}
      </AppShell>
    </>
  );
}
