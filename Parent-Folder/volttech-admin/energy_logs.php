<?php
require_once __DIR__ . '/includes/auth.php';
$admin = requireAdminLogin();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'adjust_energy') {
    $userId = (int)($_POST['user_id'] ?? 0);
    $amount = (int)($_POST['amount'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');

    if ($userId <= 0 || $amount === 0 || $reason === '') {
        flash('Pick a user, a non-zero amount, and a reason.', 'error');
    } else {
        // Same two-step pattern as the player app's adjustEnergy():
        // update the user's current_energy, then log the change.
        $db->beginTransaction();
        $db->prepare("UPDATE users SET current_energy = MAX(0, MIN(max_energy, current_energy + ?)) WHERE id = ?")
           ->execute([$amount, $userId]);
        $db->prepare("INSERT INTO energy_logs (user_id, change_amount, reason) VALUES (?, ?, ?)")
           ->execute([$userId, $amount, '[Admin] ' . $reason]);
        $db->commit();
        logAudit((int)$admin['id'], 'adjust_energy', "user #{$userId}: {$amount} ({$reason})");
        flash('Energy adjusted.', 'success');
    }
    header('Location: energy_logs.php');
    exit;
}

$query = trim($_GET['q'] ?? '');
$sql = "
    SELECT e.*, u.username
    FROM energy_logs e JOIN users u ON u.id = e.user_id
    WHERE 1=1
";
$params = [];
if ($query !== '') {
    $sql .= " AND u.username LIKE ?";
    $params[] = '%' . $query . '%';
}
$sql .= " ORDER BY e.id DESC LIMIT 100";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

$allUsers = $db->query("SELECT id, username FROM users ORDER BY username")->fetchAll();

$pageTitle = 'Energy Logs';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <h1>Monitor Energy Logs</h1>
    <p class="muted">Reads/writes the same <code>energy_logs</code> table and <code>users.current_energy</code> column the player system uses.</p>
</div>

<div class="two-col">
    <section class="panel">
        <h2>Manual Adjustment</h2>
        <form method="post" class="stack">
            <input type="hidden" name="form_action" value="adjust_energy">
            <label>User
                <select name="user_id" required>
                    <option value="">Select a user...</option>
                    <?php foreach ($allUsers as $u): ?>
                        <option value="<?= (int)$u['id'] ?>"><?= h($u['username']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Amount (positive = gain, negative = use)<input type="number" name="amount" required placeholder="e.g. 20 or -15"></label>
            <label>Reason<input type="text" name="reason" required maxlength="120" placeholder="e.g. Manual correction after bug report"></label>
            <button type="submit" class="btn btn-primary">Apply Adjustment</button>
        </form>
    </section>

    <section class="panel">
        <h2>Recent Log Entries</h2>
        <form method="get" class="filter-form">
            <input type="text" name="q" placeholder="Search by username..." value="<?= h($query) ?>">
            <button type="submit" class="btn btn-secondary">Filter</button>
        </form>
        <ul class="plain-list scroll">
            <?php foreach ($logs as $l): ?>
            <li>
                <span class="<?= $l['change_amount'] >= 0 ? 'gain' : 'loss' ?>"><?= $l['change_amount'] >= 0 ? '+' : '' ?><?= (int)$l['change_amount'] ?>⚡</span>
                <span><?= h($l['username']) ?> — <?= h($l['reason']) ?></span>
                <span class="muted small"><?= h($l['created_at']) ?></span>
            </li>
            <?php endforeach; ?>
            <?php if (empty($logs)): ?><li class="muted">No log entries match that search.</li><?php endif; ?>
        </ul>
    </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
