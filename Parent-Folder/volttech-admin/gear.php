<?php
require_once __DIR__ . '/includes/auth.php';
$admin = requireAdminLogin();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['form_action'] ?? '';

    if ($action === 'add_gear' || $action === 'edit_gear') {
        $name        = trim($_POST['name'] ?? '');
        $category    = $_POST['category'] ?? 'gadget';
        $description = trim($_POST['description'] ?? '');
        $price       = max(0, (int)($_POST['price'] ?? 0));
        $energyCost  = max(0, (int)($_POST['energy_cost'] ?? 0));
        $powerRating = max(0, min(100, (int)($_POST['power_rating'] ?? 0)));

        if ($name === '' || $description === '' || !in_array($category, ['suit', 'dampener', 'gadget'], true)) {
            flash('Please fill in all gear fields correctly.', 'error');
        } elseif ($action === 'add_gear') {
            $db->prepare("INSERT INTO gear (name, category, description, price, energy_cost, power_rating, icon) VALUES (?, ?, ?, ?, ?, ?, '')")
               ->execute([$name, $category, $description, $price, $energyCost, $powerRating]);
            logAudit((int)$admin['id'], 'add_gear', $name);
            flash("Added new gear: {$name}.", 'success');
        } else {
            $gearId = (int)($_POST['gear_id'] ?? 0);
            $db->prepare("UPDATE gear SET name=?, category=?, description=?, price=?, energy_cost=?, power_rating=? WHERE id=?")
               ->execute([$name, $category, $description, $price, $energyCost, $powerRating, $gearId]);
            logAudit((int)$admin['id'], 'edit_gear', "gear #{$gearId} ({$name})");
            flash("Updated {$name}.", 'success');
        }
    }

    if ($action === 'delete_gear') {
        $gearId = (int)($_POST['gear_id'] ?? 0);
        $stmt = $db->prepare("SELECT name FROM gear WHERE id = ?");
        $stmt->execute([$gearId]);
        $target = $stmt->fetch();
        if (!$target) {
            flash('That gear item no longer exists.', 'error');
        } else {
            $db->prepare("DELETE FROM gear WHERE id = ?")->execute([$gearId]);
            logAudit((int)$admin['id'], 'delete_gear', "gear #{$gearId} ({$target['name']})");
            flash('Gear removed (also removed from any player inventories, per the existing foreign key).', 'success');
        }
    }

    header('Location: gear.php');
    exit;
}

$query = trim($_GET['q'] ?? '');
$categoryFilter = $_GET['category'] ?? 'all';
$editId = (int)($_GET['edit'] ?? 0);

$sql = "SELECT * FROM gear WHERE 1=1";
$params = [];
if ($query !== '') {
    $sql .= " AND name LIKE ?";
    $params[] = '%' . $query . '%';
}
if (in_array($categoryFilter, ['suit', 'dampener', 'gadget'], true)) {
    $sql .= " AND category = ?";
    $params[] = $categoryFilter;
}
$sql .= " ORDER BY category, name";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$gearList = $stmt->fetchAll();

$editingGear = null;
if ($editId) {
    $stmt = $db->prepare("SELECT * FROM gear WHERE id = ?");
    $stmt->execute([$editId]);
    $editingGear = $stmt->fetch();
}

$pageTitle = 'Manage Gear';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <h1>Manage Gear</h1>
    <p class="muted">Changes here appear immediately in the player system's marketplace — same <code>gear</code> table.</p>
</div>

<div class="two-col">
    <section class="panel">
        <h2><?= $editingGear ? 'Edit Gear' : 'Add New Gear' ?></h2>
        <form method="post" class="stack">
            <input type="hidden" name="form_action" value="<?= $editingGear ? 'edit_gear' : 'add_gear' ?>">
            <?php if ($editingGear): ?>
                <input type="hidden" name="gear_id" value="<?= (int)$editingGear['id'] ?>">
            <?php endif; ?>
            <label>Name<input type="text" name="name" required maxlength="60" value="<?= h($editingGear['name'] ?? '') ?>"></label>
            <label>Category
                <select name="category">
                    <?php foreach (['suit','dampener','gadget'] as $c): ?>
                        <option value="<?= $c ?>" <?= ($editingGear['category'] ?? '') === $c ? 'selected' : '' ?>><?= ucfirst($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Description<input type="text" name="description" required maxlength="200" value="<?= h($editingGear['description'] ?? '') ?>"></label>
            <label>Price (credits)<input type="number" name="price" min="0" required value="<?= (int)($editingGear['price'] ?? 100) ?>"></label>
            <label>Energy Cost<input type="number" name="energy_cost" min="0" required value="<?= (int)($editingGear['energy_cost'] ?? 10) ?>"></label>
            <label>Power Rating (0-100)<input type="number" name="power_rating" min="0" max="100" required value="<?= (int)($editingGear['power_rating'] ?? 50) ?>"></label>
            <button type="submit" class="btn btn-primary"><?= $editingGear ? 'Save Changes' : 'Add Gear' ?></button>
            <?php if ($editingGear): ?><a href="gear.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
        </form>
    </section>

    <section class="panel">
        <h2>Catalog (<?= count($gearList) ?>)</h2>
        <form method="get" class="filter-form">
            <input type="text" name="q" placeholder="Search gear..." value="<?= h($query) ?>">
            <select name="category" onchange="this.form.submit()">
                <option value="all" <?= $categoryFilter === 'all' ? 'selected' : '' ?>>All categories</option>
                <?php foreach (['suit','dampener','gadget'] as $c): ?>
                    <option value="<?= $c ?>" <?= $categoryFilter === $c ? 'selected' : '' ?>><?= ucfirst($c) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-secondary">Filter</button>
        </form>
        <ul class="plain-list scroll">
            <?php foreach ($gearList as $g): ?>
            <li>
                <span><?= h($g['name']) ?> <span class="tag"><?= h($g['category']) ?></span></span>
                <span class="muted small">💰<?= (int)$g['price'] ?> · ⚡<?= (int)$g['energy_cost'] ?></span>
                <span class="row-actions">
                    <a href="gear.php?edit=<?= (int)$g['id'] ?>" class="btn btn-secondary" style="padding:4px 10px;font-size:0.75rem;">Edit</a>
                    <form method="post" style="display:inline" onsubmit="return confirm('Delete <?= h(addslashes($g['name'])) ?>? This also removes it from every player inventory.');">
                        <input type="hidden" name="form_action" value="delete_gear">
                        <input type="hidden" name="gear_id" value="<?= (int)$g['id'] ?>">
                        <button type="submit" class="btn btn-danger" style="padding:4px 10px;font-size:0.75rem;">Delete</button>
                    </form>
                </span>
            </li>
            <?php endforeach; ?>
            <?php if (empty($gearList)): ?><li class="muted">No gear matches that search/filter.</li><?php endif; ?>
        </ul>
    </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
