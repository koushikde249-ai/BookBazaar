<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
if (current_user() && current_user()['role'] === 'admin') { header('Location: dashboard.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    $stmt = $pdo->prepare("SELECT id,name,email,password_hash,role FROM users WHERE email=? AND role='admin' LIMIT 1");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();
    if ($admin && password_verify($password, $admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id'=>(int)$admin['id'],'name'=>$admin['name'],'email'=>$admin['email'],'role'=>'admin'];
        flash('Admin login successful.');
        header('Location: dashboard.php'); exit;
    }
    $error = 'Admin email or password is incorrect.';
}
$pageTitle = 'Admin login';
require __DIR__ . '/../includes/header.php';
?>
<section class="auth-wrap"><div class="auth-side admin-side"><span class="eyebrow">BookBazaar control room</span><h1>Keep the shelves in good order.</h1><p>Review listings and help keep the marketplace safe for readers.</p><div class="auth-quote">Admin access is separate from regular user accounts.</div></div><div class="auth-card"><span class="eyebrow">Restricted access</span><h2>Admin login</h2><p class="muted">Sign in with your administrator account.</p><?php if ($error): ?><div class="notice error"><?= e($error) ?></div><?php endif; ?><form method="post" class="stack-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label>Admin email<input type="email" name="email" required autocomplete="username"></label><label>Password<input type="password" name="password" required autocomplete="current-password"></label><button class="button button-wide" type="submit">Enter dashboard</button></form><p class="auth-switch"><a href="../index.php">← Return to website</a></p></div></section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
