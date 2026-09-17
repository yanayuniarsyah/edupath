-- Additive reliability fields. Run once in each environment.
ALTER TABLE quiz_attempts ADD COLUMN expires_at DATETIME NULL AFTER started_at;
ALTER TABLE quiz_attempts ADD COLUMN answers JSON NULL AFTER completed_at;
ALTER TABLE quiz_attempts ADD COLUMN last_saved_at DATETIME NULL AFTER answers;
CREATE INDEX quiz_attempts_student_status_idx ON quiz_attempts (student_id, status, expires_at);
ALTER TABLE quiz_results ADD UNIQUE KEY quiz_attempt_result_uq (attempt_id);
