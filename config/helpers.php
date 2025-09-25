<?php
/**
 * Helper Functions for PKK2 Project
 * Common utility functions used across the application
 */

/**
 * Format currency to Indonesian Rupiah
 */
function formatCurrency($amount) {
    if (empty($amount) || $amount == 0) {
        return 'Tidak ditentukan';
    }
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

/**
 * Format date to Indonesian format
 */
function formatDate($date, $includeTime = false) {
    if (empty($date)) {
        return 'Tidak ditentukan';
    }
    
    $months = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
        5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
        9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
    ];
    
    $timestamp = strtotime($date);
    $day = date('d', $timestamp);
    $month = $months[(int)date('m', $timestamp)];
    $year = date('Y', $timestamp);
    
    $formatted = "$day $month $year";
    
    if ($includeTime) {
        $formatted .= ' ' . date('H:i', $timestamp);
    }
    
    return $formatted;
}

/**
 * Get service type display name
 */
function getServiceDisplayName($serviceType) {
    $services = [
        'video_editing' => '🎬 Video Editing',
        'graphic_design' => '🎨 Graphic Design',
        'social_media' => '📱 Social Media Content',
        'presentation' => '📊 Presentation Design'
    ];
    
    return $services[$serviceType] ?? ucfirst(str_replace('_', ' ', $serviceType));
}

/**
 * Get package type display name
 */
function getPackageDisplayName($packageType) {
    $packages = [
        'basic' => '💼 Basic',
        'standard' => '⭐ Standard',
        'premium' => '👑 Premium'
    ];
    
    return $packages[$packageType] ?? ucfirst($packageType);
}

/**
 * Truncate text with ellipsis
 */
function truncateText($text, $length = 100) {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}

/**
 * Generate random order reference number
 */
function generateOrderReference($orderId) {
    return 'DSN-' . date('Y') . '-' . str_pad($orderId, 4, '0', STR_PAD_LEFT);
}

/**
 * Calculate estimated completion date based on package type
 */
function calculateEstimatedCompletion($packageType, $serviceType) {
    $baseDays = [
        'basic' => 3,
        'standard' => 5,
        'premium' => 7
    ];
    
    $serviceMultiplier = [
        'video_editing' => 1.5,
        'graphic_design' => 1.0,
        'social_media' => 0.8,
        'presentation' => 1.2
    ];
    
    $days = ($baseDays[$packageType] ?? 5) * ($serviceMultiplier[$serviceType] ?? 1.0);
    
    return date('Y-m-d', strtotime('+' . ceil($days) . ' days'));
}

/**
 * Get time ago format
 */
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'Baru saja';
    if ($time < 3600) return floor($time/60) . ' menit lalu';
    if ($time < 86400) return floor($time/3600) . ' jam lalu';
    if ($time < 2592000) return floor($time/86400) . ' hari lalu';
    if ($time < 31536000) return floor($time/2592000) . ' bulan lalu';
    
    return floor($time/31536000) . ' tahun lalu';
}

/**
 * Validate WhatsApp number format
 */
function validateWhatsAppNumber($number) {
    // Remove all non-numeric characters
    $cleaned = preg_replace('/[^0-9]/', '', $number);
    
    // Check if it's between 10-15 digits
    if (strlen($cleaned) < 10 || strlen($cleaned) > 15) {
        return false;
    }
    
    // Check if it starts with valid Indonesian prefixes
    $validPrefixes = ['08', '62'];
    foreach ($validPrefixes as $prefix) {
        if (strpos($cleaned, $prefix) === 0) {
            return true;
        }
    }
    
    return false;
}

/**
 * Format WhatsApp number for display
 */
function formatWhatsAppNumber($number) {
    $cleaned = preg_replace('/[^0-9]/', '', $number);
    
    if (strpos($cleaned, '62') === 0) {
        // Convert 62xxx to 08xxx for display
        $cleaned = '0' . substr($cleaned, 2);
    }
    
    // Format as 08xx-xxxx-xxxx
    if (strlen($cleaned) >= 10) {
        return substr($cleaned, 0, 4) . '-' . substr($cleaned, 4, 4) . '-' . substr($cleaned, 8);
    }
    
    return $cleaned;
}
?>
