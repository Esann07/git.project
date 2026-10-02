<?php
require_once __DIR__ . '/includes/auth.php';
$user = requireLogin();
$db = getDB();

// Handle purchase (unchanged business logic)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_gear_id'])) {
    $gearId = (int)$_POST['buy_gear_id'];
    $stmt = $db->prepare("SELECT * FROM gear WHERE id = ?");
    $stmt->execute([$gearId]);
    $item = $stmt->fetch();

    $owned = $db->prepare("SELECT id FROM inventory WHERE user_id = ? AND gear_id = ?");
    $owned->execute([$user['id'], $gearId]);

    if (!$item) {
        flash('That item does not exist.', 'error');
    } elseif ($owned->fetch()) {
        flash('You already own that item.', 'error');
    } elseif ($user['credits'] < $item['price']) {
        flash("Not enough credits for {$item['name']}.", 'error');
    } else {
        $db->beginTransaction();
        $db->prepare("UPDATE users SET credits = credits - ? WHERE id = ?")->execute([$item['price'], $user['id']]);
        $db->prepare("INSERT INTO inventory (user_id, gear_id) VALUES (?, ?)")->execute([$user['id'], $gearId]);
        $db->commit();
        flash("Purchased {$item['name']} for {$item['price']} credits.", 'success');
    }
    $redirectParams = array_filter([
        'category' => ($_GET['category'] ?? 'all') !== 'all' ? $_GET['category'] : null,
        'sort'     => ($_GET['sort'] ?? 'default') !== 'default' ? $_GET['sort'] : null,
    ]);
    header('Location: marketplace.php' . ($redirectParams ? '?' . http_build_query($redirectParams) : ''));
    exit;
}

$category = $_GET['category'] ?? 'all';
$sort = $_GET['sort'] ?? 'default';

$orderBy = match ($sort) {
    'price_asc'  => 'price ASC',
    'price_desc' => 'price DESC',
    'power_desc' => 'power_rating DESC',
    default      => 'category, price',
};

$sql = "SELECT * FROM gear";
$params = [];
if (in_array($category, ['suit', 'dampener', 'gadget'], true)) {
    $sql .= " WHERE category = ?";
    $params[] = $category;
}
$sql .= " ORDER BY {$orderBy}";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

// Full unfiltered catalog — badges are computed against the whole shop,
// not just the current filtered/sorted view, so they stay stable.
$allItems = $db->query("SELECT * FROM gear")->fetchAll();

$ownedStmt = $db->prepare("SELECT gear_id FROM inventory WHERE user_id = ?");
$ownedStmt->execute([$user['id']]);
$ownedIds = array_column($ownedStmt->fetchAll(), 'gear_id');

// Real, data-driven badges: highest power rating, best price-per-power, lowest drain.
$topPowerItem = null;
$bestValueItem = null;
$lowDrainItem = null;
foreach ($allItems as $it) {
    if (!$topPowerItem || $it['power_rating'] > $topPowerItem['power_rating']) {
        $topPowerItem = $it;
    }
    $ratio = $it['power_rating'] > 0 ? $it['price'] / $it['power_rating'] : PHP_FLOAT_MAX;
    $bestRatio = $bestValueItem ? ($bestValueItem['price'] / max(1, $bestValueItem['power_rating'])) : PHP_FLOAT_MAX;
    if (!$bestValueItem || $ratio < $bestRatio) {
        $bestValueItem = $it;
    }
    if (!$lowDrainItem || $it['energy_cost'] < $lowDrainItem['energy_cost']) {
        $lowDrainItem = $it;
    }
}

$spotlight = ($topPowerItem && !in_array($topPowerItem['id'], $ownedIds, true)) ? $topPowerItem : null;

$categoryCounts = ['suit' => 0, 'dampener' => 0, 'gadget' => 0];
foreach ($allItems as $it) {
    $categoryCounts[$it['category']] = ($categoryCounts[$it['category']] ?? 0) + 1;
}

$pageTitle = 'Marketplace';
require __DIR__ . '/includes/header.php';

function gearBadge(array $item, $topPowerItem, $bestValueItem, $lowDrainItem): ?array {
    if ($topPowerItem && $item['id'] === $topPowerItem['id']) {
        return ['label' => 'Top Power', 'class' => 'badge-power', 'icon' => 'icon-star'];
    }
    if ($bestValueItem && $item['id'] === $bestValueItem['id']) {
        return ['label' => 'Best Value', 'class' => 'badge-value', 'icon' => 'icon-coin'];
    }
    if ($lowDrainItem && $item['id'] === $lowDrainItem['id']) {
        return ['label' => 'Low Drain', 'class' => 'badge-drain', 'icon' => 'icon-battery'];
    }
    return null;
}
?>

<div class="shop-hero">
    <div class="shop-hero-text">
        <h1><?= icon('icon-store', 'icon icon-lg') ?> Gear Marketplace</h1>
        <p class="muted">Suits, dampeners, and gadgets — equip yourself for whatever's next.</p>
    </div>
    <div class="shop-hero-stats">
        <div class="shop-stat">
            <span class="shop-stat-value"><?= count($allItems) ?></span>
            <span class="shop-stat-label">Items in Stock</span>
        </div>
        <div class="shop-stat">
            <span class="shop-stat-value"><?= (int)$user['credits'] ?></span>
            <span class="shop-stat-label">Your Credits</span>
        </div>
        <div class="shop-stat">
            <span class="shop-stat-value"><?= count($ownedIds) ?></span>
            <span class="shop-stat-label">In Your Collection</span>
        </div>
    </div>
