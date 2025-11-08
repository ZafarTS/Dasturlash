CREATE DATABASE IF NOT EXISTS submission_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE submission_portal;

CREATE TABLE IF NOT EXISTS groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(64) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(128) NOT NULL UNIQUE,
    description TEXT NULL,
    practical_count TINYINT NOT NULL DEFAULT 24,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    title VARCHAR(128) NOT NULL,
    sequence INT NOT NULL,
    due_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_assignments_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    CONSTRAINT uq_assignments_subject_sequence UNIQUE (subject_id, sequence)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS group_subjects (
    group_id INT NOT NULL,
    subject_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (group_id, subject_id),
    CONSTRAINT fk_group_subjects_group FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
    CONSTRAINT fk_group_subjects_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(16) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    surname VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','student') NOT NULL DEFAULT 'student',
    group_id INT NULL,
    avatar_path VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_group FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    assignment_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    submitted_at DATETIME NOT NULL,
    grade VARCHAR(32) NULL,
    comment VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_submissions_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_submissions_assignment FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    CONSTRAINT uq_submissions_student_assignment UNIQUE (student_id, assignment_id)
) ENGINE=InnoDB;

-- Default admin account (password: admin123)
INSERT INTO users (user_id, name, surname, password_hash, role)
VALUES ('admin', 'Admin', 'User', '$2y$10$kVryG5kyR3O6jwxKF1u9Ii.YTZGi99ZTSdKCbFf8gLuwZQzQ0jrIS', 'admin')
ON DUPLICATE KEY UPDATE user_id = VALUES(user_id);
