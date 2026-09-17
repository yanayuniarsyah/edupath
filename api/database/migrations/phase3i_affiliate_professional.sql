-- Professional affiliate system hardening
-- Adds operational lifecycle fields, auditability, payout tracking and risk events.

SET @dbname = DATABASE();

-- 1) Upgrade affiliates table for production lifecycle and payout readiness
ALTER TABLE `affiliates`
  ADD COLUMN IF NOT EXISTS `status` VARCHAR(20) NOT NULL DEFAULT 'active' AFTER `commission_rate`,
  ADD COLUMN IF NOT EXISTS `acquisition_rate` DECIMAL(5,2) NOT NULL DEFAULT 20.00 AFTER `status`,
  ADD COLUMN IF NOT EXISTS `recurring_rate` DECIMAL(5,2) NOT NULL DEFAULT 10.00 AFTER `acquisition_rate`,
  ADD COLUMN IF NOT EXISTS `payout_method` VARCHAR(30) NULL DEFAULT NULL AFTER `recurring_rate`,
  ADD COLUMN IF NOT EXISTS `payout_account` VARCHAR(160) NULL DEFAULT NULL AFTER `payout_method`,
  ADD COLUMN IF NOT EXISTS `approved_by` VARCHAR(36) NULL DEFAULT NULL AFTER `payout_account`,
  ADD COLUMN IF NOT EXISTS `approved_at` TIMESTAMP NULL DEFAULT NULL AFTER `approved_by`,
  ADD COLUMN IF NOT EXISTS `suspended_at` TIMESTAMP NULL DEFAULT NULL AFTER `approved_at`,
  ADD COLUMN IF NOT EXISTS `rejected_reason` TEXT NULL DEFAULT NULL AFTER `suspended_at`;

-- Backfill status for legacy records
UPDATE `affiliates`
SET `status` = 'active'
WHERE `status` IS NULL OR `status` = '';

-- 2) Add robust referral tracking and attribution
CREATE TABLE IF NOT EXISTS `affiliate_clicks` (
  `id` VARCHAR(36) NOT NULL,
  `affiliate_id` VARCHAR(36) NOT NULL,
  `referral_code` VARCHAR(50) NOT NULL,
  `landing_path` VARCHAR(255) NOT NULL DEFAULT '/',
  `visitor_hash` CHAR(64) NULL DEFAULT NULL,
  `user_agent_hash` CHAR(64) NULL DEFAULT NULL,
  `utm_source` VARCHAR(64) NULL DEFAULT NULL,
  `utm_medium` VARCHAR(64) NULL DEFAULT NULL,
  `utm_campaign` VARCHAR(64) NULL DEFAULT NULL,
  `session_id` VARCHAR(64) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_affiliate_clicks_affiliate` (`affiliate_id`),
  KEY `idx_affiliate_clicks_created` (`created_at`),
  KEY `idx_affiliate_clicks_code` (`referral_code`),
  CONSTRAINT `fk_affiliate_clicks_affiliate`
    FOREIGN KEY (`affiliate_id`) REFERENCES `affiliates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3) Add payout workflow
CREATE TABLE IF NOT EXISTS `affiliate_payouts` (
  `id` VARCHAR(36) NOT NULL,
  `affiliate_id` VARCHAR(36) NOT NULL,
  `tenant_id` VARCHAR(36) NOT NULL,
  `amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `fee` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `net_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `method` VARCHAR(30) NOT NULL DEFAULT 'bank_transfer',
  `status` VARCHAR(20) NOT NULL DEFAULT 'requested',
  `requested_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `approved_at` TIMESTAMP NULL DEFAULT NULL,
  `processed_at` TIMESTAMP NULL DEFAULT NULL,
  `rejected_at` TIMESTAMP NULL DEFAULT NULL,
  `rejection_reason` TEXT NULL DEFAULT NULL,
  `payout_reference` VARCHAR(120) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_affiliate_payouts_affiliate` (`affiliate_id`),
  KEY `idx_affiliate_payouts_tenant` (`tenant_id`),
  KEY `idx_affiliate_payouts_status` (`status`),
  CONSTRAINT `fk_affiliate_payouts_affiliate`
    FOREIGN KEY (`affiliate_id`) REFERENCES `affiliates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4) Add risk and review tracking
CREATE TABLE IF NOT EXISTS `affiliate_risk_events` (
  `id` VARCHAR(36) NOT NULL,
  `affiliate_id` VARCHAR(36) NOT NULL,
  `event_type` VARCHAR(50) NOT NULL,
  `severity` VARCHAR(20) NOT NULL DEFAULT 'medium',
  `details` JSON NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_affiliate_risk_event_affiliate` (`affiliate_id`),
  KEY `idx_affiliate_risk_event_type` (`event_type`),
  CONSTRAINT `fk_affiliate_risk_events_affiliate`
    FOREIGN KEY (`affiliate_id`) REFERENCES `affiliates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5) Upgrade commissions table for lifecycle and idempotency
ALTER TABLE `commissions`
  ADD COLUMN IF NOT EXISTS `commission_type` VARCHAR(20) NOT NULL DEFAULT 'acquisition' AFTER `status`,
  ADD COLUMN IF NOT EXISTS `available_at` TIMESTAMP NULL DEFAULT NULL AFTER `commission_type`,
  ADD COLUMN IF NOT EXISTS `approved_at` TIMESTAMP NULL DEFAULT NULL AFTER `available_at`,
  ADD COLUMN IF NOT EXISTS `paid_at` TIMESTAMP NULL DEFAULT NULL AFTER `approved_at`,
  ADD COLUMN IF NOT EXISTS `reversed_at` TIMESTAMP NULL DEFAULT NULL AFTER `paid_at`,
  ADD COLUMN IF NOT EXISTS `payout_reference` VARCHAR(120) NULL DEFAULT NULL AFTER `reversed_at`,
  ADD COLUMN IF NOT EXISTS `metadata` JSON NULL DEFAULT NULL AFTER `payout_reference`;

-- Backfill existing data without breaking live values
UPDATE `commissions`
SET `available_at` = `created_at`
WHERE `status` IN ('approved', 'available', 'paid') AND `available_at` IS NULL;

-- Add unique idempotency guard if columns exist in supported schema
-- This protects double-commission on same order or subscription cycle.
ALTER TABLE `commissions`
  ADD UNIQUE INDEX IF NOT EXISTS `uq_commissions_order` (`order_id`);

-- 6) Optional but recommended: affiliate status validation for student referral
ALTER TABLE `students`
  ADD COLUMN IF NOT EXISTS `referral_source` VARCHAR(50) NULL DEFAULT NULL AFTER `referred_by`;

-- 7) Keep audit trail for affiliate governance events
CREATE TABLE IF NOT EXISTS `affiliates_audit_log` (
  `id` VARCHAR(36) NOT NULL,
  `affiliate_id` VARCHAR(36) NULL DEFAULT NULL,
  `actor_id` VARCHAR(36) NULL DEFAULT NULL,
  `tenant_id` VARCHAR(36) NULL DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `metadata` JSON NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_affiliates_audit_affiliate` (`affiliate_id`),
  KEY `idx_affiliates_audit_action` (`action`),
  CONSTRAINT `fk_affiliates_audit_affiliate`
    FOREIGN KEY (`affiliate_id`) REFERENCES `affiliates` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
