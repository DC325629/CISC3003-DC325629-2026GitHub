<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/schema.php';

start_app_session();

$status = null;
$message = null;

if (PHP_SAPI === 'cli') {
    try {
        install_schema(create_server_connection());
        fwrite(STDOUT, "Database schema installed successfully.\n");
        exit(0);
    } catch (Throwable $exception) {
        fwrite(STDERR, $exception->getMessage() . PHP_EOL);
        exit(1);
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        install_schema(create_server_connection());
        $status = 'success';
        $message = 'Database schema installed and seed data loaded successfully.';
    } catch (Throwable $exception) {
        $status = 'error';
        $message = $exception->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Setup | <?= e((string) config('app_name')) ?></title>
  <link rel="stylesheet" href="./assets/css/style.css">
  <link rel="stylesheet" href="./assets/css/order-pages.css">
  <link rel="stylesheet" href="./assets/css/php-app.css">
</head>
<body class="page-shell">
  <main class="page-main">
    <section class="container">
      <div class="intro-card">
        <p class="page-kicker">XAMPP setup</p>
        <h1 class="title h1 page-title">Initialize the MySQL database for the Team05 module.</h1>
        <p class="panel-copy">
          This creates the `<?= e((string) config('db.database')) ?>` database, the meals, pickup slots, orders, and order items tables, and the initial seed data for checkout and order history.
        </p>
        <?php if ($status !== null): ?>
          <div class="alert alert--<?= e($status) ?>" style="margin-top: 24px;">
            <?= e((string) $message) ?>
          </div>
        <?php endif; ?>
        <div class="button-row" style="margin-top: 24px;">
          <form method="post">
            <button class="btn" type="submit">Run setup</button>
          </form>
          <a class="btn btn--secondary" href="./index.php">Open application</a>
        </div>
        <p class="system-note">
          Default connection: host `<?= e((string) config('db.host')) ?>`, port `<?= e((string) (string) config('db.port')) ?>`, user `<?= e((string) config('db.username')) ?>`.
        </p>
      </div>
    </section>
  </main>
</body>
</html>
