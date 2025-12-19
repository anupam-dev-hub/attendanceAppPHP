// org/js/students.js

// Toggle Sex Other field
function toggleSexOther() {
    const sexSelect = document.getElementById('studentSex');
    const sexOtherGroup = document.getElementById('sexOtherGroup');
    if (sexSelect.value === 'Other') {
        sexOtherGroup.style.display = 'flex';
    } else {
        sexOtherGroup.style.display = 'none';
        document.getElementById('studentSexOther').value = '';
    }
}

// Toggle Religion Other field
function toggleReligionOther() {
    const religionSelect = document.getElementById('studentReligion');
    const religionOtherGroup = document.getElementById('religionOtherGroup');
    if (religionSelect.value === 'Other') {
        religionOtherGroup.style.display = 'flex';
    } else {
        religionOtherGroup.style.display = 'none';
        document.getElementById('studentReligionOther').value = '';
    }
}

// Toggle Community Other field
function toggleCommunityOther() {
    const communitySelect = document.getElementById('studentCommunity');
    const communityOtherGroup = document.getElementById('communityOtherGroup');
    if (communitySelect.value === 'Other') {
        communityOtherGroup.style.display = 'flex';
    } else {
        communityOtherGroup.style.display = 'none';
        document.getElementById('studentCommunityOther').value = '';
    }
}

