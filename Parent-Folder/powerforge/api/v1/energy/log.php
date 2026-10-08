<?php
require_once __DIR__ . '/../cors.php';
require_once __DIR__ . '/../../../includes/api_auth.php';
header('Content-Type: application/json');

$user = requireApiAuth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiError(405, 'Use POST.');
}

$input = apiInput();
$amount = (int)($input['amount'] ?? 0);
$reason = trim($input['reason'] ?? '');
$action = $input['action'] ?? 'use';

if ($amount <= 0 || $reason === '') {
    apiError(422, 'amount must be positive and reason must not be empty.');
}

$signed = $action === 'gain' ? $amount : -$amount;
adjustEnergy((int)$user['id'], $signed, $reason); // reuses the exact function tracker.php already calls

$db = getDB();
$stmt = $db->prepare("SELECT current_energy, max_energy FROM users WHERE id = ?");
$stmt->execute([$user['id']]);
echo json_encode(['ok' => true] + $stmt->fetch());
