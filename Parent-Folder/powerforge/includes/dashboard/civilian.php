<?php
/**
 * Civilian dashboard section.
 * Included by index.php when $user['role'] === 'civilian'.
 * Deliberately lighter — no power/notoriety stats, just community info.
 */

// Real query: simple community counts.
$counts = $db->query("
    SELECT role, COUNT(*) AS c FROM users GROUP BY role
")->fetchAll();
$countsByRole = array_column($counts, 'c', 'role');

// Static safety tips — fine to keep hardcoded, no backend needed.
$safetyTips = [
    'Never approach an active power-use incident — report it instead.',
    'Registered dampener gear is for licensed responders only.',
    'If you notice unregistered power use, use the report link below.',
];
?>
<section class="panel role-panel role-panel-civilian">
    <h2><?= icon('icon-building', 'icon icon-sm') ?> Community Snapshot</h2>
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-label">Registered Heroes</div>
            <div class="stat-value"><?= (int)($countsByRole['hero'] ?? 0) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Registered Villains</div>
            <div class="stat-value"><?= (int)($countsByRole['villain'] ?? 0) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Civilians Like You</div>
            <div class="stat-value"><?= (int)($countsByRole['civilian'] ?? 0) ?></div>
        </div>
    </div>
</section>

<section class="panel role-panel role-panel-civilian">
    <h2><?= icon('icon-newspaper', 'icon icon-sm') ?> Safety Feed</h2>
    <ul class="log-list">
        <?php foreach ($safetyTips as $tip): ?>
            <li><span><?= h($tip) ?></span></li>
        <?php endforeach; ?>
    </ul>
    <a class="btn btn-secondary" href="#" onclick="alert('Reporting isn\'t wired up yet — placeholder link.'); return false;">Report Suspicious Activity</a>
</section>
