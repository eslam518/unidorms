<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth.php';

require_student_login();

$user_id = $_SESSION['user_id'];

/* Get student id */
$student_stmt = mysqli_prepare($conn, "
  SELECT id 
  FROM students 
  WHERE user_id = ?
  LIMIT 1
");

mysqli_stmt_bind_param($student_stmt, "i", $user_id);
mysqli_stmt_execute($student_stmt);

$student_result = mysqli_stmt_get_result($student_stmt);
$student = mysqli_fetch_assoc($student_result);

$student_id = $student['id'] ?? 0;

/* Get student's accepted rooms */
$rooms_stmt = mysqli_prepare($conn, "
  SELECT 
    rooms.id,
    rooms.room_number,
    buildings.name AS dorm_name
  FROM room_requests
  JOIN rooms ON room_requests.room_id = rooms.id
  JOIN buildings ON rooms.building_id = buildings.id
  WHERE room_requests.user_id = ?
  AND room_requests.status = 'accepted'
  AND room_requests.paid_at IS NOT NULL
  LIMIT 1
");

mysqli_stmt_bind_param($rooms_stmt, "i", $user_id);
mysqli_stmt_execute($rooms_stmt);

$rooms = mysqli_stmt_get_result($rooms_stmt);

/* Submit maintenance request */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $room_id = (int)($_POST['room_id'] ?? 0);
  $title = trim($_POST['title'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $priority = $_POST['priority'] ?? 'medium';

  if ($student_id <= 0) {
    $_SESSION['flash'] = [
      'type' => 'warning',
      'message' => 'Student record not found.'
    ];
  } elseif ($room_id <= 0 || empty($title)) {
    $_SESSION['flash'] = [
      'type' => 'warning',
      'message' => 'Please complete all required fields.'
    ];
  } else {
    // Check that selected room belongs to student
    $check_room = mysqli_prepare($conn, "
      SELECT id 
      FROM room_requests
      WHERE user_id = ?
      AND room_id = ?
      AND status = 'accepted'
      LIMIT 1
    ");

    mysqli_stmt_bind_param($check_room, "ii", $user_id, $room_id);
    mysqli_stmt_execute($check_room);

    $valid_room = mysqli_stmt_get_result($check_room)->num_rows > 0;

    if (!$valid_room) {
      $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Invalid room selection.'
      ];
    } else {
      $insert = mysqli_prepare($conn, "
        INSERT INTO maintenance_requests
        (room_id, student_id, title, description, priority)
        VALUES (?, ?, ?, ?, ?)
      ");

      mysqli_stmt_bind_param(
        $insert,
        "iisss",
        $room_id,
        $student_id,
        $title,
        $description,
        $priority
      );

      mysqli_stmt_execute($insert);

      $_SESSION['flash'] = [
        'type' => 'success',
        'message' => 'Maintenance request submitted successfully.'
      ];
    }
  }

  header("Location: maintenance.php");
  exit;
}

/* Get previous requests */
$requests_stmt = mysqli_prepare($conn, "
    SELECT 
      maintenance_requests.*,
      rooms.room_number,
      buildings.name AS dorm_name
    FROM maintenance_requests
    JOIN rooms ON maintenance_requests.room_id = rooms.id
    JOIN buildings ON rooms.building_id = buildings.id
    WHERE maintenance_requests.student_id = ?
    ORDER BY maintenance_requests.created_at DESC
");

mysqli_stmt_bind_param($requests_stmt, "i", $student_id);
mysqli_stmt_execute($requests_stmt);

$requests = mysqli_stmt_get_result($requests_stmt);
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <title>Maintenance</title>
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
          <p class="page-title">Maintenance</p>
          <p class="page-sub">
            Submit and track your room maintenance requests
          </p>
        </div>
      </div>
      <?php if (!empty($_SESSION['flash'])): ?>
        <?php 
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        ?>
        <div class="flash <?php echo htmlspecialchars($flash['type']); ?>">
          <?php echo htmlspecialchars($flash['message']); ?>
        </div>
      <?php endif; ?>

      <div class="maintenance-card">
        <h2>New Request</h2>
        <form method="POST" class="styled-form maintenance-form">
          <div class="form-group">
            <label>Room</label>
            <?php $student_room = mysqli_fetch_assoc($rooms);?>
              <?php if ($student_room): ?>
              <input type="hidden" name="room_id" value="<?php echo $student_room['id']; ?>">
              <p>Room <?php echo htmlspecialchars($student_room['room_number']); ?>-<?php echo htmlspecialchars($student_room['dorm_name']); ?></p>
              <?php else: ?>
              <p class="empty-state">You don't have an active paid room yet.</p>
              <?php endif; ?>
          </div>

          <div class="form-group">
            <label for="title">Title</label>
            <div class="input-icon-wrapper">
              <input type="text" id="title" name="title" placeholder="Problem title" required>
            </div>
          </div>

          <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="5" placeholder="Describe the problem" class="maintenance-textarea"></textarea>
          </div>

          <div class="form-group">
            <label for="priority">Priority</label>
            <div class="input-icon-wrapper">
              <select id="priority" name="priority" class="maintenance-select">
                <option value="low">Low</option>
                <option value="medium" selected>Medium</option>
                <option value="high">High</option>
              </select>
            </div>
          </div>

          <?php if ($student_room): ?>
          <button type="submit" class="btn-primary apply-btn maintenance-submit-btn">
            <i class="fa-solid fa-paper-plane"></i> Submit Request
          </button>
          <?php endif; ?>
        </form>
      </div>

      <h2 class="maintenance-title">My Requests</h2>
      <?php if(mysqli_num_rows($requests) == 0): ?>
      <p class="empty-state">No maintenance requests yet.</p>
      <?php else: ?>
        <?php while($req = mysqli_fetch_assoc($requests)): ?>
          <div class="maintenance-card request-card">
            <h3><?php echo htmlspecialchars($req['title']); ?></h3>
            <p>Room <?php echo htmlspecialchars($req['room_number']); ?>-<?php echo htmlspecialchars($req['dorm_name']); ?></p>
            <p><?php echo htmlspecialchars($req['description']); ?></p>
            <span class="status-pill <?php echo $req['status']; ?>"><?php echo htmlspecialchars($req['status']); ?></span>
            <span class="priority-<?php echo $req['priority']; ?>"><?php echo htmlspecialchars($req['priority']); ?></span>
          </div>
        <?php endwhile; ?>
      <?php endif; ?>
    </main>
  </body>
</html>