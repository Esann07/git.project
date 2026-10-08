<?php
require_once __DIR__ . '/includes/auth.php';
$admin = requireAdminLogin();
$db = getDB();

$query = trim($_GET['q'] ?? '');
$actionFilter = trim($_GET['action'] ?? '');

$sql = "
    SELECT a.*, u.username AS admin_username
    FROM admin_audit_log a
    JOIN users u ON u.id = a.admin_id
    WHERE 1=1
";
$params = [];
if ($query !== '') {
    $sql .= " AND (u.username LIKE ? OR a.target LIKE ?)";
    $params[] = '%' . $query . '%';
    $params[] = '%' . $query . '%';
}
if ($actionFilter !== '' && $actionFilter !== 'all') {
    $sql .= " AND a.action = ?";
    $params[] = $actionFilter;
}
$sql .= " ORDER BY a.id DESC LIMIT 200";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$entries = $stmt->fetchAll();

$actionTypes = $db->query("SELECT DISTINCT action FROM admin_audit_log ORDER BY action")->fetchAll();

$pageTitle = 'Audit Log';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <h1>Audit Log</h1>
    <p class="muted">Every action taken from this admin app, read from the additive-only <code>admin_audit_log</code> table. Most recent 200 entries.</p>
</div>

<form method="get" class="filter-form">
    <input type="text" name="q" placeholder="Search admin or target..." value="<?= h($query) ?>">
    <select name="action" onchange="this.form.submit()">
        <option value="all">All actions</option>
        <?php foreach ($actionTypes as $a): ?>
            <option value="<?= h($a['action']) ?>" <?= $actionFilter === $a['action'] ? 'selected' : '' ?>><?= h($a['action']) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-secondary">Filter</button>
</form>

<div style="overflow-x:auto;">
<table class="admin-table">
    <thead><tr><th>Admin</th><th>Action</th><th>Target</th><th>When</th></tr></thead>
    <tbody>
    <?php foreach ($entries as $e): ?>
        <tr>
            <td><?= h($e['admin_username']) ?></td>
            <td><span class="tag"><?= h($e['action']) ?></span></td>
            <td><?= h($e['target']) ?></td>
            <td class="muted small"><?= h($e['created_at']) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (empty($entries)): ?>
        <tr><td colspan="4" class="muted">No audit entries yet — actions you take in this app will show up here.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
