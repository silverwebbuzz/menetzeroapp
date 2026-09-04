-- ---------------------------------------------------------------------------
-- MENetZero — go online-only cleanup (2026-09-03)
--
-- Removes the offline sales workflow and the two retired payment gateways:
--   1. company_package_requests   (admin "Company package requests")
--   2. consultant_entity_requests (admin "Consultant client requests")
--   3. commercial_price_book_entries (admin "Price book" — fed quotes only)
--   4. payment_gateways rows for Cashfree and Stripe
--   5. subscription_plans rows for packages no longer sold
--
-- RUN THE BACKUP FIRST. Section 0 is not optional — sections 3 and 5 delete
-- rows that cannot be reconstructed from the application code.
--
-- HOW TO RUN: execute section 1 on its own first and read the output, then run
-- sections 2-6. Section 1 counts rows in tables that section 2 drops, so it
-- only works before the cleanup — sections 2-6 are the re-runnable part.
-- ---------------------------------------------------------------------------

-- ===========================================================================
-- 0. BACKUP — run this on the shell BEFORE executing this file.
-- ===========================================================================
-- mysqldump -u USER -p DBNAME \
--   company_package_requests consultant_entity_requests \
--   commercial_price_book_entries payment_gateways subscription_plans \
--   > menetzero_pre_cleanup_backup.sql
--
-- Keep that file until the site has been verified working.


-- ===========================================================================
-- 1. PRE-FLIGHT — run this section BY ITSELF and read the output.
--    Nothing here changes data. It reads the tables section 2 drops, so run
--    it before the rest, not as part of a re-run.
-- ===========================================================================

SELECT 'package requests to be deleted' AS check_name, COUNT(*) AS row_count
FROM company_package_requests
UNION ALL
SELECT 'entity requests to be deleted', COUNT(*)
FROM consultant_entity_requests
UNION ALL
SELECT 'price book entries to be deleted', COUNT(*)
FROM commercial_price_book_entries;

-- Which retired plans are still referenced by a real subscription?
-- ANY ROW RETURNED HERE IS A PLAN SECTION 5 WILL DELIBERATELY *NOT* DELETE.
SELECT
    sp.plan_code,
    sp.plan_name,
    (SELECT COUNT(*) FROM client_subscriptions cs
       WHERE cs.subscription_plan_id = sp.id)      AS client_subscriptions,
    (SELECT COUNT(*) FROM consultant_subscriptions cns
       WHERE cns.subscription_plan_id = sp.id)     AS consultant_subscriptions,
    (SELECT COUNT(*) FROM admin_package_assignments apa
       WHERE apa.subscription_plan_id = sp.id)     AS admin_assignments,
    (SELECT COUNT(*) FROM subscription_coupons sc
       WHERE sc.subscription_plan_id = sp.id)      AS coupons
FROM subscription_plans sp
WHERE sp.plan_code IN (
    'client_starter',
    'client_growth',
    'client_scope_basic',
    'client_scope_pro',
    'client_esg_starter',
    'client_esg_complete'
)
ORDER BY sp.plan_code;


-- ===========================================================================
-- 2. DROP THE OFFLINE REQUEST TABLES
--
--    Both are leaf tables — nothing has a foreign key pointing at them, so
--    they drop without touching companies, users or subscriptions.
--    Their controllers, models and admin screens were removed in the same
--    change that ships this file.
-- ===========================================================================

DROP TABLE IF EXISTS `company_package_requests`;
DROP TABLE IF EXISTS `consultant_entity_requests`;


-- ===========================================================================
-- 3. DROP THE PRICE BOOK
--
--    commercial_price_book_entries existed only to suggest AED amounts when
--    an admin quoted one of the two request types above. With those gone
--    nothing reads this table: App\Data\CommercialPriceBook and its model
--    were deleted. Live prices are subscription_plans.price_annual, which is
--    what online checkout actually charges.
-- ===========================================================================

DROP TABLE IF EXISTS `commercial_price_book_entries`;


-- ===========================================================================
-- 4. REMOVE THE RETIRED PAYMENT GATEWAYS
--
--    Cashfree and Stripe checkout code was retired earlier; this removes the
--    leftover credential rows, their (encrypted) secrets, and with them the
--    two disabled cards on /admin/payment-gateways. Razorpay is untouched.
--
--    payment_transactions is NOT touched: historical Cashfree/Stripe payments
--    stay on the record, and the app still reads their metadata keys when
--    displaying an old order's payment reference.
-- ===========================================================================

DELETE FROM `payment_gateways`
WHERE `gateway` IN ('cashfree', 'stripe');


