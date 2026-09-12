<?php
// api/spp_engine.php

class SPPScoringEngine {
    
    // Reverse scale: 6 - raw
    private static $reverse_items = [
        'SRL_CTRL_03',
        'SRL_REFL_03',
        'GRT_PERS_04',
        'GRT_PERS_05'
    ];

    // Facet definitions
    private static $facets = [
        'task_mastery' => ['EFC_MAST_01', 'EFC_MAST_02'],
        'challenge' => ['EFC_CHAL_01', 'EFC_CHAL_02'],
        'forethought' => ['SRL_PLAN_01', 'SRL_PLAN_02', 'SRL_PLAN_03'],
        'performance_control' => ['SRL_CTRL_01', 'SRL_CTRL_02', 'SRL_CTRL_03'],
        'reflection' => ['SRL_REFL_01', 'SRL_REFL_02', 'SRL_REFL_03'],
        'perseverance' => ['GRT_PERS_01', 'GRT_PERS_02', 'GRT_PERS_03', 'GRT_PERS_04', 'GRT_PERS_05']
    ];
    
    // RIASEC items mapping
    private static $riasec_items = [
        'R' => ['INT_REAL_01', 'INT_REAL_02'],
        'I' => ['INT_INVS_01', 'INT_INVS_02'],
        'A' => ['INT_ARTS_01', 'INT_ARTS_02'],
        'S' => ['INT_SOCL_01', 'INT_SOCL_02'],
        'E' => ['INT_ENTR_01', 'INT_ENTR_02'],
        'C' => ['INT_CONV_01', 'INT_CONV_02']
    ];

    public static function validateRawResponses($raw_responses) {
        if (!is_array($raw_responses)) {
            return ["valid" => false, "error" => "Input must be an array."];
        }

        $all_expected_items = [];
        foreach (self::$facets as $items) {
            $all_expected_items = array_merge($all_expected_items, $items);
        }
        foreach (self::$riasec_items as $items) {
            $all_expected_items = array_merge($all_expected_items, $items);
        }

        if (count($raw_responses) !== 30) {
            return ["valid" => false, "error" => "Payload must contain exactly 30 items."];
        }

        foreach ($raw_responses as $key => $val) {
            if (!in_array($key, $all_expected_items)) {
                return ["valid" => false, "error" => "Unknown item ID: $key"];
            }
            if (!is_int($val) || $val < 1 || $val > 5) {
                return ["valid" => false, "error" => "Value for $key must be an integer between 1 and 5."];
            }
        }

        return ["valid" => true];
    }

    public static function processAnswers($raw_responses) {
        $processed = [];
        foreach ($raw_responses as $key => $value) {
            $val = (float)$value;
            if (in_array($key, self::$reverse_items)) {
                $processed[$key] = 6.0 - $val;
            } else {
                $processed[$key] = $val;
            }
        }
        return $processed;
    }

    private static function calculateMean($items, $processed_responses) {
        $sum = 0;
        $count = 0;
        foreach ($items as $item) {
            if (!isset($processed_responses[$item])) {
                return null; // Missing data
            }
            $sum += $processed_responses[$item];
            $count++;
        }
        return $count > 0 ? $sum / $count : null;
    }

    public static function score($raw_responses) {
        $validation = self::validateRawResponses($raw_responses);
        if (!$validation['valid']) {
            return ["status" => "INVALID_PAYLOAD", "error" => $validation['error']];
        }

        $processed = self::processAnswers($raw_responses);
        
        $facet_scores = [];
        foreach (self::$facets as $facet_name => $items) {
            $score = self::calculateMean($items, $processed);
            if ($score === null) {
                return ["status" => "INSUFFICIENT_DATA"];
            }
            $facet_scores[$facet_name] = $score;
        }

        $potential_raw = ($facet_scores['task_mastery'] + $facet_scores['challenge']) / 2.0;
        $learning_raw = ($facet_scores['forethought'] + $facet_scores['performance_control'] + $facet_scores['reflection']) / 3.0;
        $growth_raw = $facet_scores['perseverance'];

        $dimensions = [
            'potential' => [
                'raw_score' => $potential_raw,
                'transformed_score' => (($potential_raw - 1.0) / 4.0) * 100.0
            ],
            'learning' => [
                'raw_score' => $learning_raw,
                'transformed_score' => (($learning_raw - 1.0) / 4.0) * 100.0
            ],
            'growth' => [
                'raw_score' => $growth_raw,
                'transformed_score' => (($growth_raw - 1.0) / 4.0) * 100.0
            ]
        ];

        // RIASEC calculation
        $riasec_scores = [];
        foreach (self::$riasec_items as $type => $items) {
            $score = self::calculateMean($items, $processed);
            if ($score === null) {
                return ["status" => "INSUFFICIENT_DATA"];
            }
            $riasec_scores[$type] = $score;
        }

        // RIASEC Ranking logic with fixed tie break R-I-A-S-E-C
        $tie_break_order = ['R', 'I', 'A', 'S', 'E', 'C'];
        $riasec_list = [];
        foreach ($tie_break_order as $idx => $type) {
            $riasec_list[] = [
                'type' => $type,
                'score' => $riasec_scores[$type],
                'tie_rank' => 6 - $idx // Higher tie_rank wins tie
            ];
        }

        usort($riasec_list, function($a, $b) {
            // Epsilon used strictly as a computational precision mechanism for float comparison, not a psychometric rule.
            if (abs($a['score'] - $b['score']) < 0.0001) {
                return $b['tie_rank'] <=> $a['tie_rank'];
            }
            return $b['score'] <=> $a['score'];
        });

        $riasec_top3 = [
            $riasec_list[0]['type'],
            $riasec_list[1]['type'],
            $riasec_list[2]['type']
        ];

        return [
            "status" => "SCORED",
            "scoring_version" => "SPP-SCORE-1.0.0",
            "facets" => $facet_scores,
            "dimensions" => $dimensions,
            "riasec" => $riasec_scores,
            "riasec_top3" => $riasec_top3
        ];
    }
}

