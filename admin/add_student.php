<?php
// FILE: add_student.php
// Administrator page for manually adding a new student account.

include('includes/header.php'); 
// header.php includes auth_check.php (role check) and ../includes/db_connect.php (using $conn object)

$error = '';
$success = '';

// Check if $conn is available
if (!isset($conn) || $conn->connect_error) {
    die("FATAL ERROR: Database connection failed.");
}

// --- 1. HANDLE FORM SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect and sanitize input
    $fullname = trim($_POST['fullname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Default role and status for admin-added students
    $role = 'student';
    $status = 'active'; 

    // Basic Validation
    if (empty($fullname) || empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        // Check for unique username or email using prepared statements
        $check_query = "SELECT id FROM users WHERE username = ? OR email = ?";
        $check_stmt = $conn->prepare($check_query);
        
        if ($check_stmt) {
            $check_stmt->bind_param("ss", $username, $email);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                // Determine if it was username or email conflict
                $row = $check_result->fetch_assoc();
                $conflict_query = "SELECT username, email FROM users WHERE id = ?";
                $conflict_stmt = $conn->prepare($conflict_query);
                $conflict_stmt->bind_param("i", $row['id']);
                $conflict_stmt->execute();
                $conflict_row = $conflict_stmt->get_result()->fetch_assoc();
                $conflict_stmt->close();

                if ($conflict_row['username'] === $username) {
                    $error = "Username is already taken.";
                } elseif ($conflict_row['email'] === $email) {
                    $error = "Email is already registered.";
                }
            }
            $check_stmt->close();
        } else {
            $error = "Database check failed: " . $conn->error;
        }

        // Proceed to insertion if no errors
        if (empty($error)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert into 'users' table (since students are a type of user)
            $insert_user_query = "INSERT INTO users (fullname, username, email, password, role) VALUES (?, ?, ?, ?, ?)";
            $user_stmt = $conn->prepare($insert_user_query);

            if ($user_stmt) {
                // 'sssss' for string types
                $user_stmt->bind_param("sssss", $fullname, $username, $email, $hashed_password, $role);
                
                if ($user_stmt->execute()) {
                    $new_user_id = $user_stmt->insert_id;
                    $user_stmt->close();

                    // Insert the corresponding record into the 'students' table
                    $insert_student_query = "INSERT INTO students (id, fullname, username, email, status) VALUES (?, ?, ?, ?, ?)";
                    $student_stmt = $conn->prepare($insert_student_query);

                    if ($student_stmt) {
                        // 'issss' for integer (id), string (fullname, username, email, status)
                        $student_stmt->bind_param("issss", $new_user_id, $fullname, $username, $email, $status);
                        
                        if ($student_stmt->execute()) {
                            $success = "Student '{$fullname}' added successfully with ID: {$new_user_id} and status: ACTIVE.";
                            // Optionally redirect to manage_students.php
                            $_SESSION['message'] = $success;
                            header("Location: manage_students.php");
                            exit();
                        } else {
                            $error = "Error adding student record: " . $student_stmt->error;
                            // Critical: Rollback the user creation if the student insert fails
                            $conn->query("DELETE FROM users WHERE id = $new_user_id");
                        }
                        $student_stmt->close();
                    } else {
                        $error = "Student insert preparation failed: " . $conn->error;
                        $conn->query("DELETE FROM users WHERE id = $new_user_id");
                    }

                } else {
                    $error = "Error creating user account: " . $user_stmt->error;
                }
                $user_stmt->close();
            } else {
                $error = "User insert preparation failed: " . $conn->error;
            }
        }
    }
}
// Set default values for form to prevent losing data on error
$fullname = $_POST['fullname'] ?? '';
$username = $_POST['username'] ?? '';
$email = $_POST['email'] ?? '';

?>

<div class="max-w-xl mx-auto py-6 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Add New Student Manually</h1>

    <!-- Message Display -->
    <?php if ($success): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
        <strong class="font-bold">Success!</strong>
        <span class="block sm:inline"><?= $success ?></span>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
        <strong class="font-bold">Error!</strong>
        <span class="block sm:inline"><?= $error ?></span>
    </div>
    <?php endif; ?>

    <!-- Add Student Form -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg p-6">
        <form method="POST" action="add_student.php" class="space-y-6">
            
            <!-- Full Name -->
            <div>
                <label for="fullname" class="block text-sm font-medium text-gray-700">Full Name</label>
                <input type="text" id="fullname" name="fullname" value="<?= htmlspecialchars($fullname) ?>" required
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>

            <!-- Username -->
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                <input type="text" id="username" name="username" value="<?= htmlspecialchars($username) ?>" required
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input type="password" id="password" name="password" required
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                <p class="mt-1 text-xs text-gray-500">Min 6 characters. The student can change this later.</p>
            </div>

            <!-- Submit Button -->
            <div class="flex items-center justify-between">
                <a href="manage_students.php" class="text-sm font-medium text-gray-600 hover:text-indigo-600">
                    &larr; Back to Student List
                </a>
                <button type="submit" 
                        class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Create Student Account
                </button>
            </div>
        </form>
    </div>
</div>

<?php include('includes/footer.php'); ?>