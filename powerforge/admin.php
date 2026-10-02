<?php
require_once __DIR__ . '/includes/auth.php';
$user = requireAdmin();
$db = getDB();

// ---- POST actions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['form_action'] ?? '';

    if ($action === 'add_gear') {
        $name = trim($_POST['name'] ?? '');
        $category = $_POST['category'] ?? 'gadget';
        $description = trim($_POST['description'] ?? '');
        $price = max(0, (int)($_POST['price'] ?? 0));
        $energyCost = max(0, (int)($_POST['energy_cost'] ?? 0));
        $powerRating = max(0, min(100, (int)($_POST['power_rating'] ?? 0)));

        if ($name === '' || $description === '' || !in_array($category, ['suit', 'dampener', 'gadget'], true)) {
            flash('Please fill in all gear fields correctly.', 'error');
        } else {
            $db->prepare("INSERT INTO gear (name, category, description, price, energy_cost, power_rating) VALUES (?, ?, ?, ?, ?, ?)")
               ->execute([$name, $category, $description, $price, $energyCost, $powerRating]);
            flash("Added new gear: {$name}.", 'success');
        }
    }

    if ($action === 'delete_gear') {
        $gearId = (int)($_POST['gear_id'] ?? 0);
        $db->prepare("DELETE FROM gear WHERE id = ?")->execute([$gearId]);
        flash('Gear removed from the catalog.', 'success');
    }

    if ($action === 'update_user') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $credits = max(0, (int)($_POST['credits'] ?? 0));
        $maxEnergy = max(1, (int)($_POST['max_energy'] ?? 1));
        $role = $_POST['role'] ?? 'civilian';

        if (!in_array($role, ['hero', 'villain', 'civilian', 'admin'], true)) {
            flash('Invalid role.', 'error');
        } else {
            $stmt = $db->prepare("UPDATE users SET credits = ?, max_energy = ?, current_energy = MIN(current_energy, ?), role = ? WHERE id = ?");
            $stmt->execute([$credits, $maxEnergy, $maxEnergy, $role, $userId]);
            flash('User updated.', 'success');
        }
    }

    if ($action === 'delete_user') {
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId === (int)$user['id']) {
            flash("You can't delete your own account while logged in as it.", 'error');
        } else {
            $db->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
            flash('User removed.', 'success');
        }
    }

    header('Location: admin.php');
    exit;
}

$gearList = $db->query("SELECT * FROM gear ORDER BY category, name")->fetchAll();
$userList = $db->query("SELECT * FROM users ORDER BY created_at")->fetchAll();

$pageTitle = 'Admin';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <h1>Admin Panel</h1>
    <p class="muted">Manage the gear catalog and user accounts. Restricted to <code>role = 'admin'</code>.</p>
</div>

<div class="two-col">
    <section class="panel">
        <h2>Add New Gear</h2>
        <form method="post" class="stack">
            <input type="hidden" name="form_action" value="add_gear">
            <label>Name<input type="text" name="name" required maxlength="60"></label>
            <label>Category
                <select name="category">
                    <option value="suit">Suit</option>
                    <option value="dampener">Dampener</option>
                    <option value="gadget">Gadget</option>
                </select>
            </label>
            <label>Description<input type="text" name="description" required maxlength="200"></label>
            <label>Price (credits)<input type="number" name="price" min="0" required value="100"></label>
            <label>Energy Cost<input type="number" name="energy_cost" min="0" required value="10"></label>
            <label>Power Rating (0-100)<input type="number" name="power_rating" min="0" max="100" required value="50"></label>
            <button type="submit" class="btn btn-primary"><?= icon('icon-plus','icon icon-xs') ?> Add Gear</button>
        </form>
    </section>

    <section class="panel">
        <h2>Gear Catalog (<?= count($gearList) ?>)</h2>
        <ul class="log-list scroll">
            <?php foreach ($gearList as $g): ?>
            <li>
                <span class="mini-cat-icon card-icon-<?= h($g['category']) ?>"><?= icon(categoryIcon($g['category']), 'icon icon-xs') ?></span>
                <span style="flex:1"><?= h($g['name']) ?> <span class="tag tag-<?= h($g['category']) ?>"><?= h($g['category']) ?></span></span>
                <span class="muted small"><?= icon('icon-coin','icon icon-xs') ?><?= (int)$g['price'] ?> · <?= icon('icon-battery','icon icon-xs') ?><?= (int)$g['energy_cost'] ?></span>
                <form method="post" onsubmit="return confirm('Delete <?= h(addslashes($g['name'])) ?>? This removes it from every inventory too.');">
                    <input type="hidden" name="form_action" value="delete_gear">
                    <input type="hidden" name="gear_id" value="<?= (int)$g['id'] ?>">
                    <button type="submit" class="btn btn-sell" style="padding:4px 10px;font-size:0.75rem;"><?= icon('icon-trash','icon icon-xs') ?> Delete</button>
                </form>
            </li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>

<section class="panel" style="margin-top:20px;">
    <h2>Users (<?= count($userList) ?>)</h2>
    <div style="overflow-x:auto;">
    <table class="admin-table">
        <thead>
            <tr><th>Username</th><th>Role</th><th>Credits</th><th>Max Energy</th><th>Current Energy</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($userList as $u): ?>
            <tr>
                <td><?= h($u['username']) ?><?= (int)$u['id'] === (int)$user['id'] ? ' <span class="muted small">(you)</span>' : '' ?></td>
                <td>
                    <form method="post" class="inline-form">
                        <input type="hidden" name="form_action" value="update_user">
                        <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                        <input type="hidden" name="credits" value="<?= (int)$u['credits'] ?>">
                        <input type="hidden" name="max_energy" value="<?= (int)$u['max_energy'] ?>">
                        <select name="role" onchange="this.form.submit()">
                            <?php foreach (['hero','villain','civilian','admin'] as $r): ?>
                                <option value="<?= $r ?>" <?= $u['role'] === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </td>
                <td colspan="3">
                    <form method="post" class="inline-form">
                        <input type="hidden" name="form_action" value="update_user">
                        <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                        <input type="hidden" name="role" value="<?= h($u['role']) ?>">
                        <?= icon('icon-coin','icon icon-xs') ?><input type="number" name="credits" value="<?= (int)$u['credits'] ?>" min="0" style="width:80px">
                        <?= icon('icon-battery','icon icon-xs') ?>max<input type="number" name="max_energy" value="<?= (int)$u['max_energy'] ?>" min="1" style="width:70px">
                        <button type="submit" class="btn btn-secondary" style="margin:0;padding:6px 12px;">Save</button>
                    </form>
                </td>
                <td>
                    <form method="post" onsubmit="return confirm('Delete user <?= h(addslashes($u['username'])) ?> and all their data?');">
                        <input type="hidden" name="form_action" value="delete_user">
                        <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                        <button type="submit" class="btn btn-sell" <?= (int)$u['id'] === (int)$user['id'] ? 'disabled' : '' ?>><?= icon('icon-trash','icon icon-xs') ?> Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
