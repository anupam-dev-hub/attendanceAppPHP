// org/js/students.js

$(document).ready(function () {
    var table = $('#studentsTable').DataTable({
        responsive: true,
        "pageLength": 10,
        "order": [
            [2, "asc"],
            [4, "asc"]
        ], // Sort by Class (col 2) then Roll No (col 4)
        "columnDefs": [{
            "orderable": false,
            "targets": [1, 6, 7, 8]
        } // Photo, Status, QR Code, and Actions columns not sortable
        ]
    });

    // Class filter (column 2)
    $('#classFilter').on('change', function () {
        table.column(2).search(this.value).draw();
    });

    // Batch filter (column 3)
    $('#batchFilter').on('change', function () {
        table.column(3).search(this.value).draw();
    });

    // Status filter (custom search)
    $.fn.dataTable.ext.search.push(
        function (settings, data, dataIndex) {
            var statusFilter = $('#statusFilter').val();
            if (statusFilter === '') {
                return true; // Show all
            }
            var row = table.row(dataIndex).node();
            var rowStatus = $(row).attr('data-status');
            return rowStatus === statusFilter;
        }
    );

    $('#statusFilter').on('change', function () {
        table.draw();
    });
});

// Form submission with loading animation
document.getElementById('studentForm').addEventListener('submit', function (e) {
    const submitBtn = document.getElementById('submitBtn');
    const submitBtnText = document.getElementById('submitBtnText');
    const submitBtnSpinner = document.getElementById('submitBtnSpinner');

    // Show loading state
    submitBtn.disabled = true;
    submitBtnText.textContent = 'Saving...';
    submitBtnSpinner.classList.remove('hidden');
});

function openAddModal() {
    // Hide loading overlay
    document.getElementById('modalLoadingOverlay').classList.add('hidden');
    document.getElementById('modalTitle').innerText = 'Add New Student';
    document.getElementById('studentForm').reset();
    document.getElementById('studentId').value = '';

    // Clear photo preview
    document.getElementById('photoPreviewImg').src = '';
    document.getElementById('photoPreview').classList.add('hidden');

    // Clear documents preview
    document.getElementById('existingDocumentsList').innerHTML = '';
    document.getElementById('existingDocumentsPreview').classList.add('hidden');
    document.getElementById('newDocumentsList').innerHTML = '';
    document.getElementById('newDocumentsPreview').classList.add('hidden');

    // Reset file inputs
    document.getElementById('studentPhoto').value = '';
    document.getElementById('studentDocuments').value = '';

    document.getElementById('studentModal').classList.remove('hidden');
    // Reset button state
    const submitBtn = document.getElementById('submitBtn');
    const submitBtnText = document.getElementById('submitBtnText');
    const submitBtnSpinner = document.getElementById('submitBtnSpinner');
    submitBtn.disabled = false;
    submitBtnText.textContent = 'Save Student';
    submitBtnSpinner.classList.add('hidden');
}

