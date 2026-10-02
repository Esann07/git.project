<?php
require_once __DIR__ . '/includes/auth.php';
$user = requireLogin();
$db = getDB();

define('SELL_RATE', 0.5); // fraction of original price refunded on sale

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sell_inventory_id'])) {
    $invId = (int)$_POST['sell_inventory_id'];
    $stmt = $db->prepare("
        SELECT i.id, i.user_id, g.name, g.price
        FROM inventory i JOIN gear g ON g.id = i.gear_id
        WHERE i.id = ? AND i.user_id = ?
    ");
    $stmt->execute([$invId, $user['id']]);
    $row = $stmt->fetch();

    if (!$row) {
        flash('That item is not in your inventory.', 'error');
    } else {
        $refund = (int)round($row['price'] * SELL_RATE);
        $db->beginTransaction();
        $db->prepare("DELETE FROM inventory WHERE id = ?")->execute([$invId]);
        $db->prepare("UPDATE users SET credits = credits + ? WHERE id = ?")->execute([$refund, $user['id']]);
        $db->commit();
        flash("Sold {$row['name']} for {$refund} credits.", 'success');
    }
    header('Location: customizer.php');
    exit;
}

$user = currentUser(); // refresh credits after a possible sale

$stmt = $db->prepare("
    SELECT i.id AS inventory_id, i.equipped, g.*
    FROM inventory i
    JOIN gear g ON g.id = i.gear_id
    WHERE i.user_id = ?
    ORDER BY g.category, g.name
");
$stmt->execute([$user['id']]);
$owned = $stmt->fetchAll();

$equippedDrain = array_sum(array_map(fn($g) => $g['equipped'] ? (int)$g['energy_cost'] : 0, $owned));

$pageTitle = 'Customizer';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <h1>Loadout Customizer</h1>
    <p class="muted">Equip owned gear. Total drain can't exceed your max bio-energy.</p>
</div>

<div class="customizer-summary" id="drainSummary"
     data-max="<?= (int)$user['max_energy'] ?>"
     data-current="<?= (int)$equippedDrain ?>">
    <div>Total Equipped Drain: <strong id="drainValue"><?= (int)$equippedDrain ?></strong> / <?= (int)$user['max_energy'] ?> <?= icon('icon-battery', 'icon icon-xs') ?></div>
    <div class="bar"><div class="bar-fill" id="drainBar" style="width: <?= min(100, round($equippedDrain / max(1,$user['max_energy']) * 100)) ?>%"></div></div>
</div>

<?php if (empty($owned)): ?>
    <p class="muted">You don't own any gear yet. Visit the <a href="marketplace.php">Marketplace</a> to buy some.</p>
<?php else: ?>
<div class="grid" id="loadoutGrid">
    <?php foreach ($owned as $g): ?>
    <div class="card gear-card" data-inventory-id="<?= (int)$g['inventory_id'] ?>" data-cost="<?= (int)$g['energy_cost'] ?>">
        <div class="card-icon card-icon-<?= h($g['category']) ?>"><?= icon(categoryIcon($g['category']), 'icon icon-lg') ?></div>
        <h3><?= h($g['name']) ?></h3>
        <span class="tag tag-<?= h($g['category']) ?>"><?= h($g['category']) ?></span>
        <p class="card-desc"><?= h($g['description']) ?></p>
        <div class="card-stats">
            <span><?= icon('icon-battery', 'icon icon-xs') ?> <?= (int)$g['energy_cost'] ?> drain</span>
            <span><?= icon('icon-star', 'icon icon-xs') ?> <?= (int)$g['power_rating'] ?> power</span>
        </div>
        <div class="card-footer sell-footer">
            <button class="btn toggle-equip <?= $g['equipped'] ? 'btn-danger' : 'btn-primary' ?>"
                    data-equipped="<?= (int)$g['equipped'] ?>">
                <?= $g['equipped'] ? 'Unequip' : 'Equip' ?>
            </button>
            <form method="post" class="sell-form" onsubmit="return confirm('Sell <?= h(addslashes($g['name'])) ?> for <?= (int)round($g['price'] * SELL_RATE) ?> credits?');">
                <input type="hidden" name="sell_inventory_id" value="<?= (int)$g['inventory_id'] ?>">
                <button type="submit" class="btn btn-sell" title="Sell for <?= (int)round($g['price'] * SELL_RATE) ?> credits">
                    <?= icon('icon-coin', 'icon icon-xs') ?> Sell <?= (int)round($g['price'] * SELL_RATE) ?>
                </button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
