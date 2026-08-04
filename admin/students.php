<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$page_title = 'Students';
$active = 'students';

// ---------------- Handle actions ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $student_id_number = trim($_POST['student_id_number']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $gender = $_POST['gender'];
        $room_id = $_POST['room_id'] !== '' ? (int) $_POST['room_id'] : null;
        $check_in_date = $_POST['check_in_date'] ?: null;
        $status = $_POST['status'];

        $oldRoomId = null;
        if ($id > 0) {
            $prev = $pdo->prepare('SELECT room_id FROM students WHERE id = ?');
            $prev->execute([$id]);
            $oldRoomId = $prev->fetchColumn();

            $stmt = $pdo->prepare('UPDATE students SET first_name=?, last_name=?, student_id_number=?, email=?, phone=?, gender=?, room_id=?, check_in_date=?, status=? WHERE id=?');
            $stmt->execute([$first_name, $last_name, $student_id_number, $email, $phone, $gender, $room_id, $check_in_date, $status, $id]);
            set_flash('success', "$first_name $last_name updated.");
        } else {
            $stmt = $pdo->prepare('INSERT INTO students (first_name, last_name, student_id_number, email, phone, gender, room_id, check_in_date, status) VALUES (?,?,?,?,?,?,?,?,?)');
            $stmt->execute([$first_name, $last_name, $student_id_number, $email, $phone, $gender, $room_id, $check_in_date, $status]);
            set_flash('success', "$first_name $last_name added.");
        }

        if ($oldRoomId) refresh_room_status($pdo, (int) $oldRoomId);
        if ($room_id) refresh_room_status($pdo, (int) $room_id);
    }

    if ($action === 'delete') {
        $id = (int) $_POST['id'];
        $prev = $pdo->prepare('SELECT room_id FROM students WHERE id = ?');
        $prev->execute([$id]);
        $oldRoomId = $prev->fetchColumn();

        $stmt = $pdo->prepare('DELETE FROM students WHERE id = ?');
        $stmt->execute([$id]);
        if ($oldRoomId) refresh_room_status($pdo, (int) $oldRoomId);
        set_flash('success', 'Student record removed.');
    }

    header('Location: students.php');
    exit;
}

// ---------------- Data ----------------
$rooms = $pdo->query(
    "SELECT r.id, r.room_number, b.name AS building_name
     FROM rooms r JOIN buildings b ON b.id = r.building_id
     ORDER BY b.name, r.room_number"
)->fetchAll();

$students = $pdo->query(
    "SELECT s.*, r.room_number, b.name AS building_name
     FROM students s
     LEFT JOIN rooms r ON r.id = s.room_id
     LEFT JOIN buildings b ON b.id = r.building_id
     ORDER BY s.last_name, s.first_name"
)->fetchAll();

$editStudent = null;
if (!empty($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM students WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $editStudent = $stmt->fetch() ?: null;
}

require __DIR__ . '/includes/layout_top.php';
?>

<div class="topbar">
    <div>
        <div class="eyebrow">Residents</div>
        <h1> <i class="fa-solid fa-users"></i> Students</h1>
    </div>
    <a href="students.php?add=1" class="btn btn-brass">+ Add Student</a>
</div>

<?php if (!empty($_GET['add']) || $editStudent): ?>
<div class="panel">
    <div class="panel-header">
        <h2><?= $editStudent ? 'Edit ' . e($editStudent['first_name'] . ' ' . $editStudent['last_name']) : 'Add a Student' ?></h2>
        <a href="students.php" class="btn btn-outline btn-small">Cancel</a>
    </div>
    <form method="post">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= $editStudent['id'] ?? 0 ?>">
        <div class="form-grid">
            <div class="field">
                <label>First name</label>
                <input type="text" name="first_name" value="<?= e($editStudent['first_name'] ?? '') ?>" required>
            </div>
            <div class="field">
                <label>Last name</label>
                <input type="text" name="last_name" value="<?= e($editStudent['last_name'] ?? '') ?>" required>
            </div>
            <div class="field">
                <label>Student ID number</label>
                <input type="text" name="student_id_number" value="<?= e($editStudent['student_id_number'] ?? '') ?>" required>
            </div>
            <div class="field">
                <label>Gender</label>
                <select name="gender">
                    <?php foreach (['female','male','other'] as $g): ?>
                        <option value="<?= $g ?>" <?= ($editStudent['gender'] ?? '') === $g ? 'selected' : '' ?>><?= ucfirst($g) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label>Email</label>
                <input type="email" name="email" value="<?= e($editStudent['email'] ?? '') ?>">
            </div>
            <div class="field">
                <label>Phone</label>
                <input type="text" name="phone" value="<?= e($editStudent['phone'] ?? '') ?>">
            </div>
                    <!--  delete       -->
           <div class="field">
               <label>Room assignment</label>
                <select name="room_id">
                    <option value="">— Unassigned —</option> 
                    <?php foreach ($rooms as $r): ?>
                        <option value="<?= $r['id'] ?>" <?= ($editStudent['room_id'] ?? null) == $r['id'] ? 'selected' : '' ?>>
                            <?= e(format_room_label($r['building_name'], $r['room_number'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select> 
            </div>
            <div class="field">
                <label>Check-in date</label>
                <input type="date" name="check_in_date" value="<?= e($editStudent['check_in_date'] ?? '') ?>">
            </div> 
            <!--          -->
            <div class="field">
                <label>Status</label>
                <select name="status">
                    <?php foreach (['active','checked_out'] as $s): ?>
                        <option value="<?= $s ?>" <?= ($editStudent['status'] ?? '') === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-footer">
            <button type="submit" class="btn btn-primary">Save Student</button>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="panel">
    <div class="panel-header"><h2> <i class="fa-solid fa-user-graduate"></i> All Students (<?= count($students) ?>)</h2></div>
    <?php if (!$students): ?>
        <div class="empty-state">No students yet. Add the first one above.</div>
    <?php else: ?>
    <table>
        <thead>
            <tr><th>Name</th><th>Student ID</th><th>Room</th><th>Contact</th><th>Check-in</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($students as $s): ?>
            <tr>
                <td><?= e($s['first_name'] . ' ' . $s['last_name']) ?></td>
                <td class="mono"><?= e($s['student_id_number']) ?></td>
                <td><?= $s['room_number'] ? e(format_room_label($s['building_name'], $s['room_number'])) : '<span style="color:var(--slate-light)">Unassigned</span>' ?></td>
                <td><?= e($s['email'] ?: '—') ?><br><?= e($s['phone'] ?: '') ?></td>
                <td><?= $s['check_in_date'] ? e(date('M j, Y', strtotime($s['check_in_date']))) : '—' ?></td>
                <td><span class="badge <?= e($s['status']) ?>"><?= ucfirst(str_replace('_',' ', e($s['status']))) ?></span></td>
                <td class="student-actions">
                    <a href="students.php?edit=<?= $s['id'] ?>" class="btn btn-outline btn-small">Edit</a>
                    <form class="inline" method="post" onsubmit="return confirm('Remove this student record? This cannot be undone.');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
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
