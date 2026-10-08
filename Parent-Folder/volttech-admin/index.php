<?php
require_once __DIR__ . '/includes/auth.php';
$admin = requireAdminLogin();
$db = getDB();

$totalUsers    = $db->query("SELECT COUNT(*) c FROM users")->fetch()['c'];
$usersByRole   = $db->query("SELECT role, COUNT(*) c FROM users GROUP BY role")->fetchAll();
$totalGear     = $db->query("SELECT COUNT(*) c FROM gear")->fetch()['c'];
$totalInvItems = $db->query("SELECT COUNT(*) c FROM inventory")->fetch()['c'];
$equippedItems = $db->query("SELECT COUNT(*) c FROM inventory WHERE equipped = 1")->fetch()['c'];
$totalCredits  = $db->query("SELECT COALESCE(SUM(credits),0) s FROM users")->fetch()['s'];
$totalLogs     = $db->query("SELECT COUNT(*) c FROM energy_logs")->fetch()['c'];

$topGear = $db->query("
    SELECT g.name, COUNT(i.id) owners
    FROM gear g LEFT JOIN inventory i ON i.gear_id = g.id
    GROUP BY g.id ORDER BY owners DESC LIMIT 5
")->fetchAll();

$recentUsers = $db->query("SELECT username, role, created_at FROM users ORDER BY id DESC LIMIT 5")->fetchAll();

$recentAudit = $db->query("
    SELECT a.action, a.target, a.created_at, u.username
    FROM admin_audit_log a JOIN users u ON u.id = a.admin_id
    ORDER BY a.id DESC LIMIT 5
")->fetchAll();

$pageTitle = 'Dashboard';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <h1>System Dashboard</h1>
    <p class="muted">Live statistics from the shared PowerForge database.</p>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-label">Total Users</div>
        <div class="stat-value"><?= (int)$totalUsers ?></div>
        <div class="muted small">
            <?php foreach ($usersByRole as $r): ?>
                <?= h(ucfirst($r['role'])) ?>: <?= (int)$r['c'] ?> &nbsp;
            <?php endforeach; ?>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Gear in Catalog</div>
        <div class="stat-value"><?= (int)$totalGear ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Inventory Items</div>
        <div class="stat-value"><?= (int)$totalInvItems ?></div>
        <div class="muted small"><?= (int)$equippedItems ?> currently equipped</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Credits in Economy</div>
        <div class="stat-value"><?= (int)$totalCredits ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Energy Log Entries</div>
        <div class="stat-value"><?= (int)$totalLogs ?></div>
    </div>
</div>

<div class="two-col">
    <section class="panel">
        <h2>Most-Owned Gear</h2>
        <?php if (empty($topGear)): ?>
            <p class="muted">No purchases yet.</p>
        <?php else: ?>
            <ul class="plain-list">
                <?php foreach ($topGear as $g): ?>
                <li><span><?= h($g['name']) ?></span><span class="muted"><?= (int)$g['owners'] ?> owners</span></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <a class="btn btn-secondary" href="gear.php">Manage Gear</a>
    </section>

    <section class="panel">
        <h2>Recently Registered</h2>
        <?php if (empty($recentUsers)): ?>
            <p class="muted">No users yet.</p>
        <?php else: ?>
            <ul class="plain-list">
                <?php foreach ($recentUsers as $u): ?>
                <li><span><?= h($u['username']) ?> <span class="tag"><?= h($u['role']) ?></span></span><span class="muted small"><?= h($u['created_at']) ?></span></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <a class="btn btn-secondary" href="users.php">Manage Users</a>
    </section>
</div>

<section class="panel" style="margin-top:20px;">
    <h2>Recent Admin Activity</h2>
    <?php if (empty($recentAudit)): ?>
        <p class="muted">No admin actions logged yet.</p>
    <?php else: ?>
        <ul class="plain-list">
            <?php foreach ($recentAudit as $a): ?>
            <li>
                <span><strong><?= h($a['username']) ?></strong> <span class="tag"><?= h($a['action']) ?></span> <?= h($a['target']) ?></span>
                <span class="muted small"><?= h($a['created_at']) ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <a class="btn btn-secondary" href="audit_log.php">View Full Audit Log</a>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