function openEditModal(student) {
    // Show modal first
    document.getElementById('studentModal').classList.remove('hidden');

    // Show loading overlay
    const loadingOverlay = document.getElementById('modalLoadingOverlay');
    loadingOverlay.classList.remove('hidden');

    // Populate basic student data
    document.getElementById('modalTitle').innerText = 'Edit Student';
    document.getElementById('studentId').value = student.id;
    document.getElementById('studentName').value = student.name;
    document.getElementById('studentClass').value = student.class || '';
    document.getElementById('studentBatch').value = student.batch || '2025-2026';
    document.getElementById('studentRoll').value = student.roll_number || '';
    document.getElementById('studentPhone').value = student.phone;
    document.getElementById('studentEmail').value = student.email || '';
    document.getElementById('studentAddress').value = student.address || '';
    document.getElementById('studentAdmission').value = student.admission_amount || '0.00';
    document.getElementById('studentFee').value = student.fee || '0.00';
    document.getElementById('studentIsActive').checked = student.is_active == 1;
    document.getElementById('studentRemark').value = student.remark || '';

    // Reset button state
    const submitBtn = document.getElementById('submitBtn');
    const submitBtnText = document.getElementById('submitBtnText');
    const submitBtnSpinner = document.getElementById('submitBtnSpinner');
    submitBtn.disabled = false;
    submitBtnText.textContent = 'Save Student';
    submitBtnSpinner.classList.add('hidden');

    // Show existing photo preview if available
    if (student.photo) {
        document.getElementById('photoPreviewImg').src = student.photo;
        document.getElementById('photoPreview').classList.remove('hidden');
    } else {
        document.getElementById('photoPreview').classList.add('hidden');
    }

    // Fetch documents
    fetch('get_student_documents.php?student_id=' + student.id)
        .then(response => response.json())
        .then(data => {
            console.log('Documents API response:', data);
            if (data.success && data.documents && data.documents.length > 0) {
                const documentsList = document.getElementById('existingDocumentsList');
                documentsList.innerHTML = '';
                data.documents.forEach(doc => {
                    const li = document.createElement('li');
                    li.className = 'flex items-center justify-between py-2 border-b border-gray-200 last:border-b-0';

                    // Create container for thumbnail and name
                    const docInfo = document.createElement('div');
                    docInfo.className = 'flex items-center space-x-3 flex-1';

                    // Check file type and create appropriate preview
                    const fileExt = doc.file_name.split('.').pop().toLowerCase();
                    if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExt)) {
                        // Image thumbnail
                        const img = document.createElement('img');
                        img.src = doc.file_path;
                        img.className = 'w-16 h-16 object-cover rounded border border-gray-300';
                        img.alt = doc.file_name;
                        docInfo.appendChild(img);
                    } else if (fileExt === 'pdf') {
                        // PDF thumbnail - load and render first page
                        const canvas = document.createElement('canvas');
                        canvas.className = 'w-16 h-16 object-cover rounded border border-gray-300';
                        docInfo.appendChild(canvas);

                        // Load PDF and render first page
                        fetch(doc.file_path)
                            .then(response => response.arrayBuffer())
                            .then(data => {
                                const loadingTask = pdfjsLib.getDocument({ data: data });
                                loadingTask.promise.then(function (pdf) {
                                    pdf.getPage(1).then(function (page) {
                                        const scale = 0.5;
                                        const viewport = page.getViewport({ scale: scale });
                                        const context = canvas.getContext('2d');
                                        canvas.height = viewport.height;
                                        canvas.width = viewport.width;
                                        page.render({ canvasContext: context, viewport: viewport });
                                    });
                                });
                            })
                            .catch(error => {
                                console.error('Error loading PDF:', error);
                                // Fallback to icon
                                canvas.remove();
                                const icon = document.createElement('div');
                                icon.innerHTML = '<svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>';
                                docInfo.insertBefore(icon, docInfo.firstChild);
                            });
                    } else {
                        // Generic file icon
                        const icon = document.createElement('div');
                        icon.innerHTML = '<svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>';
                        docInfo.appendChild(icon);
                    }

                    // Add filename link
                    const link = document.createElement('a');
                    link.href = doc.file_path;
                    link.target = '_blank';
                    link.className = 'text-blue-600 hover:underline text-sm truncate flex-1';
                    link.textContent = doc.file_name;
                    docInfo.appendChild(link);

                    li.appendChild(docInfo);

                    // Add delete button
                    const deleteBtn = document.createElement('button');
                    deleteBtn.type = 'button';
                    deleteBtn.className = 'ml-2 text-red-600 hover:text-red-800 transition';
                    deleteBtn.title = 'Delete document';
                    deleteBtn.onclick = function (e) { deleteDocument(doc.id, student.id, e); };
                    deleteBtn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>';
                    li.appendChild(deleteBtn);

                    documentsList.appendChild(li);
                });
                document.getElementById('existingDocumentsPreview').classList.remove('hidden');
            } else {
                console.log('No documents found or error:', data.message || 'No documents');
                document.getElementById('existingDocumentsPreview').classList.add('hidden');
            }
        })
        .catch(error => {
            console.error('Error fetching documents:', error);
            document.getElementById('existingDocumentsPreview').classList.add('hidden');
        })
        .finally(() => {
            // Hide loading overlay after documents are loaded
            loadingOverlay.classList.add('hidden');
        });
}

