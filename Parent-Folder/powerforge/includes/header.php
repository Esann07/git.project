<?php
/** @var array|null $user */
$user = $user ?? currentUser();
$currentPage = basename($_SERVER['PHP_SELF']);

$navItems = [
    'index.php'        => ['label' => 'Dashboard',      'icon' => 'icon-grid'],
    'marketplace.php'   => ['label' => 'Marketplace',    'icon' => 'icon-store'],
    'customizer.php'    => ['label' => 'Customizer',     'icon' => 'icon-cpu'],
    'tracker.php'       => ['label' => 'Energy Tracker', 'icon' => 'icon-battery'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>VoltTech<?= isset($pageTitle) ? ' · ' . h($pageTitle) : '' ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@600;700;800&family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require __DIR__ . '/icons.php'; ?>

<div class="app-shell">
    <?php if ($user): ?>
    <aside class="sidebar">
        <a href="index.php" class="brand">
            <span class="brand-mark"><?= icon('icon-shield', 'icon') ?></span>
            <span class="brand-word">Volt<b>Tech</b></span>
        </a>

        <nav class="side-nav">
            <?php foreach ($navItems as $href => $item): ?>
            <a href="<?= $href ?>" class="<?= $currentPage === $href ? 'active' : '' ?>">
                <?= icon($item['icon'], 'icon') ?><span><?= $item['label'] ?></span>
            </a>
            <?php endforeach; ?>
            <?php if ($user['role'] === 'admin'): ?>
            <a href="admin.php" class="<?= $currentPage === 'admin.php' ? 'active' : '' ?>">
                <?= icon('icon-building', 'icon') ?><span>Admin</span>
            </a>
            <?php endif; ?>
        </nav>

        <div class="side-foot">
            <div class="side-stats">
                <div class="side-stat" title="Bio-Energy">
                    <?= icon('icon-battery', 'icon icon-sm') ?>
                    <span><?= (int)$user['current_energy'] ?>/<?= (int)$user['max_energy'] ?></span>
                </div>
                <div class="side-stat" title="Credits">
                    <?= icon('icon-coin', 'icon icon-sm') ?>
                    <span><?= (int)$user['credits'] ?></span>
                </div>
            </div>
            <div class="side-account">
                <div class="avatar"><?= h(strtoupper(substr($user['username'], 0, 1))) ?></div>
                <div class="side-account-text">
                    <span class="username"><?= h($user['username']) ?></span>
                    <span class="role-tag role-<?= h($user['role']) ?>"><?= h(ucfirst($user['role'])) ?></span>
                </div>
                <a href="logout.php" class="logout-link" title="Log out"><?= icon('icon-logout', 'icon icon-sm') ?></a>
            </div>
        </div>
    </aside>
    <?php endif; ?>

    <div class="main-column">
        <main class="page">
        <?php foreach (getFlashes() as $f): ?>
            <div class="flash flash-<?= h($f['type']) ?>"><?= h($f['msg']) ?></div>
        <?php endforeach; ?>
