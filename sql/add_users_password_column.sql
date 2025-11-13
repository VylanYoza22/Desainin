-- Add password column to users table if missing
ALTER TABLE users
ADD COLUMN password VARCHAR(255) NULL AFTER email;
