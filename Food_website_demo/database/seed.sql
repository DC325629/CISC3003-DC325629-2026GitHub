-- ============================================
-- 菜品数据插入脚本
-- 名称格式：英文 (中文)
-- 供应时段：所有 Sides（單點）→ breakfast
--           其他类别（自選飯食、特色飯食、特色麵、小食）→ lunch_dinner
-- ============================================

USE `cisc3003_team05`;

INSERT INTO meals (slug, name, category, description, price, image_path, is_active, available_time) VALUES
-- 自選飯食 (Multiple Choice Meal) → lunch_dinner
('1-meat-1-vegetable', '1 Meat 1 Vegetable (1肉1菜)', 'Multiple Choice Meal', '1 Meat 1 Vegetable', 28.00, '', 1, 'lunch_dinner'),
('2-meat-1-vegetable', '2 Meat 1 Vegetable (2肉1菜)', 'Multiple Choice Meal', '2 Meat 1 Vegetable', 33.00, '', 1, 'lunch_dinner'),
('3-meat-1-vegetable', '3 Meat 1 Vegetable (3肉1菜)', 'Multiple Choice Meal', '3 Meat 1 Vegetable', 38.00, '', 1, 'lunch_dinner'),
('brown-rice', 'Brown Rice (五谷雜糧飯)', 'Multiple Choice Meal', 'Change Rice to Brown Rice', 28.00, '', 1, 'lunch_dinner'),

-- 特色飯食 (Featured Rice) → lunch_dinner
('rice-with-chicken-cheese-egg', 'Rice with Chicken, Cheese and Egg (滑蛋芝士雞肉飯)', 'Featured Rice', 'Rice w/ Chicken, Cheese & Egg', 36.00, '', 1, 'lunch_dinner'),
('rice-with-eel-cheese-egg', 'Rice with Eel, Cheese and Egg (滑蛋芝士鳗魚飯)', 'Featured Rice', 'Rice w/ Eel, Cheese & Egg', 42.00, '', 1, 'lunch_dinner'),
('rice-with-beef-pork-shrimp-cheese-egg', 'Rice with Beef, Pork, Shrimp, Cheese and Egg (滑蛋芝士三寶飯)', 'Featured Rice', 'Rice w/ Beef, Pork, Shrimp, Cheese & Egg', 42.00, '', 1, 'lunch_dinner'),
('bbq-pork-rice-scrambled-egg', 'BBQ Pork Rice with Scrambled Egg (叉燒滑蛋飯)', 'Featured Rice', 'BBQ Pork Rice w/ Scrambled Egg', 38.00, '', 1, 'lunch_dinner'),
('chicken-chop-rice-thai-curry', 'Chicken Chop Rice with Thai Curry (泰式咖哩雞扒飯)', 'Featured Rice', 'Chicken Chop Rice w/ Thai Curry', 38.00, '', 1, 'lunch_dinner'),
('pork-chop-rice-thai-curry', 'Pork Chop Rice with Thai Curry (泰式咖哩豬扒飯)', 'Featured Rice', 'Pork Chop Rice w/ Thai Curry', 38.00, '', 1, 'lunch_dinner'),
('sizzling-fried-rice-chicken-pineapple', 'Sizzling Fried Rice with Chicken and Pineapple (鐵板菠蘿雞粒炒飯)', 'Featured Rice', 'Sizzling Fried Rice w/ Chicken & Pineapple', 42.00, '', 1, 'lunch_dinner'),

-- 特色麵 (Featured Noodle) → lunch_dinner
('tomato-and-egg-noodle', 'Tomato and Egg Noodle (蕃茄雞蛋麵)', 'Featured Noodle', 'Tomato & Egg Noodle (Choice of Yellow Noodle / Flat Rice Noodle / Instant Noodle)', 28.00, '', 1, 'lunch_dinner'),
('hot-and-spicy-duck-mixed-ball-noodle', 'Hot and Spicy Duck and Mixed Ball Noodle (鴨血雞丸雞肉麻辣麵)', 'Featured Noodle', 'Hot & Spicy Duck & Mixed Ball Noodle (Choice of Yellow Noodle / Flat Rice Noodle / Instant Noodle)', 38.00, '', 1, 'lunch_dinner'),
('bak-kut-teh', 'Bak Kut Teh (肉骨茶)', 'Featured Noodle', 'Bak Kut Teh', 38.00, '', 1, 'lunch_dinner'),
('beef-rice-vermicelli-golden-soup', 'Beef Rice Vermicelli in Golden Soup (酸湯肥牛米線)', 'Featured Noodle', 'Beef Rice Vermicelli in Golden Soup', 42.00, '', 1, 'lunch_dinner'),

