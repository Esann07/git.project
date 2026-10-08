<?php
require_once __DIR__ . '/../config/database.php';

/**
 * A distinct session cookie name from the player app. If both apps ever
 * run on the same hostname (e.g. localhost on two different ports),
 * browser cookies are shared by domain — NOT by port — so without this
 * the two apps could overwrite each other's login session.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_name('powerforge_admin_session');
    session_start();
}

/**
 * Returns the logged-in ADMIN's row, or null. Deliberately re-checks
 * role = 'admin' on every call (not just at login) — if someone's role
 * gets changed away from admin mid-session, they lose admin access on
 * their very next request instead of keeping it until they log out.
 */
function currentAdmin(): ?array {
    if (!isset($_SESSION['admin_id'])) {
        return null;
    }
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND role = 'admin'");
    $stmt->execute([$_SESSION['admin_id']]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function requireAdminLogin(): array {
    $admin = currentAdmin();
    if (!$admin) {
        header('Location: login.php');
        exit;
    }
    return $admin;
}

function flash(string $msg, string $type = 'info'): void {
    $_SESSION['flash'][] = ['msg' => $msg, 'type' => $type];
}

function getFlashes(): array {
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/** Records an action in admin_audit_log. Never throws — logging failures must never break the actual request. */
function logAudit(int $adminId, string $action, string $target): void {
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO admin_audit_log (admin_id, action, target) VALUES (?, ?, ?)");
        $stmt->execute([$adminId, $action, $target]);
    } catch (Throwable $e) {
        // Intentionally swallowed — audit logging is best-effort, not critical-path.
    }
}
