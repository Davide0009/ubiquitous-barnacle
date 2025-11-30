<?php
// Start session for all pages
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Real Sensor Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body class="antialiased text-gray-800 bg-gray-50">

    <nav class="bg-white shadow-lg sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">
          <div class="text-blue-600 font-bold text-2xl">Real Sensor Technology</div>
          <div class="hidden md:block">
            <div class="ml-10 flex items-baseline space-x-5">
              <a href="index.php" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100">Home</a>
              <a href="login.php" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100 font-bold">Portal Login</a>
              <a href="register.php" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100">Register</a>
              <?php if (isset($_SESSION['user_role'])): ?>
                <a href="logout.php" class="px-3 py-2 rounded-md text-sm font-medium text-white bg-red-500 hover:bg-red-600">Logout</a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </nav>

    ```

---

    </body>
</html>