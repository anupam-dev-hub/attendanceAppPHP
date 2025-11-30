<?php
// admin/dashboard.php
session_start();
require '../config.php';
require '../functions.php';

if (!isAdmin()) {
    redirect('index.php');
}

// Fetch organizations
$sql = "SELECT * FROM organizations ORDER BY created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 font-sans antialiased">
    <nav class="bg-blue-600 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="dashboard.php" class="text-white text-xl font-bold tracking-wide">Admin Panel</a>
                </div>
                <div class="flex items-center space-x-6">
                    <a href="dashboard.php" class="text-white hover:text-blue-100 font-medium transition">Dashboard</a>
                    <a href="add_org.php" class="text-white hover:text-blue-100 font-medium transition">Add Organization</a>
                    <a href="subscriptions.php" class="text-white hover:text-blue-100 font-medium transition relative">
                        Subscriptions
                        <?php 
                        $pendingCount = getPendingSubscriptionCount();
                        if ($pendingCount > 0): 
                        ?>
                            <span class="absolute -top-2 -right-4 inline-flex items-center justify-center px-1.5 py-0.5 border border-yellow-400 rounded-full text-[10px] font-bold bg-gray-900 text-yellow-400 shadow-sm">
                                <?php echo $pendingCount; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <a href="settings.php" class="text-white hover:text-blue-100 font-medium transition">Settings</a>
                    <a href="../logout.php" class="text-white hover:text-red-200 font-medium transition">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-900">Registered Organizations</h2>
            <p class="mt-2 text-sm text-gray-600">A list of all organizations registered in the system.</p>
        </div>

        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <?php if ($result->num_rows > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $row['id']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($row['owner_name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($row['phone']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="org_details.php?id=<?php echo $row['id']; ?>" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-teal-600 hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 transition">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="p-6 text-center text-gray-500">
                    No organizations found.
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
