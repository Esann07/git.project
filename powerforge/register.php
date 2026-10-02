<?php
require_once __DIR__ . '/includes/auth.php';

if (currentUser()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? 'hero';

    if ($username === '' || $password === '') {
        $error = 'Username and password are required.';
    } elseif (strlen($password) < 4) {
        $error = 'Password must be at least 4 characters.';
    } elseif (!in_array($role, ['hero', 'villain', 'civilian'], true)) {
        $error = 'Invalid role.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = 'That username is already taken.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $insert = $db->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)");
            $insert->execute([$username, $hash, $role]);
            $_SESSION['user_id'] = (int)$db->lastInsertId();
            flash("Welcome to PowerForge, {$username}. Your bio-energy reserves are fully charged.", 'success');
            header('Location: index.php');
            exit;
        }
    }
}

$pageTitle = 'Register';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-card">
    <h1>Create your account</h1>
    <p class="muted">Register as a hero or villain to start equipping gear.</p>
    <?php if ($error): ?><div class="flash flash-error"><?= h($error) ?></div><?php endif; ?>
    <form method="post" class="stack">
        <label>Username
            <input type="text" name="username" required maxlength="30" value="<?= h($_POST['username'] ?? '') ?>">
        </label>
        <label>Password
            <input type="password" name="password" required minlength="4">
        </label>
        <label>Role
            <select name="role">
                <option value="hero">Hero</option>
                <option value="villain">Villain</option>
                <option value="civilian">Civilian</option>
            </select>
        </label>
        <button type="submit" class="btn btn-primary">Create Account</button>
    </form>
    <p class="muted">Already registered? <a href="login.php">Log in</a></p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
