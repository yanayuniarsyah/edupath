<?php
// api/RevenueSplitEngine.php
// EduPath Revenue Sharing & Waterfall Calculation Engine (PRD v1.2)

class RevenueSplitEngine {

    /**
     * Compute financial waterfall according to PRD v1.2
     *
     * @param array $params
     *   - gross_amount (string|float)
     *   - channel ('AFFILIATE'|'SCHOOL'|'COMMUNITY'|'DIRECT')
     *   - is_renewal (bool)
     *   - gateway_fee (string|float|null)
     *   - gateway_rate (string|float|null, default 0.025 = 2.5%)
     *   - royalty_percentage_rate (string|float|null)
     *   - royalty_usage_amount (string|float|null)
     *   - affiliate_id (string|null)
     *   - branch_tenant_id (string|null)
     *   - school_id (string|null)
     *   - school_commission_rate (string|float|null, e.g. 7.00 or 10.00)
     *   - community_id (string|null)
     *   - community_commission_rate (string|float|null, e.g. 3.00)
     * @return array
     */
    public static function calculate(array $params): array {
        $G = number_format((float)($params['gross_amount'] ?? 0), 2, '.', '');
        $channel = strtoupper($params['channel'] ?? 'DIRECT');
        if (!in_array($channel, ['AFFILIATE', 'SCHOOL', 'COMMUNITY', 'DIRECT'])) {
            $channel = 'DIRECT';
        }
        $isRenewal = !empty($params['is_renewal']);

        // 1. Gateway Fee (F)
        if (isset($params['gateway_fee']) && $params['gateway_fee'] !== null) {
            $F = number_format((float)$params['gateway_fee'], 2, '.', '');
        } else {
            $rate = $params['gateway_rate'] ?? '0.025'; // 2.5% default
            $F = bcmul($G, (string)$rate, 2);
        }

        // 2. Royalty (R)
        if (isset($params['royalty_fee']) && $params['royalty_fee'] !== null) {
            $R = number_format((float)$params['royalty_fee'], 2, '.', '');
            $royaltyPctAmt = '0.00';
            $royaltyUseAmt = $R;
        } else {
            $royaltyPctRate = $params['royalty_percentage_rate'] ?? '0.00';
            $royaltyUseAmt  = number_format((float)($params['royalty_usage_amount'] ?? '0.00'), 2, '.', '');
            $royaltyPctAmt  = bcmul($G, bcdiv((string)$royaltyPctRate, '100', 6), 2);
            $R = bcadd($royaltyPctAmt, $royaltyUseAmt, 2);
        }

        // Pre-check: G - F - R
        $prePool = bcsub(bcsub($G, $F, 2), $R, 2);
        if (bccomp($prePool, '0.00', 2) === -1) {
            throw new InvalidArgumentException("Deficit Waterfall: Gross amount ($G) is insufficient for Gateway fee ($F) and Royalty ($R)");
        }

        $affiliateId     = $params['affiliate_id'] ?? null;
        $branchTenantId  = $params['branch_tenant_id'] ?? null;
        $schoolId        = $params['school_id'] ?? null;
        $communityId     = $params['community_id'] ?? null;

        $hqAmount        = '0.00';
        $affiliateAmount = '0.00';
        $branchAmount    = '0.00';
        $schoolAmount    = '0.00';
        $communityAmount = '0.00';
        $unassignedBranchAmount = '0.00'; // For independent affiliates without branch
        $pool            = '0.00';

        // ----------------------------------------------------------------------
        // CHANNEL 1: AFFILIATE
        // ----------------------------------------------------------------------
        if ($channel === 'AFFILIATE') {
            $pool = $prePool; // P_affiliate = G - F - R

            // Base: 50% HQ, 50% Branch Base
            $hqBase     = bcmul($pool, '0.50', 2);
            $branchBase = bcmul($pool, '0.50', 2);

            // Affiliate commission: 20% first subscription, 10% renewal
            $affRate = $isRenewal ? '0.10' : '0.20';
            $affiliateAmount = bcmul($pool, $affRate, 2);

            // Branch share: Branch Base (50%) - Affiliate commission
            $branchNet = bcsub($branchBase, $affiliateAmount, 2);

            if (!empty($branchTenantId)) {
                $branchAmount = $branchNet;
            } else {
                // Independent Affiliator without Branch:
                // Do not pay branch. HQ retains or holds unassigned amount.
                $unassignedBranchAmount = $branchNet;
                // Add to HQ as unassigned pool retainer
                $hqBase = bcadd($hqBase, $unassignedBranchAmount, 2);
                $branchAmount = '0.00';
            }

            $hqAmount = $hqBase;

            // Balance penny rounding if any
            $distributed = bcadd(bcadd($hqAmount, $branchAmount, 2), $affiliateAmount, 2);
            $diff = bcsub($pool, $distributed, 2);
            if (bccomp($diff, '0.00', 2) !== 0) {
                $hqAmount = bcadd($hqAmount, $diff, 2);
            }
        }

        // ----------------------------------------------------------------------
        // CHANNEL 2: SCHOOL & COMMUNITY
        // ----------------------------------------------------------------------
        elseif ($channel === 'SCHOOL' || $channel === 'COMMUNITY') {
            // S = combined max 10% of G
            $sSchoolRate = (float)($params['school_commission_rate'] ?? ($channel === 'SCHOOL' ? 10.0 : 0.0));
            $sCommRate   = (float)($params['community_commission_rate'] ?? ($channel === 'COMMUNITY' ? 10.0 : 0.0));

            $isCapped = (($sSchoolRate + $sCommRate) > 10.00);
            if ($isCapped) {
                $scale = 10.00 / ($sSchoolRate + $sCommRate);
                $sSchoolRate *= $scale;
                $sCommRate   *= $scale;
            }

            $schoolAmount = bcmul($G, bcdiv((string)$sSchoolRate, '100', 6), 2);
            if ($isCapped) {
                // Ensure exact 10% without penny loss due to truncation
                $maxS = bcmul($G, '0.10', 2);
                $communityAmount = bcsub($maxS, $schoolAmount, 2);
            } else {
                $communityAmount = bcmul($G, bcdiv((string)$sCommRate, '100', 6), 2);
            }
            $S = bcadd($schoolAmount, $communityAmount, 2);

            $pool = bcsub($prePool, $S, 2);
            if (bccomp($pool, '0.00', 2) === -1) {
                throw new InvalidArgumentException("Deficit Waterfall in School channel: Pool after S ($S) is negative");
            }

            $hqAmount = $pool; // Remainder goes to HQ
        }

        // ----------------------------------------------------------------------
        // CHANNEL 3: DIRECT
        // ----------------------------------------------------------------------
        else {
            $pool = $prePool; // P_direct = G - F - R
            $hqAmount = $pool;
        }

        // Final Invariance Verification: Sum must equal Gross
        $sum = bcadd(bcadd(bcadd(bcadd(bcadd($hqAmount, $branchAmount, 2), $affiliateAmount, 2), $schoolAmount, 2), $communityAmount, 2), bcadd($F, $R, 2), 2);
        if (bccomp($sum, $G, 2) !== 0) {
            $drift = bcsub($G, $sum, 2);
            $hqAmount = bcadd($hqAmount, $drift, 2);
        }

        $snapshot = [
            'gross_sales_basis'        => $G,
            'channel'                  => $channel,
            'is_renewal'               => $isRenewal,
            'gateway_fee'              => $F,
            'royalty_fee'              => $R,
            'royalty_percentage_part'  => $royaltyPctAmt,
            'royalty_usage_part'       => $royaltyUseAmt,
            'distributable_pool'       => $pool,
            'hq_amount'                => $hqAmount,
            'affiliate_id'             => $affiliateId,
            'affiliate_amount'         => $affiliateAmount,
            'affiliate_rate_applied'   => ($channel === 'AFFILIATE') ? ($isRenewal ? '10%' : '20%') : '0%',
            'branch_tenant_id'         => $branchTenantId,
            'branch_amount'            => $branchAmount,
            'branch_rate_applied'      => ($channel === 'AFFILIATE' && !empty($branchTenantId)) ? ($isRenewal ? '40%' : '30%') : '0%',
            'unassigned_branch_amount' => $unassignedBranchAmount,
            'school_id'                => $schoolId,
            'school_amount'            => $schoolAmount,
            'community_id'             => $communityId,
            'community_amount'         => $communityAmount,
            'calculated_at'            => date('Y-m-d H:i:s'),
            'prd_version'              => '1.2'
        ];

        return [
            'gross_amount'       => $G,
            'gateway_fee'        => $F,
            'royalty_fee'        => $R,
            'distributable_pool' => $pool,
            'hq_amount'          => $hqAmount,
            'affiliate_amount'   => $affiliateAmount,
            'branch_amount'      => $branchAmount,
            'school_amount'      => $schoolAmount,
            'community_amount'   => $communityAmount,
            'unassigned_branch'  => $unassignedBranchAmount,
            'rule_snapshot'      => $snapshot
        ];
    }
}
