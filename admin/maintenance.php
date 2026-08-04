<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$page_title = 'Maintenance';
$active = 'maintenance';

// ---------------- Handle actions ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $room_id = (int) $_POST['room_id'];
        $student_id = $_POST['student_id'] !== '' ? (int) $_POST['student_id'] : null;
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $priority = $_POST['priority'];
        $status = $_POST['status'];
        $resolved_at = $status === 'resolved' ? date('Y-m-d H:i:s') : null;

        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE maintenance_requests SET room_id=?, student_id=?, title=?, description=?, priority=?, status=?, resolved_at=IF(? IS NOT NULL, ?, resolved_at) WHERE id=?');
            $stmt->execute([$room_id, $student_id, $title, $description, $priority, $status, $resolved_at, $resolved_at, $id]);
            set_flash('success', 'Maintenance request updated.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO maintenance_requests (room_id, student_id, title, description, priority, status, resolved_at) VALUES (?,?,?,?,?,?,?)');
            $stmt->execute([$room_id, $student_id, $title, $description, $priority, $status, $resolved_at]);
            set_flash('success', 'Maintenance request logged.');
        }

        // A room actively under high-priority open work can be flagged as maintenance
        if ($status !== 'resolved') {
            $room = $pdo->prepare('SELECT status FROM rooms WHERE id = ?');
            $room->execute([$room_id]);
            if ($room->fetchColumn() !== 'maintenance' && $priority === 'high') {
                $pdo->prepare("UPDATE rooms SET status = 'maintenance' WHERE id = ?")->execute([$room_id]);
            }
        } else {
            refresh_room_status($pdo, $room_id);
        }
    }

    if ($action === 'delete') {
        $id = (int) $_POST['id'];
        $pdo->prepare('DELETE FROM maintenance_requests WHERE id = ?')->execute([$id]);
        set_flash('success', 'Request removed.');
    }

    header('Location: maintenance.php');
    exit;
}

// ---------------- Data ----------------
$rooms = $pdo->query(
    "SELECT r.id, r.room_number, b.name AS building_name FROM rooms r JOIN buildings b ON b.id=r.building_id ORDER BY b.name, r.room_number"
)->fetchAll();
$students = $pdo->query("SELECT id, first_name, last_name FROM students WHERE status='active' ORDER BY last_name")->fetchAll();

$requests = $pdo->query(
    "SELECT m.*, r.room_number, b.name AS building_name, s.first_name, s.last_name
     FROM maintenance_requests m
     JOIN rooms r ON r.id = m.room_id
     JOIN buildings b ON b.id = r.building_id
     LEFT JOIN students s ON s.id = m.student_id
     ORDER BY FIELD(m.status,'open','in_progress','resolved'), FIELD(m.priority,'high','medium','low'), m.created_at DESC"
)->fetchAll();

$editReq = null;
if (!empty($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM maintenance_requests WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $editReq = $stmt->fetch() ?: null;
}

require __DIR__ . '/includes/layout_top.php';
?>

<div class="topbar">
    <div>
        <div class="eyebrow">Facilities</div>
        <h1> <i class="fa-solid fa-screwdriver-wrench"></i> Maintenance Requests</h1>
    </div>
</div>

<?php if ($editReq): ?>
<div class="panel">
    <div class="panel-header">
        <h2><?= $editReq ? 'Edit Request' : 'Log a Maintenance Request' ?></h2>
        <a href="maintenance.php" class="btn btn-outline btn-small">Cancel</a>
    </div>
    <form method="post">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= $editReq['id'] ?? 0 ?>">
        <div class="form-grid">
            <div class="field">
                <label>Room</label>
                <select name="room_id" required>
                    <?php foreach ($rooms as $r): ?>
                        <option value="<?= $r['id'] ?>" <?= ($editReq['room_id'] ?? null) == $r['id'] ? 'selected' : '' ?>><?= e(format_room_label($r['building_name'], $r['room_number'])) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label>Reported by (optional)</label>
                <select name="student_id">
                    <option value="">— Not specified —</option>
                    <?php foreach ($students as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= ($editReq['student_id'] ?? null) == $s['id'] ? 'selected' : '' ?>><?= e($s['first_name'].' '.$s['last_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field full">
                <label>Title</label>
                <input type="text" name="title" value="<?= e($editReq['title'] ?? '') ?>" required>
            </div>
            <div class="field full">
                <label>Description</label>
                <textarea name="description" rows="3"><?= e($editReq['description'] ?? '') ?></textarea>
            </div>
            <div class="field">
                <label>Priority</label>
                <select name="priority">
                    <?php foreach (['low','medium','high'] as $p): ?>
                        <option value="<?= $p ?>" <?= ($editReq['priority'] ?? 'medium') === $p ? 'selected' : '' ?>><?= ucfirst($p) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label>Status</label>
                <select name="status">
                    <?php foreach (['open','in_progress','resolved'] as $s): ?>
                        <option value="<?= $s ?>" <?= ($editReq['status'] ?? 'open') === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-footer">
            <button type="submit" class="btn btn-primary">Save Request</button>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="panel">
    <div class="panel-header"><h2>All Requests (<?= count($requests) ?>)</h2></div>
    <?php if (!$requests): ?>
        <div class="empty-state">No maintenance requests logged.</div>
    <?php else: ?>
    <table>
        <thead>
            <tr><th>Room</th><th>Title</th><th>Reported by</th><th>Priority</th><th>Status</th><th>Logged</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($requests as $r): ?>
            <tr>
                <td><?= e(format_room_label($r['building_name'], $r['room_number'])) ?></td>
                <td><?= e($r['title']) ?></td>
                <td><?= $r['first_name'] ? e($r['first_name'].' '.$r['last_name']) : '—' ?></td>
                <td><span class="badge <?= e($r['priority']) ?>"><?= ucfirst(e($r['priority'])) ?></span></td>
                <td><span class="badge <?= e($r['status']) ?>"><?= ucfirst(str_replace('_',' ', e($r['status']))) ?></span></td>
                <td><?= e(date('M j, Y', strtotime($r['created_at']))) ?></td>
                <td class="actions">
                    <a href="maintenance.php?edit=<?= $r['id'] ?>" class="btn btn-outline btn-small">Edit</a>
                    <form class="inline" method="post" onsubmit="return confirm('Remove this request?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
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
