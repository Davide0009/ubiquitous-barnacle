<?php
// FILE: student_dashboard.php
// Main dashboard for logged-in students to view their enrolled courses and academic results

include('includes/header.php'); 
// header.php includes auth_check.php, which verifies the user is an 'active' student 
// and loads the database connection ($conn) and session variables.

// Get the student ID from the session (using 'user_id' as per the provided structure)
$student_id = $_SESSION['user_id'];
global $conn; // Access the global database connection

$student_results = [];
$error = '';

// --- CRITICAL FIX: DEFENSIVE DATABASE SCHEMA CHECK ---
// We only ensure the table exists. The problematic ALTER TABLE block has been removed 
// as it was causing a "Duplicate column name" fatal error when the column already existed.
if ($conn) {
    // 1. Ensure the 'results' table exists and defines all necessary columns
    $create_table_query = "
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
    // We run this using mysqli_query as prepared statements are not needed for DDL
    // Error suppression is no longer needed here as IF NOT EXISTS is safe.
    mysqli_query($conn, $create_table_query);
}
// --- END CRITICAL FIX ---


// --- 1. Fetch Student Details ---
$query = "SELECT id, fullname, username, email, class, phone_number, created_at FROM students WHERE id = ? LIMIT 1";
$stmt = $conn->prepare($query);
if (!$stmt) {
    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">Error preparing student data query.</div>';
    include('includes/footer.php');
    exit();
}
$stmt->bind_param('i', $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">Error: Could not retrieve student data. Please log out and log in again.</div>';
    include('includes/footer.php');
    exit();
}

$student = $result->fetch_assoc();
$stmt->close();

// Prepare data for display
$registration_date = date('F j, Y', strtotime($student['created_at']));
$fullname = htmlspecialchars($student['fullname'] ?? 'Student');
$class = htmlspecialchars($student['class'] ?? 'N/A');
$username = htmlspecialchars($student['username'] ?? 'N/A');
$email = htmlspecialchars($student['email'] ?? 'N/A');

$phone_number_display = 'N/A';
if (isset($student['phone_number']) && $student['phone_number'] !== '') {
    $phone_number_display = htmlspecialchars($student['phone_number']);
} else {
    $phone_number_display = '<span class="text-gray-500 font-normal">Not provided. <a href="profile.php" class="text-indigo-600 hover:text-indigo-800">Add now</a></span>';
}


// --- 2. Fetch ALL Results for the Student, grouped by Session and Term ---
$results_query = "
    SELECT 
        c.name AS course_name, 
        c.price,
        r.score,
        r.term,
        r.session
    FROM results r
    JOIN courses c ON r.course_id = c.id
    WHERE r.student_id = ?
    ORDER BY r.session DESC, r.term DESC, c.name ASC
";

$stmt_results = $conn->prepare($results_query);
if ($stmt_results) {
    $stmt_results->bind_param('i', $student_id);
    if ($stmt_results->execute()) {
        $results_set = $stmt_results->get_result();
        
        // Group results by Session and Term
        while ($row = $results_set->fetch_assoc()) {
            // Use the session as the primary key for grouping
            $session_key = htmlspecialchars($row['session']);
            
            if (!isset($student_results[$session_key])) {
                $student_results[$session_key] = [
                    'session' => $session_key,
                    'terms' => []
                ];
            }
            // Group further by Term within the Session
            $term_key = htmlspecialchars($row['term']);
            if (!isset($student_results[$session_key]['terms'][$term_key])) {
                 $student_results[$session_key]['terms'][$term_key] = [
                    'term' => $term_key,
                    'subjects' => []
                ];
            }
            $student_results[$session_key]['terms'][$term_key]['subjects'][] = $row;
        }
    } else {
        // If the execution fails here, it is likely due to an issue with data (e.g., integrity constraint)
        $error = "Error retrieving results: " . $stmt_results->error;
    }
    $stmt_results->close();
} else {
    // If preparation fails here, the CRITICAL FIX section might have failed or the 'courses' table is missing
    $error = "Database query preparation failed for results: " . $conn->error;
}


// Function to determine a simple letter grade based on score
function calculateGrade($score) {
    if ($score >= 90) return 'A+';
    if ($score >= 80) return 'A';
    if ($score >= 70) return 'B+';
    if ($score >= 60) return 'B';
    if ($score >= 50) return 'C';
    if ($score >= 40) return 'D';
    return 'F';
}

// Function to get Tailwind class for score color
function getScoreClass($score) {
    if ($score >= 75) return 'bg-green-100 text-green-800';
    if ($score >= 50) return 'bg-yellow-100 text-yellow-800';
    return 'bg-red-100 text-red-800';
}
?>

<h1 class="text-4xl font-extrabold text-gray-800 mb-2">Welcome Back, <?php echo $fullname; ?>!</h1>
<p class="text-gray-600 mb-8">This is your personal Student Dashboard—your academic progress at a glance.</p>


