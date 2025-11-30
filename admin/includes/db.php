<?php
// Start the session

// Database connection credentials
$server   = "localhost";
$username = "root";
$password = ""; // Your MySQL password (leave empty for default XAMPP/WAMP)
$dbname   = "school_portal_db"; // The database name you created

// Create connection
$conn = mysqli_connect($server, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// --- INITIAL DATABASE SETUP (Run once to create necessary tables) ---

// 1. Students Table (if not exists)
$sql_students = "
CREATE TABLE IF NOT EXISTS students (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(255) NOT NULL,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    status ENUM('pending', 'active', 'rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $sql_students);


// 2. Admins Table (if not exists)
$sql_admins = "
CREATE TABLE IF NOT EXISTS admins (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    fullname VARCHAR(255) NOT NULL
)";
mysqli_query($conn, $sql_admins);

// Check if default admin exists (Add this only if it wasn't added before)
$check_admin = "SELECT id FROM admins WHERE username = 'admin'";
$admin_result = mysqli_query($conn, $check_admin);
if ($admin_result && mysqli_num_rows($admin_result) == 0) {
    // Default password is 'adminpass'
    $default_password_hash = password_hash('adminpass', PASSWORD_DEFAULT);
    $insert_admin = "INSERT INTO admins (username, password_hash, fullname) VALUES ('admin', '{$default_password_hash}', 'Default Administrator')";
    mysqli_query($conn, $insert_admin);
}

// 3. Courses Table (if not exists)
$sql_courses = "
CREATE TABLE IF NOT EXISTS courses (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $sql_courses);

// 4. Enrollments Table (if not exists)
$sql_enrollments = "
CREATE TABLE IF NOT EXISTS enrollments (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    student_id INT(11) NOT NULL,
    course_id INT(11) NOT NULL,
    enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_enrollment (student_id, course_id),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
)";
mysqli_query($conn, $sql_enrollments);

// 5. Results Table (UPDATED SCHEMA: uses course_id as subject, removes grade, adds term/session)
$sql_results = "
CREATE TABLE IF NOT EXISTS results (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    student_id INT(11) NOT NULL,
    course_id INT(11) NOT NULL, -- Renamed from 'subject' to 'course_id' for FK integrity
    score DECIMAL(5, 2) NOT NULL,
    term VARCHAR(50) NOT NULL,
    session VARCHAR(50) NOT NULL,
    -- Unique constraint ensures only one result per student, subject, term, and session
    UNIQUE KEY unique_result (student_id, course_id, term, session),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
)";
mysqli_query($conn, $sql_results);

// Ensure all database logic uses global $conn when accessing the connection object
// The connection is now established.

?>