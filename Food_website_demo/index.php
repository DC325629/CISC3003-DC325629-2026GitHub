<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

$meals = [];
$cart = ['items' => [], 'subtotal' => 0.0, 'service_fee' => 0.0, 'total' => 0.0];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo instanceof PDO) {
    ensure_csrf_token();
    
    $mealId = (int) ($_POST['meal_id'] ?? 0);
    $quantity = max(1, (int) ($_POST['quantity'] ?? 1));
    $mealExists = isset(meal_index($pdo)[$mealId]);
    
    if (!$mealExists) {
        flash('error', 'Selected meal was not found.');
        redirect(app_url('index.php'));
    }
    
    add_to_cart($mealId, $quantity);
    flash('success', 'Meal added to cart.');
    redirect(app_url('index.php#menu'));
}

if ($pdo instanceof PDO) {
    // 随机显示 3 个已上架的菜品
    $stmt = $pdo->prepare("SELECT * FROM meals WHERE is_active = 1 ORDER BY RAND() LIMIT 3");
    $stmt->execute();
    $meals = $stmt->fetchAll();
    $cart = cart_snapshot($pdo);
}

render_header('Home', 'home', cart_count(), $flashMessages, $dbError);
?>
<section class="hero landing-hero" aria-label="home">
  <div class="container">
    <div class="hero-content">
      <h1 class="h1 title hero-title">Crispy Chicken Burgers</h1>
      <p class="section-text">
        Fresh campus favorites, quick pickup times, and a smooth ordering flow for lunch and dinner.
      </p>
      <div class="wrapper">
        <img src="./assets/images/down-arrow.png" width="40" height="40" alt="arrow" class="arrow">
        <a href="./checkout.php" class="btn">
          <span class="span">Checkout Now</span>
          <ion-icon name="arrow-forward" aria-hidden="true"></ion-icon>
        </a>
      </div>
    </div>
    <figure class="hero-banner img-holder" style="--width: 632; --height: 606;">
      <img src="./assets/images/hero-1.jpg" width="632" height="606" alt="Burger meal" class="img-cover">
    </figure>
    <img src="./assets/images/hero-shape-1.png" width="490" height="455" alt="shape" class="shape shape-1">
    <img src="./assets/images/hero-shape-2.png" width="512" height="512" alt="shape" class="shape shape-2">
    <img src="./assets/images/line-1.png" width="630" height="506" alt="line" class="shape shape-3">
  </div>
</section>

