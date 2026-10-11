
<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

verify_csrf();

$bookId = filter_input(INPUT_POST, 'book_id', FILTER_VALIDATE_INT);
$name = trim((string)($_POST['buyer_name'] ?? ''));
$email = strtolower(trim((string)($_POST['buyer_email'] ?? '')));
$phone = trim((string)($_POST['buyer_phone'] ?? ''));
$address = trim((string)($_POST['delivery_address'] ?? ''));
$note = trim((string)($_POST['delivery_note'] ?? ''));
$method = (string)($_POST['payment_method'] ?? 'cod');

$latitude = filter_input(INPUT_POST, 'delivery_latitude', FILTER_VALIDATE_FLOAT);
$longitude = filter_input(INPUT_POST, 'delivery_longitude', FILTER_VALIDATE_FLOAT);

if (
    !$bookId ||
    mb_strlen($name) < 2 ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    !preg_match('/^[0-9+() -]{8,25}$/', $phone) ||
    mb_strlen($address) < 8 ||
    mb_strlen($address) > 1500 ||
    mb_strlen($note) > 500 ||
    $method !== 'cod' ||
    $latitude === false || $latitude === null ||
    $longitude === false || $longitude === null ||
    $latitude < -90 || $latitude > 90 ||
    $longitude < -180 || $longitude > 180
) {
    flash('Please check your details and select a valid delivery location on the map.', 'error');
    header('Location: checkout.php?id=' . (int)$bookId);
    exit;
}

$stmt = $pdo->prepare("
    SELECT b.*, u.name AS seller_name
    FROM books b
    JOIN users u ON u.id = b.user_id
    WHERE b.id = ?
      AND b.status = 'approved'
      AND b.is_sold = 0
    LIMIT 1
");
$stmt->execute([$bookId]);
$book = $stmt->fetch();

if (!$book || (int)$book['user_id'] === (int)current_user()['id']) {
    flash('Sorry, this book is no longer available.', 'error');
    header('Location: index.php');
    exit;
}

try {
    $pdo->beginTransaction();

    $lock = $pdo->prepare("
        SELECT id, user_id, title, price, is_sold, status
        FROM books
        WHERE id = ?
        FOR UPDATE
    ");
    $lock->execute([$bookId]);
    $fresh = $lock->fetch();

    if (
        !$fresh ||
        $fresh['status'] !== 'approved' ||
        (int)$fresh['is_sold'] === 1 ||
        (int)$fresh['user_id'] === (int)current_user()['id']
    ) {
        $pdo->rollBack();
        flash('This book is no longer available.', 'error');
        header('Location: index.php');
        exit;
    }

    $ins = $pdo->prepare("
        INSERT INTO orders (
            buyer_id, seller_id, book_id, book_title, amount,
            buyer_name, buyer_email, buyer_phone,
            delivery_address, delivery_note,
            delivery_latitude, delivery_longitude,
            payment_method, payment_status, order_status
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'cod', 'pending', 'placed'
        )
    ");

    $ins->execute([
        (int)current_user()['id'],
        (int)$fresh['user_id'],
        (int)$fresh['id'],
        $fresh['title'],
        $fresh['price'],
        $name,
        $email,
        $phone,
        $address,
        $note !== '' ? $note : null,
        $latitude,
        $longitude
    ]);

    $orderId = (int)$pdo->lastInsertId();

    $upd = $pdo->prepare(
        'UPDATE books SET is_sold = 1 WHERE id = ? AND is_sold = 0'
    );
    $upd->execute([$bookId]);

    if ($upd->rowCount() !== 1) {
        throw new RuntimeException('Book is no longer available.');
    }

    $pdo->commit();

    flash('Order placed successfully! Contact the seller to arrange delivery.');
    header('Location: order.php?id=' . $orderId);
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('BookBazaar order error: ' . $e->getMessage());
    flash('Could not place order. Please check the database setup and try again.', 'error');
    header('Location: checkout.php?id=' . (int)$bookId);
    exit;
}
