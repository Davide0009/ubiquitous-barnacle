<?php
// Database connection credentials
$server   = "localhost";
$username = "root"; // Check if this user name is correct
$password = ""; // Check if you set a password for this user
$dbname   = "school_portal_db"; // Ensure this matches your database name exactly

// Create connection
$conn = mysqli_connect($server, $username, $password, $dbname);

// Check connection
if (!$conn) {
    // If connection fails, log the error but DO NOT use die().
    // The rest of the application is now designed to handle a failed/null $conn object.
    error_log("Database connection failed: " . mysqli_connect_error());
}

// NOTE: If the connection fails, $conn will be false. The caller files 
// (dashboard.php, manage_students.php, etc.) will check for this and display
// the FATAL ERROR banner gracefully.
?>