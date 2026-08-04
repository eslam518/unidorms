<?php
// Make sure session + db connection are available on every page
require_once __DIR__ . '/../config.php';
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniDorms</title>
    
    <!-- Design System Fonts & Font Awesome 6.7.2 -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    
    <!-- Unified Stylesheet -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="main-header">
    <div class="navbar-container">
        <a class="brand-logo" href="index.php">
            <i class="fa-solid fa-hotel"></i> UniDorms
        </a>
        <nav class="navbar-nav">
            <ul>
                <li><a href="index.php">Home</a></li>
                <?php
                // Show public informational links for logged-in students when viewing any student page
                $showPublicLinks = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'student' && strpos($_SERVER['PHP_SELF'], '/student/') !== false;
                if ($showPublicLinks):
                ?>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="contact.php">Contact Us</a></li>
                    <li><a href="faq.php">FAQ</a></li>
                    <li><a href="terms.php">Terms & Conditions</a></li>
                    <li><a href="privacy.php">Privacy Policy</a></li>
                <?php endif; ?>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                        <li><a href="admin/index.php" class="nav-btn admin-btn"><i class="fa-solid fa-user-shield"></i> Admin Panel</a></li>
                    <?php else: ?>
                        <li><a href="student/index.php" class="nav-btn dashboard-btn"><i class="fa-solid fa-chart-line"></i> Dashboard</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php" class="logout-link"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php" class="nav-link-login"><i class="fa-solid fa-right-to-bracket"></i> Login</a></li>
                    <li><a href="register.php" class="nav-btn register-btn"><i class="fa-solid fa-user-plus"></i> Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

<div class="main-wrapper">