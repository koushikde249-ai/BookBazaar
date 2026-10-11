<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_login();
$errors = [];
$title = $author = $category = $description = '';
$price = '';
$condition = 'used';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $title = trim((string)($_POST['title'] ?? ''));
    $author = trim((string)($_POST['author'] ?? ''));
    $category = trim((string)($_POST['category'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $price = trim((string)($_POST['price'] ?? ''));
    $condition = (string)($_POST['condition_type'] ?? 'used');
    if (mb_strlen($title) < 2 || mb_strlen($title) > 150) $errors[] = 'Title must be between 2 and 150 characters.';
    if (mb_strlen($author) < 2 || mb_strlen($author) > 120) $errors[] = 'Enter the author name.';
    if ($category === '' || mb_strlen($category) > 60) $errors[] = 'Enter a category (up to 60 characters).';
    if (!is_numeric($price) || (float)$price <= 0 || (float)$price > 1000000) $errors[] = 'Enter a valid price greater than zero.';
    if (!in_array($condition, ['new','used'], true)) $errors[] = 'Choose a valid book condition.';
    $imagePath = null;
    $hasUpload = isset($_FILES['book_image']) && $_FILES['book_image']['error'] !== UPLOAD_ERR_NO_FILE;
    if ($hasUpload && $_FILES['book_image']['error'] !== UPLOAD_ERR_OK) $errors[] = 'The image upload failed. Check PHP upload limits and try again.';
    if ($hasUpload && $_FILES['book_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['book_image'];
        if ($file['size'] > 3 * 1024 * 1024) $errors[] = 'Image must be 3 MB or smaller.';
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
        if (!isset($allowed[$mime])) $errors[] = 'Use a JPG, PNG, or WebP image.';
        if (!$errors) {
            $uploadDir = __DIR__ . '/uploads/books';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
            if (move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $filename)) {
                $imagePath = 'uploads/books/' . $filename;
            } else {
                $errors[] = 'Could not save the uploaded image. Check folder permissions.';
            }
        }
    }
    if (!$errors) {
        $stmt = $pdo->prepare("INSERT INTO books (user_id,title,author,category,description,price,condition_type,image_path,status) VALUES (?,?,?,?,?,?,?,?, 'pending')");
        $stmt->execute([(int)current_user()['id'],$title,$author,$category,$description,(float)$price,$condition,$imagePath]);
        flash('Your book listing was submitted and is waiting for admin approval.');
        header('Location: index.php'); exit;
    }
}
$pageTitle = 'Sell a book';
require __DIR__ . '/includes/header.php';
?>
<section class="form-page"><div class="form-intro"><span class="eyebrow">Pass a story along</span><h1>List your book</h1><p>Add the details and a clear photo of the cover. Your listing will appear publicly after admin approval.</p></div><div class="form-card"><?php foreach ($errors as $error): ?><div class="notice error"><?= e($error) ?></div><?php endforeach; ?><form method="post" enctype="multipart/form-data" class="stack-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><div class="form-row"><label>Book title<input name="title" required maxlength="150" value="<?= e($title) ?>"></label><label>Author<input name="author" required maxlength="120" value="<?= e($author) ?>"></label></div><div class="form-row"><label>Category<input name="category" required maxlength="60" placeholder="Fiction, Business, Education..." value="<?= e($category) ?>"></label><label>Price (₹)<input type="number" name="price" required min="1" max="1000000" step="0.01" value="<?= e($price) ?>"></label></div><label>Book condition<select name="condition_type"><option value="used" <?= $condition==='used'?'selected':'' ?>>Pre-loved / Used</option><option value="new" <?= $condition==='new'?'selected':'' ?>>New</option></select></label><label>Book cover image <span class="muted">(optional, JPG/PNG/WebP, max 3 MB)</span><input type="file" name="book_image" accept="image/jpeg,image/png,image/webp"><small>Use a clear photo of the actual book cover.</small></label><label>Description<textarea name="description" rows="5" maxlength="2000" placeholder="Condition, edition, notes..."><?= e($description) ?></textarea></label><button class="button button-wide" type="submit">Submit for approval</button></form></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
