-- Add role column to users table and default existing users to 'user'
ALTER TABLE users
ADD COLUMN role ENUM('admin','user') NOT NULL DEFAULT 'user' AFTER email;

-- Optional: make email unique if not already
ALTER TABLE users
ADD UNIQUE KEY unique_email (email);
