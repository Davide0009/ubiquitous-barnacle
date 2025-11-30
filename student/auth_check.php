<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Check if the user is logged in as a student
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student' || !isset($_SESSION['user_id'])) {
    // If not a student, redirect to the public login page
    header('Location: ../login.php');
    exit();
}

$student_id = $_SESSION['user_id'];

// 2. Fetch student status from the database
$query = "SELECT status FROM students WHERE id = '$student_id' LIMIT 1";
$result = mysqli_query($conn, $query);
$user_data = mysqli_fetch_assoc($result);

if (!$user_data || $user_data['status'] !== 'active') {
    // If account is pending, rejected, or something else, redirect to the pending page or logout
    
    // Check for pending status explicitly (or awaiting_otp, if they somehow get here)
    if ($user_data['status'] === 'pending') {
        header('Location: ../pending.php');
        exit();
    }
    
    // If rejected or status unknown, force logout
    session_destroy();
    header('Location: ../login.php?error=AccessDenied');
    exit();
}

// If the code reaches this point, the user is a verified, active student.
// The primary key 'id' for the student is available via $_SESSION['user_id']
?>