$(document).ready(function () {
    var table = $('#studentsTable').DataTable({
        responsive: true,
        "pageLength": 10,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
        "order": [
            [2, "asc"],
            [4, "asc"]
        ], // Sort by Class (col 2) then Roll No (col 4)
        "columnDefs": [
            {
                "orderable": false,
                "targets": [1, 7, 8, 9]
            }, // Photo, Status, QR Code, and Actions columns not sortable
            {
                "responsivePriority": 1,
                "targets": 0
            }, // Name - always visible
            {
                "responsivePriority": 2,
                "targets": 9
            }, // Actions - always visible
            {
                "responsivePriority": 10001,
                "targets": 1
            }, // Photo - hide first
            {
                "responsivePriority": 10002,
                "targets": 5
            }, // Balance - hide second
            {
                "responsivePriority": 10003,
                "targets": 6
            }, // Phone - hide third
            {
                "responsivePriority": 10004,
                "targets": 8
            }, // QR - hide fourth
            {
                "responsivePriority": 3,
                "targets": 2
            }, // Class - keep visible longer
            {
                "responsivePriority": 4,
                "targets": 3
            }, // Batch - keep visible longer
            {
                "responsivePriority": 5,
                "targets": 7
            }, // Status - keep visible longer
            {
                "responsivePriority": 6,
                "targets": 4
            } // Roll No - keep visible longer
        ],
        "language": {
            "lengthMenu": "Show _MENU_ students",
            "info": "Showing _START_ to _END_ of _TOTAL_ students",
            "infoEmpty": "No students to show",
            "infoFiltered": "(filtered from _MAX_ total students)",
            "search": "Search:",
            "zeroRecords": "No matching students found"
        }
    });
    
    // Force responsive recalculation on window resize
    $(window).on('resize', function() {
        table.responsive.recalc();
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

    // Handle payment button clicks via event delegation
    $(document).on('click', 'button.payment-btn', function(e) {
        e.preventDefault();
        const studentData = $(this).attr('data-student-fee');
        if (studentData) {
            try {
                const student = JSON.parse(studentData);
                openPaymentModal(student);
            } catch (error) {
                Swal.fire('Error', 'Failed to open payment modal', 'error');
            }
        }
    });
});

// Form submission with loading animation
document.getElementById('studentForm').addEventListener('submit', function (e) {
    // Collect fees data before submission
    const feesJson = collectFeeData();
    
    if (feesJson) {
        // Add hidden input for fees_json
        let feesInput = document.getElementById('feesJsonInput');
        if (!feesInput) {
            feesInput = document.createElement('input');
            feesInput.type = 'hidden';
            feesInput.id = 'feesJsonInput';
            feesInput.name = 'fees_json';
            document.getElementById('studentForm').appendChild(feesInput);
        }
        feesInput.value = feesJson;
    }
    
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
    
    // Load organization fees
    loadOrgFees();
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
    
    // Populate new personal fields
    document.getElementById('studentSex').value = student.sex || '';
    if (student.sex === 'Other') {
        document.getElementById('sexOtherGroup').style.display = 'flex';
        document.getElementById('studentSexOther').value = student.sex_other || '';
    }
    document.getElementById('studentDOB').value = student.date_of_birth || '';
    document.getElementById('studentPlaceOfBirth').value = student.place_of_birth || '';
    document.getElementById('studentNationality').value = student.nationality || 'Indian';
    document.getElementById('studentMotherTongue').value = student.mother_tongue || '';
    document.getElementById('studentReligion').value = student.religion || '';
    if (student.religion === 'Other') {
        document.getElementById('religionOtherGroup').style.display = 'flex';
        document.getElementById('studentReligionOther').value = student.religion_other || '';
    }
    document.getElementById('studentCommunity').value = student.community || '';
    if (student.community === 'Other') {
        document.getElementById('communityOtherGroup').style.display = 'flex';
        document.getElementById('studentCommunityOther').value = student.community_other || '';
    }
    document.getElementById('studentNativeDistrict').value = student.native_district || '';
    document.getElementById('studentPinCode').value = student.pin_code || '';
    
    // Populate parent/guardian info
    document.getElementById('studentParentName').value = student.parent_guardian_name || '';
    document.getElementById('studentParentContact').value = student.parent_contact || '';
    
    // Populate examination info
    document.getElementById('studentExamName').value = student.exam_name || '';
    document.getElementById('studentExamTotalMarks').value = student.exam_total_marks || '';
    document.getElementById('studentExamMarksObtained').value = student.exam_marks_obtained || '';
    document.getElementById('studentExamPercentage').value = student.exam_percentage || '';
    document.getElementById('studentExamGrade').value = student.exam_grade || '';
    
    document.getElementById('studentAdmission').value = student.admission_amount || '0.00';
    
    // Load fees and populate them
    loadOrgFees();
    setTimeout(() => {
        if (student.fees_json) {
            populateFeesInModal(student.fees_json);
        }
    }, 100);
    
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
    const fileInput = document.getElementById('studentDocuments');

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
                            // Refresh the document list with correct element ID
                            fetch('get_student_documents.php?student_id=' + studentId)
                                .then(response => response.json())
                                .then(data => {
                                    const documentsList = document.getElementById('existingDocumentsList');
                                    const documentsPreview = document.getElementById('existingDocumentsPreview');
                                    
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
                                            deleteBtn.onclick = function (e) { deleteDocument(doc.id, studentId, e); };
                                            deleteBtn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>';
                                            li.appendChild(deleteBtn);

                                            documentsList.appendChild(li);
                                        });
                                        documentsPreview.classList.remove('hidden');
                                    } else {
                                        documentsPreview.classList.add('hidden');
                                    }
                                })
                                .catch(error => {
                                    console.error('Error refreshing documents:', error);
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
    // Use the document modal for photos too
    const modal = document.getElementById('documentModal');
    const content = document.getElementById('documentPreviewContent');
    const title = document.getElementById('documentModalTitle');
    const downloadLink = document.getElementById('documentDownloadLink');

    title.innerText = `${studentName} - Photo`;
    
    // Set up download link
    downloadLink.href = photoPath;
    const fileName = photoPath.split('/').pop() || `${studentName.replace(/\s+/g, '_')}_photo.jpg`;
    downloadLink.download = fileName;
    
    // Add onclick handler
    downloadLink.onclick = function(e) {
        e.preventDefault();
        downloadDocument(photoPath, fileName);
    };

    content.innerHTML = '';

    // Display photo
    const img = document.createElement('img');
    img.src = photoPath;
    img.alt = studentName;
    img.className = 'w-full h-auto max-h-[70vh] object-contain rounded-lg mx-auto';
    content.appendChild(img);

    modal.classList.remove('hidden');
}

function closePhotoModal() {
    // This function now closes the document modal
    document.getElementById('documentModal').classList.add('hidden');
    document.getElementById('documentPreviewContent').innerHTML = '';
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

    // Populate student details - Personal Information
    document.getElementById('viewName').innerText = student.name || '-';
    document.getElementById('viewRoll').innerText = student.roll_number || 'Not Assigned';
    
    // Handle Sex with "Other" specification
    let sexDisplay = student.sex || '-';
    if (student.sex === 'Other' && student.sex_other) {
        sexDisplay = student.sex_other;
    }
    document.getElementById('viewSex').innerText = sexDisplay;
    
    // Date of Birth - format if exists
    let dobDisplay = '-';
    if (student.date_of_birth) {
        const dobDate = new Date(student.date_of_birth);
        dobDisplay = dobDate.toLocaleDateString('en-IN', { year: 'numeric', month: 'long', day: 'numeric' });
    }
    document.getElementById('viewDOB').innerText = dobDisplay;
    
    document.getElementById('viewPlaceOfBirth').innerText = student.place_of_birth || '-';
    document.getElementById('viewNationality').innerText = student.nationality || '-';
    document.getElementById('viewMotherTongue').innerText = student.mother_tongue || '-';
    
    // Handle Religion with "Other" specification
    let religionDisplay = student.religion || '-';
    if (student.religion === 'Other' && student.religion_other) {
        religionDisplay = student.religion_other;
    }
    document.getElementById('viewReligion').innerText = religionDisplay;
    
    // Handle Community with "Other" specification
    let communityDisplay = student.community || '-';
    if (student.community === 'Other' && student.community_other) {
        communityDisplay = student.community_other;
    }
    document.getElementById('viewCommunity').innerText = communityDisplay;
    
    document.getElementById('viewNativeDistrict').innerText = student.native_district || '-';
    document.getElementById('viewPinCode').innerText = student.pin_code || '-';
    document.getElementById('viewPhone').innerText = student.phone || '-';
    document.getElementById('viewEmail').innerText = student.email || '-';
    document.getElementById('viewAddress').innerText = student.address || '-';
    
    // Parent/Guardian Information
    document.getElementById('viewParentName').innerText = student.parent_guardian_name || '-';
    document.getElementById('viewParentContact').innerText = student.parent_contact || '-';
    
    // Previous Examination Details
    document.getElementById('viewExamName').innerText = student.exam_name || '-';
    document.getElementById('viewExamTotalMarks').innerText = student.exam_total_marks || '-';
    document.getElementById('viewExamMarksObtained').innerText = student.exam_marks_obtained || '-';
    document.getElementById('viewExamPercentage').innerText = student.exam_percentage ? student.exam_percentage + '%' : '-';
    document.getElementById('viewExamGrade').innerText = student.exam_grade || '-';
    
    // Academic and Other Details
    document.getElementById('viewClass').innerText = student.class || '-';
    document.getElementById('viewBatch').innerText = student.batch || '-';
    document.getElementById('viewStatus').innerText = student.is_active == 1 ? 'Active' : 'Inactive';
    document.getElementById('viewRemark').innerText = student.remark || '-';
    document.getElementById('viewAdmission').innerText = student.admission_amount ? '₹' + student.admission_amount : '-';
    
    // Display fees from fees_json
    const feesDisplay = document.getElementById('feesDisplay');
    if (student.fees_json) {
        try {
            const feesData = JSON.parse(student.fees_json);
            let feesHtml = '<p class="text-xs text-gray-500 uppercase font-semibold mb-2">Fees</p>';
            feesHtml += '<div class="space-y-2">';
            
            let totalFees = 0;
            for (const [feeName, amount] of Object.entries(feesData)) {
                feesHtml += `<div class="flex justify-between items-center"><span class="text-sm text-gray-600">${feeName}:</span><span class="text-sm font-medium text-gray-900">₹${parseFloat(amount).toFixed(2)}</span></div>`;
                totalFees += parseFloat(amount);
            }
            
            feesHtml += `<div class="border-t border-gray-200 pt-2 mt-2 flex justify-between items-center"><span class="text-sm font-semibold text-gray-700">Total Fees:</span><span class="text-sm font-bold text-teal-600">₹${totalFees.toFixed(2)}</span></div>`;
            feesHtml += '</div>';
            feesDisplay.innerHTML = feesHtml;
        } catch (e) {
            console.error('Error parsing fees:', e);
            feesDisplay.innerHTML = '<p class="text-sm text-gray-500">-</p>';
        }
    } else {
        feesDisplay.innerHTML = '<p class="text-xs text-gray-500 uppercase font-semibold mb-2">Fees</p><p class="text-sm text-gray-500">No fees configured</p>';
    }
    
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
    // Use the document modal for photos too
    const modal = document.getElementById('documentModal');
    const content = document.getElementById('documentPreviewContent');
    const title = document.getElementById('documentModalTitle');
    const downloadLink = document.getElementById('documentDownloadLink');

    title.innerText = `${studentName} - Photo`;
    
    // Set up download link
    downloadLink.href = photoPath;
    const fileName = photoPath.split('/').pop() || `${studentName.replace(/\s+/g, '_')}_photo.jpg`;
    downloadLink.download = fileName;
    
    // Add onclick handler
    downloadLink.onclick = function(e) {
        e.preventDefault();
        downloadDocument(photoPath, fileName);
    };

    content.innerHTML = '';

    // Display photo
    const img = document.createElement('img');
    img.src = photoPath;
    img.alt = studentName;
    img.className = 'w-full h-auto max-h-[70vh] object-contain rounded-lg mx-auto';
    content.appendChild(img);

    modal.classList.remove('hidden');
}

function closePhotoModal() {
    // This function now closes the document modal
    document.getElementById('documentModal').classList.add('hidden');
    document.getElementById('documentPreviewContent').innerHTML = '';
}

// Document Preview Functions
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
    // Fetch student's current balance and pending payments
    Promise.all([
        fetch(`get_student_details.php?student_id=${student.id}`).then(r => r.json()),
        fetch(`api/get_pending_payments.php?student_id=${student.id}`).then(r => r.json())
    ])
        .then(([balanceData, pendingData]) => {
            if (!balanceData.success) {
                Swal.fire('Error', balanceData.message || 'Failed to fetch student balance', 'error');
                return;
            }
            
            const currentBalance = parseFloat(balanceData.student.balance || 0);
            let pendingPaymentsHtml = '';
            
            // Build pending payments HTML
            if (pendingData.success && pendingData.pending_payments && pendingData.pending_payments.length > 0) {
                pendingPaymentsHtml = '<div class="mb-4 p-3 bg-yellow-50 border border-yellow-300 rounded-md"><div class="text-sm font-semibold text-yellow-800 mb-2">📋 Pending Payments (Click to pay)</div>';
                
                pendingData.pending_payments.forEach(category => {
                    const feeType = category.fee_type;
                    const netAmount = category.net_amount; // negative = owed
                    const displayAmount = netAmount.toFixed(2);
                    const payAmount = Math.abs(netAmount);
                    pendingPaymentsHtml += `
                        <div class="mb-2 p-2 bg-white border border-yellow-200 rounded cursor-pointer hover:bg-yellow-100 transition" onclick="quickPayPending('${student.id}', '${feeType.replace(/'/g, "\\'")}', ${payAmount})">
                            <div class="flex justify-between items-center">
                                <span class="font-medium text-gray-700">${feeType}</span>
                                <span class="text-yellow-700 font-bold">₹${displayAmount}</span>
                            </div>
                            <div class="text-xs text-gray-600 mt-1">${category.items.length} item(s)</div>
                        </div>
                    `;
                });
                
                pendingPaymentsHtml += `<div class="mt-2 pt-2 border-t border-yellow-300">
                    <div class="flex justify-between items-center text-sm font-semibold">
                        <span>Total Pending:</span>
                        <span class="text-red-600">₹${pendingData.total_pending.toFixed(2)}</span>
                    </div>
                </div></div>`;
            }
            
            Swal.fire({
                title: 'Record Student Payment',
                html: `
                    <div class="text-left">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Student Name</label>
                            <input type="text" value="${student.name}" disabled class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-700">
                        </div>
                        
                        <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
                            <p class="text-sm text-gray-700"><strong>Current Balance:</strong> ₹${currentBalance.toFixed(2)}</p>
                        </div>
                        
                        ${pendingPaymentsHtml}
                        
                        <div class="p-3 bg-gray-50 border border-gray-200 rounded-md text-sm text-gray-700">
                            Use the pending payments list above to record payments. Manual entry is disabled for accuracy.
                        </div>
                    </div>
                `,
                showCancelButton: true,
                cancelButtonText: 'Close',
                showConfirmButton: false
            });
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'Failed to load student information', 'error');
        });
}

