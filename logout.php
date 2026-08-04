<?php
require_once 'config.php';

// Destroy the session to log the user out
session_unset();
session_destroy();

header("Location: login.php");
exit;
?>
