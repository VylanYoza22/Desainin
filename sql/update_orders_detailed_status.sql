-- Update orders table to support detailed status system with progress tracking
ALTER TABLE orders MODIFY COLUMN status ENUM(
    'pending',           -- 10% - Pesanan baru masuk
    'confirmed',         -- 20% - Pesanan dikonfirmasi
    'payment_pending',   -- 30% - Menunggu pembayaran
    'payment_confirmed', -- 40% - Pembayaran dikonfirmasi
    'in_progress',       -- 50% - Sedang dikerjakan
    'review',           -- 70% - Review & revisi
    'final_review',     -- 85% - Review final
    'completed',        -- 100% - Selesai
    'cancelled'         -- 0% - Dibatalkan
) DEFAULT 'pending';

-- Add progress percentage column
ALTER TABLE orders ADD COLUMN progress_percentage INT DEFAULT 10;

-- Add status description column
ALTER TABLE orders ADD COLUMN status_description TEXT DEFAULT NULL;

-- Update existing records to have proper progress percentage
UPDATE orders SET 
    progress_percentage = CASE 
        WHEN status = 'pending' THEN 10
        WHEN status = 'in_progress' THEN 50
        WHEN status = 'completed' THEN 100
        WHEN status = 'cancelled' THEN 0
        ELSE 10
    END;
