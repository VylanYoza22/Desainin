<?php
$host = "localhost";    // biasanya localhost
$user = "root";         // default user MySQL
$pass = "";             // default password kosong di XAMPP
$db   = "desainin_db";  // nama database

$conn = new mysqli($host, $user, $pass, $db);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>