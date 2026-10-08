<?php
require_once __DIR__ . '/../cors.php';
require_once __DIR__ . '/../../../includes/api_auth.php';
header('Content-Type: application/json');

requireApiAuth(); // matches existing marketplace.php: catalog is login-gated

$db = getDB();
$category = $_GET['category'] ?? null;

if ($category && in_array($category, ['suit', 'dampener', 'gadget'], true)) {
    $stmt = $db->prepare("SELECT * FROM gear WHERE category = ? ORDER BY price");
    $stmt->execute([$category]);
} else {
    $stmt = $db->query("SELECT * FROM gear ORDER BY category, price");
}

echo json_encode(['ok' => true, 'gear' => $stmt->fetchAll()]);
