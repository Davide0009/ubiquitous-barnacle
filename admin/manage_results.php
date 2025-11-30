<?php
// FILE: manage_results.php
// Handles form submission for adding new student results and displays the form.

// Include header which contains auth check, database connection ($conn), and necessary setup.
include('includes/header.php'); 

global $conn;

$success = '';
$error = '';
$students = [];
$courses = [];

// --- Database Safety Check: Ensure the 'results' table exists ---
// This ensures that even if the student dashboard failed to create the table, 
// the admin panel can proceed.
$create_results_table = "
    CREATE TABLE IF NOT EXISTS results (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        student_id INT(11) NOT NULL,
        course_id INT(11) NOT NULL,
        score DECIMAL(5, 2) NOT NULL,
        term VARCHAR(50) NOT NULL,
        session VARCHAR(50) NOT NULL,
        recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
";
// Use error suppression (@) for DDL operations as they often fail if the table already exists.
@mysqli_query($conn, $create_results_table);


// --- 1. Handle Form Submission (Add Result) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_result') {
    // Sanitize and validate inputs
    $student_id = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
    $course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
    $score = filter_input(INPUT_POST, 'score', FILTER_VALIDATE_FLOAT);
    $term = filter_input(INPUT_POST, 'term', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $session = filter_input(INPUT_POST, 'session', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    if (!$student_id || !$course_id || $score === false || empty($term) || empty($session)) {
        $error = "Please fill in all required fields with valid data.";
    } elseif ($score < 0 || $score > 100) {
        $error = "Score must be between 0 and 100.";
    } else {
        $insert_query = "INSERT INTO results (student_id, course_id, score, term, session) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        
        if ($stmt) {
            // 'i' for integer, 'i' for integer, 'd' for double/float, 's' for string, 's' for string
            $stmt->bind_param('iidss', $student_id, $course_id, $score, $term, $session);
            if ($stmt->execute()) {
                $success = "Result added successfully for student ID {$student_id} in course ID {$course_id}.";
            } else {
                $error = "Database error during insertion: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = "Failed to prepare database statement: " . $conn->error;
        }
    }
}

// --- 2. Fetch Students and Courses for Dropdowns ---
if ($conn) {
    // Fetch students (ID, Fullname, and Class)
    $stmt_students = $conn->prepare("SELECT id, fullname, class FROM students WHERE status = 'active' ORDER BY fullname ASC");
    if ($stmt_students && $stmt_students->execute()) {
        $result_students = $stmt_students->get_result();
        while ($row = $result_students->fetch_assoc()) {
            $students[] = $row;
        }
        $stmt_students->close();
    } else {
        // Only set error if there's a serious issue, not just zero students
        if ($stmt_students && $stmt_students->error) {
             $error = "Could not load student list: " . $stmt_students->error;
        }
    }

    // Fetch courses (ID and Name)
    $stmt_courses = $conn->prepare("SELECT id, name FROM courses ORDER BY name ASC");
    if ($stmt_courses && $stmt_courses->execute()) {
        $result_courses = $stmt_courses->get_result();
        while ($row = $result_courses->fetch_assoc()) {
            $courses[] = $row;
        }
        $stmt_courses->close();
    } else {
        // Only set error if there's a serious issue, not just zero courses
        if ($stmt_courses && $stmt_courses->error) {
            $error = "Could not load course list: " . $stmt_courses->error;
        }
    }
}

// Define fixed lists for Term and Session
$terms = ['First Term', 'Second Term', 'Third Term'];
// Populate common session range (e.g., current year - 2 years)
$current_year = date('Y');
$sessions = [];
for ($i = 0; $i < 3; $i++) {
    $start_year = $current_year - $i;
    $sessions[] = $start_year . '/' . ($start_year + 1);
}
?>

<div class="max-w-4xl mx-auto">
    <h1 class="text-4xl font-extrabold text-gray-800 mb-8">Manage Academic Results</h1>
    
    <!-- Success/Error Messages -->
    <?php if ($success): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
            <strong class="font-bold">Success!</strong>
            <span class="block sm:inline"><?= htmlspecialchars($success) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
            <strong class="font-bold">Error!</strong>
            <span class="block sm:inline"><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <!-- Add New Result Form -->
    <div class="bg-white p-8 rounded-xl shadow-xl mb-10">
        <h2 class="text-2xl font-bold text-gray-800 border-b pb-3 mb-6">Add New Student Result</h2>
        
        <form method="POST" action="manage_results.php" class="space-y-6">
            <input type="hidden" name="action" value="add_result">

            <!-- Student Dropdown -->
            <div>
                <label for="student_id" class="block text-sm font-medium text-gray-700 mb-1">Student</label>
                <select id="student_id" name="student_id" required class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="">-- Select Student --</option>
                    <?php foreach ($students as $student): ?>
                        <option value="<?= $student['id'] ?>">
                            <?= htmlspecialchars($student['fullname']) ?> (Class: <?= htmlspecialchars($student['class']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($students)): ?><p class="text-red-500 text-xs mt-1">No active students found. Check student management page.</p><?php endif; ?>
            </div>

            <!-- Course Dropdown -->
            <div>
                <label for="course_id" class="block text-sm font-medium text-gray-700 mb-1">Course / Subject</label>
                <select id="course_id" name="course_id" required class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="">-- Select Course --</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?= $course['id'] ?>">
                            <?= htmlspecialchars($course['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                 <?php if (empty($courses)): ?><p class="text-red-500 text-xs mt-1">No courses found. Check course management page.</p><?php endif; ?>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Score Input -->
                <div>
                    <label for="score" class="block text-sm font-medium text-gray-700 mb-1">Score (%)</label>
                    <input type="number" step="0.1" min="0" max="100" id="score" name="score" required 
                           class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>

                <!-- Term Dropdown -->
                <div>
                    <label for="term" class="block text-sm font-medium text-gray-700 mb-1">Term</label>
                    <select id="term" name="term" required class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <option value="">-- Select Term --</option>
                        <?php foreach ($terms as $t): ?>
                            <option value="<?= $t ?>" <?= $t == 'First Term' ? 'selected' : '' ?>>
                                <?= $t ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Session Dropdown -->
                <div>
                    <label for="session" class="block text-sm font-medium text-gray-700 mb-1">Academic Session</label>
                    <select id="session" name="session" required class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <option value="">-- Select Session --</option>
                        <?php foreach ($sessions as $s): ?>
                            <option value="<?= $s ?>" <?= $s == $sessions[0] ? 'selected' : '' ?>>
                                <?= $s ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="pt-4">
                <button type="submit" class="w-full inline-flex justify-center py-3 px-6 border border-transparent shadow-lg text-lg font-medium rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Record Result
                </button>
            </div>
        </form>
    </div>
    
    <!-- (Future feature: List of Existing Results to Manage/Edit) -->
    <div class="text-center py-8">
        <p class="text-gray-500">A table listing all existing results for easy editing and deletion can be built here next.</p>
    </div>

</div>

<?php
include('includes/footer.php');
?>