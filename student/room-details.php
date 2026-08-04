<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth.php';

// Only logged-in STUDENTS may access this page
require_student_login();

$room_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($room_id <= 0) {
    die("Invalid room number.");
}

$applied = false;
$already_requested = false;
$has_active_booking = false;

// ================= When the student clicks "Apply for room" =================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply'])) {
  // Ensure the user record exists (avoid FK violation if session user_id is stale)
  $userCheck = mysqli_prepare($conn, "SELECT id FROM users WHERE id = ? LIMIT 1");
  mysqli_stmt_bind_param($userCheck, 'i', $_SESSION['user_id']);
  mysqli_stmt_execute($userCheck);
  $userRes = mysqli_stmt_get_result($userCheck);
  if (!$userRes || mysqli_num_rows($userRes) === 0) {
    $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Account record not found. Please log out and log in again or register.'];
    header('Location: ../login.php');
    exit;
  }

  // Check if student already has an active booking
  $check = mysqli_prepare($conn, "
      SELECT id 
      FROM room_requests 
      WHERE user_id = ?
      AND status IN ('pending','accepted')
      LIMIT 1
  ");

  mysqli_stmt_bind_param($check, "i", $_SESSION['user_id']);
  mysqli_stmt_execute($check);

  if (mysqli_stmt_get_result($check)->num_rows > 0) {
      $has_active_booking = true;
  } else {
      $insert = mysqli_prepare($conn, "
          INSERT INTO room_requests (user_id, room_id, status)
          VALUES (?, ?, 'pending')
      ");

      mysqli_stmt_bind_param($insert, "ii", $_SESSION['user_id'], $room_id);
      mysqli_stmt_execute($insert);
      mysqli_stmt_close($insert);
      $applied = true;
  }

  mysqli_stmt_close($check);
}

// ================= Fetch room data =================
$stmt = mysqli_prepare($conn, "
    SELECT rooms.id, rooms.room_number, rooms.floor, rooms.room_type, rooms.capacity,
           rooms.occupied, rooms.price_per_term, rooms.status,
           rooms.room_image AS image_file,
           buildings.name AS dorm_name, buildings.address AS dorm_location
    FROM rooms
    JOIN buildings ON rooms.building_id = buildings.id
    WHERE rooms.id = ?
");
mysqli_stmt_bind_param($stmt, "i", $room_id);
mysqli_stmt_execute($stmt);
$room = mysqli_stmt_get_result($stmt)->fetch_assoc();
mysqli_stmt_close($stmt);

if (!$room) {
    die("This room is not available.");
}

// ================= QR code data =================
$qr_data = urlencode("Room:" . $room['room_number'] . " | Dorm:" . $room['dorm_name'] . " | ID:" . $room['id']);
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=" . $qr_data;
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<title>Room Details</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
<link rel="stylesheet" href="style.css">
</head>
<body>

<?php require __DIR__ . '/includes/sidebar.php'; ?>

<main>
  <a class="back-link" href="index.php">
    <i class="fa-solid fa-arrow-left"></i> Back to Available Rooms
  </a>

  <div class="card">
    <?php if ($applied): ?>
    <p class="request_success">
    Your request has been successfully submitted. You can track its status from the "My Bookings" page.
    </p>
    <?php elseif ($has_active_booking): ?>
    <p class="request_applay">
    You already have an active booking request.
    </p>
    <?php endif; ?>

    <div class="room-details-grid">
      <div>
        <div class="room-photo">
          <?php if (!empty($room['image_file'])): ?>
            <img src="../uploads/rooms/<?php echo htmlspecialchars($room['image_file']); ?>" alt="">
          <?php endif; ?>
          <span class="type-tag"><?php echo htmlspecialchars(ucfirst($room['room_type'])); ?></span>
        </div>

        <h2 class="room">Room <?php echo htmlspecialchars($room['room_number']); ?></h2>
        <p class="room_number">
          <?php echo htmlspecialchars($room['dorm_name']); ?> · <?php echo htmlspecialchars($room['dorm_location']); ?>
        </p>

        <table class="table_info">
          <tr><th>Floor</th><td><?php echo (int)$room['floor']; ?></td></tr>
          <tr><th>Capacity</th><td><?php echo (int)$room['capacity']; ?> Students</td></tr>
          <tr><th>Currently Occupied</th><td><?php echo (int)$room['occupied']; ?></td></tr>
          <tr><th>Price</th><td><?php echo number_format($room['price_per_term'], 0); ?> EGP / Term</td></tr>
          <tr><th>Status</th><td><?php echo htmlspecialchars(ucfirst($room['status'])); ?></td></tr>
        </table>

        <?php if ($room['status'] === 'available' && !$already_requested && !$applied): ?>
          <form method="POST">
            <button type="submit" name="apply" class="apply-btn room-details-apply-btn">
              <i class="fa-solid fa-paper-plane"></i> Apply for Room
            </button>
          </form>
        <?php endif; ?>
      </div>

      <div class="qr">
        <p class="header_Qr">Room QR Code</p>
        <img src="<?php echo $qr_url; ?>" alt="QR code" class="qr-img">
        <p class="header_Qr">Scan the code to view room details</p>
      </div>
    </div>

  </div>
</main>

</body>
</html>