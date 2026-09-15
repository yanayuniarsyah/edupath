-- =====================================================
-- Assessment & Scoring Engine Schema
-- Immutable historical records
-- =====================================================

-- Assessment / Tryout Master Data
CREATE TABLE IF NOT EXISTS tryouts (
  id VARCHAR(36) PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  type ENUM('mini_quiz', 'practice', 'full_tryout', 'diagnostic') DEFAULT 'practice',
  subtes VARCHAR(100), -- e.g., 'matematika', 'bahasa_inggris'
  total_questions INT DEFAULT 0,
  time_limit_minutes INT,
  passing_score INT DEFAULT 60,
  irt_model_version VARCHAR(50), -- For versioning IRT calculations
  is_active BOOLEAN DEFAULT true,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_type (type),
  INDEX idx_subtes (subtes),
  INDEX idx_active (is_active)
);

-- Question Bank with IRT Weights
CREATE TABLE IF NOT EXISTS questions (
  id VARCHAR(36) PRIMARY KEY,
  tryout_id VARCHAR(36) NOT NULL,
  subtes VARCHAR(100),
  topic VARCHAR(255),
  question_text LONGTEXT NOT NULL,
  question_version VARCHAR(50), -- For immutability
  options JSON, -- [{text: '', isCorrect: bool}, ...]
  correct_answer INT, -- 0-3 index
  explanation TEXT,
  difficulty ENUM('easy', 'medium', 'hard') DEFAULT 'medium',
  irt_difficulty FLOAT, -- IRT theta parameter
  irt_weight FLOAT DEFAULT 1.0,
  irt_model_version VARCHAR(50),
  is_active BOOLEAN DEFAULT true,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (tryout_id) REFERENCES tryouts(id),
  INDEX idx_tryout (tryout_id),
  INDEX idx_subtes (subtes),
  INDEX idx_topic (topic),
  INDEX idx_difficulty (difficulty),
  INDEX idx_active (is_active)
);

-- Immutable Attempt Records
CREATE TABLE IF NOT EXISTS attempts (
  id VARCHAR(36) PRIMARY KEY,
  user_id VARCHAR(36) NOT NULL,
  tryout_id VARCHAR(36) NOT NULL,
  status ENUM('in_progress', 'submitted', 'graded') DEFAULT 'in_progress',
  start_time TIMESTAMP,
  end_time TIMESTAMP,
  duration_seconds INT,
  score INT, -- Raw score
  percentile FLOAT, -- Percentile rank
  irt_score FLOAT, -- IRT calculated score
  irt_model_version VARCHAR(50),
  correct_count INT DEFAULT 0,
  total_questions INT DEFAULT 0,
  answer_hash VARCHAR(255), -- SHA256 hash of answers for integrity
  submitted_at TIMESTAMP NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  -- IMMUTABLE: no UPDATE allowed after submitted
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (tryout_id) REFERENCES tryouts(id),
  UNIQUE KEY unique_submission (user_id, tryout_id, submitted_at),
  INDEX idx_user (user_id),
  INDEX idx_tryout (tryout_id),
  INDEX idx_status (status),
  INDEX idx_submitted (submitted_at)
);

-- Individual Answers (Immutable)
CREATE TABLE IF NOT EXISTS answers (
  id VARCHAR(36) PRIMARY KEY,
  attempt_id VARCHAR(36) NOT NULL,
  question_id VARCHAR(36) NOT NULL,
  selected_option INT, -- 0-3
  correct_answer INT,
  is_correct BOOLEAN,
  irt_weight FLOAT,
  time_spent_seconds INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (attempt_id) REFERENCES attempts(id),
  FOREIGN KEY (question_id) REFERENCES questions(id),
  INDEX idx_attempt (attempt_id),
  INDEX idx_question (question_id),
  INDEX idx_correct (is_correct)
);

-- Scoring Versions (Audit Trail)
CREATE TABLE IF NOT EXISTS scoring_versions (
  id VARCHAR(36) PRIMARY KEY,
  version VARCHAR(50) NOT NULL UNIQUE,
  algorithm TEXT, -- JSON describing the scoring algorithm
  irt_model TEXT, -- JSON describing IRT parameters
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  effective_from TIMESTAMP,
  effective_to TIMESTAMP,
  is_active BOOLEAN DEFAULT true,
  created_by VARCHAR(36),
  INDEX idx_version (version),
  INDEX idx_active (is_active)
);

-- Student Performance Analytics
CREATE TABLE IF NOT EXISTS student_performance (
  id VARCHAR(36) PRIMARY KEY,
  user_id VARCHAR(36) NOT NULL UNIQUE,
  subtes VARCHAR(100),
  topic VARCHAR(255),
  total_attempts INT DEFAULT 0,
  correct_answers INT DEFAULT 0,
  avg_score FLOAT,
  highest_score INT,
  latest_score INT,
  accuracy_percent FLOAT,
  last_attempt_at TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  INDEX idx_user (user_id),
  INDEX idx_subtes (subtes),
  INDEX idx_topic (topic)
);

-- Audit Log for Assessment Changes
CREATE TABLE IF NOT EXISTS assessment_audit_log (
  id VARCHAR(36) PRIMARY KEY,
  event_type VARCHAR(50), -- 'attempt_submitted', 'score_updated', 'result_corrected'
  attempt_id VARCHAR(36),
  user_id VARCHAR(36),
  admin_id VARCHAR(36), -- NULL if system event
  old_value JSON,
  new_value JSON,
  reason TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (attempt_id) REFERENCES attempts(id),
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (admin_id) REFERENCES users(id),
  INDEX idx_attempt (attempt_id),
  INDEX idx_user (user_id),
  INDEX idx_event (event_type),
  INDEX idx_created (created_at)
);

-- Triggers to prevent updates on attempts (IMMUTABILITY)
DELIMITER //

CREATE TRIGGER prevent_attempt_update
BEFORE UPDATE ON attempts
FOR EACH ROW
BEGIN
  IF OLD.status = 'submitted' THEN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Cannot update submitted attempt. Immutable record.';
  END IF;
END//

DELIMITER ;
