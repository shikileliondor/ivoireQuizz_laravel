# Email Verification and Password Reset Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add non-blocking email verification and app-opening password recovery links to the Laravel API while preserving neutral account-discovery responses.

**Architecture:** Laravel notifications generate signed HTTPS links on `https://laravelquizz.studiobeyam.net`. API endpoints validate verification data, expose neutral resend behavior, and retain the existing password broker flow. Small HTTPS fallback routes and a configurable `/.well-known/assetlinks.json` response support Android App Links until the publication identifiers are supplied.

**Tech Stack:** PHP 8.2, Laravel 12, Sanctum, Laravel Notifications, PHPUnit 11.

**Spec:** `docs/superpowers/specs/2026-11-06-email-verification-password-reset-design.md`

## Global Constraints

- Registration and application access remain available before email verification.
- Unknown, known, and already-verified addresses receive the same public resend response.
- Production link origin is `https://laravelquizz.studiobeyam.net`.
- SMTP credentials exist only in the production `.env`.
- Notification delivery is queue-compatible, with cPanel cron as the deployment default.
- Existing password-reset tokens and Sanctum-token revocation behavior remain intact.

## Review Focus

- A queued notification must not be dispatched before a rolled-back registration transaction finishes; Task 2 tests dispatch after commit.
- A modified, expired, or mismatched verification payload must never verify an account; Task 3 tests each case.
- Repeated verification must be idempotent; Task 3 tests the second confirmation.
- Missing mobile publication identifiers must yield valid, non-claiming association responses; Task 5 tests disabled configuration.
- SMTP or queue failure must not undo account creation; Task 2 tests registration independently from mail transport execution.

---

### Task 1: Authentication-link configuration

**Files:**
- Create: `config/auth_links.php`
- Modify: `.env.example`
- Test: `tests/Feature/Config/AuthLinkConfigTest.php`

**Interfaces:**
- Produces: `config('auth_links.url')`, `verification_expire` and Android association settings.

- [ ] Write a failing configuration test asserting the default HTTPS origin and nullable mobile identifiers.
- [ ] Run `php artisan test tests/Feature/Config/AuthLinkConfigTest.php` and confirm failure.
- [ ] Add the focused config file and documented environment keys, with no secrets.
- [ ] Re-run the configuration test and confirm it passes.

### Task 2: Verification notification after registration

**Files:**
- Create: `app/Notifications/VerifyEmailNotification.php`
- Modify: `app/Models/User.php`
- Modify: `app/Services/Auth/AuthService.php`
- Test: `tests/Feature/Api/V1/EmailVerificationTest.php`

**Interfaces:**
- Consumes: `config('auth_links.url')` and `verification_expire`.
- Produces: `User::sendEmailVerificationNotification(): void` and a queued French verification email with a signed HTTPS action URL.

- [ ] Write failing tests proving registration returns a usable token, leaves `email_verified_at` null, and schedules one verification notification after persistence.
- [ ] Run the focused test and confirm failure.
- [ ] Make `User` implement `MustVerifyEmail`, add the custom queued notification, and dispatch it only after successful registration persistence.
- [ ] Run the focused tests and confirm they pass.

### Task 3: Neutral resend and signed confirmation API

**Files:**
- Create: `app/Http/Controllers/Api/V1/EmailVerificationController.php`
- Create: `app/Http/Requests/Api/V1/Auth/ResendEmailVerificationRequest.php`
- Create: `app/Http/Requests/Api/V1/Auth/VerifyEmailRequest.php`
- Modify: `routes/api.php`
- Modify: `bootstrap/app.php` or the existing rate-limit provider owning API limiters.
- Test: `tests/Feature/Api/V1/EmailVerificationTest.php`

**Interfaces:**
- Produces: `POST /api/v1/auth/email/verification-notification` and `POST /api/v1/auth/email/verify` JSON contracts.

- [ ] Write failing tests for identical resend responses across unknown, known, and verified addresses, including throttling.
- [ ] Write failing tests for valid, repeated, expired, modified, and user-mismatched signed confirmation payloads.
- [ ] Run the focused tests and confirm failures.
- [ ] Implement minimal request validation, neutral lookup behavior, signed URL validation, and idempotent `markEmailAsVerified()` handling.
- [ ] Run the focused tests and confirm they pass.

### Task 4: Universal password-reset URL

**Files:**
- Modify: `app/Notifications/ResetPasswordNotification.php`
- Modify: `tests/Feature/Api/V1/PhaseOnePasswordTest.php`

**Interfaces:**
- Consumes: `config('auth_links.url')`.
- Produces: reset action URL at `https://laravelquizz.studiobeyam.net/auth/reset-password` containing encoded `token` and `email`.

- [ ] Add a failing notification-content test for the exact HTTPS origin, path, token, and email.
- [ ] Run the focused test and confirm failure.
- [ ] Replace `frontend_url` usage with the authentication-link configuration.
- [ ] Run all password tests and confirm they pass.

### Task 5: HTTPS fallback and mobile association endpoints

**Files:**
- Create: `app/Http/Controllers/Site/AuthLinkController.php`
- Create: `resources/views/site/auth-link.blade.php`
- Modify: `routes/web.php`
- Modify: `config/auth_links.php`
- Test: `tests/Feature/Site/AuthLinkFallbackTest.php`

**Interfaces:**
- Produces: fallback GET routes for `/auth/verify-email` and `/auth/reset-password`, plus `/.well-known/assetlinks.json` when configured.

- [ ] Write failing tests for neutral fallback pages that never echo raw secrets and association responses in enabled and disabled configurations.
- [ ] Run the focused test and confirm failure.
- [ ] Implement the minimal fallback controller/view and configuration-driven association JSON.
- [ ] Run the focused test and confirm it passes.

### Task 6: Regression verification and deployment documentation

**Files:**
- Modify: `.env.example`
- Create: `docs/deployment/email-authentication.md`
- Test: existing authentication test suites.

**Interfaces:**
- Produces: cPanel deployment commands and documented SMTP/cron configuration without credentials.

- [ ] Document SMTP keys, `APP_URL`, `AUTH_LINK_URL`, mobile association keys, `php artisan migrate --force`, cache refresh, and a one-minute cPanel queue cron.
- [ ] Run `php artisan test tests/Feature/Api/V1/AuthTest.php tests/Feature/Api/V1/PhaseOnePasswordTest.php tests/Feature/Api/V1/EmailVerificationTest.php tests/Feature/Site/AuthLinkFallbackTest.php` and confirm all pass.
- [ ] Run `vendor/bin/pint --test` and correct only files touched by this feature.
- [ ] Run the complete `php artisan test` suite and record any unrelated pre-existing failures separately.
