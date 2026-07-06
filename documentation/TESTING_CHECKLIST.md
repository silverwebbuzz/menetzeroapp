# Post-Fix Testing Checklist

Use this when you're ready to regression-test everything after the Phase 1 bug fixes (permission signature corrections, role-destroy permission, password-reset security, `company_type` migration, partner-route cleanup).

## How to test

### 1. Tail the logs in a terminal

```bash
tail -f /home/silverwebbuzz_in/public_html/menetzero/app/storage/logs/laravel.log
```

Keep this open while you click through the app. Real-time errors appear here.

### 2. Test as three distinct user types

Work through each role's checklist below. Tick items off as you go. For any failure, capture:

```
Where: <URL or page>
Who:   <role — super admin / owner / staff with role X>
Did:   <steps to reproduce>
Got:   <error / blank / wrong data / etc.>
Log:   <relevant lines from laravel.log>
```

Paste that block into a conversation with me and I'll fix it.

---

## Super Admin (`/admin/login`)

- [ ] Dashboard loads
- [ ] Companies — list page
- [ ] Companies — show individual company
- [ ] Users — list page
- [ ] Users — show individual user
- [ ] Subscription Plans — list, create, edit, delete
- [ ] Role Templates — list, create, edit, delete
- [ ] Statistics page
- [ ] **Emissions master data (CRUD each):**
  - [ ] Sources
  - [ ] Factors
  - [ ] GWP values
  - [ ] Unit conversions
  - [ ] Industry labels
  - [ ] Selection rules
- [ ] Admin logout works

---

## Company Owner (regular `/login` or register)

### Account creation

- [ ] Register with email + password
- [ ] Register with Google OAuth
- [ ] Welcome email arrives after registration
- [ ] Login with email + password
- [ ] Login with Google OAuth
- [ ] Logout works

### First-time setup

- [ ] Dashboard prompts for company setup when no company exists
- [ ] Company setup form saves correctly
- [ ] Industry sector → industry → subcategory cascading dropdowns work
- [ ] UAE emirate field saves (if applicable)

### Profile

- [ ] View profile page
- [ ] Update personal info (name, phone, designation)
- [ ] Update company info
- [ ] Change password → password-changed email arrives

### Locations

- [ ] List locations
- [ ] Create a new location (stepped form)
- [ ] Edit an existing location
- [ ] Toggle active/inactive status
- [ ] Toggle head-office flag
- [ ] Configure emission boundaries for a location

### Measurements

- [ ] List measurements (filter by location / status / fiscal year / search)
- [ ] Open a measurement — totals show
- [ ] Submit a draft measurement
- [ ] Per-source: calculate / edit / delete emission data
- [ ] Measurement audit trail entries get written (verify in DB or admin view)

### Quick Input — test each source

**Scope 1:**
- [ ] Natural Gas
- [ ] Fuel (with fuel category + fuel type cascades)
- [ ] Vehicle (owned / leased, with distance or fuel amount)
- [ ] Refrigerants (gas type dropdown)
- [ ] Process (if available)

**Scope 2:**
- [ ] Electricity (check UAE grid factor applies)
- [ ] Heat, Steam & Cooling

**Scope 3:**
- [ ] Flights
- [ ] Public Transport
- [ ] Home Workers

**Verify for each:**
- [ ] CO₂e calculation shows correctly on form
- [ ] Entry persists after save
- [ ] Edit entry works
- [ ] Delete entry works
- [ ] Totals roll up to the parent measurement

### Reports

- [ ] Index page loads with current totals
- [ ] Accordion behavior (first section does NOT auto-open — that was recent fix)
- [ ] Excel export downloads and opens
- [ ] PDF export downloads and opens
- [ ] PDF renders company name, dates, totals correctly (especially if company has special chars)

### Roles & Staff

