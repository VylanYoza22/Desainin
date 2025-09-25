-- ========================================
-- UPDATE FEEDBACK TABLE SCHEMA
-- ========================================
-- Script untuk menambahkan kolom user profile ke tabel feedback
-- Jalankan script ini di phpMyAdmin atau MySQL command line

-- 1. Tambah kolom user_id untuk link ke tabel users
ALTER TABLE feedback 
ADD COLUMN user_id INT NULL AFTER id;

-- 2. Tambah kolom profile_picture untuk cache foto profil
ALTER TABLE feedback 
ADD COLUMN profile_picture VARCHAR(255) NULL AFTER user_id;

-- 3. Tambah foreign key constraint (opsional - uncomment jika diperlukan)
-- ALTER TABLE feedback 
-- ADD CONSTRAINT fk_feedback_user 
-- FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

-- 4. Update existing feedback untuk set user_id = NULL (anonymous feedback)
UPDATE feedback 
SET user_id = NULL 
WHERE user_id IS NULL;
