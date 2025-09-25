<?php
/**
 * Order Status Management System
 * Provides detailed status tracking with progress percentages and timeline visualization
 * 
 * Features:
 * - Granular status definitions with progress tracking
 * - Timeline generation for order progress visualization
 * - Progress bar HTML generation
 * - Status update handling with validation
 */

// Status definitions with progress percentages and descriptions
function getStatusDefinitions() {
    return [
        'pending' => [
            'percentage' => 10,
            'label' => 'Pesanan Diterima',
            'description' => 'Pesanan Anda telah diterima dan menunggu konfirmasi',
            'icon' => 'fas fa-clock',
            'color' => 'amber',
            'badge' => 'BARU'
        ],
        'confirmed' => [
            'percentage' => 20,
            'label' => 'Dikonfirmasi',
            'description' => 'Pesanan dikonfirmasi dan masuk antrian pengerjaan',
            'icon' => 'fas fa-check-circle',
            'color' => 'green',
            'badge' => 'SELESAI'
        ],
        'payment_pending' => [
            'percentage' => 30,
            'label' => 'Menunggu Pembayaran',
            'description' => 'Menunggu konfirmasi pembayaran dari klien',
            'icon' => 'fas fa-credit-card',
            'color' => 'blue',
            'badge' => 'AKTIF'
        ],
        'payment_confirmed' => [
            'percentage' => 40,
            'label' => 'Pembayaran Dikonfirmasi',
            'description' => 'Pembayaran telah dikonfirmasi, siap untuk dikerjakan',
            'icon' => 'fas fa-money-check-alt',
            'color' => 'green',
            'badge' => 'SELESAI'
        ],
        'in_progress' => [
            'percentage' => 50,
            'label' => 'Sedang Dikerjakan',
            'description' => 'Tim kreatif sedang mengerjakan proyek Anda',
            'icon' => 'fas fa-cogs',
            'color' => 'amber',
            'badge' => 'AKTIF'
        ],
        'review' => [
            'percentage' => 70,
            'label' => 'Review & Revisi',
            'description' => 'Hasil kerja sedang direview dan siap untuk revisi jika diperlukan',
            'icon' => 'fas fa-search',
            'color' => 'blue',
            'badge' => 'AKTIF'
        ],
        'final_review' => [
            'percentage' => 85,
            'label' => 'Review Final',
            'description' => 'Tahap review final sebelum penyelesaian',
            'icon' => 'fas fa-eye',
            'color' => 'purple',
            'badge' => 'AKTIF'
        ],
        'completed' => [
            'percentage' => 100,
            'label' => 'Selesai',
            'description' => 'Proyek telah selesai dan siap digunakan',
            'icon' => 'fas fa-trophy',
            'color' => 'green',
            'badge' => 'SELESAI'
        ],
        'cancelled' => [
            'percentage' => 0,
            'label' => 'Dibatalkan',
            'description' => 'Pesanan telah dibatalkan',
            'icon' => 'fas fa-times-circle',
            'color' => 'red',
            'badge' => 'BATAL'
        ]
    ];
}

/**
 * Get all available order statuses with progress information
 * @return array Array of status definitions with progress, descriptions, and styling
 */
function getStatusInfo($status) {
    $definitions = getStatusDefinitions();
    return $definitions[$status] ?? $definitions['pending'];
}

/**
 * Get all available statuses for admin dropdown
 */
function getStatusOptions() {
    $definitions = getStatusDefinitions();
    $options = [];
    
    foreach ($definitions as $key => $info) {
        if ($key !== 'cancelled') { // Don't show cancelled in normal flow
            $options[$key] = $info['label'] . ' (' . $info['percentage'] . '%)';
        }
    }
    
    return $options;
}

/**
 * Update order status and progress
 */
function updateOrderStatus($conn, $orderId, $newStatus, $description = null) {
    $statusInfo = getStatusInfo($newStatus);
    $percentage = $statusInfo['percentage'];
    
    if ($description === null) {
        $description = $statusInfo['description'];
    }
    
    $stmt = $conn->prepare("UPDATE orders SET status = ?, progress_percentage = ?, status_description = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("sisi", $newStatus, $percentage, $description, $orderId);
    
    return $stmt->execute();
}

/**
 * Generate timeline steps for order progress visualization
 * @param string $currentStatus Current order status
 * @return array Array of timeline steps with completion status
 */
function getTimelineSteps($currentStatus = 'pending') {
    $definitions = getStatusDefinitions();
    $timeline = [];
    
    // Define the normal flow (excluding cancelled)
    $flowSteps = ['pending', 'confirmed', 'payment_pending', 'payment_confirmed', 'in_progress', 'review', 'final_review', 'completed'];
    
    $currentPercentage = $definitions[$currentStatus]['percentage'];
    
    foreach ($flowSteps as $step) {
        $info = $definitions[$step];
        $isCompleted = $info['percentage'] <= $currentPercentage;
        $isActive = $step === $currentStatus;
        
        $timeline[] = [
            'status' => $step,
            'label' => $info['label'],
            'description' => $info['description'],
            'percentage' => $info['percentage'],
            'icon' => $info['icon'],
            'color' => $info['color'],
            'badge' => $info['badge'],
            'is_completed' => $isCompleted,
            'is_active' => $isActive
        ];
    }
    
    return $timeline;
}

/**
 * Generate progress bar HTML
 */
function generateProgressBar($percentage, $status = 'pending') {
    $statusInfo = getStatusInfo($status);
    $colorClass = 'bg-' . $statusInfo['color'] . '-500';
    
    return "
    <div class='w-full bg-gray-200 rounded-full h-3 mb-2'>
        <div class='{$colorClass} h-3 rounded-full transition-all duration-500' style='width: {$percentage}%'></div>
    </div>
    <div class='text-sm text-gray-600 text-center'>{$percentage}% - {$statusInfo['label']}</div>
    ";
}
?>
