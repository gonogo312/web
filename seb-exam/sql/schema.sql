




CREATE DATABASE IF NOT EXISTS `seb_exam`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE `seb_exam`;




CREATE TABLE IF NOT EXISTS `users` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username`      VARCHAR(50)  NOT NULL UNIQUE,
    `email`         VARCHAR(255) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role`          ENUM('teacher','student') NOT NULL DEFAULT 'student',
    `full_name`     VARCHAR(100) NOT NULL DEFAULT '',
    `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;




CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username`     VARCHAR(50)  NOT NULL,
    `ip_address`   VARCHAR(45)  NOT NULL,
    `attempted_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `success`      TINYINT(1)   NOT NULL DEFAULT 0,
    INDEX `idx_login_rate` (`ip_address`, `attempted_at`)
) ENGINE=InnoDB;




CREATE TABLE IF NOT EXISTS `exams` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `teacher_id`     INT UNSIGNED NOT NULL,
    `title`          VARCHAR(255) NOT NULL,
    `description`    TEXT,
    `time_limit_min` INT UNSIGNED NOT NULL DEFAULT 30,
    `passing_score`  DECIMAL(5,2) NOT NULL DEFAULT 50.00,
    `access_code`    VARCHAR(20)  DEFAULT NULL,
    `is_published`   TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`teacher_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;




CREATE TABLE IF NOT EXISTS `questions` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `exam_id`        INT UNSIGNED NOT NULL,
    `type`           ENUM('mcq','tf','short') NOT NULL DEFAULT 'mcq',
    `question_text`  TEXT NOT NULL,
    `options_json`   JSON DEFAULT NULL,   
    `correct_answer` VARCHAR(500) NOT NULL,
    `points`         DECIMAL(5,2) NOT NULL DEFAULT 1.00,
    `sort_order`     INT UNSIGNED NOT NULL DEFAULT 0,
    FOREIGN KEY (`exam_id`) REFERENCES `exams`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;




CREATE TABLE IF NOT EXISTS `attempts` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id`   INT UNSIGNED NOT NULL,
    `exam_id`      INT UNSIGNED NOT NULL,
    `started_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `finished_at`  DATETIME     DEFAULT NULL,
    `score`        DECIMAL(7,2) DEFAULT NULL,
    `max_score`    DECIMAL(7,2) DEFAULT NULL,
    `is_submitted` TINYINT(1)   NOT NULL DEFAULT 0,
    FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`exam_id`)    REFERENCES `exams`(`id`) ON DELETE CASCADE,
    INDEX `idx_student_exam` (`student_id`, `exam_id`)
) ENGINE=InnoDB;




CREATE TABLE IF NOT EXISTS `attempt_answers` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `attempt_id`      INT UNSIGNED NOT NULL,
    `question_id`     INT UNSIGNED NOT NULL,
    `student_answer`  TEXT DEFAULT NULL,
    `is_correct`      TINYINT(1) DEFAULT NULL,
    `points_awarded`  DECIMAL(5,2) DEFAULT 0.00,
    FOREIGN KEY (`attempt_id`)  REFERENCES `attempts`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`question_id`) REFERENCES `questions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;




CREATE TABLE IF NOT EXISTS `games` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `teacher_id`    INT UNSIGNED NOT NULL,
    `title`         VARCHAR(255) NOT NULL,
    `description`   TEXT,
    `start_node_id` INT UNSIGNED DEFAULT NULL,
    `access_code`   VARCHAR(20)  DEFAULT NULL,
    `is_published`  TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`teacher_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;




CREATE TABLE IF NOT EXISTS `game_nodes` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `game_id`     INT UNSIGNED NOT NULL,
    `node_key`    VARCHAR(50)  NOT NULL,
    `title`       VARCHAR(255) NOT NULL,
    `description` TEXT,
    `is_end_node` TINYINT(1)   NOT NULL DEFAULT 0,
    `sort_order`  INT UNSIGNED NOT NULL DEFAULT 0,
    FOREIGN KEY (`game_id`) REFERENCES `games`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_game_node_key` (`game_id`, `node_key`)
) ENGINE=InnoDB;




CREATE TABLE IF NOT EXISTS `game_choices` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `node_id`        INT UNSIGNED NOT NULL,
    `choice_text`    VARCHAR(500) NOT NULL,
    `target_node_id` INT UNSIGNED DEFAULT NULL,
    `sort_order`     INT UNSIGNED NOT NULL DEFAULT 0,
    FOREIGN KEY (`node_id`)        REFERENCES `game_nodes`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`target_node_id`) REFERENCES `game_nodes`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;




CREATE TABLE IF NOT EXISTS `seb_configs` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `teacher_id`    INT UNSIGNED NOT NULL,
    `activity_type` ENUM('exam','game') NOT NULL,
    `activity_id`   INT UNSIGNED NOT NULL,
    `title`         VARCHAR(255) NOT NULL,
    `settings_json` JSON NOT NULL,
    `xml_path`      VARCHAR(500) DEFAULT NULL,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`teacher_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;




CREATE TABLE IF NOT EXISTS `api_tokens` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`    INT UNSIGNED NOT NULL,
    `token_hash` VARCHAR(64)  NOT NULL UNIQUE,
    `label`      VARCHAR(100) NOT NULL DEFAULT 'API Token',
    `expires_at` DATETIME     DEFAULT NULL,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;




