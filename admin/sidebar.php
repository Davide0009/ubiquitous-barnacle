<?php
// Determine the current page for active highlighting
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
if ($current_dir === 'admin') {
    $current_page = $current_dir . '/' . $current_page;
}
$is_dashboard_active = ($current_page === 'admin/dashboard.php');
$is_students_active = ($current_page === 'admin/manage_students.php');
$is_courses_active = ($current_page === 'admin/manage_courses.php'); // New link check

function isActive($check, $current) {
    return $check ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-600';
}
?>

<div class="flex flex-col h-full bg-indigo-800 text-white">
    <div class="px-6 py-4 border-b border-indigo-700 text-center">
        <h1 class="text-2xl font-extrabold tracking-wider">Admin Portal</h1>
    </div>

    <nav class="flex-grow p-4 space-y-2">
        
        <!-- Dashboard Link -->
        <a href="dashboard.php" 
           class="flex items-center px-4 py-2 rounded-lg transition duration-150 <?= isActive($is_dashboard_active, $current_page); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            Dashboard
        </a>

        <!-- Manage Students Link -->
        <a href="manage_students.php" 
           class="flex items-center px-4 py-2 rounded-lg transition duration-150 <?= isActive($is_students_active, $current_page); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20h-2m2 0h-2m0-9a4 4 0 100-8 4 4 0 000 8zM12 12c-1.573 0-3-.615-4-1.666m9 3.666V7a3 3 0 00-3-3H7a3 3 0 00-3 3v2" />
            </svg>
            Manage Students
        </a>

        <!-- Manage Courses Link (NEW) -->
        <a href="manage_courses.php" 
           class="flex items-center px-4 py-2 rounded-lg transition duration-150 <?= isActive($is_courses_active, $current_page); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.5l.5-.5m-.5.5v5m0 0l-4.5 4.5m4.5-4.5l4.5 4.5M19 4H5a2 2 0 00-2 2v12a2 2 0 002 2h14a2 2 0 002-2V6a2 2 0 00-2-2z" />
            </svg>
            Manage Courses
        </a>

    </nav>

    <div class="p-4 border-t border-indigo-700">
        <a href="../logout.php" class="flex items-center justify-center w-full px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 transition duration-150">
            Logout
        </a>
    </div>
</div>