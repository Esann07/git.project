<?php
require_once __DIR__ . '/../cors.php';
require_once __DIR__ . '/../../../includes/api_auth.php';
header('Content-Type: application/json');

$user = requireApiAuth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiError(405, 'Use POST.');
}

$gearId = (int)(apiInput()['gear_id'] ?? 0);
$db = getDB();

$stmt = $db->prepare("SELECT * FROM gear WHERE id = ?");
$stmt->execute([$gearId]);
$item = $stmt->fetch();

$owned = $db->prepare("SELECT id FROM inventory WHERE user_id = ? AND gear_id = ?");
$owned->execute([$user['id'], $gearId]);

if (!$item) {
    apiError(404, 'That item does not exist.');
}
if ($owned->fetch()) {
    apiError(409, 'You already own that item.');
}
if ((int)$user['credits'] < (int)$item['price']) {
    apiError(402, "Not enough credits for {$item['name']}.");
}

$db->beginTransaction();
$db->prepare("UPDATE users SET credits = credits - ? WHERE id = ?")->execute([$item['price'], $user['id']]);
$db->prepare("INSERT INTO inventory (user_id, gear_id) VALUES (?, ?)")->execute([$user['id'], $gearId]);
$db->commit();

echo json_encode(['ok' => true, 'message' => "Purchased {$item['name']} for {$item['price']} credits."]);
