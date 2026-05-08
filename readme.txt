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
* Bundled Grant Writing USA logo + Becky Helm signature assets; optional CEO signature override in settings.
* Subaward type hints setting; agency/organization field; certificate ID GWU-YEAR-#####.

= 1.0.0 =
* Initial release.
