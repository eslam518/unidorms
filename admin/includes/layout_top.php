<?php
// Expects $page_title and $active to be set before including this file.
$active = $active ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($page_title ?? 'Dashboard') ?> Uidorms</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Source+Serif+4:wght@500;600;700&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css?v=2">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>
<body>
<!--Side bar   -->
<div class="app">
    <aside class="sidebar">
        <div class="brand">Uidorms</div>
        <nav>
            <a href="index.php" class="<?= $active === 'dashboard' ? 'active' : '' ?>">
                 <i class="fa-solid fa-chart-line"></i>
                Dashboard</a>
            <a href="rooms.php" class="<?= $active === 'rooms' ? 'active' : '' ?>">
                 <i class="fa-solid fa-bed"></i>
                Rooms </a>
            <a href="students.php" class="<?= $active === 'students' ? 'active' : '' ?>">
                  <i class="fa-solid fa-users"></i>
                Students</a>
                <a href="bookings.php" class="<?= $active === 'bookings' ? 'active' : '' ?>">
                 <i class="fa-solid fa-calendar-check"></i>Bookings </a>
            <a href="maintenance.php" class="<?= $active === 'maintenance' ? 'active' : '' ?>">
                 <i class="fa-solid fa-screwdriver-wrench"></i>
                Maintenance</a>
            <a href="payments.php" class="<?= $active === 'payments' ? 'active' : '' ?>">
                  <i class="fa-solid fa-credit-card"></i>
                Payments</a>
        
        </nav>
        <div class="logout"><a href="logout.php"> 
            <i class="fa-solid fa-right-from-bracket"></i>
            Sign out</a></div>
    </aside>
    <main class="main">
        <?php $flash = get_flash(); if ($flash): ?>
            <div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>
