-- EduPath: Migration untuk Password Reset + Subscriptions + Entitlements
-- File: api/database/migrations/phase1b_foundation.sql
-- Jalankan via: php api/migrate_mobile.php
-- Tanggal: 2026-09-10

-- ============================================================
-- 1. PASSWORD RESET TOKENS
-- ============================================================
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id            CHAR(36)     NOT NULL DEFAULT (UUID()),
    email         VARCHAR(255) NOT NULL,
    token_hash    VARCHAR(64)  NOT NULL COMMENT 'SHA-256 hash dari raw token yang dikirim ke email',
    expires_at    DATETIME     NOT NULL,
    used_at       DATETIME     NULL     DEFAULT NULL COMMENT 'NULL = belum digunakan',
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_token_hash (token_hash),
    INDEX idx_email (email),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. PACKAGES / PLANS (rename/extend tabel plans yang sudah ada)
-- Tambahkan kolom yang diperlukan untuk subscription lifecycle
-- ============================================================
ALTER TABLE plans
    ADD COLUMN IF NOT EXISTS billing_period  ENUM('monthly','quarterly','annual','lifetime') NOT NULL DEFAULT 'monthly' AFTER duration,
    ADD COLUMN IF NOT EXISTS is_active        TINYINT(1) NOT NULL DEFAULT 1 AFTER features,
    ADD COLUMN IF NOT EXISTS description      TEXT NULL AFTER name,
    ADD COLUMN IF NOT EXISTS stripe_price_id  VARCHAR(255) NULL COMMENT 'Untuk integrasi Stripe di masa depan',
    ADD COLUMN IF NOT EXISTS updated_at       DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- ============================================================
-- 3. SUBSCRIPTIONS
-- Sumber kebenaran subscription — BUKAN students.plan
-- ============================================================
CREATE TABLE IF NOT EXISTS subscriptions (
    id                 CHAR(36)     NOT NULL DEFAULT (UUID()),
    student_id         CHAR(36)     NOT NULL,
    plan_id            CHAR(36)     NOT NULL,
    status             ENUM('active','expired','cancelled','pending','trial') NOT NULL DEFAULT 'pending',
    billing_period     ENUM('monthly','quarterly','annual','lifetime') NOT NULL DEFAULT 'monthly',
    started_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at         DATETIME     NULL     COMMENT 'NULL = lifetime',
    cancelled_at       DATETIME     NULL,
    cancel_reason      VARCHAR(255) NULL,
    payment_reference  VARCHAR(255) NULL     COMMENT 'Midtrans order_id yang meng-aktifkan subscription ini',
    plan_name_snapshot VARCHAR(100) NULL     COMMENT 'Snapshot nama plan saat subscribe (immutable history)',
    price_snapshot     DECIMAL(12,2) NULL    COMMENT 'Snapshot harga saat subscribe',
    created_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_student (student_id),
    INDEX idx_status  (status),
    INDEX idx_expires (expires_at),
    CONSTRAINT fk_sub_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_sub_plan    FOREIGN KEY (plan_id)    REFERENCES plans(id)    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. ENTITLEMENTS
-- Feature access yang aktif untuk seorang siswa
-- Diupdate setiap kali subscription berubah
-- ============================================================
CREATE TABLE IF NOT EXISTS entitlements (
    id             CHAR(36)     NOT NULL DEFAULT (UUID()),
    student_id     CHAR(36)     NOT NULL,
    feature_key    VARCHAR(100) NOT NULL COMMENT 'e.g.: tryout_unlimited, ai_tutor, drills_advanced',
    subscription_id CHAR(36)   NULL     COMMENT 'NULL = granted manual/trial',
    granted_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at     DATETIME     NULL     COMMENT 'NULL = tidak expires',
    revoked_at     DATETIME     NULL,
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_student_feature (student_id, feature_key),
    INDEX idx_student  (student_id),
    INDEX idx_feature  (feature_key),
    INDEX idx_expires  (expires_at),
    CONSTRAINT fk_ent_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. PLAN ENTITLEMENT MAPPINGS
-- Fitur apa yang diberikan oleh sebuah plan
-- ============================================================
CREATE TABLE IF NOT EXISTS plan_entitlements (
    id          CHAR(36)     NOT NULL DEFAULT (UUID()),
    plan_id     CHAR(36)     NOT NULL,
    feature_key VARCHAR(100) NOT NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_plan_feature (plan_id, feature_key),
    CONSTRAINT fk_pe_plan FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. UPDATE ORDERS TABLE
-- Tambah plan_id (FK) untuk menggantikan plan_name string
-- plan_name tetap ada sebagai snapshot (backward compat)
-- ============================================================
ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS plan_id        CHAR(36)      NULL AFTER plan_name,
    ADD COLUMN IF NOT EXISTS subscription_id CHAR(36)     NULL COMMENT 'Subscription yang dibuat dari order ini',
    ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50)   NULL,
    ADD COLUMN IF NOT EXISTS paid_at        DATETIME      NULL,
    ADD COLUMN IF NOT EXISTS updated_at     DATETIME      NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ADD INDEX IF NOT EXISTS idx_student     (student_id),
    ADD INDEX IF NOT EXISTS idx_status      (status);

-- ============================================================
-- 7. MIGRATION TRACKING
-- ============================================================
INSERT IGNORE INTO schema_migrations (migration_name, executed_at)
VALUES ('phase1b_foundation', NOW());
