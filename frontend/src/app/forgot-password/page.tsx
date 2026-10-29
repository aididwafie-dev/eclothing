import ForgotPasswordForm from "@/components/ForgotPasswordForm";

export default function ForgotPasswordPage() {
  return (
    <ForgotPasswordForm
      checkUrl="/api/auth/forgot-password/check"
      sendUrl="/api/auth/forgot-password"
      backHref="/login"
      backLabel="User Login"
    />
  );
}
