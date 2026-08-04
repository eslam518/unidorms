<?php include 'includes/header.php'; ?>

<?php
$error = "";
$success = "";
$token = isset($_GET['token']) ? $_GET['token'] : (isset($_POST['token']) ? $_POST['token'] : "");

// Check if token exists in database
$email = "";
if ($token != "") {
    $stmt = mysqli_prepare($conn, "SELECT email FROM password_resets WHERE token = ? ORDER BY id DESC LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    if ($row) {
        $email = $row['email'];
    } else {
        $error = "Invalid or expired reset link.";
    }
} else {
    $error = "No reset token provided.";
}

// Handle new password submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $email != "") {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password == "" || $confirm_password == "") {
        $error = "Please fill in all fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt2 = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE email = ?");
        mysqli_stmt_bind_param($stmt2, "ss", $hashed_password, $email);

        if (mysqli_stmt_execute($stmt2)) {
            // Delete used token so it cannot be reused
            $stmt3 = mysqli_prepare($conn, "DELETE FROM password_resets WHERE token = ?");
            mysqli_stmt_bind_param($stmt3, "s", $token);
            mysqli_stmt_execute($stmt3);

            $success = "Your password has been updated. You can now <a href='login.php' class='auth-link bold'>Login</a>.";
        } else {
            $error = "Something went wrong. Please try again.";
        }
    }
}
?>

<main class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-icon"><i class="fa-solid fa-lock-open"></i></div>
            <h1 class="auth-title">Reset Password</h1>
            <p class="auth-subtitle">Enter your new secure password below</p>
        </div>

        <?php if ($error): ?>
            <div class="message error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="message success">
                <i class="fa-solid fa-circle-check"></i>
                <span><?php echo $success; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($email != "" && !$success): ?>
        <form method="POST" action="reset_password.php" class="styled-form">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

            <div class="form-group">
                <label for="password">New Password</label>
                <div class="input-icon-wrapper">
                    <i class="fa-solid fa-lock input-icon"></i>
                    <input type="password" id="password" name="password" placeholder="At least 6 characters" required>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <div class="input-icon-wrapper">
                    <i class="fa-solid fa-shield-halved input-icon"></i>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter your new password" required>
                </div>
            </div>

            <button type="submit" class="btn-primary auth-btn">
                <i class="fa-solid fa-key"></i> Reset Password
            </button>
        </form>
        <?php endif; ?>

        <div class="auth-footer">
            <p>Remembered your password? <a href="login.php" class="auth-link bold">Back to Login</a></p>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>