class SPPProfileEngine {
    
    private static function getBand($score) {
        // Handle float precision for boundary (e.g. 39.999 is < 40)
        // using an epsilon to prevent float math errors, but < 40 is strict.
        if ($score < 40.0) return "FOUNDATIONAL";
        if ($score >= 75.0) return "PROMINENT";
        return "DEVELOPING";
    }

    private static function getBandKeySuffix($band) {
        if ($band === "FOUNDATIONAL") return "B1";
        if ($band === "DEVELOPING") return "B2";
        return "B3";
    }

    public static function generateProfile($scoring_output) {
        if ($scoring_output['status'] !== 'SCORED') {
            return null; // No profile for insufficient data
        }

        $dims = $scoring_output['dimensions'];
        $p = $dims['potential']['transformed_score'];
        $l = $dims['learning']['transformed_score'];
        $g = $dims['growth']['transformed_score'];

        $bands = [
            'potential' => self::getBand($p),
            'learning' => self::getBand($l),
            'growth' => self::getBand($g)
        ];

        // Three-way tie
        // Epsilon used strictly as a computational precision mechanism for float comparison, not a psychometric rule.
        if (abs($p - $l) < 0.0001 && abs($l - $g) < 0.0001) {
            $relative_strength = null;
            $development_area = null;
        } else {
            // Priority for strength: Learning > Growth > Potential
            $arr_max = [
                ['name' => 'LEARNING', 'score' => $l, 'tie' => 3],
                ['name' => 'GROWTH', 'score' => $g, 'tie' => 2],
                ['name' => 'POTENTIAL', 'score' => $p, 'tie' => 1]
            ];
            usort($arr_max, function($a, $b) {
                // Epsilon used strictly as a computational precision mechanism for float comparison.
                if (abs($a['score'] - $b['score']) < 0.0001) return $b['tie'] <=> $a['tie'];
                return $b['score'] <=> $a['score'];
            });
            $relative_strength = $arr_max[0]['name'];

            // Priority for development: Potential > Learning > Growth
            $arr_min = [
                ['name' => 'POTENTIAL', 'score' => $p, 'tie' => 3],
                ['name' => 'LEARNING', 'score' => $l, 'tie' => 2],
                ['name' => 'GROWTH', 'score' => $g, 'tie' => 1]
            ];
            usort($arr_min, function($a, $b) {
                // Epsilon used strictly as a computational precision mechanism for float comparison.
                if (abs($a['score'] - $b['score']) < 0.0001) return $b['tie'] <=> $a['tie'];
                return $a['score'] <=> $b['score']; // ascending for min
            });
            $development_area = $arr_min[0]['name'];
        }

        $riasec_top3 = $scoring_output['riasec_top3'];
        $riasec_keys = array_map(function($type) { return "RIASEC_" . $type; }, $riasec_top3);
        $exploration_keys = array_map(function($type) { return "EXPLORE_" . $type; }, $riasec_top3);

        return [
            "profile_type" => "DESCRIPTIVE_NARRATIVE",
            "interpretation_version" => "SPP-INTERPRET-1.0.0",
            "narrative_bank_version" => "SPP-NARRATIVE-1.0.0",
            "bands" => $bands,
            "relative_strength" => $relative_strength,
            "development_area" => $development_area,
            "narratives" => [
                "potential_narrative_key" => "POTENTIAL_" . self::getBandKeySuffix($bands['potential']),
                "learning_narrative_key" => "LEARNING_" . self::getBandKeySuffix($bands['learning']),
                "growth_narrative_key" => "GROWTH_" . self::getBandKeySuffix($bands['growth']),
                "riasec_narrative_keys" => $riasec_keys,
                "exploration_keys" => $exploration_keys,
                "disclaimer_key" => "DISCLAIMER_STD"
            ]
        ];
    }
}

class SPPStateResolver {
    public static function resolveQualityStatus($duration_seconds, $straight_line_flag) {
        if ($duration_seconds < 45 || $straight_line_flag) {
            return "QUALITY_REVIEW_REQUIRED";
        }
        return "ACCEPTABLE";
    }
}