-- ===========================================================================
-- 5. REMOVE SUBSCRIPTION PLANS NO LONGER SOLD
--
--    Keeps exactly the eight plans in the live catalogue:
--      client_carbon, client_esg, client_enterprise, client_free,
--      consultant_carbon, consultant_esg, consultant_enterprise, consultant_free
--    plus consultant_1 (inactive demo/QA row, kept on purpose) and
--    consultant_managed_standard (a limit template for consultant-managed
--    clients — it is not sellable and is never shown, but entitlement lookups
--    resolve against it, so deleting it would break managed-client limits).
--
--    Retired codes are deleted ONLY when nothing references them. A
--    grandfathered subscriber on client_scope_pro keeps a working plan row;
--    re-run section 1 afterwards to see which (if any) survived and why.
-- ===========================================================================

DELETE sp
FROM `subscription_plans` sp
WHERE sp.`plan_code` IN (
        'client_starter',
        'client_growth',
        'client_scope_basic',
        'client_scope_pro',
        'client_esg_starter',
        'client_esg_complete'
      )
  AND NOT EXISTS (
        SELECT 1 FROM `client_subscriptions` cs
        WHERE cs.`subscription_plan_id` = sp.`id`
      )
  AND NOT EXISTS (
        SELECT 1 FROM `consultant_subscriptions` cns
        WHERE cns.`subscription_plan_id` = sp.`id`
      )
  AND NOT EXISTS (
        SELECT 1 FROM `admin_package_assignments` apa
        WHERE apa.`subscription_plan_id` = sp.`id`
      )
  -- subscription_coupons.subscription_plan_id is ON DELETE SET NULL, so this
  -- would not error -- it would silently widen a plan-restricted coupon into
  -- one valid on EVERY plan. Guarded rather than allowed.
  AND NOT EXISTS (
        SELECT 1 FROM `subscription_coupons` sc
        WHERE sc.`subscription_plan_id` = sp.`id`
      );


-- ===========================================================================
-- 6. VERIFY — the remaining catalogue should match the admin screen.
--
--    Expect: the 8 live plans, plus consultant_1 and
--    consultant_managed_standard, plus any retired plan section 5 protected
--    because a real subscription still points at it. Gateways: Razorpay only.
--
--    After running, clear the app caches on the server:
--        php artisan optimize:clear
-- ===========================================================================

SELECT
    `plan_code`,
    `plan_name`,
    `plan_category`,
    `price_annual`,
    `currency`,
    `is_active`
FROM `subscription_plans`
ORDER BY `plan_category`, `sort_order`, `plan_code`;

SELECT `gateway`, `label`, `is_enabled`, `mode`
FROM `payment_gateways`
ORDER BY `sort_order`;


-- ===========================================================================
-- 7. AED-ONLY CURRENCY  (added 2026-09-04, after Razorpay International
--    Payments + AED settlement were activated)
--
--    The application no longer offers INR: CurrencyService::SUPPORTED is
--    ['AED'] and the AED -> INR checkout fallbacks were removed, so a rejected
--    AED order now fails visibly instead of silently charging rupees. These
--    settings are the DB half of that.
--
--    Why it matters beyond display: the seller is an Indian entity exporting
--    services to UAE buyers. Receiving convertible foreign exchange is what
--    keeps the supply a zero-rated export; an INR charge would make it look
--    like a domestic supply.
--
--    VAT stays at 0%. billing_trn is deliberately left EMPTY -- InvoiceService
--    applies VAT only once a TRN is present, because charging 5% while not
--    registered with the UAE FTA is a penalisable offence, and the customer
--    could not reclaim it without a TRN on the invoice. Do not set billing_trn
--    or billing_vat_rate unless MENetZero actually registers for UAE VAT.
-- ===========================================================================

INSERT INTO `site_settings` (`key`, `value`, `created_at`, `updated_at`)
VALUES ('default_currency', 'AED', NOW(), NOW())
ON DUPLICATE KEY UPDATE `value` = 'AED', `updated_at` = NOW();

-- Geo-detection served the INR/AED split and has nothing left to choose.
INSERT INTO `site_settings` (`key`, `value`, `created_at`, `updated_at`)
VALUES ('currency_auto_detect', '0', NOW(), NOW())
ON DUPLICATE KEY UPDATE `value` = '0', `updated_at` = NOW();

-- Confirm VAT is off and no TRN is set.
SELECT `key`, `value`
FROM `site_settings`
WHERE `key` IN ('default_currency', 'currency_auto_detect', 'billing_trn', 'billing_vat_rate')
ORDER BY `key`;
