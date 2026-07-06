# Payment Gateways — Setup & Endpoints

| | |
|---|---|
| **Version** | 1.0 |
| **Date** | June 2026 |
| **Audience** | Developers, ops, super-admin |
| **Related** | [PROJECT_OVERVIEW.md](./PROJECT_OVERVIEW.md), [CONSULTANT_AGENCY_PLAN_V1.md](./CONSULTANT_AGENCY_PLAN_V1.md) |

MenetZero supports three payment gateways for one-time checkout flows:

| Gateway | Integration style | Primary currency |
|---|---|---|
| **Razorpay** | Embedded checkout (JS popup) | INR |
| **Cashfree** | Hosted redirect | AED |
| **Stripe** | Hosted Checkout (redirect) | AED (marketplace/agency) or plan currency (subscriptions) |

Gateway credentials are stored in the `payment_gateways` table and managed in the super-admin UI. Secrets are encrypted at rest.

**Admin UI:** `/admin/payment-gateways`  
**Core service:** [app/Services/PaymentService.php](../app/Services/PaymentService.php)  
**Webhook handler:** [app/Http/Controllers/PaymentWebhookController.php](../app/Http/Controllers/PaymentWebhookController.php)

---

## 1. Stripe — overview

Stripe uses **Checkout Sessions** (hosted payment page). The app does **not** use Stripe Elements or client-side card collection.

**Flow:**

1. User selects Stripe at checkout → app creates a `PaymentTransaction` (status `pending`).
2. `PaymentService::createStripeCheckoutSession()` calls Stripe API and stores `stripe_session_id` + `stripe_session_url` in transaction metadata.
3. Browser redirects to Stripe hosted checkout.
4. On success, Stripe redirects to the app callback URL with `session_id` and `transaction_id`.
5. Callback verifies the session via Stripe API and marks the transaction `completed` (activates subscription / pack / marketplace order).
6. Stripe also POSTs webhook events for reliability (recommended for production).

---

## 2. Stripe — admin configuration

1. Log in to **Super Admin** → **Payment Gateways** (`/admin/payment-gateways`).
2. Find the **Stripe** card.
3. Set **Mode** to `Test / Sandbox` or `Live / Production`.
4. Enable **Enable for clients** when ready.
5. Fill in:

| Admin field | Stripe Dashboard value | Example |
|---|---|---|
| **Publishable Key** (`key_id`) | Publishable key | `pk_test_...` or `pk_live_...` |
| **Secret Key** (`key_secret`) | Secret key | `sk_test_...` or `sk_live_...` |
| **Webhook Secret** (`webhook_secret`) | Signing secret from webhook endpoint | `whsec_...` |

> Leave secret fields blank when saving other settings — the saved value is kept.

6. Run migration on first deploy (adds Stripe row if missing):

```bash
php artisan migrate
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

Migration: [database/migrations/2026_06_16_120000_add_stripe_payment_gateway.php](../database/migrations/2026_06_16_120000_add_stripe_payment_gateway.php)

---

## 3. Stripe — webhook setup (Stripe Dashboard)

1. Open [Stripe Dashboard → Developers → Webhooks](https://dashboard.stripe.com/webhooks).
2. **Add endpoint**.
3. **Endpoint URL** (production):

```
https://app.menetzero.com/webhooks/payments/stripe
```

Use your local/staging `APP_URL` for non-production environments. The exact URL is also shown on the admin Payment Gateways page.

4. **Events to send** — subscribe to:

| Event | Purpose |
|---|---|
| `checkout.session.completed` | Payment succeeded (card and most methods) |
| `checkout.session.async_payment_succeeded` | Delayed payment methods (e.g. bank transfer) |
| `checkout.session.expired` | Session timed out → transaction marked `failed` |

5. After creating the endpoint, copy the **Signing secret** (`whsec_...`) into **Webhook Secret** in admin.

**Notes:**

- Webhook route is **CSRF-exempt** and **public**; authenticity is verified via `Stripe-Signature` header.
- If webhook secret is missing, the endpoint returns `400 Webhook not configured`.
- Callback URL + webhook both can complete a payment; webhook is the backup if the user closes the browser before redirect.

---

## 4. HTTP endpoints

Replace `https://app.menetzero.com` with your `APP_URL`.

