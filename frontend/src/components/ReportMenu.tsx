"use client";

import Link from "next/link";
import { useEffect, useState } from "react";

export type ReportCard = {
  href: string;
  icon: string;
  iconClass: string;
  title: string;
  subtitle: string;
  description: string;
};

/**
 * The report-grid of order_report.blade.php / users_report.blade.php: each
 * card opens a Bootstrap modal describing the report, whose "Open Report"
 * button goes to it. Same markup and classes as Blade, with React state in
 * place of Bootstrap's data-toggle JS.
 */
export default function ReportMenu({ cards }: { cards: ReportCard[] }) {
  const [open, setOpen] = useState<ReportCard | null>(null);

  useEffect(() => {
    document.body.classList.toggle("modal-open", open !== null);
    function onKeyDown(e: KeyboardEvent) {
      if (e.key === "Escape") setOpen(null);
    }
    window.addEventListener("keydown", onKeyDown);
    return () => {
      document.body.classList.remove("modal-open");
      window.removeEventListener("keydown", onKeyDown);
    };
  }, [open]);

  return (
    <>
      <div className="report-grid">
        {cards.map((card) => (
          <a
            key={card.href}
            href="#"
            className="report-card"
            onClick={(e) => {
              e.preventDefault();
              setOpen(card);
            }}
          >
            <div className={`report-icon ${card.iconClass}`}>
              <i className={`fa ${card.icon}`} aria-hidden="true"></i>
            </div>
            <div className="report-card-title">{card.title}</div>
            <div className="report-card-subtitle">{card.subtitle}</div>
          </a>
        ))}
      </div>

      {open && (
        <>
          <div className="modal fade in" style={{ display: "block" }} role="dialog" onClick={() => setOpen(null)}>
            <div className="modal-dialog" role="document" onClick={(e) => e.stopPropagation()}>
              <div className="modal-content">
                <div className="modal-header">
                  <button type="button" className="close" aria-label="Close" onClick={() => setOpen(null)}>
                    <span aria-hidden="true">&times;</span>
                  </button>
                  <h4 className="modal-title">{open.title}</h4>
                </div>
                <div className="modal-body">{open.description}</div>
                <div className="modal-footer">
                  <Link href={open.href} className="btn btn-brand">
                    Open Report
                  </Link>
                  <button type="button" className="btn btn-default" onClick={() => setOpen(null)}>
                    Close
                  </button>
                </div>
              </div>
            </div>
          </div>
          <div className="modal-backdrop fade in"></div>
        </>
      )}
    </>
  );
}
