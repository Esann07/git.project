<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function currentUser(): ?array {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function requireLogin(): array {
    $user = currentUser();
    if (!$user) {
        header('Location: login.php');
        exit;
    }
    return $user;
}

function requireAdmin(): array {
    $user = requireLogin();
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        flash('Admins only.', 'error');
        header('Location: index.php');
        exit;
    }
    return $user;
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

/** Maps a gear category to its line-icon symbol id (see includes/icons.php). */
function categoryIcon(string $category): string {
    return match ($category) {
        'suit'     => 'icon-shield',
        'dampener' => 'icon-link',
        'gadget'   => 'icon-cpu',
        default    => 'icon-cpu',
    };
}

/** Renders an <svg><use> reference to a symbol defined in includes/icons.php. */
function icon(string $name, string $class = 'icon'): string {
    return '<svg class="' . h($class) . '" aria-hidden="true"><use href="#' . h($name) . '"/></svg>';
}

function adjustEnergy(int $userId, int $amount, string $reason): void {
    $db = getDB();
    $db->beginTransaction();
    $stmt = $db->prepare("UPDATE users SET current_energy = MAX(0, MIN(max_energy, current_energy + ?)) WHERE id = ?");
    $stmt->execute([$amount, $userId]);
    $log = $db->prepare("INSERT INTO energy_logs (user_id, change_amount, reason) VALUES (?, ?, ?)");
    $log->execute([$userId, $amount, $reason]);
    $db->commit();
}
