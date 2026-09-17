# EDUPATH TARGET ARCHITECTURE

## 1. System Architecture
EduPath is a **B2C SaaS Education Platform** designed as a Modular Monolith.
- **Frontend**: Vue.js (SPA) running on static hosting or CDN, communicating statelessly with the backend API.
- **Backend**: PHP 8+ stateless REST API.
- **Database**: MariaDB / MySQL.

## 2. Authentication & Authorization Model
- **Authentication**: JWT-based stateless authentication. Tokens are short-lived. Refresh tokens are stored in the database.
- **Authorization**: Role-based access control (RBAC) to distinguish `student`, `admin`, and `superadmin`.
- **Tenant Context**: All requests are inherently bound to a `tenant_id` to prevent cross-tenant data leakage (even though it's B2C, tenant isolation prepares for B2B/B2B2C pivots).

## 3. Subscription & Entitlement Architecture
- **Plans**: Defined in the database. Prices are server-authoritative.
- **Subscriptions**: A `student` subscribes to a `plan`. A subscription has a lifecycle (`active`, `expired`, `cancelled`).
- **Entitlements**: Granular access control based on features (e.g., `tryout_unlimited`, `premium_materials`). Entitlements are mapped from Plans. The application checks Entitlements, NOT Plans, before granting access to premium resources.

## 4. Payment Architecture
- **Gateway**: Midtrans (Snap API).
- **Flow**: Order -> Invoice -> Payment Attempt.
- **Webhook**: Server-to-server validation using signature keys. Modifies financial state using pessimistic locking (`FOR UPDATE`) to ensure idempotency.
- **Commission**: Automatically computed in the webhook atomic transaction.

## 5. Question Bank & Assessment Architecture
- **Question Bank**: Questions are stored with exact `sub_materi` and `cognitive_demand`. Provenance (`source_name`, `source_year`) is strictly maintained.
- **Scoring**: Server-side deterministic scoring using IRT or Classical Test Theory, completely hiding correct answers until submission.
- **Assessment**: Endpoints must enforce `premium_assessments` entitlement.

## 6. SPP & AI Tutor Architecture
- **SPP (Student Potential Path)**: Narrative, self-reflective diagnostic tool. Accessible pre-authentication as a lead magnet.
- **AI Tutor**: Entitlement-gated (`ai_adaptive_path`). Server proxy to LLM endpoints to prevent API key exposure and control rate limits.

## 7. Scalability & Security Strategy
- **Security**: Prepared statements, XSS output encoding, CSRF protection, rate limiting on sensitive endpoints.
- **Scalability**: Stateless API ensures horizontal scalability. Database indexes must cover all hot queries (foreign keys, `tenant_id`).
