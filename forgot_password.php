<?php include 'includes/header.php'; ?>

<?php
$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);

    if ($email == "") {
        $error = "Please enter your email.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            // Generate a simple random token
            $token = bin2hex(random_bytes(20));

            $stmt2 = mysqli_prepare($conn, "INSERT INTO password_resets (email, token) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt2, "ss", $email, $token);
            mysqli_stmt_execute($stmt2);

            // NOTE: In a real project you would email this link to the user.
            // Since this is a simple project (no mail server), we display it directly.
            $reset_link = "reset_password.php?token=" . $token;
            $success = "A reset link has been generated. Click here to reset your password: 
                        <a href='$reset_link' class='auth-link bold'>Reset Password</a>";
        } else {
            $error = "No account found with this email.";
        }
    }
}
?>

<main class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-icon"><i class="fa-solid fa-key"></i></div>
            <h1 class="auth-title">Forgot Password?</h1>
            <p class="auth-subtitle">Enter your university email to receive a password reset link</p>
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

        <form method="POST" action="forgot_password.php" class="styled-form">
            <div class="form-group">
                <label for="email">University Email</label>
                <div class="input-icon-wrapper">
                    <i class="fa-solid fa-envelope input-icon"></i>
                    <input type="email" id="email" name="email" placeholder="student@univ.edu.eg" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>
            </div>

            <button type="submit" class="btn-primary auth-btn">
                <i class="fa-solid fa-paper-plane"></i> Send Reset Link
            </button>
        </form>

        <div class="auth-footer">
            <p>Remembered your password? <a href="login.php" class="auth-link bold">Back to Login</a></p>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>