function closeModal() {
    document.getElementById('studentModal').classList.add('hidden');
    // Hide loading overlay if visible
    document.getElementById('modalLoadingOverlay').classList.add('hidden');

    // Clear photo preview
    document.getElementById('photoPreviewImg').src = '';
    document.getElementById('photoPreview').classList.add('hidden');

    // Clear documents preview
    document.getElementById('existingDocumentsList').innerHTML = '';
    document.getElementById('existingDocumentsPreview').classList.add('hidden');
    document.getElementById('newDocumentsList').innerHTML = '';
    document.getElementById('newDocumentsPreview').classList.add('hidden');
    // document.getElementById('documentsPreviewTitle').innerText = 'Selected Files:'; // Removed as titles are now static

    // Reset file inputs
    document.getElementById('studentPhoto').value = '';
    document.getElementById('studentDocuments').value = '';

    // Reset button state
    const submitBtn = document.getElementById('submitBtn');
    const submitBtnText = document.getElementById('submitBtnText');
    const submitBtnSpinner = document.getElementById('submitBtnSpinner');
    submitBtn.disabled = false;
    submitBtnText.textContent = 'Save Student';
    submitBtnSpinner.classList.add('hidden');
}

// QR Code Functions
let currentQRData = '';
let currentQRFilename = '';

function generateQR(studentId, studentName, batch, rollNumber, studentClass) {
    currentQRData = studentId;
    // Format: student batch-roll_number-class
    // Example: student 2025-2026-101-10A
    currentQRFilename = `QR-${studentName}-${batch}-${rollNumber}-${studentClass}`;

    // Clear previous QR code
    document.getElementById('qrcode').innerHTML = '';

    // Generate new QR code
    new QRCode(document.getElementById('qrcode'), {
        text: studentId,
        width: 256,
        height: 256,
        colorDark: '#000000',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.H
    });

    // Update modal content
    document.getElementById('qrStudentName').innerText = 'Student: ' + studentName;
    document.getElementById('qrData').innerText = 'QR Data: ' + studentId;

    // Show modal
    document.getElementById('qrModal').classList.remove('hidden');
}

function closeQRModal() {
    document.getElementById('qrModal').classList.add('hidden');
}

function downloadQR() {
    const canvas = document.querySelector('#qrcode canvas');
    if (canvas) {
        const url = canvas.toDataURL('image/png');
        const link = document.createElement('a');
        link.download = currentQRFilename + '.png';
        link.href = url;
        link.click();
    }
}

// File Preview Functions
function previewPhoto(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function (e) {
            document.getElementById('photoPreviewImg').src = e.target.result;
            document.getElementById('photoPreview').classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    } else {
        document.getElementById('photoPreview').classList.add('hidden');
    }
}

