<?php
session_start();
require_once 'config/database.php';
require_once 'config/whatsapp_functions.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pesan  = trim($_POST['pesan']);
    $rating = (int) $_POST['rating'];
    $user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : null;
    $nama = null;
    
    // Jika user login, ambil data dari session/database
    if ($user_id && isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id) {
        // User yang login - ambil nama dari database
        $stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $user_data = $result->fetch_assoc();
            $nama = $user_data['full_name'];
        }
        $stmt->close();
    } else {
        // User tidak login - gunakan nama dari form
        $nama = isset($_POST['nama']) ? trim($_POST['nama']) : '';
        $user_id = null;
    }
    
    // Validasi input
    if (empty($nama) || empty($pesan) || $rating < 1 || $rating > 5) {
        header("Location: index.php#feedback?error=invalid_input");
        exit;
    }

    // Cek jumlah testimoni yang ada
    $count_result = $conn->query("SELECT COUNT(*) as total FROM feedback");
    $count_row = $count_result->fetch_assoc();
    $total_feedback = $count_row['total'];
    
    // Jika sudah ada 3 testimoni, hapus yang terlama
    if ($total_feedback >= 3) {
        $conn->query("DELETE FROM feedback ORDER BY created_at ASC LIMIT 1");
    }
    
    // Gunakan prepared statement untuk keamanan
    $stmt = $conn->prepare("INSERT INTO feedback (user_id, nama, pesan, rating, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("issi", $user_id, $nama, $pesan, $rating);
    
    if ($stmt->execute()) {
        // Siapkan data untuk WhatsApp notification
        $feedbackData = array(
            'nama' => $nama,
            'pesan' => $pesan,
            'rating' => $rating
        );
        
        // Kirim notifikasi WhatsApp ke admin
        try {
            $whatsappResult = sendFeedbackNotification($feedbackData);
            
            // Log aktivitas WhatsApp
            logWhatsAppActivity(
                ADMIN_WHATSAPP, 
                "Feedback notification from " . $nama, 
                $whatsappResult['success'] ? 'SUCCESS' : 'FAILED',
                $whatsappResult['response']
            );
            
        } catch (Exception $e) {
            // Log error tapi jangan gagalkan proses feedback
            error_log("WhatsApp Error: " . $e->getMessage());
        }
        
        // Redirect dengan pesan sukses
        header("Location: index.php#feedback?success=1");
    } else {
        // Redirect dengan pesan error
        header("Location: index.php#feedback?error=database_error");
    }
    $stmt->close();
}
?>
