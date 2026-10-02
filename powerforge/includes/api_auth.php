<?php
/**
 * Bearer-token auth for the JSON API (api/v1/*).
 *
 * Your existing pages (index.php, marketplace.php, etc.) keep using
 * PHP session cookies via includes/auth.php — that's untouched.
 * The new API uses a separate mechanism: a random token issued at
 * login/register, sent back on every request as:
 *
 *   Authorization: Bearer <token>
 *
 * This avoids cross-origin cookie problems entirely, since a bearer
 * token doesn't rely on the browser's same-origin cookie rules.
 */

require_once __DIR__ . '/auth.php'; // gives us getDB() (session_start() here is harmless for token requests)

function bearerToken(): ?string {
    $header = $_SERVER['HTTP_AUTHORIZATION']
        ?? (function_exists('getallheaders') ? (getallheaders()['Authorization'] ?? '') : '');
    if (preg_match('/Bearer\s+(\S+)/i', $header, $m)) {
        return $m[1];
    }
    return null;
}

function apiError(int $status, string $message): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => $message]);
    exit;
}

function apiInput(): array {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

function requireApiAuth(): array {
    $token = bearerToken();
    if (!$token) {
        apiError(401, 'Missing Authorization: Bearer <token> header.');
    }
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE api_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    if (!$user) {
        apiError(401, 'Invalid or expired token.');
    }
    return $user;
}

/** Issues a fresh token for a user, overwriting any previous one (single active session). */
function issueToken(int $userId): string {
    $token = bin2hex(random_bytes(32));
    $db = getDB();
    $db->prepare("UPDATE users SET api_token = ? WHERE id = ?")->execute([$token, $userId]);
    return $token;
}

/** Strips password_hash and api_token out of a user row before sending it to the client. */
function publicUser(array $user): array {
    return [
        'id'             => (int)$user['id'],
        'username'       => $user['username'],
        'role'           => $user['role'],
        'credits'        => (int)$user['credits'],
        'current_energy' => (int)$user['current_energy'],
        'max_energy'     => (int)$user['max_energy'],
    ];
}
