-- Update orders table to support new status values
-- This will add the new status options for better progress tracking

-- First, let's see current status values
SELECT DISTINCT status FROM orders;

-- Update existing status values to new system
UPDATE orders SET status = 'confirmed' WHERE status = 'pending' AND updated_at < DATE_SUB(NOW(), INTERVAL 1 DAY);

-- Add new status options (if using ENUM, we need to modify the column)
-- For flexibility, we'll use VARCHAR instead of ENUM

-- If the status column is ENUM, convert it to VARCHAR
ALTER TABLE orders MODIFY COLUMN status VARCHAR(20) NOT NULL DEFAULT 'pending';

-- Add index for better performance
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_user_status ON orders(user_id, status);

-- Insert sample data with new status values for testing
-- UPDATE orders SET status = 'confirmed' WHERE id = 1;
-- UPDATE orders SET status = 'in_progress' WHERE id = 2;
-- UPDATE orders SET status = 'review' WHERE id = 3;

-- Add notes column if it doesn't exist (for admin notes)
ALTER TABLE orders ADD COLUMN IF NOT EXISTS notes TEXT NULL AFTER description;

-- Add estimated_completion column for better time tracking
ALTER TABLE orders ADD COLUMN IF NOT EXISTS estimated_completion DATETIME NULL AFTER deadline;
