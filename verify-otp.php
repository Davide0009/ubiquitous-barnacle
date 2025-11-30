<?php
// Include essential files
include('includes/db.php');
// Include the new email utility using PHPMailer
include('includes/email_sender.php'); 
// NOTE: You must also ensure PHPMailer vendor files are included/autoloaded here.

// Include header (starts HTML and Navigation)
include('includes/header.php'); 

$error_message = '';
$success_message = '';

// 1. Check if the user is supposed to be here (i.e., just registered)
if (!isset($_SESSION['temp_email'])) {
    // If not, redirect them back to registration or login
    header('Location: register.php');
    exit();
}

$email = $_SESSION['temp_email'];

// 2. Process OTP Submission
if (isset($_POST['verify_btn'])) {
    // Ensure all session variables used in mysqli_real_escape_string are properly initialized 
    // before use if they are coming from an external source. Here $conn is global.
    $submitted_otp = mysqli_real_escape_string($conn, $_POST['otp']);
    
    // Check for a matching user and OTP that hasn't been verified yet
    $query = "SELECT id FROM students WHERE email = '$email' AND otp = '$submitted_otp' AND status = 'awaiting_otp' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {
        // OTP is correct! Update status to 'pending' and clear the OTP
        $update_query = "UPDATE students SET status = 'pending', otp = NULL WHERE email = '$email'";
        
        if (mysqli_query($conn, $update_query)) {
            // Clear temporary session data
            unset($_SESSION['temp_email']);
            
            // Redirect to a pending approval page
            header('Location: pending.php');
            exit();
        } else {
            $error_message = "Verification failed. Database error.";
        }
    } else {
        $error_message = "Invalid or expired verification code. Please check your email.";
    }
}

// 3. Resend OTP Logic
if (isset($_POST['resend_otp'])) {
    // Generate a new OTP
    $new_otp = rand(100000, 999999);
    
    // Update the database with the new OTP
    $update_query = "UPDATE students SET otp = '$new_otp' WHERE email = '$email' AND status = 'awaiting_otp'";
    mysqli_query($conn, $update_query);
    
    // --- START: New Email Sending Implementation using PHPMailer ---
    if (send_otp_email($email, $new_otp)) {
        $success_message = "A new verification code has been successfully sent to **" . htmlspecialchars($email) . "**. Check your inbox (or spam folder)!";
    } else {
        // If PHPMailer fails (e.g., bad credentials, connection issue)
        // Log details are already in error_log from email_sender.php
        $error_message = "A new code was generated, but the email could not be sent. Please check the SMTP configuration in `includes/email_sender.php`.";
    }
    // --- END: New Email Sending Implementation using PHPMailer ---
}

// The test OTP display logic has been removed as requested.
?>

<div class="flex items-center justify-center py-10 px-4"> 
    
    <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-md border border-gray-200">
        <div class="text-center mb-6">
            <div class="text-orange-600 font-bold text-3xl mb-2">Verify Your Account</div>
            <!-- Removed the Test OTP display variable -->
            <p class="text-gray-600">A 6-digit verification code has been sent to **<?php echo htmlspecialchars($email); ?>**.</p>
        </div>

        <?php if ($error_message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $error_message; ?></span>
            </div>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $success_message; ?></span>
            </div>
        <?php endif; ?>

        <form action="verify-otp.php" method="POST" class="space-y-6">
            <div>
                <label for="otp" class="block text-sm font-medium text-gray-700 mb-2">Verification Code</label>
                <input type="text" id="otp" name="otp" required maxlength="6" pattern="\d{6}"
                       class="w-full text-center text-2xl font-mono tracking-widest px-4 py-3 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500" 
                       placeholder="Enter 6-digit code">
            </div>

            <button type="submit" name="verify_btn"
                    class="w-full bg-orange-600 hover:bg-orange-700 text-white font-bold py-3 rounded-md transition duration-150">
                Verify Account
            </button>
        </form>
        
        <form action="verify-otp.php" method="POST" class="mt-4 text-center">
            <button type="submit" name="resend_otp"
                    class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                Resend Code
            </button>
        </form>

    </div>
    
</div>

<?php
// Include footer (closes body and html tags)
include('includes/footer.php');
?>