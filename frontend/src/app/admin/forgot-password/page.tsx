import ForgotPasswordForm from "@/components/ForgotPasswordForm";

export default function AdminForgotPasswordPage() {
  return (
    <ForgotPasswordForm
      checkUrl="/api/admin/auth/forgot-password/check"
      sendUrl="/api/admin/auth/forgot-password"
      backHref="/admin/login"
      backLabel="Admin Login"
    />
  );
}
