<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

// 获取筛选参数
$selectedCategories = isset($_GET['category']) && is_array($_GET['category']) ? $_GET['category'] : [];
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$itemsPerPage = 12;  // 3行 × 4列

$meals = [];
$totalItems = 0;
$totalPages = 0;
$allCategories = [];

if ($pdo instanceof PDO) {
    // 获取所有可用分类
    $catStmt = $pdo->query("SELECT DISTINCT category FROM meals WHERE is_active = 1 ORDER BY category");
    $allCategories = $catStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // 构建条件（全部使用位置参数，避免混合）
    $where = "WHERE is_active = 1";
    $params = [];
    
    if (!empty($selectedCategories)) {
        $placeholders = implode(',', array_fill(0, count($selectedCategories), '?'));
        $where .= " AND category IN ($placeholders)";
        $params = array_merge($params, $selectedCategories);
    }
    
    if (!empty($keyword)) {
        $where .= " AND name LIKE ?";
        $params[] = "%$keyword%";
    }
    
    // 统计总数
    $countSql = "SELECT COUNT(*) FROM meals $where";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalItems = (int)$countStmt->fetchColumn();
    $totalPages = ceil($totalItems / $itemsPerPage);
    
    // 修正当前页
    if ($currentPage > $totalPages && $totalPages > 0) {
        $currentPage = $totalPages;
    }
    $offset = ($currentPage - 1) * $itemsPerPage;
    
    // 获取当前页数据
    $sql = "SELECT * FROM meals $where ORDER BY category, name LIMIT ? OFFSET ?";
    $params[] = $itemsPerPage;
    $params[] = $offset;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $meals = $stmt->fetchAll();
}

render_header('Full Menu', 'menu', cart_count(), $flashMessages ?? [], $dbError ?? null);
?>

<!-- 页面标题区域 -->
<section class="page-hero" style="background: var(--bg-seashell); padding-block: 120px 60px;">
    <div class="container">
        <p class="page-kicker">Our Complete Collection</p>
        <h1 class="title h1 page-title">Full Menu</h1>
        <p class="page-description">
            Browse all available meals from our canteen. Use filters to find what you love.
        </p>
    </div>
</section>

