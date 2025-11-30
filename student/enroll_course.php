<?php
// FILE: enroll_courses.php
// Allows logged-in students to view available courses and enroll in new ones.

include('includes/header.php'); 
// header.php includes auth_check.php (for session and user verification) 
// and loads the database connection ($conn).

// Get the student ID from the session
$student_id = $_SESSION['user_id'];
global $conn;

$success_message = '';
$error_message = '';

// --- 1. Defensive Database Schema Checks ---
if ($conn) {
    // A. Ensure the 'enrollments' table exists
    $create_enrollments_query = "
        CREATE TABLE IF NOT EXISTS enrollments (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            student_id INT(11) NOT NULL,
            course_id INT(11) NOT NULL,
            enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_enrollment (student_id, course_id)
        )
    ";
    mysqli_query($conn, $create_enrollments_query);

    // B. Ensure the 'courses' table exists and has the necessary 'status' column
    $create_courses_query = "
        CREATE TABLE IF NOT EXISTS courses (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            price DECIMAL(10, 2) DEFAULT 0.00,
            status ENUM('active', 'archived') DEFAULT 'active'
        )
    ";
    mysqli_query($conn, $create_courses_query);

    // C. Add 'status' column to 'courses' table if it is missing (this addresses the error)
    $check_column_query = "SHOW COLUMNS FROM courses LIKE 'status'";
    $result = mysqli_query($conn, $check_column_query);
    
    if ($result && mysqli_num_rows($result) === 0) {
        $alter_table_query = "ALTER TABLE courses ADD COLUMN status ENUM('active', 'archived') DEFAULT 'active' AFTER price";
        mysqli_query($conn, $alter_table_query);
    }
}

// --- 2. Handle Enrollment Request (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_id'])) {
    $course_id = (int)$_POST['course_id'];

    // Check if student is already enrolled
    $check_query = "SELECT COUNT(*) FROM enrollments WHERE student_id = ? AND course_id = ?";
    $stmt_check = $conn->prepare($check_query);
    $stmt_check->bind_param('ii', $student_id, $course_id);
    $stmt_check->execute();
    $stmt_check->bind_result($count);
    $stmt_check->fetch();
    $stmt_check->close();

    if ($count > 0) {
        $error_message = "You are already enrolled in this course.";
    } else {
        // Proceed with enrollment
        $enroll_query = "INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)";
        $stmt_enroll = $conn->prepare($enroll_query);
        $stmt_enroll->bind_param('ii', $student_id, $course_id);

        if ($stmt_enroll->execute()) {
            $success_message = "Successfully enrolled in the course! You can see your progress on the dashboard.";
        } else {
            // Handle specific errors like foreign key constraints if they were set up
            $error_message = "Enrollment failed. Database error: " . $stmt_enroll->error;
        }
        $stmt_enroll->close();
    }
}

// --- 3. Fetch Data for Display ---

// A. Get all enrolled course IDs for the current student
$enrolled_courses = [];
$enrolled_query = "SELECT course_id FROM enrollments WHERE student_id = ?";
$stmt_enrolled = $conn->prepare($enrolled_query);
$stmt_enrolled->bind_param('i', $student_id);
$stmt_enrolled->execute();
$result_enrolled = $stmt_enrolled->get_result();

while ($row = $result_enrolled->fetch_assoc()) {
    $enrolled_courses[] = $row['course_id'];
}
$stmt_enrolled->close();

// B. Get all available active courses
$available_courses = [];
// This is the original problematic query (line 94 in the original file).
// We assume the column now exists due to the DDL checks above.
$courses_query = "SELECT id, name, description, price FROM courses WHERE status = 'active' ORDER BY name ASC";
$result_courses = mysqli_query($conn, $courses_query);

if (!$result_courses) {
    // If the query still fails (e.g., column not found for some reason), 
    // fall back to a safer query without the 'status' column.
    $courses_query_fallback = "SELECT id, name, description, price FROM courses ORDER BY name ASC";
    $result_courses = mysqli_query($conn, $courses_query_fallback);
    
    if (!$result_courses) {
        // Log the severe error if both attempts fail
        $error_message .= " Could not retrieve available courses: " . mysqli_error($conn);
    }
}

if ($result_courses) {
    while ($row = mysqli_fetch_assoc($result_courses)) {
        $available_courses[] = $row;
    }
}
?>

<h1 class="text-4xl font-extrabold text-gray-800 mb-2">Course Catalog</h1>
<p class="text-gray-600 mb-8">Browse the available subjects and enroll to start learning immediately.</p>

<!-- Notifications -->
<?php if ($success_message): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg" role="alert">
        <p class="font-bold">Success!</p>
        <p><?= htmlspecialchars($success_message) ?></p>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg" role="alert">
        <p class="font-bold">Enrollment Error</p>
        <p><?= htmlspecialchars($error_message) ?></p>
    </div>
<?php endif; ?>

<!-- Course List Grid -->
<?php if (!empty($available_courses)): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php foreach ($available_courses as $course): 
            $is_enrolled = in_array($course['id'], $enrolled_courses);
            $course_id = htmlspecialchars($course['id']);
            $course_name = htmlspecialchars($course['name']);
            // Fallback for description if null
            $course_description = htmlspecialchars($course['description'] ?? 'No description provided.');
            $course_price = ($course['price'] > 0) ? '$' . number_format($course['price'], 2) : 'Free';
        ?>
            <div class="bg-white rounded-xl shadow-xl overflow-hidden flex flex-col hover:shadow-2xl transition duration-300">
                <div class="p-6 flex-grow">
                    <h2 class="text-2xl font-bold text-indigo-700 mb-2"><?= $course_name ?></h2>
                    <p class="text-sm font-medium text-gray-500 mb-4">Price: <span class="text-green-600 font-semibold"><?= $course_price ?></span></p>
                    <p class="text-gray-600 line-clamp-3"><?= $course_description ?></p>
                </div>
                
                <div class="p-6 bg-gray-50 border-t border-gray-100">
                    <?php if ($is_enrolled): ?>
                        <button class="w-full py-3 bg-green-500 text-white font-bold rounded-lg cursor-not-allowed opacity-75 flex items-center justify-center space-x-2" disabled>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                            <span>Enrolled</span>
                        </button>
                    <?php else: ?>
                        <form method="POST" action="enroll_courses.php">
                            <input type="hidden" name="course_id" value="<?= $course_id ?>">
                            <button type="submit" class="w-full py-3 bg-indigo-600 text-white font-bold rounded-lg shadow-md hover:bg-indigo-700 transition duration-150 transform hover:scale-[1.02]">
                                Enroll Now
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700 p-4 rounded-lg" role="alert">
        <p class="font-bold">No Courses Available</p>
        <p>The course catalog is currently empty. Please check back later or contact administration.</p>
    </div>
<?php endif; ?>


<?php
include('includes/footer.php');
?>