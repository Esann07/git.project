<?php
/**
 * Hero dashboard section.
 * Included by index.php when $user['role'] === 'hero'.
 * Inherits $db and $user from the parent scope (plain PHP includes
 * share variable scope with the file that requires them).
 */

// Real query: rank heroes by total power rating of their equipped gear.
$heroLeaderboard = $db->query("
    SELECT u.username, COALESCE(SUM(g.power_rating), 0) AS total_power
    FROM users u
    LEFT JOIN inventory i ON i.user_id = u.id AND i.equipped = 1
    LEFT JOIN gear g ON g.id = i.gear_id
    WHERE u.role = 'hero'
    GROUP BY u.id
    ORDER BY total_power DESC
    LIMIT 5
")->fetchAll();

// Placeholder data — swap for a real `incidents` table when you build one.
$activeThreats = [
    ['label' => 'Warehouse fire — Downtown Sector 4', 'urgency' => 'high'],
    ['label' => 'Bank alarm triggered — 5th & Main', 'urgency' => 'medium'],
    ['label' => 'Reported sighting — unregistered power use', 'urgency' => 'low'],
];
?>
<section class="panel role-panel role-panel-hero">
    <h2><?= icon('icon-alert', 'icon icon-sm') ?> Threat Board</h2>
    <p class="muted small">Sample data — wire this up to a real incidents table later.</p>
    <ul class="log-list">
        <?php foreach ($activeThreats as $t): ?>
        <li>
            <span class="tag tag-<?= $t['urgency'] === 'high' ? 'dampener' : ($t['urgency'] === 'medium' ? 'suit' : 'gadget') ?>">
                <?= h(ucfirst($t['urgency'])) ?>
            </span>
            <span><?= h($t['label']) ?></span>
        </li>
        <?php endforeach; ?>
    </ul>
</section>

<section class="panel role-panel role-panel-hero">
    <h2><?= icon('icon-star', 'icon icon-sm') ?> Power Leaderboard</h2>
    <ul class="log-list">
        <?php foreach ($heroLeaderboard as $i => $row): ?>
        <li>
            <span>#<?= $i + 1 ?></span>
            <span style="flex:1"><?= h($row['username']) ?></span>
            <span class="gain"><?= icon('icon-star', 'icon icon-xs') ?> <?= (int)$row['total_power'] ?></span>
        </li>
        <?php endforeach; ?>
    </ul>
</section>
