<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$stmt = $pdo->prepare("SELECT b.*, u.name AS seller_name FROM books b JOIN users u ON u.id=b.user_id WHERE b.id=? AND (b.status='approved' OR b.user_id=?) LIMIT 1");
$stmt->execute([$id ?: 0, current_user()['id'] ?? 0]);
$book = $stmt->fetch();
if (!$book || ($book['status'] !== 'approved' && (!current_user() || ((int)$book['user_id'] !== (int)current_user()['id'] && current_user()['role'] !== 'admin')))) {
    http_response_code(404); $pageTitle='Book not found'; require __DIR__.'/includes/header.php'; echo '<section class="empty-state container"><h2>Book not found</h2><p>This listing may be pending approval or no longer available.</p><a class="button" href="index.php">Back to books</a></section>'; require __DIR__.'/includes/footer.php'; exit;
}
$pageTitle = $book['title'];
require __DIR__ . '/includes/header.php';
?>
<section class="detail-layout"><div class="detail-cover"><?php if ($book['image_path']): ?><img src="<?= e($book['image_path']) ?>" alt="Cover of <?= e($book['title']) ?>"><?php else: ?><div class="cover-placeholder"><span>BOOKBAZAAR</span><strong><?= e($book['title']) ?></strong><small><?= e($book['author']) ?></small></div><?php endif; ?></div><div class="detail-info"><span class="eyebrow"><?= e($book['category']) ?> · <?= e(ucfirst($book['condition_type'])) ?></span><h1><?= e($book['title']) ?></h1><p class="detail-author">by <?= e($book['author']) ?></p><div class="detail-price">₹<?= number_format((float)$book['price'], 2) ?></div><p><?= nl2br(e($book['description'] ?: 'No description has been added for this book.')) ?></p><div class="seller-box"><strong>Listed by <?= e($book['seller_name']) ?></strong><span>Buyer and seller arrange delivery directly.</span></div>
<?php if ((int)$book['is_sold'] === 1): ?><div class="notice error">This book has already been ordered.</div>
<?php elseif ($book['status'] === 'approved' && (!current_user() || (int)current_user()['id'] !== (int)$book['user_id'])): ?><a class="button" href="checkout.php?id=<?= (int)$book['id'] ?>">Buy this book · ₹<?= number_format((float)$book['price'], 2) ?></a>
<?php elseif (current_user() && (int)current_user()['id'] === (int)$book['user_id']): ?><div class="notice">This is your own listing.</div>
<?php endif; ?><p><a class="clear-link" href="index.php#books">← Back to browsing</a></p></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
