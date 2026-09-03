# Payment Gateways — Setup & Endpoints

| | |
|---|---|
| **Version** | 2.0 |
| **Date** | September 2026 |
| **Audience** | Developers, ops, super-admin |
| **Related** | [PROJECT_OVERVIEW.md](./PROJECT_OVERVIEW.md), [CONSULTANT_AGENCY_PLAN_V1.md](./CONSULTANT_AGENCY_PLAN_V1.md) |

**Razorpay is the only payment gateway.** Cashfree and Stripe were removed in
September 2026 — their checkout code, webhook handlers, service methods,
credential rows and admin cards are all gone. If you are looking for their
setup instructions, they were deleted on purpose: re-adding a gateway means
writing the integration again, not just filling in keys.

| Gateway | Integration style | Currency |
|---|---|---|
| **Razorpay** | Embedded checkout (JS popup) | INR, or AED where International Payments is activated on the account |

Gateway credentials live in the `payment_gateways` table and are managed in the
super-admin UI. Secrets are encrypted at rest.

**Admin UI:** `/admin/payment-gateways`
**Core service:** [app/Services/PaymentService.php](../app/Services/PaymentService.php)
**Webhook handler:** [app/Http/Controllers/PaymentWebhookController.php](../app/Http/Controllers/PaymentWebhookController.php)

---

## 1. Overview

Razorpay uses an **embedded checkout popup**. The app never collects card
details itself — the popup is served by Razorpay and posts back a signed
result.

1. Buyer confirms a purchase → app creates a `PaymentTransaction` (status `pending`).
2. `PaymentService::createRazorpayOrder()` creates the order and stores
   `razorpay_order_id` in the transaction metadata.
3. The checkout blade opens the Razorpay popup with that order id.
4. On success the popup posts `razorpay_order_id`, `razorpay_payment_id` and
   `razorpay_signature` back to the app's callback route.
5. The callback verifies the HMAC signature and marks the transaction
   `completed`, which activates the subscription / pack / marketplace order.
6. Razorpay also POSTs a webhook, so a buyer who closes the browser mid-payment
   still gets activated. Completion is idempotent — whichever arrives first wins.

## 2. Admin configuration

1. Sign in as super admin and open `/admin/payment-gateways`.
2. Find the **Razorpay** card.

| Admin field | Razorpay Dashboard value |
|---|---|
| Key ID | `rzp_test_…` / `rzp_live_…` |
| Key Secret | Generated alongside the Key ID (shown once) |
| Webhook Secret | The secret you set when creating the webhook (section 3) |
| Mode | `Test / Sandbox` or `Live / Production` |

A gateway cannot be enabled without both a Key ID and a Key Secret — the
controller rejects it, because an enabled-but-unconfigured gateway would offer
the buyer a checkout that cannot complete.

## 3. Webhook setup (Razorpay Dashboard)

Add a webhook pointing at:

```
POST https://app.menetzero.com/webhooks/payments/razorpay
```

Subscribe to `payment.captured`. Set a webhook secret and paste the same value
into the admin card. The handler verifies `X-Razorpay-Signature` as an
HMAC-SHA256 of the raw request body and returns `400` on mismatch or missing
configuration.

## 4. HTTP endpoints

Replace `https://app.menetzero.com` with your `APP_URL`.

### 4.1 Webhook

| Method | Path | Route name | Handler |
|---|---|---|---|
| `POST` | `/webhooks/payments/razorpay` | `webhooks.payments.razorpay` | `PaymentWebhookController@razorpay` |

Public and CSRF-exempt (see `bootstrap/app.php`); authenticated by signature.

### 4.2 Client subscription checkout

| Method | Path | Route name | Handler |
|---|---|---|---|
| `GET` | `/subscriptions/upgrade` | `subscriptions.upgrade` | Plan chooser |
| `GET` | `/subscriptions/checkout/{id}` | `subscriptions.checkout` | Payment page |
| `POST` | `/subscriptions/payment/razorpay/callback` | `subscriptions.payment.razorpay` | `SubscriptionController@razorpayCallback` |

### 4.3 Consultant marketplace (client buys a consultant service)

| Method | Path | Route name | Handler |
|---|---|---|---|
| `GET` | `/consultants/payment/checkout/{id}` | `client.consultants.payment.checkout` | Payment page |
| `POST` | `/consultants/payment/razorpay` | `client.consultants.payment.razorpay` | `ConsultantMarketplaceController@razorpayCallback` |

### 4.4 Consultant agency packs (pack purchase, extra slots, year unlock)

| Method | Path | Route name | Handler |
|---|---|---|---|
| `GET` | `/consultant/packs/payment/{transaction}` | `consultant.packs.payment.checkout` | Payment page |
| `POST` | `/consultant/packs/payment/razorpay` | `consultant.packs.payment.razorpay` | `PackCheckoutController@razorpayCallback` |

Consultant renewal (`POST /consultant/renewal`) follows the same pack payment path.

## 5. Currency

`CurrencyService::chargeAmount()` charges in the visitor's display currency so
checkout shows the same currency they saw on the site. A standard Indian
Razorpay account accepts INR only; AED needs **International Payments**
activated. When AED is rejected, `PaymentService::isRazorpayCurrencyDisabledError()`
detects it and the agency checkout re-prices to the INR equivalent, telling the
buyer before they pay.

## 6. Deploy checklist

- [ ] Razorpay card at `/admin/payment-gateways` has Key ID + Secret, mode `live`, enabled.
- [ ] Webhook created in the Razorpay Dashboard with a secret matching the admin field.
- [ ] `APP_URL` matches the domain the webhook posts to.
- [ ] A test purchase completes and activates the plan.
- [ ] Killing the browser mid-payment still activates via webhook.

## 7. Code map

| Concern | File |
|---|---|
| Gateway credentials model | [app/Models/PaymentGateway.php](../app/Models/PaymentGateway.php) |
| Razorpay API calls + signature verification | [app/Services/PaymentService.php](../app/Services/PaymentService.php) |
| Webhook handler | [app/Http/Controllers/PaymentWebhookController.php](../app/Http/Controllers/PaymentWebhookController.php) |
| Activation after payment | [app/Services/PaymentCompletionService.php](../app/Services/PaymentCompletionService.php) |
| Admin settings screen | [app/Http/Controllers/Admin/PaymentGatewayController.php](../app/Http/Controllers/Admin/PaymentGatewayController.php) |

## 8. Historical Cashfree / Stripe payments

Payments taken through the retired gateways are **not** deleted. Their rows stay
in `payment_transactions`, and `ConsultantMarketplaceService` still reads the
`cashfree_order_id`, `stripe_payment_intent_id` and `stripe_session_id`
metadata keys when displaying an old order's payment reference. The
`client_subscriptions` and `client_billing_methods` tables likewise keep their
`stripe_*` columns for the same reason. Do not drop them.
