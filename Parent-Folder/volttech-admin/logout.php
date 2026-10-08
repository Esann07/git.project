<?php
require_once __DIR__ . '/includes/auth.php';
if ($admin = currentAdmin()) {
    logAudit((int)$admin['id'], 'logout', $admin['username']);
}
session_destroy();
header('Location: login.php');
exit;
