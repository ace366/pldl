// resources/js/family_availability.jsx
import React from "react";
import { createRoot } from "react-dom/client";
import AvailabilityPage from "./pages/family/AvailabilityPage";

function readProps() {
  const el = document.getElementById("family-availability-props");
  if (!el) return null;
  try {
    return JSON.parse(el.textContent || "{}");
  } catch {
    return null;
  }
}

const rootEl = document.getElementById("family-availability-root");
const props = readProps();

if (rootEl && props) {
  createRoot(rootEl).render(<AvailabilityPage {...props} />);
}