### 4.1 Webhook (all flows)

| Method | Path | Route name | Handler |
|---|---|---|---|
| `POST` | `/webhooks/payments/stripe` | `webhooks.payments.stripe` | `PaymentWebhookController@stripe` |

**Request headers:** `Stripe-Signature` (required)  
**Response:** `200 {"status":"ok"}` on success; `400` on bad/missing signature or missing config.

### 4.2 Client subscription checkout

| Method | Path | Route name | Handler |
|---|---|---|---|
| `GET` | `/subscriptions/checkout/{id}` | `subscriptions.checkout` | Start checkout (gateway selected on prior step) |
| `GET` | `/subscriptions/payment/stripe/callback` | `subscriptions.payment.stripe` | `SubscriptionController@stripeCallback` |

**Callback query params:** `session_id`, `transaction_id`

### 4.3 Consultant marketplace (client buys consultant service)

| Method | Path | Route name | Handler |
|---|---|---|---|
| `GET` | `/consultants/payment/checkout/{id}` | `client.consultants.payment.checkout` | Payment page |
| `GET` | `/consultants/payment/stripe` | `client.consultants.payment.stripe` | `ConsultantMarketplaceController@stripeCallback` |

### 4.4 Consultant agency packs (pack purchase, extra slots, year unlock)

| Method | Path | Route name | Handler |
|---|---|---|---|
| `GET` | `/consultant/packs/payment/{transaction}` | `consultant.packs.payment.checkout` | Payment page |
| `GET` | `/consultant/packs/payment/stripe` | `consultant.packs.payment.stripe` | `PackCheckoutController@stripeCallback` |

Consultant renewal (`POST /consultant/renewal`) also accepts `gateway=stripe` and follows the same pack payment checkout path.

### 4.5 Other gateway webhooks (reference)

| Gateway | Method | Path | Route name |
|---|---|---|---|
| Razorpay | `POST` | `/webhooks/payments/razorpay` | `webhooks.payments.razorpay` |
| Cashfree | `POST` | `/webhooks/payments/cashfree` | `webhooks.payments.cashfree` |

---

## 5. Where Stripe appears in the product

| Area | Who pays | Gateway selector location |
|---|---|---|
| **Client subscriptions** | Company owner | `/subscriptions/upgrade` → checkout |
| **Consultant marketplace** | Company owner | `/consultants/{id}/checkout` |
| **Agency pack purchase** | Consultant org | `/consultant/packs` |
| **Extra slots / year unlock** | Consultant org | `/consultant/packs`, `/consultant/clients/{id}` |
| **Contract renewal** | Consultant org | `/consultant/renewal` |

Stripe is listed alongside Razorpay and Cashfree in each gateway dropdown when enabled in admin.

---

## 6. Stripe API usage (internal)

Implemented in `PaymentService` — no Stripe PHP SDK; uses Laravel HTTP client.

| Method | Stripe API | Purpose |
|---|---|---|
| `createStripeCheckoutSession()` | `POST /v1/checkout/sessions` | Create hosted session |
| `getStripeCheckoutSession()` | `GET /v1/checkout/sessions/{id}` | Verify payment on callback |
| `verifyStripeWebhook()` | — | HMAC verify `Stripe-Signature` |

**Session parameters (summary):**

- `mode`: `payment` (one-time)
- `payment_method_types[]`: `card`
- `client_reference_id` + `metadata[transaction_id]`: links to `payment_transactions.id`
- `line_items[0].price_data`: amount in minor units from transaction currency/amount
- `success_url`: callback with `{CHECKOUT_SESSION_ID}` placeholder
- `cancel_url`: returns user to checkout page

