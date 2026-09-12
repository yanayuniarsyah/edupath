<?php
// api/EntitlementService.php
// Foundation for Centralized Entitlement Check (Phase 1C)

require_once 'config.php';

class EntitlementService {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Mengecek apakah seorang student memiliki hak akses (entitlement) terhadap fitur tertentu.
     * Menggabungkan pengecekan:
     * 1. Direct student-owned entitlement 
     * 2. Tenant-owned entitlement (jika student adalah bagian dari tenant tersebut)
     * 
     * @param string $student_id UUID student
     * @param string $tenant_id UUID tenant
     * @param string $feature_key Key fitur (e.g., 'tryout_unlimited')
     * @return bool True jika berhak, false jika tidak
     */
    public function hasEntitlement(string $student_id, string $tenant_id, string $feature_key): bool {
        // Cek tabel entitlements untuk kepemilikan student ATAU tenant
        $stmt = $this->pdo->prepare("
            SELECT id FROM entitlements 
            WHERE (student_id = ? OR tenant_id = ?)
              AND feature_key = ? 
              AND revoked_at IS NULL 
              AND (expires_at IS NULL OR expires_at > NOW())
            LIMIT 1
        ");
        $stmt->execute([$student_id, $tenant_id, $feature_key]);
        
        if ($stmt->fetch()) {
            return true; // Punya entitlement
        }

        // LEGACY COMPATIBILITY: Fallback ke pengecekan `students.plan` jika data belum termigrasi penuh
        $stmt_legacy = $this->pdo->prepare("SELECT plan FROM students WHERE id = ?");
        $stmt_legacy->execute([$student_id]);
        $student = $stmt_legacy->fetch();

        if ($student) {
            if ($feature_key === 'feature_quiz' && in_array(strtolower($student['plan']), ['pro', 'premium', 'pro annual'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Memberikan entitlement ke student
     */
    public function grantEntitlement(string $student_id, string $feature_key, ?string $subscription_id = null, ?string $expires_at = null): bool {
        try {
            $id = bin2hex(random_bytes(16));
            $id = substr($id,0,8).'-'.substr($id,8,4).'-'.substr($id,12,4).'-'.substr($id,16,4).'-'.substr($id,20,12);

            $stmt = $this->pdo->prepare("
                INSERT INTO entitlements (id, student_id, feature_key, subscription_id, expires_at) 
                VALUES (?, ?, ?, ?, ?)
            ");
            return $stmt->execute([$id, $student_id, $feature_key, $subscription_id, $expires_at]);
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>
