<?php
// FILE: manage_students.php
// Administrator page for managing student accounts (approving, rejecting, deleting)

include('includes/header.php'); 
// header.php already includes auth_check.php (role check) and ../includes/db_connect.php (using $conn object)

// Ensure $conn is available, although it should be loaded by header.php
if (!isset($conn) || $conn->connect_error) {
    die("FATAL ERROR: Database connection failed.");
}

$message = '';
$error = '';
$is_delete_action = false; // Flag to skip the update block if a delete occurs

// --- 1. HANDLE ADMIN ACTIONS (Approve, Reject, Delete) using Prepared Statements ---
if (isset($_POST['action']) && isset($_POST['student_id'])) {
    $student_id = $_POST['student_id'];
    $action = $_POST['action'];
    $success_message = '';
    $error_message = '';

    // Determine the action and the corresponding status/query
    if ($action == 'approve') {
        $status = 'active';
        $success_message = "Student ID $student_id has been **APPROVED** and is now active.";
        $error_message = "Approval failed";
    } elseif ($action == 'reject') {
        $status = 'rejected';
        $success_message = "Student ID $student_id has been **REJECTED**.";
        $error_message = "Rejection failed";
    } elseif ($action == 'delete') {
        $is_delete_action = true;
        
        // Delete action requires deleting records from both 'students' and 'users' tables
        
        // 1. Delete from students table
        $delete_student_query = "DELETE FROM students WHERE id = ?";
        $stmt_student = $conn->prepare($delete_student_query);
        
        if ($stmt_student) {
            $stmt_student->bind_param("i", $student_id);
            if ($stmt_student->execute()) {
                // 2. Delete from users table
                $delete_user_query = "DELETE FROM users WHERE id = ?";
                $stmt_user = $conn->prepare($delete_user_query);
                
                if ($stmt_user) {
                    $stmt_user->bind_param("i", $student_id);
                    if ($stmt_user->execute()) {
                        $_SESSION['message'] = "Student ID $student_id has been **DELETED** permanently from both tables.";
                        header("Location: manage_students.php");
                        exit();
                    } else {
                         // User deletion failed, log error and inform user
                        $error = "Deletion failed (User Table): " . $stmt_user->error;
                        // Re-insert into students table if possible (complex for simple logic)
                    }
                    $stmt_user->close();
                } else {
                    $error = "Deletion prepare failed (User Table): " . $conn->error;
                }
            } else {
                $error = "Deletion failed (Student Table): " . $stmt_student->error;
            }
            $stmt_student->close();
        } else {
            $error = "Deletion prepare failed (Student Table): " . $conn->error;
        }
    }

    // Handle Approve/Reject (UPDATE queries) only if not a delete action
    if (!$is_delete_action && isset($status)) {
        // 1. Update students table status
        $update_student_query = "UPDATE students SET status = ? WHERE id = ?";
        $stmt_student = $conn->prepare($update_student_query);
        
        if ($stmt_student) {
            // 's' for string (status), 'i' for integer (id)
            $stmt_student->bind_param("si", $status, $student_id);
            if ($stmt_student->execute()) {
                // Using a session flash message for better UX after redirect
                $_SESSION['message'] = $success_message;
                // Redirect to avoid form resubmission
                header("Location: manage_students.php");
                exit();
            } else {
                $error = "$error_message: " . $stmt_student->error;
            }
            $stmt_student->close();
        } else {
            $error = "Update prepare failed: " . $conn->error;
        }
    }
}

// Check for session flash message
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']); // Clear the flash message
}


// --- 2. DATA FILTERING, PAGINATION, and FETCHING (using Prepared Statements) ---

// Pagination variables
$limit = 10;
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$offset = ($page - 1) * $limit;

// Filtering variables
$filter_status = isset($_GET['status']) ? htmlspecialchars($_GET['status']) : 'all';
$search_term = isset($_GET['search']) ? htmlspecialchars(trim($_GET['search'])) : '';

$where_clauses = [];
$bind_types = '';
$bind_params = [];

// 1. Status Filter
if ($filter_status !== 'all') {
    $where_clauses[] = "status = ?";
    $bind_types .= 's';
    $bind_params[] = $filter_status;
}

// 2. Search Filter (by username or fullname)
if (!empty($search_term)) {
    // Add parentheses to ensure search takes precedence over other conditions
    $where_clauses[] = "(username LIKE ? OR fullname LIKE ?)";
    $bind_types .= 'ss';
    // Add the '%' wildcard for LIKE search
    $bind_params[] = "%{$search_term}%";
    $bind_params[] = "%{$search_term}%";
}

// Construct the WHERE clause
$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(' AND ', $where_clauses) : "";

// --- Get Total Rows (for pagination) ---
$count_query = "SELECT COUNT(id) FROM students " . $where_sql;
$count_stmt = $conn->prepare($count_query);

if ($count_stmt) {
    // Bind parameters for the COUNT query
    if (count($bind_params) > 0) {
        $count_stmt->bind_param($bind_types, ...$bind_params);
    }
    
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_rows = $count_result->fetch_row()[0];
    $count_stmt->close();
} else {
    $error = "Failed to prepare count query: " . $conn->error;
    $total_rows = 0;
}

$total_pages = ceil($total_rows / $limit);

