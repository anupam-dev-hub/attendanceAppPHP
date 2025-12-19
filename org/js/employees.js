// org/js/employees.js
// Updated: 2025-12-03 - Added document thumbnails, preview modal, download buttons

$(document).ready(function () {
    var table = $('#employeesTable').DataTable({
        responsive: true,
        "pageLength": 10,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
        "order": [[0, "asc"]],
        "columnDefs": [
            {
                "orderable": false,
                "targets": [1, 6, 7]
            },
            {
                "responsivePriority": 1,
                "targets": 0
            },
            {
                "responsivePriority": 2,
                "targets": 7
            },
            {
                "responsivePriority": 10001,
                "targets": 1
            },
            {
                "responsivePriority": 10002,
                "targets": 5
            },
            {
                "responsivePriority": 3,
                "targets": 2
            },
            {
                "responsivePriority": 4,
                "targets": 3
            }
        ],
        "language": {
            "lengthMenu": "Show _MENU_ employees",
            "info": "Showing _START_ to _END_ of _TOTAL_ employees",
            "infoEmpty": "No employees to show",
            "infoFiltered": "(filtered from _MAX_ total employees)",
            "search": "Search:",
            "zeroRecords": "No matching employees found"
        }
    });

    // Handle payment button clicks via event delegation
    $(document).on('click', 'button.payment-btn', function(e) {
        e.preventDefault();
        var employeeId = $(this).data('employee-id');
        var employeeName = $(this).data('employee-name');
        openEmployeePaymentModal(employeeId, employeeName);
    });

    // Populate filter dropdowns
    var departments = [];
    var designations = [];
    
    table.column(3).data().unique().sort().each(function(d) {
        if (d && d !== '-') departments.push(d);
    });
    table.column(2).data().unique().sort().each(function(d) {
        if (d && d !== '-') designations.push(d);
    });
    
    departments.forEach(function(dept) {
        $('#departmentFilter').append('<option value="' + dept + '">' + dept + '</option>');
    });
    
    designations.forEach(function(desig) {
        $('#designationFilter').append('<option value="' + desig + '">' + desig + '</option>');
    });

    // Department filter
    $('#departmentFilter').on('change', function () {
        table.column(3).search(this.value).draw();
    });

    // Designation filter
    $('#designationFilter').on('change', function () {
        table.column(2).search(this.value).draw();
    });

    // Status filter
    $.fn.dataTable.ext.search.push(
        function (settings, data, dataIndex) {
            var statusFilter = $('#statusFilter').val();
            if (statusFilter === '') {
                return true;
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

function openAddModal() {
    document.getElementById('modalTitle').innerText = 'Add New Employee';
    document.getElementById('formAction').value = 'add';
    document.getElementById('employeeForm').reset();
    document.getElementById('employeeId').value = '';
    document.getElementById('photoPreview').classList.add('hidden');
    document.getElementById('existingDocumentsPreview').classList.add('hidden');
    document.getElementById('newDocumentsPreview').classList.add('hidden');
    document.getElementById('employeeIsActive').checked = true;
    document.getElementById('employeeModal').classList.remove('hidden');
    
    const submitBtn = document.getElementById('submitBtn');
    const submitBtnText = document.getElementById('submitBtnText');
    const submitBtnSpinner = document.getElementById('submitBtnSpinner');
    submitBtn.disabled = false;
    submitBtnText.textContent = 'Save Employee';
    submitBtnSpinner.classList.add('hidden');
}

function openEditModal(employee) {
    document.getElementById('modalTitle').innerText = 'Edit Employee';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('employeeId').value = employee.id;
    document.getElementById('employeeName').value = employee.name;
    document.getElementById('employeePhone').value = employee.phone;
    document.getElementById('employeeEmail').value = employee.email || '';
    document.getElementById('employeeAddress').value = employee.address || '';
    document.getElementById('employeeDesignation').value = employee.designation || '';
    document.getElementById('employeeDepartment').value = employee.department || '';
    document.getElementById('employeeSalary').value = employee.salary || '0.00';
    document.getElementById('employeeIsActive').checked = employee.is_active == 1;

    // Handle photo preview
    const photoPreview = document.getElementById('photoPreview');
    const photoPreviewImg = document.getElementById('photoPreviewImg');
    
    if (employee.photo && employee.photo !== '' && employee.photo !== null && employee.photo !== '0') {
        photoPreviewImg.src = employee.photo;
        photoPreview.classList.remove('hidden');
    } else {
        photoPreview.classList.add('hidden');
    }

    // Clear new documents preview
    document.getElementById('newDocumentsPreview').classList.add('hidden');
    document.getElementById('employeeDocuments').value = '';

    // Store employee ID for use in delete function
    const currentEmployeeId = employee.id;

    // Fetch and display existing documents with thumbnails (matching student implementation)
    fetch('get_employee_documents.php?employee_id=' + employee.id)
        .then(response => response.json())
        .then(data => {
            const documentsList = document.getElementById('existingDocumentsList');
            documentsList.innerHTML = '';
            if (data.success && data.documents && data.documents.length > 0) {
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
                    deleteBtn.onclick = function (e) { deleteDocument(doc.id, currentEmployeeId, e); };
                    deleteBtn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>';
                    li.appendChild(deleteBtn);

                    documentsList.appendChild(li);
                });
                document.getElementById('existingDocumentsPreview').classList.remove('hidden');
            } else {
                document.getElementById('existingDocumentsPreview').classList.add('hidden');
            }
        })
        .catch(error => {
            console.error('Error fetching documents:', error);
            document.getElementById('existingDocumentsPreview').classList.add('hidden');
        });

    document.getElementById('employeeModal').classList.remove('hidden');
    
    const submitBtn = document.getElementById('submitBtn');
    const submitBtnText = document.getElementById('submitBtnText');
    const submitBtnSpinner = document.getElementById('submitBtnSpinner');
    submitBtn.disabled = false;
    submitBtnText.textContent = 'Update Employee';
    submitBtnSpinner.classList.add('hidden');
}

function closeModal() {
    document.getElementById('employeeModal').classList.add('hidden');
    document.getElementById('employeeForm').reset();
    document.getElementById('photoPreview').classList.add('hidden');
    document.getElementById('existingDocumentsPreview').classList.add('hidden');
    document.getElementById('newDocumentsPreview').classList.add('hidden');
}

function viewEmployee(employee) {
    // Store current employee data for payment functions
    currentPaymentEmployeeId = employee.id;
    currentPaymentEmployeeName = employee.name;
    
    document.getElementById('viewName').innerText = employee.name || '-';
    document.getElementById('viewName').setAttribute('data-employee-id', employee.id);
    document.getElementById('viewPhone').innerText = employee.phone || '-';
    document.getElementById('viewEmail').innerText = employee.email || '-';
    document.getElementById('viewAddress').innerText = employee.address || '-';
    document.getElementById('viewDesignation').innerText = employee.designation || '-';
    document.getElementById('viewDepartment').innerText = employee.department || '-';
    document.getElementById('viewSalary').innerText = employee.salary ? '₹' + parseFloat(employee.salary).toFixed(2) : '-';
    document.getElementById('viewStatus').innerText = employee.is_active == 1 ? 'Active' : 'Inactive';

    // Store current employee photo for preview/download
    window.currentEmployeePhoto = employee.photo;
    window.currentEmployeeName = employee.name;

    if (employee.photo) {
        document.getElementById('viewEmployeePhoto').src = employee.photo;
        document.getElementById('viewPhotoContainer').classList.remove('hidden');
        document.getElementById('viewNoPhoto').classList.add('hidden');
        document.getElementById('viewPhotoActions').classList.remove('hidden');
    } else {
        document.getElementById('viewPhotoContainer').classList.add('hidden');
        document.getElementById('viewNoPhoto').classList.remove('hidden');
        document.getElementById('viewPhotoActions').classList.add('hidden');
    }

    // Fetch documents with View/Download buttons (matching student implementation)
    fetch('get_employee_documents.php?employee_id=' + employee.id)
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
                            <button onclick="downloadDocument('${filePath}', '${fileName}')" class="ml-2 text-teal-600 hover:text-teal-800 text-sm font-medium flex items-center space-x-1">
                                <span>Download</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                            </button>
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

    const viewModal = document.getElementById('viewModal');
    viewModal.style.display = 'block';
    setTimeout(() => {
        viewModal.classList.remove('opacity-0', 'pointer-events-none');
        viewModal.classList.add('opacity-100', 'pointer-events-auto');
    }, 10);
}

function closeViewModal() {
    const viewModal = document.getElementById('viewModal');
    viewModal.classList.remove('opacity-100', 'pointer-events-auto');
    viewModal.classList.add('opacity-0', 'pointer-events-none');
    setTimeout(() => {
        viewModal.style.display = 'none';
    }, 200);
}

function viewFullPhoto() {
    if (window.currentEmployeePhoto) {
        Swal.fire({
            title: window.currentEmployeeName || 'Employee Photo',
            imageUrl: window.currentEmployeePhoto,
            imageAlt: window.currentEmployeeName || 'Employee Photo',
            showCloseButton: true,
            showConfirmButton: false,
            width: 600
        });
    }
}

function downloadPhoto() {
    if (window.currentEmployeePhoto) {
        const link = document.createElement('a');
        link.href = window.currentEmployeePhoto;
        link.download = (window.currentEmployeeName || 'employee') + '_photo.jpg';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

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

function viewPhoto(photoPath, employeeName) {
    Swal.fire({
        title: employeeName,
        imageUrl: photoPath,
        imageAlt: employeeName,
        showCloseButton: true,
        showConfirmButton: false,
        width: 600
    });
}

function toggleEmployeeStatus(employeeId, currentStatus, buttonElement) {
    const newStatus = currentStatus ? 0 : 1;
    const statusText = newStatus ? 'activate' : 'deactivate';

    Swal.fire({
        title: `${statusText.charAt(0).toUpperCase() + statusText.slice(1)} Employee?`,
        text: `Are you sure you want to ${statusText} this employee?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: newStatus ? '#0d9488' : '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: `Yes, ${statusText}!`,
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            buttonElement.disabled = true;

            const formData = new FormData();
            formData.append('employee_id', employeeId);
            formData.append('is_active', newStatus);

            fetch('toggle_employee_status.php', {
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
    } else if (pdfExtensions.includes(fileExt)) {
        // Display PDF
        const iframe = document.createElement('iframe');
        iframe.src = filePath;
        iframe.className = 'w-full h-[70vh] rounded-lg border-2 border-gray-300';
        content.appendChild(iframe);
    } else {
        // Display message for other file types
        content.innerHTML = `
            <div class="text-center p-8">
                <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <p class="text-gray-600 mb-4">Preview not available for this file type.</p>
                <p class="text-sm text-gray-500">Click the Download button to view the file.</p>
            </div>
        `;
    }

    modal.classList.remove('hidden');
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
            console.error('Download failed:', error);
            // Fallback: open in new tab
            Swal.fire({
                icon: 'info',
                title: 'Download Notice',
                text: 'Direct download failed. Opening file in new tab...',
                confirmButtonColor: '#0d9488',
                timer: 2000
            });
            window.open(filePath, '_blank');
        });
}

// Delete Document Function
function deleteDocument(documentId, employeeId, event) {
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

            fetch('delete_employee_document.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: 'Document has been deleted.',
                            confirmButtonColor: '#0d9488',
                            timer: 1500
                        }).then(() => {
                            // Refresh the documents list
                            const documentsList = document.getElementById('existingDocumentsList');
                            documentsList.innerHTML = '';
                            
                            // Fetch updated documents
                            fetch('get_employee_documents.php?employee_id=' + employeeId)
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success && data.documents && data.documents.length > 0) {
                                        // Re-populate the list with updated documents
                                        data.documents.forEach(doc => {
                                            const li = document.createElement('li');
                                            li.className = 'flex items-center justify-between py-2 border-b border-gray-200 last:border-b-0';

                                            const docInfo = document.createElement('div');
                                            docInfo.className = 'flex items-center space-x-3 flex-1';

                                            const fileExt = doc.file_name.split('.').pop().toLowerCase();
                                            if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExt)) {
                                                const img = document.createElement('img');
                                                img.src = doc.file_path;
                                                img.className = 'w-16 h-16 object-cover rounded border border-gray-300';
                                                img.alt = doc.file_name;
                                                docInfo.appendChild(img);
                                            } else if (fileExt === 'pdf') {
                                                const canvas = document.createElement('canvas');
                                                canvas.className = 'w-16 h-16 object-cover rounded border border-gray-300';
                                                docInfo.appendChild(canvas);

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
                                                        canvas.remove();
                                                        const icon = document.createElement('div');
                                                        icon.innerHTML = '<svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>';
                                                        docInfo.insertBefore(icon, docInfo.firstChild);
                                                    });
                                            } else {
                                                const icon = document.createElement('div');
                                                icon.innerHTML = '<svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>';
                                                docInfo.appendChild(icon);
                                            }

                                            const link = document.createElement('a');
                                            link.href = doc.file_path;
                                            link.target = '_blank';
                                            link.className = 'text-blue-600 hover:underline text-sm truncate flex-1';
                                            link.textContent = doc.file_name;
                                            docInfo.appendChild(link);

                                            li.appendChild(docInfo);

                                            const deleteBtn = document.createElement('button');
                                            deleteBtn.type = 'button';
                                            deleteBtn.className = 'ml-2 text-red-600 hover:text-red-800 transition';
                                            deleteBtn.title = 'Delete document';
                                            deleteBtn.onclick = function (e) { deleteDocument(doc.id, employeeId, e); };
                                            deleteBtn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>';
                                            li.appendChild(deleteBtn);

                                            documentsList.appendChild(li);
                                        });
                                        document.getElementById('existingDocumentsPreview').classList.remove('hidden');
                                    } else {
                                        document.getElementById('existingDocumentsPreview').classList.add('hidden');
                                    }
                                });
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: data.message || 'Failed to delete document',
                            confirmButtonColor: '#dc2626'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'An error occurred while deleting the document',
                        confirmButtonColor: '#dc2626'
                    });
                });
        }
    });
}

function viewDocument(filePath, fileName, isPDF, isImage) {
    if (isImage) {
        Swal.fire({
            title: fileName,
            imageUrl: filePath,
            imageAlt: fileName,
            showCloseButton: true,
            showConfirmButton: false,
            width: 800
        });
    } else if (isPDF) {
        window.open(filePath, '_blank');
    } else {
        window.open(filePath, '_blank');
    }
}

function previewDocuments(event) {
    const files = event.target.files;
    const list = document.getElementById('newDocumentsList');
    const preview = document.getElementById('newDocumentsPreview');
    const fileInput = document.getElementById('employeeDocuments');

    list.innerHTML = '';

    if (files && files.length > 0) {
        preview.classList.remove('hidden');
        
        // Convert FileList to Array to allow manipulation
        const filesArray = Array.from(files);
        
        filesArray.forEach((file, index) => {
            const li = document.createElement('li');
            li.className = 'flex items-center justify-between py-2 border-b border-gray-200 last:border-b-0';
            li.dataset.fileIndex = index;

            const contentDiv = document.createElement('div');
            contentDiv.className = 'flex items-center space-x-3 flex-1';

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

                    contentDiv.appendChild(img);
                    contentDiv.appendChild(textSpan);
                };
                reader.readAsDataURL(file);
            } else if (file.type === 'application/pdf') {
                // Create PDF thumbnail
                const reader = new FileReader();
                reader.onload = function (e) {
                    const loadingTask = pdfjsLib.getDocument({ data: e.target.result });
                    loadingTask.promise.then(function (pdf) {
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

                                contentDiv.appendChild(canvas);
                                contentDiv.appendChild(textSpan);
                            });
                        });
                    }).catch(function (error) {
                        console.error('Error loading PDF:', error);
                        const icon = '<svg class="w-8 h-8 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>';
                        contentDiv.innerHTML = `${icon}<span class="text-gray-700 text-sm truncate flex-1">${file.name}</span>`;
                    });
                };
                reader.readAsArrayBuffer(file);
            } else {
                // Show icon for other file types
                const icon = '<svg class="w-8 h-8 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>';
                contentDiv.innerHTML = `${icon}<span class="text-gray-700 text-sm truncate flex-1">${file.name}</span>`;
            }

            li.appendChild(contentDiv);

            // Add remove button
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'ml-2 text-red-600 hover:text-red-800 transition';
            removeBtn.title = 'Remove document';
            removeBtn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>';
            removeBtn.onclick = function() {
                // Remove this file from the array
                filesArray.splice(index, 1);
                
                // Create new FileList from remaining files
                const dt = new DataTransfer();
                filesArray.forEach(f => dt.items.add(f));
                fileInput.files = dt.files;
                
                // Re-render the preview
                previewDocuments({ target: fileInput });
            };
            li.appendChild(removeBtn);

            list.appendChild(li);
        });
    } else {
        preview.classList.add('hidden');
    }
}

document.getElementById('employeeForm').addEventListener('submit', function (e) {
    const submitBtn = document.getElementById('submitBtn');
    const submitBtnText = document.getElementById('submitBtnText');
    const submitBtnSpinner = document.getElementById('submitBtnSpinner');

    submitBtn.disabled = true;
    submitBtnText.textContent = 'Saving...';
    submitBtnSpinner.classList.remove('hidden');
});

// Employee Payment Functions
let currentPaymentEmployeeId = null;
let currentPaymentEmployeeName = '';

function openEmployeePaymentModal(employeeId, employeeName) {
    currentPaymentEmployeeId = employeeId;
    currentPaymentEmployeeName = employeeName;
    
    Swal.fire({
        title: 'Record Employee Payment',
        html: `
            <div class="text-left">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Employee Name</label>
                    <input type="text" value="${employeeName}" disabled class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-700">
                </div>
                
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Transaction Type</label>
                        <select id="paymentTransactionType" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="salary">Salary</option>
                            <option value="bonus">Bonus</option>
                            <option value="advance">Advance</option>
                            <option value="deduction">Deduction</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Payment Date</label>
                        <input type="date" id="paymentDate" value="${new Date().toISOString().split('T')[0]}" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Amount (₹)</label>
                        <input type="number" id="paymentAmount" placeholder="0.00" min="0.01" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <select id="paymentCategory" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="Monthly Salary">Monthly Salary</option>
                            <option value="Performance Bonus">Performance Bonus</option>
                            <option value="Festival Bonus">Festival Bonus</option>
                            <option value="Overtime">Overtime</option>
                            <option value="Advance Payment">Advance Payment</option>
                            <option value="Tax Deduction">Tax Deduction</option>
                            <option value="Leave Deduction">Leave Deduction</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description (Optional)</label>
                    <textarea id="paymentDescription" class="w-full px-3 py-2 border border-gray-300 rounded-md" rows="2" placeholder="Enter description..."></textarea>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Record Payment',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#0d9488',
        didOpen: () => {
            // Focus on the amount field
            setTimeout(() => {
                document.getElementById('paymentAmount').focus();
            }, 100);
        },
        preConfirm: () => {
            const amount = document.getElementById('paymentAmount').value;
            const transactionType = document.getElementById('paymentTransactionType').value;
            const category = document.getElementById('paymentCategory').value;
            const paymentDate = document.getElementById('paymentDate').value;
            const description = document.getElementById('paymentDescription').value;
            
            if (!amount || amount <= 0) {
                Swal.showValidationMessage('Please enter a valid amount');
                return false;
            }
            if (!transactionType) {
                Swal.showValidationMessage('Please select a transaction type');
                return false;
            }
            if (!paymentDate) {
                Swal.showValidationMessage('Please select a payment date');
                return false;
            }
            
            return {
                employee_id: currentPaymentEmployeeId,
                amount: amount,
                transaction_type: transactionType,
                category: category,
                payment_date: paymentDate,
                description: description
            };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            submitEmployeePayment(result.value);
        }
    });
}

function submitEmployeePayment(paymentData) {
    Swal.fire({
        title: 'Processing...',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    const formData = new FormData();
    formData.append('employee_id', paymentData.employee_id);
    formData.append('amount', paymentData.amount);
    formData.append('transaction_type', paymentData.transaction_type);
    formData.append('category', paymentData.category);
    formData.append('payment_date', paymentData.payment_date);
    formData.append('description', paymentData.description);
    
    fetch('api/save_employee_payment.php', {
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
                timer: 2000
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: data.message,
                confirmButtonColor: '#dc2626'
            });
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: 'An error occurred while recording payment',
            confirmButtonColor: '#dc2626'
        });
    });
}

function viewEmployeePaymentHistory() {
    if (!currentPaymentEmployeeId) {
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: 'No employee selected',
            confirmButtonColor: '#dc2626'
        });
        return;
    }
    
    fetch(`api/get_employee_payment_history.php?employee_id=${currentPaymentEmployeeId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = `
                    <div class="text-left">
                        <div class="grid grid-cols-3 gap-2 mb-4">
                            <div class="bg-green-50 p-3 rounded border border-green-200">
                                <p class="text-xs text-gray-600">Total Paid</p>
                                <p class="text-xl font-bold text-green-600">₹${data.total_paid.toFixed(2)}</p>
                            </div>
                            <div class="bg-red-50 p-3 rounded border border-red-200">
                                <p class="text-xs text-gray-600">Total Deductions</p>
                                <p class="text-xl font-bold text-red-600">₹${data.total_deductions.toFixed(2)}</p>
                            </div>
                            <div class="bg-blue-50 p-3 rounded border border-blue-200">
                                <p class="text-xs text-gray-600">Net Payment</p>
                                <p class="text-xl font-bold text-blue-600">₹${data.net_payment.toFixed(2)}</p>
                            </div>
                        </div>
                        
                        <div class="max-h-96 overflow-y-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 sticky top-0">
                                    <tr>
                                        <th class="px-2 py-2 text-left font-medium text-gray-700">Date</th>
                                        <th class="px-2 py-2 text-left font-medium text-gray-700">Type</th>
                                        <th class="px-2 py-2 text-left font-medium text-gray-700">Category</th>
                                        <th class="px-2 py-2 text-right font-medium text-gray-700">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.payments && data.payments.length > 0 ? data.payments.map(p => `
                                        <tr class="border-b">
                                            <td class="px-2 py-2 text-gray-900">${p.payment_date}</td>
                                            <td class="px-2 py-2">
                                                <span class="px-2 py-1 rounded text-xs font-medium ${
                                                    p.transaction_type === 'deduction' ? 'bg-red-100 text-red-800' :
                                                    p.transaction_type === 'advance' ? 'bg-yellow-100 text-yellow-800' :
                                                    p.transaction_type === 'bonus' ? 'bg-blue-100 text-blue-800' :
                                                    'bg-green-100 text-green-800'
                                                }">
                                                    ${p.transaction_type.charAt(0).toUpperCase() + p.transaction_type.slice(1)}
                                                </span>
                                            </td>
                                            <td class="px-2 py-2 text-gray-700">${p.category}</td>
                                            <td class="px-2 py-2 text-right font-semibold ${p.transaction_type === 'deduction' ? 'text-red-600' : 'text-green-600'}">
                                                ₹${parseFloat(p.amount).toFixed(2)}
                                            </td>
                                        </tr>
                                    `).join('') : '<tr><td colspan="4" class="px-2 py-4 text-center text-gray-500">No payment history found</td></tr>'}
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
                
                Swal.fire({
                    title: `Payment History - ${currentPaymentEmployeeName}`,
                    html: html,
                    width: 700,
                    showConfirmButton: true,
                    confirmButtonText: 'Close',
                    confirmButtonColor: '#0d9488'
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: data.message,
                    confirmButtonColor: '#dc2626'
                });
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'An error occurred while fetching payment history',
                confirmButtonColor: '#dc2626'
            });
        });
}
