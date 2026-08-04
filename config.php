<?php

$host = "localhost";
$db_user = "root";      // change if your MySQL user is different
$db_pass = "";          // change if your MySQL has a password
$db_name = "unidorms";

$conn = mysqli_connect($host, $db_user, $db_pass, $db_name);

// Check connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Start session on every page that includes this file
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
