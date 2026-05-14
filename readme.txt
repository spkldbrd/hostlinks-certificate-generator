=== Hostlinks Certificate Generator ===
Contributors: digitalsolution
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.0
License: GPLv2 or later
Requires Plugins: hostlinks

Generate PDF completion certificates from Hostlinks events with optional email delivery.

== Description ==

* Shortcode `[hostlinks_certificate_generator]` for the generator UI
* Settings under Settings → HL Certificates
* REST API: `hl-cert/v1/events` and `hl-cert/v1/pdf`
* Optional integration with Hostlinks Marketing Ops bucket scoping
* Hostlinks 2.10.5+ adds a Certificates toolbar button (Settings → Certificates)

== Changelog ==

= 1.0.28 =
* Change: Seal (circular emblem) moved to lower-left corner of certificate.
* Change: Event details (dates and location) moved into certificate body below the body text.
* Change: Certificate number label updated to "Certificate of Completion No."

= 1.0.27 =
* Improvement: Pen watermark enlarged by 150px and opacity reduced to 25%.
* Improvement: Name rule line and body text moved up 60px closer to recipient name (preview and PDF).

= 1.0.26 =
* Change: Removed "Office of Professional Development & Training" subheader from both preview and PDF.
* Improvement: Logo enlarged to 4.5rem in preview / 140px in PDF.
* Improvement: Name rule line sits immediately below recipient name.
* Improvement: Body text increased to 1.1rem for better readability.
* Improvement: Seal enlarged to 7rem (approximately 1.5x previous size).
* New: Pen & flag watermark added at 50% opacity in lower-right corner of certificate (preview and PDF).

= 1.0.25 =
* Improvement: Name rule line sits tighter directly under the recipient name.
* Improvement: Body text increased back to 1rem for better readability.
* Improvement: Seal enlarged to 4.75rem in the preview.

= 1.0.24 =
* Fix: Certificate scroll area reverted to overflow:hidden to prevent body text bleeding into the footer area.
* Fix: Element sizes and margins reduced so all certificate content fits within the aspect-ratio-constrained preview at narrower page container widths (e.g. live site themes with a content max-width narrower than 1280px).

= 1.0.23 =
* Fix: Certificate footer replaced with pure flexbox layout — no longer uses a <table> element so theme CSS table resets (display: block on tr/td) cannot break the three-column layout.
* Fix: Logo and seal images now explicitly clear floats so theme img { float } rules cannot displace them into the body text.

= 1.0.22 =
* Fix: Certificate preview footer columns no longer collapse on live sites whose theme resets table elements to block display — explicit display: table/table-row/table-cell overrides now ensure the three-column footer always renders correctly.
* Fix: Logo and seal images now resist theme img { max-width: 100% } rules that were distorting their dimensions.
* Fix: Flex layout on certificate inner container protected against theme style resets.

= 1.0.21 =
* Fix: Certificate preview body text no longer clips when the page container is wide.
* Improvement: Shell max-width raised to 1280px; preview certificate capped at 760px so it stays balanced at all container widths.
* Improvement: Updater now busts its GitHub release cache when an admin visits the HL Certificates settings page, so a version check is always fresh on that page load.

= 1.0.20 =
* Change: Replace bundled Grant Writing / Grant Management certificate seals with updated stamp artwork.
* Change: Two-day workshops now show 12 contact hours; Subaward now shows 6 contact hours.
* Change: Footer now shows Event Details with event date range and location / Zoom webinar.
* Change: Signature text now reads Rebecca Helm.
* Improvement: HL Certificates now appears as a top-level admin menu item directly after Hostlinks.

= 1.0.19 =
* Improvement: Print / Save PDF now uses the live certificate preview with print-only sizing and spacing for a one-page landscape PDF.
* Improvement: Emailed certificate PDF layout is tuned to more closely match the printed certificate layout while staying Dompdf-safe.
* Fix: Event filters now classify simple Management event types correctly and filter year/month by certificate completion date.

= 1.0.18 =
* Fix: Rewrite PDF layout — replace position:absolute footer with height:100% table layout; eliminates second blank page and gap between content and footer

