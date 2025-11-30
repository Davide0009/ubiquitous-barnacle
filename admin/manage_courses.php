<?php
// CRITICAL FIX: The database connection and all logic that involves header() redirects
// MUST come before including 'header.php' to prevent the "headers already sent" error.

// NOTE: Please ensure the path below points correctly to your database connection file.
// If your admin folder is directly inside 'school_portal', this path is likely correct.
include('../includes/db.php');
global $conn;

$success = '';
$error = '';
$edit_course = null; 
$page_title = "Manage Courses"; // Default page title

// --- SAFETY CHECK: Create Courses Table if it doesn't exist ---
$create_table_query = "CREATE TABLE IF NOT EXISTS courses (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn && !mysqli_query($conn, $create_table_query)) {
    $error = "Database error: Could not verify/create 'courses' table: " . mysqli_error($conn);
}


// --- 1. Handle Course Deletion (GET) ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    if (!$conn) {
        $error = "Cannot delete: Database connection is unavailable.";
    } else {
        $course_id = $_GET['id'];
        
        $stmt = $conn->prepare("DELETE FROM courses WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $course_id);

        if ($stmt->execute()) {
            $success = "Course deleted successfully!";
            // REDIRECT (header()) is safe here because no output has occurred.
            header("Location: manage_courses.php?success=" . urlencode($success));
            exit();
        } else {
            $error = "Error deleting course: " . $stmt->error;
        }
        $stmt->close();
    }
}

// --- 2. Handle Course Creation (POST) / Update (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_name'])) {
    if (!$conn) {
        $error = "Cannot save: Database connection is unavailable.";
    } else {
        $name = trim($_POST['course_name']);
        $description = trim($_POST['course_description']);
        $price = (float)($_POST['course_price'] ?? 0.00);
        $course_id = $_POST['course_id'] ?? null;

        if (empty($name) || empty($description)) {
            $error = "Course Name and Description are required.";
        } else if ($course_id) {
            // --- UPDATE EXISTING COURSE ---
            $stmt = $conn->prepare("UPDATE courses SET name = ?, description = ?, price = ? WHERE id = ?");
            $stmt->bind_param("ssdi", $name, $description, $price, $course_id);

            if ($stmt->execute()) {
                $success = "Course **" . htmlspecialchars($name) . "** updated successfully!";
                // REDIRECT (header()) is safe here.
                header("Location: manage_courses.php?success=" . urlencode($success));
                exit();
            } else {
                $error = "Error updating course: " . $stmt->error;
            }
            $stmt->close();
        } else {
            // --- CREATE NEW COURSE ---
            $stmt = $conn->prepare("INSERT INTO courses (name, description, price) VALUES (?, ?, ?)");
            $stmt->bind_param("ssd", $name, $description, $price);

            if ($stmt->execute()) {
                $success = "New course **" . htmlspecialchars($name) . "** created successfully!";
                // REDIRECT (header()) is safe here.
                header("Location: manage_courses.php?success=" . urlencode($success));
                exit();
            } else {
                if ($conn->errno == 1062) {
                    $error = "Error: A course with the name '" . htmlspecialchars($name) . "' already exists.";
                } else {
                    $error = "Error creating course: " . $stmt->error;
                }
            }
            $stmt->close();
        }
    }
}

// --- 3. Handle Course Editing (GET) - Load data into form ---
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    if (!$conn) {
        $error = "Cannot load course: Database connection is unavailable.";
    } else {
        $course_id = $_GET['id'];
        $stmt = $conn->prepare("SELECT id, name, description, price FROM courses WHERE id = ?");
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $edit_course = $result->fetch_assoc();
            $page_title = "Edit Course: " . htmlspecialchars($edit_course['name']);
        } else {
            $error = "Course not found.";
        }
        $stmt->close();
    }
}

// --- Now include the header, as all redirecting logic is complete ---
// This file includes the authentication check and starts the HTML output.
include('includes/header.php'); 


