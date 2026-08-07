<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$page_title = 'Rooms & Beds';
$active = 'rooms';

// ---------------- Handle actions ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $building_id = (int) $_POST['building_id'];
        $room_number = trim($_POST['room_number']);
        $floor = (int) $_POST['floor'];
        $capacity = max(1, (int) $_POST['capacity']);
        $price_per_term = (float) ($_POST['price_per_term'] ?? 0);
        $room_type = $_POST['room_type'];
        $status = $_POST['status'];
        $image = null;

       if (!empty($_FILES['image']['name'])) {
          $folder = __DIR__ . '/../uploads/rooms/';
          if (!file_exists($folder)) {
              mkdir($folder, 0777, true);
             }
          $image = time() . "_" . $_FILES['image']['name'];
          $path = $folder . $image;
          move_uploaded_file($_FILES['image']['tmp_name'], $path);
          }

        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE rooms SET building_id=?, room_number=?, floor=?, capacity=?, price_per_term=?, room_type=?, status=? WHERE id=?');
            $stmt->execute([$building_id, $room_number, $floor, $capacity, $price_per_term, $room_type, $status, $id]);
            set_flash('success', "Room $room_number updated.");
        } else {
            $stmt = $pdo->prepare('INSERT INTO rooms (building_id, room_number, floor, capacity,price_per_term, room_type, status) VALUES (?,?,?,?,?,?,?)');
            $stmt->execute([$building_id, $room_number, $floor, $capacity,  $price_per_term, $room_type, $status]);
            
            if ($image) {
               $room_id = $pdo->lastInsertId();
               $stmt = $pdo->prepare('UPDATE rooms SET room_image = ? WHERE id = ?');
               $stmt->execute([$image, $room_id]);
            }

            set_flash('success', "Room $room_number added.");
        }
    }

    if ($action === 'delete') {
        $id = (int) $_POST['id'];
        $stmt = $pdo->prepare('DELETE FROM rooms WHERE id = ?');
        $stmt->execute([$id]);
        set_flash('success', 'Room removed.');
    }

    header('Location: rooms.php');
    exit;
}

// ---------------- Data for the page ----------------
$buildings = $pdo->query('SELECT id, name FROM buildings ORDER BY name')->fetchAll();

$rooms = $pdo->query(
    "SELECT r.*, b.name AS building_name,
            (SELECT COUNT(*) FROM students s WHERE s.room_id = r.id AND s.status='active') AS occupied
     FROM rooms r
     JOIN buildings b ON b.id = r.building_id
     ORDER BY b.name, r.floor, r.room_number"
)->fetchAll();

$editRoom = null;
if (!empty($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM rooms WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $editRoom = $stmt->fetch() ?: null;
}

require __DIR__ . '/includes/layout_top.php';
?>

<div class="topbar">
    <div>
        <div class="eyebrow">Facilities</div>
        <h1><i class="fa-solid fa-bed"></i> Rooms &amp; Beds</h1>
    </div>
    <a href="rooms.php?add=1" class="btn btn-brass">+ Add Room</a>
</div>

<?php if (!empty($_GET['add']) || $editRoom): ?>
<div class="panel">
    <div class="panel-header">
        <h2><?= $editRoom ? 'Edit Room ' . e($editRoom['room_number']) : 'Add a Room' ?></h2>
        <a href="rooms.php" class="btn btn-outline btn-small">Cancel</a>
    </div>


    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= $editRoom['id'] ?? 0 ?>">
        <div class="form-grid">
            <div class="field">
                <label>Building</label>
                <select name="building_id" required>
                    <?php foreach ($buildings as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= ($editRoom['building_id'] ?? null) == $b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label>Room number</label>
                <input type="text" name="room_number" value="<?= e($editRoom['room_number'] ?? '') ?>" required>
            </div>
            <div class="field">
                <label>Floor</label>
                <input type="number" name="floor" value="<?= e($editRoom['floor'] ?? 1) ?>" required>
            </div>
            <div class="field">
                <label>Capacity (beds)</label>
                <input type="number" min="1" name="capacity" value="<?= e($editRoom['capacity'] ?? 2) ?>" required>
            </div>
            <div class="field">
                 <label>Price per Term</label>
                <input type="number" step="0.01" min="0" name="price_per_term" value="<?= e($editRoom['price_per_term'] ?? 0) ?>" required>
            </div>
            <div class="field">
                <label>Room type</label>
                <select name="room_type">
                    <?php foreach (['standard','deluxe','single'] as $t): ?>
                        <option value="<?= $t ?>" <?= ($editRoom['room_type'] ?? '') === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
               <label>Room Image</label>
               <input type="file" name="image" accept="image/*">
            </div>
            <div class="field">
                <label>Status</label>
                <select name="status">
                    <?php foreach (['available','full','maintenance'] as $s): ?>
                        <option value="<?= $s ?>" <?= ($editRoom['status'] ?? '') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-footer">
            <button type="submit" class="btn btn-primary">Save Room</button>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="panel">
    <div class="panel-header"><h2><i class="fa-solid fa-door-open"></i> All Rooms (<?= count($rooms) ?>)</h2></div>
    <?php if (!$rooms): ?>
        <div class="empty-state">No rooms yet. Add the first one above.</div>
    <?php else: ?>
    <table>
        <thead>
            <tr><th>Building</th><th>Room</th><th>Floor</th><th>Type</th><th>Occupancy</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($rooms as $r): ?>
            <tr id="room-<?= $r['id'] ?>">
                <td><?= e($r['building_name']) ?></td>
                <td class="mono"><?= e(format_room_label($r['building_name'], $r['room_number'])) ?></td>
                <td><?= (int)$r['floor'] ?></td>
                <td><?= ucfirst(e($r['room_type'])) ?></td>
                <td><?= (int)$r['occupied'] ?> / <?= (int)$r['capacity'] ?> beds</td>
                <td><span class="badge <?= e($r['status']) ?>"><?= ucfirst(e($r['status'])) ?></span></td>
                <td class="actions">
                    <a href="rooms.php?edit=<?= $r['id'] ?>" class="btn btn-outline btn-small">Edit</a>
                    <form class="inline" method="post" onsubmit="return confirm('Remove this room? This cannot be undone.');">
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
