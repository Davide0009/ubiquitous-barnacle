<?php

require_once(__DIR__ . '/../auth_check.php');
require_once(__DIR__ . '/../includes/db.php');

// Fetch user details for display in the header/sidebar (assuming 'admins' table)
$admin_name = 'Admin User'; // Default name

// Use the global connection object $conn established in db.php
global $conn; 

if (isset($_SESSION['admin_id'])) {
    $admin_id = $_SESSION['admin_id'];
    
    if (isset($conn)) {
        // Prepare statement is essential for security
        $stmt = $conn->prepare("SELECT fullname FROM admins WHERE id = ?");
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $admin_name = htmlspecialchars($user['fullname']);
        }
        $stmt->close();
    }
}

// Simple path determination for active links
$current_page = basename($_SERVER['PHP_SELF']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - School Portal</title>
    <!-- Load Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom scrollbar style for the sidebar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #4338ca; }
        ::-webkit-scrollbar-thumb { background: #818cf8; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #a5b4fc; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex">
    
    <!-- Sidebar -->
    <aside id="sidebar" class="w-64 bg-gray-800 text-white flex-shrink-0 min-h-screen transition-transform transform -translate-x-full md:translate-x-0 fixed md:relative z-30 flex flex-col overflow-y-auto">
        
        <!-- Header -->
        <div class="p-6">
            <h1 class="text-3xl font-extrabold text-white">Admin Panel</h1>
            <p class="text-sm text-indigo-300 mt-1">Hello, <?php echo $admin_name; ?></p>
        </div>

        <!-- Navigation Links -->
        <nav class="mt-4 flex-1 space-y-2 p-4">
            
            <a href="dashboard.php" class="flex items-center py-3 px-4 rounded-xl transition duration-200 <?php echo $current_page == 'dashboard.php' ? 'bg-indigo-700 text-white shadow-lg font-semibold' : 'text-indigo-200 hover:bg-indigo-600 hover:text-white'; ?>">
                <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l-2-2m2 2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                Dashboard
            </a>
            
            <a href="manage_students.php" class="flex items-center py-3 px-4 rounded-xl transition duration-200 <?php echo $current_page == 'manage_students.php' ? 'bg-indigo-700 text-white shadow-lg font-semibold' : 'text-indigo-200 hover:bg-indigo-600 hover:text-white'; ?>">
                <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20v-2m0 2h-2m2 0h-2M13 15h2m-2-4h2m-2-4h2m-6-2h7a4 4 0 014 4v7a4 4 0 01-4 4h-7a4 4 0 01-4-4V5a4 4 0 014-4z"></path></svg>
                Manage Students
            </a>
            
            <a href="manage_courses.php" class="flex items-center py-3 px-4 rounded-xl transition duration-200 <?php echo $current_page == 'manage_courses.php' ? 'bg-indigo-700 text-white shadow-lg font-semibold' : 'text-indigo-200 hover:bg-indigo-600 hover:text-white'; ?>">
                <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.201 5 7.5 5S4.168 5.477 3 6.253M12 6.253C13.168 5.477 14.8 5 16.5 5s3.332.477 4.5 1.253m-13 0C5.168 5.477 3.5 5 2 5m17 0c-1.668 0-3.332.477-4.5 1.253"></path></path></svg>
                Manage Courses
            </a>
            
            <!-- ADDED: Manage Results Link -->
            <a href="manage_results.php" class="flex items-center py-3 px-4 rounded-xl transition duration-200 <?php echo $current_page == 'manage_results.php' || $current_page == 'add_result.php' ? 'bg-indigo-700 text-white shadow-lg font-semibold' : 'text-indigo-200 hover:bg-indigo-600 hover:text-white'; ?>">
                <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                Manage Results
            </a>

        </nav>
        
        <!-- Logout Link (always at the bottom for visibility) -->
        <div class="mt-auto p-4 border-t border-indigo-700">
             <a href="../logout.php" class="flex items-center py-3 px-4 rounded-xl transition duration-200 text-indigo-200 hover:bg-indigo-600 hover:text-white">
                <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                Logout
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col overflow-hidden">
        
        <!-- Top Bar for Mobile Menu Toggle -->
        <header class="w-full bg-white shadow-md md:hidden flex items-center justify-between p-4 flex-shrink-0">
            <h2 class="text-xl font-semibold text-gray-800">Admin Panel</h2>
            <button id="sidebar-toggle" class="text-gray-600 hover:text-gray-800 focus:outline-none p-1 rounded-md">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
            </button>
        </header>
        
        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-8">
            <!-- JavaScript for Mobile Sidebar Toggle -->
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const sidebar = document.getElementById('sidebar');
                    const toggleButton = document.getElementById('sidebar-toggle');

                    if (toggleButton && sidebar) {
                        toggleButton.addEventListener('click', () => {
                            // Toggle the -translate-x-full class to show/hide the sidebar
                            sidebar.classList.toggle('-translate-x-full');
                        });

                        // Close sidebar on larger screens if it was toggled open on mobile
                        window.addEventListener('resize', () => {
                            if (window.innerWidth >= 768) { // md breakpoint
                                sidebar.classList.remove('-translate-x-full');
                            }
                        });
                    }
                });
            </script>
            <!-- Content of each page goes here -->