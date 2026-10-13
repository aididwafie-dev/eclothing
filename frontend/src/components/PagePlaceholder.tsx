export default function PagePlaceholder({
  title,
  replaces,
  children,
}: {
  title: string;
  replaces: string;
  children?: React.ReactNode;
}) {
  return (
    <div className="rounded-lg border border-black/10 bg-white p-6">
      <h1 className="text-xl font-semibold">{title}</h1>
      <p className="mt-1 text-sm text-black/50">
        Scaffold placeholder &mdash; will replace <code>{replaces}</code>.
      </p>
      {children && <div className="mt-6">{children}</div>}
    </div>
  );
}