function previewDocuments(event) {
    const files = event.target.files;
    const list = document.getElementById('newDocumentsList');
    const preview = document.getElementById('newDocumentsPreview');

    list.innerHTML = '';

    if (files && files.length > 0) {
        preview.classList.remove('hidden');
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const li = document.createElement('li');
            li.className = 'flex items-center space-x-3 py-2 border-b border-gray-200 last:border-b-0';

            // Check if file is an image
            if (file.type.startsWith('image/')) {
                // Create image thumbnail
                const reader = new FileReader();
                reader.onload = function (e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'w-16 h-16 object-cover rounded border border-gray-300';
                    img.alt = file.name;

                    const textSpan = document.createElement('span');
                    textSpan.className = 'text-gray-700 text-sm truncate flex-1';
                    textSpan.textContent = file.name;

                    li.appendChild(img);
                    li.appendChild(textSpan);
                };
                reader.readAsDataURL(file);
            } else if (file.type === 'application/pdf') {
                // Create PDF thumbnail
                const reader = new FileReader();
                reader.onload = function (e) {
                    const loadingTask = pdfjsLib.getDocument({ data: e.target.result });
                    loadingTask.promise.then(function (pdf) {
                        // Get first page
                        pdf.getPage(1).then(function (page) {
                            const scale = 0.5;
                            const viewport = page.getViewport({ scale: scale });

                            const canvas = document.createElement('canvas');
                            const context = canvas.getContext('2d');
                            canvas.height = viewport.height;
                            canvas.width = viewport.width;
                            canvas.className = 'w-16 h-16 object-cover rounded border border-gray-300';

                            const renderContext = {
                                canvasContext: context,
                                viewport: viewport
                            };

                            page.render(renderContext).promise.then(function () {
                                const textSpan = document.createElement('span');
                                textSpan.className = 'text-gray-700 text-sm truncate flex-1';
                                textSpan.textContent = file.name;

                                li.appendChild(canvas);
                                li.appendChild(textSpan);
                            });
                        });
                    }).catch(function (error) {
                        console.error('Error loading PDF:', error);
                        // Fallback to icon if PDF loading fails
                        const icon = '<svg class="w-8 h-8 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>';
                        li.innerHTML = `${icon}<span class="text-gray-700 text-sm truncate flex-1">${file.name}</span>`;
                    });
                };
                reader.readAsArrayBuffer(file);
            } else {
                // Show icon for other file types
                const icon = '<svg class="w-8 h-8 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>';
                li.innerHTML = `${icon}<span class="text-gray-700 text-sm truncate flex-1">${file.name}</span>`;
            }

            list.appendChild(li);
        }
    } else {
        preview.classList.add('hidden');
    }
}

// Delete Document Function
function deleteDocument(documentId, studentId, event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    Swal.fire({
        title: 'Delete Document?',
        text: "This action cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        allowOutsideClick: false,
        allowEscapeKey: true
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('document_id', documentId);

            // Show loading state
            Swal.fire({
                title: 'Deleting...',
                text: 'Please wait',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('delete_student_document.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: 'Document has been deleted successfully',
                            confirmButtonColor: '#0d9488',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            // Refresh the document list AFTER user clicks OK
                            fetch('get_student_documents.php?student_id=' + studentId)
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success && data.documents && data.documents.length > 0) {
                                        const documentsList = document.getElementById('documentsList');
                                        documentsList.innerHTML = '';
                                        data.documents.forEach(doc => {
                                            const li = document.createElement('li');
                                            li.className = 'flex items-center justify-between py-1';
                                            li.innerHTML = `
                                            <a href="${doc.file_path}" target="_blank" class="text-blue-600 hover:underline flex-1">${doc.file_name}</a>
                                            <button type="button" onclick="deleteDocument(${doc.id}, ${studentId}, event)" class="ml-2 text-red-600 hover:text-red-800 transition" title="Delete document">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        `;
                                            documentsList.appendChild(li);
                                        });
                                        document.getElementById('documentsPreview').classList.remove('hidden');
                                    } else {
                                        document.getElementById('documentsPreview').classList.add('hidden');
                                    }
                                });
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: data.message || 'Failed to delete document'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'An error occurred while deleting the document'
                    });
                });
        }
    });
}

