<?php
// api/RoyaltyRegistry.php
// Question Owner & Content Royalty Management Service (PRD v1.2)

class RoyaltyRegistry {

    private static function mkuuid(): string {
        $b = bin2hex(random_bytes(16));
        return substr($b,0,8).'-'.substr($b,8,4).'-'.substr($b,12,4).'-'.substr($b,16,4).'-'.substr($b,20,12);
    }

    /**
     * Compute applicable royalties for a plan / product order
     *
     * @param PDO $pdo
     * @param string|null $planId
     * @param string $grossAmount
     * @return array
     *   - total_royalty: string
     *   - percentage_total: string
     *   - usage_total: string
     *   - contracts: array of matched contracts
     */
    public static function calculateRoyalties(PDO $pdo, ?string $planId, string $grossAmount): array {
        $today = date('Y-m-d');
        $contracts = [];
        try {
            // Match active contracts for this plan OR global contracts
            $stmt = $pdo->prepare("
                SELECT * FROM royalty_contracts 
                WHERE is_active = 1 
                  AND valid_from <= ? 
                  AND (valid_to IS NULL OR valid_to >= ?)
                  AND (plan_id = ? OR plan_id IS NULL)
            ");
            $stmt->execute([$today, $today, $planId]);
            $contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $contracts = [];
        }

        $totalRoyalty = '0.00';
        $totalPct     = '0.00';
        $totalUse     = '0.00';
        $matched      = [];

        foreach ($contracts as $c) {
            $pctAmt = bcmul($grossAmount, bcdiv((string)$c['percentage_rate'], '100', 6), 2);
            $useAmt = number_format((float)$c['per_use_rate'], 2, '.', '');
            $itemTotal = bcadd($pctAmt, $useAmt, 2);

            $totalPct = bcadd($totalPct, $pctAmt, 2);
            $totalUse = bcadd($totalUse, $useAmt, 2);
            $totalRoyalty = bcadd($totalRoyalty, $itemTotal, 2);

            $matched[] = [
                'contract_id'        => $c['id'],
                'owner_id'           => $c['owner_id'],
                'percentage_rate'    => $c['percentage_rate'],
                'per_use_rate'       => $c['per_use_rate'],
                'percentage_amount'  => $pctAmt,
                'usage_amount'       => $useAmt,
                'total_amount'       => $itemTotal
            ];
        }

        return [
            'total_royalty'    => $totalRoyalty,
            'percentage_total' => $totalPct,
            'usage_total'      => $totalUse,
            'contracts'        => $matched
        ];
    }

    /**
     * Record royalty accruals upon order settlement
     */
    public static function recordAccruals(PDO $pdo, string $orderId, array $matchedContracts): void {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO royalty_accruals 
                    (id, contract_id, owner_id, order_id, percentage_amount, usage_amount, total_amount, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
            ");

            foreach ($matchedContracts as $mc) {
                $id = self::mkuuid();
                $stmt->execute([
                    $id,
                    $mc['contract_id'],
                    $mc['owner_id'],
                    $orderId,
                    $mc['percentage_amount'],
                    $mc['usage_amount'],
                    $mc['total_amount']
                ]);
            }
        } catch (\Throwable $e) {}
    }
}