<section class="container">
  <?php if (!$pdo instanceof PDO): ?>
    <div class="empty-card" style="margin-top: 24px;">
      <h2 class="title h2">Service unavailable</h2>
      <p>The menu is temporarily unavailable. Please try again shortly.</p>
      <div class="button-row">
        <a class="btn" href="./index.php">Refresh</a>
      </div>
    </div>
  <?php else: ?>
    <section class="section menu landing-menu" id="menu" aria-labelledby="menu-label">
      <div class="landing-section-head text-center">
        <p class="section-subtitle" id="menu-label">Best Food Menu</p>
        <h2 class="title h2 section-title">RECOMMENDED FOR YOU</h2>
      </div>

      <div class="menu-layout">
        <section class="menu-grid">
          <?php foreach ($meals as $meal): ?>
            <article class="meal-card">
              <div class="meal-card__image">
                <img src="<?= e($meal['image_path']) ?>" alt="<?= e($meal['name']) ?>">
              </div>
              <div class="meal-card__meta">
                <div class="meal-card__top">
                  <div>
                    <p class="meal-card__category"><?= e($meal['category']) ?></p>
                    <h2 class="title h3 meal-card__title"><?= e($meal['name']) ?></h2>
                  </div>
                  <strong><?= money((float) $meal['price']) ?></strong>
                </div>
                <p><?= e($meal['description']) ?></p>
                <form method="post" class="form-inline">
                  <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="meal_id" value="<?= (int) $meal['id'] ?>">
                  <input class="small-input" type="number" min="1" max="20" name="quantity" value="1">
                  <button class="btn" type="submit">Add to cart</button>
                </form>
              </div>
            </article>
          <?php endforeach; ?>
        </section>

        <aside class="cart-preview">
          <div>
            <p class="page-kicker">Order Summary</p>
            <h2 class="title h2">Ready for checkout</h2>
            <p class="panel-copy">Review your selected meals before choosing a pickup time.</p>
          </div>

          <?php if ($cart['items'] === []): ?>
            <div class="empty-card">
              <h2 class="title h3">Cart is empty</h2>
              <p>Add meals from the menu and continue to checkout.</p>
            </div>
          <?php else: ?>
            <div class="cart-preview__list">
              <?php foreach ($cart['items'] as $item): ?>
                <div class="cart-preview__item">
                  <div>
                    <strong><?= e($item['name']) ?></strong>
                    <p class="line-muted">Qty <?= (int) $item['quantity'] ?></p>
                  </div>
                  <strong><?= money((float) $item['line_total']) ?></strong>
                </div>
              <?php endforeach; ?>
            </div>
            <div class="price-stack">
              <div><span>Subtotal</span><strong><?= money((float) $cart['subtotal']) ?></strong></div>
              <div><span>Service fee</span><strong><?= money((float) $cart['service_fee']) ?></strong></div>
              <div class="total-line"><span>Total</span><strong><?= money((float) $cart['total']) ?></strong></div>
            </div>
          <?php endif; ?>

          <div class="button-row">
            <a class="btn" href="./checkout.php">Start checkout</a>
            <a class="btn btn--secondary" href="./orders.php">View orders</a>
          </div>
        </aside>
      </div>
    </section>

    <!-- ========== 营业时间区块：三个卡片，Monday – Sunday 统一标识 ========== -->
    <section class="section schedule landing-hours" id="hours" aria-labelledby="hours-label">
      <div class="container">
        <div class="schedule-content">
          <p class="section-subtitle" id="hours-label">Opening Hours</p>
          <h2 class="h2 title section-title">Serving You Every Day</h2>
          <p style="text-align: center; font-weight: var(--weight-semiBold); color: var(--text-rich-black-fogra-29); margin-bottom: 20px;">Monday – Sunday</p>
          <ul class="schedule-list">
            <li class="schedule-item">
              <p class="h4 title">Breakfast</p>
              <div class="separator"></div>
              <div class="time title">07:30 – 10:30</div>
            </li>
            <li class="schedule-item">
              <p class="h4 title">Lunch</p>
              <div class="separator"></div>
              <div class="time title">11:30 – 14:30</div>
            </li>
            <li class="schedule-item">
              <p class="h4 title">Dinner</p>
              <div class="separator"></div>
              <div class="time title">17:30 – 21:00</div>
            </li>
          </ul>
        </div>
        <div class="schedule-banner">
          <figure class="img-holder">
            <img src="./assets/images/schedule-banner.jpg" width="960" height="640" loading="lazy" alt="Restaurant service hours" class="img-cover">
          </figure>
        </div>
      </div>
    </section>
    <!-- ========== 营业时间结束 ========== -->

    <section class='section reservation' aria-labelledby='reservation-label'>
        <div class='container'>
            <figure class='reservation-banner img-holder' style='--width: 630; --height: 730;'>
                <img src='./assets/images/reservation-banner.jpg' width='630' height='730' loading='lazy' alt='Reservation banner' class='img-cover'>
            </figure>
        
            <div class='reservation-content' id="reservation-content">
                <!-- Content will be loaded by JavaScript -->
            </div>
            <img src='./assets/images/line-1.png' width='630' height='506' loading='lazy' alt='shape' class='shape'>
        </div>
    </section>
    
    <script>
        const currentUser = JSON.parse(localStorage.getItem('currentUser'));
        const reservationContent = document.getElementById('reservation-content');
        
        if (currentUser) {
            reservationContent.innerHTML = `
                <p class='section-subtitle' id='reservation-label'>
                    Welcome, ${escapeHtml(currentUser.full_name)}!
                </p>
                <h2 class='h2 title section-title'>
                    Ready to Order?
                </h2>
                <p class='section-text'>
                    You are logged in as ${escapeHtml(currentUser.email)}.
                </p>
                <div class="auth-actions" style="margin-top: 20px;">
                    <a href="logout.php" class="btn" style="background: #c62828; border-color: #c62828;">
                        <span class="span">Logout</span>
                    </a>
                    <a href="checkout.php" class="btn" style="margin-top: 10px;">
                        <span class="span">Go to Checkout</span>
                        <ion-icon name="arrow-forward-outline"></ion-icon>
                    </a>
                </div>
            `;
        } else {
            reservationContent.innerHTML = `
                <p class='section-subtitle' id='reservation-label'>
                    Booking Table
                </p>
                <h2 class='h2 title section-title'>
                    Make A Reservation
                </h2>
                <p class='section-text'>
                    Sit amet consectetur adipiscing elitsue risus mauris adipiscing phasellus aene ante orcirat
                </p>
                <form action='./checkout.php' method='get' class='booking-form'>
                    <div class='input-wrapper'>
                        <input type='text' name='name' placeholder='Person' autocomplete='off' class='input-field'>
                        <ion-icon name="person-outline"></ion-icon>
                    </div>
                    <div class='input-wrapper'>
                        <input type='text' name='date' placeholder='Reserved Date' autocomplete='off' class='input-field'>
                        <ion-icon name="calendar-outline"></ion-icon>
                    </div>
                    <div class='input-wrapper'>
                        <input type='text' name='time' placeholder='Reservations Time' autocomplete='off' class='input-field'>
                        <ion-icon name="time-outline"></ion-icon>
                    </div>
                    <div class="auth-buttons">
                        <a href="login.php" class="btn-auth-login">Login</a>
                        <a href="register.php" class="btn-auth-register">Register</a>
                    </div>
                    <button type='submit' class='btn' style="margin-top: 15px;">
                        <span class='span'>Go To Checkout</span>
                        <ion-icon name="arrow-forward-outline"></ion-icon>
                    </button>
                </form>
            `;
        }
        
        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/[&<>]/g, function(m) {
                if (m === '&') return '&amp;';
                if (m === '<') return '&lt;';
                if (m === '>') return '&gt;';
                return m;
            });
        }
    </script>
  <?php endif; ?>
