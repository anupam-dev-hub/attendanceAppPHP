<?php
// admin/edit_org.php
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
$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $address = $_POST['address'];
    $principal = $_POST['principal_name'];
    $owner = $_POST['owner_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $alt_phone = $_POST['alt_phone'];
    
    // Handle logo upload
    $logoPath = $_POST['existing_logo'];
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $newLogo = uploadFile($_FILES['logo'], '../uploads/');
        if ($newLogo) {
            $logoPath = $newLogo;
        }
    }

    // Update organization
    $stmt = $conn->prepare("UPDATE organizations SET name = ?, address = ?, principal_name = ?, owner_name = ?, email = ?, phone = ?, alt_phone = ?, logo = ? WHERE id = ?");
    $stmt->bind_param("ssssssssi", $name, $address, $principal, $owner, $email, $phone, $alt_phone, $logoPath, $id);

    if ($stmt->execute()) {
        // Handle new supporting documents upload
        if (isset($_FILES['documents'])) {
            $total = count($_FILES['documents']['name']);
            for ($i = 0; $i < $total; $i++) {
                $tmpFilePath = $_FILES['documents']['tmp_name'][$i];
                if ($tmpFilePath != "") {
                    $newFilePath = "../uploads/" . uniqid() . '_' . $_FILES['documents']['name'][$i];
                    if (move_uploaded_file($tmpFilePath, $newFilePath)) {
                        $docStmt = $conn->prepare("INSERT INTO org_documents (org_id, file_path) VALUES (?, ?)");
                        $docStmt->bind_param("is", $id, $newFilePath);
                        $docStmt->execute();
                        $docStmt->close();
                    }
                }
            }
        }
        $success = "Organization updated successfully!";
    } else {
        $error = "Error updating organization: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch organization details
$stmt = $conn->prepare("SELECT * FROM organizations WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$org = $result->fetch_assoc();

if (!$org) {
    echo "Organization not found.";
    exit;
}

// Fetch existing documents
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
    <title>Edit Organization</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .dropdown { position: relative; display: inline-block; }
        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #fff;
            min-width: 200px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1000;
            border-radius: 0.375rem;
            margin-top: 0.5rem;
            top: 100%;
            right: 0;
        }
        .dropdown-content a {
            color: #1f2937;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            transition: background-color 0.2s;
            font-size: 0.875rem;
        }
        .dropdown-content a:hover { background-color: #f3f4f6; }
        .dropdown:hover .dropdown-content { display: block; }
        .dropdown.open .dropdown-content { display: block; }
        .dropdown-btn { cursor: pointer; }
    </style>
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
                    <div class="dropdown">
                        <span class="dropdown-btn text-white hover:text-blue-100 font-medium transition">
                            Settings ▾
                        </span>
                        <div class="dropdown-content">
                            <a href="settings.php">Payment Settings</a>
                            <a href="contact_settings.php">Contact Details</a>
                        </div>
                    </div>
                    <a href="../logout.php" class="text-white hover:text-red-200 font-medium transition">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h2 class="text-3xl font-bold text-gray-900">Edit Organization</h2>
                <p class="mt-2 text-sm text-gray-600">Update organization details.</p>
            </div>
            <a href="org_details.php?id=<?php echo $id; ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Cancel
            </a>
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
                <input type="hidden" name="existing_logo" value="<?php echo htmlspecialchars($org['logo']); ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Organization Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($org['name']); ?>" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($org['email']); ?>" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Principal Name</label>
                        <input type="text" name="principal_name" value="<?php echo htmlspecialchars($org['principal_name']); ?>" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Owner Name</label>
                        <input type="text" name="owner_name" value="<?php echo htmlspecialchars($org['owner_name']); ?>" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Phone</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($org['phone']); ?>" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Alt Phone</label>
                        <input type="text" name="alt_phone" value="<?php echo htmlspecialchars($org['alt_phone']); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Address</label>
                    <textarea name="address" rows="3" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($org['address']); ?></textarea>
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Current Logo</label>
                    <?php if ($org['logo']): ?>
                        <div class="mt-2 mb-4">
                            <img src="<?php echo htmlspecialchars($org['logo']); ?>" alt="Current Logo" class="max-w-xs border border-gray-200 rounded shadow-sm">
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500 text-sm mb-4">No logo uploaded.</p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Upload New Logo (optional)</label>
                    <input class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none p-2" type="file" name="logo" accept="image/*" id="logoInput" onchange="previewLogo(event)">
                    <p class="mt-1 text-sm text-gray-500">Leave empty to keep current logo.</p>
                    <div id="logoPreview" class="mt-4 hidden">
                        <p class="text-sm text-gray-600 mb-2">New Logo Preview:</p>
                        <img id="logoPreviewImg" src="" alt="Logo Preview" class="max-w-xs border border-gray-200 rounded shadow-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Supporting Documents</label>
                    
                    <?php if ($docs->num_rows > 0): ?>
                        <div class="mb-4">
                            <p class="text-sm text-gray-600 mb-3">Existing Documents:</p>
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
                                                    <span class="ml-2 flex-1 w-0 truncate">
                                                        <?php echo basename($doc['file_path']); ?>
                                                    </span>
                                                </div>
                                                <div class="mt-3">
                                                    <?php if (in_array($ext, ['jpg','jpeg','png','gif','webp'])): ?>
                                                        <img src="<?php echo htmlspecialchars($doc['file_path']); ?>" alt="Preview" class="max-h-32 border border-gray-200 rounded shadow-sm">
                                                    <?php elseif ($ext === 'pdf'): ?>
                                                        <embed src="<?php echo htmlspecialchars($doc['file_path']); ?>" type="application/pdf" class="w-full h-40 border border-gray-200 rounded" />
                                                    <?php else: ?>
                                                        <p class="text-gray-500">Preview not available for this file type.</p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="flex-shrink-0 flex gap-2">
                                                <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" download class="font-medium text-blue-600 hover:text-blue-500">Download</a>
                                                <button type="button" onclick="deleteDocument(<?php echo $doc['id']; ?>)" class="font-medium text-red-600 hover:text-red-500">Delete</button>
                                            </div>
                                        </div>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500 text-sm mb-4">No documents uploaded.</p>
                    <?php endif; ?>
                    
                    <label class="block text-gray-700 text-sm font-bold mb-2 mt-4">Upload New Documents (optional)</label>
                    <input class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none p-2" type="file" name="documents[]" multiple id="docsInput" onchange="previewDocuments(event)">
                    <p class="mt-1 text-sm text-gray-500">You can select multiple files.</p>
                    <div id="docsPreview" class="mt-4 hidden">
                        <p class="text-sm text-gray-600 mb-2">New Files Selected (images/PDF only shown):</p>
                        <div id="docsPreviewGrid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4"></div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="org_details.php?id=<?php echo $id; ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        Cancel
                    </a>
                    <button class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded shadow focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" type="submit">
                        Update Organization
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const dropdowns = document.querySelectorAll('.dropdown');
        const closeAll = () => dropdowns.forEach(d => d.classList.remove('open'));
        const openDropdown = (dd) => {
            closeAll();
            dd.classList.add('open');
        };

        dropdowns.forEach(dd => {
            const btn = dd.querySelector('.dropdown-btn');
            const content = dd.querySelector('.dropdown-content');
            if (!btn || !content) return;

            btn.addEventListener('mouseenter', function() {
                openDropdown(dd);
            });

            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const isOpen = dd.classList.contains('open');
                if (isOpen) {
                    closeAll();
                } else {
                    openDropdown(dd);
                }
            });

            content.addEventListener('click', function(e) {
                e.stopPropagation();
                closeAll();
            });
        });

        document.addEventListener('click', function() {
            closeAll();
        });
    });
    
    // Logo preview function
    function previewLogo(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('logoPreviewImg').src = e.target.result;
                document.getElementById('logoPreview').classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        } else {
            document.getElementById('logoPreview').classList.add('hidden');
        }
    }
    
    // Manage selected files with DataTransfer to allow deletions
    let docsDT = new DataTransfer();

    // Documents preview function (overwrites current selection)
    function previewDocuments(event) {
        const input = document.getElementById('docsInput');
        docsDT = new DataTransfer();
        const files = event.target.files;
        for (let i = 0; i < files.length; i++) {
            docsDT.items.add(files[i]);
        }
        input.files = docsDT.files;
        renderSelectedDocs();
    }

    function renderSelectedDocs() {
        const grid = document.getElementById('docsPreviewGrid');
        grid.innerHTML = '';

        const isImage = (type) => type.startsWith('image/');
        const isPdf = (type, name) => type === 'application/pdf' || name.toLowerCase().endsWith('.pdf');

        const files = docsDT.files;
        if (files.length > 0) {
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const card = document.createElement('div');
                card.className = 'border border-gray-200 rounded p-2 bg-white shadow-sm';

                const header = document.createElement('div');
                header.className = 'flex items-center justify-between mb-2';
                const title = document.createElement('p');
                title.className = 'text-xs text-gray-600 truncate';
                title.textContent = file.name;
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'text-xs text-red-600 hover:text-red-700 font-semibold';
                removeBtn.textContent = 'Remove';
                removeBtn.onclick = () => removeSelectedFile(i);
                header.appendChild(title);
                header.appendChild(removeBtn);
                card.appendChild(header);

                if (isImage(file.type)) {
                    const img = document.createElement('img');
                    img.className = 'max-h-32 w-auto border border-gray-100 rounded';
                    const reader = new FileReader();
                    reader.onload = (e) => { img.src = e.target.result; };
                    reader.readAsDataURL(file);
                    card.appendChild(img);
                } else if (isPdf(file.type, file.name)) {
                    const url = URL.createObjectURL(file);
                    const embed = document.createElement('embed');
                    embed.src = url;
                    embed.type = 'application/pdf';
                    embed.className = 'w-full h-40 border border-gray-100 rounded';
                    card.appendChild(embed);
                } else {
                    const note = document.createElement('p');
                    note.className = 'text-xs text-gray-500';
                    note.textContent = 'Preview not available (only images/PDFs shown).';
                    card.appendChild(note);
                }

                grid.appendChild(card);
            }
            document.getElementById('docsPreview').classList.remove('hidden');
        } else {
            document.getElementById('docsPreview').classList.add('hidden');
        }
    }

    function removeSelectedFile(index) {
        const input = document.getElementById('docsInput');
        const newDT = new DataTransfer();
        for (let i = 0; i < docsDT.files.length; i++) {
            if (i !== index) {
                newDT.items.add(docsDT.files[i]);
            }
        }
        docsDT = newDT;
        input.files = docsDT.files;
        renderSelectedDocs();
    }
    
    // Delete document function
    function deleteDocument(docId) {
        if (!confirm('Are you sure you want to delete this document?')) {
            return;
        }
        
        fetch('delete_org_document.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: docId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error deleting document: ' + data.error);
            }
        })
        .catch(error => {
            alert('Error: ' + error);
        });
    }
</script>
</html>
