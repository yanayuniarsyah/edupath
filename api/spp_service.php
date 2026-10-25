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
            $stmt = $this->pdo->prepare("SELECT * FROM spp_attempts WHERE id = ? AND student_id = ? FOR UPDATE");
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
            JOIN (
                SELECT student_id, MAX(submitted_at) AS latest_submitted_at
                FROM spp_attempts
                WHERE scoring_status = 'SCORED'
                GROUP BY student_id
            ) latest ON latest.student_id = att.student_id AND latest.latest_submitted_at = att.submitted_at
            WHERE att.scoring_status = 'SCORED'
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

    private function mapRiasecToRecommendations($riasecCode) {
        $dict = [
            'R' => ['Teknik Elektro / Mesin', 'Teknologi Informasi & Jaringan', 'Ilmu Pertanian Presisi'],
            'I' => ['Kedokteran & Biomedis', 'Ilmu Komputer & AI', 'Fisika / Matematika Murni'],
            'A' => ['Desain Komunikasi Visual', 'Arsitektur', 'Film & Animasi Digital'],
            'S' => ['Psikologi Pendidikan', 'Pendidikan Guru Profesional', 'Manajemen SDM & Konseling'],
            'E' => ['Manajemen Bisnis Digital', 'Hubungan Internasional', 'Hukum Bisnis & Korporasi'],
            'C' => ['Akuntansi Sektor Publik', 'Aktuaria & Analisis Data', 'Administrasi Fiskal / Pajak']
        ];
        $primary = substr($riasecCode, 0, 1) ?: 'I';
        $secondary = substr($riasecCode, 1, 1) ?: 'E';
        
        $primaryRecs = $dict[$primary] ?? $dict['I'];
        $secondaryRecs = $dict[$secondary] ?? $dict['E'];
        
        return [
            "bidang_utama" => $primaryRecs[0] ?? "Ilmu Komputer & Sistem Informasi",
            "bidang_pendukung" => $secondaryRecs[0] ?? "Manajemen Bisnis",
            "pilihan_alternatif" => $primaryRecs[1] ?? "Teknologi Terapan",
            "keterangan_profil" => "Berdasarkan pemetaan dominansi minat psikometri {$riasecCode} dan efikasi belajar adaptif."
        ];
    }

    public function getOrCreateCertificate($attempt_id, $student_id) {
        // 1. Validasi attempt
        $stmt = $this->pdo->prepare("SELECT a.*, s.name as student_name, s.school as student_school, s.tenant_id 
                                    FROM spp_attempts a 
                                    JOIN students s ON s.id = a.student_id 
                                    WHERE a.id = ? AND a.student_id = ?");
        $stmt->execute([$attempt_id, $student_id]);
        $attempt = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$attempt) {
            return ["status" => "ERROR", "message" => "Data asesmen tidak ditemukan untuk siswa ini."];
        }

        if ($attempt['scoring_status'] !== 'SCORED') {
            return ["status" => "ERROR", "message" => "Asesmen belum selesai dinilai atau status skor tidak valid."];
        }

        // 2. Cek apakah sertifikat sudah pernah dibuat
        $stmt_cert = $this->pdo->prepare("SELECT * FROM spp_certificates WHERE attempt_id = ?");
        $stmt_cert->execute([$attempt_id]);
        $existing = $stmt_cert->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $existing['top_recommendations'] = json_decode($existing['top_recommendations'] ?? '[]', true);
            $existing['status'] = 'SUCCESS';
            return $existing;
        }

        // 3. Ambil data RIASEC & Profil
        $stmt_score = $this->pdo->prepare("SELECT riasec_top3 FROM spp_scores WHERE attempt_id = ?");
        $stmt_score->execute([$attempt_id]);
        $scoreRow = $stmt_score->fetch(PDO::FETCH_ASSOC);
        $top3 = $scoreRow ? json_decode($scoreRow['riasec_top3'], true) : ['I', 'A', 'R'];
        $riasecCode = is_array($top3) ? strtoupper(implode('', array_slice($top3, 0, 3))) : 'IAR';

        $stmt_prof = $this->pdo->prepare("SELECT relative_strength FROM spp_profiles WHERE attempt_id = ?");
        $stmt_prof->execute([$attempt_id]);
        $profRow = $stmt_prof->fetch(PDO::FETCH_ASSOC);
        $strength = $profRow['relative_strength'] ?? 'Metacognitive Mastery';
        $learningStyle = 'Gaya ' . ucwords(str_replace('_', ' ', $strength));

        $recommendations = $this->mapRiasecToRecommendations($riasecCode);

        // 4. Buat Nomor Sertifikat Unik & Hash Verifikasi
        $certYear = date('Y');
        $randomHex = strtoupper(substr(hash('crc32b', $attempt_id . microtime()), 0, 8));
        $certNumber = "CERT-SPP-{$certYear}-{$randomHex}";
        $issueDate = date('Y-m-d');
        $verificationHash = hash('sha256', "EDUPATH_SPP_VERIFY|{$certNumber}|{$student_id}|{$attempt['student_name']}|{$riasecCode}|{$issueDate}");
        $certId = $this->generate_uuid();

        // 5. Simpan ke database
        $ins = $this->pdo->prepare("INSERT INTO spp_certificates (
            id, tenant_id, student_id, attempt_id, certificate_number,
            student_name_snapshot, school_name_snapshot, riasec_code,
            learning_style, top_recommendations, issue_date,
            verification_hash, pdf_generation_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ready')");
        $ins->execute([
            $certId,
            $attempt['tenant_id'],
            $student_id,
            $attempt_id,
            $certNumber,
            $attempt['student_name'],
            $attempt['student_school'] ?? 'Siswa EduPath',
            $riasecCode,
            $learningStyle,
            json_encode($recommendations),
            $issueDate,
            $verificationHash
        ]);

        return [
            "status" => "SUCCESS",
            "id" => $certId,
            "certificate_number" => $certNumber,
            "student_name_snapshot" => $attempt['student_name'],
            "school_name_snapshot" => $attempt['student_school'] ?? 'Siswa EduPath',
            "riasec_code" => $riasecCode,
            "learning_style" => $learningStyle,
            "top_recommendations" => $recommendations,
            "issue_date" => $issueDate,
            "verification_hash" => $verificationHash,
            "pdf_generation_status" => "ready"
        ];
    }

    public function verifyCertificate($identifier) {
        if (!$identifier) {
            return ["valid" => false, "message" => "Nomor atau hash sertifikat wajib disertakan."];
        }

        $stmt = $this->pdo->prepare("SELECT * FROM spp_certificates WHERE certificate_number = ? OR verification_hash = ? OR id = ? LIMIT 1");
        $stmt->execute([$identifier, $identifier, $identifier]);
        $cert = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cert) {
            return [
                "valid" => false,
                "message" => "Sertifikat tidak terdaftar atau kode verifikasi tidak cocok dalam sistem EduPath."
            ];
        }

        return [
            "valid" => true,
            "certificate_number" => $cert['certificate_number'],
            "student_name" => $cert['student_name_snapshot'],
            "school_name" => $cert['school_name_snapshot'],
            "riasec_code" => $cert['riasec_code'],
            "learning_style" => $cert['learning_style'],
            "top_recommendations" => json_decode($cert['top_recommendations'] ?? '[]', true),
            "issue_date" => $cert['issue_date'],
            "verification_hash" => $cert['verification_hash'],
            "verified_at" => date('Y-m-d H:i:s'),
            "issuer" => "EduPath Indonesia - Adaptive Learning & Assessment Platform"
        ];
    }
}
