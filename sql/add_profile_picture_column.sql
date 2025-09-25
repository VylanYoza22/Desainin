-- Add profile_picture column to users table
ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) NULL AFTER phone;

-- Update existing users to have NULL profile_picture (default)
UPDATE users SET profile_picture = NULL WHERE profile_picture IS NULL;