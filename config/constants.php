<?php
/**
 * Application Constants
 * Define all application-wide constants here
 */

// Application Info
define('APP_NAME', 'Desainin');
define('APP_VERSION', '2.0.0');
define('APP_DESCRIPTION', 'Platform Kreatif untuk Video Editing & Graphic Design');

// File Upload Settings
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('PROFILE_UPLOAD_PATH', UPLOAD_PATH . 'profiles/');

// Pagination
define('ORDERS_PER_PAGE', 10);
define('ADMIN_ORDERS_PER_PAGE', 20);

// Order Settings
define('DEFAULT_ORDER_STATUS', 'pending');
define('CANCELLABLE_STATUSES', ['pending', 'confirmed']);
define('EDITABLE_STATUSES', ['pending']);

// Notification Settings
define('ENABLE_EMAIL_NOTIFICATIONS', false);
define('ENABLE_WHATSAPP_NOTIFICATIONS', true);

// Security
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// API Settings
define('WHATSAPP_API_TIMEOUT', 30);
define('WHATSAPP_MAX_RETRIES', 3);

// UI Settings
define('ITEMS_PER_PAGE', 12);
define('SEARCH_MIN_LENGTH', 3);
define('TOAST_DURATION', 5000); // milliseconds

// Status Colors (for consistency)
define('STATUS_COLORS', [
    'pending' => 'amber',
    'confirmed' => 'green',
    'payment_pending' => 'blue',
    'payment_confirmed' => 'green',
    'in_progress' => 'amber',
    'review' => 'blue',
    'final_review' => 'purple',
    'completed' => 'green',
    'cancelled' => 'red'
]);

// Service Types
define('SERVICE_TYPES', [
    'video_editing' => 'Video Editing',
    'graphic_design' => 'Graphic Design',
    'social_media' => 'Social Media Content',
    'presentation' => 'Presentation Design'
]);

// Package Types
define('PACKAGE_TYPES', [
    'basic' => 'Basic',
    'standard' => 'Standard',
    'premium' => 'Premium'
]);
?>
