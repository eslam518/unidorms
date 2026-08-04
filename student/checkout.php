<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth.php';

require_student_login();

$request_id = isset($_GET['request_id']) ? (int) $_GET['request_id'] : 0;
if ($request_id <= 0) {
    header('Location: mybooking.php');
    exit;
}

// Load the request and ensure it belongs to this user and is accepted
$stmt = mysqli_prepare($conn, "SELECT rr.id, rr.user_id, rr.room_id, rr.status, rr.paid_at, r.room_number, r.price_per_term, b.name AS building_name
    FROM room_requests rr
    JOIN rooms r ON rr.room_id = r.id
    JOIN buildings b ON r.building_id = b.id
    WHERE rr.id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $request_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$request = mysqli_fetch_assoc($res);

if (!$request || $request['user_id'] != ($_SESSION['user_id'] ?? 0) || $request['status'] !== 'accepted') {
    header('Location: mybooking.php');
    exit;
}

// Check if student already has another accepted booking
$check = mysqli_prepare($conn, "
    SELECT id
    FROM room_requests
    WHERE user_id = ?
    AND status = 'accepted'
    AND id != ?
    LIMIT 1
");

mysqli_stmt_bind_param(
    $check,
    "ii",
    $_SESSION['user_id'],
    $request_id
);

mysqli_stmt_execute($check);

if (mysqli_stmt_get_result($check)->num_rows > 0) {
    $_SESSION['flash'] = [
        'type' => 'warning',
        'message' => 'You already have another active booking.'
    ];

    header('Location: mybooking.php');
    exit;
}

mysqli_stmt_close($check);

if (!empty($request['paid_at'])) {
  $_SESSION['flash'] = [
      'type' => 'warning',
      'message' => 'This booking has already been paid.'
  ];

  header('Location: mybooking.php');
  exit;
}

$errors = [];
$success = '';

$defaultAmount = 100.00;
if (!empty($request['price_per_term'])) {
  $defaultAmount = (float)$request['price_per_term'];
}

// Find or create student record
$stuStmt = mysqli_prepare($conn, 'SELECT id FROM students WHERE user_id = ? LIMIT 1');
mysqli_stmt_bind_param($stuStmt, 'i', $_SESSION['user_id']);
mysqli_stmt_execute($stuStmt);
$stuRes = mysqli_stmt_get_result($stuStmt);
$student = mysqli_fetch_assoc($stuRes);
if ($student) {
    $student_id = (int) $student['id'];
} else {
    // Try to create a minimal student record from users table
    $uStmt = mysqli_prepare($conn, 'SELECT full_name, email FROM users WHERE id = ? LIMIT 1');
    mysqli_stmt_bind_param($uStmt, 'i', $_SESSION['user_id']);
    mysqli_stmt_execute($uStmt);
    $uRes = mysqli_stmt_get_result($uStmt);
    $user = mysqli_fetch_assoc($uRes) ?: ['full_name' => 'Student', 'email' => ''];

    $nameParts = preg_split('/\s+/', trim($user['full_name']), 2);
    $first_name = $nameParts[0] ?? $user['full_name'];
    $last_name = $nameParts[1] ?? '';
    $student_id_number = 'STU' . str_pad((string)($_SESSION['user_id'] ?? 0), 4, '0', STR_PAD_LEFT);

    $ins = mysqli_prepare($conn, 'INSERT INTO students (user_id, first_name, last_name, student_id_number, email, status, check_in_date) VALUES (?,?,?,?,?,"active",CURDATE())');
    mysqli_stmt_bind_param($ins, 'issss', $_SESSION['user_id'], $first_name, $last_name, $student_id_number, $user['email']);
    mysqli_stmt_execute($ins);
    $student_id = mysqli_insert_id($conn);
}

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = isset($_POST['amount']) ? (float) $_POST['amount'] : 0.0;
    if ($amount <= 0) {
        $errors[] = 'Please enter a valid amount.';
    }

    if (empty($errors)) {
        $payment_type = 'Booking Fee';
        $due_date = date('Y-m-d');
        $paid_date = date('Y-m-d');
        $status = 'paid';
        $notes = 'Paid via student checkout for request ' . $request_id;

        $checkPaid = mysqli_prepare($conn, "SELECT paid_at FROM room_requests WHERE id = ?");
        mysqli_stmt_bind_param($checkPaid, "i", $request_id);
        mysqli_stmt_execute($checkPaid);
        $paidResult = mysqli_stmt_get_result($checkPaid);
        $paidRow = mysqli_fetch_assoc($paidResult);

        if (!empty($paidRow['paid_at'])) {
            $_SESSION['flash'] = [
                'type' => 'warning',
                'message' => 'This booking has already been paid.'
            ];
            header('Location: mybooking.php');
            exit;
        }

        $pStmt = mysqli_prepare($conn, 'INSERT INTO payments (student_id, amount, payment_type, due_date, paid_date, status, notes) VALUES (?,?,?,?,?,?,?)');
        mysqli_stmt_bind_param($pStmt, 'idsssss', $student_id, $amount, $payment_type, $due_date, $paid_date, $status, $notes);
        mysqli_stmt_execute($pStmt);

        // Mark the associated room request as paid and set flash
        $updateReq = mysqli_prepare($conn, 'UPDATE room_requests SET paid_at = NOW() WHERE id = ?');
        mysqli_stmt_bind_param($updateReq, 'i', $request_id);
        mysqli_stmt_execute($updateReq);

        // Redirect back to bookings page with success (use standard flash shape)
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Payment recorded successfully. Thank you!'];
        header('Location: mybooking.php');
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="UTF-8">
  <title>Checkout</title>
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
        <a href="mybooking.php" class="back-link back-link-inline">
          <i class="fa-solid fa-arrow-left"></i> Back to My Bookings
        </a>
        <p class="page-title">Checkout</p>
        <p class="page-sub">Complete your payment to confirm room reservation.</p>
      </div>
    </div>

    <div class="card checkout-card checkout-card-wrapper">
      <h2 class="checkout-title">
        Pay for Room <?php echo htmlspecialchars($request['room_number']); ?>[cite: 1]
      </h2>
      <p class="checkout-subtitle">
        Building: <strong><?php echo htmlspecialchars($request['building_name']); ?></strong>[cite: 1]
      </p>

      <?php if ($errors): ?>
        <div class="flash warning" style="margin-bottom: 20px;">
          <ul style="margin: 0; padding-left: 20px;">
            <?php foreach ($errors as $e): ?>
              <li><?php echo htmlspecialchars($e); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="post" class="styled-form checkout-form">
        <div class="form-group checkout-form-group">
          <label for="amount">Amount (EGP)</label>
          <input type="number" id="amount" name="amount" step="0.01" min="0" 
                 value="<?php echo htmlspecialchars(number_format($defaultAmount, 2, '.', '')); ?>" required
                 class="checkout-input">
        </div>

        <div class="checkout-actions">
          <button type="submit" class="apply-btn checkout-submit-btn">
            <i class="fa-solid fa-credit-card"></i> Confirm Payment
          </button>
          <a href="mybooking.php" class="checkout-cancel-link">
            Cancel
          </a>
        </div>
      </form>
    </div>
  </main>
</body>
</html>