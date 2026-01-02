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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Configure PDF.js worker
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    </script>
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
                <h2 class="text-3xl font-bold text-gray-900"><?php echo htmlspecialchars($org['name']); ?></h2>
                <p class="mt-2 text-sm text-gray-600">Detailed information about the organization.</p>
            </div>
            <a href="edit_org.php?id=<?php echo $id; ?>" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded shadow focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                Edit Organization
            </a>
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
                                <div class="flex flex-col gap-2">
                                    <?php while($doc = $docs->fetch_assoc()): 
                                        $fileName = basename($doc['file_path']);
                                        $filePath = htmlspecialchars($doc['file_path']);
                                    ?>
                                        <div class="flex items-center justify-between p-2 bg-white rounded border border-gray-200 hover:bg-gray-50 transition">
                                            <div class="flex items-center space-x-2 flex-1">
                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                                <span class="text-sm text-gray-700"><?php echo htmlspecialchars($fileName); ?></span>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <button onclick="previewDocument('<?php echo $filePath; ?>', '<?php echo htmlspecialchars($fileName); ?>')" class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center space-x-1 px-2 py-1">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                    <span>Preview</span>
                                                </button>
                                                <button onclick="downloadDocument('<?php echo $filePath; ?>', '<?php echo htmlspecialchars($fileName); ?>')" class="text-teal-600 hover:text-teal-800 text-sm font-medium flex items-center space-x-1 px-2 py-1">
                                                    <span>Download</span>
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-gray-500 italic text-center py-4">No documents uploaded.</p>
                            <?php endif; ?>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>

    <!-- Document Preview Modal -->
    <div id="documentModal" class="fixed z-30 inset-0 overflow-y-auto hidden" aria-labelledby="document-modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-90 transition-opacity" aria-hidden="true" onclick="closeDocumentModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-middle bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-4xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900" id="documentModalTitle">Document Preview</h3>
                        <button onclick="closeDocumentModal()" class="text-gray-400 hover:text-gray-500">
                            <span class="sr-only">Close</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div id="documentPreviewContent" class="max-h-[70vh] overflow-auto">
                        <!-- Content will be populated here -->
                    </div>
                    <div class="mt-4 flex justify-end space-x-3">
                        <a id="documentDownloadLink" href="#" target="_blank" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-teal-600 hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            Download
                        </a>
                    </div>
                </div>
            </div>
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

    // Document Preview and Download Functions
    function previewDocument(filePath, fileName) {
        const modal = document.getElementById('documentModal');
        const content = document.getElementById('documentPreviewContent');
        const title = document.getElementById('documentModalTitle');
        const downloadLink = document.getElementById('documentDownloadLink');

        title.innerText = fileName;
        
        // Set up download link with download attribute
        downloadLink.href = filePath;
        downloadLink.download = fileName;
        
        // Also add onclick handler as backup
        downloadLink.onclick = function(e) {
            e.preventDefault();
            downloadDocument(filePath, fileName);
        };

        // Get file extension
        const fileExt = fileName.split('.').pop().toLowerCase();
        const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
        const pdfExtensions = ['pdf'];

        content.innerHTML = '';

        if (imageExtensions.includes(fileExt)) {
            // Display image
            const img = document.createElement('img');
            img.src = filePath;
            img.alt = fileName;
            img.className = 'w-full h-auto max-h-[70vh] object-contain rounded-lg mx-auto';
            content.appendChild(img);
            modal.classList.remove('hidden');
        } else if (pdfExtensions.includes(fileExt)) {
            // Display PDF using PDF.js
            content.innerHTML = '<div class="flex items-center justify-center py-8"><div class="text-center"><svg class="animate-spin h-12 w-12 text-blue-600 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><p class="mt-4 text-gray-700 font-medium">Loading PDF...</p></div></div>';
            modal.classList.remove('hidden');
            
            // Load PDF using PDF.js
            const loadingTask = pdfjsLib.getDocument(filePath);
            loadingTask.promise.then(function(pdf) {
                content.innerHTML = '<div id="pdfContainer" class="space-y-4"></div>';
                const pdfContainer = document.getElementById('pdfContainer');
                
                // Render all pages
                const numPages = pdf.numPages;
                const renderPromises = [];
                
                for (let pageNum = 1; pageNum <= numPages; pageNum++) {
                    renderPromises.push(
                        pdf.getPage(pageNum).then(function(page) {
                            const viewport = page.getViewport({ scale: 1.5 });
                            const canvas = document.createElement('canvas');
                            const context = canvas.getContext('2d');
                            canvas.height = viewport.height;
                            canvas.width = viewport.width;
                            canvas.className = 'mx-auto border border-gray-300 shadow-sm';
                            
                            const pageDiv = document.createElement('div');
                            pageDiv.className = 'pdf-page';
                            pageDiv.appendChild(canvas);
                            pdfContainer.appendChild(pageDiv);
                            
                            const renderContext = {
                                canvasContext: context,
                                viewport: viewport
                            };
                            return page.render(renderContext).promise;
                        })
                    );
                }
                
                return Promise.all(renderPromises);
            }).catch(function(error) {
                console.error('Error loading PDF:', error);
                content.innerHTML = `
                    <div class="text-center py-8">
                        <svg class="w-16 h-16 text-red-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <p class="text-gray-600 mb-2">Failed to load PDF</p>
                        <p class="text-sm text-gray-500">Click Download to view the file</p>
                    </div>
                `;
            });
        } else {
            // For other file types, show a message with download option
            const message = document.createElement('div');
            message.className = 'text-center py-8';
            message.innerHTML = `
                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <p class="text-gray-600 mb-2">Preview not available for this file type</p>
                <p class="text-sm text-gray-500">Click Download to view the file</p>
            `;
            content.appendChild(message);
            modal.classList.remove('hidden');
        }
    }

    function closeDocumentModal() {
        document.getElementById('documentModal').classList.add('hidden');
        // Clear content when closing
        document.getElementById('documentPreviewContent').innerHTML = '';
    }

    function downloadDocument(filePath, fileName) {
        fetch(filePath)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.blob();
            })
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = fileName;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                window.URL.revokeObjectURL(url);
            })
            .catch(error => {
                // Fallback: open in new tab
                Swal.fire({
                    icon: 'info',
                    title: 'Download Notice',
                    text: 'Direct download failed. Opening file in new tab...',
                    confirmButtonColor: '#0d9488',
                    timer: 2000
                }).then(() => {
                    window.open(filePath, '_blank');
                });
            });
    }
</script>
</html>