<!-- Dashboard Overview Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">

    <!-- Card 1: Account Status -->
    <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-green-500">
        <div class="flex items-center space-x-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-green-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
            <div>
                <p class="text-sm font-medium text-gray-500 uppercase">Account Status</p>
                <p class="text-2xl font-bold text-gray-900">Active</p>
            </div>
        </div>
    </div>

    <!-- Card 2: Current Class -->
    <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-blue-500">
        <div class="flex items-center space-x-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-blue-500" viewBox="0 0 20 20" fill="currentColor"><path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM10 9a3 3 0 00-3 3v2a3 3 0 003 3h3a3 3 0 003-3v-2a3 3 0 00-3-3h-3z" /></svg>
            <div>
                <p class="text-sm font-medium text-gray-500 uppercase">Current Class</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $class; ?></p>
            </div>
        </div>
    </div>

    <!-- Card 3: Registration Date -->
    <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-yellow-500">
        <div class="flex items-center space-x-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-yellow-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" /></svg>
            <div>
                <p class="text-sm font-medium text-gray-500 uppercase">Member Since</p>
                <p class="text-xl font-bold text-gray-900"><?php echo $registration_date; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Areas -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    <!-- Profile Details Card (Left Column, lg:col-span-1) -->
    <div class="lg:col-span-1 bg-white p-8 rounded-xl shadow-xl h-fit">
        <h2 class="text-2xl font-bold text-gray-800 border-b pb-3 mb-4 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-indigo-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" /></svg>
            Your Profile
        </h2>
        
        <div class="space-y-4">
            <div>
                <p class="text-sm font-medium text-gray-500">Student ID</p>
                <p class="text-gray-800 font-semibold"><?php echo htmlspecialchars($student['id']); ?></p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Username</p>
                <p class="text-gray-800"><?php echo $username; ?></p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Email Address</p>
                <p class="text-gray-800"><?php echo $email; ?></p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Contact Number</p>
                <p class="text-gray-800"><?php echo $phone_number_display; ?></p>
            </div>
            <a href="profile.php" class="inline-block mt-2 text-sm font-medium text-indigo-600 hover:text-indigo-800 transition duration-150">Edit Profile &rarr;</a>
        </div>
    </div>

    <!-- Academic Records Section (Right Column, lg:col-span-2) -->
    <div class="lg:col-span-2 space-y-6">
        
        <div class="bg-white p-8 rounded-xl shadow-xl">
            <h2 class="text-2xl font-bold text-gray-800 border-b pb-3 mb-4 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-teal-500" viewBox="0 0 20 20" fill="currentColor"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" /><path fill-rule="evenodd" d="M4 5a2 2 0 012-2h8a2 2 0 012 2v2h1a1 1 0 110 2h-1v1h1a1 1 0 110 2h-1v1h1a1 1 0 110 2h-1v1a2 2 0 01-2 2H6a2 2 0 01-2-2v-1H3a1 1 0 110-2h1v-1H3a1 1 0 110-2h1V7H3a1 1 0 110-2h1V5zm3 2h6v10H7V7z" clip-rule="evenodd" /></svg>
                My Academic Records
            </h2>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">Error!</strong>
                    <span class="block sm:inline"><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($student_results)): ?>
                <?php 
                // Iterate through results grouped by Academic Session
                foreach ($student_results as $session_data): 
                ?>
                    <div class="mt-6 mb-8 p-4 bg-gray-50 rounded-lg shadow border-l-4 border-indigo-500">
                        <h3 class="text-xl font-bold text-indigo-700 mb-2">
                            Academic Session: <?= $session_data['session'] ?>
                        </h3>
                        
                        <?php 
                        // Iterate through results grouped by Term within the Session
                        foreach ($session_data['terms'] as $term_data): 
                        ?>
                            <div class="mt-4 p-4 bg-white rounded-md shadow-inner border border-gray-200">
                                <h4 class="text-lg font-semibold text-gray-800 mb-3 border-b pb-2">
                                    Term: <?= $term_data['term'] ?>
                                </h4>
                                
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-center">Score (%)</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-center">Grade</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <?php foreach ($term_data['subjects'] as $subject): ?>
                                                <?php 
                                                    $score = $subject['score'];
                                                    $grade = calculateGrade($score);
                                                    $score_class = getScoreClass($score);
                                                ?>
                                                <tr class="hover:bg-gray-50 transition duration-150">
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                        <?= htmlspecialchars($subject['course_name']) ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-center">
                                                        <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full <?= str_replace(['bg-green-100', 'bg-yellow-100', 'bg-red-100'], ['bg-green-50', 'bg-yellow-50', 'bg-red-50'], $score_class) ?> text-gray-900">
                                                            <?= number_format($score, 1) ?>
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                                        <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full <?= $score_class ?>">
                                                            <?= $grade ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; // End Term loop ?>

                    </div>
                <?php endforeach; // End Session loop ?>

            <?php else: ?>
                <div class="bg-gray-100 p-6 rounded-lg border-2 border-dashed border-gray-300 text-center">
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">No Academic Records Available</h3>
                    <p class="text-gray-600">Results will appear here once they are recorded by the administration for your enrolled subjects.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Resources/Links Section (Kept from the original structure) -->
        <div class="bg-white p-8 rounded-xl shadow-xl">
            <h2 class="text-2xl font-bold text-gray-800 border-b pb-3 mb-4 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-pink-500" viewBox="0 0 20 20" fill="currentColor"><path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" /></svg>
                Important Resources
            </h2>
            <ul class="list-disc ml-5 space-y-2 text-gray-700">
                <li><a href="#" class="text-pink-600 hover:underline">Download Class Schedule (PDF)</a></li>
                <li><a href="#" class="text-pink-600 hover:underline">Access Discussion Forums</a></li>
                <li><a href="profile.php" class="text-pink-600 hover:underline">Update Personal & Contact Information</a></li>
                <li><a href="../logout.php" class="text-pink-600 hover:underline">Quick Logout</a></li>
            </ul>
        </div>
    </div>
</div>


<?php
include('includes/footer.php');
?>