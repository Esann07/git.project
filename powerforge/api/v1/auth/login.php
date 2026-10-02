<?php
require_once __DIR__ . '/../cors.php';
require_once __DIR__ . '/../../../includes/api_auth.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiError(405, 'Use POST.');
}

$input = apiInput();
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';

$db = getDB();
$stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    apiError(401, 'Invalid username or password.');
}

$token = issueToken((int)$user['id']);
echo json_encode(['ok' => true, 'token' => $token, 'user' => publicUser($user)]);