</div>

<?php if ($spotlight): $badge = gearBadge($spotlight, $topPowerItem, $bestValueItem, $lowDrainItem); ?>
<div class="spotlight">
    <div class="spotlight-icon card-icon-<?= h($spotlight['category']) ?>">
        <?= icon(categoryIcon($spotlight['category']), 'icon icon-xl') ?>
    </div>
    <div class="spotlight-body">
        <div class="spotlight-eyebrow">Featured Gear</div>
        <h2><?= h($spotlight['name']) ?></h2>
        <p class="muted"><?= h($spotlight['description']) ?></p>
        <div class="card-stats">
            <span><?= icon('icon-battery', 'icon icon-xs') ?> <?= (int)$spotlight['energy_cost'] ?> drain</span>
            <span><?= icon('icon-star', 'icon icon-xs') ?> <?= (int)$spotlight['power_rating'] ?> power</span>
        </div>
    </div>
    <div class="spotlight-buy">
        <span class="price price-lg"><?= icon('icon-coin', 'icon icon-sm') ?> <?= (int)$spotlight['price'] ?></span>
        <form method="post">
            <input type="hidden" name="buy_gear_id" value="<?= (int)$spotlight['id'] ?>">
            <button type="submit" class="btn btn-primary" <?= $user['credits'] < $spotlight['price'] ? 'disabled' : '' ?>>Buy Featured Item</button>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="shop-controls">
    <div class="filter-bar">
        <a href="marketplace.php?<?= http_build_query(array_filter(['sort' => $sort !== 'default' ? $sort : null])) ?>" class="chip <?= $category === 'all' ? 'chip-active' : '' ?>">All</a>
        <a href="marketplace.php?<?= http_build_query(array_filter(['category' => 'suit', 'sort' => $sort !== 'default' ? $sort : null])) ?>" class="chip <?= $category === 'suit' ? 'chip-active' : '' ?>">Suits (<?= $categoryCounts['suit'] ?>)</a>
        <a href="marketplace.php?<?= http_build_query(array_filter(['category' => 'dampener', 'sort' => $sort !== 'default' ? $sort : null])) ?>" class="chip <?= $category === 'dampener' ? 'chip-active' : '' ?>">Dampeners (<?= $categoryCounts['dampener'] ?>)</a>
        <a href="marketplace.php?<?= http_build_query(array_filter(['category' => 'gadget', 'sort' => $sort !== 'default' ? $sort : null])) ?>" class="chip <?= $category === 'gadget' ? 'chip-active' : '' ?>">Gadgets (<?= $categoryCounts['gadget'] ?>)</a>
    </div>
    <form method="get" class="sort-form">
        <?php if ($category !== 'all'): ?><input type="hidden" name="category" value="<?= h($category) ?>"><?php endif; ?>
        <label class="sort-label">
            Sort
            <select name="sort" onchange="this.form.submit()">
                <option value="default" <?= $sort === 'default' ? 'selected' : '' ?>>Default</option>
                <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                <option value="power_desc" <?= $sort === 'power_desc' ? 'selected' : '' ?>>Power: Highest First</option>
            </select>
        </label>
    </form>
</div>

<div class="grid">
    <?php foreach ($items as $item): $isOwned = in_array($item['id'], $ownedIds); $badge = gearBadge($item, $topPowerItem, $bestValueItem, $lowDrainItem); ?>
    <div class="card">
        <?php if ($badge): ?>
        <div class="card-badge <?= $badge['class'] ?>"><?= icon($badge['icon'], 'icon icon-xs') ?> <?= h($badge['label']) ?></div>
        <?php endif; ?>
        <div class="card-icon card-icon-<?= h($item['category']) ?>"><?= icon(categoryIcon($item['category']), 'icon icon-lg') ?></div>
        <h3><?= h($item['name']) ?></h3>
        <span class="tag tag-<?= h($item['category']) ?>"><?= h($item['category']) ?></span>
        <p class="card-desc"><?= h($item['description']) ?></p>
        <div class="card-stats">
            <span><?= icon('icon-battery', 'icon icon-xs') ?> <?= (int)$item['energy_cost'] ?> drain</span>
            <span><?= icon('icon-star', 'icon icon-xs') ?> <?= (int)$item['power_rating'] ?> power</span>
        </div>
        <div class="card-footer">
            <span class="price"><?= icon('icon-coin', 'icon icon-xs') ?> <?= (int)$item['price'] ?></span>
            <?php if ($isOwned): ?>
                <button class="btn btn-owned" disabled>Owned</button>
            <?php else: ?>
                <form method="post">
                    <input type="hidden" name="buy_gear_id" value="<?= (int)$item['id'] ?>">
                    <button type="submit" class="btn btn-primary" <?= $user['credits'] < $item['price'] ? 'disabled' : '' ?>>Buy</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
