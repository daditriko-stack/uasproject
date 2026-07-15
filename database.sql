-- Skema Database WarungKu (Toko Kelontong)
CREATE DATABASE IF NOT EXISTS uas_warungku;
USE uas_warungku;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'customer') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    icon VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    image_url VARCHAR(255) DEFAULT 'default.jpg',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    shipping_address TEXT,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Insert Dummy Data (Password for both is 'password123' using password_hash)
INSERT INTO users (name, username, password, role) VALUES 
('Administrator', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Customer Satu', 'customer1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer');

INSERT INTO categories (name, slug, icon) VALUES 
('Sembako', 'sembako', 'fa-box'),
('Minuman', 'minuman', 'fa-bottle-water'),
('Camilan', 'camilan', 'fa-cookie'),
('Kebutuhan Rumah', 'kebutuhan-rumah', 'fa-pump-soap');

INSERT INTO products (category_id, name, description, price, stock, image_url) VALUES 
(1, 'Beras Ramos 5kg', 'Beras putih kualitas premium.', 65000.00, 20, 'beras.jpg'),
(1, 'Gula Pasir 1kg', 'Gula pasir kristal putih alami.', 15000.00, 50, 'gula.jpg'),
(2, 'Teh Botol 450ml', 'Teh manis dalam botol ukuran sedang.', 5000.00, 100, 'tehbotol.jpg'),
(3, 'Keripik Kentang Rasa Sapi Panggang', 'Keripik kentang renyah dan gurih.', 12000.00, 3, 'keripik.jpg'), -- Stok kritis untuk test alert
(4, 'Sabun Cuci Piring 800ml', 'Sabun cuci piring aroma jeruk nipis.', 18000.00, 15, 'sabun_cuci.jpg');

