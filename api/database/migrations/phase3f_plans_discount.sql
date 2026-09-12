-- EduPath: Migration for Phase 3 Plans Discount and Archiving
-- File: api/database/migrations/phase3f_plans_discount.sql

-- ============================================================
-- 1. ADD DISCOUNT TO PLANS
-- Required for Phase 3 Plan/Package Discount feature
-- ============================================================
ALTER TABLE plans
    ADD COLUMN IF NOT EXISTS discount DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER price,
    ADD COLUMN IF NOT EXISTS is_archived TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active;

-- ============================================================
-- 2. MIGRATION TRACKING
-- ============================================================
INSERT IGNORE INTO schema_migrations (migration_name, executed_at)
VALUES ('phase3f_plans_discount', NOW());