// --- 4. Fetch All Courses (READ) ---
$courses = [];
if ($conn) {
    // Note: We use mysqli_query here as we are only reading and there is no user input involved.
    $courses_result = mysqli_query($conn, "SELECT id, name, description, price FROM courses ORDER BY name ASC");
    if ($courses_result) {
        while ($row = mysqli_fetch_assoc($courses_result)) {
            $courses[] = $row;
        }
    } else {
        $error .= " Could not fetch courses: " . mysqli_error($conn);
    }
}

// Check for and display messages from URL redirect (if any)
if (isset($_GET['success'])) {
    $success = htmlspecialchars($_GET['success']);
}
if (isset($_GET['error'])) {
    // If an error was appended during fetching, show it. Otherwise, URL error is preferred.
    if (!empty($error)) {
        $error = htmlspecialchars($_GET['error']) . " " . $error;
    } else {
        $error = htmlspecialchars($_GET['error']);
    }
}

?>

<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-6">💻 <?= $page_title ?></h1>

    <?php if ($success): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded-lg shadow-md" role="alert">
            <p class="font-bold">Success!</p>
            <p><?= $success ?></p>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded-lg shadow-md" role="alert">
            <p class="font-bold">Error!</p>
            <p><?= $error ?></p>
        </div>
    <?php endif; ?>

    <!-- Course Management Form (Create/Edit) -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-8 p-6 border border-gray-100">
        <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">
            <?= $edit_course ? 'Edit Course: ' . htmlspecialchars($edit_course['name']) : 'Add New Course' ?>
        </h2>
        <form action="manage_courses.php" method="POST">
            <?php if ($edit_course): ?>
                <input type="hidden" name="course_id" value="<?= htmlspecialchars($edit_course['id']) ?>">
            <?php endif; ?>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label for="course_name" class="block text-sm font-medium text-gray-700">Course Name (e.g., Advanced Python)</label>
                    <input type="text" name="course_name" id="course_name" required
                           value="<?= htmlspecialchars($edit_course['name'] ?? '') ?>"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label for="course_price" class="block text-sm font-medium text-gray-700">Price (USD)</label>
                    <input type="number" step="0.01" name="course_price" id="course_price" required
                           value="<?= htmlspecialchars($edit_course['price'] ?? 0.00) ?>"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
            </div>

            <div class="mt-4">
                <label for="course_description" class="block text-sm font-medium text-gray-700">Description</label>
                <textarea name="course_description" id="course_description" rows="3" required
                          class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"><?= htmlspecialchars($edit_course['description'] ?? '') ?></textarea>
            </div>

            <div class="mt-6">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150">
                    <?= $edit_course ? '💾 Save Changes' : '➕ Add Course' ?>
                </button>
                <?php if ($edit_course): ?>
                    <a href="manage_courses.php" class="ml-4 text-sm font-medium text-gray-600 hover:text-gray-900 transition duration-150">Cancel Edit</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Course List -->
    <h2 class="text-2xl font-semibold text-gray-900 mb-4 border-b pb-2">All Courses (<?= count($courses) ?>)</h2>
    
    <?php if (empty($courses)): ?>
        <p class="text-gray-500">No courses have been added yet.</p>
    <?php else: ?>
        <div class="overflow-x-auto shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-300">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course Name</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price (USD)</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description Snippet</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($courses as $course): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($course['name']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$<?= number_format($course['price'], 2) ?></td>
                        <td class="px-6 py-4 text-sm text-gray-500 max-w-xs" title="<?= htmlspecialchars($course['description']) ?>">
                            <?= substr(htmlspecialchars($course['description']), 0, 80) . (strlen($course['description']) > 80 ? '...' : '') ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="manage_courses.php?action=edit&id=<?= $course['id'] ?>" 
                               class="text-indigo-600 hover:text-indigo-900 mr-4 transition duration-150">Edit</a>
                            <a href="manage_courses.php?action=delete&id=<?= $course['id'] ?>" 
                               onclick="return confirm('Are you sure you want to delete the course: <?= htmlspecialchars($course['name']) ?>? This action cannot be undone.');"
                               class="text-red-600 hover:text-red-900 transition duration-150">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
include('includes/footer.php');
?>