<?php
include('includes/header.php'); 
// header.php includes auth_check.php, $conn, and session variables

$student_id = $_SESSION['user_id'];
$success = '';
$error = '';

// --- 1. Fetch Current Student Details ---
$fetch_query = "SELECT fullname, username, email, class, phone_number, created_at FROM students WHERE id = '$student_id' LIMIT 1";
$fetch_result = mysqli_query($conn, $fetch_query);

if (!$fetch_result || mysqli_num_rows($fetch_result) == 0) {
    $error = "Error: Could not retrieve profile data.";
    // Provide a default structure to avoid breaking the page
    $student = ['fullname' => 'N/A', 'username' => 'N/A', 'email' => 'N/A', 'class' => 'N/A', 'phone_number' => ''];
} else {
    $student = mysqli_fetch_assoc($fetch_result);
}


// --- 2. Handle Profile Update (POST request for profile details) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    
    // Sanitize input
    $new_fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $new_class = mysqli_real_escape_string($conn, $_POST['class']);
    // We assume 'phone_number' is the correct column name based on your issue
    $new_phone_number = mysqli_real_escape_string($conn, $_POST['phone_number']); 

    // Validation (basic check)
    if (empty($new_fullname) || empty($new_class)) {
        $error = "Full Name and Class cannot be empty.";
    } else {
        // Build the update query
        $update_query = "UPDATE students SET 
                            fullname = '$new_fullname', 
                            class = '$new_class', 
                            phone_number = '$new_phone_number' 
                         WHERE id = '$student_id'";

        if (mysqli_query($conn, $update_query)) {
            $success = "Profile details updated successfully!";
            // Re-fetch updated data
            $fetch_result = mysqli_query($conn, $fetch_query);
            $student = mysqli_fetch_assoc($fetch_result);
        } else {
            // This is where you would see a MySQL error if the phone_number column is missing!
            $error = "Error updating profile: " . mysqli_error($conn);
        }
    }
}


// --- 3. Handle Password Update (POST request for password change) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Input validation
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All password fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New password and confirmation password do not match.";
    } elseif (strlen($new_password) < 8) {
        $error = "New password must be at least 8 characters long.";
    } else {
        // Fetch current hashed password to verify
        $pass_query = "SELECT password FROM students WHERE id = '$student_id' LIMIT 1";
        $pass_result = mysqli_query($conn, $pass_query);
        $pass_data = mysqli_fetch_assoc($pass_result);
        $hashed_password = $pass_data['password'];

        if (password_verify($current_password, $hashed_password)) {
            // Hash the new password and update
            $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $pass_update_query = "UPDATE students SET password = '$new_hashed_password' WHERE id = '$student_id'";

            if (mysqli_query($conn, $pass_update_query)) {
                $success = "Password updated successfully!";
            } else {
                $error = "Error updating password: " . mysqli_error($conn);
            }
        } else {
            $error = "Current password is incorrect.";
        }
    }
}

// Display variables, ensuring they exist even on error
$fullname = htmlspecialchars($student['fullname'] ?? 'N/A');
$username = htmlspecialchars($student['username'] ?? 'N/A');
$email = htmlspecialchars($student['email'] ?? 'N/A');
$class = htmlspecialchars($student['class'] ?? ''); // Needs to be empty for input field
$phone_number = htmlspecialchars($student['phone_number'] ?? ''); // Needs to be empty for input field

?>

<h1 class="text-3xl font-bold text-gray-800 mb-6">Manage Your Profile</h1>
<p class="text-gray-600 mb-8">Update your personal information and manage your account security here.</p>

<?php if ($error): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
        <strong class="font-bold">Error!</strong>
        <span class="block sm:inline"><?php echo $error; ?></span>
    </div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
        <strong class="font-bold">Success!</strong>
        <span class="block sm:inline"><?php echo $success; ?></span>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    
    <!-- Profile Details Update Form -->
    <div class="bg-white p-8 rounded-xl shadow-xl">
        <h2 class="text-2xl font-bold text-gray-800 border-b pb-3 mb-6">Personal Details</h2>
        <form method="POST" action="profile.php" class="space-y-4">
            <input type="hidden" name="update_profile" value="1">

            <!-- Full Name -->
            <div>
                <label for="fullname" class="block text-sm font-medium text-gray-700">Full Name</label>
                <input type="text" id="fullname" name="fullname" value="<?php echo $fullname; ?>" required 
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 border p-2">
            </div>

            <!-- Username (Read-only) -->
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                <input type="text" id="username" value="<?php echo $username; ?>" readonly 
                       class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 p-2 text-gray-500 cursor-not-allowed">
            </div>

            <!-- Email (Read-only) -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                <input type="email" id="email" value="<?php echo $email; ?>" readonly 
                       class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 p-2 text-gray-500 cursor-not-allowed">
            </div>

            <!-- Class -->
            <div>
                <label for="class" class="block text-sm font-medium text-gray-700">Current Class/Track</label>
                <input type="text" id="class" name="class" value="<?php echo $class; ?>" readonly 
                       class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 p-2 text-gray-500 cursor-not-allowed">
            </div>

            <!-- Contact Number -->
            <div>
                <label for="phone_number" class="block text-sm font-medium text-gray-700">Contact Number</label>
                <input type="text" id="phone_number" name="phone_number" value="<?php echo $phone_number; ?>" 
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 border p-2"
                       placeholder="e.g., 08012345678">
            </div>

            <button type="submit"
                    class="w-full py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150">
                Save Changes
            </button>
        </form>
    </div>

    <!-- Password Update Form -->
    <div class="bg-white p-8 rounded-xl shadow-xl self-start">
        <h2 class="text-2xl font-bold text-gray-800 border-b pb-3 mb-6">Change Password</h2>
        <form method="POST" action="profile.php" class="space-y-4">
            <input type="hidden" name="change_password" value="1">

            <!-- Current Password -->
            <div>
                <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                <input type="password" id="current_password" name="current_password" required 
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 border p-2">
            </div>
            
            <!-- New Password -->
            <div>
                <label for="new_password" class="block text-sm font-medium text-gray-700">New Password (min 8 chars)</label>
                <input type="password" id="new_password" name="new_password" required 
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 border p-2">
            </div>
            
            <!-- Confirm Password -->
            <div>
                <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required 
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 border p-2">
            </div>

            <button type="submit"
                    class="w-full py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition duration-150">
                Change Password
            </button>
        </form>
    </div>
</div>

<?php
include('includes/footer.php');
?>