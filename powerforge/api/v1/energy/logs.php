<?php
require_once __DIR__ . '/../cors.php';
require_once __DIR__ . '/../../../includes/api_auth.php';
header('Content-Type: application/json');

$user = requireApiAuth();
$db = getDB();
$stmt = $db->prepare("SELECT * FROM energy_logs WHERE user_id = ? ORDER BY id DESC LIMIT 30");
$stmt->execute([$user['id']]);
echo json_encode(['ok' => true, 'logs' => $stmt->fetchAll()]);
