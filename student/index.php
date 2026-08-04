<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth.php';

// Only logged-in STUDENTS may browse and apply for rooms
require_student_login();

$activeBooking = false;

$checkBooking = mysqli_prepare($conn, "
  SELECT id
  FROM room_requests
  WHERE user_id = ?
  AND status IN ('pending','accepted')
  LIMIT 1
");

mysqli_stmt_bind_param(
  $checkBooking,
  "i",
  $_SESSION['user_id']
);

mysqli_stmt_execute($checkBooking);

$activeBooking =
mysqli_stmt_get_result($checkBooking)->num_rows > 0;

$per_page = 6;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = $page * $per_page;

$type_filter = isset($_GET['type']) && in_array($_GET['type'], ['single', 'standard', 'deluxe']) ? $_GET['type'] : null;

$where = "WHERE rooms.status = 'available'";
if ($type_filter) {
    $where .= " AND rooms.room_type = '" . mysqli_real_escape_string($conn, $type_filter) . "'";
}

$count_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM rooms $where");
$total_rooms = mysqli_fetch_assoc($count_result)['total'];

$sql = "
SELECT rooms.id, rooms.room_number, rooms.room_type, rooms.price_per_term,
           rooms.room_image AS image_file,
           buildings.name AS dorm_name
    FROM rooms
    JOIN buildings ON rooms.building_id = buildings.id
    $where
    ORDER BY rooms.id DESC
    LIMIT $limit
";
$result = mysqli_query($conn, $sql);

$has_more = $total_rooms > $limit;
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<title>Dashboard</title>
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
      <p class="page-title">Available Rooms</p>
      <p class="page-sub">Choose the room that suits you and apply for it.</p>
    </div>
    <div class="counter-box">
      <div class="counter-icon"><i class="fa-solid fa-door-open"></i></div>
      <div>
        <div id="counter-num" data-total="<?php echo (int)$total_rooms; ?>">0</div>
        <div class="counter-label">Rooms Displayed</div>
      </div>
    </div>
  </div>

  <div class="filters">
    <a class="filter-chip <?php echo !$type_filter ? 'active' : ''; ?>" href="?">All</a>
    <a class="filter-chip <?php echo $type_filter === 'single' ? 'active' : ''; ?>" href="?type=single">Single</a>
    <a class="filter-chip <?php echo $type_filter === 'standard' ? 'active' : ''; ?>" href="?type=standard">Standard</a>
    <a class="filter-chip <?php echo $type_filter === 'deluxe' ? 'active' : ''; ?>" href="?type=deluxe">Deluxe</a>
  </div>

  <div class="rooms-grid" id="rooms-grid">
    <?php while ($room = mysqli_fetch_assoc($result)): ?>
      <a class="room-card" href="room-details.php?id=<?php echo $room['id']; ?>" >
        <div class="room-photo">
          <?php if (!empty($room['image_file'])): ?>
            <img src="../uploads/rooms/<?php echo htmlspecialchars($room['image_file']); ?>">
          <?php endif; ?>
          <span class="type-tag"><?php echo htmlspecialchars(ucfirst($room['room_type'])); ?></span>
        </div>
        <div class="room-body">
          <h3>Room <?php echo htmlspecialchars($room['room_number']); ?></h3>
          <p><?php echo htmlspecialchars($room['dorm_name']); ?></p>
          <div class="room-price">
            <?php echo "EGP " ?>
            <?php echo number_format($room['price_per_term'], 0); ?> <span>/ semester</span>
          </div>
          <?php if (!$activeBooking): ?>
            <span class="apply-btn">
              <i class="fa-solid fa-paper-plane"></i> Book a Room
            </span>
            <?php else: ?>
            <span class="apply-btn">
              Already Booked
            </span>
          <?php endif; ?>
        </div>
      </a>
    <?php endwhile; ?>
  </div>

  <?php if ($has_more): ?>
    <a class="load-more" href="?page=<?php echo $page + 1; ?><?php echo $type_filter ? '&type=' . $type_filter : ''; ?>">
      <i class="fa-solid fa-plus"></i> Show More Rooms
    </a>
  <?php endif; ?>
</main>

<script>
  const counterEl = document.getElementById('counter-num');
  const target = parseInt(counterEl.dataset.total, 10) || 0;

  function animateCounter(to){
    let start = null;
    function step(ts){
      if(!start) start = ts;
      let progress = Math.min((ts - start) / 600, 1);
      counterEl.textContent = Math.round(to * progress);
      if(progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }
  animateCounter(target);
</script>
</body>
</html>