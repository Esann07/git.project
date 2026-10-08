<?php
require_once __DIR__ . '/includes/auth.php';
$admin = requireAdminLogin();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['form_action'] ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);

    if ($action === 'update_user') {
        $credits   = max(0, (int)($_POST['credits'] ?? 0));
        $maxEnergy = max(1, (int)($_POST['max_energy'] ?? 1));
        $role      = $_POST['role'] ?? 'civilian';

        if (!in_array($role, ['hero', 'villain', 'civilian', 'admin'], true)) {
            flash('Invalid role.', 'error');
        } else {
            $stmt = $db->prepare("
                UPDATE users
                SET credits = ?, max_energy = ?, current_energy = MIN(current_energy, ?), role = ?
                WHERE id = ?
            ");
            $stmt->execute([$credits, $maxEnergy, $maxEnergy, $role, $userId]);
            logAudit((int)$admin['id'], 'update_user', "user #{$userId} -> role={$role}, credits={$credits}, max_energy={$maxEnergy}");
            flash('User updated.', 'success');
        }
    }

    if ($action === 'delete_user') {
        if ($userId === (int)$admin['id']) {
            flash("You can't delete the admin account you're logged in as.", 'error');
        } else {
            $stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $target = $stmt->fetch();
            if (!$target) {
                flash('That user no longer exists.', 'error');
            } else {
                $db->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
                logAudit((int)$admin['id'], 'delete_user', "user #{$userId} ({$target['username']})");
                flash('User deleted.', 'success');
            }
        }
    }

    header('Location: users.php' . (isset($_GET['q']) ? '?q=' . urlencode($_GET['q']) : ''));
    exit;
}

$query = trim($_GET['q'] ?? '');
$roleFilter = $_GET['role'] ?? 'all';

$sql = "SELECT * FROM users WHERE 1=1";
$params = [];
if ($query !== '') {
    $sql .= " AND username LIKE ?";
    $params[] = '%' . $query . '%';
}
if (in_array($roleFilter, ['hero', 'villain', 'civilian', 'admin'], true)) {
    $sql .= " AND role = ?";
    $params[] = $roleFilter;
}
$sql .= " ORDER BY id";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle = 'Manage Users';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <h1>Manage Users</h1>
    <p class="muted">Directly edits the same <code>users</code> table the player system uses.</p>
</div>

<form method="get" class="filter-form">
    <input type="text" name="q" placeholder="Search username..." value="<?= h($query) ?>">
    <select name="role" onchange="this.form.submit()">
        <option value="all" <?= $roleFilter === 'all' ? 'selected' : '' ?>>All roles</option>
        <?php foreach (['hero','villain','civilian','admin'] as $r): ?>
            <option value="<?= $r ?>" <?= $roleFilter === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-secondary">Filter</button>
</form>

<div style="overflow-x:auto;">
<table class="admin-table">
    <thead>
        <tr><th>Username</th><th>Role</th><th>Credits</th><th>Max Energy</th><th>Current Energy</th><th>Joined</th><th></th></tr>
    </thead>
    <tbody>
    <?php foreach ($users as $u): ?>
        <tr>
            <td><?= h($u['username']) ?><?= (int)$u['id'] === (int)$admin['id'] ? ' <span class="muted small">(you)</span>' : '' ?></td>
            <td colspan="3">
                <form method="post" class="inline-form">
                    <input type="hidden" name="form_action" value="update_user">
                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                    <select name="role">
                        <?php foreach (['hero','villain','civilian','admin'] as $r): ?>
                            <option value="<?= $r ?>" <?= $u['role'] === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                        <?php endforeach; ?>
                    </select>
                    💰<input type="number" name="credits" value="<?= (int)$u['credits'] ?>" min="0" style="width:75px">
                    max⚡<input type="number" name="max_energy" value="<?= (int)$u['max_energy'] ?>" min="1" style="width:65px">
                    <button type="submit" class="btn btn-secondary" style="padding:5px 12px;">Save</button>
                </form>
            </td>
            <td><?= (int)$u['current_energy'] ?></td>
            <td class="muted small"><?= h($u['created_at']) ?></td>
            <td>
                <form method="post" onsubmit="return confirm('Delete user <?= h(addslashes($u['username'])) ?> and all their data?');">
                    <input type="hidden" name="form_action" value="delete_user">
                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                    <button type="submit" class="btn btn-danger" <?= (int)$u['id'] === (int)$admin['id'] ? 'disabled' : '' ?>>Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (empty($users)): ?>
        <tr><td colspan="7" class="muted">No users match that search/filter.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
