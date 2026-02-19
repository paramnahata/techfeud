-- Tech Feud Database Setup
-- Run this in phpMyAdmin or MySQL command line

CREATE DATABASE IF NOT EXISTS techfeud CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE techfeud;

-- Game state table
CREATE TABLE IF NOT EXISTS game_state (
    id INT AUTO_INCREMENT PRIMARY KEY,
    is_active TINYINT(1) DEFAULT 0,
    current_question_id INT DEFAULT 0,
    current_turn INT DEFAULT 0,
    show_question TINYINT(1) DEFAULT 0,
    show_answers TINYINT(1) DEFAULT 0,
    show_leaderboard TINYINT(1) DEFAULT 0,
    show_welcome TINYINT(1) DEFAULT 1,
    revealed_answers TEXT DEFAULT '[]',
    timer_seconds INT DEFAULT 30,
    timer_started_at BIGINT DEFAULT 0,
    timer_running TINYINT(1) DEFAULT 0,
    buzzers_locked TINYINT(1) DEFAULT 0,
    logo_path VARCHAR(255) DEFAULT '',
    question_type ENUM('feud','kbc','open') DEFAULT 'feud',
    kbc_correct_answer INT DEFAULT -1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert initial state
INSERT INTO game_state (id) VALUES (1) ON DUPLICATE KEY UPDATE id=1;

-- Contestants table
CREATE TABLE IF NOT EXISTS contestants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    score INT DEFAULT 0,
    position INT NOT NULL,
    game_id INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Buzzer queue table
CREATE TABLE IF NOT EXISTS buzzer_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contestant_id INT NOT NULL,
    contestant_name VARCHAR(100) NOT NULL,
    question_id INT DEFAULT 0,
    buzz_time BIGINT NOT NULL,
    rank_position INT DEFAULT 0,
    game_id INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Questions table (for dynamic questions)
CREATE TABLE IF NOT EXISTS questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_text TEXT NOT NULL,
    question_type ENUM('feud','kbc','open') DEFAULT 'feud',
    points INT DEFAULT 10,
    game_id INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Answers table
CREATE TABLE IF NOT EXISTS answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    answer_text VARCHAR(255) NOT NULL,
    points INT DEFAULT 0,
    is_correct TINYINT(1) DEFAULT 0,
    option_label CHAR(1) DEFAULT '',
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Answer records (who answered what)
CREATE TABLE IF NOT EXISTS answer_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    answer_id INT DEFAULT -1,
    contestant_id INT NOT NULL,
    points_awarded INT DEFAULT 0,
    game_id INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
