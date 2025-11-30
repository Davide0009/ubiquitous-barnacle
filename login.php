<?php
// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include('includes/db.php');

$error = '';
$success = '';

// Check if a user is already logged in
if (isset($_SESSION['user_role'])) {
    if ($_SESSION['user_role'] === 'admin') {
        header('Location: admin/dashboard.php');
        exit();
    } elseif ($_SESSION['user_role'] === 'student') {
        // IMPORTANT: Active students go to dashboard, others are checked by auth_check.php or redirected by pending.php
        // We will perform a quick status check here to avoid immediate redirects from a user with status other than 'active'.
        
        $user_id = $_SESSION['user_id'];
        $query = "SELECT status FROM students WHERE id = '$user_id' LIMIT 1";
        $result = mysqli_query($conn, $query);
        $user_data = mysqli_fetch_assoc($result);

        if ($user_data && $user_data['status'] === 'active') {
            header('Location: student/dashboard.php');
            exit();
        } elseif ($user_data && $user_data['status'] === 'pending') {
            header('Location: pending.php');
            exit();
        } else {
             // If status is rejected or unknown, we let the current page load or force logout later.
        }
    }
}

// Handle login attempt
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password']; // Get the raw password

    // --- 1. Check Admin Table ---
    $admin_query = "SELECT * FROM admin WHERE username = '$username' LIMIT 1";
    $admin_result = mysqli_query($conn, $admin_query);
    
    if (mysqli_num_rows($admin_result) == 1) {
        $user = mysqli_fetch_assoc($admin_result);
        if (password_verify($password, $user['password'])) {
            // Admin login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = 'admin';
            header('Location: admin/dashboard.php');
            exit();
        }
    }

    // --- 2. Check Student Table ---
    $student_query = "SELECT * FROM students WHERE username = '$username' OR email = '$username' LIMIT 1";
    $student_result = mysqli_query($conn, $student_query);

    if (mysqli_num_rows($student_result) == 1) {
        $user = mysqli_fetch_assoc($student_result);
        if (password_verify($password, $user['password'])) {
            // Student login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = 'student';
            
            // FIX IS HERE: Redirect based on student status
            if ($user['status'] === 'active') {
                header('Location: student/dashboard.php');
                exit();
            } elseif ($user['status'] === 'pending') {
                header('Location: pending.php');
                exit();
            } elseif ($user['status'] === 'awaiting_otp') {
                // Future OTP validation page
                $error = "OTP validation is required before accessing the portal.";
            } elseif ($user['status'] === 'rejected') {
                $error = "Your account registration was rejected by the administration.";
            }
            
        } else {
            $error = 'Invalid credentials for student account.';
        }
    } else {
        $error = 'No user found with that username or email.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Login - Real Sensor</title>
    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center font-sans">

    <div class="max-w-md w-full bg-white p-8 rounded-xl shadow-2xl space-y-8 border-t-4 border-blue-600">
        <div class="text-center">
            <h1 class="text-3xl font-bold text-gray-900">Sign In to Your Account</h1>
            <p class="mt-2 text-sm text-gray-600">Admin or Student Portal</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <strong class="font-bold">Success!</strong>
                <span class="block sm:inline"><?php echo $success; ?></span>
            </div>
        <?php endif; ?>

        <form class="mt-8 space-y-6" method="POST" action="login.php">
            <input type="hidden" name="login" value="1">
            
            <div>
                <label for="username" class="sr-only">Username or Email</label>
                <input id="username" name="username" type="text" autocomplete="email" required
                       class="relative block w-full appearance-none rounded-md border border-gray-300 px-3 py-3 placeholder-gray-500 text-gray-900 focus:z-10 focus:border-blue-500 focus:outline-none focus:ring-blue-500 sm:text-sm"
                       placeholder="Username or Email">
      </div>

      <div>
                <label for="password" class="sr-only">Password</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required
                       class="relative block w-full appearance-none rounded-md border border-gray-300 px-3 py-3 placeholder-gray-500 text-gray-900 focus:z-10 focus:border-blue-500 focus:outline-none focus:ring-blue-500 sm:text-sm"
                       placeholder="Password">
            </div>

        <div class="flex items-center justify-between">
          <div class="text-sm">
                    <a href="register.php" class="font-medium text-blue-600 hover:text-blue-500">
                        Don't have an account? Register
                    </a>
        </div>
      </div>

      <div>
                <button type="submit"
                        class="group relative flex w-full justify-center rounded-md border border-transparent bg-blue-600 py-3 px-4 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-150">
                    Sign In
                </button>
      </div>
    </form>
        <div class="text-center text-xs text-gray-500 mt-4">
            <a href="index.php" class="hover:text-gray-700">← Back to Homepage</a>
  </div>
</div>
 
</body>
</html>