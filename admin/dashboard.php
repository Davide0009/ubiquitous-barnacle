<?php
include('includes/header.php'); 
// header.php already includes auth_check.php and ../includes/db.php (using $conn object)

// FIX: Declare $conn as global to ensure it's accessible in this script's scope
global $conn;

// Function to safely fetch a single value using prepared statements
function fetchSingleValue($query, $types = null, $params = null) {
    // We must use the global $conn here if the function doesn't take it as an argument
    global $conn; 
    
    // Check if $conn is valid before proceeding
    if (!$conn || $conn->connect_error) {
        error_log("Database connection failed in fetchSingleValue: " . ($conn ? $conn->connect_error : "Connection object is null"));
        return 0; // Return 0 if the connection is bad
    }

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("Prepare statement failed: " . $conn->error);
        return 0;
    }

    if ($types && $params) {
        // Use call_user_func_array for binding parameters
        $bind_params = array();
        $bind_params[] = $types;
        foreach ($params as &$param) {
            $bind_params[] = &$param;
        }
        call_user_func_array(array($stmt, 'bind_param'), $bind_params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_row();
    $stmt->close();
    return $row ? $row[0] : 0;
}

// --- 1. Dashboard Metrics (Prepared Statements used for security) ---

// Only attempt to fetch data if the connection is valid
if ($conn) {
    // Total Students
    $total_students = fetchSingleValue("SELECT COUNT(id) FROM students");

    // Active Students
    $active_students = fetchSingleValue("SELECT COUNT(id) FROM students WHERE status = ?", 's', ['active']);

    // Pending Approvals
    $pending_students = fetchSingleValue("SELECT COUNT(id) FROM students WHERE status = ?", 's', ['pending']);

    // Total Courses
    $total_courses = fetchSingleValue("SELECT COUNT(id) FROM courses");
} else {
    // Set defaults if connection failed
    $total_students = 0;
    $active_students = 0;
    $pending_students = 0;
    $total_courses = 0;
}


// --- 2. Recent Students (Fixing 'full_name' to 'fullname' and using Prepared Statements) ---
$recent_students_query = "SELECT id, username, fullname, created_at 
                          FROM students 
                          ORDER BY created_at DESC 
                          LIMIT 5";

$recent_students_stmt = false; // Initialize to false

if ($conn) {
    $recent_students_stmt = $conn->prepare($recent_students_query);
    if ($recent_students_stmt) {
        $recent_students_stmt->execute();
        $recent_students_result = $recent_students_stmt->get_result();
    } else {
        error_log("Prepare statement failed for recent students: " . $conn->error);
        $recent_students_result = false;
    }
} else {
    $recent_students_result = false;
}

?>

<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Admin Dashboard</h1>

    <?php 
    // Check if $conn failed from db.php (which is included via header.php)
    if (!$conn): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <strong class="font-bold">FATAL ERROR!</strong>
            <span class="block sm:inline">Database connection failed. Dashboard data is unavailable. Please check your configuration in `includes/db.php`.</span>
        </div>
    <?php endif; ?>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <!-- Card 1: Total Students -->
        <div class="bg-white overflow-hidden shadow-lg rounded-lg border-l-4 border-indigo-500 p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-indigo-100 p-3 rounded-full">
                    <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.65-.183-1.278-.516-1.857M7 20v-2a3 3 0 015.356-1.857M7 20h2m4 0h4m-4 0a3 3 0 01-5.356-1.857M12 20h2"></path></svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dt class="text-sm font-medium text-gray-500 truncate">Total Students</dt>
                    <dd class="text-3xl font-extrabold text-gray-900">
                        <?php echo $total_students; ?>
                    </dd>
                </div>
            </div>
        </div>
        
        <!-- Card 2: Active Students -->
        <div class="bg-white overflow-hidden shadow-lg rounded-lg border-l-4 border-green-500 p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-green-100 p-3 rounded-full">
                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.001 12.001 0 002.92 14.618l3.125 3.125m0 0l-1.414 1.414m1.414-1.414l-1.414 1.414m3.536 3.536l-1.414-1.414m1.414 1.414l-1.414-1.414M16.92 7.08a12.001 12.001 0 00-.001-10.999h-.001"></path></svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dt class="text-sm font-medium text-gray-500 truncate">Active Students</dt>
                    <dd class="text-3xl font-extrabold text-gray-900">
                        <?php echo $active_students; ?>
                    </dd>
                </div>
            </div>
        </div>

        <!-- Card 3: Pending Approvals -->
        <div class="bg-white overflow-hidden shadow-lg rounded-lg border-l-4 border-yellow-500 p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-yellow-100 p-3 rounded-full">
                    <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dt class="text-sm font-medium text-gray-500 truncate">Pending Approvals</dt>
                    <dd class="text-3xl font-extrabold text-gray-900">
                        <?php echo $pending_students; ?>
                    </dd>
                </div>
            </div>
        </div>

        <!-- Card 4: Total Courses -->
        <div class="bg-white overflow-hidden shadow-lg rounded-lg border-l-4 border-blue-500 p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-blue-100 p-3 rounded-full">
                    <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13M3.463 9.897l5.695 2.126M18.537 9.897l-5.695 2.126M12 2v3M4.218 5.782l1.414 1.414M18.232 5.768l-1.414 1.414M21 12h-3M3 12h3M16.95 18.95l1.414-1.414M4.21 18.95l-1.414-1.414"></path></svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dt class="text-sm font-medium text-gray-500 truncate">Total Courses</dt>
                    <dd class="text-3xl font-extrabold text-gray-900">
                        <?php echo $total_courses; ?>
                    </dd>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        
        <!-- Recent Students Panel (Spanning 2 columns) -->
        <div class="bg-white shadow-lg rounded-lg p-6 md:col-span-2"> <!-- Make this span 2 columns if the other panel is removed -->
            <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">🆕 Recent Student Signups</h2>
            <ul class="divide-y divide-gray-200">
                <?php if ($recent_students_result && $recent_students_result->num_rows > 0): ?>
                    <?php while ($student = $recent_students_result->fetch_assoc()): ?>
                        <li class="py-3 flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($student['fullname']); ?></p>
                                <p class="text-xs text-gray-500">@<?php echo htmlspecialchars($student['username']); ?></p>
                            </div>
                            <span class="text-xs text-gray-500"><?php echo date('M d, H:i', strtotime($student['created_at'])); ?></span>
                        </li>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-gray-500 text-sm">No recent student signups or failed to load data.</p>
                <?php endif; ?>
            </ul>
            <?php 
            // Close the statement if it was successfully prepared
            if (isset($recent_students_stmt) && $recent_students_stmt) {
                $recent_students_stmt->close(); 
            }
            ?>
            <a href="manage_students.php" class="mt-4 inline-block text-indigo-600 hover:text-indigo-800 text-sm font-medium">View All Students &rarr;</a>
        </div>

        <!-- The Recent Enrollments Panel (Placeholder for future feature) -->
        <div class="bg-white shadow-lg rounded-lg p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">📚 Quick Links</h2>
            <nav class="space-y-3">
                <a href="manage_students.php?status=pending" class="flex items-center text-sm font-medium text-yellow-600 hover:text-yellow-800 p-2 rounded-lg bg-yellow-50 hover:bg-yellow-100 transition duration-150">
                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.3 17c-.77 1.333.192 3 1.732 3z"></path></svg>
                    Review Pending Students
                </a>
                <a href="manage_courses.php?action=add" class="flex items-center text-sm font-medium text-blue-600 hover:text-blue-800 p-2 rounded-lg bg-blue-50 hover:bg-blue-100 transition duration-150">
                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    Add a New Course
                </a>
                <a href="reports.php" class="flex items-center text-sm font-medium text-gray-600 hover:text-gray-800 p-2 rounded-lg bg-gray-50 hover:bg-gray-100 transition duration-150">
                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6m0 0h18M12 3l10 9H2L12 3zm0 0l-5 5M12 3l5 5"></path></svg>
                    View Enrollment Reports
                </a>
            </nav>
        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>