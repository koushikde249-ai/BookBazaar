<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$search = trim((string)($_GET['q'] ?? ''));
$category = trim((string)($_GET['category'] ?? ''));
$sql = "SELECT b.*, u.name AS seller_name FROM books b JOIN users u ON u.id = b.user_id WHERE b.status = 'approved' AND b.is_sold = 0";
$params = [];
if ($search !== '') {
    $sql .= " AND (b.title LIKE ? OR b.author LIKE ? OR b.category LIKE ?)";
    $term = '%' . $search . '%';
    array_push($params, $term, $term, $term);
}
if ($category !== '') {
    $sql .= " AND b.category = ?";
    $params[] = $category;
}
$sql .= " ORDER BY b.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();
$categories = $pdo->query("SELECT DISTINCT category FROM books WHERE status='approved' AND is_sold=0 AND category <> '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
$pageTitle = 'Discover your next favourite book';
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <div class="hero-copy">
    <span class="eyebrow">A marketplace for every reader</span>
    <h1>Your next great read is <em>waiting.</em></h1>
    <p>Discover affordable new and pre-loved books. Give stories a new home and find your next favourite page.</p>
    <div class="hero-actions"><a class="button" href="#books">Explore books <span>→</span></a><a class="button button-light" href="<?= current_user() ? 'sell-book.php' : 'register.php' ?>">Sell your book</a></div>
    <div class="hero-stats"><div><strong>Discover</strong><span>great stories</span></div><div><strong>Save more</strong><span>on pre-loved books</span></div><div><strong>Share</strong><span>your bookshelf</span></div></div>
  </div>
  <div class="hero-art" aria-label="Illustration of books"><div class="sun"></div><div class="plant">✦</div><div class="book-stack"><div class="fake-book book-a"><span>THE ART OF<br>READING</span></div><div class="fake-book book-b"><span>little<br>stories</span></div><div class="fake-book book-c"><span>OPEN<br>A WORLD</span></div></div><div class="art-caption">Turn the page. Find your story.</div></div>
</section>
<section class="container benefits">
  <div><span class="benefit-icon">⌕</span><div><strong>Find your next read</strong><p>Search by title, author or category.</p></div></div>
  <div><span class="benefit-icon">↗</span><div><strong>Sell books simply</strong><p>Upload a cover and list your price.</p></div></div>
  <div><span class="benefit-icon">♡</span><div><strong>Give books another life</strong><p>Share stories with fellow readers.</p></div></div>
</section>
<section class="container books-section" id="books">
  <div class="section-heading"><div><span class="eyebrow">The book shelf</span><h2>Find a book to love</h2><p>Browse approved listings from our community.</p></div><span class="result-count"><?= count($books) ?> books</span></div>
  <form class="search-panel" method="get" action="index.php#books">
    <label class="search-box"><span>⌕</span><input name="q" value="<?= e($search) ?>" placeholder="Search title, author or category..."></label>
    <select name="category" aria-label="Filter by category"><option value="">All categories</option><?php foreach ($categories as $cat): ?><option value="<?= e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= e($cat) ?></option><?php endforeach; ?></select>
    <button class="button" type="submit">Search</button>
    <?php if ($search !== '' || $category !== ''): ?><a class="clear-link" href="index.php#books">Clear</a><?php endif; ?>
  </form>
  <?php if (!$books): ?>
    <div class="empty-state"><div class="empty-icon">▤</div><h3>No books found just yet</h3><p>Try another search, or log in to submit a book for approval.</p><a class="button" href="<?= current_user() ? 'sell-book.php' : 'login.php' ?>">List a book</a></div>
  <?php else: ?>
    <div class="book-grid">
      <?php foreach ($books as $book): ?>
        <article class="book-card">
          <a class="cover-link" href="book.php?id=<?= (int)$book['id'] ?>">
            <div class="book-cover"><?php if ($book['image_path']): ?><img src="<?= e($book['image_path']) ?>" alt="Cover of <?= e($book['title']) ?>"><?php else: ?><div class="cover-placeholder"><span>BOOKBAZAAR</span><strong><?= e($book['title']) ?></strong><small><?= e($book['author']) ?></small></div><?php endif; ?></div>
          </a>
          <div class="book-info"><span class="book-category"><?= e($book['category']) ?> · <?= e(ucfirst($book['condition_type'])) ?></span><h3><a href="book.php?id=<?= (int)$book['id'] ?>"><?= e($book['title']) ?></a></h3><p class="book-author">by <?= e($book['author']) ?></p><div class="book-card-bottom"><strong class="price">₹<?= number_format((float)$book['price'], 2) ?></strong><span class="seller">by <?= e($book['seller_name']) ?></span></div></div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<section class="sell-banner container"><div><span class="eyebrow">Your shelf, someone else's treasure</span><h2>Ready to pass a book along?</h2><p>Upload a photo, add a few details, and let other readers discover it.</p></div><a class="button button-dark" href="<?= current_user() ? 'sell-book.php' : 'register.php' ?>">Start selling <span>→</span></a></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
