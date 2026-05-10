<?php

declare(strict_types=1);

function render_header(string $title, string $activePage, int $cartCount, array $flashMessages, ?string $dbError): void
{
    $pageTitle = $title . ' | ' . config('app_name');
    $checkoutLabel = $cartCount > 0 ? 'Start Checkout (' . $cartCount . ')' : 'Start Checkout';
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?></title>
  <link rel="shortcut icon" href="./assets/images/favicon.svg" type="image/svg+xml">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Forum&family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="./assets/css/style.css">
  <link rel="stylesheet" href="./assets/css/order-pages.css">
  <link rel="stylesheet" href="./assets/css/php-app.css">
  <link rel="stylesheet" href="./assets/css/auth.css">
  <style>
    @media (min-width: 992px) {
      .mobile-only-nav { display: none !important; }
      .auth-icon-link { display: flex !important; align-items: center; }
      .auth-user-link {
        display: flex;
        align-items: center;
        gap: 8px;
        color: inherit;
        text-decoration: none;
        transition: opacity 0.2s;
      }
      .auth-user-link:hover { opacity: 0.7; }
    }
    @media (max-width: 991px) {
      .mobile-only-nav { display: block !important; }
      .auth-icon-link { display: none !important; }
    }
  </style>
</head>
<body id="top" class="page-shell">
  <header class="header" data-header>
    <div class="container">
      <a href="<?= e(app_url('index.php')) ?>" class="logo">
        <img src="./assets/images/logo.svg" width="130" height="45" alt="Crispy home">
      </a>

      <nav class="navbar" data-navbar>
        <button class="nav-close-btn" aria-label="close menu" data-nav-toggler>
          <ion-icon name="close-outline" aria-hidden="true"></ion-icon>
        </button>
        <a href="<?= e(app_url('index.php')) ?>" class="logo nav-logo">
          <img src="./assets/images/logo.svg" width="130" height="45" alt="Crispy home">
        </a>
        <ul class="navbar-list">
          <li class="navbar-item"><a href="<?= e(app_url('index.php#top')) ?>" class="navbar-link<?= $activePage === 'home' ? ' is-current' : '' ?>">Home</a></li>
          <li class="navbar-item"><a href="<?= e(app_url('index.php#menu')) ?>" class="navbar-link<?= $activePage === 'menu' ? ' is-current' : '' ?>">Menu</a></li>
          <li class="navbar-item"><a href="<?= e(app_url('index.php#hours')) ?>" class="navbar-link">Hours</a></li>
          <li class="navbar-item"><a href="<?= e(app_url('checkout.php')) ?>" class="navbar-link<?= $activePage === 'checkout' ? ' is-current' : '' ?>">Checkout</a></li>
          <li class="navbar-item"><a href="<?= e(app_url('orders.php')) ?>" class="navbar-link<?= $activePage === 'orders' ? ' is-current' : '' ?>">Orders</a></li>
          <li class="navbar-item"><a href="<?= e(app_url('account.php')) ?>" class="navbar-link<?= $activePage === 'account' ? ' is-current' : '' ?>">My Account</a></li>
          <li class="navbar-item"><a href="<?= e(app_url('menu_by_time.php')) ?>" class="navbar-link<?= $activePage === 'menu_by_time' ? ' is-current' : '' ?>">Menu by Time</a></li>

          <?php if (isLoggedIn()): ?>
            <li class="navbar-item mobile-only-nav"><a href="<?= e(app_url('account.php')) ?>" class="navbar-link">My Account</a></li>
            <li class="navbar-item mobile-only-nav"><a href="<?= e(app_url('logout.php')) ?>" class="navbar-link" style="color: var(--text-sinopia);">Logout (<?= e($_SESSION['user_name'] ?? '') ?>)</a></li>
          <?php else: ?>
            <li class="navbar-item mobile-only-nav"><a href="<?= e(app_url('login.php')) ?>" class="navbar-link">Login</a></li>
            <li class="navbar-item mobile-only-nav"><a href="<?= e(app_url('register.php')) ?>" class="navbar-link">Register</a></li>
          <?php endif; ?>
        </ul>
      </nav>

      <div class="header-action">
        
        <a href="<?= e(app_url('checkout.php')) ?>" class="btn">
          <span class="span"><?= e($checkoutLabel) ?></span>
          <ion-icon name="arrow-forward-outline" aria-hidden="true"></ion-icon>
        </a>

        <div class="auth-icon-link" style="margin-left: 15px; gap: 8px;">
          <?php if (isLoggedIn()): ?>
            <a href="<?= e(app_url('account.php')) ?>" class="auth-user-link" title="My Account">
              <ion-icon name="person-circle-outline" style="font-size: 2.4rem;"></ion-icon>
              <span style="font-size: 1.4rem;"><?= e($_SESSION['user_name'] ?? '') ?></span>
            </a>
            <a href="<?= e(app_url('logout.php')) ?>" style="color: #d49b3a; margin-left: 4px;" title="Logout">
              <ion-icon name="log-out-outline" style="font-size: 2rem;"></ion-icon>
            </a>
          <?php else: ?>
            <a href="<?= e(app_url('login.php')) ?>" title="Login" style="display: flex; align-items: center; gap: 5px;">
              <ion-icon name="log-in-outline" style="font-size: 2rem;"></ion-icon>
              <span style="font-size: 1.3rem;">Login</span>
            </a>
            <span style="color: #ccc;">|</span>
            <a href="<?= e(app_url('register.php')) ?>" title="Register" style="display: flex; align-items: center; gap: 5px;">
              <ion-icon name="person-add-outline" style="font-size: 2rem;"></ion-icon>
              <span style="font-size: 1.3rem;">Register</span>
            </a>
          <?php endif; ?>
        </div>
      </div>

      <button class="nav-open-btn" aria-label="open menu" data-nav-toggler>
        <ion-icon name="menu-outline" aria-hidden="true"></ion-icon>
      </button>
      <div class="overlay" data-overlay data-nav-toggler></div>
    </div>
  </header>

  <main class="page-main">
    <section class="container">
      <?php if ($dbError !== null): ?>
        <div class="alert alert--error">
          <strong>Ordering service is temporarily unavailable.</strong>
          <span>Please try again in a moment.</span>
        </div>
      <?php endif; ?>

      <?php foreach ($flashMessages as $message): ?>
        <div class="alert alert--<?= e($message['type']) ?>">
          <?= e($message['message']) ?>
        </div>
      <?php endforeach; ?>
    </section>
<?php
}

