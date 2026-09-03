# MENetZero

Laravel GHG carbon-accounting SaaS for the UAE market. Companies record
emissions data; consultants (agencies) manage client companies; a super admin
oversees everything.

## Start here

- `documentation/DATABASE_SCHEMA.md` — the 79 tables, what they do, and which
  ones are traps. **Read before touching the database.**
- `documentation/PROJECT_OVERVIEW.md` — product and domain background.
- `graphify-out/wiki/index.md` — generated code map, for broad navigation.

## Three things that cause most bugs

### 1. There are three login tables, not one

`users` (guard `web`), `consultants` (guard `consultant`), `admins` (guard
`admin`, signs in at `/admin/login`). A consultant is **not** a `users` row — it
has its own email and password.

Anything touching auth, org deletion, or admin "who has access" screens must
consider all three. `consultants.agency_company_id` is `nullOnDelete`, so
deleting an agency company leaves a working consultant login behind unless it is
deleted explicitly — `OrganisationDeletionService` does this.

### 2. Emissions live in `measurements`, not `carbon_emissions`

The chain is `companies → locations → measurements → measurement_data`, and
`measurements` keys off `location_id`, not `company_id`. Use
`Company::measurements()`.

`carbon_emissions` and `carbon_calculations` are legacy and empty. Nothing writes
to them. Do not add new reads.

### 3. Roles come from `user_company_roles`, not `users.role`

The `users.role` column is legacy and is not what the app authorises against.
Real roles live in `user_company_roles` joined to `company_custom_roles`; a null
or `0` `company_custom_role_id` means **Owner**, not "no role".

## Migrations

The schema was squashed on 2026-09-03. `database/schema/mysql-schema.sql` is the
baseline; `database/migrations/` holds only the empty baseline marker.

Every schema change from now on is a **new migration** dated after
`2026_09_03_000000`. Never edit the schema file to make a change.

Before dropping a table, search for its **model class** — several live tables
(`usage_tracking`, `feature_flags`, `company_invitations`) are referenced only
through Eloquent, so grepping the table name finds nothing.

## Conventions

- Match the surrounding code's style, comment density, and naming.
- Comments explain *why*, not *what* — this codebase's existing comments are a
  good guide to the expected level.
- Destructive or irreversible operations get a typed-name confirmation, not a
  JS `confirm()`. See the danger zone in `admin/companies/show.blade.php`.
- After changing code, run `graphify update .` to keep the knowledge graph fresh.
