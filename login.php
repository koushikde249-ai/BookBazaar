<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
if (current_user()) { header('Location: ' . (current_user()['role'] === 'admin' ? 'admin/dashboard.php' : 'index.php')); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    $stmt = $pdo->prepare('SELECT id,name,email,password_hash,role FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id'=>(int)$user['id'],'name'=>$user['name'],'email'=>$user['email'],'role'=>$user['role']];
        flash('Welcome back, ' . $user['name'] . '!');
        header('Location: ' . ($user['role'] === 'admin' ? 'admin/dashboard.php' : 'index.php')); exit;
    }
    $error = 'Email or password is incorrect.';
}
$pageTitle = 'Login';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-wrap"><div class="auth-side"><span class="eyebrow">Welcome back</span><h1>Pick up where your next story begins.</h1><p>Sign in to manage your bookshelf, list a book, and find your next read.</p><div class="auth-quote">Good books. Great prices. More stories.</div></div><div class="auth-card"><span class="eyebrow">Member login</span><h2>Welcome back</h2><p class="muted">Enter your account details below.</p><?php if ($error): ?><div class="notice error"><?= e($error) ?></div><?php endif; ?><form method="post" class="stack-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label>Email address<input type="email" name="email" required autocomplete="email"></label><label>Password<input type="password" name="password" required autocomplete="current-password"></label><button class="button button-wide" type="submit">Log in</button></form><p class="auth-switch">New to BookBazaar? <a href="register.php">Create an account</a></p><p class="small-center"><a href="admin/login.php">Admin login</a></p></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