= 1.0.17 =
* Feature: Email template editor — From Name, From Email, Subject, and Body are now configurable in plugin settings

= 1.0.15 =
* Change: Signature font switched from Great Vibes to Alex Brush for a more natural ink-pen feel

= 1.0.14 =
* Fix: Replace signature image with Great Vibes script font text — eliminates white-background rectangle covering the rule line and name label
* Improvement: Signature now renders identically in both live preview and PDF with no image dependency

= 1.0.13 =
* Change: Certificate background is now white (PDF and preview) — no toner wasted on parchment fill
* Change: All bundled JPEG assets re-composited on white so signature and logos render cleanly

= 1.0.12 =
* Fix: PDF generation crash ("The PHP GD extension is required") — switched all PDF images to JPEG so Dompdf's Cpdf renderer works without the GD extension
* Fix: Dompdf render exceptions now caught and returned as a clean API error instead of a WordPress white-screen fatal

= 1.0.11 =
* Added a Class Type selector (Grant Writing / Grant Management / Subaward) at the top of the form. The selector now drives the certificate preview text, logo, and seal — no more dependence on per-event variant detection.
* Event dropdown is filtered server-side by the chosen class type, so you only ever see events of the selected workshop type.
* REST /events endpoint now accepts a `class` query parameter (grant_writing | grant_management | subaward).

= 1.0.10 =
* Event dropdown labels are now consistent: "Location — Class Type" (the class type is appended only when not already in the location string).
* ZOOM events (eve_zoom = yes) get a "ZOOM · " prefix in the dropdown so they are easy to spot.
* cvent_event_title is no longer used by default — it produced duplicated dates because it already contains the dates inline.

= 1.0.9 =
* Fix: preview now reliably updates for every event selection by using selectedIndex position lookup instead of string-key matching in eventsById (prevents stale preview when database event IDs are null or duplicated).
* UI: removed redundant Year / Month / Event label text above selectors; aria-label attributes retained for accessibility.

= 1.0.8 =
* Layout: restore two-column grid (form left, full preview right).
* Seals: replaced photo PNGs with vector SVG seals (transparent background, gold laurel wreath design).
* Logos: stripped near-white backgrounds from GWUSA / GMUSA logo PNGs; added mix-blend-mode: multiply so any fringe disappears on the parchment background.
* PDF: seals now rendered as inline SVG in Dompdf with DejaVu Serif font mapping.

= 1.0.7 =
* Thin horizontal rule under the Hostlinks toolbar before the certificate generator.

= 1.0.6 =
* Header logos: GWUSA / GMUSA artwork as transparent PNGs (regenerated from bundled `gwusa-logo.pdf` / `gmusa-logo.pdf` via `build/rasterize_logo_pdfs.py`); per–class-type defaults before legacy PNG fallback.

= 1.0.5 =
* Footer center seal uses bundled GWUSA / GMUSA artwork by workshop type (Grant Management and Subaward use the Grant Management seal).

= 1.0.4 =
* Remove Certificate Studio header strip; past-events-only event list with year (default: current year) and month filters for the dropdown; events sorted newest first.

= 1.0.3 =
* When Hostlinks can resolve the Reports page URL, the certificate hub repeats the same top toolbar as Reports (Certificates highlighted) and loads Hostlinks calendar styles.

= 1.0.2 =
* PDF: remove SVG seal text (Dompdf fatal), fixed US Letter landscape frame, aligned footer rules/labels, centered certificate number, removed “Issued in” line.
* Settings: separate Subaward logo (three logos). Grant Management / Subaward certificate copy per class type.

= 1.0.1 =
* Grant Certify Pro–style certificate layout, live preview, studio header, and workshop copy (Grant Writing / Grant Management / Subaward).
* Bundled Grant Writing USA logo + certificate seal assets; signature renders as styled Rebecca Helm text for print/PDF consistency.
* Subaward type hints setting; agency/organization field; certificate ID GWU-YEAR-#####.

= 1.0.0 =
* Initial release.
