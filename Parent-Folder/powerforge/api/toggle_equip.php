<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');

$user = currentUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Not logged in.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$inventoryId = (int)($input['inventory_id'] ?? 0);

$db = getDB();
$stmt = $db->prepare("
    SELECT i.*, g.energy_cost, g.name
    FROM inventory i
    JOIN gear g ON g.id = i.gear_id
    WHERE i.id = ? AND i.user_id = ?
");
$stmt->execute([$inventoryId, $user['id']]);
$row = $stmt->fetch();

if (!$row) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Item not found.']);
    exit;
}

if ((int)$row['equipped'] === 0) {
    // Trying to equip — check total drain won't exceed max_energy
    $drainStmt = $db->prepare("
        SELECT COALESCE(SUM(g.energy_cost), 0) AS total
        FROM inventory i JOIN gear g ON g.id = i.gear_id
        WHERE i.user_id = ? AND i.equipped = 1
    ");
    $drainStmt->execute([$user['id']]);
    $currentDrain = (int)$drainStmt->fetch()['total'];
    $newDrain = $currentDrain + (int)$row['energy_cost'];

    if ($newDrain > (int)$user['max_energy']) {
        echo json_encode([
            'ok' => false,
            'error' => "Equipping {$row['name']} would exceed your max bio-energy ({$newDrain}/{$user['max_energy']}).",
        ]);
        exit;
    }

    $db->prepare("UPDATE inventory SET equipped = 1 WHERE id = ?")->execute([$inventoryId]);
    echo json_encode(['ok' => true, 'equipped' => true, 'total_drain' => $newDrain]);
} else {
    $db->prepare("UPDATE inventory SET equipped = 0 WHERE id = ?")->execute([$inventoryId]);
    $drainStmt = $db->prepare("
        SELECT COALESCE(SUM(g.energy_cost), 0) AS total
        FROM inventory i JOIN gear g ON g.id = i.gear_id
        WHERE i.user_id = ? AND i.equipped = 1
    ");
    $drainStmt->execute([$user['id']]);
    $total = (int)$drainStmt->fetch()['total'];
    echo json_encode(['ok' => true, 'equipped' => false, 'total_drain' => $total]);
}
