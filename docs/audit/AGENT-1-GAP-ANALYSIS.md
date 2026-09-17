# AGENT 1 - GAP ANALYSIS

## CRITICAL
1. **Entitlement Bypass in Legacy Endpoints**
   - **Current State**: `api/quiz.php` only checks if the user is a `student`. It does not verify if the student has the `tryout_unlimited` or `premium_assessments` entitlement.
   - **Target State**: `api/quiz.php` must verify entitlements via `EntitlementService`.
   - **Impact**: Any registered user can access premium tryouts without paying.

2. **Scoring Determinism in Legacy Code**
   - **Current State**: `api/quiz.php` might be processing scores directly on the frontend or loosely coupled backend.
   - **Target State**: All scoring must be strictly calculated server-side upon submission.
   - **Impact**: Frontend manipulation could fake scores.

## HIGH
1. **AI Tutor Implementation**
   - **Current State**: Currently unverified if it's functional or mock.
   - **Target State**: Real AI proxy to OpenAI/Gemini with entitlement gates and usage quotas.
   - **Impact**: False claims of AI features.

2. **Material Access (Materials.php)**
   - **Current State**: Uses `EntitlementService` but needs strict validation to ensure all materials are protected.
   - **Target State**: Comprehensive entitlement check.

## MEDIUM
1. **SPP (Student Potential Path)**
   - **Current State**: Public endpoint.
   - **Target State**: Differentiate between guest SPP and full SPP.
   - **Impact**: Minimal, as it acts as a lead magnet, but tracking could be lost.

## LOW
1. **Admin Authorization**
   - **Current State**: Needs complete audit.
   - **Target State**: Complete RBAC validation.
   - **Impact**: Admin tools could leak data if cross-tenant isn't enforced.