-- 小食 (Snacks) → lunch_dinner
('rice-roll', 'Rice Roll (雞錦飯團)', 'Snack', 'Rice Roll', 13.00, '', 1, 'lunch_dinner'),
('cake', 'Cake (雞錦蛋糕)', 'Snack', 'Cake', 25.00, '', 1, 'lunch_dinner'),
('fried-chicken-wings-honey-sauce-4pcs', 'Fried Chicken Wings with Honey Sauce (4 pcs) (蜜糖脆炸雞翼(4隻))', 'Snack', 'Fried Chicken Wings w/ Honey Sauce (4pcs)', 24.00, '', 1, 'lunch_dinner'),
('hot-spicy-crispy-pork', 'Hot and Spicy Crispy Pork (麻辣小酥肉)', 'Snack', 'Hot & Spicy Crispy Pork', 28.00, '', 1, 'lunch_dinner'),
('korean-rice-cake-chicken-chili-sauce', 'Korean Rice Cake and Chicken with Chili Sauce (韓式辣醬炸年糕)', 'Snack', 'Korean Rice Cake & Chicken w/ Chili Sauce', 32.00, '', 1, 'lunch_dinner'),

-- 單點 (Sides) → 仅早餐可点 (breakfast)
('boiled-egg', 'Boiled Egg (燴雞蛋)', 'Side', 'Boiled Egg', 3.00, '', 1, 'breakfast'),
('tea-egg', 'Tea Egg (茶葉蛋)', 'Side', 'Tea Egg', 3.00, '', 1, 'breakfast'),
('steamed-pork-cabbage-bun', 'Steamed Pork and Cabbage Bun (菜肉包)', 'Side', 'Steamed Pork and Cabbage Bun', 5.00, '', 1, 'breakfast'),
('chinese-fried-dough-sticks', 'Chinese Fried Dough Sticks (油條)', 'Side', 'Chinese Fried Dough Sticks', 8.00, '', 1, 'breakfast'),
('healthy-bread-limit-offer', 'Healthy Bread (Limit Offer) (健康麵包(限售))', 'Side', 'Bun (Limit Offer)', 8.00, '', 1, 'breakfast'),
('corn', 'Corn (蛤粟米)', 'Side', 'Corn', 10.00, '', 1, 'breakfast'),
('pork-bone-congee', 'Pork Bone Congee (咸猪骨粥)', 'Side', 'Pork Bone Congee', 18.00, '', 1, 'breakfast'),
('stir-fried-noodles-egg-vegetables', 'Stir-fried Noodles with Egg and Vegetables (雞蛋雞菜炒麵)', 'Side', 'Stir-fried Noodle w/ Veggie & Egg', 18.00, '', 1, 'breakfast'),
('steamed-glutinous-rice', 'Steamed Glutinous Rice (糯米糍)', 'Side', 'Steamed Glutinous Rice', 20.00, '', 1, 'breakfast'),
('pork-seaweed-wonton', 'Pork and Seaweed Wonton (紫菜肉碎小雲吞)', 'Side', 'Pork & Seaweed Wonton', 24.00, '', 1, 'breakfast'),
('chicken-chop-egg-instant-noodle', 'Chicken Chop and Egg Instant Noodle (雞扒蛋公仔麵)', 'Side', 'Chicken Chop & Egg Instant Noodle (can switch to raw noodle)', 32.00, '', 1, 'breakfast')
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    category = VALUES(category),
    description = VALUES(description),
    price = VALUES(price),
    image_path = VALUES(image_path),
    is_active = VALUES(is_active),
    available_time = VALUES(available_time);