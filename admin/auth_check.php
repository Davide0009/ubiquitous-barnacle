<?php
// Start session if not already started (important for checking login status)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in AND if their role is 'admin'
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    // If not admin, redirect to the public login page
    header('Location: ../login.php');
    exit();
}
// If the code reaches this point, the user is a verified admin.
?>