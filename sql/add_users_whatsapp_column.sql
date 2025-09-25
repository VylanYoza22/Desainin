-- Add WhatsApp number column to users table
ALTER TABLE users ADD COLUMN IF NOT EXISTS whatsapp_number VARCHAR(15) DEFAULT NULL;

-- Add notification preference columns to users table
ALTER TABLE users ADD COLUMN IF NOT EXISTS email_notifications TINYINT(1) DEFAULT 1;
ALTER TABLE users ADD COLUMN IF NOT EXISTS whatsapp_notifications TINYINT(1) DEFAULT 1;
