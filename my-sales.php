```php
<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$stmt = $pdo->prepare("
    SELECT o.*, u.name AS buyer_account_name
    FROM orders o
    JOIN users u ON u.id = o.buyer_id
    WHERE o.seller_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([(int) current_user()['id']]);
$orders = $stmt->fetchAll();

$pageTitle = 'My Sales';
require __DIR__ . '/includes/header.php';
?>

<section class="container order-page">
    <div class="section-heading">
        <div>
            <span class="eyebrow">Your listings</span>
            <h2>My Sales</h2>
            <p>Orders placed for your books. Coordinate delivery with the buyer directly.</p>
        </div>
    </div>

    <?php if (!$orders): ?>
        <div class="empty-state">
            <h3>No sales yet</h3>
            <p>When someone orders one of your approved books, it will appear here.</p>
            <a class="button" href="sell-book.php">List a book</a>
        </div>
    <?php else: ?>
        <div class="order-list">
            <?php foreach ($orders as $o): ?>
                <article class="order-card">
                    <div class="order-top">
                        <div>
                            <span class="eyebrow">
                                Order #<?= (int) $o['id'] ?>
                            </span>
                            <h3><?= e($o['book_title']) ?></h3>
                            <p>
                                Buyer: <?= e($o['buyer_name']) ?>
                                (account: <?= e($o['buyer_account_name']) ?>)
                            </p>
                        </div>

                        <strong class="price">
                            ₹<?= number_format((float) $o['amount'], 2) ?>
                        </strong>
                    </div>

                    <div class="order-badges">
                        <span class="status status-pending">
                            Order: <?= e(ucfirst($o['order_status'])) ?>
                        </span>
                        <span class="status <?= $o['payment_status'] === 'paid' ? 'status-approved' : 'status-pending' ?>">
                            Payment: <?= e(ucfirst($o['payment_status'])) ?>
                        </span>
                    </div>

                    <p>
                        <strong>Buyer contact:</strong>
                        <?= e($o['buyer_phone']) ?> ·
                        <a href="mailto:<?= e($o['buyer_email']) ?>">
                            <?= e($o['buyer_email']) ?>
                        </a>
                    </p>

                    <p>
                        <strong>Delivery address:</strong><br>
                        <?= nl2br(e($o['delivery_address'])) ?>
                    </p>

                    <?php
                    $location = trim((string) ($o['delivery_location'] ?? ''));
                    $latitude = $o['delivery_latitude'] ?? null;
                    $longitude = $o['delivery_longitude'] ?? null;
                    $mapUrl = '';

                    // Prefer exact coordinates when available.
                    if (
                        $latitude !== null &&
                        $longitude !== null &&
                        is_numeric($latitude) &&
                        is_numeric($longitude) &&
                        (float) $latitude >= -90 &&
                        (float) $latitude <= 90 &&
                        (float) $longitude >= -180 &&
                        (float) $longitude <= 180
                    ) {
                        $mapUrl = 'https://www.google.com/maps/search/?api=1&query='
                            . rawurlencode((string) $latitude . ',' . (string) $longitude);
                    } elseif (
                        $location !== '' &&
                        filter_var($location, FILTER_VALIDATE_URL) &&
                        in_array(
                            strtolower((string) parse_url($location, PHP_URL_SCHEME)),
                            ['http', 'https'],
                            true
                        ) &&
                        in_array(
                            strtolower((string) parse_url($location, PHP_URL_HOST)),
                            ['google.com', 'www.google.com', 'maps.google.com', 'maps.app.goo.gl', 'goo.gl'],
                            true
                        )
                    ) {
                        $mapUrl = $location;
                    }
                    ?>

                    <p>
                        <strong>Google Maps delivery location:</strong><br>

                        <?php if ($mapUrl !== ''): ?>
                            <a
                                class="button"
                                href="<?= e($mapUrl) ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                View Delivery Location
                            </a>
                        <?php else: ?>
                            <span class="muted">
                                No map coordinates or valid map link saved for this order.
                            </span>
                        <?php endif; ?>
                    </p>

                    <p>
                        <strong>Delivery note:</strong>
                        <?= e($o['delivery_note'] ?: 'None') ?>
                    </p>

                    <p class="muted">
                        Coordinate delivery directly with the buyer.
                        For COD, collect payment when the book is delivered.
                    </p>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
```