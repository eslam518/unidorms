<?php
// Admin login now happens through the site-wide login page (same users table,
// role decides where you land). This file just forwards you there.
require_once __DIR__ . '/includes/auth.php';

if (!empty($_SESSION['user_id']) && !empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    header('Location: index.php');
} else {
    header('Location: ../login.php');
}
exit;
