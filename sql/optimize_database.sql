-- Database Optimization Queries for PKK2 Project
-- Run these queries to improve database performance

-- Add indexes for frequently queried columns
ALTER TABLE `orders` ADD INDEX `idx_user_id` (`user_id`);
ALTER TABLE `orders` ADD INDEX `idx_status` (`status`);
ALTER TABLE `orders` ADD INDEX `idx_created_at` (`created_at`);
ALTER TABLE `orders` ADD INDEX `idx_user_status` (`user_id`, `status`);

ALTER TABLE `users` ADD INDEX `idx_email` (`email`);
ALTER TABLE `users` ADD INDEX `idx_whatsapp` (`whatsapp`);

-- Optimize table structure
OPTIMIZE TABLE `users`;
OPTIMIZE TABLE `orders`;

-- Update table engine to InnoDB if not already (for better performance)
ALTER TABLE `users` ENGINE=InnoDB;
ALTER TABLE `orders` ENGINE=InnoDB;

-- Set proper charset and collation
ALTER TABLE `users` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `orders` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Add foreign key constraint for data integrity
ALTER TABLE `orders` 
ADD CONSTRAINT `fk_orders_user_id` 
FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) 
ON DELETE CASCADE ON UPDATE CASCADE;

-- Create view for order statistics (optional)
CREATE OR REPLACE VIEW `order_stats` AS
SELECT 
    u.id as user_id,
    u.name as user_name,
    COUNT(o.id) as total_orders,
    SUM(CASE WHEN o.status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
    SUM(CASE WHEN o.status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
    SUM(o.budget) as total_budget,
    AVG(o.budget) as avg_budget,
    MAX(o.created_at) as last_order_date
FROM users u
LEFT JOIN orders o ON u.id = o.user_id
GROUP BY u.id, u.name;

-- Analyze tables for query optimization
ANALYZE TABLE `users`;
ANALYZE TABLE `orders`;
