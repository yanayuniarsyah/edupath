<?php
// api/FinancialLedger.php
// Immutable Financial Ledger Service (PRD v1.2)

class FinancialLedger {

    private static function mkuuid(): string {
        $b = bin2hex(random_bytes(16));
        return substr($b,0,8).'-'.substr($b,8,4).'-'.substr($b,12,4).'-'.substr($b,16,4).'-'.substr($b,20,12);
    }

    /**
     * Post order revenue split to financial_ledger atomically
     *
     * @param PDO $pdo
     * @param string $orderId
     * @param string|null $tenantId
     * @param string $channel
     * @param array $splitResult (output of RevenueSplitEngine::calculate)
     * @return array of generated ledger entry IDs
     */
    public static function postOrderSplit(PDO $pdo, string $orderId, ?string $tenantId, string $channel, array $splitResult): array {
        // Idempotency: verify no entries already posted for this order
        $check = $pdo->prepare("SELECT id FROM financial_ledger WHERE order_id = ? AND entry_type = 'GROSS_SALES' LIMIT 1");
        $check->execute([$orderId]);
        if ($check->fetch()) {
            // Already posted
            return [];
        }

        $snapshotJson = json_encode($splitResult['rule_snapshot']);
        $entryIds = [];

        $stmt = $pdo->prepare("
            INSERT INTO financial_ledger 
                (id, order_id, tenant_id, channel, entry_type, party_type, party_id, amount, is_credit, rule_snapshot, status, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'posted', ?)
        ");

        // 1. GROSS_SALES (Credit: Total incoming payment)
        $id = self::mkuuid();
        $stmt->execute([
            $id, $orderId, $tenantId, $channel, 'GROSS_SALES', 'STUDENT_PAYMENT', null,
            $splitResult['gross_amount'], 1, $snapshotJson, 'Gross student payment received'
        ]);
        $entryIds[] = $id;

        // 2. GATEWAY_FEE (Debit expense to Payment Gateway)
        if (bccomp($splitResult['gateway_fee'], '0.00', 2) > 0) {
            $id = self::mkuuid();
            $stmt->execute([
                $id, $orderId, $tenantId, $channel, 'GATEWAY_FEE', 'GATEWAY', 'MIDTRANS',
                $splitResult['gateway_fee'], 0, $snapshotJson, 'Payment gateway processing fee'
            ]);
            $entryIds[] = $id;
        }

        // 3. ROYALTY_EXPENSE (Debit expense to Question/Content Owners)
        if (bccomp($splitResult['royalty_fee'], '0.00', 2) > 0) {
            $id = self::mkuuid();
            $stmt->execute([
                $id, $orderId, $tenantId, $channel, 'ROYALTY_EXPENSE', 'CONTENT_OWNER', null,
                $splitResult['royalty_fee'], 0, $snapshotJson, 'Content & Question royalty obligation'
            ]);
            $entryIds[] = $id;
        }

        // 4. HQ_REVENUE (Net allocation to HQ)
        if (bccomp($splitResult['hq_amount'], '0.00', 2) > 0) {
            $id = self::mkuuid();
            $stmt->execute([
                $id, $orderId, $tenantId, $channel, 'HQ_REVENUE', 'HQ', 'HQ_CENTRAL',
                $splitResult['hq_amount'], 1, $snapshotJson, 'Platform HQ revenue share'
            ]);
            $entryIds[] = $id;
        }

        // 5. AFFILIATE_COMMISSION (Credit payable to Affiliator)
        if (bccomp($splitResult['affiliate_amount'], '0.00', 2) > 0) {
            $id = self::mkuuid();
            $stmt->execute([
                $id, $orderId, $tenantId, $channel, 'AFFILIATE_COMMISSION', 'AFFILIATE', $splitResult['rule_snapshot']['affiliate_id'] ?? null,
                $splitResult['affiliate_amount'], 1, $snapshotJson, 'Affiliator marketing commission'
            ]);
            $entryIds[] = $id;
        }

        // 6. BRANCH_NET_REVENUE (Credit payable to Branch Partner)
        if (bccomp($splitResult['branch_amount'], '0.00', 2) > 0) {
            $id = self::mkuuid();
            $stmt->execute([
                $id, $orderId, $tenantId, $channel, 'BRANCH_NET_REVENUE', 'BRANCH', $splitResult['rule_snapshot']['branch_tenant_id'] ?? null,
                $splitResult['branch_amount'], 1, $snapshotJson, 'Branch partner net margin (50% base - affiliate cut)'
            ]);
            $entryIds[] = $id;
        }

        // 7. SCHOOL_COMMISSION (Credit payable to School)
        if (bccomp($splitResult['school_amount'], '0.00', 2) > 0) {
            $id = self::mkuuid();
            $stmt->execute([
                $id, $orderId, $tenantId, $channel, 'SCHOOL_COMMISSION', 'SCHOOL', $splitResult['rule_snapshot']['school_id'] ?? null,
                $splitResult['school_amount'], 1, $snapshotJson, 'Institutional school revenue share'
            ]);
            $entryIds[] = $id;
        }

        // 8. COMMUNITY_COMMISSION (Credit payable to Community)
        if (bccomp($splitResult['community_amount'], '0.00', 2) > 0) {
            $id = self::mkuuid();
            $stmt->execute([
                $id, $orderId, $tenantId, $channel, 'COMMUNITY_COMMISSION', 'COMMUNITY', $splitResult['rule_snapshot']['community_id'] ?? null,
                $splitResult['community_amount'], 1, $snapshotJson, 'School business community marketing fee'
            ]);
            $entryIds[] = $id;
        }

        return $entryIds;
    }

    /**
     * Reverse order ledger entries for refunds / chargebacks
     * Rules: Never delete posted entries. Create reversing counter-entries.
     *
     * @param PDO $pdo
     * @param string $orderId
     * @param string $reason
     * @return int Number of reversal entries created
     */
    public static function reverseOrderLedger(PDO $pdo, string $orderId, string $reason = 'Order Refund / Chargeback'): int {
        // Fetch all posted entries for this order that have not been reversed yet
        $stmt = $pdo->prepare("SELECT * FROM financial_ledger WHERE order_id = ? AND status = 'posted'");
        $stmt->execute([$orderId]);
        $postedEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($postedEntries)) {
            return 0;
        }

        $revStmt = $pdo->prepare("
            INSERT INTO financial_ledger 
                (id, order_id, tenant_id, channel, entry_type, party_type, party_id, amount, is_credit, rule_snapshot, status, reversal_ref_id, notes)
            VALUES (?, ?, ?, ?, 'REVERSAL', ?, ?, ?, ?, ?, 'reversed', ?, ?)
        ");

        $reversedCount = 0;
        foreach ($postedEntries as $entry) {
            $revId = self::mkuuid();
            // Invert credit flag: original credit (1) becomes 0, original debit (0) becomes 1
            $invertedCredit = ($entry['is_credit'] == 1) ? 0 : 1;
            $note = "Reversal of {$entry['entry_type']} ({$entry['id']}) - $reason";

            $revStmt->execute([
                $revId,
                $entry['order_id'],
                $entry['tenant_id'],
                $entry['channel'],
                $entry['party_type'],
                $entry['party_id'],
                $entry['amount'],
                $invertedCredit,
                $entry['rule_snapshot'],
                $entry['id'],
                $note
            ]);

            // Mark original entry status as reversed
            $pdo->prepare("UPDATE financial_ledger SET status = 'reversed', reversal_ref_id = ? WHERE id = ?")
                ->execute([$revId, $entry['id']]);

            $reversedCount++;
        }

        return $reversedCount;
    }

    /**
     * Get net settled balance for a party (affiliate, branch, school, hq)
     */
    public static function getPartyBalance(PDO $pdo, string $partyType, string $partyId): string {
        $stmt = $pdo->prepare("
            SELECT 
                SUM(CASE WHEN is_credit = 1 AND status = 'posted' THEN amount 
                         WHEN is_credit = 0 AND status = 'posted' THEN -amount 
                         ELSE 0 END) as net_balance
            FROM financial_ledger 
            WHERE party_type = ? AND party_id = ?
        ");
        $stmt->execute([$partyType, $partyId]);
        $res = $stmt->fetchColumn();
        return number_format((float)($res ?: 0), 2, '.', '');
    }
}
