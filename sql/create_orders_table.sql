-- SQL untuk membuat tabel orders (pesanan)
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    service_type ENUM('video_editing', 'graphic_design', 'social_media', 'presentation') NOT NULL,
    package_type ENUM('basic', 'standard', 'premium') NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    budget DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    deadline DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert sample data untuk testing
INSERT INTO orders (user_id, service_type, package_type, title, description, budget, status, deadline) VALUES
(1, 'video_editing', 'premium', 'Video Promosi Produk', 'Edit video promosi untuk launching produk baru dengan durasi 2 menit', 150000, 'completed', '2025-08-20'),
(1, 'graphic_design', 'standard', 'Logo Perusahaan', 'Desain logo modern untuk startup teknologi', 75000, 'in_progress', '2025-08-30'),
(2, 'social_media', 'basic', 'Konten Instagram', 'Desain feed Instagram untuk 1 minggu', 50000, 'pending', '2025-09-05');
