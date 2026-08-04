<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth.php';

// Only logged-in STUDENTS may access this page
require_student_login();

$stmt = mysqli_prepare($conn, "
    SELECT room_requests.id, room_requests.status,
      room_requests.paid_at,
           rooms.room_number, rooms.room_type,
           rooms.room_image AS image_file,
           buildings.name AS dorm_name
    FROM room_requests
    JOIN rooms ON room_requests.room_id = rooms.id
    JOIN buildings ON rooms.building_id = buildings.id
    WHERE room_requests.user_id = ?
    ORDER BY room_requests.requested_at DESC
");

mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$requests = mysqli_stmt_get_result($stmt);
$has_requests = $requests->num_rows > 0;
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<title>My Bookings</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
<link rel="stylesheet" href="style.css">
</head>
<body>
  <?php require __DIR__ . '/includes/sidebar.php'; ?>
  <main>
    <div class="page-head">
      <div>
        <p class="page-title">My Bookings</p>
        <p class="page-sub">Track the status of your submitted requests.</p>
      </div>
    </div>

    <?php if (!empty($_SESSION['flash'])): ?>
      <?php $flash = $_SESSION['flash']; unset($_SESSION['flash']); ?>
      <div class="flash <?php echo htmlspecialchars($flash['type'] ?? ''); ?>"><?php echo htmlspecialchars($flash['message'] ?? ''); ?></div>
    <?php endif; ?>

    <?php if (!$has_requests): ?>
      <p class="empty-state">You don't have any booking requests yet. Go to <a href="index.php" class="mybook-link">Available Rooms</a> and choose a room.</p>
    <?php endif; ?>

    <?php while ($req = mysqli_fetch_assoc($requests)): ?>
      <div class="room-card request-row">
        <div class="room-photo room-photo--sm">
          <?php if (!empty($req['image_file'])): ?>
            <img src="../uploads/rooms/<?php echo htmlspecialchars($req['image_file']); ?>" alt="">
          <?php endif; ?>
        </div>
        <div class="room-body" >
          <h3>Room <?php echo htmlspecialchars($req['room_number']); ?> - <?php echo htmlspecialchars(ucfirst($req['room_type'])); ?></h3>
          <p><?php echo htmlspecialchars($req['dorm_name']); ?></p>
          <?php if ($req['status'] === 'accepted'): ?>
            <span class="status-pill accepted"><i class="fa-solid fa-circle-check"></i> Accepted</span>
          <?php elseif ($req['status'] === 'rejected'): ?>
            <span class="status-pill rejected"><i class="fa-solid fa-circle-xmark"></i> Rejected</span>
          <?php else: ?>
            <span class="status-pill pending"><i class="fa-solid fa-clock"></i> Under Review</span>
          <?php endif; ?>
        </div>
        <?php if ($req['status'] === 'accepted' && empty($req['paid_at'])): ?>
          <a class="checkout-btn" href="checkout.php?request_id=<?php echo $req['id']; ?>">
            <i class="fa-solid fa-credit-card"></i> Pay Now
          </a>
        <?php elseif ($req['status'] === 'accepted' && !empty($req['paid_at'])): ?>
          <span class="status-pill accepted">
            <i class="fa-solid fa-circle-check"></i> Paid
          </span>
        <?php endif; ?>
      </div>
    <?php endwhile; ?>
  </main>
</body>
</html>