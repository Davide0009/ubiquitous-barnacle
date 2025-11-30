<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and has a student role
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student' || !isset($_SESSION['user_id'])) {
    // If not a student, send them to login
    header('Location: login.php');
    exit();
}

// Include database connection (assuming it's in includes/db.php)
include('includes/db.php');

// Fetch user status to confirm they are still pending
$student_id = $_SESSION['user_id'];
$query = "SELECT status, fullname FROM students WHERE id = '$student_id' LIMIT 1";
$result = mysqli_query($conn, $query);
$user_data = mysqli_fetch_assoc($result);

// If the status is 'active' (meaning they were approved while they had this page open), 
// redirect them to the dashboard. This prevents the loop if they refresh this page after approval.
if ($user_data && $user_data['status'] === 'active') {
    header('Location: student/dashboard.php');
    exit();
}
// If they are rejected or status is missing, force log out.
if (!$user_data || $user_data['status'] === 'rejected' || $user_data['status'] === 'awaiting_otp') {
    session_destroy();
    header('Location: login.php?error=AccountIssue');
    exit();
}
// If they are not 'pending' but still landed here, something is wrong, log them out.
if ($user_data['status'] !== 'pending') {
     session_destroy();
     header('Location: login.php?error=SessionMismatch');
     exit();
}


// --- Display Pending Message ---
$fullname = $user_data['fullname'] ?? 'Student';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Account Pending - Real Sensor</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center font-sans">

    <div class="max-w-md w-full bg-white p-8 rounded-xl shadow-2xl text-center border-t-4 border-yellow-500">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-yellow-500 mb-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
        
        <h1 class="text-3xl font-bold text-gray-800 mb-3">Hello, <?php echo htmlspecialchars($fullname); ?>.</h1>
        
        <p class="text-gray-600 mb-6">
            Thank you for registering! Your account is currently **Awaiting Admin Approval**.
        </p>

        <p class="text-sm text-gray-500 mb-8">
            The administration team will review your application shortly. You will be able to access the Student Dashboard once your status changes to **Active**.
        </p>

        <a href="logout.php" class="inline-block w-full py-3 px-4 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition duration-150 shadow-md">
            Logout
        </a>
    </div>

</body>
</html>