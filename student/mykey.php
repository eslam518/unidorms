<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth.php';

// Only logged-in STUDENTS may access this page
require_student_login();

// Get the student's name
$user_stmt = mysqli_prepare($conn, "SELECT full_name FROM users WHERE id = ?");
mysqli_stmt_bind_param($user_stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($user_stmt);
$user_row = mysqli_stmt_get_result($user_stmt)->fetch_assoc();
$student_name = $user_row ? $user_row['full_name'] : "Guest";
mysqli_stmt_close($user_stmt);

// Get the student's accepted room (this becomes the "key")
// Only show the key if the student has a recorded PAID payment.
$stmt = mysqli_prepare($conn, "
  SELECT DISTINCT rooms.room_number, buildings.name AS dorm_name
  FROM room_requests
  JOIN rooms ON room_requests.room_id = rooms.id
  JOIN buildings ON rooms.building_id = buildings.id
  WHERE room_requests.user_id = ?
  AND room_requests.status = 'accepted'
  AND room_requests.paid_at IS NOT NULL
  ORDER BY room_requests.requested_at DESC
  LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$key = mysqli_stmt_get_result($stmt)->fetch_assoc();
mysqli_stmt_close($stmt);

$has_key = (bool)$key;

if ($has_key) {
    $qr_data = urlencode("Student:" . $student_name . " | Room:" . $key['room_number'] . " | Dorm:" . $key['dorm_name']);
    $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=" . $qr_data;
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<title>My Key</title>
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
      <p class="page-title">My Key</p>
      <p class="page-sub">Appears here after payment is completed and remains available anytime.</p>
    </div>
  </div>

  <?php if (!$has_key): ?>
    <p class="empty-state">You don't have an active key yet. It will appear once your request is accepted and paid.</p>
  <?php else: ?>
    <div class="phone-frame">
      <div class="phone-screen">
        <div class="wallet-card">
          <div class="wallet-top">
          <hr>
            <div class="wallet-status"><i class="fa-solid fa-circle-check"></i> Active</div>
          </div>
          <div class="wallet-room">Room: <?php echo htmlspecialchars($key['room_number']); ?></div>
          <div class="wallet-dorm"><?php echo htmlspecialchars($key['dorm_name']); ?></div>
          <div class="wallet-meta">
            <div>Student: <strong><?php echo htmlspecialchars($student_name); ?></strong></div>
            <div>Valid Until: <strong>End of Semester</strong></div>
          </div>
        </div>
        <br>
        <div class="wallet-qr">
          <img src="<?php echo $qr_url; ?>" alt="QR Code" class="mykey-qr-img"> <br>
          <span>Show this QR code upon entering the dorm.</span>
        </div>
      </div>
    </div>
  <?php endif; ?>
</main>

</body>
</html>