<?php
/**
 * PowerForge - Promote a user to admin
 *
 * Run from the VS Code terminal (NOT accessible over the web):
 *   php tools/promote_admin.php <username>
 *
 * Example:
 *   php tools/promote_admin.php alex
 *
 * This is deliberately a CLI-only script. Admin promotion should never be
 * exposed as a public web form/registration option — otherwise anyone
 * could grant themselves admin rights.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("This script can only be run from the command line.\n");
}

require_once __DIR__ . '/../config/database.php';

$username = $argv[1] ?? null;
if (!$username) {
    fwrite(STDERR, "Usage: php tools/promote_admin.php <username>\n");
    exit(1);
}

$db = getDB();
$stmt = $db->prepare("SELECT id, username, role FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user) {
    fwrite(STDERR, "No user found with username '{$username}'. Register that account in the app first.\n");
    exit(1);
}

if ($user['role'] === 'admin') {
    echo "'{$username}' is already an admin.\n";
    exit(0);
}

$db->prepare("UPDATE users SET role = 'admin' WHERE id = ?")->execute([$user['id']]);
echo "Done — '{$username}' is now an admin. Log out and back in to see the Admin panel.\n";
