<?php
// admin/add_org.php
session_start();
require '../config.php';
require '../functions.php';

if (!isAdmin()) {
    redirect('index.php');
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $address = $_POST['address'];
    $principal = $_POST['principal_name'];
    $owner = $_POST['owner_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $alt_phone = $_POST['alt_phone'];
    
    // Generate random password
    $raw_password = bin2hex(random_bytes(4)); // 8 chars
    $hashed_password = password_hash($raw_password, PASSWORD_DEFAULT);

    // Handle Logo Upload
    $logoPath = '';
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $logoPath = uploadFile($_FILES['logo'], '../uploads/');
    }

    // Insert Organization
    $stmt = $conn->prepare("INSERT INTO organizations (name, address, principal_name, owner_name, email, phone, alt_phone, logo, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssss", $name, $address, $principal, $owner, $email, $phone, $alt_phone, $logoPath, $hashed_password);

    if ($stmt->execute()) {
        $org_id = $stmt->insert_id;
        
        // Handle Supporting Documents (Multiple)
        if (isset($_FILES['documents'])) {
            $total = count($_FILES['documents']['name']);
            for( $i=0 ; $i < $total ; $i++ ) {
                $tmpFilePath = $_FILES['documents']['tmp_name'][$i];
                if ($tmpFilePath != ""){
                    $newFilePath = "../uploads/" . uniqid() . '_' . $_FILES['documents']['name'][$i];
                    if(move_uploaded_file($tmpFilePath, $newFilePath)) {
                        $docStmt = $conn->prepare("INSERT INTO org_documents (org_id, file_path) VALUES (?, ?)");
                        $docStmt->bind_param("is", $org_id, $newFilePath);
                        $docStmt->execute();
                    }
                }
            }
        }

        // Send Email with Credentials
        $subject = "Welcome to Attendance App - Your Credentials";
        $message = "Hello $owner,\n\nYour organization has been registered.\n\nLogin Details:\nEmail: $email\nPassword: $raw_password\n\nLogin here: http://localhost/attendanceAppPHP/org/index.php";
        sendEmail($email, $subject, $message);

        $success = "Organization added successfully! Credentials sent to email.";
    } else {
        $error = "Error: " . $stmt->error;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Organization</title>
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
            <h2 class="text-3xl font-bold text-gray-900">Add New Organization</h2>
            <p class="mt-2 text-sm text-gray-600">Register a new organization in the system.</p>
        </div>

        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
                <span class="block sm:inline"><?php echo $success; ?></span>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                <span class="block sm:inline"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <div class="bg-white shadow overflow-hidden sm:rounded-lg p-6">
            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Organization Name</label>
                        <input type="text" name="name" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                        <input type="email" name="email" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Principal Name</label>
                        <input type="text" name="principal_name" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Owner Name</label>
                        <input type="text" name="owner_name" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Phone</label>
                        <input type="text" name="phone" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Alt Phone</label>
                        <input type="text" name="alt_phone" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Address</label>
                    <textarea name="address" required rows="3" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-blue-500"></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Logo</label>
                        <input type="file" name="logo" accept="image/*" id="logoInput" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none p-2">
                        <div id="logoPreviewContainer" class="mt-4 hidden">
                            <p class="text-sm text-gray-600 mb-2">Logo Preview:</p>
                            <img id="logoPreview" src="" class="max-w-[150px] border border-gray-200 rounded p-1">
                        </div>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Supporting Documents (Multiple)</label>
                        <input type="file" name="documents[]" multiple id="docsInput" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none p-2">
                        <div id="docsPreviewContainer" class="mt-4 hidden">
                            <p class="text-sm text-gray-600 mb-2">Selected Files:</p>
                            <ul id="docsList" class="list-none p-0 space-y-2"></ul>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end mt-6">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded focus:outline-none focus:shadow-outline transition">
                        Create Organization
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script>
        // Logo Preview
        document.getElementById('logoInput').addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('logoPreview').src = e.target.result;
                    document.getElementById('logoPreviewContainer').classList.remove('hidden');
                }
                reader.readAsDataURL(file);
            } else {
                document.getElementById('logoPreviewContainer').classList.add('hidden');
            }
        });

        // Documents Preview (List of names + Image preview if applicable)
        document.getElementById('docsInput').addEventListener('change', function(event) {
            const files = event.target.files;
            const list = document.getElementById('docsList');
            const container = document.getElementById('docsPreviewContainer');
            list.innerHTML = '';
            
            if (files.length > 0) {
                container.classList.remove('hidden');
                Array.from(files).forEach(file => {
                    const li = document.createElement('li');
                    li.className = 'flex items-center border-b border-gray-100 pb-2';
                    
                    const nameSpan = document.createElement('span');
                    nameSpan.textContent = file.name;
                    nameSpan.className = 'text-sm text-gray-700';
                    li.appendChild(nameSpan);

                    // If image, show small thumbnail
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            img.className = 'w-8 h-8 ml-3 object-cover rounded';
                            li.appendChild(img);
                        }
                        reader.readAsDataURL(file);
                    }
                    
                    list.appendChild(li);
                });
            } else {
                container.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
