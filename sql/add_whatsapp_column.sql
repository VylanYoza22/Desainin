-- Add WhatsApp number column to orders table
ALTER TABLE orders ADD COLUMN whatsapp_number VARCHAR(15) DEFAULT NULL AFTER notes;