**Stored references on completion:**

- `stripe_session_id`
- `stripe_payment_intent_id` (on `payment_transactions` and in metadata)

---

## 7. Deploy checklist

### Test mode

- [ ] Run `php artisan migrate` (Stripe gateway row exists)
- [ ] Admin: Stripe enabled, mode **Test**, test `pk_` / `sk_` keys saved
- [ ] Stripe Dashboard: webhook endpoint pointing to your environment URL
- [ ] Webhook secret saved in admin
- [ ] Test card: `4242 4242 4242 4242`, any future expiry, any CVC
- [ ] Verify: subscription upgrade, consultant pack, or marketplace order completes
- [ ] Check `payment_transactions.status = completed` and subscription/pack activated

### Live mode

- [ ] Switch admin mode to **Live / Production**
- [ ] Replace keys with live `pk_live_` / `sk_live_`
- [ ] Create **separate** live webhook endpoint in Stripe Dashboard
- [ ] Save live `whsec_` in admin
- [ ] Confirm `/terms`, `/privacy`, `/refunds`, `/pricing`, `/contact` are reachable (gateway whitelisting)

### Troubleshooting

| Symptom | Likely cause |
|---|---|
| "Could not start Stripe payment" | Invalid secret key, wrong mode (test key in live mode), or amount/currency error |
| Payment stuck `pending` | User did not return from Stripe; check webhook delivery in Stripe Dashboard |
| Webhook `400 Invalid signature` | Wrong `whsec_`, or request not from Stripe |
| Webhook `400 not configured` | Webhook secret empty in admin |
| Gateway not in dropdown | Stripe disabled in admin or migration not run |

**Logs:** `storage/logs/laravel.log` — search for `Stripe checkout` or `Stripe webhook`.

---

## 8. Code map

| File | Role |
|---|---|
| [app/Services/PaymentService.php](../app/Services/PaymentService.php) | Stripe session create/fetch, webhook verify |
| [app/Http/Controllers/PaymentWebhookController.php](../app/Http/Controllers/PaymentWebhookController.php) | Webhook → activate transaction |
| [app/Http/Controllers/Client/SubscriptionController.php](../app/Http/Controllers/Client/SubscriptionController.php) | Client plan checkout + callback |
| [app/Http/Controllers/Client/ConsultantMarketplaceController.php](../app/Http/Controllers/Client/ConsultantMarketplaceController.php) | Marketplace checkout + callback |
| [app/Http/Controllers/Consultant/Agency/PackCheckoutController.php](../app/Http/Controllers/Consultant/Agency/PackCheckoutController.php) | Agency pack checkout + callback |
| [app/Services/ConsultantAgencyPaymentService.php](../app/Services/ConsultantAgencyPaymentService.php) | Shared agency payment session creation |
| [app/Models/PaymentGateway.php](../app/Models/PaymentGateway.php) | Gateway config + `forGateway('stripe')` |
| [resources/views/admin/payment-gateways/index.blade.php](../resources/views/admin/payment-gateways/index.blade.php) | Admin keys + webhook URL display |
| [routes/web.php](../routes/web.php) | All routes listed in §4 |

---

## 9. Razorpay & Cashfree (brief reference)

### Razorpay

- **Admin fields:** Key ID, Key Secret, Webhook Secret (optional)
- **Webhook:** `POST /webhooks/payments/razorpay`
- **Checkout:** Embedded JS on checkout blade; callback is `POST` with signature verification
- **Currency:** INR

### Cashfree

- **Admin fields:** App ID (`x-client-id`), Secret Key, Webhook Secret
- **Webhook:** `POST /webhooks/payments/cashfree`
- **Checkout:** Redirect to Cashfree hosted page; callback is `GET`
- **Currency:** AED

For full Razorpay/Cashfree callback paths, see [routes/web.php](../routes/web.php) (search `payment.razorpay` and `payment.cashfree`).
