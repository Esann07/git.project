<?php
require_once __DIR__ . '/../cors.php';
require_once __DIR__ . '/../../../includes/api_auth.php';
header('Content-Type: application/json');

$user = requireApiAuth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiError(405, 'Use POST.');
}

$invId = (int)(apiInput()['inventory_id'] ?? 0);
$db = getDB();

$stmt = $db->prepare("
    SELECT i.*, g.energy_cost, g.name
    FROM inventory i JOIN gear g ON g.id = i.gear_id
    WHERE i.id = ? AND i.user_id = ?
");
$stmt->execute([$invId, $user['id']]);
$row = $stmt->fetch();

if (!$row) {
    apiError(404, 'Item not found.');
}

$drainStmt = $db->prepare("
    SELECT COALESCE(SUM(g.energy_cost), 0) AS total
    FROM inventory i JOIN gear g ON g.id = i.gear_id
    WHERE i.user_id = ? AND i.equipped = 1
");
$drainStmt->execute([$user['id']]);
$currentDrain = (int)$drainStmt->fetch()['total'];

if ((int)$row['equipped'] === 0) {
    $newDrain = $currentDrain + (int)$row['energy_cost'];
    if ($newDrain > (int)$user['max_energy']) {
        apiError(422, "Equipping {$row['name']} would exceed your max bio-energy ({$newDrain}/{$user['max_energy']}).");
    }
    $db->prepare("UPDATE inventory SET equipped = 1 WHERE id = ?")->execute([$invId]);
    echo json_encode(['ok' => true, 'equipped' => true, 'total_drain' => $newDrain]);
} else {
    $db->prepare("UPDATE inventory SET equipped = 0 WHERE id = ?")->execute([$invId]);
    echo json_encode(['ok' => true, 'equipped' => false, 'total_drain' => $currentDrain - (int)$row['energy_cost']]);
}
