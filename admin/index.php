<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$page_title = 'Dashboard';
$active = 'dashboard';

// ---- Stats ----
$totalRooms = (int) $pdo->query('SELECT COUNT(*) FROM rooms')->fetchColumn();
$totalCapacity = (int) $pdo->query('SELECT COALESCE(SUM(capacity),0) FROM rooms')->fetchColumn();
$occupiedBeds = (int) $pdo->query("SELECT COUNT(*) FROM students WHERE status = 'active' AND room_id IS NOT NULL")->fetchColumn();
$vacancy = $totalCapacity > 0 ? round((($totalCapacity - $occupiedBeds) / $totalCapacity) * 100) : 0;
$openMaintenance = (int) $pdo->query("SELECT COUNT(*) FROM maintenance_requests WHERE status != 'resolved'")->fetchColumn();
$duePayments = (int) $pdo->query("SELECT COUNT(*) FROM payments WHERE status IN ('pending','overdue')")->fetchColumn();


// ---- Recent bookings (student room requests) ----
$recentBookings = $pdo->query("SELECT rr.status, rr.requested_at AS booking_date,
    u.full_name, r.room_number, bu.name AS building_name
    FROM room_requests rr
    JOIN users u ON u.id = rr.user_id
    JOIN rooms r ON r.id = rr.room_id
    JOIN buildings bu ON bu.id = r.building_id
    ORDER BY rr.requested_at DESC LIMIT 5"
)->fetchAll();


// ---- Occupancy board data ----
$buildings = $pdo->query('SELECT id, name FROM buildings ORDER BY name')->fetchAll();
$roomsStmt = $pdo->prepare(
    "SELECT r.id, r.room_number, r.floor, r.capacity, r.status,
            (SELECT COUNT(*) FROM students s WHERE s.room_id = r.id AND s.status = 'active') AS occupied
     FROM rooms r WHERE r.building_id = ? ORDER BY r.floor, r.room_number"
);

require __DIR__ . '/includes/layout_top.php';
?>

<div class="topbar">
    <div>
        <div class="eyebrow">University Dormitory Management</div>
        <h1>Welcome back👋</h1>
        <span><?= e(current_admin_name()) ?></span>
    </div>
    <div class="who"><?= date('l, F j, Y') ?></div>
</div>

<div class="stats-row">
    <div class="stat-card">
        <div class="stat-icon">
           <i class="fa-solid fa-bed"></i>
        </div>
        <div class="value"><?= $totalRooms ?></div>
        <div class="label">Total rooms</div>
    </div>
    <div class="stat-card accent-moss">
         <div class="stat-icon">
             <i class="fa-solid fa-users"></i>
         </div>
        <div class="value"><?= $occupiedBeds ?> / <?= $totalCapacity ?></div>
        <div class="label">Beds occupied</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fa-solid fa-chart-pie"></i>
        </div>
        <div class="value"><?= $vacancy ?>%</div>
        <div class="label">Vacancy rate</div>
    </div>
    <div class="stat-card <?= $openMaintenance > 0 ? 'accent-rust' : '' ?>">
        <div class="stat-icon">
            <i class="fa-solid fa-screwdriver-wrench"></i>
        </div>
        <div class="value"><?= $openMaintenance ?></div>
        <div class="label">Open maintenance requests</div>
    </div>
    <div class="stat-card <?= $duePayments > 0 ? 'accent-amber' : '' ?>">
        <div class="stat-icon">
           <i class="fa-solid fa-money-check-dollar"></i>
        </div>
        <div class="value"><?= $duePayments ?></div>
        <div class="label">Payments due</div>
    </div>
   
</div>
                   <!--  Start  Quick Actions               -->

 <section class="quick-action">
    <h2><i class="fa-solid fa-bolt"></i>Quick Actions</h2>

    <div class="quick-grid">
        <a href="students.php?add=1" class="quick-card">
            <i class="fa-solid fa-user-plus"></i>
            <span>Add Student</span>
        </a>
        <a href="rooms.php?add=1"class="quick-card">
           <i class="fa-solid fa-bed"></i>
            <span>Add Room</span>
        </a>
        <a href="bookings.php" class="quick-card">
            <i class="fa-solid fa-clipboard-list"></i>
            <span>Booking Requests</span>
        </a>
        <a href="maintenance.php" class="quick-card">
            <i class="fa-solid fa-screwdriver-wrench"></i>
            <span>Maintenance Requests</span>
        </a>

    </div>
 </section>                         

               <!--      End Quick Actions               -->

              <!--       Start Occupancy Board        -->
<div class="board">
    <h2>Occupancy Board</h2>
    <div class="sub">Every room, at a glance.</div>

    <?php foreach ($buildings as $b): ?>
        <?php
        $roomsStmt->execute([$b['id']]);
        $rooms = $roomsStmt->fetchAll();
        if (!$rooms) continue;
        ?>
        <div class="board-building">
            <div class="name"><?= e($b['name']) ?></div>
            <div class="room-grid">
                <?php foreach ($rooms as $r): ?>
                    <a href="rooms.php?highlight=<?= $r['id'] ?>" class="room-tile <?= e($r['status']) ?>" title="<?= e(format_room_label($b['name'], $r['room_number'])) ?> — <?= e($r['status']) ?>">
                        <?= e(format_room_label($b['name'], $r['room_number'])) ?>
                        <span class="occ"><?= (int)$r['occupied'] ?>/<?= (int)$r['capacity'] ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="legend">
        <span><span class="dot available"></span>Available</span>
        <span><span class="dot full"></span>Full</span>
        <span><span class="dot maintenance"></span>Under maintenance</span>
    </div>
</div>



<!-- Start Recent Bookings -->
<div class="panel recent-bookings">
    <div class="panel-header">
        <h2><i class="fa-solid fa-calendar-check"></i> Recent Bookings</h2>
        <a href="bookings.php" class="view-all"> View All
        <i class="fa-solid fa-arrow-right"></i></a>
    </div>
    <?php if(!$recentBookings): ?>
        <div class="empty-state">
            No booking requests yet.
        </div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Student</th>
                <th>Room</th>
                <th>Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($recentBookings as $b): ?>
            <tr>
                <td>
                    <?= e($b['full_name']) ?>
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
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<!-- End Recent Bookings -->

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>

