<?php
require_once __DIR__ . '/includes/auth.php';

if (currentAdmin()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $db = getDB();
    // Reuses the exact same users table and password_hash column as the
    // player system — an admin's password is identical in both apps.
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        $error = 'Invalid username or password.';
    } elseif ($user['role'] !== 'admin') {
        $error = 'This account does not have admin access.';
    } else {
        $_SESSION['admin_id'] = (int)$user['id'];
        logAudit((int)$user['id'], 'login', $user['username']);
        header('Location: index.php');
        exit;
    }
}

$pageTitle = 'Login';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-card">
    <h1>Admin Login</h1>
    <p class="muted">Sign in with your existing PowerForge admin account.</p>
    <?php if ($error): ?><div class="flash flash-error"><?= h($error) ?></div><?php endif; ?>
    <form method="post" class="stack">
        <label>Username
            <input type="text" name="username" required autofocus>
        </label>
        <label>Password
            <input type="password" name="password" required>
        </label>
        <button type="submit" class="btn btn-primary">Log In</button>
    </form>
    <p class="muted small">Don't have an admin account yet? Promote one from the player system's
    <code>tools/promote_admin.php</code> script, then log in here with those same credentials.</p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