function formatFileSize(bytes) {

    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

// Photo View Functions
function viewPhoto(photoPath, studentName) {
    document.getElementById('photoViewImg').src = photoPath;
    document.getElementById('photoStudentName').innerText = studentName;
    document.getElementById('photoModal').classList.remove('hidden');
}

function closePhotoModal() {
    document.getElementById('photoModal').classList.add('hidden');
}

// Student Info View Modal Functions
let currentViewStudent = null;
let attendanceChartInstance = null;

function switchTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('[role="tabpanel"]').forEach(el => {
        el.classList.add('hidden');
        el.classList.remove('block');
    });

    // Reset tab buttons
    document.querySelectorAll('[role="tab"]').forEach(el => {
        el.classList.remove('text-teal-600', 'border-teal-600', 'active-tab');
        el.classList.add('text-gray-500', 'border-transparent');
    });

    // Show selected tab
    document.getElementById(tabName).classList.remove('hidden');
    document.getElementById(tabName).classList.add('block');

    // Highlight selected button
    const btn = document.getElementById(tabName + '-tab');
    btn.classList.remove('text-gray-500', 'border-transparent');
    btn.classList.add('text-teal-600', 'border-teal-600', 'active-tab');
}

function viewStudent(student) {
    currentViewStudent = student;

    // Show modal first
    document.getElementById('viewModal').classList.remove('hidden');

    // Show loading overlay
    const loadingOverlay = document.getElementById('viewModalLoadingOverlay');
    loadingOverlay.classList.remove('hidden');

    // Populate student details
    document.getElementById('viewName').innerText = student.name || '-';
    document.getElementById('viewRoll').innerText = student.roll_number || 'Not Assigned';
    document.getElementById('viewPhone').innerText = student.phone || '-';
    document.getElementById('viewEmail').innerText = student.email || '-';
    document.getElementById('viewAddress').innerText = student.address || '-';
    document.getElementById('viewClass').innerText = student.class || '-';
    document.getElementById('viewBatch').innerText = student.batch || '-';
    document.getElementById('viewStatus').innerText = student.is_active == 1 ? 'Active' : 'Inactive';
    document.getElementById('viewRemark').innerText = student.remark || '-';
    document.getElementById('viewAdmission').innerText = student.admission_amount ? '₹' + student.admission_amount : '-';
    document.getElementById('viewFee').innerText = student.fee ? '₹' + student.fee : '-';
    document.getElementById('viewStudentId').innerText = 'STU-' + student.id;



    // Handle photo display
    if (student.photo) {
        document.getElementById('viewStudentPhoto').src = student.photo;
        document.getElementById('viewPhotoContainer').classList.remove('hidden');
        document.getElementById('viewNoPhoto').classList.add('hidden');
        document.getElementById('viewPhotoActions').classList.remove('hidden');
    } else {
        document.getElementById('viewPhotoContainer').classList.add('hidden');
        document.getElementById('viewNoPhoto').classList.remove('hidden');
        document.getElementById('viewPhotoActions').classList.add('hidden');
    }

    // Reset to first tab
    switchTab('overview');

    // Fetch and populate documents
    const documentsPromise = fetch('get_student_documents.php?student_id=' + student.id)
        .then(response => response.json())
        .then(data => {
            const documentsList = document.getElementById('viewDocumentsList');
            const noDocumentsMsg = document.getElementById('viewNoDocuments');

            if (data.success && data.documents && data.documents.length > 0) {
                documentsList.innerHTML = '';
                noDocumentsMsg.classList.add('hidden');

                data.documents.forEach(doc => {
                    const docItem = document.createElement('div');
                    docItem.className = 'flex items-center justify-between p-2 bg-gray-50 rounded border border-gray-200 hover:bg-gray-100 transition';

                    // Escape file path and name for use in HTML attributes
                    const filePath = doc.file_path.replace(/'/g, "\\'").replace(/"/g, '&quot;');
                    const fileName = doc.file_name.replace(/'/g, "\\'").replace(/"/g, '&quot;');

                    docItem.innerHTML = `
                        <div class="flex items-center space-x-2 flex-1">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span class="text-sm text-gray-700">${doc.file_name}</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button onclick="previewDocument('${filePath}', '${fileName}')" class="ml-2 text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center space-x-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                <span>Preview</span>
                            </button>
                            <a href="${doc.file_path}" target="_blank" class="ml-2 text-teal-600 hover:text-teal-800 text-sm font-medium flex items-center space-x-1">
                                <span>Download</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                            </a>
                        </div>
                    `;
                    documentsList.appendChild(docItem);
                });
            } else {
                documentsList.innerHTML = '';
                noDocumentsMsg.classList.remove('hidden');
            }
        })
        .catch(error => {
            console.error('Error fetching documents:', error);
            document.getElementById('viewDocumentsList').innerHTML = '';
            document.getElementById('viewNoDocuments').classList.remove('hidden');
        });

    // Fetch and populate history
    const detailsPromise = fetch('get_student_details.php?student_id=' + student.id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {


                // Populate Attendance History
                const attBody = document.getElementById('attendanceHistoryBody');
                attBody.innerHTML = '';
                if (data.attendance_history.length > 0) {
                    data.attendance_history.forEach(att => {
                        const row = `<tr>
                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">${att.date}</td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">${att.in_time}</td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">${att.out_time}</td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    ${att.status}
                                </span>
                            </td>
                        </tr>`;
                        attBody.innerHTML += row;
                    });
                } else {
                    attBody.innerHTML = '<tr><td colspan="4" class="px-4 py-2 text-center text-sm text-gray-500">No attendance records found</td></tr>';
                }

                // Render Chart
                renderAttendanceChart(data.attendance_chart);




            }
        })
        .catch(error => {
            console.error('Error fetching student details:', error);
        });

    // Fetch and populate payment history
    const paymentsPromise = fetch('api/get_payment_history.php?student_id=' + student.id)
        .then(response => response.json())
        .then(data => {
            const body = document.getElementById('paymentHistoryBody');
            const noMsg = document.getElementById('noPaymentsMsg');
            body.innerHTML = '';
            if (data.success && data.payments && data.payments.length > 0) {
                noMsg.classList.add('hidden');
                // Save for filtering
                body.dataset.allPayments = JSON.stringify(data.payments);
                ensurePaymentDataTable();
                renderPaymentRows(data.payments);
                // Totals
                if (data.totals) {
                    document.getElementById('totalDebit').innerText = `₹${Number(data.totals.total_debit).toFixed(2)}`;
                    document.getElementById('totalCredit').innerText = `₹${Number(data.totals.total_credit).toFixed(2)}`;
                    document.getElementById('netBalance').innerText = `₹${Number(data.totals.balance).toFixed(2)}`;
                }
                // Hook up filter
                const filterEl = document.getElementById('paymentTypeFilter');
                if (filterEl) {
                    filterEl.onchange = function () {
                        const val = filterEl.value;
                        const table = $('#paymentHistoryTable').DataTable();
                        // Column indexes: 0 Date, 1 Amount, 2 Type, 3 Category, 4 Description
                        table.column(2).search(val === 'all' ? '' : '^' + val + '$', true, false).draw();
                    };
                }
            } else {
                noMsg.classList.remove('hidden');
                body.innerHTML = '<tr><td colspan="5" class="px-4 py-2 text-center text-sm text-gray-500">No payments recorded</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error fetching payment history:', error);
            const body = document.getElementById('paymentHistoryBody');
            const noMsg = document.getElementById('noPaymentsMsg');
            noMsg.classList.remove('hidden');
            body.innerHTML = '<tr><td colspan="5" class="px-4 py-2 text-center text-sm text-red-500">Failed to load payments</td></tr>';
        });

    // Hide loading overlay only after both promises complete
    Promise.all([documentsPromise, detailsPromise, paymentsPromise]).then(() => {
        loadingOverlay.classList.add('hidden');
    }).catch(() => {
        // Even if there's an error, hide the loading overlay
        loadingOverlay.classList.add('hidden');
    });
}

