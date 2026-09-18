<?php

/**
 * Which portals the public marketing site advertises.
 *
 * This is a VISIBILITY switch, not an access control. Every consultant route
 * stays registered and every consultant login keeps working -- turning this off
 * only removes the links from the marketing nav, footer, homepage, pricing page
 * and the company auth screens, so app.menetzero.com reads as a single-product
 * company app. The portal is then handed out by direct URL:
 *
 *   /consultant          landing
 *   /consultant/login    sign in
 *   /consultant/register apply for a listing
 *
 * Do not use this flag to protect anything. An unlisted URL is unlisted, not
 * private -- it stays reachable, and search engines may already have it.
 * Blocking signups or gating the portal is a separate change.
 *
 * Rationale: while the company packages are the thing being sold, showing two
 * audiences on one homepage made visitors pick the wrong door. Flip this back
 * to true to relaunch the consultant side.
 */
return [

    /*
    |---------------------------------------------------------------------------
    | Consultant portal — publicly advertised
    |---------------------------------------------------------------------------
    |
    | false: no consultant links anywhere on the public site. Routes still work.
    | true:  the consultant portal is advertised again, as before.
    |
    | Override per environment with PORTAL_CONSULTANT_PUBLIC=true in .env.
    |
    */
    'consultant_public' => env('PORTAL_CONSULTANT_PUBLIC', false),

    /*
    |---------------------------------------------------------------------------
    | Require a paid subscription before the company workspace opens
    |---------------------------------------------------------------------------
    |
    | true:  after saving their business profile, a company is held at plan
    |        selection until a subscription is active (EnsureSubscribed).
    | false: the workspace opens unpaid on the free entitlement floor -- data
    |        entry works, Scope 3 is locked and downloads are watermarked.
    |
    | The paywall deliberately sits after business setup rather than at
    | registration: subscriptions key off company_id, and no company exists
    | until that profile is saved.
    |
    | Set PORTAL_REQUIRE_SUBSCRIPTION=false in .env to reopen the workspace
    | immediately -- no deploy, and nothing about existing subscriptions
    | changes, since access is always read from the subscription itself.
    |
    */
    'require_subscription' => env('PORTAL_REQUIRE_SUBSCRIPTION', true),

];
