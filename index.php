<?php
// index.php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance & Fee Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-10 rounded-xl shadow-2xl max-w-lg w-full text-center">
        <h1 class="text-3xl font-extrabold text-gray-900 mb-8">Attendance & Fee Management System</h1>
        <div class="space-y-4">
            <a href="admin/index.php" class="block w-full bg-teal-600 hover:bg-teal-700 text-white font-bold py-3 px-4 rounded-lg transition duration-300 transform hover:scale-105 shadow-md">
                Admin Login
            </a>
            <a href="org/index.php" class="block w-full bg-gray-800 hover:bg-gray-900 text-white font-bold py-3 px-4 rounded-lg transition duration-300 transform hover:scale-105 shadow-md">
                Organization Login
            </a>
        </div>
    </div>
</body>
</html>