function renderPaymentRows(payments) {
    const tableEl = $('#paymentHistoryTable');
    let table = null;
    if ($.fn.DataTable.isDataTable(tableEl)) {
        table = tableEl.DataTable();
        table.clear();
    } else {
        table = tableEl.DataTable({
            responsive: true,
            pageLength: 10,
            order: [[0, 'desc']],
            columnDefs: [
                { targets: [4], orderable: false }
            ],
            createdRow: function (row, data) {
                // Add modern styling to rows
                $(row).addClass('transition-all hover:bg-gray-50');
            }
        });
    }

    const rows = payments.map(p => {
        const amt = Number(p.amount);
        const type = (p.transaction_type || '').toLowerCase();
        // Show only date part (YYYY-MM-DD)
        const dateOnly = (p.date || '').split(' ')[0] || p.date;
        
        // Format amount with color coding
        const amtFormatted = isNaN(amt) ? p.amount : amt.toFixed(2);
        const amountClass = type === 'debit' ? 'amount-debit' : type === 'credit' ? 'amount-credit' : '';
        const signedAmt = `<span class="${amountClass}">${type === 'debit' ? '+' : type === 'credit' ? '-' : ''}₹${amtFormatted}</span>`;
        
        // Modern type badge with gradient
        const typeBadgeClass = type === 'debit' 
            ? 'bg-gradient-to-r from-green-50 to-emerald-50 text-green-700 border border-green-200' 
            : type === 'credit' 
            ? 'bg-gradient-to-r from-red-50 to-rose-50 text-red-700 border border-red-200' 
            : 'bg-gray-100 text-gray-700 border border-gray-200';
        const typeBadge = `<span class="px-2.5 py-1 inline-flex text-xs font-semibold rounded-lg ${typeBadgeClass}">${p.transaction_type || '-'}</span>`;
        
        return [
            dateOnly,
            signedAmt,
            typeBadge,
            p.category || '-',
            (p.description || '-')
        ];
    });
    table.rows.add(rows).draw();
}

