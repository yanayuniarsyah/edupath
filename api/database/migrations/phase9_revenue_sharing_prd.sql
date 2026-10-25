-- ==============================================================================
-- PHASE 9: EDUPATH REVENUE SHARING & FINANCIAL LEDGER (PRD v1.2)
-- ==============================================================================

-- 1. Alter orders table to record distribution channel, branch partner, and rules snapshot
ALTER TABLE `orders` 
ADD COLUMN `channel` VARCHAR(30) DEFAULT 'DIRECT' AFTER `affiliate_id`,
ADD COLUMN `branch_tenant_id` VARCHAR(36) DEFAULT NULL AFTER `channel`,
ADD COLUMN `school_id` VARCHAR(36) DEFAULT NULL AFTER `branch_tenant_id`,
ADD COLUMN `community_id` VARCHAR(36) DEFAULT NULL AFTER `school_id`,
ADD COLUMN `is_renewal` TINYINT(1) DEFAULT 0 AFTER `community_id`,
ADD COLUMN `gateway_fee` DECIMAL(15,2) DEFAULT '0.00' AFTER `amount`,
ADD COLUMN `royalty_fee` DECIMAL(15,2) DEFAULT '0.00' AFTER `gateway_fee`,
ADD COLUMN `distributable_pool` DECIMAL(15,2) DEFAULT '0.00' AFTER `royalty_fee`,
ADD COLUMN `rule_snapshot` LONGTEXT DEFAULT NULL AFTER `distributable_pool`;

-- 2. Create financial_ledger table for immutable double-entry style audit
CREATE TABLE IF NOT EXISTS `financial_ledger` (
    `id` VARCHAR(36) NOT NULL PRIMARY KEY,
    `order_id` VARCHAR(36) NOT NULL,
    `tenant_id` VARCHAR(36) DEFAULT NULL,
    `channel` VARCHAR(30) NOT NULL DEFAULT 'DIRECT',
    `entry_type` VARCHAR(50) NOT NULL, 
    -- e.g. GROSS_SALES, GATEWAY_FEE, ROYALTY_EXPENSE, HQ_REVENUE, AFFILIATE_COMMISSION, BRANCH_NET_REVENUE, SCHOOL_COMMISSION, COMMUNITY_COMMISSION, REVERSAL
    `party_type` VARCHAR(50) NOT NULL,
    -- e.g. HQ, BRANCH, AFFILIATE, SCHOOL, COMMUNITY, CONTENT_OWNER, GATEWAY
    `party_id` VARCHAR(100) DEFAULT NULL,
    `amount` DECIMAL(15,2) NOT NULL DEFAULT '0.00',
    `is_credit` TINYINT(1) NOT NULL DEFAULT 1,
    `rule_snapshot` LONGTEXT DEFAULT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'posted', -- posted, reversed
    `reversal_ref_id` VARCHAR(36) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_fl_order` (`order_id`),
    INDEX `idx_fl_party` (`party_type`, `party_id`),
    INDEX `idx_fl_type` (`entry_type`),
    INDEX `idx_fl_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Create content_owners table (Pemilik Soal)
CREATE TABLE IF NOT EXISTS `content_owners` (
    `id` VARCHAR(36) NOT NULL PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(50) DEFAULT NULL,
    `bank_name` VARCHAR(50) DEFAULT NULL,
    `bank_account` VARCHAR(50) DEFAULT NULL,
    `bank_owner` VARCHAR(150) DEFAULT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_co_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Create royalty_contracts table
CREATE TABLE IF NOT EXISTS `royalty_contracts` (
    `id` VARCHAR(36) NOT NULL PRIMARY KEY,
    `owner_id` VARCHAR(36) NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `plan_id` VARCHAR(36) DEFAULT NULL,
    `package_key` VARCHAR(100) DEFAULT NULL,
    `percentage_rate` DECIMAL(5,2) NOT NULL DEFAULT '0.00',
    `per_use_rate` DECIMAL(15,2) NOT NULL DEFAULT '0.00',
    `valid_from` DATE NOT NULL,
    `valid_to` DATE DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `terms_notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_rc_owner` (`owner_id`),
    INDEX `idx_rc_plan` (`plan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Create royalty_accruals table
CREATE TABLE IF NOT EXISTS `royalty_accruals` (
    `id` VARCHAR(36) NOT NULL PRIMARY KEY,
    `contract_id` VARCHAR(36) NOT NULL,
    `owner_id` VARCHAR(36) NOT NULL,
    `order_id` VARCHAR(36) NOT NULL,
    `percentage_amount` DECIMAL(15,2) NOT NULL DEFAULT '0.00',
    `usage_amount` DECIMAL(15,2) NOT NULL DEFAULT '0.00',
    `total_amount` DECIMAL(15,2) NOT NULL DEFAULT '0.00',
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ra_order` (`order_id`),
    INDEX `idx_ra_owner` (`owner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Create communities table
CREATE TABLE IF NOT EXISTS `communities` (
    `id` VARCHAR(36) NOT NULL PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `school_id` VARCHAR(36) DEFAULT NULL,
    `contact_name` VARCHAR(150) DEFAULT NULL,
    `email` VARCHAR(150) DEFAULT NULL,
    `phone` VARCHAR(50) DEFAULT NULL,
    `revenue_share_rate` DECIMAL(5,2) NOT NULL DEFAULT '3.00',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
