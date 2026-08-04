<?php
require_once 'config.php';

if (isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    header('Location: admin/index.php');
} else {
    header('Location: login.php');
}
exit;