</section>

<script>
(function() {
    const headerAction = document.querySelector('.header-action');
    if (headerAction && !document.querySelector('.auth-icon-link')) {
        const authLink = document.createElement('a');
        authLink.className = 'auth-icon-link';
        authLink.style.display = 'flex';
        authLink.style.alignItems = 'center';
        authLink.style.marginLeft = '15px';
        authLink.style.gap = '8px';
        
        const isLoggedIn = <?php echo isLoggedIn() ? 'true' : 'false'; ?>;
        const userName = <?php echo json_encode($_SESSION['user_name'] ?? ''); ?>;
        
        if (isLoggedIn && userName) {
            authLink.innerHTML = `
                <ion-icon name="person-circle-outline" style="font-size: 2.4rem;"></ion-icon>
                <span style="font-size: 1.4rem;">${escapeHtml(userName)}</span>
                <a href="logout.php" style="color: #d49b3a; margin-left: 8px;" title="Logout">
                    <ion-icon name="log-out-outline" style="font-size: 2rem;"></ion-icon>
                </a>
            `;
            const logoutBtn = authLink.querySelector('a');
            if (logoutBtn) {
                logoutBtn.addEventListener('click', (e) => {});
            }
        } else {
            authLink.innerHTML = `
                <a href="login.php" title="Login" style="display: flex; align-items: center; gap: 5px;">
                    <ion-icon name="log-in-outline" style="font-size: 2rem;"></ion-icon>
                    <span style="font-size: 1.3rem;">Login</span>
                </a>
                <span style="color: #ccc;">|</span>
                <a href="register.php" title="Register" style="display: flex; align-items: center; gap: 5px;">
                    <ion-icon name="person-add-outline" style="font-size: 2rem;"></ion-icon>
                    <span style="font-size: 1.3rem;">Register</span>
                </a>
            `;
        }
        headerAction.appendChild(authLink);
    }
    
    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }
})();
</script>
<?php render_footer(); ?>