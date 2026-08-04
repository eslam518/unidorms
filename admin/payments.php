<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$page_title = 'Payments';
$active = 'payments';

// ---------------- Handle actions ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $student_id = (int) $_POST['student_id'];
        $amount = (float) $_POST['amount'];
        $payment_type = trim($_POST['payment_type']);
        $due_date = $_POST['due_date'];
        $paid_date = $_POST['paid_date'] ?: null;
        $status = $_POST['status'];
        $notes = trim($_POST['notes']);

        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE payments SET student_id=?, amount=?, payment_type=?, due_date=?, paid_date=?, status=?, notes=? WHERE id=?');
            $stmt->execute([$student_id, $amount, $payment_type, $due_date, $paid_date, $status, $notes, $id]);
            set_flash('success', 'Payment record updated.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO payments (student_id, amount, payment_type, due_date, paid_date, status, notes) VALUES (?,?,?,?,?,?,?)');
            $stmt->execute([$student_id, $amount, $payment_type, $due_date, $paid_date, $status, $notes]);
            set_flash('success', 'Payment record added.');
        }
    }

    if ($action === 'delete') {
        $id = (int) $_POST['id'];
        $pdo->prepare('DELETE FROM payments WHERE id = ?')->execute([$id]);
        set_flash('success', 'Payment record removed.');
    }

    header('Location: payments.php');
    exit;
}

// ---------------- Data ----------------
$students = $pdo->query("SELECT id, first_name, last_name FROM students ORDER BY last_name")->fetchAll();

$payments = $pdo->query(
    "SELECT p.*, s.first_name, s.last_name
     FROM payments p JOIN students s ON s.id = p.student_id
     ORDER BY FIELD(p.status,'overdue','pending','paid'), p.due_date"
)->fetchAll();

$totalDue = 0;
foreach ($payments as $p) {
    if ($p['status'] !== 'paid') $totalDue += $p['amount'];
}

$editPay = null;
if (!empty($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM payments WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $editPay = $stmt->fetch() ?: null;
}

require __DIR__ . '/includes/layout_top.php';
?>

<div class="topbar">
    <div>
        <div class="eyebrow">Finance</div>
        <h1><i class="fa-solid fa-money-check-dollar"></i> Payments</h1>
    </div>
    
</div>

<div class="stats-row">
    <div class="stat-card accent-amber">
        <div class="value">$<?= number_format($totalDue, 2) ?></div>
        <div class="label">Outstanding balance</div>
    </div>
</div>


<div class="panel">
    <div class="panel-header"><h2>All Payments (<?= count($payments) ?>)</h2></div>
    <?php if (!$payments): ?>
        <div class="empty-state">No payment records yet.</div>
    <?php else: ?>
    <table>
        <thead>
            <tr><th>Student</th><th>Type</th><th>Amount</th><th>Due</th><th>Paid</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($payments as $p): ?>
            <tr>
                <td><?= e($p['first_name'].' '.$p['last_name']) ?></td>
                <td><?= e($p['payment_type']) ?></td>
                <td class="mono">$<?= number_format($p['amount'], 2) ?></td>
                <td><?= e(date('M j, Y', strtotime($p['due_date']))) ?></td>
                <td><?= $p['paid_date'] ? e(date('M j, Y', strtotime($p['paid_date']))) : '—' ?></td>
                <td><span class="badge <?= e($p['status']) ?>"><?= ucfirst(e($p['status'])) ?></span></td>
                <td class="actions">
                    <a href="payments.php?edit=<?= $p['id'] ?>" class="btn btn-outline btn-small">Edit</a>
                    <form class="inline" method="post" onsubmit="return confirm('Remove this payment record?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-small">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