<section class="container" style="padding-block: 40px;">
    <!-- 筛选表单 -->
    <div class="filter-panel" style="background: #fff; border-radius: 24px; padding: 24px; margin-bottom: 32px; box-shadow: var(--shadow);">
        <form method="GET" action="" id="filterForm">
            <!-- 关键词搜索框 -->
            <div style="margin-bottom: 24px;">
                <label style="display: block; font-weight: var(--weight-semiBold); margin-bottom: 8px;">Search by name</label>
                <input type="text" name="keyword" value="<?= e($keyword) ?>" placeholder="e.g., Chicken, Rice, Noodle..." 
                       style="width: 100%; max-width: 400px; padding: 12px 16px; border: 1px solid var(--border-platinum); border-radius: 40px;">
            </div>
            
            <!-- 分类多选（网格整齐排列，整体居中） -->
            <div style="margin-bottom: 24px;">
                <label style="display: block; font-weight: var(--weight-semiBold); margin-bottom: 12px;">Categories (select multiple)</label>
                <div class="categories-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; justify-items: center;">
                    <?php foreach ($allCategories as $cat): ?>
                        <label style="display: flex; align-items: center; justify-content: center; gap: 8px; background: var(--bg-isabelline); padding: 8px 16px; border-radius: 40px; width: 100%; max-width: 200px; cursor: pointer; transition: var(--transition-1);">
                            <input type="checkbox" name="category[]" value="<?= e($cat) ?>" 
                                   <?= in_array($cat, $selectedCategories) ? 'checked' : '' ?>>
                            <span><?= e($cat) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div style="display: flex; justify-content: center; gap: 16px; flex-wrap: wrap;">
                <button type="submit" class="btn" style="padding: 12px 28px;">Apply Filters</button>
                <a href="?<?= http_build_query(['page' => $currentPage]) ?>" class="btn btn--secondary" style="padding: 12px 28px;">Clear All</a>
            </div>
        </form>
    </div>

    <?php if (!$pdo instanceof PDO): ?>
        <div class="empty-card">
            <h2 class="title h2">Service unavailable</h2>
            <p>Unable to load menu at this time. Please try again later.</p>
        </div>
    <?php elseif (empty($meals)): ?>
        <div class="empty-card">
            <h2 class="title h2">No meals found</h2>
            <p>Try changing your search or filter criteria.</p>
        </div>
    <?php else: ?>
        <!-- 显示结果统计 -->
        <p style="margin-bottom: 20px; font-size: 1.4rem; text-align: center;">Found <strong><?= $totalItems ?></strong> meal(s)</p>
        
        <!-- 菜品网格：桌面端4列，平板2列，手机1列 -->
        <div class="menu-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 28px;">
            <?php foreach ($meals as $meal): ?>
                <article class="meal-card" style="display: flex; flex-direction: column; height: 100%; background: #fff; border-radius: 24px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
                    <div class="meal-card__image" style="aspect-ratio: 1; width: 100%; overflow: hidden; background: #f7f3ef;">
                        <img src="<?= e($meal['image_path'] ?: './assets/images/placeholder.jpg') ?>" 
                             alt="<?= e($meal['name']) ?>"
                             style="width: 100%; height: 100%; object-fit: cover; display: block;">
                    </div>
                    <div class="meal-card__meta" style="padding: 20px 16px 24px;">
                        <div class="meal-card__top" style="display: flex; justify-content: space-between; align-items: baseline; flex-wrap: wrap;">
                            <div>
                                <p class="meal-card__category" style="color: var(--text-sinopia); text-transform: uppercase; font-size: 1.2rem;">
                                    <?= e($meal['category']) ?>
                                </p>
                                <h2 class="title h3 meal-card__title" style="margin-block: 8px 6px; font-size: 1.8rem;">
                                    <?= e($meal['name']) ?>
                                </h2>
                            </div>
                            <strong style="font-size: 1.8rem; color: var(--text-rich-black-fogra-29);">
                                <?= money((float) $meal['price']) ?>
                            </strong>
                        </div>
                        <p style="font-size: 1.4rem; margin: 8px 0 20px; color: var(--text-granite-gray); line-height: 1.4;">
                            <?= e($meal['description']) ?>
                        </p>
                        <form method="post" action="./index.php" class="form-inline" style="margin-top: auto; display: flex; gap: 8px; align-items: center;">
                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="meal_id" value="<?= (int) $meal['id'] ?>">
                            <input class="small-input" type="number" name="quantity" value="1" min="1" max="20" style="width: 70px; padding: 10px; border: 1px solid var(--border-platinum); border-radius: 40px;">
                            <button class="btn" type="submit" style="padding: 10px 20px;">Add</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <!-- 分页导航（保留筛选参数） -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination" style="display: flex; justify-content: center; gap: 8px; margin-top: 48px; flex-wrap: wrap;">
                <?php
                $baseParams = $_GET;
                unset($baseParams['page']);
                $queryBase = http_build_query($baseParams);
                $queryBase = $queryBase ? '?' . $queryBase . '&' : '?';
                ?>
                <?php if ($currentPage > 1): ?>
                    <a href="<?= $queryBase ?>page=<?= $currentPage - 1 ?>" class="btn btn--secondary" style="padding: 10px 18px;">&laquo; Prev</a>
                <?php else: ?>
                    <span class="btn btn--secondary" style="opacity: 0.5; cursor: not-allowed; padding: 10px 18px;">&laquo; Prev</span>
                <?php endif; ?>

                <?php
                $startPage = max(1, $currentPage - 2);
                $endPage = min($totalPages, $currentPage + 2);
                if ($startPage > 1): ?>
                    <a href="<?= $queryBase ?>page=1" class="btn btn--secondary" style="padding: 10px 18px;">1</a>
                    <?php if ($startPage > 2): ?>
                        <span class="btn btn--secondary" style="opacity: 0.6;">...</span>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <a href="<?= $queryBase ?>page=<?= $i ?>" class="btn <?= $i === $currentPage ? '' : 'btn--secondary' ?>" 
                       style="padding: 10px 18px; <?= $i === $currentPage ? 'background-color: var(--bg-sinopia); color: white;' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($endPage < $totalPages): ?>
                    <?php if ($endPage < $totalPages - 1): ?>
                        <span class="btn btn--secondary" style="opacity: 0.6;">...</span>
                    <?php endif; ?>
                    <a href="<?= $queryBase ?>page=<?= $totalPages ?>" class="btn btn--secondary" style="padding: 10px 18px;"><?= $totalPages ?></a>
                <?php endif; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="<?= $queryBase ?>page=<?= $currentPage + 1 ?>" class="btn btn--secondary" style="padding: 10px 18px;">Next &raquo;</a>
                <?php else: ?>
                    <span class="btn btn--secondary" style="opacity: 0.5; cursor: not-allowed; padding: 10px 18px;">Next &raquo;</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php render_footer(); ?>