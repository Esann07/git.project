<?php
/**
 * Villain dashboard section.
 * Included by index.php when $user['role'] === 'villain'.
 */

// Real query: "notoriety" = equipped power rating + a bonus per dampener owned
// (flavor formula, but built from actual inventory data, not hardcoded).
$notorietyStmt = $db->prepare("
    SELECT
        COALESCE(SUM(CASE WHEN i.equipped = 1 THEN g.power_rating ELSE 0 END), 0) AS equipped_power,
        COALESCE(SUM(CASE WHEN g.category = 'dampener' THEN 1 ELSE 0 END), 0) AS dampener_count
    FROM inventory i
    JOIN gear g ON g.id = i.gear_id
    WHERE i.user_id = ?
");
$notorietyStmt->execute([$user['id']]);
$row = $notorietyStmt->fetch();
$notoriety = (int)$row['equipped_power'] + ((int)$row['dampener_count'] * 5);

// Real query: rank villains by the same notoriety idea, computed inline.
$villainLeaderboard = $db->query("
    SELECT u.username,
        COALESCE(SUM(CASE WHEN i.equipped = 1 THEN g.power_rating ELSE 0 END), 0)
        + (COALESCE(SUM(CASE WHEN g.category = 'dampener' THEN 1 ELSE 0 END), 0) * 5) AS notoriety
    FROM users u
    LEFT JOIN inventory i ON i.user_id = u.id
    LEFT JOIN gear g ON g.id = i.gear_id
    WHERE u.role = 'villain'
    GROUP BY u.id
    ORDER BY notoriety DESC
    LIMIT 5
")->fetchAll();

// Placeholder — swap for a real `plots` table later.
$knownPlots = [
    'Heist blueprint — Central Vault (in progress)',
    'Signal jammer network — city-wide (planning)',
];
?>
<section class="panel role-panel role-panel-villain">
    <h2><?= icon('icon-alert', 'icon icon-sm') ?> Notoriety Score</h2>
    <div class="stat-value"><?= $notoriety ?></div>
    <p class="muted small">Equipped power rating + 5 per dampener owned.</p>
</section>

<section class="panel role-panel role-panel-villain">
    <h2><?= icon('icon-scroll', 'icon icon-sm') ?> Known Plots</h2>
    <p class="muted small">Sample data — wire this up to a real plots table later.</p>
    <ul class="log-list">
        <?php foreach ($knownPlots as $p): ?>
            <li><span><?= h($p) ?></span></li>
        <?php endforeach; ?>
    </ul>
</section>

<section class="panel role-panel role-panel-villain">
    <h2><?= icon('icon-network', 'icon icon-sm') ?> Underworld Ranking</h2>
    <ul class="log-list">
        <?php foreach ($villainLeaderboard as $i => $r): ?>
        <li>
            <span>#<?= $i + 1 ?></span>
            <span style="flex:1"><?= h($r['username']) ?></span>
            <span class="loss"><?= icon('icon-alert', 'icon icon-xs') ?> <?= (int)$r['notoriety'] ?></span>
        </li>
        <?php endforeach; ?>
    </ul>
</section>
