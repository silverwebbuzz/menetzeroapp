# Cron & scheduled jobs

Everything this application needs running on a timer, and how to install it.

---

## 1. The only cron entry you need

Laravel does its own scheduling. **One** system cron entry drives every job
below; you never add a cron line per command.

```cron
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

Install as the user that owns the application files (not root, unless that is
the owner) with `crontab -e`.

Verify it is installed:

```bash
crontab -l                      # the entry is listed
php artisan schedule:list       # what Laravel intends to run, and when
```

`schedule:list` showing jobs does **not** prove cron is running — it only
reads `routes/console.php`. If the cron entry is missing, `schedule:list`
still looks perfectly healthy and nothing ever fires.

---

## 2. Scheduled jobs

Defined in `routes/console.php`.

| Time | Command | What it does | If it does not run |
|---|---|---|---|
| 07:30 daily | `subscriptions:apply-scheduled-downgrades` | Moves a subscription onto the cheaper plan the customer chose, once the paid term has ended. Grants the term and raises an invoice. | The customer silently drops to Free instead of the plan they selected. |
| 08:00 daily | `subscriptions:send-renewal-reminders` | Emails company and consultant renewal nudges in the 45 / 14 / 3-day windows. | Nobody is told their capacity is about to lapse. |

Order matters: the downgrade job runs first so a term that ended overnight is
moved to its new plan before anything emails the customer about renewing.

Both accept `--dry-run`, which counts what would happen and writes nothing:

```bash
php artisan subscriptions:apply-scheduled-downgrades --dry-run
php artisan subscriptions:send-renewal-reminders --dry-run
```

---

## 3. Manual commands (not scheduled)

Run by hand when needed. Do **not** add these to cron.

| Command | Purpose |
|---|---|
| `menetzero:install-scope3` | Installs the 15 GHG Protocol Scope 3 sources, forms and factors. One-time setup per environment. |
| `companies:cleanup` | Removes orphaned company records. Destructive — read it before running. |

---

## 4. Queue worker — not currently required

`QUEUE_CONNECTION` defaults to `database`, but **nothing queues today**:
no class implements `ShouldQueue` and there are no `Mail::queue()` calls. All
mail sends synchronously inside the request.

That means a queued job would sit in the `jobs` table forever with no worker.
If anything is ever changed to queue — the invoice PDF and its email are the
likely first candidates, since PDF rendering is slow — a worker becomes
mandatory:

```cron
# ONLY once something actually queues. Supervisor is better than cron here.
* * * * * cd /path/to/app && php artisan queue:work --stop-when-empty >> /dev/null 2>&1
```

Prefer Supervisor with `queue:work --tries=3` for a long-running worker.

---

## 5. After deploying a new scheduled job

```bash
php artisan optimize:clear
php artisan schedule:list          # confirm it appears
php artisan <the:command> --dry-run  # confirm it runs clean
```

A job that has never been executed once by hand should not be left to fire
unattended at 07:30.
