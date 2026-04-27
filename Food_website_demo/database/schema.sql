-- ============================================
-- 数据库初始化脚本（结构部分）
-- 包含：数据库、表、取餐时段数据
-- available_time 仅包含 breakfast / lunch_dinner
-- ============================================

CREATE DATABASE IF NOT EXISTS `cisc3003_team05` 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `cisc3003_team05`;

-- 1. 菜品表（时段控制字段仅 breakfast / lunch_dinner）
CREATE TABLE IF NOT EXISTS meals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    category VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    available_time ENUM('breakfast', 'lunch_dinner') NOT NULL DEFAULT 'lunch_dinner'
        COMMENT 'breakfast: 早餐时段(07:30-10:30), lunch_dinner: 午晚餐时段(11:30-14:30 & 17:30-21:00)',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. 取餐时段表
CREATE TABLE IF NOT EXISTS pickup_slots (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slot_value VARCHAR(10) NOT NULL UNIQUE,
    label VARCHAR(50) NOT NULL,
    capacity INT UNSIGNED NOT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. 订单表
CREATE TABLE IF NOT EXISTS orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_code VARCHAR(24) NOT NULL UNIQUE,
    customer_name VARCHAR(150) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    pickup_date DATE NOT NULL,
    pickup_slot_id INT UNSIGNED NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    note TEXT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'Confirmed',
    subtotal DECIMAL(10, 2) NOT NULL,
    service_fee DECIMAL(10, 2) NOT NULL,
    total DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_pickup_slot FOREIGN KEY (pickup_slot_id) REFERENCES pickup_slots(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. 订单明细表
CREATE TABLE IF NOT EXISTS order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    meal_id INT UNSIGNED NOT NULL,
    meal_name VARCHAR(150) NOT NULL,
    meal_price DECIMAL(10, 2) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    line_total DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_meal FOREIGN KEY (meal_id) REFERENCES meals(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. 用户表（认证）
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL UNIQUE,
    full_name VARCHAR(150) NOT NULL,
    phone VARCHAR(50) DEFAULT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email_verified TINYINT(1) NOT NULL DEFAULT 0,
    verification_token VARCHAR(64) DEFAULT NULL,
    verification_token_expires DATETIME DEFAULT NULL,
    reset_token VARCHAR(64) DEFAULT NULL,
    reset_expires DATETIME DEFAULT NULL,
    last_login DATETIME DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. 插入取餐时段数据
INSERT INTO pickup_slots (slot_value, label, capacity, sort_order) VALUES
    ('11:30', '11:30 AM', 6, 1),
    ('12:00', '12:00 PM', 8, 2),
    ('12:30', '12:30 PM', 8, 3),
    ('13:00', '1:00 PM', 6, 4),
    ('17:30', '5:30 PM', 5, 5),
    ('18:00', '6:00 PM', 5, 6)
ON DUPLICATE KEY UPDATE
    label = VALUES(label),
    capacity = VALUES(capacity),
    sort_order = VALUES(sort_order);