function ensurePaymentDataTable() {
    const tableEl = $('#paymentHistoryTable');
    if (!$.fn.DataTable.isDataTable(tableEl)) {
        tableEl.DataTable({
            responsive: true,
            pageLength: 10,
            order: [[0, 'desc']],
            columnDefs: [
                { targets: [4], orderable: false }
            ],
            createdRow: function (row, data) {
                // Add modern styling to rows
                $(row).addClass('transition-all hover:bg-gray-50');
            }
        });
    }
}

function getOrdinal(n) {
    var s = ["th", "st", "nd", "rd"];
    var v = n % 100;
    return (s[(v - 20) % 10] || s[v] || s[0]);
}

function renderAttendanceChart(chartData) {
    const ctx = document.getElementById('attendanceChart').getContext('2d');

    if (attendanceChartInstance) {
        attendanceChartInstance.destroy();
    }

    attendanceChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartData.labels,
            datasets: [{
                label: 'Days Present',
                data: chartData.data,
                backgroundColor: 'rgba(13, 148, 136, 0.6)', // Teal-600
                borderColor: 'rgba(13, 148, 136, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

function closeViewModal() {
    document.getElementById('viewModal').classList.add('hidden');
    currentViewStudent = null;
}

function viewPhoto(photoPath, studentName) {
    document.getElementById('fullSizePhoto').src = photoPath;
    document.getElementById('photoModalTitle').innerText = studentName;
    const downloadLink = document.getElementById('photoDownloadLink');
    downloadLink.href = photoPath;
    // Extract filename from path or use student name
    const fileName = photoPath.split('/').pop() || `${studentName.replace(/\s+/g, '_')}_photo.jpg`;
    downloadLink.download = fileName;
    document.getElementById('photoModal').classList.remove('hidden');
}

function closePhotoModal() {
    document.getElementById('photoModal').classList.add('hidden');
}

// Document Preview Functions
function previewDocument(filePath, fileName) {
    const modal = document.getElementById('documentModal');
    const content = document.getElementById('documentPreviewContent');
    const title = document.getElementById('documentModalTitle');
    const downloadLink = document.getElementById('documentDownloadLink');

    title.innerText = fileName;
    downloadLink.href = filePath;

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
    } else if (pdfExtensions.includes(fileExt)) {
        // Display PDF in iframe
        const iframe = document.createElement('iframe');
        iframe.src = filePath;
        iframe.className = 'w-full h-[70vh] border-0 rounded-lg';
        iframe.title = fileName;
        content.appendChild(iframe);
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
    }

    modal.classList.remove('hidden');
}

function closeDocumentModal() {
    document.getElementById('documentModal').classList.add('hidden');
    // Clear content when closing
    document.getElementById('documentPreviewContent').innerHTML = '';
}

function viewFullPhoto() {
    if (currentViewStudent && currentViewStudent.photo) {
        viewPhoto(currentViewStudent.photo, currentViewStudent.name);
    }
}

// Payment Modal Functions
function openPaymentModal(student) {
    document.getElementById('paymentStudentId').value = student.id;
    document.getElementById('paymentStudentName').value = student.name;
    document.getElementById('paymentAmount').value = '';
    document.getElementById('paymentDescription').value = '';
    document.getElementById('paymentModal').classList.remove('hidden');
}

function closePaymentModal() {
    document.getElementById('paymentModal').classList.add('hidden');
}

function submitPayment() {
    const studentId = document.getElementById('paymentStudentId').value;
    const amount = document.getElementById('paymentAmount').value;
    const category = document.getElementById('paymentCategory').value;
    const description = document.getElementById('paymentDescription').value;

    if (!amount || amount <= 0) {
        Swal.fire('Error', 'Please enter a valid amount', 'error');
        return;
    }

    const submitBtn = document.getElementById('submitPaymentBtn');
    const originalText = submitBtn.innerText;
    submitBtn.disabled = true;
    submitBtn.innerText = 'Processing...';

    const formData = new FormData();
    formData.append('student_id', studentId);
    formData.append('amount', amount);
    formData.append('category', category);
    formData.append('description', description);

    fetch('api/save_payment.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Payment Recorded',
                    text: 'Payment has been recorded successfully',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    closePaymentModal();
                    // Optionally refresh data
                });
            } else {
                Swal.fire('Error', data.message || 'Failed to record payment', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'An error occurred', 'error');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerText = originalText;
        });
}

