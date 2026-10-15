-- Smart Home Visitor Entry System - database setup (phpMyAdmin version)
--
-- How to import:
--   phpMyAdmin -> click the "student" database on the left -> Import tab
--   -> choose this file -> Import.
--
-- This file does NOT create a database or a MySQL user, so it works with the
-- limited "student" MySQL user. In app/config.php use:
--   db_name = student, db_user = student, db_pass = your phpMyAdmin password.
--
-- WARNING: the three tables below are dropped and re-created so the structure
-- is exactly what the app expects. Only admins, visitors and login_attempts are
-- touched; all other tables in the database are left alone.

USE student;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS visitors;
DROP TABLE IF EXISTS admins;

SET FOREIGN_KEY_CHECKS = 1;

-- Homeowner accounts (create them with:  php create_admin.php yourname)
CREATE TABLE admins (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(50)  NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_admins_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One row per visitor entry
CREATE TABLE visitors (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL,
  phone       VARCHAR(20)  NOT NULL,
  photo_file  VARCHAR(64)  NOT NULL,
  ip_address  VARCHAR(45)  NULL,
  entry_time  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_visitors_entry_time (entry_time),
  KEY idx_visitors_ip_time (ip_address, entry_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Failed login tracking (blocks brute-force guessing)
CREATE TABLE login_attempts (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  ip_address   VARCHAR(45)  NOT NULL,
  attempted_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_attempts_ip_time (ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;