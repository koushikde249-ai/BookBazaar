<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
if (current_user()) { header('Location: index.php'); exit; }
$errors = [];
$name = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim((string)($_POST['name'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    if (mb_strlen($name) < 2 || mb_strlen($name) > 80) $errors[] = 'Name must be between 2 and 80 characters.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if (!$errors) {
        try {
            $stmt = $pdo->prepare("INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,'user')");
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            session_regenerate_id(true);
            $_SESSION['user'] = ['id'=>(int)$pdo->lastInsertId(),'name'=>$name,'email'=>$email,'role'=>'user'];
            flash('Welcome to BookBazaar! Your account is ready.');
            header('Location: index.php'); exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') $errors[] = 'This email is already registered.';
            else $errors[] = 'Could not create account. Please try again.';
        }
    }
}
$pageTitle = 'Create an account';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-wrap"><div class="auth-side"><span class="eyebrow">Join the reading circle</span><h1>Every book has a story. So do you.</h1><p>Create your account to discover books and list the ones ready for a new home.</p><div class="auth-quote">“A room without books is like a body without a soul.”</div></div><div class="auth-card"><span class="eyebrow">Get started</span><h2>Create your account</h2><p class="muted">It only takes a minute.</p><?php foreach ($errors as $error): ?><div class="notice error"><?= e($error) ?></div><?php endforeach; ?><form method="post" class="stack-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label>Full name<input name="name" required maxlength="80" value="<?= e($name) ?>" autocomplete="name"></label><label>Email address<input type="email" name="email" required value="<?= e($email) ?>" autocomplete="email"></label><label>Password<input type="password" name="password" required minlength="8" autocomplete="new-password"><small>Use at least 8 characters.</small></label><button class="button button-wide" type="submit">Create account</button></form><p class="auth-switch">Already a member? <a href="login.php">Log in</a></p></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