function quickPayPending(studentId, feeType, amount) {
    // Auto-populate form with pending payment details
    const student = { id: studentId };
    
    // Close current alert if any
    Swal.close();
    
    // Fetch fresh data and reopen with pre-filled info
    fetch(`get_student_details.php?student_id=${studentId}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                Swal.fire('Error', 'Failed to load student details', 'error');
                return;
            }
            
            const currentBalance = parseFloat(data.student.balance || 0);
            
            Swal.fire({
                title: 'Record Payment - ' + feeType,
                html: `
                    <div class="text-left">
                        <div class="mb-4 p-3 bg-green-50 border border-green-300 rounded-md">
                            <p class="text-sm text-green-800"><strong>Pending Amount:</strong> ₹${amount.toFixed(2)}</p>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Amount (₹)</label>
                            <input type="number" id="paymentAmount" value="${amount.toFixed(2)}" min="0.01" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                            <select id="paymentCategory" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100" disabled>
                                <option value="${feeType}" selected>${feeType}</option>
                            </select>
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
                    setTimeout(() => {
                        document.getElementById('paymentAmount').focus();
                    }, 100);
                },
                preConfirm: () => {
                    const amount = parseFloat(document.getElementById('paymentAmount').value);
                    const category = document.getElementById('paymentCategory').value;
                    const description = document.getElementById('paymentDescription').value;
                    
                    if (!amount || amount <= 0) {
                        Swal.showValidationMessage('Please enter a valid amount');
                        return false;
                    }
                    
                    return {
                        student_id: studentId,
                        amount: amount,
                        category: category,
                        description: description,
                        current_balance: currentBalance
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    submitPayment(result.value);
                }
            });
        });
}

