<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $bookId = filter_input(INPUT_POST, 'book_id', FILTER_VALIDATE_INT);
    $action = (string)($_POST['action'] ?? '');
    if ($bookId && in_array($action, ['approve','reject','delete'], true)) {
        if ($action === 'delete') {
            $stmt = $pdo->prepare('DELETE FROM books WHERE id=?'); $stmt->execute([$bookId]);
            flash('Book listing deleted.');
        } else {
            $status = $action === 'approve' ? 'approved' : 'rejected';
            $stmt = $pdo->prepare('UPDATE books SET status=? WHERE id=?'); $stmt->execute([$status,$bookId]);
            flash('Book listing ' . $status . '.');
        }
    }
    header('Location: dashboard.php'); exit;
}
$stats = [
 'books' => (int)$pdo->query("SELECT COUNT(*) FROM books")->fetchColumn(),
 'pending' => (int)$pdo->query("SELECT COUNT(*) FROM books WHERE status='pending'")->fetchColumn(),
 'users' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn(),
 'approved' => (int)$pdo->query("SELECT COUNT(*) FROM books WHERE status='approved'")->fetchColumn(),
];
$filter = (string)($_GET['status'] ?? 'pending');
$validFilters = ['pending','approved','rejected','all'];
if (!in_array($filter,$validFilters,true)) $filter='pending';
$sql = "SELECT b.*,u.name AS seller_name FROM books b JOIN users u ON u.id=b.user_id";
if ($filter !== 'all') $sql .= " WHERE b.status=" . $pdo->quote($filter);
$sql .= " ORDER BY b.created_at DESC LIMIT 100";
$books = $pdo->query($sql)->fetchAll();
$pageTitle = 'Admin dashboard';
require __DIR__ . '/../includes/header.php';
?>
<section class="dashboard-head"><div><span class="eyebrow">Administrator</span><h1>Dashboard</h1><p>Review book submissions and manage marketplace listings.</p></div><div><span class="admin-pill">● Admin access</span><p><a class="button button-small" href="orders.php">Manage Orders →</a></p></div></section>
<section class="stats-grid"><div class="stat-card"><span>Total listings</span><strong><?= $stats['books'] ?></strong><small>All submitted books</small></div><div class="stat-card"><span>Pending review</span><strong><?= $stats['pending'] ?></strong><small>Need your attention</small></div><div class="stat-card"><span>Approved books</span><strong><?= $stats['approved'] ?></strong><small>Visible on home page</small></div><div class="stat-card"><span>Registered users</span><strong><?= $stats['users'] ?></strong><small>Community members</small></div></section>
<section class="admin-table-section"><div class="section-heading"><div><span class="eyebrow">Moderation</span><h2>Book listings</h2></div><form method="get"><select name="status" aria-label="Filter listings" onchange="this.form.submit()"><option value="pending" <?= $filter==='pending'?'selected':'' ?>>Pending</option><option value="approved" <?= $filter==='approved'?'selected':'' ?>>Approved</option><option value="rejected" <?= $filter==='rejected'?'selected':'' ?>>Rejected</option><option value="all" <?= $filter==='all'?'selected':'' ?>>All listings</option></select></form></div>
<?php if (!$books): ?><div class="empty-state"><div class="empty-icon">✓</div><h3>Nothing to review here</h3><p>New submissions will appear in this section.</p></div><?php else: ?><div class="admin-book-list"><?php foreach ($books as $book): ?><article class="admin-book-row"><div class="admin-thumb"><?php if ($book['image_path']): ?><img src="../<?= e($book['image_path']) ?>" alt=""><?php else: ?><span>BOOK</span><?php endif; ?></div><div class="admin-book-main"><h3><?= e($book['title']) ?></h3><p><?= e($book['author']) ?> · <?= e($book['category']) ?></p><p>Seller: <?= e($book['seller_name']) ?> · ₹<?= number_format((float)$book['price'],2) ?></p><span class="status status-<?= e($book['status']) ?>"><?= e(ucfirst($book['status'])) ?></span></div><div class="admin-actions"><?php if ($book['status'] !== 'approved'): ?><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="book_id" value="<?= (int)$book['id'] ?>"><input type="hidden" name="action" value="approve"><button class="button button-small" type="submit">Approve</button></form><?php endif; ?><?php if ($book['status'] !== 'rejected'): ?><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="book_id" value="<?= (int)$book['id'] ?>"><input type="hidden" name="action" value="reject"><button class="button button-small button-light" type="submit">Reject</button></form><?php endif; ?><form method="post" onsubmit="return confirm('Delete this book listing permanently?')"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="book_id" value="<?= (int)$book['id'] ?>"><input type="hidden" name="action" value="delete"><button class="text-danger" type="submit">Delete</button></form></div></article><?php endforeach; ?></div><?php endif; ?></section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