- [ ] List custom roles
- [ ] Create a new custom role with specific permissions (try various combinations)
- [ ] Edit existing role — permission toggles persist
- [ ] **Delete a role** — this was a known bug (was checking wrong permission). Should now work for users with `roles_permissions.delete`.
- [ ] Invite staff member by email
- [ ] Staff invitation email arrives
- [ ] Resend invitation works
- [ ] Cancel pending invitation works
- [ ] Update existing staff member's role
- [ ] Remove staff member

### Subscriptions

- [ ] View subscription index
- [ ] View current plan
- [ ] Upgrade flow
- [ ] Billing page
- [ ] Payment history
- [ ] Add billing method
- [ ] Update billing method
- [ ] Set default billing method
- [ ] Delete billing method
- [ ] Cancel subscription

### Forgot Password

- [ ] Request reset with valid email → "Check your email" page
- [ ] Reset email actually arrives (if not, check `laravel.log` — SMTP fallback was removed in recent fix)
- [ ] Reset link in email works
- [ ] New password saves
- [ ] Can login with new password

### Account switcher (if user has multiple companies)

- [ ] Selector appears on login when user has access to 2+ companies
- [ ] Switch between companies works
- [ ] Active company context persists across pages

---

## Staff (invited, not owner)

### Permission gates — THESE WERE RECENTLY FIXED, test carefully

Create test staff users with these specific role combinations and verify:

- [ ] **Staff with only `locations.add`** can create locations (bug was: silent 403)
- [ ] **Staff with only `locations.edit`** can edit locations
- [ ] **Staff with only `measurements.add`** can add measurement data (bug was: checking non-existent `.create` permission)
- [ ] **Staff with only `measurements.edit`** can delete source data (bug was: array passed wrong to requirePermission)
- [ ] **Staff with only `measurements.delete`** can delete source data
- [ ] **Staff with `roles_permissions.delete`** can delete a role (bug was: checking `manage_settings` which doesn't exist)
- [ ] **Staff WITHOUT permission** gets a proper 403 page, not a blank or 500

### Staff-specific flows

- [ ] Staff login succeeds
- [ ] Dashboard shows only what they have permission for
- [ ] Sidebar only shows permitted modules
- [ ] Staff can view/edit only within their assigned company
- [ ] Staff cannot switch to another company they don't have access to

---

## Edge cases worth checking

- [ ] User with no active company (`$user->getActiveCompany()` returns null) — all protected pages redirect to company setup
- [ ] User with multiple companies — workspace selector appears after login
- [ ] Super admin trying `/admin/login` while already logged in as client → no conflict
- [ ] Client trying to access `/admin/*` → 403
- [ ] Deleted soft-deleted items don't appear in lists
- [ ] CSRF token errors don't appear on form submissions
- [ ] File upload avatars (if any) save and display
- [ ] Timezone / date formatting consistent across reports

---

## Known things that MIGHT break (from code audit)

These are untested suspects — if you hit them, it's likely one of these:

1. **Excel export fails** — needs `ext-gd` on PHP. Confirmed loaded on server but not runtime-tested.
2. **PDF export with unicode company names** — DomPDF can choke on special characters.
3. **ClientSubscription pages** — table has no migration; if code tries to `create()` a subscription and schema drifts, it'll error.
4. **Google OAuth first-time login** — auto-creates user as `company_admin` with no company. Should redirect to company setup — verify.
5. **Welcome / password-changed emails** — if SMTP fails, no fallback anymore. Check `laravel.log` for mail errors.
6. **Measurement audit trail gaps** — not all mutations confirmed to write audit entries.

---

## After testing — what to send me

For each failure:

```
### Failure 1
Where: https://app.menetzero.com/measurements/15/sources/54/calculate
Who:   Company owner (email: foo@bar.com)
Did:   Selected Scope 1 → Fuel, entered 100 litres Diesel, clicked Calculate
Got:   Page shows blank; no success message; data not saved
Log:
[2026-04-22 14:23:11] production.ERROR: Undefined property...
  at /home/.../QuickInputController.php:575
```

That's all I need to diagnose and fix each one.
