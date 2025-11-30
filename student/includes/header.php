<?php
// Use the security check first
include('../includes/db.php'); // Need DB connection to check status

include('auth_check.php'); 
// Database connection is included within auth_check.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Student Dashboard - Real Sensor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/styles.css">
</head>
<body class="bg-gray-100 font-sans leading-normal tracking-normal">

    <nav class="bg-blue-800 p-4 shadow-xl sticky top-0 z-50">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <div class="text-white font-bold text-xl">Student Portal</div>
            <div class="flex items-center space-x-6">
                <a href="dashboard.php" class="text-gray-300 hover:text-white font-medium">Dashboard</a>
                <a href="my_courses.php" class="text-gray-300 hover:text-white font-medium">My Courses</a>
                <a href="profile.php" class="text-gray-300 hover:text-white font-medium">Profile</a>
                <a href="../logout.php" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-1 px-3 rounded-md transition duration-150">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container mx-auto mt-8 p-4">