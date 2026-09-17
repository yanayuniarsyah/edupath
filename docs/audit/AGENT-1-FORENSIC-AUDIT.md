# AGENT 1 - FORENSIC AUDIT

## 1. Executive Summary
This document outlines the forensic audit findings of the `edupath` repository as part of the Multi-Agent Autonomous Engineering process. The focus is on security, business logic, payment flows, subscription lifecycle, and architectural gaps in the current implementation.

## 2. Authentication & Authorization
- **Status**: VULNERABLE (in legacy endpoints)
- **Findings**:
  - `api/auth.php` uses `authenticate()` which validates JWT tokens securely. Rate limiting is applied correctly.
  - However, `api/quiz.php` checks role (`$payload->role !== 'student'`), but completely bypasses `EntitlementService`. This allows any registered user (even free users) to access Tryout and Quiz endpoints unconditionally.
  - Entitlements are correctly checked in the newer `api/v1/assessments.php` (`premium_assessments` feature key).
  - Admin endpoints (`api/admin.php`) need verification to ensure tenant isolation is strictly enforced.

## 3. Payment & Subscription Architecture
- **Status**: STRONG (but needs integration across endpoints)
- **Findings**:
  - `api/payment.php` implements an atomic transaction covering `orders`, `invoices`, and `payments`.
  - Midtrans Webhook correctly verifies signatures (`signature_key`) and uses pessimistic locking (`FOR UPDATE`) to prevent race conditions.
  - Financial calculations use `bcmath` to avoid float precision issues.
  - Subscriptions and Entitlements are idempotently provisioned inside the webhook upon a `paid` event.
  - **Gap**: Legacy features (like `tryout_unlimited` or `premium_materials`) might not consistently map to the new `EntitlementService` across legacy endpoints (`api/quiz.php`, `api/materials.php`).

## 4. Affiliate Commission
- **Status**: IMPLEMENTED
- **Findings**:
  - Commissions are calculated using `bcmath` in `api/payment.php`.
  - First transaction vs renewal is checked via a query. Rates are correctly set (20% vs 10%).
  - Cross-tenant attribution is guarded against.
  - The calculation is strictly server-side, not trusting frontend values.

## 5. Scoring Engine & Question Bank
- **Status**: MIXED
- **Findings**:
  - Legacy `quiz.php` calculates scores at the end. The V1 endpoints (`api/v1/assessments.php`) seem to delegate to `api/v1/scoring/ScoringEngine.php` (need to verify).
  - The Question Bank allows metadata like `source_name` and `source_year`, satisfying the "provenance" requirement.

## 6. SPP & AI Tutor
- **Status**: PARTIAL
- **Findings**:
  - `api/spp.php` allows public access. This acts as a top-of-funnel diagnostic tool. The logic appears deterministic.
  - AI Tutor status requires deeper investigation to ensure it's not just a mock endpoint.

## 7. Next Steps
- Enforce `EntitlementService` in `api/quiz.php`.
- Check `api/materials.php` for correct entitlement checks.
- Investigate `api/v1/scoring/ScoringEngine.php` for determinism.
- Audit AI tutor implementation.