function render_footer(): void
{
    ?>
  </main>

  <footer class="footer">
    <div class="section footer-top">
      <div class="container">
        <div class="footer-brand">
          <a href="./index.php" class="logo">
            <img src="./assets/images/logo.svg" width="170" height="61" loading="lazy" alt="Crispy home">
          </a>
          <p class="footer-text">
            Pre‑order your favourite campus meals, reserve a pickup slot, and skip the lunch queue.
          </p>
          <form action="./orders.php" method="get" class="footer-form">
            <input type="email" name="newsletter_email" placeholder="Email Address" autocomplete="off" class="footer-input">
            <button type="submit" class="form-btn" aria-label="Subscribe">
              <ion-icon name="arrow-forward"></ion-icon>
            </button>
          </form>
        </div>

        <ul class="footer-list">
          <li><p class="title footer-list-title">Quick Links</p></li>
          <li><a href="./index.php#top" class="footer-link">Home</a></li>
          <li><a href="./index.php#menu" class="footer-link">Menu</a></li>
          <li><a href="./index.php#hours" class="footer-link">Working Hours</a></li>
          <li><a href="./checkout.php" class="footer-link">Checkout</a></li>
          <li><a href="./orders.php" class="footer-link">Order History</a></li>
          <li><a href="./account.php" class="footer-link">My Account</a></li>
          <li><a href="./menu_by_time.php" class="footer-link">Menu by Time</a></li>
        </ul>

        <ul class="footer-list">
          <li><p class="title footer-list-title">Products</p></li>
          <li><a href="#menu" class="footer-link">View Our Menu</a></li>
        </ul>

        <ul class="footer-list">
          <li>
            
            <a href="mailto:bluequpz@gmail.com" class="email contact-text">bluequpz@gmail.com</a>
          </li>
          <li>
            <p class="list-subtitle">Location :</p>
            <address class="contact-text">
              University of Macau <br>
              Avenida da Universidade<br>
              Taipa, Macau, China
            </address>
          </li>
        </ul>
      </div>
    </div>

    <div class="footer-bottom">
      <div class="container">
        <p class="copyright text-center">
          &copy; 2026 CISC3003Team05. All Rights Reserved.
        </p>
        <ul class="footer-bottom-list">
          <li><a href="./index.php" class="footer-bottom-link">Setting & Privacy</a></li>
          <li><a href="./orders.php" class="footer-bottom-link">Faqs</a></li>
          <li><a href="./index.php#menu" class="footer-bottom-link">Food Menu</a></li>
        </ul>
      </div>
    </div>
  </footer>

  <a href="#top" class="back-top-btn active" aria-label="back to top" data-back-top-btn>
    <ion-icon name="chevron-up" aria-hidden="true"></ion-icon>
  </a>

  <script src="./assets/js/script.js"></script>
<script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>

<script>
    const protectedPages = ['checkout.php', 'orders.php', 'order-details.php', 'account.php', 'menu_by_time.php'];
    const isLoggedIn = <?= json_encode(isLoggedIn()) ?>;
    let loginPromptShown = false;   // 弹窗锁，只弹一次

    document.addEventListener('click', function(e) {
        if (loginPromptShown) return;   // 已弹过，不再处理
        let link = e.target.closest('a');
        if (!link) return;
        let href = link.getAttribute('href');
        if (!href) return;
        let needLogin = protectedPages.some(page => href.includes(page));
        if (needLogin && !isLoggedIn) {
            e.preventDefault();
            loginPromptShown = true;     // 锁定，避免二次弹窗
            if (confirm('You need to login or register to continue. Click OK to go to login page.')) {
                window.location.href = 'login.php';
            }
            // 用户点击取消后，不再弹出任何提示（因为已被锁定）
        }
    });
</script>
</body>
</html>
<?php
}