// --- Get Students Data ---
$data_query = "SELECT id, fullname, username, email, status, created_at 
               FROM students 
               $where_sql 
               ORDER BY created_at DESC 
               LIMIT ?, ?";
$data_stmt = $conn->prepare($data_query);

$students_result = false;
if ($data_stmt) {
    // Prepare the full set of bind parameters for the main query
    $full_bind_types = $bind_types . 'ii'; // Add 'ii' for LIMIT (offset and limit count)
    $full_bind_params = $bind_params;
    $full_bind_params[] = $offset; // LIMIT offset
    $full_bind_params[] = $limit; // LIMIT count

    if (count($full_bind_params) > 0) {
        $data_stmt->bind_param($full_bind_types, ...$full_bind_params);
    }
    
    $data_stmt->execute();
    $students_result = $data_stmt->get_result();
} else {
    $error = "Failed to prepare data query: " . $conn->error;
}


?>

<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    
    <!-- Title and Add Button ROW (NEW ADDITION) -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Manage Students</h1>
        <a href="add_student.php" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
            <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
            </svg>
            Add New Student
        </a>
    </div>

    <!-- Success/Error Message Display -->
    <?php if ($message): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
        <strong class="font-bold">Success!</strong>
        <span class="block sm:inline"><?php echo $message; ?></span>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
        <strong class="font-bold">Error!</strong>
        <span class="block sm:inline"><?php echo $error; ?></span>
    </div>
    <?php endif; ?>

    <!-- Filter and Search Form -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg p-6 mb-6">
        <form method="GET" action="manage_students.php" class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4 items-end">
            
            <!-- Status Filter -->
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700">Filter by Status</label>
                <select id="status" name="status" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                    <option value="all" <?= $filter_status == 'all' ? 'selected' : '' ?>>All Students</option>
                    <option value="pending" <?= $filter_status == 'pending' ? 'selected' : '' ?>>Pending Approval</option>
                    <option value="active" <?= $filter_status == 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="rejected" <?= $filter_status == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
            </div>

            <!-- Search Input -->
            <div class="flex-grow">
                <label for="search" class="block text-sm font-medium text-gray-700">Search (Name or Username)</label>
                <input type="text" id="search" name="search" value="<?= $search_term ?>" placeholder="Enter name or username..." 
                       class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md p-2" />
            </div>

            <!-- Submit Button -->
            <button type="submit" class="w-full sm:w-auto inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Apply Filters
            </button>
        </form>
    </div>

    <!-- Student Table -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name/Username</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if ($students_result && $students_result->num_rows > 0): ?>
                    <?php while ($student = $students_result->fetch_assoc()): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($student['fullname']) ?></div>
                            <div class="text-xs text-gray-500">@<?= htmlspecialchars($student['username']) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?= htmlspecialchars($student['email']) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php 
                                $status_text = ucwords($student['status']);
                                $status_class = [
                                    'active' => 'bg-green-100 text-green-800',
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'rejected' => 'bg-red-100 text-red-800'
                                ][$student['status']] ?? 'bg-gray-100 text-gray-800';
                            ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $status_class ?>">
                                <?= $status_text ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?= date('M d, Y', strtotime($student['created_at'])) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex space-x-2">
                                <!-- Approve Form -->
                                <form method="POST" action="manage_students.php" onsubmit="return confirm('Are you sure you want to activate this student?');">
                                    <input type="hidden" name="student_id" value="<?= $student['id'] ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="text-green-600 hover:text-green-900 disabled:text-gray-400" <?= $student['status'] == 'active' ? 'disabled' : '' ?>>Approve</button>
                                </form>
                                <span class="text-gray-300">|</span>
                                <!-- Reject Form -->
                                <form method="POST" action="manage_students.php" onsubmit="return confirm('Are you sure you want to reject this student?');">
                                    <input type="hidden" name="student_id" value="<?= $student['id'] ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="text-yellow-600 hover:text-yellow-900 disabled:text-gray-400" <?= $student['status'] == 'rejected' ? 'disabled' : '' ?>>Reject</button>
                                </form>
                                <span class="text-gray-300">|</span>
                                <!-- Delete Form -->
                                <form method="POST" action="manage_students.php" onsubmit="return confirm('WARNING: Are you sure you want to PERMANENTLY DELETE this student AND their associated user account?');">
                                    <input type="hidden" name="student_id" value="<?= $student['id'] ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                        <?php echo $error ? "Data could not be loaded due to an error: " . $error : "No students found matching the current filters."; ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<div class="mt-6 flex justify-between items-center">
    <div class="text-sm text-gray-700">
        Showing <?= min($limit, $total_rows) ?> - <?= min($page * $limit, $total_rows) ?> of <?= $total_rows ?> results
    </div>
    
    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++): 
            // Preserve filters in the pagination links
            $query_params = http_build_query([
                'p' => $i, 
                'status' => $filter_status, 
                'search' => $search_term
            ]);
        ?>
            <a href="?<?= $query_params ?>"
               class="relative inline-flex items-center px-4 py-2 border text-sm font-medium 
                      <?= $i == $page ? 'z-10 bg-indigo-600 text-white border-indigo-600' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </nav>
</div>

<?php
if (isset($data_stmt) && $data_stmt) {
    $data_stmt->close();
}
include('includes/footer.php'); 
?>