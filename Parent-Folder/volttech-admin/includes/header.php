<?php
/** @var array|null $admin */
$admin = $admin ?? currentAdmin();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>VoltTech Admin<?= isset($pageTitle) ? ' · ' . h($pageTitle) : '' ?></title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<header class="topbar">
    <div class="topbar-inner">
        <a href="index.php" class="brand">VoltTech <span>Admin</span></a>
        <?php if ($admin): ?>
        <nav class="nav">
            <a href="index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">Dashboard</a>
            <a href="users.php" class="<?= $currentPage === 'users.php' ? 'active' : '' ?>">Users</a>
            <a href="gear.php" class="<?= $currentPage === 'gear.php' ? 'active' : '' ?>">Gear</a>
            <a href="inventories.php" class="<?= $currentPage === 'inventories.php' ? 'active' : '' ?>">Inventories</a>
            <a href="energy_logs.php" class="<?= $currentPage === 'energy_logs.php' ? 'active' : '' ?>">Energy Logs</a>
            <a href="audit_log.php" class="<?= $currentPage === 'audit_log.php' ? 'active' : '' ?>">Audit Log</a>
        </nav>
        <div class="admin-chip">
            <span class="username"><?= h($admin['username']) ?></span>
            <a href="logout.php" class="logout-link">Logout</a>
        </div>
        <?php endif; ?>
    </div>
</header>
<main class="page">
<?php foreach (getFlashes() as $f): ?>
    <div class="flash flash-<?= h($f['type']) ?>"><?= h($f['msg']) ?></div>
<?php endforeach; ?>
