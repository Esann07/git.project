<?php
require_once __DIR__ . '/../cors.php';
require_once __DIR__ . '/../../../includes/api_auth.php';
header('Content-Type: application/json');

define('SELL_RATE', 0.5); // matches customizer.php's sell rate

$user = requireApiAuth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiError(405, 'Use POST.');
}

$invId = (int)(apiInput()['inventory_id'] ?? 0);
$db = getDB();

$stmt = $db->prepare("
    SELECT i.id, g.name, g.price
    FROM inventory i JOIN gear g ON g.id = i.gear_id
    WHERE i.id = ? AND i.user_id = ?
");
$stmt->execute([$invId, $user['id']]);
$row = $stmt->fetch();

if (!$row) {
    apiError(404, 'That item is not in your inventory.');
}

$refund = (int)round($row['price'] * SELL_RATE);
$db->beginTransaction();
$db->prepare("DELETE FROM inventory WHERE id = ?")->execute([$invId]);
$db->prepare("UPDATE users SET credits = credits + ? WHERE id = ?")->execute([$refund, $user['id']]);
$db->commit();

echo json_encode(['ok' => true, 'message' => "Sold {$row['name']} for {$refund} credits.", 'refund' => $refund]);
