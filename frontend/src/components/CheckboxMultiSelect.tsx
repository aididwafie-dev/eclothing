"use client";

import { useEffect, useRef, useState } from "react";
import type { DropdownOption } from "@/lib/types";

/**
 * Stand-in for the jQuery multiple-select plugin the Blade cloth forms used:
 * a closed box that lists the picked values, opening into a checkbox list
 * with a "select all" row at the top.
 */
export default function CheckboxMultiSelect({
  options,
  value,
  onChange,
  selectAllLabel,
}: {
  options: DropdownOption[];
  value: string[];
  onChange: (value: string[]) => void;
  selectAllLabel: string;
}) {
  const [open, setOpen] = useState(false);
  const ref = useRef<HTMLDivElement>(null);

  useEffect(() => {
    function onDocClick(e: MouseEvent) {
      if (ref.current && !ref.current.contains(e.target as Node)) setOpen(false);
    }
    document.addEventListener("mousedown", onDocClick);
    return () => document.removeEventListener("mousedown", onDocClick);
  }, []);

  const allSelected = options.length > 0 && value.length === options.length;
  const toggle = (id: string) => onChange(value.includes(id) ? value.filter((v) => v !== id) : [...value, id]);
  const summary = allSelected
    ? "All selected"
    : options
        .filter((o) => value.includes(o.id))
        .map((o) => o.value)
        .join(", ");

  return (
    <div ref={ref} style={{ position: "relative", width: "100%" }}>
      <button
        type="button"
        className="form-control text-left"
        style={{ overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}
        onClick={() => setOpen((v) => !v)}
      >
        {summary || " "} <span className="caret pull-right" style={{ marginTop: 8 }}></span>
      </button>
      {open && (
        <div
          className="dropdown-menu"
          style={{ display: "block", width: "100%", maxHeight: 250, overflowY: "auto", padding: "4px 10px" }}
        >
          <div className="checkbox">
            <label>
              <input
                type="checkbox"
                checked={allSelected}
                onChange={() => onChange(allSelected ? [] : options.map((o) => o.id))}
              />{" "}
              {selectAllLabel}
            </label>
          </div>
          {options.map((o) => (
            <div className="checkbox" key={o.id}>
              <label>
                <input type="checkbox" checked={value.includes(o.id)} onChange={() => toggle(o.id)} /> {o.value}
              </label>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
