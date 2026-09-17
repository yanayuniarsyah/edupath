# CHANGELOG - AGENT 1

## [1.0.1] - 2026-09-15
### Added
- Created `docs/audit/AGENT-1-FORENSIC-AUDIT.md` to document all findings from the repository audit.
- Created `docs/architecture/EDUPATH-TARGET-ARCHITECTURE.md` to define the target B2C SaaS architecture.
- Created `docs/audit/AGENT-1-GAP-ANALYSIS.md` to prioritize missing features and security vulnerabilities.
- Created `docs/qa/AGENT-1-TEST-REPORT.md` to document the tests executed after patching the vulnerabilities.

### Fixed
- **CRITICAL VULNERABILITY**: `api/quiz.php` was missing an entitlement check, allowing any registered user to bypass the paywall and access premium tryout assessments. Added `EntitlementService` enforcement to the endpoint.

### Refactored
- Validated `api/payment.php` for atomic transaction compliance (Order -> Invoice -> Payment -> Commission). It correctly uses `bcmath` and prevents float errors.
- Validated `api/spp.php` implementation as a top-of-funnel lead magnet.
