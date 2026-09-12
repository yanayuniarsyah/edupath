<?php
// api/spp_service.php
require_once __DIR__ . '/spp_engine.php';

class SPPService {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    private function generate_uuid() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    public function createAttempt($student_id) {
        $attempt_id = $this->generate_uuid();
        $stmt = $this->pdo->prepare("INSERT INTO spp_attempts (id, student_id, attempt_status, scoring_status) VALUES (?, ?, 'DRAFT', 'PENDING')");
        $stmt->execute([$attempt_id, $student_id]);
        return ["id" => $attempt_id, "status" => "DRAFT"];
    }

    public function submitAttempt($student_id, $attempt_id, $responses, $duration_seconds) {
        $this->pdo->beginTransaction();
        try {
            // Lock attempt
            $stmt = $this->pdo->prepare("SELECT * FROM spp_attempts WHERE id = ? AND student_id = ?");
            $stmt->execute([$attempt_id, $student_id]);
            $attempt = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$attempt) {
                throw new Exception("Attempt not found");
            }

            if ($attempt['attempt_status'] === 'SUBMITTED') {
                $this->pdo->rollBack();
                // Idempotent: return existing result
                return $this->getResult($attempt_id, $student_id);
            }

            // Move to IN_PROGRESS then immediately to SUBMITTED
            $stmt = $this->pdo->prepare("UPDATE spp_attempts SET attempt_status = 'IN_PROGRESS' WHERE id = ?");
            $stmt->execute([$attempt_id]);

            // Scoring
            $scoring_output = SPPScoringEngine::score($responses);
            
            $quality_status = "ACCEPTABLE";
            if ($scoring_output['status'] === 'SCORED') {
                $quality_status = SPPStateResolver::resolveQualityStatus($duration_seconds, false); // straight line not implemented here natively
            }

            // Update Attempt State
            $stmt = $this->pdo->prepare("UPDATE spp_attempts SET attempt_status = 'SUBMITTED', scoring_status = ?, quality_status = ?, duration_seconds = ?, submitted_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$scoring_output['status'], $quality_status, $duration_seconds, $attempt_id]);

            // Save Raw Responses
            $stmt_resp = $this->pdo->prepare("INSERT INTO spp_responses (id, attempt_id, responses) VALUES (?, ?, ?)");
            $stmt_resp->execute([$this->generate_uuid(), $attempt_id, json_encode($responses)]);

            if ($scoring_output['status'] === 'SCORED') {
                // Save Scores
                $stmt_scores = $this->pdo->prepare("INSERT INTO spp_scores (id, attempt_id, facets, dimensions, riasec, riasec_top3) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt_scores->execute([
                    $this->generate_uuid(),
                    $attempt_id,
                    json_encode($scoring_output['facets']),
                    json_encode($scoring_output['dimensions']),
                    json_encode($scoring_output['riasec']),
                    json_encode($scoring_output['riasec_top3'])
                ]);

                // Profile
                $profile_output = SPPProfileEngine::generateProfile($scoring_output);
                if ($profile_output) {
                    $stmt_prof = $this->pdo->prepare("INSERT INTO spp_profiles (id, attempt_id, bands, relative_strength, development_area, narratives) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt_prof->execute([
                        $this->generate_uuid(),
                        $attempt_id,
                        json_encode($profile_output['bands']),
                        $profile_output['relative_strength'],
                        $profile_output['development_area'],
                        json_encode($profile_output['narratives'])
                    ]);
                }
            }

            $this->pdo->commit();
            return $this->getResult($attempt_id, $student_id);

        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ["status" => "ERROR", "message" => $e->getMessage()];
        }
    }

    public function getResult($attempt_id, $student_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM spp_attempts WHERE id = ? AND student_id = ?");
        $stmt->execute([$attempt_id, $student_id]);
        $attempt = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$attempt) return null;

        $result = [
            "attempt" => $attempt,
            "scores" => null,
            "profile" => null
        ];

        if ($attempt['scoring_status'] === 'SCORED') {
            $stmt_scores = $this->pdo->prepare("SELECT * FROM spp_scores WHERE attempt_id = ?");
            $stmt_scores->execute([$attempt_id]);
            $scores = $stmt_scores->fetch(PDO::FETCH_ASSOC);
            if ($scores) {
                $scores['facets'] = json_decode($scores['facets'], true);
                $scores['dimensions'] = json_decode($scores['dimensions'], true);
                $scores['riasec'] = json_decode($scores['riasec'], true);
                $scores['riasec_top3'] = json_decode($scores['riasec_top3'], true);
                $result['scores'] = $scores;
            }

            $stmt_prof = $this->pdo->prepare("SELECT * FROM spp_profiles WHERE attempt_id = ?");
            $stmt_prof->execute([$attempt_id]);
            $prof = $stmt_prof->fetch(PDO::FETCH_ASSOC);
            if ($prof) {
                $prof['bands'] = json_decode($prof['bands'], true);
                $prof['narratives'] = json_decode($prof['narratives'], true);
                $result['profile'] = $prof;
            }
        }
        return $result;
    }

    public function getSchoolAggregate($school_name = null, $tenant_id = null) {
        if (!$school_name && !$tenant_id) return null;
        
        // Find canonical attempts for students in this school
        // Canonical: latest completed attempt with scoring_status = 'SCORED'
        
        $sql = "
            SELECT p.relative_strength, p.development_area, s.riasec_top3, att.quality_status
            FROM students st
            JOIN spp_attempts att ON att.student_id = st.id
            JOIN spp_profiles p ON p.attempt_id = att.id
            JOIN spp_scores s ON s.attempt_id = att.id
            WHERE att.scoring_status = 'SCORED'
            AND att.submitted_at = (
                SELECT MAX(submitted_at) FROM spp_attempts a2
                WHERE a2.student_id = st.id AND a2.scoring_status = 'SCORED'
            )
        ";
        
        $params = [];
        if ($school_name) {
            $sql .= " AND st.school = ?";
            $params[] = $school_name;
        }
        if ($tenant_id) {
            $sql .= " AND st.tenant_id = ?";
            $params[] = $tenant_id;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $aggregate = [
            "total_canonical_students" => count($rows),
            "quality_review_flags" => 0,
            "riasec_distribution" => [],
            "strength_distribution" => [],
            "development_distribution" => []
        ];

        foreach ($rows as $row) {
            if ($row['quality_status'] === 'QUALITY_REVIEW_REQUIRED') {
                $aggregate['quality_review_flags']++;
            }
            
            $riasec = json_decode($row['riasec_top3'], true);
            if (is_array($riasec) && count($riasec) > 0) {
                $top = $riasec[0];
                $aggregate['riasec_distribution'][$top] = ($aggregate['riasec_distribution'][$top] ?? 0) + 1;
            }

            if ($row['relative_strength']) {
                $str = $row['relative_strength'];
                $aggregate['strength_distribution'][$str] = ($aggregate['strength_distribution'][$str] ?? 0) + 1;
            }

            if ($row['development_area']) {
                $dev = $row['development_area'];
                $aggregate['development_distribution'][$dev] = ($aggregate['development_distribution'][$dev] ?? 0) + 1;
            }
        }

        return $aggregate;
    }
}
