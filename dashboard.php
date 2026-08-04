<?php
require_once 'config.php';

// Protect this page - only logged in users can see it
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Send the user straight to the dashboard that matches their role
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    header('Location: admin/index.php');
} else {
    header('Location: student/index.php');
}
exit;
