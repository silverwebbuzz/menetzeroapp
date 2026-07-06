# Design Refresh — Deploy Notes

## What changed

Applied a full enterprise-SaaS design system across the whole app, not just two screens. Everything now uses **Inter font** (Poppins kept as fallback), **emerald `#10b981` brand**, consistent buttons/cards/tables/badges, and a proper spacing system. 513 purple/violet class usages across 39 files now render as brand emerald automatically via a Tailwind config alias — so the old "purple admin, teal client" split disappears without touching each file.

## Files changed

| File | What changed |
|---|---|
| [public/css/app-shell.css](../public/css/app-shell.css) | Full rewrite — proper design system with tokens, buttons, cards, forms, tables, badges, alerts, stat cards, responsive breakpoints, print styles |
| [public/css/design-system.css](../public/css/design-system.css) | Brand token refreshed to emerald `#10b981`. Font family now Inter-first |
| [public/css/quick-input.css](../public/css/quick-input.css) | Appended design-system overrides — fonts, inputs, buttons, card shadows. Fixes purple "Select" button, blue "Calculate" button, green "Calculate & Add" button to all be brand primary |
| [resources/views/layouts/app.blade.php](../resources/views/layouts/app.blade.php) | Inter font, purple/violet/indigo Tailwind palette aliased to brand emerald |
| [resources/views/admin/layouts/app.blade.php](../resources/views/admin/layouts/app.blade.php) | Same — Inter, brand alias. Dropped the separate "admin purple" theme |
| [resources/views/admin/companies/index.blade.php](../resources/views/admin/companies/index.blade.php) | Full redesign — stat cards, new table with avatars + status badges + type badges, proper search/filter bar, empty state |
| [resources/views/quick-input/show.blade.php](../resources/views/quick-input/show.blade.php) | Action buttons now use `btn btn-primary` / `btn btn-outline` / `btn btn-secondary` instead of inline gradients |

## What this cascades to

Because the design system uses **class names that already exist in other views** (`.btn`, `.btn-primary`, `.card`, `.table`, `.badge`, `.form-control`, `.nav-link`, `.stat-card`, etc.), **any view that already uses these classes automatically inherits the new look**. Concretely:

- Every page with a button class gets consistent buttons
- Every Tailwind `bg-purple-*` / `bg-violet-*` / `bg-indigo-*` / `text-purple-*` across 39 files now renders as emerald brand colour
- The sidebar active state, hamburger, overlay, dropdown — all re-styled
- Tables across admin pages get the same polish
- Form elements (inputs, selects, textareas) get consistent styling wherever they're used

Views that use **inline `style=""` attributes or hardcoded Tailwind colour classes like `bg-blue-600`** keep their original look until touched. I left the Natural Gas form's template logic alone (611 lines of complex form builder) and changed only its submit buttons + CSS overrides.

## Deploy steps

Same pattern as before — push files to the server, clear view cache.

```bash
# On the server, as root:
cd /home/silverwebbuzz_in/public_html/menetzero/app

# Clear the compiled Blade cache so the new layouts take effect
rm -rf storage/framework/views/*
rm -rf bootstrap/cache/*.php

# Leave permissions alone — the dirs are already owned by silverwebbuzz_in:silverwebbuzz_in
# PHP-FPM will recompile as the right user on next request.

# Hard-refresh the browser (Cmd+Shift+R) to bust the CSS cache.
```

**Do NOT run `php artisan view:cache` as root** — that's what broke things last time. If you need to prime the cache, do it as the web user:

```bash
sudo -u silverwebbuzz_in php artisan view:cache
```

## Verify after deploy

Visit these pages and check they look polished:

1. **`/admin/companies`** — stat cards on top, table has avatars + "Client"/"Consultant" badges + "Active"/"Inactive" badges, hover row highlight, clean search bar. No purple anywhere.
2. **`/quick-input/1/natural-gas?fiscal_year=2026&location_id=1`** — "Select" / "Calculate" / "Calculate & Add to Footprint" buttons all consistent (emerald primary + outline + primary). No purple gradient. Inputs have proper focus ring.
3. **`/dashboard`** — sidebar left-bar active indicator, Inter font, clean header. User avatar dropdown in top-right.
4. **`/locations`** — same sidebar, same buttons.
5. **`/measurements`** — table renders with new styling.
6. **`/reports`** — buttons consistent.

## Phase 2 additions (2026-04)

- **Admin layout rebuilt** to use the proven app-shell structure (same pattern as client). Fixes the bug where content fell below the sidebar on `/admin/companies` and similar pages.
- **Admin nav rebuilt** using `.nav-link` + `.nav-section` classes — vertical brand bar on active item, tighter spacing.
- **Locations index redesigned** — table-based layout with avatars, status badges, head-office badges, consistent row actions.
- **Reports index polished** — Generate Report button, Export to Excel button, By Scope / By Emission Source toggle all standardised.
- **Dashboard progress bar** brand-coloured.
- **Auth layout (login/register/forgot/reset)** upgraded to Inter, emerald gradient with subtle radial highlights, softer glass card, proper inputs.
- **`orange-*` classes now alias to brand** (Locations and Measurements pages were using orange accents).
- **`deploy.sh` patched** to `chown` storage + bootstrap/cache back to the site user after running as root. Permission issue shouldn't recur.

## Known limitations / next steps

1. **Quick Input form layout is preserved as-is.** 611 lines of complex form builder with dependent selects and deduplication — not safe to rewrite blind. Styling refreshed via CSS overrides.
2. **Dashboard charts** (Chart.js) still use their default colours. Update the chart config in the dashboard blade to pass `#10b981` if you want brand-coloured charts.
3. **Tailwind CDN warning** still appears in prod console. Proper fix = build pipeline (Vite + npm) — deferred until ready.
4. **Individual emission-form sub-pages** (scope1/scope2/scope3/review/evidence) not individually touched — they inherit the purple→brand alias so should look much better but haven't been redesigned.
5. **Admin sub-pages** (users detail, subscription plan CRUD, role template CRUD, emission factor CRUD) weren't individually redesigned. They'll benefit from the purple→brand alias and table styling catch-alls but have not been hand-polished.

## Rollback

Pure CSS + view changes. If anything looks wrong:

```bash
cd /home/silverwebbuzz_in/public_html/menetzero/app
git log --oneline -5          # find the commit before these changes
git reset --hard <commit>
rm -rf storage/framework/views/*
```

No DB involvement, no migration.
