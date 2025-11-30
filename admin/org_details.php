<?php
// admin/org_details.php
session_start();
require '../config.php';
require '../functions.php';

if (!isAdmin()) {
    redirect('index.php');
}

if (!isset($_GET['id'])) {
    redirect('dashboard.php');
}

$id = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM organizations WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$org = $result->fetch_assoc();

if (!$org) {
    echo "Organization not found.";
    exit;
}

// Fetch documents
$docStmt = $conn->prepare("SELECT * FROM org_documents WHERE org_id = ?");
$docStmt->bind_param("i", $id);
$docStmt->execute();
$docs = $docStmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Details</title>
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
            <h2 class="text-3xl font-bold text-gray-900"><?php echo htmlspecialchars($org['name']); ?></h2>
            <p class="mt-2 text-sm text-gray-600">Detailed information about the organization.</p>
        </div>

        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Organization Information</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">Personal details and application.</p>
            </div>
            <div class="border-t border-gray-200">
                <dl>
                    <?php if ($org['logo']): ?>
                        <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500">Logo</dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                <img src="<?php echo htmlspecialchars($org['logo']); ?>" alt="Logo" class="max-w-[150px] border border-gray-200 rounded p-1">
                            </dd>
                        </div>
                    <?php endif; ?>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Address</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo nl2br(htmlspecialchars($org['address'])); ?></dd>
                    </div>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Principal Name</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo htmlspecialchars($org['principal_name']); ?></dd>
                    </div>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Owner Name</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo htmlspecialchars($org['owner_name']); ?></dd>
                    </div>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Email</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo htmlspecialchars($org['email']); ?></dd>
                    </div>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Phone</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo htmlspecialchars($org['phone']); ?></dd>
                    </div>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Alt Phone</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo htmlspecialchars($org['alt_phone']); ?></dd>
                    </div>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Registered At</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo $org['created_at']; ?></dd>
                    </div>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Supporting Documents</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <?php if ($docs->num_rows > 0): ?>
                                <ul class="border border-gray-200 rounded-md divide-y divide-gray-200">
                                    <?php while($doc = $docs->fetch_assoc()): ?>
                                        <li class="pl-3 pr-4 py-3 flex items-center justify-between text-sm">
                                            <div class="w-0 flex-1 flex items-center">
                                                <svg class="flex-shrink-0 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a5 5 0 0110 0v4a3 3 0 11-6 0V7a1 1 0 012 0v4a1 1 0 102 0V7a3 3 0 00-3-3z" clip-rule="evenodd" />
                                                </svg>
                                                <span class="ml-2 flex-1 w-0 truncate">
                                                    <?php echo basename($doc['file_path']); ?>
                                                </span>
                                            </div>
                                            <div class="ml-4 flex-shrink-0">
                                                <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" class="font-medium text-blue-600 hover:text-blue-500">
                                                    View
                                                </a>
                                            </div>
                                        </li>
                                    <?php endwhile; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-gray-500">No documents uploaded.</p>
                            <?php endif; ?>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</body>
</html>