function closePaymentModal() {
    // Not needed with SweetAlert2, but kept for compatibility
}

function submitPayment(paymentData) {
    Swal.fire({
        title: 'Processing...',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    const formData = new FormData();
    formData.append('student_id', paymentData.student_id);
    formData.append('amount', paymentData.amount);
    formData.append('category', paymentData.category);
    formData.append('description', paymentData.description);

    fetch('api/save_payment.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Payment has been recorded successfully',
                    confirmButtonColor: '#0d9488',
                    timer: 2000
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: data.message || 'Failed to record payment',
                    confirmButtonColor: '#dc2626'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'An error occurred while recording payment',
                confirmButtonColor: '#dc2626'
            });
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

// Alternative download function with fetch and better error handling
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

// Bulk deactivate by Class + Batch
function deactivateClassBatch() {
    const classVal = document.getElementById('classFilter').value;
    const batchVal = document.getElementById('batchFilter').value;

    if (!classVal || !batchVal) {
        Swal.fire({
            icon: 'warning',
            title: 'Select Class & Batch',
            text: 'Please select both Class and Batch to proceed.'
        });
        return;
    }

    // Show loading while fetching student list
    Swal.fire({
        title: 'Loading...',
        text: 'Fetching student list',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Fetch students matching the class and batch
    fetch(`get_students_by_class_batch.php?class=${encodeURIComponent(classVal)}&batch=${encodeURIComponent(batchVal)}`)
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Failed to fetch students'
                });
                return;
            }

            const students = data.students || [];
            const count = data.count || 0;

            if (count === 0) {
                Swal.fire({
                    icon: 'info',
                    title: 'No Active Students',
                    text: `No active students found in ${classVal} - ${batchVal}`
                });
                return;
            }

            // Build student list HTML
            let studentListHtml = `<div class="text-left mb-3">
                <p class="mb-2"><strong>${count} active student${count === 1 ? '' : 's'}</strong> in <strong>${classVal}</strong> - <strong>${batchVal}</strong> will be deactivated:</p>
                <div class="max-h-60 overflow-y-auto border border-gray-300 rounded p-3 bg-gray-50">
                    <ul class="list-disc list-inside space-y-1">`;
            
            students.forEach(student => {
                const rollNo = student.roll_number || 'N/A';
                studentListHtml += `<li class="text-sm"><strong>${student.name}</strong> (Roll: ${rollNo})</li>`;
            });
            
            studentListHtml += `</ul></div></div>`;

            // Show confirmation with student list
            Swal.fire({
                title: 'Deactivate Students?',
                html: studentListHtml,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, deactivate all',
                cancelButtonText: 'Cancel',
                width: '600px'
            }).then((result) => {
                if (!result.isConfirmed) return;

                const btn = document.getElementById('deactivateClassBatchBtn');
                const originalText = btn.innerText;
                btn.disabled = true;
                btn.innerText = 'Processing...';

                const formData = new FormData();
                formData.append('class', classVal);
                formData.append('batch', batchVal);

                fetch('deactivate_class_batch.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            const updated = Number(data.updated) || 0;
                            Swal.fire({
                                icon: 'success',
                                title: 'Done',
                                text: `Deactivated ${updated} student${updated === 1 ? '' : 's'} successfully.`,
                                timer: 1800,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'Failed to update students.'
                            });
                        }
                    })
                    .catch(err => {
                        console.error('Bulk deactivate error:', err);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An unexpected error occurred.'
                        });
                    })
                    .finally(() => {
                        btn.disabled = false;
                        btn.innerText = originalText;
                    });
            });
        })
        .catch(err => {
            console.error('Error fetching students:', err);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load student list'
            });
        });
}

