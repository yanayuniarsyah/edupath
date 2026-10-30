-- ==============================================================================
-- Migration: Phase 11 — Purchase & Full-Price Upgrade Lifecycle
-- PRD: EDUPATH UNIFIED PRODUCT ARCHITECTURE (v1.1, Ratified)
-- Decisions: B2 (Full-Price Upgrade), C5 (Order Snapshot), D2-D4 (Commerce Lifecycle)
-- Acceptance Criteria: AC-04, AC-05, AC-06, AC-07
-- ==============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

-- 1. ADD SNAPSHOT & UPGRADE AUDIT COLUMNS TO ORDERS
ALTER TABLE `orders`
  ADD COLUMN IF NOT EXISTS `order_type` ENUM('new_purchase', 'upgrade', 'renewal') NOT NULL DEFAULT 'new_purchase' AFTER `is_renewal`,
  ADD COLUMN IF NOT EXISTS `package_version` INT NOT NULL DEFAULT 1 AFTER `billing_cycle_snapshot`,
  ADD COLUMN IF NOT EXISTS `purchase_snapshot` JSON NULL COMMENT 'Snapshot komponen, kurikulum, mode belajar, dan durasi saat order dibuat' AFTER `package_version`,
  ADD COLUMN IF NOT EXISTS `upgrade_from_order_id` VARCHAR(36) NULL COMMENT 'Referensi order sebelumnya jika transaksi ini merupakan upgrade paket' AFTER `purchase_snapshot`,
  ADD KEY IF NOT EXISTS `idx_order_type` (`order_type`),
  ADD KEY IF NOT EXISTS `idx_upgrade_from` (`upgrade_from_order_id`);

-- 2. CREATE UPGRADE AUDIT LOG TABLE
-- Merekam jejak audit transisi upgrade paket untuk kepatuhan B2 & AC-06
CREATE TABLE IF NOT EXISTS `upgrade_audit_logs` (
  `id` VARCHAR(36) NOT NULL PRIMARY KEY,
  `student_id` VARCHAR(36) NOT NULL,
  `tenant_id` VARCHAR(36) NULL,
  `from_order_id` VARCHAR(36) NULL,
  `from_plan_id` VARCHAR(36) NULL,
  `to_order_id` VARCHAR(36) NOT NULL,
  `to_plan_id` VARCHAR(36) NOT NULL,
  `from_plan_name` VARCHAR(150) NULL,
  `to_plan_name` VARCHAR(150) NOT NULL,
  `upgrade_price_paid` DECIMAL(15,2) NOT NULL COMMENT 'Harga penuh paket tujuan tanpa prorata (AC-04, AC-05)',
  `previous_expires_at` DATETIME NULL COMMENT 'Masa aktif paket lama (tidak dibatalkan, AC-06)',
  `new_expires_at` DATETIME NULL COMMENT 'Masa aktif paket baru dengan periode sendiri (AC-06)',
  `metadata` JSON NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `ual_student_fk` (`student_id`),
  KEY `ual_to_order_fk` (`to_order_id`),
  CONSTRAINT `ual_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;
