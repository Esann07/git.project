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
$role = $input['role'] ?? 'civilian';

if ($username === '' || strlen($password) < 4) {
    apiError(422, 'Username required; password must be at least 4 characters.');
}
if (!in_array($role, ['hero', 'villain', 'civilian'], true)) {
    apiError(422, 'Role must be hero, villain, or civilian.');
}

$db = getDB();
$exists = $db->prepare("SELECT id FROM users WHERE username = ?");
$exists->execute([$username]);
if ($exists->fetch()) {
    apiError(409, 'That username is already taken.');
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$db->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)")
   ->execute([$username, $hash, $role]);
$userId = (int)$db->lastInsertId();

$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$token = issueToken($userId);
echo json_encode(['ok' => true, 'token' => $token, 'user' => publicUser($user)]);
