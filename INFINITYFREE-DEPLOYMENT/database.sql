-- ============================================
-- ACHAL MOBILE SHOP - DATABASE SCHEMA
-- For InfinityFree MySQL Database
-- ============================================

-- Drop existing tables if they exist
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS addresses;
DROP TABLE IF EXISTS sessions;
DROP TABLE IF EXISTS users;

-- ============================================
-- USERS TABLE
-- ============================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    role ENUM('customer', 'driver', 'admin') DEFAULT 'customer',
    status ENUM('active', 'blocked', 'pending') DEFAULT 'active',
    email_verified BOOLEAN DEFAULT FALSE,
    phone_verified BOOLEAN DEFAULT FALSE,
    profile_photo VARCHAR(255),
    referral_code VARCHAR(20) UNIQUE,
    referred_by VARCHAR(20),
    wallet_balance DECIMAL(10,2) DEFAULT 0.00,
    total_orders INT DEFAULT 0,
    total_spent DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    
    INDEX idx_email (email),
    INDEX idx_user_id (user_id),
    INDEX idx_role (role),
    INDEX idx_status (status),
    INDEX idx_referral_code (referral_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SESSIONS TABLE (Custom session handling)
-- ============================================
CREATE TABLE sessions (
    id VARCHAR(128) PRIMARY KEY,
    data TEXT,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ADDRESSES TABLE
-- ============================================
CREATE TABLE addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    address_id VARCHAR(50) UNIQUE NOT NULL,
    user_id VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255),
    landmark VARCHAR(100),
    city VARCHAR(50) NOT NULL,
    state VARCHAR(50) NOT NULL,
    pincode VARCHAR(10) NOT NULL,
    address_type ENUM('home', 'work', 'other') DEFAULT 'home',
    is_default BOOLEAN DEFAULT FALSE,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_address_id (address_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- PRODUCTS TABLE
-- ============================================
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(200) NOT NULL,
    sku VARCHAR(50) UNIQUE,
    category VARCHAR(50) NOT NULL,
    brand VARCHAR(50),
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    mrp DECIMAL(10,2),
    discount_percent INT DEFAULT 0,
    stock INT DEFAULT 0,
    min_stock INT DEFAULT 5,
    image VARCHAR(255),
    images TEXT, -- JSON array of image URLs
    specifications TEXT, -- JSON object
    features TEXT, -- JSON array
    status ENUM('active', 'inactive', 'out_of_stock') DEFAULT 'active',
    views INT DEFAULT 0,
    sales INT DEFAULT 0,
    rating DECIMAL(3,2) DEFAULT 0.00,
    reviews_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_product_id (product_id),
    INDEX idx_category (category),
    INDEX idx_brand (brand),
    INDEX idx_status (status),
    INDEX idx_price (price),
    FULLTEXT idx_search (name, description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ORDERS TABLE
-- ============================================
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(50) UNIQUE NOT NULL,
    user_id VARCHAR(50) NOT NULL,
    driver_id VARCHAR(50),
    address_id VARCHAR(50),
    
    -- Order details
    items_count INT NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    shipping_fee DECIMAL(10,2) DEFAULT 0.00,
    tax DECIMAL(10,2) DEFAULT 0.00,
    discount DECIMAL(10,2) DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL,
    
    -- Payment
    payment_method ENUM('cod', 'upi', 'card', 'wallet', 'pay_later') NOT NULL,
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    payment_id VARCHAR(100),
    
    -- Status
    status ENUM('pending', 'confirmed', 'packed', 'shipped', 'out_for_delivery', 'delivered', 'cancelled', 'returned') DEFAULT 'pending',
    
    -- Delivery
    delivery_otp VARCHAR(6),
    delivery_photo VARCHAR(255),
    delivery_signature VARCHAR(255),
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    confirmed_at TIMESTAMP NULL,
    shipped_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    
    -- Notes
    customer_notes TEXT,
    admin_notes TEXT,
    cancellation_reason TEXT,
    
    INDEX idx_order_id (order_id),
    INDEX idx_user_id (user_id),
    INDEX idx_driver_id (driver_id),
    INDEX idx_status (status),
    INDEX idx_payment_status (payment_status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ORDER ITEMS TABLE
-- ============================================
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(50) NOT NULL,
    product_id VARCHAR(50) NOT NULL,
    product_name VARCHAR(200) NOT NULL,
    product_image VARCHAR(255),
    variant VARCHAR(100),
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    
    INDEX idx_order_id (order_id),
    INDEX idx_product_id (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INSERT DEFAULT DATA
-- ============================================

-- Default Admin User
-- Email: admin@achalmobileshop.com
-- Password: Admin@123
INSERT INTO users (user_id, name, email, password, role, status, email_verified) 
VALUES (
    'admin_001',
    'Admin User',
    'admin@achalmobileshop.com',
    '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyYzNW6J7.Ky',
    'admin',
    'active',
    TRUE
);

-- Sample Products
INSERT INTO products (product_id, name, sku, category, brand, description, price, mrp, discount_percent, stock, image, status) VALUES
('prod_001', 'iPhone 15 Pro', 'IPH15PRO128', 'smartphones', 'Apple', 'Latest iPhone with A17 Pro chip, 128GB storage', 129900.00, 134900.00, 4, 50, 'iphone15pro.jpg', 'active'),
('prod_002', 'Samsung Galaxy S24', 'SAMS24128', 'smartphones', 'Samsung', 'Flagship Samsung phone with AI features, 128GB', 79999.00, 84999.00, 6, 75, 'galaxys24.jpg', 'active'),
('prod_003', 'OnePlus 12', 'OP12256', 'smartphones', 'OnePlus', 'Flagship killer with Snapdragon 8 Gen 3, 256GB', 64999.00, 69999.00, 7, 100, 'oneplus12.jpg', 'active'),
('prod_004', 'AirPods Pro 2', 'AIRPODSPRO2', 'accessories', 'Apple', 'Active Noise Cancellation, Spatial Audio', 24900.00, 26900.00, 7, 200, 'airpodspro2.jpg', 'active'),
('prod_005', 'Samsung Galaxy Buds 2 Pro', 'GALBUDS2PRO', 'accessories', 'Samsung', 'Premium wireless earbuds with ANC', 14999.00, 17999.00, 17, 150, 'galaxybuds2pro.jpg', 'active');

-- Sample Customer
-- Email: customer@example.com
-- Password: Customer@123
INSERT INTO users (user_id, name, email, phone, password, role, status, email_verified, referral_code) 
VALUES (
    'user_001',
    'John Doe',
    'customer@example.com',
    '+919876543210',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'customer',
    'active',
    TRUE,
    'ACHAL001'
);

-- Sample Driver
-- Email: driver@example.com
-- Password: Driver@123
INSERT INTO users (user_id, name, email, phone, password, role, status, email_verified) 
VALUES (
    'driver_001',
    'Rajesh Kumar',
    'driver@example.com',
    '+919876543211',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'driver',
    'active',
    TRUE
);

-- ============================================
-- VIEWS FOR REPORTING
-- ============================================

-- Daily Sales Summary
CREATE OR REPLACE VIEW daily_sales AS
SELECT 
    DATE(created_at) as date,
    COUNT(*) as total_orders,
    SUM(total) as total_revenue,
    AVG(total) as avg_order_value,
    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders
FROM orders
GROUP BY DATE(created_at)
ORDER BY date DESC;

-- Product Performance
CREATE OR REPLACE VIEW product_performance AS
SELECT 
    p.product_id,
    p.name,
    p.category,
    p.price,
    p.stock,
    p.views,
    p.sales,
    p.rating,
    p.reviews_count,
    COALESCE(SUM(oi.quantity), 0) as total_sold,
    COALESCE(SUM(oi.total), 0) as total_revenue
FROM products p
LEFT JOIN order_items oi ON p.product_id = oi.product_id
GROUP BY p.product_id
ORDER BY total_revenue DESC;

-- Customer Lifetime Value
CREATE OR REPLACE VIEW customer_ltv AS
SELECT 
    u.user_id,
    u.name,
    u.email,
    u.total_orders,
    u.total_spent,
    u.created_at as customer_since,
    DATEDIFF(NOW(), u.created_at) as days_as_customer,
    CASE 
        WHEN u.total_orders = 0 THEN 0
        ELSE u.total_spent / u.total_orders 
    END as avg_order_value
FROM users u
WHERE u.role = 'customer'
ORDER BY u.total_spent DESC;

-- ============================================
-- STORED PROCEDURES
-- ============================================

DELIMITER //

-- Update product stock after order
CREATE PROCEDURE update_product_stock(
    IN p_product_id VARCHAR(50),
    IN p_quantity INT,
    IN p_operation ENUM('add', 'subtract')
)
BEGIN
    IF p_operation = 'subtract' THEN
        UPDATE products 
        SET stock = stock - p_quantity,
            sales = sales + p_quantity
        WHERE product_id = p_product_id;
    ELSE
        UPDATE products 
        SET stock = stock + p_quantity
        WHERE product_id = p_product_id;
    END IF;
END //

-- Update user stats after order
CREATE PROCEDURE update_user_stats(
    IN p_user_id VARCHAR(50),
    IN p_order_total DECIMAL(10,2)
)
BEGIN
    UPDATE users 
    SET total_orders = total_orders + 1,
        total_spent = total_spent + p_order_total
    WHERE user_id = p_user_id;
END //

DELIMITER ;

-- ============================================
-- TRIGGERS
-- ============================================

DELIMITER //

-- Auto-update product status based on stock
CREATE TRIGGER update_product_status_on_stock_change
AFTER UPDATE ON products
FOR EACH ROW
BEGIN
    IF NEW.stock = 0 AND OLD.stock > 0 THEN
        UPDATE products SET status = 'out_of_stock' WHERE id = NEW.id;
    ELSEIF NEW.stock > 0 AND OLD.stock = 0 THEN
        UPDATE products SET status = 'active' WHERE id = NEW.id;
    END IF;
END //

DELIMITER ;

-- ============================================
-- INDEXES FOR PERFORMANCE
-- ============================================

-- Composite indexes for common queries
CREATE INDEX idx_orders_user_status ON orders(user_id, status);
CREATE INDEX idx_orders_driver_status ON orders(driver_id, status);
CREATE INDEX idx_products_category_status ON products(category, status);
CREATE INDEX idx_users_role_status ON users(role, status);

-- ============================================
-- GRANT PERMISSIONS (if needed)
-- ============================================
-- GRANT ALL PRIVILEGES ON achalshop.* TO 'your_username'@'localhost';
-- FLUSH PRIVILEGES;

-- ============================================
-- DATABASE SETUP COMPLETE
-- ============================================
-- Default Login Credentials:
-- 
-- ADMIN:
-- Email: admin@achalmobileshop.com
-- Password: Admin@123
--
-- CUSTOMER:
-- Email: customer@example.com
-- Password: Customer@123
--
-- DRIVER:
-- Email: driver@example.com
-- Password: Driver@123
-- ============================================
