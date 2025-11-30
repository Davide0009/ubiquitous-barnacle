<?php
// Include essential files
include('includes/db.php');

// Include header (starts HTML and Navigation)
include('includes/header.php'); 

$success_message = '';
$error_message = '';

if (isset($_POST['register_btn'])) {
    // 1. Sanitize and Collect Data
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $class = mysqli_real_escape_string($conn, $_POST['class']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // 2. Input Validation (Check for duplicate username or email)
    $check_query = "SELECT * FROM students WHERE username='$username' OR email='$email' LIMIT 1";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
        $error_message = "Username or Email already exists. Please try logging in.";
        } else {
        // 3. Hash Password and Generate OTP
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $otp = rand(100000, 999999);
        $status = 'awaiting_otp';
            
        // 4. Insert New Student Data
        $insert_query = "INSERT INTO students (fullname, email, phone, class, username, password, otp, status, created_at) 
                         VALUES ('$fullname', '$email', '$phone', '$class', '$username', '$hashed_password', '$otp', '$status', NOW())";
            
            if (mysqli_query($conn, $insert_query)) {
            
            // 5. Store data in session for verification page
            $_SESSION['temp_email'] = $email; 
            $_SESSION['temp_otp'] = $otp; // Used for testing verification without live email sending
            
            // Redirect to OTP verification page
            header('Location: verify-otp.php');
            exit();

            } else {
            $error_message = "Registration failed. Database Error: " . mysqli_error($conn);
        }
    }
}
?>

<div class="flex items-center justify-center py-10 px-4"> 
    
    <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-lg border border-gray-200">
        <div class="text-center mb-6">
            <div class="text-blue-600 font-bold text-3xl mb-2">New Student Registration</div>
            <p class="text-gray-600">Please fill out your details to create an account.</p>
        </div>

        <?php if ($error_message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $error_message; ?></span>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST" class="space-y-4">
            <div>
                <label for="fullname" class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                <input type="text" id="fullname" name="fullname" required class="w-full px-4 py-2 border border-gray-300 rounded-md">
            </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email (For OTP & Login)</label>
                    <input type="email" id="email" name="email" required class="w-full px-4 py-2 border border-gray-300 rounded-md">
                </div>
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                    <input type="tel" id="phone" name="phone" required class="w-full px-4 py-2 border border-gray-300 rounded-md">
                </div>
                <div>
                    <label for="class" class="block text-sm font-medium text-gray-700 mb-2">Class / Program</label>
                    <select id="class" name="class" required class="w-full px-4 py-2 border border-gray-300 rounded-md">
                        <option value="">Select Class/Program</option>
                        <option value="Web Development">Web Development</option>
                        <option value="Graphic Design">Graphic Design</option>
                        <option value="Video Editing">Video Editing</option>
                        <option value="MS Office Suite">MS Office Suite</option>
                    </select>
                </div>

                <div class="pt-4 border-t border-gray-200">
                    <h3 class="font-bold text-lg text-blue-600 mb-3">Set Login Credentials</h3>
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Desired Username</label>
                        <input type="text" id="username" name="username" required class="w-full px-4 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                        <input type="password" id="password" name="password" required class="w-full px-4 py-2 border border-gray-300 rounded-md">
                    </div>
                </div>

                <button type="submit" name="register_btn"
                        class="w-full bg-orange-600 hover:bg-orange-700 text-white font-bold py-3 rounded-md transition duration-150">
                    Register & Get Verification Code
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Already have an account? 
                    <a href="login.php" class="text-blue-600 hover:text-blue-700 font-medium">Log In</a>
                </p>
            </div>
        </div>
        
    </div>

    <?php
    // Include footer (closes body and html tags)
    include('includes/footer.php');
?>