// Load and display organization fees
async function loadOrgFees() {
    try {
        const response = await fetch('api/manage_fees.php?action=get_fees');
        const data = await response.json();
        
        if (!data.success) {
            console.error('Failed to load fees:', data.message);
            return;
        }
        
        const fees = data.fees || [];
        const wrapper = document.getElementById('feeInputsWrapper');
        
        if (!wrapper) return;
        
        wrapper.innerHTML = '';
        
        if (fees.length === 0) {
            wrapper.innerHTML = '<p class="text-sm text-gray-500 col-span-2">No fees configured. Contact administrator.</p>';
            return;
        }
        
        fees.forEach(fee => {
            const inputId = `fee_${fee.id}`;
            const fieldGroup = document.createElement('div');
            fieldGroup.className = 'form-field-group';
            fieldGroup.innerHTML = `
                <label>${fee.fee_name}</label>
                <input type="number" step="0.01" class="fee-input" id="${inputId}" data-fee-id="${fee.id}" data-fee-name="${fee.fee_name}" placeholder="0.00">
            `;
            wrapper.appendChild(fieldGroup);
        });
    } catch (error) {
        console.error('Error loading fees:', error);
    }
}

// Collect fees data from form and return as formatted string
function collectFeeData() {
    const feeInputs = document.querySelectorAll('.fee-input');
    const feeData = {};
    
    feeInputs.forEach(input => {
        const value = parseFloat(input.value) || 0;
        if (value > 0) {
            const feeName = input.getAttribute('data-fee-name');
            feeData[feeName] = value;
        }
    });
    
    return Object.keys(feeData).length > 0 ? JSON.stringify(feeData) : null;
}

// Populate fees in edit modal
function populateFeesInModal(feesJson) {
    const feeInputs = document.querySelectorAll('.fee-input');
    let feesData = {};
    
    try {
        if (feesJson && typeof feesJson === 'string') {
            feesData = JSON.parse(feesJson);
        }
    } catch (e) {
        console.error('Error parsing fees JSON:', e);
    }
    
    feeInputs.forEach(input => {
        const feeName = input.getAttribute('data-fee-name');
        input.value = feesData[feeName] || '';
    });
}
