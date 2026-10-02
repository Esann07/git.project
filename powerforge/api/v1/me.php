<?php
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../../includes/api_auth.php';
header('Content-Type: application/json');

$user = requireApiAuth();
echo json_encode(['ok' => true, 'user' => publicUser($user)]);
