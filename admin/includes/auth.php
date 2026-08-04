<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login(): void
{
    // Must be logged in AND have the admin role (set by the site-wide login.php)
    if (empty($_SESSION['user_id']) || empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        header('Location: ../login.php');
        exit;
    }
}

function current_admin_name(): string
{
    return $_SESSION['user_name'] ?? 'Admin';
}
