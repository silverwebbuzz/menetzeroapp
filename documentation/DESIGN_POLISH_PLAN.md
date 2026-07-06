# Design Polish Plan (Phase 1)

A targeted polish of the existing design, not a redesign. Goal: fix the obvious mobile bugs, clean up the inline-CSS mess in the layout, make the existing look feel more professional. Preserves the current teal/Poppins brand.

## What this phase does NOT do

- No new visual identity or logo work
- No redesign of dashboard / measurements / quick input flows
- No dark mode
- No build pipeline (stays on CDN Tailwind for now)
- No new color palette

Save those for Phase 2 once we've confirmed the direction.

## What this phase DOES

1. **Extract the 270-line `<style>` block out of [layouts/app.blade.php](../resources/views/layouts/app.blade.php)** into a standalone CSS file at `public/css/app-shell.css`. Same styles, just organised.
2. **Fix the mobile sidebar bugs:**
   - Remove the flash-of-no-sidebar on desktop (inline `translateX(-100%)` on every load)
   - Replace fragile string-comparison toggle with a proper Alpine.js component
   - Add smooth slide-in/out with overlay fade
   - Hamburger button gets a proper active/open visual state
3. **Fix the hardcoded logo URL.** `https://app.menetzero.com/public/images/menetzero.svg` should be `{{ asset('images/menetzero.svg') }}`.
4. **Add a proper mobile-friendly top bar:**
   - Hamburger on the left (currently already there, but behavior is buggy)
   - Page title visible
   - Company switcher collapsed to a logo/name-only button on mobile
   - User menu becomes an avatar dropdown instead of separate "Notifications" and "Logout" links
5. **Consistent container / responsive wrapper for content.** Right now `content-area` is just padding. Add a sensible `max-width`, center on large screens, consistent vertical rhythm.
6. **Move Chart.js + Alpine.js + Tailwind CDN scripts to `@push('scripts')` pattern** so only pages that need them load them (not critical — leave as-is if it's messy).
7. **Polish the brand colors** — the teal (`#0ea5a3`) is used ad-hoc across inline styles. Extract to a tiny Tailwind config via the CDN script so it becomes `bg-brand`, `text-brand`, etc. More maintainable.
8. **Add print styles** for reports (tables readable, navigation hidden).

## What I'm NOT touching

- Component views (dashboard/measurements/quick-input/reports blade files) — one page refresh at a time can come in Phase 2 after this foundation.
- The login/register/auth pages — they already use `x-components` and look acceptable.
- Tables — they need real work (horizontal scroll on mobile, sticky headers) but that's Phase 2.
- Forms — same, Phase 2.

## Files that will change

- `resources/views/layouts/app.blade.php` — the 270-line `<style>` block gets externalized, hamburger logic rewritten
- NEW: `public/css/app-shell.css` — the extracted styles
- NEW: small Alpine component for sidebar toggle embedded in layout

## Risk / rollback

- All changes are CSS + markup only. No DB, no controllers, no routes.
- If it looks worse: revert the `app.blade.php` edit and delete the new CSS file. 10 seconds.
- Browser-level only. Zero risk to data.

## Ship + verify

1. After changes: push to server (whatever your current workflow is — direct file copy or git).
2. No deploy script needed since no composer/migration changes. Just hard-refresh browser (Cmd+Shift+R).
3. Test on:
   - Desktop (sidebar always visible)
   - iPad-width (sidebar slides in on hamburger)
   - iPhone-width (sidebar fills 90% width, content readable)
   - Landscape mobile
4. If anything looks broken, paste a screenshot.

## Phase 2 candidates (save for later)

- Proper build pipeline (Vite + npm on server)
- Dashboard card redesign (the current stat cards are plain)
- Measurements list with better grouping/density controls
- Quick Input forms: stepper-style progress, sticky submit bar
- Empty states with illustrations
- Loading states / skeletons
- Toasts instead of top-banner alerts
- Dark mode
