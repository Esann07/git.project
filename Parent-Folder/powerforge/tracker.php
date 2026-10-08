<?php
require_once __DIR__ . '/includes/auth.php';
$user = requireLogin();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (int)($_POST['amount'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    $action = $_POST['action'] ?? 'use';

    if ($amount <= 0 || $reason === '') {
        flash('Enter a positive amount and a reason.', 'error');
    } else {
        $signed = $action === 'gain' ? $amount : -$amount;
        adjustEnergy($user['id'], $signed, $reason);
        flash('Energy log recorded.', 'success');
    }
    header('Location: tracker.php');
    exit;
}

$user = currentUser(); // refresh after possible update
$logsStmt = $db->prepare("SELECT * FROM energy_logs WHERE user_id = ? ORDER BY id DESC LIMIT 30");
$logsStmt->execute([$user['id']]);
$logs = $logsStmt->fetchAll();

$pageTitle = 'Energy Tracker';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <h1>Bio-Energy Tracker</h1>
    <p class="muted">Log training sessions, combat drains, and recharges to keep your reserves honest.</p>
</div>

<div class="stat-card wide">
    <div class="stat-label">Current Reserve</div>
    <div class="stat-value"><?= (int)$user['current_energy'] ?> / <?= (int)$user['max_energy'] ?> <?= icon('icon-battery', 'icon icon-md') ?></div>
    <div class="bar"><div class="bar-fill" style="width: <?= min(100, round($user['current_energy'] / max(1,$user['max_energy']) * 100)) ?>%"></div></div>
</div>

<div class="two-col">
    <section class="panel">
        <h2>Log Activity</h2>
        <form method="post" class="stack">
            <label>Type
                <select name="action">
                    <option value="use">Use energy (training, combat, gear)</option>
                    <option value="gain">Gain energy (rest, recharge cell)</option>
                </select>
            </label>
            <label>Amount
                <input type="number" name="amount" min="1" max="1000" required>
            </label>
            <label>Reason
                <input type="text" name="reason" maxlength="120" placeholder="e.g. Sparring session, Overload Choker use" required>
            </label>
            <button type="submit" class="btn btn-primary">Log It</button>
        </form>
    </section>

    <section class="panel">
        <h2>History</h2>
        <?php if (empty($logs)): ?>
            <p class="muted">No entries yet.</p>
        <?php else: ?>
            <ul class="log-list scroll">
                <?php foreach ($logs as $l): ?>
                <li>
                    <span class="<?= $l['change_amount'] >= 0 ? 'gain' : 'loss' ?>">
                        <?= $l['change_amount'] >= 0 ? '+' : '' ?><?= (int)$l['change_amount'] ?>
                    </span>
                    <span><?= h($l['reason']) ?></span>
                    <span class="muted small"><?= h($l['created_at']) ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
