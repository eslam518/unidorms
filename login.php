<?php
require_once 'config.php';

$error = "";

// If already logged in, redirect to appropriate dashboard based on role
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
    if ($_SESSION['user_role'] === 'admin') {
        header('Location: admin/index.php');
    } else {
        header('Location: student/index.php');
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if ($email === '' || $password === '') {
        $error = 'Please enter both email and password.';
    } else {
        $stmt = mysqli_prepare($conn, 'SELECT id, full_name, password, role FROM users WHERE email = ?');
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];

            // Route straight to the dashboard that matches this account's role
            if ($user['role'] === 'admin') {
                header('Location: admin/index.php');
            } else {
                header('Location: student/index.php');
            }
            exit;
        }

        $error = 'Invalid email or password.';
    }
}
?>
<?php include 'includes/header.php'; ?>

<main class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-icon"><i class="fa-solid fa-lock"></i></div>
            <h1 class="auth-title">Welcome Back</h1>
            <p class="auth-subtitle">Sign in to access your UniDorms account</p>
        </div>

        <?php if ($error): ?>
            <div class="message error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="styled-form">
            <div class="form-group">
                <label for="email">University Email</label>
                <div class="input-icon-wrapper">
                    <i class="fa-solid fa-envelope input-icon"></i>
                    <input type="email" id="email" name="email" placeholder="student@univ.edu.eg" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>
            </div>

            <div class="form-group">
                <div class="label-row">
                    <label for="password">Password</label>
                    <a href="forgot_password.php" class="auth-link font-sm">Forgot Password?</a>
                </div>
                <div class="input-icon-wrapper">
                    <i class="fa-solid fa-lock input-icon"></i>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
            </div>

            <button type="submit" class="btn-primary auth-btn">
                <i class="fa-solid fa-right-to-bracket"></i> Sign In
            </button>
        </form>

        <div class="auth-footer">
            <p>Don't have an account? <a href="register.php" class="auth-link bold">Register here</a></p>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>