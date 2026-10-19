# Phase 1: Baseline Audit EduPath.co.id

## 1. Repository & Structure
- **URL**: https://github.com/yanayuniarsyah/edupath.git
- **Production URL**: https://edupath.co.id/
- **Frontend**: Vue.js (Vite, Vue 3, TailwindCSS, Phosphor Icons)
- **Backend**: Native PHP (located in `api/` directory)
- **Database**: MySQL/MariaDB (schema scripts in `install_db.php`, `schema_dump.json`)
- **Deployment**: Custom FTP deployment script (`deploy-ftp.js`) + cPanel Git Version Control for some parts.
- **Build Process**: `npm run build` generates `dist/` directory, which is currently committed to Git to serve as the document root payload for cPanel Git deployments.

## 2. Environment & Configuration
- Configuration is driven by `.env` files (e.g. `VITE_API_BASE_URL` for frontend, backend config in `api/config.php` or `api/env.php`).
- **PROPOSED**: Ensure `.env` is properly isolated in production and not exposed via public directory.

## 3. Authentication & Authorization
- **Implementation**: JWT-based authentication (`api/jwt.php`, `api/auth.php`).
- **Roles**: `superadmin`, `admin`, `affiliate`, `student`. (Extracted dynamically in Vue via `extractRoleFromToken`).
- **NOT VALIDATED**: Whether backend strictly validates roles on all endpoints or relies on frontend guards.

## 4. Payment Flow
- **Provider**: Midtrans
- **Implementation**: Handled in `api/payment.php`.
- **Flow**: Package selection -> Midtrans Snap -> Webhook (`notification`/`handling`) -> Update entitlement.
- **PROPOSED**: Audit webhook idempotency to ensure duplicate notifications do not credit accounts twice.

## 5. Entitlement / Premium Access
- Handled by `api/EntitlementService.php`.
- **NOT VALIDATED**: Need to verify if expired entitlements are automatically revoked or if payment failure can inadvertently grant access.

## 6. API Audit
Major APIs found:
- `auth.php`: Registration, login, validation.
- `payment.php`: Order creation, webhook handling.
- `admin.php`: Admin operations, student management.
- `spp.php` / `spp_engine.php`: Student Potential Path engine.
- `quiz.php`: TryOut/CBT handling.

## 7. Next Steps
- Validate endpoint security (authentication/authorization enforcement).
- Smoke test payment flow using Midtrans Sandbox.
- Audit database schema for orphaned records and missing constraints.
