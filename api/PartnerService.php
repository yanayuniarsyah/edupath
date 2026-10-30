<?php
// api/PartnerService.php
// Dual Partner Operating Models & Scoped Operator Authorization (PRD v1.1 Phase 4: AC-08, AC-09, AC-10)

class PartnerService {
    /**
     * Memvalidasi apakah user memiliki penugasan (assignment) aktif untuk resource tertentu.
     *
     * @param PDO $pdo
     * @param string $userId ID user
     * @param string $resourceType 'offering' | 'class' | 'session'
     * @param string $resourceId ID resource yang dituju
     * @param string|null $tenantId Tenant context (optional)
     * @return bool
     */
    public static function hasActiveAssignment($pdo, $userId, $resourceType, $resourceId, $tenantId = null) {
        if (empty($userId) || empty($resourceId)) return false;

        $col = match ($resourceType) {
            'offering' => 'offering_id',
            'class' => 'class_id',
            'session' => 'session_id',
            default => null
        };

        if (!$col) return false;

        // 1. Direct Resource Assignment Check (Fast path)
        $paramsDirect = [$userId, $userId, $resourceId];
        $sqlDirect = "
            SELECT pa.id FROM partner_assignments pa
            LEFT JOIN partner_profiles pp ON pa.partner_id = pp.id
            WHERE (pa.operator_user_id = ? OR pp.user_id = ?)
              AND pa.status = 'active'
              AND (CURRENT_DATE >= pa.start_date)
              AND (pa.end_date IS NULL OR CURRENT_DATE <= pa.end_date)
              AND pa.$col = ?
        ";
        if ($tenantId !== null) {
            $sqlDirect .= " AND (pa.tenant_id = ? OR pa.tenant_id IS NULL)";
            $paramsDirect[] = $tenantId;
        }
        $sqlDirect .= " LIMIT 1";

        try {
            $stmtDirect = $pdo->prepare($sqlDirect);
            $stmtDirect->execute($paramsDirect);
            if ($stmtDirect->fetchColumn()) {
                return true;
            }
        } catch (\Throwable $e) {}

        // 2. Hierarchical Scope Fallback (Parent scopes)
        try {
            if ($resourceType === 'session') {
                $sqlParent = "
                    SELECT pa.id FROM partner_assignments pa
                    LEFT JOIN partner_profiles pp ON pa.partner_id = pp.id
                    WHERE (pa.operator_user_id = ? OR pp.user_id = ?)
                      AND pa.status = 'active'
                      AND (CURRENT_DATE >= pa.start_date)
                      AND (pa.end_date IS NULL OR CURRENT_DATE <= pa.end_date)
                      AND (
                          (pa.session_id IS NULL AND pa.class_id = (SELECT cs.class_id FROM class_sessions cs WHERE cs.id = ?))
                          OR (pa.session_id IS NULL AND pa.class_id IS NULL AND pa.offering_id = (
                              SELECT co.id FROM class_offerings co JOIN class_sessions cs ON co.class_id = cs.class_id WHERE cs.id = ?
                          ))
                          OR (pa.session_id IS NULL AND pa.class_id IS NULL AND pa.offering_id IS NULL AND pa.tenant_id = (
                              SELECT c.tenant_id FROM class_sessions cs JOIN classes c ON cs.class_id = c.id WHERE cs.id = ?
                          ))
                      )
                ";
                $parentParams = [$userId, $userId, $resourceId, $resourceId, $resourceId];
                if ($tenantId !== null) {
                    $sqlParent .= " AND (pa.tenant_id = ? OR pa.tenant_id IS NULL)";
                    $parentParams[] = $tenantId;
                }
                $sqlParent .= " LIMIT 1";
                $stmt = $pdo->prepare($sqlParent);
                $stmt->execute($parentParams);
                return (bool)$stmt->fetchColumn();
            } elseif ($resourceType === 'class') {
                $sqlParent = "
                    SELECT pa.id FROM partner_assignments pa
                    LEFT JOIN partner_profiles pp ON pa.partner_id = pp.id
                    WHERE (pa.operator_user_id = ? OR pp.user_id = ?)
                      AND pa.status = 'active'
                      AND (CURRENT_DATE >= pa.start_date)
                      AND (pa.end_date IS NULL OR CURRENT_DATE <= pa.end_date)
                      AND (
                          (pa.class_id IS NULL AND pa.offering_id = (SELECT co.id FROM class_offerings co WHERE co.class_id = ?))
                          OR (pa.class_id IS NULL AND pa.offering_id IS NULL AND pa.tenant_id = (SELECT c.tenant_id FROM classes c WHERE c.id = ?))
                      )
                ";
                $parentParams = [$userId, $userId, $resourceId, $resourceId];
                if ($tenantId !== null) {
                    $sqlParent .= " AND (pa.tenant_id = ? OR pa.tenant_id IS NULL)";
                    $parentParams[] = $tenantId;
                }
                $sqlParent .= " LIMIT 1";
                $stmt = $pdo->prepare($sqlParent);
                $stmt->execute($parentParams);
                return (bool)$stmt->fetchColumn();
            } elseif ($resourceType === 'offering') {
                $sqlParent = "
                    SELECT pa.id FROM partner_assignments pa
                    LEFT JOIN partner_profiles pp ON pa.partner_id = pp.id
                    WHERE (pa.operator_user_id = ? OR pp.user_id = ?)
                      AND pa.status = 'active'
                      AND (CURRENT_DATE >= pa.start_date)
                      AND (pa.end_date IS NULL OR CURRENT_DATE <= pa.end_date)
                      AND (pa.offering_id IS NULL AND pa.tenant_id = (SELECT co.tenant_id FROM class_offerings co WHERE co.id = ?))
                ";
                $parentParams = [$userId, $userId, $resourceId];
                if ($tenantId !== null) {
                    $sqlParent .= " AND (pa.tenant_id = ? OR pa.tenant_id IS NULL)";
                    $parentParams[] = $tenantId;
                }
                $sqlParent .= " LIMIT 1";
                $stmt = $pdo->prepare($sqlParent);
                $stmt->execute($parentParams);
                return (bool)$stmt->fetchColumn();
            }
        } catch (\Throwable $e) {
            return false;
        }

        return false;
    }

    /**
     * Mengambil seluruh ID kelas yang aktif ditugaskan ke operator ini
     */
    public static function getActiveAssignedClassIds($pdo, $userId) {
        if (empty($userId)) return [];

        $sql = "
            SELECT DISTINCT pa.class_id FROM partner_assignments pa
            LEFT JOIN partner_profiles pp ON pa.partner_id = pp.id
            WHERE (pa.operator_user_id = ? OR pp.user_id = ?)
              AND pa.status = 'active'
              AND (CURRENT_DATE >= pa.start_date)
              AND (pa.end_date IS NULL OR CURRENT_DATE <= pa.end_date)
              AND pa.class_id IS NOT NULL
        ";
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId, $userId]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }
}
