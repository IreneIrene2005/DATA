<?php
// Start the session
session_start();

// Remove all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Prevent browser caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// Redirect back to login page
header("Location: index.php?page=login");
exit;
