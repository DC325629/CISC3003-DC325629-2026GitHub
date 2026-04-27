<?php
$config = require __DIR__ . '/config/app.php';
$dbConfig = $config['db'];
$host = $dbConfig['host'];
$port = $dbConfig['port'];
$dbname = $dbConfig['database'];
$username = $dbConfig['username'];
$password = $dbConfig['password'];

$category = $_GET['category'] ?? '';
$keyword = $_GET['keyword'] ?? '';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    $catStmt = $pdo->query("SELECT DISTINCT category FROM meals ORDER BY category");
    $categories = $catStmt->fetchAll();
    
    $sql = "SELECT * FROM meals WHERE 1=1";
    $params = [];
    if (!empty($category)) {
        $sql .= " AND category = ?";
        $params[] = $category;
    }
    if (!empty($keyword)) {
        $sql .= " AND name LIKE ?";
        $params[] = "%$keyword%";
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $meals = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - Crispy College Meals</title>
    <link rel="shortcut icon" href="./assets/images/favicon.svg" type="image/svg+xml">
    <link href="https://fonts.googleapis.com/css2?family=Forum&family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./assets/css/style.css">
    <link rel="stylesheet" href="./assets/css/auth.css">
</head>
<body>

<header class="header" data-header>
    <div class="container">
        <a href="menu.php" class="logo">
            <img src="./assets/images/logo.svg" width="130" height="45" alt="Crispy home">
        </a>
    </div>
</header>

<main>
    <div class="container" style="padding-top: 120px;">
        <h1 class="auth-title" style="text-align: center;">Our Menu</h1>

        <div class="filter-bar" style="display: flex; flex-wrap: wrap; justify-content: center; gap: 15px; margin: 30px 0;">
            <a href="menu.php" class="btn" style="background: var(--bg-sinopia); padding: 8px 24px;">All</a>
            <?php foreach ($categories as $cat): ?>
                <a href="menu.php?category=<?php echo urlencode($cat['category']); ?>" class="btn" style="background: var(--bg-sinopia); padding: 8px 24px;">
                    <?php echo htmlspecialchars($cat['category']); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <form method="get" style="display: flex; justify-content: center; gap: 10px; margin-bottom: 40px;">
            <input type="text" name="keyword" placeholder="Search by name..." value="<?php echo htmlspecialchars($keyword); ?>" style="padding: 10px; width: 250px; border-radius: 5px; border: 1px solid #ddd;">
            <button type="submit" class="btn" style="background: var(--bg-sinopia); padding: 10px 20px;">Search</button>
        </form>

        <div class="grid-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 30px;">
            <?php if (count($meals) > 0): ?>
                <?php foreach ($meals as $meal): ?>
                    <div class="popular-card" style="text-align: center; padding: 15px;">
                        <?php if (!empty($meal['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($meal['image_path']); ?>" alt="<?php echo htmlspecialchars($meal['name']); ?>" style="width: 100%; height: 150px; object-fit: cover; border-radius: 8px;">
                        <?php else: ?>
                            <div style="height:150px; background:#eee; border-radius:8px;"></div>
                        <?php endif; ?>
                        <h3 class="card-title"><?php echo htmlspecialchars($meal['name']); ?></h3>
                        <p class="price" style="color: var(--text-sinopia); font-weight: bold;">$<?php echo number_format($meal['price'], 2); ?></p>
                        <p class="category" style="font-size: 0.8rem;"><?php echo htmlspecialchars($meal['category']); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center;">No meals found.</p>
            <?php endif; ?>
        </div>
    </div>
</main>

<script src="./assets/js/script.js"></script>
<script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>
</html>