function downloadPhoto() {
    if (currentViewStudent && currentViewStudent.photo) {
        // Create a temporary anchor element to trigger download
        const link = document.createElement('a');
        link.href = currentViewStudent.photo;
        // Extract filename from path or use student name
        const fileName = currentViewStudent.photo.split('/').pop() || `student_${currentViewStudent.id}_photo.jpg`;
        link.download = fileName;
        link.target = '_blank';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

function editFromView() {
    closeViewModal();
    if (currentViewStudent) {
        openEditModal(currentViewStudent);
    }
}



// Toggle Student Status Function
function toggleStudentStatus(studentId, currentStatus, buttonElement) {
    const newStatus = currentStatus ? 0 : 1;
    const statusText = newStatus ? 'activate' : 'deactivate';

    Swal.fire({
        title: `${statusText.charAt(0).toUpperCase() + statusText.slice(1)} Student?`,
        text: `Are you sure you want to ${statusText} this student?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: newStatus ? '#0d9488' : '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: `Yes, ${statusText}!`,
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Disable button during request
            buttonElement.disabled = true;

            const formData = new FormData();
            formData.append('student_id', studentId);
            formData.append('is_active', newStatus);

            fetch('toggle_student_status.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: data.message,
                            confirmButtonColor: '#0d9488',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            // Reload page to reflect changes
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: data.message || 'Failed to update status',
                            confirmButtonColor: '#dc2626'
                        });
                        buttonElement.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'An error occurred while updating the status',
                        confirmButtonColor: '#dc2626'
                    });
                    buttonElement.disabled = false;
                });
        }
    });
}
