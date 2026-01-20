<?php
// org/org_details.php
session_start();
require '../config.php';
require '../functions.php';

if (!isOrg()) {
    redirect('index.php');
}

$org_id = $_SESSION['user_id'];

// Fetch organization details
$stmt = $conn->prepare("SELECT * FROM organizations WHERE id = ?");
$stmt->bind_param("i", $org_id);
$stmt->execute();
$result = $stmt->get_result();
$org = $result->fetch_assoc();
$stmt->close();

if (!$org) {
    echo "Organization not found.";
    exit;
}

// Fetch documents
$docStmt = $conn->prepare("SELECT * FROM org_documents WHERE org_id = ? ORDER BY id DESC");
$docStmt->bind_param("i", $org_id);
$docStmt->execute();
$docs = $docStmt->get_result();
$docStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Profile</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 font-sans antialiased">
    <?php include 'navbar.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h2 class="text-3xl font-bold text-gray-900">Organization Profile</h2>
                <p class="mt-2 text-sm text-gray-600">View your organization information and supporting documents.</p>
            </div>
        </div>

        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Organization Information</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">Details and current profile.</p>
            </div>
            <div class="border-t border-gray-200">
                <dl>
                    <?php if (!empty($org['logo'])): ?>
                        <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500">Logo</dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                <img src="<?php echo htmlspecialchars($org['logo']); ?>" alt="Logo" class="max-w-[150px] border border-gray-200 rounded p-1">
                            </dd>
                        </div>
                    <?php endif; ?>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Name</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo htmlspecialchars($org['name']); ?></dd>
                    </div>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Address</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo nl2br(htmlspecialchars($org['address'])); ?></dd>
                    </div>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Principal Name</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo htmlspecialchars($org['principal_name']); ?></dd>
                    </div>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Owner Name</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo htmlspecialchars($org['owner_name']); ?></dd>
                    </div>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Email</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo htmlspecialchars($org['email']); ?></dd>
                    </div>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Phone</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo htmlspecialchars($org['phone']); ?></dd>
                    </div>
                    <?php if (!empty($org['alt_phone'])): ?>
                        <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500">Alt Phone</dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo htmlspecialchars($org['alt_phone']); ?></dd>
                        </div>
                    <?php endif; ?>
                </dl>
            </div>
        </div>

        <div class="bg-white shadow overflow-hidden sm:rounded-lg mt-8">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Supporting Documents</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">Preview and download, matching student style.</p>
            </div>
            <div class="border-t border-gray-200 p-6">
                <?php if ($docs->num_rows > 0): ?>
                    <ul class="border border-gray-200 rounded-md divide-y divide-gray-200">
                        <?php while($doc = $docs->fetch_assoc()): ?>
                            <?php $ext = strtolower(pathinfo($doc['file_path'], PATHINFO_EXTENSION)); ?>
                            <li class="pl-3 pr-4 py-3 text-sm">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center">
                                            <svg class="flex-shrink-0 h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a5 5 0 0110 0v4a3 3 0 11-6 0V7a1 1 0 012 0v4a1 1 0 102 0V7a3 3 0 00-3-3z" clip-rule="evenodd" />
                                            </svg>
                                            <span class="ml-2 flex-1 w-0 truncate"><?php echo htmlspecialchars(basename($doc['file_path'])); ?></span>
                                        </div>
                                        <div class="mt-3">
                                            <?php if (in_array($ext, ['jpg','jpeg','png','gif','webp'])): ?>
                                                <img src="<?php echo htmlspecialchars($doc['file_path']); ?>" alt="Preview" class="max-h-32 border border-gray-200 rounded">
                                            <?php elseif ($ext === 'pdf'): ?>
                                                <embed src="<?php echo htmlspecialchars($doc['file_path']); ?>" type="application/pdf" class="w-full h-40 border border-gray-200 rounded" />
                                            <?php else: ?>
                                                <p class="text-gray-500">Preview not available for this file type.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0 flex gap-2">
                                        <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" class="font-medium text-teal-600 hover:text-teal-700">View</a>
                                        <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" download class="font-medium text-blue-600 hover:text-blue-500">Download</a>
                                    </div>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-gray-500">No documents uploaded.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
