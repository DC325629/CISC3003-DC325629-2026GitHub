-- ============================================
-- 数据库初始化脚本（结构部分）
-- 包含：数据库、表、取餐时段数据
-- available_time 仅包含 breakfast / lunch_dinner
-- 添加 users 表和 orders.user_id 外键，支持用户关联
-- ============================================

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

-- 3. 用户表（认证）
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL UNIQUE,
    full_name VARCHAR(150) NOT NULL,
    phone VARCHAR(50) DEFAULT NULL,
    password_hash VARCHAR(255) NOT NULL,
    balance DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    email_verified TINYINT(1) NOT NULL DEFAULT 0,
    verification_token VARCHAR(64) DEFAULT NULL,
    verification_expires DATETIME DEFAULT NULL,          -- 统一字段名（原 verification_token_expires）
    reset_token VARCHAR(64) DEFAULT NULL,
    reset_expires DATETIME DEFAULT NULL,
    last_login DATETIME DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. 订单表（添加 user_id 字段，允许空值，支持游客下单）
CREATE TABLE IF NOT EXISTS orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,                           -- 关联用户，可为空
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
    
    CONSTRAINT fk_orders_pickup_slot FOREIGN KEY (pickup_slot_id) REFERENCES pickup_slots(id),
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. 订单明细表
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

-- 6. 插入取餐时段数据
INSERT INTO pickup_slots (slot_value, label, capacity, sort_order) VALUES
    ('07:30', '07:30 AM', 10, 1),
    ('08:00', '08:00 AM', 12, 2),
    ('08:30', '08:30 AM', 12, 3),
    ('09:00', '09:00 AM', 12, 4),
    ('09:30', '09:30 AM', 12, 5),
    ('10:00', '10:00 AM', 12, 6),
    ('10:30', '10:30 AM', 12, 7),
    ('11:00', '11:00 AM', 12, 8),
    ('11:30', '11:30 AM', 12, 9),
    ('12:00', '12:00 PM', 12, 10),
    ('12:30', '12:30 PM', 12, 11),
    ('13:00', '1:00 PM', 12, 12),
    ('13:30', '1:30 PM', 12, 13),
    ('14:00', '2:00 PM', 12, 14),
    ('14:30', '2:30 PM', 12, 15),
    ('15:00', '3:00 PM', 12, 16),
    ('15:30', '3:30 PM', 12, 17),
    ('16:00', '4:00 PM', 12, 18),
    ('16:30', '4:30 PM', 12, 19),
    ('17:00', '5:00 PM', 12, 20),
    ('17:30', '5:30 PM', 12, 21),
    ('18:00', '6:00 PM', 12, 22),
    ('18:30', '6:30 PM', 12, 23),
    ('19:00', '7:00 PM', 12, 24),
    ('19:30', '7:30 PM', 12, 25),
    ('20:00', '8:00 PM', 12, 26),
    ('20:30', '8:30 PM', 12, 27),
    ('21:00', '9:00 PM', 12, 28)
ON DUPLICATE KEY UPDATE
    label = VALUES(label),
    capacity = VALUES(capacity),
    sort_order = VALUES(sort_order);