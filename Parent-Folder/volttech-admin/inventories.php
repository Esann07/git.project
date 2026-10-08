<?php
require_once __DIR__ . '/includes/auth.php';
$admin = requireAdminLogin();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'remove_item') {
    $invId = (int)($_POST['inventory_id'] ?? 0);
    $db->prepare("DELETE FROM inventory WHERE id = ?")->execute([$invId]);
    logAudit((int)$admin['id'], 'remove_inventory_item', "inventory #{$invId}");
    flash('Item removed from that player\'s inventory.', 'success');
    header('Location: inventories.php');
    exit;
}

$query = trim($_GET['q'] ?? '');
$equippedOnly = isset($_GET['equipped']);

$sql = "
    SELECT i.id AS inventory_id, i.equipped, i.acquired_at, u.username, g.name AS gear_name, g.category
    FROM inventory i
    JOIN users u ON u.id = i.user_id
    JOIN gear g ON g.id = i.gear_id
    WHERE 1=1
";
$params = [];
if ($query !== '') {
    $sql .= " AND (u.username LIKE ? OR g.name LIKE ?)";
    $params[] = '%' . $query . '%';
    $params[] = '%' . $query . '%';
}
if ($equippedOnly) {
    $sql .= " AND i.equipped = 1";
}
$sql .= " ORDER BY i.id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle = 'Inventories';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <h1>Monitor Inventories</h1>
    <p class="muted">Read-only view of every player's owned gear, joined from <code>inventory</code>, <code>users</code> and <code>gear</code>.</p>
</div>

<form method="get" class="filter-form">
    <input type="text" name="q" placeholder="Search by username or gear..." value="<?= h($query) ?>">
    <label class="checkbox-label"><input type="checkbox" name="equipped" value="1" <?= $equippedOnly ? 'checked' : '' ?> onchange="this.form.submit()"> Equipped only</label>
    <button type="submit" class="btn btn-secondary">Filter</button>
</form>

<div style="overflow-x:auto;">
<table class="admin-table">
    <thead><tr><th>User</th><th>Gear</th><th>Category</th><th>Equipped</th><th>Acquired</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= h($r['username']) ?></td>
            <td><?= h($r['gear_name']) ?></td>
            <td><span class="tag"><?= h($r['category']) ?></span></td>
            <td><?= $r['equipped'] ? '<span class="status-on">Yes</span>' : '<span class="muted">No</span>' ?></td>
            <td class="muted small"><?= h($r['acquired_at']) ?></td>
            <td>
                <form method="post" onsubmit="return confirm('Remove this item from their inventory?');">
                    <input type="hidden" name="form_action" value="remove_item">
                    <input type="hidden" name="inventory_id" value="<?= (int)$r['inventory_id'] ?>">
                    <button type="submit" class="btn btn-danger" style="padding:4px 10px;font-size:0.75rem;">Remove</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (empty($rows)): ?><tr><td colspan="6" class="muted">No inventory items match that search/filter.</td></tr><?php endif; ?>
    </tbody>
</table>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
