-- Add tracking columns for login state
ALTER TABLE users
ADD COLUMN last_login DATETIME NULL AFTER password,
ADD COLUMN is_online TINYINT(1) NOT NULL DEFAULT 0 AFTER last_login;
