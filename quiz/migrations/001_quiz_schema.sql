-- Quiz module schema additions for zafarts.uz/quiz
-- NOTE: Replace referenced users table/column names if your LMS differs.

CREATE TABLE IF NOT EXISTS quiz_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    semester_code VARCHAR(32) NOT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_semester (semester_code),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS quiz_questions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quiz_id BIGINT UNSIGNED NOT NULL,
    question_order INT UNSIGNED NOT NULL,
    question_html MEDIUMTEXT NOT NULL,
    answer_a_html TEXT NOT NULL,
    answer_b_html TEXT NOT NULL,
    answer_c_html TEXT NOT NULL,
    answer_d_html TEXT NOT NULL,
    correct_option ENUM('A','B','C','D') NOT NULL,
    time_limit_seconds INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_questions_quiz FOREIGN KEY (quiz_id) REFERENCES quiz_templates(id) ON DELETE CASCADE,
    UNIQUE KEY uq_quiz_order (quiz_id, question_order),
    INDEX idx_quiz_id (quiz_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS quiz_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quiz_id BIGINT UNSIGNED NOT NULL,
    started_by BIGINT UNSIGNED NOT NULL,
    status ENUM('waiting','running','paused','finished') NOT NULL DEFAULT 'waiting',
    current_question_order INT UNSIGNED NOT NULL DEFAULT 0,
    started_at DATETIME NULL,
    paused_at DATETIME NULL,
    finished_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_sessions_quiz FOREIGN KEY (quiz_id) REFERENCES quiz_templates(id) ON DELETE RESTRICT,
    INDEX idx_status (status),
    INDEX idx_quiz_status (quiz_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Enforce globally one active session by generated column + unique index.
ALTER TABLE quiz_sessions
    ADD COLUMN active_guard TINYINT AS (
        CASE WHEN status IN ('waiting','running','paused') THEN 1 ELSE NULL END
    ) STORED,
    ADD UNIQUE INDEX uq_one_active_session (active_guard);

CREATE TABLE IF NOT EXISTS quiz_session_students (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    avg_latency_ms INT UNSIGNED NOT NULL DEFAULT 0,
    last_ping_ms INT UNSIGNED NOT NULL DEFAULT 0,
    connection_state ENUM('online','offline') NOT NULL DEFAULT 'online',
    CONSTRAINT fk_session_students_session FOREIGN KEY (session_id) REFERENCES quiz_sessions(id) ON DELETE CASCADE,
    UNIQUE KEY uq_session_student (session_id, student_id),
    INDEX idx_student (student_id),
    INDEX idx_session_state (session_id, connection_state)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS quiz_session_questions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id BIGINT UNSIGNED NOT NULL,
    question_id BIGINT UNSIGNED NOT NULL,
    question_order INT UNSIGNED NOT NULL,
    opens_at_ms BIGINT UNSIGNED NULL,
    closes_at_ms BIGINT UNSIGNED NULL,
    status ENUM('pending','open','closed') NOT NULL DEFAULT 'pending',
    CONSTRAINT fk_sess_q_session FOREIGN KEY (session_id) REFERENCES quiz_sessions(id) ON DELETE CASCADE,
    CONSTRAINT fk_sess_q_question FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE RESTRICT,
    UNIQUE KEY uq_session_q_order (session_id, question_order),
    INDEX idx_session_status (session_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS quiz_answers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id BIGINT UNSIGNED NOT NULL,
    question_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    selected_option ENUM('A','B','C','D') NOT NULL,
    submitted_at_ms BIGINT UNSIGNED NOT NULL,
    latency_compensation_ms INT UNSIGNED NOT NULL DEFAULT 0,
    effective_time_ms BIGINT UNSIGNED NOT NULL,
    is_correct TINYINT(1) NOT NULL DEFAULT 0,
    score_awarded INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_answers_session FOREIGN KEY (session_id) REFERENCES quiz_sessions(id) ON DELETE CASCADE,
    CONSTRAINT fk_answers_question FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE RESTRICT,
    UNIQUE KEY uq_one_answer (session_id, question_id, student_id),
    INDEX idx_answer_lookup (session_id, question_id),
    INDEX idx_student_session (student_id, session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS quiz_session_scores (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    total_score INT UNSIGNED NOT NULL DEFAULT 0,
    correct_count INT UNSIGNED NOT NULL DEFAULT 0,
    wrong_count INT UNSIGNED NOT NULL DEFAULT 0,
    unanswered_count INT UNSIGNED NOT NULL DEFAULT 0,
    rank_position INT UNSIGNED NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_scores_session FOREIGN KEY (session_id) REFERENCES quiz_sessions(id) ON DELETE CASCADE,
    UNIQUE KEY uq_session_student_score (session_id, student_id),
    INDEX idx_scoreboard (session_id, total_score DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS quiz_semester_scores (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    semester_code VARCHAR(32) NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    cumulative_score INT UNSIGNED NOT NULL DEFAULT 0,
    session_count INT UNSIGNED NOT NULL DEFAULT 0,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_semester_student (semester_code, student_id),
    INDEX idx_student_semester (student_id, semester_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
