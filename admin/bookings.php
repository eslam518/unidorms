<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$page_title = 'Bookings';
$active = 'bookings';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $request_id = (int)$_POST['booking_id'];

    if ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE room_requests SET status='rejected' WHERE id=?");
        $stmt->execute([$request_id]);
        set_flash('success', 'Booking rejected.');
    }

    if ($action === 'approve') {
        // Get the request
        $stmt = $pdo->prepare("SELECT user_id, room_id FROM room_requests WHERE id = ?");
        $stmt->execute([$request_id]);
        $request = $stmt->fetch();

        if ($request) {
            $user_id = $request['user_id'];
            $room_id = $request['room_id'];

            // 1- Approve the request
            $check = $pdo->prepare("
            SELECT id
            FROM room_requests
            WHERE user_id = ?
            AND status='accepted'
            AND id != ?
            LIMIT 1
            ");

            $check->execute([$user_id,$request_id]);

            if($check->fetch()){
                set_flash(
                  'error',
                  'Student already has an active booking.'
                );
                header("Location: bookings.php");
                exit;
            }

            $stmt = $pdo->prepare("UPDATE room_requests SET status='accepted' WHERE id=?");
            $stmt->execute([$request_id]);

            // 2- Make sure this user has a record on the student roster.
            //    If this is their first accepted request, create one automatically
            //    using their account details so nothing has to be entered twice.
            $stmt = $pdo->prepare('SELECT id FROM students WHERE user_id = ?');
            $stmt->execute([$user_id]);
            $student = $stmt->fetch();

            if ($student) {
                $student_id = $student['id'];
                $stmt = $pdo->prepare(
                    "UPDATE students SET room_id=?, check_in_date=CURDATE(), status='active' WHERE id=?"
                );
                $stmt->execute([$room_id, $student_id]);
            } else {
                $userStmt = $pdo->prepare('SELECT full_name, email FROM users WHERE id = ?');
                $userStmt->execute([$user_id]);
                $user = $userStmt->fetch();

                $nameParts = preg_split('/\s+/', trim($user['full_name']), 2);
                $first_name = $nameParts[0] ?? $user['full_name'];
                $last_name = $nameParts[1] ?? '';
                $student_id_number = 'STU' . str_pad((string)$user_id, 4, '0', STR_PAD_LEFT);

                $insert = $pdo->prepare(
                    'INSERT INTO students (user_id, first_name, last_name, student_id_number, email, room_id, check_in_date, status)
                     VALUES (?,?,?,?,?,?,CURDATE(),"active")'
                );
                $insert->execute([$user_id, $first_name, $last_name, $student_id_number, $user['email'], $room_id]);
            }

            // 3- Refresh room occupancy/status
            refresh_room_status($pdo, $room_id);
            set_flash('success', 'Booking approved successfully.');
        }
    }

    header("Location: bookings.php");
    exit;
}

$bookings = $pdo->query(
    "SELECT rr.id, rr.requested_at AS booking_date, rr.status,
        u.full_name, s.student_id_number, r.room_number, bu.name AS building_name
    FROM room_requests rr
    JOIN users u ON u.id = rr.user_id
    LEFT JOIN students s ON s.user_id = rr.user_id
    JOIN rooms r ON r.id = rr.room_id
    JOIN buildings bu ON bu.id = r.building_id
    ORDER BY rr.requested_at DESC"
)->fetchAll();

require __DIR__ . '/includes/layout_top.php';
?>
<div class="topbar">
    <div>
        <div class="eyebrow">Requests</div>
        <h1> <i class="fa-solid fa-calendar-check"></i> Bookings</h1>
    </div>
</div>

<div class="panel">

    <div class="panel-header">
        <h2><i class="fa-solid fa-list-check"></i> Booking Requests (<?= count($bookings) ?>)</h2>
    </div>
    <?php if (!$bookings): ?>
        <div class="empty-state">
            No booking requests yet.
        </div>

    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Student</th>
                <th>Student ID</th>
                <th>Room</th>
                <th>Requested</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($bookings as $b): ?>
            <tr>
                <td>
                    <?= e($b['full_name']) ?>
                </td>
                <td class="mono">
                    <?= e($b['student_id_number'] ?? '—') ?>
                </td>
                <td>
                    <?= e(format_room_label($b['building_name'], $b['room_number'])) ?>
                </td>
                <td>
                    <?= e(date('M j, Y', strtotime($b['booking_date']))) ?>
                </td>
                <td>
                    <span class="badge <?= e($b['status']) ?>">
                        <?= ucfirst($b['status']) ?>
                    </span>
                </td>
                <td class="actions">
                    <?php if ($b['status'] === 'pending'): ?>
                    <form method="post" class="inline">
                      <input type="hidden" name="action" value="approve">
                      <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                      <button class="btn btn-primary btn-small">Approve</button>
                    </form>
                     <form method="post" class="inline">
                       <input type="hidden" name="action" value="reject">
                       <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                       <button class="btn btn-danger btn-small">Reject</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>

        </tbody>
    </table>
    <?php endif; ?>

</div>
<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
