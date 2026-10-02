<?php
require_once __DIR__ . '/../cors.php';
require_once __DIR__ . '/../../../includes/api_auth.php';
header('Content-Type: application/json');

$user = requireApiAuth();
$db = getDB();
$stmt = $db->prepare("
    SELECT i.id AS inventory_id, i.equipped, g.*
    FROM inventory i
    JOIN gear g ON g.id = i.gear_id
    WHERE i.user_id = ?
    ORDER BY g.category, g.name
");
$stmt->execute([$user['id']]);
echo json_encode(['ok' => true, 'inventory' => $stmt->fetchAll()]);
