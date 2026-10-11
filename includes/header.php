<?php
require_once __DIR__ . '/functions.php';
$pageTitle = $pageTitle ?? 'BookBazaar';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> | BookBazaar</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <script src="assets/js/script.js" defer></script>
</head>
<body>
<header class="site-header">
  <div class="container nav-wrap">
    <a class="brand" href="index.php"><span class="brand-mark">B</span> BookBazaar</a>
    <button class="menu-toggle" type="button" aria-label="Toggle navigation" aria-expanded="false">☰</button>
    <nav class="main-nav">
      <a href="index.php">Home</a>
      <a href="index.php#books">Browse Books</a>
      <?php if (current_user()): ?>
        <a href="sell-book.php">Sell a Book</a>
        <a href="my-orders.php">My Orders</a>
        <a href="my-sales.php">My Sales</a>
        <?php if (current_user()['role'] === 'admin'): ?><a href="admin/dashboard.php">Admin</a><?php endif; ?>
        <span class="nav-user">Hi, <?= e(current_user()['name']) ?></span>
        <form class="inline-form" action="logout.php" method="post">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <button class="nav-button" type="submit">Logout</button>
        </form>
      <?php else: ?>
        <a href="login.php">Login</a>
        <a class="button button-small" href="register.php">Join free</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main>
<div class="container page-flash"><?php show_flash(); ?></div>
