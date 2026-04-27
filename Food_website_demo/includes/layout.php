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
</head>
<body id="top" class="page-shell">
  <header class="header active" data-header>
    <div class="container">
      <a href="<?= e(app_url('index.php')) ?>" class="logo">
        <img src="./assets/images/logo.svg" width="130" height="45" alt="Crispy home">
      </a>
      <nav class="navbar active" data-navbar>
        <ul class="navbar-list">
          <li class="navbar-item"><a href="<?= e(app_url('index.php#top')) ?>" class="navbar-link<?= $activePage === 'home' ? ' is-current' : '' ?>">Home</a></li>
          <li class="navbar-item"><a href="<?= e(app_url('index.php#menu')) ?>" class="navbar-link<?= $activePage === 'menu' ? ' is-current' : '' ?>">Menu</a></li>
          <li class="navbar-item"><a href="<?= e(app_url('index.php#hours')) ?>" class="navbar-link">Hours</a></li>
          <li class="navbar-item"><a href="<?= e(app_url('checkout.php')) ?>" class="navbar-link<?= $activePage === 'checkout' ? ' is-current' : '' ?>">Checkout</a></li>
          <li class="navbar-item"><a href="<?= e(app_url('orders.php')) ?>" class="navbar-link<?= $activePage === 'orders' ? ' is-current' : '' ?>">Orders</a></li>
        </ul>
      </nav>
      <div class="header-action">
        <a href="tel:00012345689" class="call">
          <ion-icon name="call-outline" aria-hidden="true"></ion-icon>
          <span class="span">000 (123) 456 89</span>
        </a>
        <a href="<?= e(app_url('checkout.php')) ?>" class="btn">
          <span class="span"><?= e($checkoutLabel) ?></span>
          <ion-icon name="arrow-forward-outline" aria-hidden="true"></ion-icon>
        </a>
      </div>
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
            Fresh burgers, quick pickup slots, and a simple ordering experience for busy campus days.
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
        </ul>

        <ul class="footer-list">
          <li><p class="title footer-list-title">Products</p></li>
          <li><a href="./index.php#menu" class="footer-link">Hamburger</a></li>
          <li><a href="./index.php#menu" class="footer-link">Chicken Burger</a></li>
          <li><a href="./index.php#menu" class="footer-link">Vegetable Pizza</a></li>
          <li><a href="./index.php#menu" class="footer-link">Chicken Roll</a></li>
          <li><a href="./index.php#menu" class="footer-link">Ice-Cream</a></li>
          <li><a href="./index.php#menu" class="footer-link">Potato</a></li>
          <li><a href="./index.php#menu" class="footer-link">Organic Juice</a></li>
          <li><a href="./index.php#menu" class="footer-link">Lemon Juice</a></li>
          <li><a href="./index.php#menu" class="footer-link">Mutton Tikka</a></li>
          <li><a href="./index.php#menu" class="footer-link">Seafoods</a></li>
        </ul>

        <ul class="footer-list">
          <li>
            <p class="list-subtitle">Call for order:</p>
            <a href="tel:+125865892" class="title call">+1 2586 5892</a>
            <a href="mailto:hello@crispy.com" class="email contact-text">hello@crispy.com</a>
          </li>
          <li>
            <p class="list-subtitle">Location :</p>
            <address class="contact-text">
              119 Tanglewood Lane, Gulfport, MS 39503
            </address>
          </li>
        </ul>
      </div>
    </div>

    <div class="footer-bottom">
      <div class="container">
        <p class="copyright text-center">
          &copy; 2026 Crispy. All Rights Reserved.
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
</body>
</html>
<?php
}
