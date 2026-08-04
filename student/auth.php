<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_student_login(): void
{
    // Must be logged in AND have the student role
    if (empty($_SESSION['user_id']) || empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student') {
        header('Location: ../login.php');
        exit;
    }
}

function current_student_name(): string
{
    return $_SESSION['user_name'] ?? 'Student';
}
