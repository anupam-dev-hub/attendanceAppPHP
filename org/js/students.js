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

var table; // Global table variable

$(document).ready(function () {
    table = $('#studentsTable').DataTable({
        responsive: true,
        "pageLength": 10,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
        "order": [
            [10, "desc"],  // Sort by Status (col 10) - Active (1) first, then Inactive (0)
            [3, "asc"],   // Then by Class (col 3)
            [6, "asc"]    // Then by Roll No (col 6)
        ],
        "columnDefs": [
            {
                "orderable": false,
                "targets": [2, 11, 12]
            }, // Photo, QR Code, and Actions columns not sortable
            {
                "responsivePriority": 1,
                "targets": 1
            }, // Name - always visible
            {
                "responsivePriority": 2,
                "targets": 12
            }, // Actions - always visible
            {
                "responsivePriority": 10001,
                "targets": 2
            }, // Photo - hide first
            {
                "responsivePriority": 10002,
                "targets": 7
            }, // Balance - hide second
            {
                "responsivePriority": 10003,
                "targets": 9
            }, // Phone - hide third
            {
                "responsivePriority": 10004,
                "targets": 11
            }, // QR - hide fourth
            {
                "responsivePriority": 3,
                "targets": 3
            }, // Class - keep visible longer
            {
                "responsivePriority": 4,
                "targets": 4
            }, // Stream - keep visible longer
            {
                "responsivePriority": 5,
                "targets": 5
            }, // Batch - keep visible longer
            {
                "responsivePriority": 6,
                "targets": 10
            }, // Status - keep visible longer
            {
                "responsivePriority": 7,
                "targets": 6
            }, // Roll No - keep visible longer
            {
                "responsivePriority": 8,
                "targets": 0
            } // ID - keep visible
        ],
        "language": {
            "lengthMenu": "Show _MENU_ students",
            "info": "Showing _START_ to _END_ of _TOTAL_ students",
            "infoEmpty": "No students to show",
            "infoFiltered": "(filtered from _MAX_ total students)",
            "search": "Search:",
            "zeroRecords": "No matching students found"
        },
        initComplete: function () {
            // Add column search inputs to relevant columns (ID, Name, Class, Stream, Batch, Roll No, Balance, Advance, Phone)
            this.api().columns([0, 1, 3, 4, 5, 6, 7, 8, 9]).every(function () {
                var column = this;
                var title = $(column.header()).text();
                
                // Create input element
                var input = $('<input type="text" placeholder="Search ' + title + '" class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-teal-500 mt-1" />')
                    .appendTo($(column.header()))
                    .on('click', function(e) {
                        e.stopPropagation(); // Prevent sorting when clicking input
                    })
                    .on('keyup change', function () {
                        if (column.search() !== this.value) {
                            column.search(this.value).draw();
                        }
                    });
            });
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
            var streamFilter = $('#streamFilter').val();
            var districtFilter = $('#districtFilter').val();
            var religionFilter = $('#religionFilter').val();
            var communityFilter = $('#communityFilter').val();
            var genderFilter = $('#genderFilter').val();
            var nationalityFilter = $('#nationalityFilter').val();
            var percentageFilter = $('#percentageFilter').val();
            var balanceFilter = $('#balanceFilter').val();
            var advanceFilter = $('#advanceFilter').val();
            
            var row = table.row(dataIndex).node();
            var rowData = table.row(dataIndex).data();
            
            // Status filter
            if (statusFilter !== '') {
                var rowStatus = $(row).attr('data-status');
                if (rowStatus !== statusFilter) return false;
            }
            
            // Get student data from row attributes
            var studentDataAttr = $(row).find('.payment-btn').attr('data-student-fee');
            var studentData = null;
            if (studentDataAttr) {
                try {
                    studentData = JSON.parse(studentDataAttr);
                } catch(e) {}
            }
            
            // Stream filter
            if (streamFilter !== '' && studentData) {
                if ((studentData.stream || '') !== streamFilter) return false;
            }
            
            // Native District filter
            if (districtFilter !== '' && studentData) {
                if ((studentData.native_district || '') !== districtFilter) return false;
            }
            
            // Religion filter
            if (religionFilter !== '' && studentData) {
                if ((studentData.religion || '') !== religionFilter) return false;
            }
            
            // Community filter
            if (communityFilter !== '' && studentData) {
                if ((studentData.community || '') !== communityFilter) return false;
            }
            
            // Gender filter
            if (genderFilter !== '' && studentData) {
                if ((studentData.sex || '') !== genderFilter) return false;
            }
            
            // Nationality filter
            if (nationalityFilter !== '' && studentData) {
                if ((studentData.nationality || '') !== nationalityFilter) return false;
            }
            
            // Percentage filter
            if (percentageFilter !== '' && studentData) {
                var percentage = parseFloat(studentData.exam_percentage) || 0;
                var range = percentageFilter.split('-');
                var min = parseFloat(range[0]);
                var max = parseFloat(range[1]);
                if (percentage < min || percentage > max) return false;
            }
            
            // Balance filter
            if (balanceFilter !== '' && studentData) {
                var balance = parseFloat(studentData.net_balance) || 0;
                if (balanceFilter === 'due' && balance >= 0) return false;
                if (balanceFilter === 'paid' && balance <= 0) return false;
                if (balanceFilter === 'clear' && balance !== 0) return false;
            }
            
            // Advance filter
            if (advanceFilter !== '' && studentData) {
                var advance = parseFloat(studentData.advance_payment) || 0;
                if (advanceFilter === 'has' && advance <= 0) return false;
                if (advanceFilter === 'none' && advance > 0) return false;
            }
            
            return true;
        }
    );

    $('#statusFilter').on('change', function () {
        table.draw();
        updateActiveFilterCount();
    });
    
    // Stream filter
    $('#streamFilter').on('change', function () {
        $(this).removeClass('border-red-500', 'border-2');
        table.draw();
        updateActiveFilterCount();
    });
    
    // Class filter - remove red border on change
    $('#classFilter').on('change', function () {
        $(this).removeClass('border-red-500', 'border-2');
        updateActiveFilterCount();
    });
    
    // Batch filter - remove red border on change
    $('#batchFilter').on('change', function () {
        $(this).removeClass('border-red-500', 'border-2');
        updateActiveFilterCount();
    });
    
    // Native District filter
    $('#districtFilter').on('change', function() {
        table.draw();
        updateActiveFilterCount();
    });
    
    // Religion filter
    $('#religionFilter').on('change', function() {
        table.draw();
        updateActiveFilterCount();
    });
    
    // Community filter
    $('#communityFilter').on('change', function() {
        table.draw();
        updateActiveFilterCount();
    });
    
    // Gender filter
    $('#genderFilter').on('change', function() {
        table.draw();
        updateActiveFilterCount();
    });
    
    // Nationality filter
    $('#nationalityFilter').on('change', function() {
        table.draw();
        updateActiveFilterCount();
    });
    
    // Percentage filter
    $('#percentageFilter').on('change', function() {
        table.draw();
        updateActiveFilterCount();
    });
    
    // Balance filter
    $('#balanceFilter').on('change', function() {
        table.draw();
        updateActiveFilterCount();
    });
    
    // Advance filter
    $('#advanceFilter').on('change', function() {
        table.draw();
        updateActiveFilterCount();
    });
    
    // Update existing filters to update count
    $('#classFilter').on('change', updateActiveFilterCount);
    $('#batchFilter').on('change', updateActiveFilterCount);

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
    
    // Always add/update hidden input for fees_json (even if null/empty)
    let feesInput = document.getElementById('feesJsonInput');
    if (!feesInput) {
        feesInput = document.createElement('input');
        feesInput.type = 'hidden';
        feesInput.id = 'feesJsonInput';
        feesInput.name = 'fees_json';
        document.getElementById('studentForm').appendChild(feesInput);
    }
    feesInput.value = feesJson || '';
    
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
    document.getElementById('studentStream').value = student.stream || '';
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
                             position: "top-end",
                             icon: 'success',
                             toast: true,
                            title: 'Document has been deleted successfully',
                            showConfirmButton: false,
                            timer: 5000,
                            timerProgressBar: true
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

    // Fetch and populate advance payment information
    fetch('api/get_advance_payment.php?student_id=' + student.id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const advanceBalance = data.advance_balance || 0;
                document.getElementById('viewAdvancePayment').innerText = '₹' + Number(advanceBalance).toFixed(2);
                if (advanceBalance > 0) {
                    document.getElementById('viewAdvancePayment').classList.add('text-purple-600');
                } else {
                    document.getElementById('viewAdvancePayment').classList.add('text-gray-600');
                }
            }
        })
        .catch(error => {
            console.error('Error fetching advance payment:', error);
            document.getElementById('viewAdvancePayment').innerText = '₹0.00';
        });



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
                    if (document.getElementById('advanceBalance')) {
                        document.getElementById('advanceBalance').innerText = `₹${Number(data.totals.advance_balance || 0).toFixed(2)}`;
                    }
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
            order: [[1, 'desc']],
            columnDefs: [
                { targets: [0], orderable: false },
                { targets: [5], orderable: false }
            ],
            createdRow: function (row, data) {
                // Add modern styling to rows
                $(row).addClass('transition-all hover:bg-gray-50');
            }
        });
    }

    const rows = payments.map((p, idx) => {
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
        
        // Checkbox with payment data
        const checkbox = `<input type="checkbox" class="payment-select-cb" data-payment='${JSON.stringify(p)}' style="cursor: pointer;">`;
        
        return [
            checkbox,
            dateOnly,
            signedAmt,
            typeBadge,
            p.category || '-',
            (p.description || '-')
        ];
    });
    table.rows.add(rows).draw();
    
    // Add event listeners for checkboxes
    setupPaymentCheckboxListeners();
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
                pendingPaymentsHtml = '<div class="mb-4 p-3 bg-yellow-50 border border-yellow-300 rounded-md"><div class="text-sm font-semibold text-yellow-800 mb-3 flex items-center"><svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>Pending Payments Details</div>';
                
                pendingData.pending_payments.forEach(category => {
                    const feeType = category.fee_type;
                    const netAmount = category.net_amount; // negative = owed
                    const displayAmount = Math.abs(netAmount).toFixed(2);
                    const payAmount = Math.abs(netAmount);
                    
                    // Build unpaid months list
                    let monthsHtml = '';
                    if (category.unpaid_months && category.unpaid_months.length > 0) {
                        monthsHtml = '<div class="mt-2 space-y-1">';
                        category.unpaid_months.forEach(monthData => {
                            monthsHtml += `
                                <div class="flex justify-between items-center text-xs bg-white px-2 py-1 rounded border border-yellow-100">
                                    <span class="text-gray-600">📅 ${monthData.month}</span>
                                    <div class="text-right">
                                        <div class="text-blue-600 font-semibold">₹${monthData.amount.toFixed(2)} <span class="text-gray-500">(Fee)</span></div>
                                        <div class="font-bold text-red-600">₹${monthData.balance.toFixed(2)} <span class="text-gray-500">(Pending)</span></div>
                                    </div>
                                </div>
                            `;
                        });
                        monthsHtml += '</div>';
                    }
                    
                    pendingPaymentsHtml += `
                        <div class="mb-3 p-3 bg-white border border-yellow-200 rounded-lg shadow-sm hover:shadow-md transition cursor-pointer pending-fee-card" data-student-id="${student.id}" data-fee-type="${feeType}" data-amount="${payAmount}">
                            <div class="flex justify-between items-center mb-2">
                                <span class="font-semibold text-gray-800 flex items-center">
                                    <svg class="w-4 h-4 mr-1 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    ${feeType}
                                </span>
                                <span class="text-lg font-bold text-red-600">₹${displayAmount}</span>
                            </div>
                            <div class="text-xs text-gray-500 mb-1">${category.items.length} transaction(s)</div>
                            ${monthsHtml}
                            <div class="mt-2 pt-2 border-t border-gray-200 text-xs text-center text-teal-600 font-medium">
                                💳 Click to pay this fee
                            </div>
                        </div>
                    `;
                });
                
                pendingPaymentsHtml += `<div class="mt-3 pt-3 border-t border-yellow-300">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-semibold text-gray-700">Total Outstanding:</span>
                        <span class="text-xl font-bold text-red-600">₹${Math.abs(pendingData.total_pending).toFixed(2)}</span>
                    </div>
                </div></div>`;
            }
            
            // Fetch advance payment data separately (non-blocking)
            fetch(`api/get_advance_payment.php?student_id=${student.id}`)
                .then(r => r.json())
                .then(advanceData => {
                    const currentAdvance = advanceData.success ? parseFloat(advanceData.advance_balance || 0) : 0;
                    showPaymentModal(student.name, currentBalance, currentAdvance, pendingPaymentsHtml, student.id);
                })
                .catch(err => {
                    console.error('Error fetching advance payment:', err);
                    // Show modal even if advance payment fails
                    showPaymentModal(student.name, currentBalance, 0, pendingPaymentsHtml, student.id);
                });
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'Failed to load student information', 'error');
        });
}

function showPaymentModal(studentName, currentBalance, currentAdvance, pendingPaymentsHtml, studentId) {
    Swal.fire({
        title: 'Student Payment Portal',
        html: `
            <div class="text-left">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Student Name</label>
                    <input type="text" value="${studentName}" disabled class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-700">
                </div>
                
                <div class="mb-4 grid grid-cols-2 gap-3">
                    <div class="p-3 bg-blue-50 border border-blue-200 rounded-md">
                        <p class="text-xs text-blue-600 font-semibold uppercase mb-1">Current Balance</p>
                        <p class="text-lg font-bold text-blue-900">₹${currentBalance.toFixed(2)}</p>
                    </div>
                    <div class="p-3 ${currentAdvance > 0 ? 'bg-purple-50 border-purple-200' : 'bg-gray-50 border-gray-200'} border rounded-md">
                        <p class="text-xs ${currentAdvance > 0 ? 'text-purple-600' : 'text-gray-600'} font-semibold uppercase mb-1">Advance Payment</p>
                        <p class="text-lg font-bold ${currentAdvance > 0 ? 'text-purple-900' : 'text-gray-700'}">₹${currentAdvance.toFixed(2)}</p>
                    </div>
                </div>
                
                ${pendingPaymentsHtml}
                
                <div class="mb-4 p-3 bg-gray-50 border border-gray-200 rounded-md text-sm text-gray-700">
                    <p class="font-semibold mb-2">ℹ️ Payment Options:</p>
                    <ul class="space-y-1 text-xs">
                        <li>✓ Click on pending payments above to pay specific fees</li>
                        <li>✓ Use "Record Advance Payment" button below to collect advance</li>
                        <li>✓ Advance payments auto-deduct from future payments</li>
                    </ul>
                </div>
                
                <button class="w-full bg-purple-600 hover:bg-purple-700 text-white font-semibold py-2 px-4 rounded-lg transition" onclick="recordAdvanceFromModal('${studentId}', '${studentName.replace(/'/g, "\\'")}')">
                    💰 Record Advance Payment
                </button>
            </div>
        `,
        showCancelButton: true,
        cancelButtonText: 'Close',
        showConfirmButton: false,
        didOpen: () => {
            // Add event listeners for pending fee cards
            document.querySelectorAll('.pending-fee-card').forEach(card => {
                card.addEventListener('click', function() {
                    const studentId = this.getAttribute('data-student-id');
                    const feeType = this.getAttribute('data-fee-type');
                    const amount = parseFloat(this.getAttribute('data-amount'));
                    quickPayPending(studentId, feeType, amount);
                });
            });
        }
    });
}

// Record advance payment from payment modal
function recordAdvanceFromModal(studentId, studentName) {
    // Fetch current balance for the modal (non-blocking)
    let currentAdvance = 0;
    let historyHtml = '';
    
    fetch(`api/get_advance_payment.php?student_id=${studentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentAdvance = data.advance_balance || 0;
                
                // Build advance payment history
                if (data.history && data.history.length > 0) {
                    historyHtml = '<div class="mb-4"><p class="text-sm font-semibold text-gray-700 mb-2">Recent Advance Payments:</p>';
                    historyHtml += '<div class="space-y-2 max-h-32 overflow-y-auto">';
                    
                    data.history.slice(0, 3).forEach(h => {
                        const dateObj = new Date(h.payment_date);
                        const dateStr = dateObj.toLocaleDateString('en-IN');
                        historyHtml += `
                            <div class="p-2 bg-gray-50 border border-gray-200 rounded text-xs">
                                <div class="flex justify-between items-center">
                                    <span class="font-medium">₹${parseFloat(h.amount).toFixed(2)}</span>
                                    <span class="text-gray-500">${dateStr}</span>
                                </div>
                            </div>
                        `;
                    });
                    
                    historyHtml += '</div></div>';
                }
            }
            showAdvancePaymentModal(studentId, studentName, currentAdvance, historyHtml);
        })
        .catch(err => {
            console.error('Error fetching advance payment:', err);
            // Show modal even if advance payment fails
            showAdvancePaymentModal(studentId, studentName, 0, '');
        });
}

function showAdvancePaymentModal(studentId, studentName, currentAdvance, historyHtml) {
    Swal.fire({
        title: `Record Advance Payment - ${studentName}`,
        html: `
            <div class="text-left">
                <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
                    <p class="text-sm text-blue-800"><strong>Current Advance Balance:</strong> ₹${currentAdvance.toFixed(2)}</p>
                </div>
                
                ${historyHtml}
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Advance Amount (₹)</label>
                    <input type="number" id="advanceAmount" value="" min="0.01" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Enter advance amount">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description (Optional)</label>
                    <input type="text" id="advanceDescription" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="e.g., Advance for next month">
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Record Advance Payment',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#a855f7',
        didOpen: () => {
            setTimeout(() => {
                document.getElementById('advanceAmount').focus();
            }, 100);
        },
        preConfirm: () => {
            const amount = parseFloat(document.getElementById('advanceAmount').value);
            const description = document.getElementById('advanceDescription').value;
            
            if (!amount || amount <= 0) {
                Swal.showValidationMessage('Please enter a valid amount');
                return false;
            }
            
            return {
                student_id: studentId,
                amount: amount,
                description: description || 'Advance Payment'
            };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            submitAdvancePayment(result.value);
        }
    });
}

async function quickPayPending(studentId, feeType, amount) {
    // Close current alert if any
    Swal.close();

    try {
        // Fetch student details and pending payments in parallel
        const [detailsResp, pendingResp] = await Promise.all([
            fetch(`get_student_details.php?student_id=${studentId}`),
            fetch(`api/get_pending_payments.php?student_id=${studentId}`)
        ]);
        
        if (!detailsResp.ok) {
            throw new Error(`Student details request failed (${detailsResp.status})`);
        }
        const balanceData = await detailsResp.json();
        if (!balanceData.success) {
            Swal.fire('Error', balanceData.message || 'Failed to load student details', 'error');
            return;
        }
        
        // Get pending data
        let pendingMonths = [];
        if (pendingResp.ok) {
            const pendingData = await pendingResp.json();
            if (pendingData.success && pendingData.pending_payments) {
                const feeCategory = pendingData.pending_payments.find(cat => cat.fee_type === feeType);
                if (feeCategory && feeCategory.unpaid_months) {
                    pendingMonths = feeCategory.unpaid_months;
                }
            }
        }

        // Fetch advance payment (tolerant to failure)
        let advanceBalance = 0;
        try {
            const advResp = await fetch(`api/get_advance_payment.php?student_id=${studentId}`);
            if (advResp.ok) {
                const advData = await advResp.json();
                if (advData.success) {
                    advanceBalance = parseFloat(advData.advance_balance || 0);
                }
            }
        } catch (err) {
            console.error('Advance fetch failed:', err);
        }

        const currentBalance = parseFloat(balanceData.student.balance || 0);

        // Calculate amount after advance deduction
        const advanceDeduction = Math.min(advanceBalance, amount);
        const amountAfterAdvance = Math.max(0, amount - advanceDeduction);
        
        // Build pending items breakdown
        let pendingItemsHtml = '';
        if (pendingMonths.length > 0) {
            pendingItemsHtml = `
                <div class="mb-4 p-3 bg-yellow-50 border border-yellow-300 rounded-md">
                    <p class="text-sm text-yellow-800 mb-2 font-semibold flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Pending Items
                    </p>
                    <div class="space-y-1 max-h-32 overflow-y-auto">
            `;
            
            pendingMonths.forEach(monthData => {
                pendingItemsHtml += `
                    <div class="flex justify-between items-center text-xs bg-white px-3 py-2 rounded border border-yellow-200">
                        <span class="text-gray-700 font-medium">📅 ${monthData.month}</span>
                        <div class="text-right">
                            <div class="font-bold text-blue-600">₹${monthData.amount.toFixed(2)} <span class="text-xs text-gray-500">(Fee)</span></div>
                            <div class="font-bold text-red-600">₹${monthData.balance.toFixed(2)} <span class="text-xs text-gray-500">(Pending)</span></div>
                        </div>
                    </div>
                `;
            });
            
            pendingItemsHtml += `
                    </div>
                    <div class="mt-2 pt-2 border-t border-yellow-300 flex justify-between items-center text-sm">
                        <span class="font-semibold text-gray-700">Total:</span>
                        <span class="font-bold text-red-600">₹${amount.toFixed(2)}</span>
                    </div>
                </div>
            `;
        }

        let advanceHtml = '';
        if (advanceBalance > 0) {
            advanceHtml = `
                <div class="mb-4 p-3 bg-purple-50 border border-purple-300 rounded-md">
                    <p class="text-sm text-purple-800 mb-2"><strong>💜 Advance Payment Available</strong></p>
                    <div class="grid grid-cols-3 gap-2 text-sm">
                        <div>
                            <p class="text-xs text-purple-600 font-semibold">Advance Balance</p>
                            <p class="font-bold text-purple-900">₹${advanceBalance.toFixed(2)}</p>
                        </div>
                        <div>
                            <p class="text-xs text-purple-600 font-semibold">Will Deduct</p>
                            <p class="font-bold text-green-600">₹${advanceDeduction.toFixed(2)}</p>
                        </div>
                        <div>
                            <p class="text-xs text-purple-600 font-semibold">Remaining Advance</p>
                            <p class="font-bold text-purple-900">₹${(advanceBalance - advanceDeduction).toFixed(2)}</p>
                        </div>
                    </div>
                </div>
            `;
        }

        Swal.fire({
            title: 'Record Payment - ' + feeType,
            html: `
                <div class="text-left">
                    <div class="mb-4 p-3 bg-blue-50 border border-blue-300 rounded-md">
                        <p class="text-sm text-blue-800"><strong>📋 Fee Details</strong></p>
                        <div class="grid grid-cols-2 gap-3 mt-2 text-sm">
                            <div>
                                <p class="text-xs text-blue-600 font-semibold">Fee Type</p>
                                <p class="font-bold text-blue-900">${feeType}</p>
                            </div>
                            <div>
                                <p class="text-xs text-blue-600 font-semibold">Total Due</p>
                                <p class="font-bold text-blue-900">₹${amount.toFixed(2)}</p>
                            </div>
                            <div>
                                <p class="text-xs text-blue-600 font-semibold">Current Balance</p>
                                <p class="font-bold text-blue-900">₹${currentBalance.toFixed(2)}</p>
                            </div>
                            <div>
                                <p class="text-xs text-blue-600 font-semibold">Status</p>
                                <p class="font-bold text-red-600">Outstanding</p>
                            </div>
                        </div>
                    </div>
                    
                    ${pendingItemsHtml}
                    
                    ${advanceHtml}
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Amount to Pay (₹)</label>
                        <input type="number" id="paymentAmount" value="${amount.toFixed(2)}" min="0.01" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                        <p class="text-xs text-gray-600 mt-1">📌 You can modify this amount for partial payment. Advance will auto-deduct from the amount you enter.</p>
                    </div>
                    
                    ${amountAfterAdvance > 0 ? `
                        <div class="mb-4 p-3 bg-orange-50 border border-orange-300 rounded-md">
                            <p class="text-sm text-orange-800"><strong>⚠️ Amount to Collect After Advance</strong></p>
                            <p class="text-lg font-bold text-orange-900 mt-1">₹${amountAfterAdvance.toFixed(2)}</p>
                        </div>
                    ` : `
                        <div class="mb-4 p-3 bg-green-50 border border-green-300 rounded-md">
                            <p class="text-sm text-green-800"><strong>✅ Fully Covered by Advance</strong></p>
                            <p class="text-xs text-green-700 mt-1">This fee will be completely covered by advance payment</p>
                        </div>
                    `}
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description (Optional)</label>
                        <textarea id="paymentDescription" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" rows="2" placeholder="Enter description..."></textarea>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Record Payment',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#16a34a',
            didOpen: () => {
                setTimeout(() => {
                    document.getElementById('paymentDescription').focus();
                }, 100);
            },
            preConfirm: () => {
                const payAmount = parseFloat(document.getElementById('paymentAmount').value);
                const description = document.getElementById('paymentDescription').value;
                
                if (!payAmount || payAmount <= 0) {
                    Swal.showValidationMessage('Please enter a valid amount');
                    return false;
                }
                
                return {
                    student_id: studentId,
                    amount: payAmount,
                    original_fee_amount: amount,
                    category: feeType,
                    description: description || 'Payment for ' + feeType,
                    advance_balance: advanceBalance,
                    advance_deduction: advanceDeduction,
                    amount_after_advance: amountAfterAdvance,
                    current_balance: currentBalance
                };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                submitPayment(result.value);
            }
        });
    } catch (err) {
        console.error('Error:', err);
        Swal.fire('Error', 'Failed to load payment details', 'error');
    }
}

function closePaymentModal() {
    // Not needed with SweetAlert2, but kept for compatibility
}

function submitPayment(paymentData) {
    // Check if payment amount is greater than original fee amount
    if (paymentData.original_fee_amount && paymentData.amount > paymentData.original_fee_amount) {
        const extraAmount = paymentData.amount - paymentData.original_fee_amount;
        
        Swal.fire({
            title: 'Extra Payment Detected!',
            html: `
                <div class="text-left">
                    <div class="mb-4 p-3 bg-blue-50 border border-blue-300 rounded-md">
                        <p class="text-sm text-blue-800"><strong>Fee Amount:</strong> ₹${paymentData.original_fee_amount.toFixed(2)}</p>
                        <p class="text-sm text-blue-800"><strong>Amount Paid:</strong> ₹${paymentData.amount.toFixed(2)}</p>
                        <p class="text-sm text-green-800 font-bold mt-2"><strong>Extra Amount:</strong> ₹${extraAmount.toFixed(2)}</p>
                    </div>
                    <p class="text-sm text-gray-700 mb-3">The student has paid <strong>₹${extraAmount.toFixed(2)}</strong> more than the fee amount.</p>
                    <p class="text-sm text-gray-700 font-semibold">This extra amount will be saved as advance payment for future use.</p>
                </div>
            `,
            icon: 'question',
            showCancelButton: false,
            confirmButtonText: '✓ Save as Advance',
            confirmButtonColor: '#a855f7'
        }).then((result) => {
            if (result.isConfirmed) {
                // User wants to save extra as advance
                processPaymentWithAdvance(paymentData, extraAmount);
            }
        });
    } else {
        // Normal payment, no extra amount
        processPaymentNormally(paymentData);
    }
}

function processPaymentWithAdvance(paymentData, extraAmount) {
    Swal.fire({
        title: 'Processing...',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    let paymentResult = null; // capture main payment result for receipt

    const formData = new FormData();
    formData.append('student_id', paymentData.student_id);
    formData.append('amount', paymentData.original_fee_amount); // Only record the actual fee amount
    formData.append('category', paymentData.category);
    formData.append('description', paymentData.description);

    // First, record the payment (only the fee amount)
    fetch('api/save_payment.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                paymentResult = data;
                // Payment recorded, now save extra as advance
                const advanceFormData = new FormData();
                advanceFormData.append('student_id', paymentData.student_id);
                advanceFormData.append('amount', extraAmount);
                advanceFormData.append('description', 'Extra payment saved as advance');

                return fetch('api/save_advance_payment.php', {
                    method: 'POST',
                    body: advanceFormData
                });
            } else {
                throw new Error(data.message || 'Failed to record payment');
            }
        })
        .then(async response => {
            if (!response.ok) {
                const text = await response.text();
                throw new Error(`Advance payment failed (${response.status}): ${text || 'Unknown error'}`);
            }
            return response.json();
        })
        .then(advanceData => {
            if (advanceData.success) {
                Swal.fire({
                    icon: 'success',
                    title: `Payment recorded successfully!`,
                    html: `<p>Fee recorded and extra ₹${extraAmount.toFixed(2)} saved as advance.</p><p>Print combined receipt?</p>`,
                    confirmButtonColor: '#16a34a',
                    confirmButtonText: '🖨️ Print Receipt',
                    showCancelButton: true,
                    cancelButtonText: 'Close',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        const studentData = {
                            name: (paymentResult?.student_name) || advanceData.student_name || 'Student',
                            student_id: paymentData.student_id,
                            class_name: (paymentResult?.class_name) || advanceData.class_name || '',
                            batch_name: (paymentResult?.batch_name) || advanceData.batch_name || ''
                        };
                        const baseAmount = paymentResult?.amount ?? paymentData.original_fee_amount ?? 0;
                        const paymentDist = paymentResult?.payment_distribution ? [...paymentResult.payment_distribution] : [];
                        paymentDist.push({ month: 'Advance', fee_category: 'Advance Saved', amount_paid: extraAmount });
                        const receiptData = {
                            payment_id: paymentResult?.payment_id || advanceData.payment_id,
                            amount: baseAmount + extraAmount,
                            fee_category: (paymentResult?.fee_category || paymentData.category || 'Fee') + ' + Advance',
                            payment_mode: paymentResult?.payment_mode || 'Cash',
                            payment_date: paymentResult?.payment_date || new Date().toISOString(),
                            student_name: studentData.name,
                            class_name: studentData.class_name,
                            batch_name: studentData.batch_name,
                            payment_distribution: paymentDist,
                            remaining_months: paymentResult?.remaining_months || []
                        };
                        generateReceipt(receiptData, studentData, receiptData.remaining_months);
                    }
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                });
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Partial Success',
                    text: 'Payment recorded but failed to save extra as advance: ' + advanceData.message,
                    confirmButtonColor: '#f59e0b'
                }).then(() => {
                    location.reload();
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'An error occurred while processing payment: ' + error.message,
                confirmButtonColor: '#dc2626'
            });
        });
}

function processPaymentNormally(paymentData) {
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
    // If original_fee_amount exists and is less than entered amount, use original fee amount
    // Otherwise use the amount entered
    const amountToRecord = (paymentData.original_fee_amount && paymentData.amount > paymentData.original_fee_amount) 
        ? paymentData.original_fee_amount 
        : paymentData.amount;
    formData.append('amount', amountToRecord);
    formData.append('category', paymentData.category);
    formData.append('description', paymentData.description);

    fetch('api/save_payment.php', {
        method: 'POST',
        body: formData
    })
        .then(async response => {
            if (!response.ok) {
                const text = await response.text();
                throw new Error(`Server ${response.status}: ${text || 'Failed to record payment'}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Payment response data:', data);
            
            if (data.success) {
                let message = 'Payment recorded successfully';
                let remainingHtml = '';
                
                // Show remaining months breakdown
                if (data.remaining_months && data.remaining_months.length > 0) {
                    remainingHtml = '<div class="mt-3 p-3 bg-yellow-50 border border-yellow-300 rounded-md text-left">';
                    remainingHtml += '<p class="text-sm font-semibold text-yellow-800 mb-2">📋 Remaining Pending:</p>';
                    remainingHtml += '<div class="space-y-1">';
                    
                    data.remaining_months.forEach(month => {
                        remainingHtml += `<div class="flex justify-between items-center text-sm border-b border-yellow-200 pb-1 mb-1 last:border-0">
                            <span class="text-gray-700 font-medium">${month.month}</span>
                            <div class="text-right">
                                <div class="text-xs text-gray-500">Fee: ₹${parseFloat(month.original_fee).toFixed(2)}</div>
                                <div class="font-bold text-red-600">Pending: ₹${parseFloat(month.remaining).toFixed(2)}</div>
                            </div>
                        </div>`;
                    });
                    
                    remainingHtml += `</div>`;
                    remainingHtml += `<div class="mt-2 pt-2 border-t border-yellow-300 flex justify-between text-sm">
                        <span class="font-semibold text-gray-700">Total Remaining:</span>
                        <span class="font-bold text-red-600">₹${parseFloat(data.total_remaining || 0).toFixed(2)}</span>
                    </div>`;
                    remainingHtml += '</div>';
                }
                
                if (data.advance_deducted && data.advance_deducted > 0) {
                    message = `Payment recorded! ₹${data.advance_deducted.toFixed(2)} deducted from advance.`;
                }
                
                if (remainingHtml) {
                    Swal.fire({
                        icon: 'success',
                        title: message,
                        html: remainingHtml,
                        confirmButtonColor: '#16a34a',
                        confirmButtonText: 'OK',
                        showDenyButton: true,
                        denyButtonText: '🖨️ Print Receipt',
                        denyButtonColor: '#3b82f6',
                        allowOutsideClick: false,
                        allowEscapeKey: false
                    }).then((result) => {
                        if (result.isDenied) {
                            console.log('Print Receipt clicked');
                            // Student data comes from API response
                            const studentData = {
                                name: data.student_name || 'Student',
                                student_id: paymentData.student_id,
                                class_name: data.class_name || '',
                                batch_name: data.batch_name || ''
                            };
                            console.log('Generating receipt with:', studentData, data.remaining_months);
                            generateReceipt(data, studentData, data.remaining_months || []);
                        }
                        // Reload after button is clicked
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    });
                } else {
                    Swal.fire({
                        icon: 'success',
                        title: message,
                        html: '<p>All fees cleared! A receipt will be generated.</p>',
                        confirmButtonColor: '#16a34a',
                        confirmButtonText: 'Print Receipt',
                        allowOutsideClick: false,
                        allowEscapeKey: false
                    }).then(() => {
                        console.log('All fees cleared - generating receipt');
                        // Student data comes from API response
                        const studentData = {
                            name: data.student_name || 'Student',
                            student_id: paymentData.student_id,
                            class_name: data.class_name || '',
                            batch_name: data.batch_name || ''
                        };
                        console.log('Generating receipt with:', studentData, data.remaining_months);
                        generateReceipt(data, studentData, data.remaining_months || []);
                        
                        // Reload after receipt generation
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    });
                }
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
                text: error.message || 'An error occurred while recording payment',
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

// ============ ADVANCE PAYMENT FUNCTIONS ============

// Open advance payment modal
function openAdvancePaymentModal(student) {
    // Fetch current advance payment balance
    fetch(`api/get_advance_payment.php?student_id=${student.id}`)
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                Swal.fire('Error', 'Failed to fetch advance payment info', 'error');
                return;
            }
            
            const currentAdvance = data.advance_balance || 0;
            let historyHtml = '';
            
            // Build advance payment history
            if (data.history && data.history.length > 0) {
                historyHtml = '<div class="mb-4"><p class="text-sm font-semibold text-gray-700 mb-2">Advance Payment History:</p>';
                historyHtml += '<div class="space-y-2 max-h-48 overflow-y-auto">';
                
                data.history.forEach(h => {
                    const dateObj = new Date(h.payment_date);
                    const dateStr = dateObj.toLocaleDateString('en-IN');
                    historyHtml += `
                        <div class="p-2 bg-gray-50 border border-gray-200 rounded">
                            <div class="flex justify-between items-center">
                                <span class="font-medium text-gray-700">₹${parseFloat(h.amount).toFixed(2)}</span>
                                <span class="text-xs text-gray-500">${dateStr}</span>
                            </div>
                            <div class="text-xs text-gray-600">${h.description || 'Advance Payment'}</div>
                        </div>
                    `;
                });
                
                historyHtml += '</div></div>';
            }
            
            Swal.fire({
                title: `Advance Payment - ${student.name}`,
                html: `
                    <div class="text-left">
                        <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
                            <p class="text-sm text-blue-800"><strong>Current Advance Balance:</strong> ₹${currentAdvance.toFixed(2)}</p>
                        </div>
                        
                        ${historyHtml}
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Advance Amount (₹)</label>
                            <input type="number" id="advanceAmount" value="" min="0.01" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Enter advance amount">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description (Optional)</label>
                            <input type="text" id="advanceDescription" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="e.g., Advance for next month">
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Record Advance Payment',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#0d9488',
                didOpen: () => {
                    setTimeout(() => {
                        document.getElementById('advanceAmount').focus();
                    }, 100);
                },
                preConfirm: () => {
                    const amount = parseFloat(document.getElementById('advanceAmount').value);
                    const description = document.getElementById('advanceDescription').value;
                    
                    if (!amount || amount <= 0) {
                        Swal.showValidationMessage('Please enter a valid amount');
                        return false;
                    }
                    
                    return {
                        student_id: student.id,
                        amount: amount,
                        description: description || 'Advance Payment'
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    submitAdvancePayment(result.value);
                }
            });
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'Failed to load advance payment information', 'error');
        });
}

// Submit advance payment
function submitAdvancePayment(paymentData) {
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
    formData.append('description', paymentData.description);

    console.log('Submitting advance payment:', {
        student_id: paymentData.student_id,
        amount: paymentData.amount,
        description: paymentData.description
    });

    fetch('api/save_advance_payment.php', {
        method: 'POST',
        body: formData
    })
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Advance payment recorded successfully',
                    text: 'Would you like to print a receipt?',
                    confirmButtonColor: '#16a34a',
                    confirmButtonText: '🖨️ Print Receipt',
                    showCancelButton: true,
                    cancelButtonText: 'Close',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        const studentData = {
                            name: data.student_name || 'Student',
                            student_id: paymentData.student_id,
                            class_name: data.class_name || '',
                            batch_name: data.batch_name || ''
                        };
                        const receiptData = {
                            payment_id: data.payment_id,
                            amount: paymentData.amount,
                            fee_category: 'Advance Payment',
                            payment_mode: 'Cash',
                            payment_date: new Date().toISOString(),
                            student_name: data.student_name,
                            class_name: data.class_name,
                            batch_name: data.batch_name,
                            payment_distribution: [
                                {
                                    month: '-',
                                    fee_category: 'Advance Payment',
                                    amount_paid: paymentData.amount
                                }
                            ],
                            remaining_months: []
                        };
                        generateReceipt(receiptData, studentData, []);
                    }
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: data.message || 'Failed to record advance payment',
                    confirmButtonColor: '#dc2626'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'An error occurred while recording advance payment: ' + error.message,
                confirmButtonColor: '#dc2626'
            });
        });
}

// ============ END ADVANCE PAYMENT FUNCTIONS ============

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
                             position: "top-end",
                             icon: 'success',
                             toast: true,
                            title: data.message,
                            showConfirmButton: false,
                            timer: 1500,
                            timerProgressBar: true
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
    const streamVal = document.getElementById('streamFilter').value;

    // Remove any existing red borders
    document.getElementById('classFilter').classList.remove('border-red-500', 'border-2');
    document.getElementById('batchFilter').classList.remove('border-red-500', 'border-2');
    document.getElementById('streamFilter').classList.remove('border-red-500', 'border-2');

    if (!classVal || !batchVal || !streamVal) {
        // Show the filters panel if hidden
        const panel = document.getElementById('advancedFiltersPanel');
        if (panel.classList.contains('hidden')) {
            panel.classList.remove('hidden');
        }
        
        // Add red border to empty filters
        if (!classVal) {
            document.getElementById('classFilter').classList.add('border-red-500', 'border-2');
        }
        if (!batchVal) {
            document.getElementById('batchFilter').classList.add('border-red-500', 'border-2');
        }
        if (!streamVal) {
            document.getElementById('streamFilter').classList.add('border-red-500', 'border-2');
        }
        
        Swal.fire({
            icon: 'warning',
            title: 'Select Class, Batch & Stream',
            text: 'Please select Class, Batch and Stream to proceed.'
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

    // Fetch students matching the class, batch, and stream
    let fetchUrl = `get_students_by_class_batch.php?class=${encodeURIComponent(classVal)}&batch=${encodeURIComponent(batchVal)}&stream=${encodeURIComponent(streamVal)}`;
    fetch(fetchUrl)
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
                    text: `No active students found in ${classVal} - ${batchVal} - ${streamVal}`
                });
                return;
            }

            // Build student list HTML
            let studentListHtml = `<div class="text-left mb-3">
                <p class="mb-2"><strong>${count} active student${count === 1 ? '' : 's'}</strong> in <strong>${classVal}</strong> - <strong>${batchVal}</strong> - <strong>${streamVal}</strong> will be deactivated:</p>
                <div class="max-h-60 overflow-y-auto border border-gray-300 rounded p-3 bg-gray-50">
                    <ul class="list-disc list-inside space-y-1">`;
            
            students.forEach(student => {
                const rollNo = student.roll_number || 'N/A';
                const stream = student.stream ? ` - ${student.stream}` : '';
                studentListHtml += `<li class="text-sm"><strong>${student.name}</strong> (Roll: ${rollNo}${stream})</li>`;
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

                // Show loading state
                Swal.fire({
                    title: 'Processing...',
                    text: 'Deactivating students',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                const formData = new FormData();
                formData.append('class', classVal);
                formData.append('batch', batchVal);
                formData.append('stream', streamVal);

                fetch('deactivate_class_batch.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            const updated = Number(data.updated) || 0;
                            Swal.fire({
                                position: "top-end",
                                icon: 'success',
                                toast: true,
                                title: `Deactivated ${updated} student${updated === 1 ? '' : 's'} successfully.`,
                                timer: 1800,
                                timerProgressBar: true,
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

// Advanced Filters Functions
function toggleAdvancedFilters() {
    const panel = document.getElementById('advancedFiltersPanel');
    panel.classList.toggle('hidden');
}

function clearAllFilters() {
    // Reset all filter selects
    document.getElementById('classFilter').value = '';
    document.getElementById('batchFilter').value = '';
    document.getElementById('streamFilter').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('districtFilter').value = '';
    document.getElementById('religionFilter').value = '';
    document.getElementById('communityFilter').value = '';
    document.getElementById('genderFilter').value = '';
    document.getElementById('nationalityFilter').value = '';
    document.getElementById('percentageFilter').value = '';
    document.getElementById('balanceFilter').value = '';
    document.getElementById('advanceFilter').value = '';
    
    // Trigger redraw
    table.draw();
    updateActiveFilterCount();
}

function updateActiveFilterCount() {
    const filters = [
        'classFilter', 'batchFilter', 'streamFilter', 'statusFilter', 'districtFilter',
        'religionFilter', 'communityFilter', 'genderFilter', 'nationalityFilter',
        'percentageFilter', 'balanceFilter', 'advanceFilter'
    ];
    
    let count = 0;
    filters.forEach(id => {
        const elem = document.getElementById(id);
        if (elem && elem.value !== '') count++;
    });
    
    const badge = document.getElementById('activeFiltersCount');
    if (badge) {
        if (count > 0) {
            badge.textContent = count;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }
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

// Setup payment checkbox listeners
function setupPaymentCheckboxListeners() {
    // Select all checkbox
    $('#selectAllPayments').off('change').on('change', function() {
        const isChecked = $(this).prop('checked');
        $('.payment-select-cb').prop('checked', isChecked);
        togglePrintReceiptButton();
    });
    
    // Individual checkboxes
    $('.payment-select-cb').off('change').on('change', function() {
        const totalCheckboxes = $('.payment-select-cb').length;
        const checkedCheckboxes = $('.payment-select-cb:checked').length;
        $('#selectAllPayments').prop('checked', totalCheckboxes === checkedCheckboxes);
        togglePrintReceiptButton();
    });
}

function togglePrintReceiptButton() {
    const checkedCount = $('.payment-select-cb:checked').length;
    const btn = $('#printSelectedReceiptBtn');
    if (checkedCount > 0) {
        btn.removeClass('hidden');
        btn.text(`🖨️ Print Receipt (${checkedCount} selected)`);
    } else {
        btn.addClass('hidden');
    }
}

function printSelectedPaymentReceipt() {
    const selected = $('.payment-select-cb:checked');
    if (selected.length === 0) {
        Swal.fire('No Selection', 'Please select at least one payment to print receipt.', 'info');
        return;
    }
    
    // Get selected payment data
    const payments = [];
    selected.each(function() {
        const paymentData = $(this).data('payment');
        if (paymentData) {
            payments.push(paymentData);
        }
    });
    
    if (payments.length === 0) {
        Swal.fire('Error', 'No payment data found for selected items.', 'error');
        return;
    }
    
    // Get current student data from modal
    const studentData = {
        name: currentViewStudent?.name || 'Student',
        student_id: currentViewStudent?.id || 'N/A',
        class_name: currentViewStudent?.class_name || currentViewStudent?.class || '',
        batch_name: currentViewStudent?.batch_name || currentViewStudent?.batch || ''
    };
    
    // Calculate totals
    let totalAmount = 0;
    const distribution = [];
    
    payments.forEach(p => {
        const amt = Number(p.amount) || 0;
        const type = (p.transaction_type || '').toLowerCase();
        
        // Only include debit (payments received) in receipt
        if (type === 'debit') {
            totalAmount += amt;
            distribution.push({
                month: p.date ? p.date.split(' ')[0] : '-',
                fee_category: p.category || 'Payment',
                amount_paid: amt
            });
        }
    });
    
    if (totalAmount === 0) {
        Swal.fire('No Payments', 'Selected items contain no payment records (debits only).', 'info');
        return;
    }
    
    // Build receipt data
    const receiptData = {
        payment_id: payments[0].id || 'N/A',
        amount: totalAmount,
        fee_category: payments.length > 1 ? 'Multiple Payments' : (payments[0].category || 'Payment'),
        payment_mode: 'Cash',
        payment_date: payments[0].date || new Date().toISOString(),
        student_name: studentData.name,
        class_name: studentData.class_name,
        batch_name: studentData.batch_name,
        org_name: currentViewStudent?.org_name || 'Educational Institution',
        org_logo: currentViewStudent?.org_logo || '',
        payment_distribution: distribution,
        remaining_months: []
    };
    
    generateReceipt(receiptData, studentData, []);
}

// Generate printable receipt
function generateReceipt(paymentData, studentData, remainingMonths) {
    console.log('generateReceipt called with:', { paymentData, studentData, remainingMonths });
    
    // Show loading indicator
    Swal.fire({
        title: 'Generating Receipt...',
        text: 'Please wait',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    setTimeout(() => {
        const receiptWindow = window.open('', '_blank', 'width=800,height=600');
        
        if (!receiptWindow) {
            Swal.fire({
                icon: 'error',
                title: 'Popup Blocked!',
                text: 'Please allow popups for this site to generate the receipt.',
                confirmButtonColor: '#dc2626'
            });
            return;
        }
        
        Swal.close();
    
    // Format date
    const formatDate = (dateStr) => {
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-IN', { 
            day: '2-digit', 
            month: 'short', 
            year: 'numeric' 
        });
    };
    
    // Format currency
    const formatCurrency = (amount) => {
        return '₹' + parseFloat(amount).toFixed(2);
    };
    
    // Build payment distribution table (detailed rows per fee month and advance)
    let distributionHTML = '';
    const paymentDist = paymentData.payment_distribution ? [...paymentData.payment_distribution] : [];
    // If advance was used to cover part of payment, show it as an adjustment line
    if (paymentData.advance_deducted && Number(paymentData.advance_deducted) > 0) {
        paymentDist.push({
            month: '-',
            fee_category: 'Advance adjustment',
            amount_paid: -Number(paymentData.advance_deducted)
        });
    }
    const feeTotal = paymentDist
        .filter(item => !(String(item.fee_category || '').toLowerCase().includes('advance')) && item.month !== 'Advance')
        .reduce((sum, item) => sum + Number(item.amount_paid || 0), 0);
    const advanceTotal = paymentDist
        .filter(item => String(item.fee_category || '').toLowerCase().includes('advance') || item.month === 'Advance')
        .reduce((sum, item) => sum + Number(item.amount_paid || 0), 0);
    const totalPaid = (Number(paymentData.amount) || 0) || (feeTotal + advanceTotal);
    const advanceDeducted = Number(paymentData.advance_deducted || 0);
    const netCashPaid = Math.max(0, totalPaid - advanceDeducted);

    if (paymentDist.length > 0 || feeTotal > 0 || advanceTotal > 0) {
        distributionHTML = `
            <div style="margin-bottom: 20px;">
                <h3 style="font-size: 14px; font-weight: bold; margin-bottom: 10px; border-bottom: 2px solid #333; padding-bottom: 5px;">
                    Payment Distribution
                </h3>
                <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                    <thead>
                        <tr style="background-color: #f0f0f0;">
                            <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Month</th>
                            <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Fee Category</th>
                            <th style="border: 1px solid #ddd; padding: 8px; text-align: right;">Amount Paid</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${paymentDist.map(item => `
                            <tr>
                                <td style=\"border: 1px solid #ddd; padding: 8px;\">${item.month}</td>
                                <td style=\"border: 1px solid #ddd; padding: 8px;\">${item.fee_category}</td>
                                <td style=\"border: 1px solid #ddd; padding: 8px; text-align: right;\">${formatCurrency(item.amount_paid)}</td>
                            </tr>
                        `).join('')}
                        ${paymentDist.length === 0 && feeTotal > 0 ? `
                            <tr>
                                <td style=\"border: 1px solid #ddd; padding: 8px;\">Fee payment</td>
                                <td style=\"border: 1px solid #ddd; padding: 8px;\">–</td>
                                <td style=\"border: 1px solid #ddd; padding: 8px; text-align: right;\">${formatCurrency(feeTotal)}</td>
                            </tr>` : ''}
                        ${paymentDist.length === 0 && advanceTotal > 0 ? `
                            <tr>
                                <td style=\"border: 1px solid #ddd; padding: 8px;\">Advance saved</td>
                                <td style=\"border: 1px solid #ddd; padding: 8px;\">–</td>
                                <td style=\"border: 1px solid #ddd; padding: 8px; text-align: right;\">${formatCurrency(advanceTotal)}</td>
                            </tr>` : ''}
                    </tbody>
                </table>
            </div>
        `;
    }
    
    // Build remaining balance table
    let remainingHTML = '';
    if (remainingMonths && remainingMonths.length > 0) {
        remainingHTML = `
            <div style="margin-bottom: 20px;">
                <h3 style="font-size: 14px; font-weight: bold; margin-bottom: 10px; border-bottom: 2px solid #333; padding-bottom: 5px;">
                    Remaining Pending Fees
                </h3>
                <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                    <thead>
                        <tr style="background-color: #f0f0f0;">
                            <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Month</th>
                            <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Fee Category</th>
                            <th style="border: 1px solid #ddd; padding: 8px; text-align: right;">Original Fee</th>
                            <th style="border: 1px solid #ddd; padding: 8px; text-align: right;">Pending Amount</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        remainingMonths.forEach(item => {
            remainingHTML += `
                        <tr>
                            <td style="border: 1px solid #ddd; padding: 8px;">${item.month}</td>
                            <td style="border: 1px solid #ddd; padding: 8px;">${item.fee_category}</td>
                            <td style="border: 1px solid #ddd; padding: 8px; text-align: right;">${formatCurrency(item.original_fee)}</td>
                            <td style="border: 1px solid #ddd; padding: 8px; text-align: right;">${formatCurrency(item.remaining)}</td>
                        </tr>
            `;
        });
        
        remainingHTML += `
                    </tbody>
                </table>
            </div>
        `;
    } else {
        remainingHTML = `
            <div style="padding: 15px; background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; color: #155724; font-size: 13px; margin-bottom: 20px;">
                ✓ All pending fees have been cleared for this fee category.
            </div>
        `;
    }
    
    // Get organization name and logo from payment data
    const orgName = paymentData.org_name || 'Educational Institution';
    const orgLogo = paymentData.org_logo || '';
    
    const receiptHTML = `
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Payment Receipt - ${studentData.name}</title>
    <style>
        @page {
            size: A4;
            margin: 15mm;
        }
        
        @media print {
            body {
                width: 210mm;
                height: 297mm;
            }
            .no-print {
                display: none !important;
            }
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background-color: #fff;
            color: #333;
        }
        
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            border: 2px solid #333;
            padding: 30px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px double #333;
            padding-bottom: 15px;
        }
        
        .header h1 {
            font-size: 24px;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        .header h2 {
            font-size: 18px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .receipt-number {
            text-align: right;
            font-size: 12px;
            margin-bottom: 20px;
            color: #666;
        }
        
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
            gap: 20px;
        }
        
        .info-block {
            flex: 1;
        }
        
        .info-block h3 {
            font-size: 14px;
            margin-bottom: 10px;
            border-bottom: 1px solid #333;
            padding-bottom: 5px;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 5px;
            font-size: 12px;
        }
        
        .info-label {
            font-weight: bold;
            width: 120px;
        }
        
        .info-value {
            flex: 1;
        }
        
        .payment-summary {
            background-color: #f9f9f9;
            border: 2px solid #333;
            padding: 15px;
            margin-bottom: 25px;
        }
        
        .payment-summary h3 {
            font-size: 16px;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .payment-amount {
            text-align: center;
            font-size: 28px;
            font-weight: bold;
            color: #2e7d32;
            margin: 10px 0;
        }
        
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
            padding-top: 20px;
        }
        
        .signature-block {
            text-align: center;
            width: 200px;
        }
        
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 60px;
            padding-top: 5px;
            font-size: 12px;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            font-size: 11px;
            color: #666;
        }
        
        .button-container {
            text-align: center;
            margin-top: 20px;
            gap: 10px;
            display: flex;
            justify-content: center;
        }
        
        button {
            padding: 10px 25px;
            font-size: 14px;
            cursor: pointer;
            border: none;
            border-radius: 4px;
            font-weight: bold;
        }
        
        .print-btn {
            background-color: #2196F3;
            color: white;
        }
        
        .print-btn:hover {
            background-color: #1976D2;
        }
        
        .close-btn {
            background-color: #f44336;
            color: white;
        }
        
        .close-btn:hover {
            background-color: #da190b;
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="header">
            ${orgLogo ? `<img src="${orgLogo}" alt="Logo" style="max-width: 80px; max-height: 80px; margin-bottom: 10px;">` : ''}
            <h1>${orgName}</h1>
            <h2>Fee Payment Receipt</h2>
        </div>
        
        <div class="receipt-number">
            Receipt No: ${paymentData.payment_id || 'N/A'} | Date: ${formatDate(paymentData.payment_date || new Date())}
        </div>
        
        <div class="info-section">
            <div class="info-block">
                <h3>Student Information</h3>
                <div class="info-row">
                    <span class="info-label">Student Name:</span>
                    <span class="info-value">${studentData.name}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Student ID:</span>
                    <span class="info-value">${studentData.student_id}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Class:</span>
                    <span class="info-value">${studentData.class_name || 'N/A'}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Batch:</span>
                    <span class="info-value">${studentData.batch_name || 'N/A'}</span>
                </div>
            </div>
            
            <div class="info-block">
                <h3>Payment Information</h3>
                <div class="info-row">
                    <span class="info-label">Fee Category:</span>
                    <span class="info-value">${paymentData.fee_category || 'N/A'}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Payment Mode:</span>
                    <span class="info-value">${paymentData.payment_mode || 'Cash'}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Received By:</span>
                    <span class="info-value">${paymentData.received_by || 'Admin'}</span>
                </div>
            </div>
        </div>
        
        <div class="payment-summary">
            <h3>Amount Paid</h3>
            <div class="payment-amount">${formatCurrency(netCashPaid)}</div>
            ${advanceDeducted > 0 ? `<div style="margin-top: 6px; font-size: 12px; color: #444; text-align: center;">Includes advance adjustment of ${formatCurrency(advanceDeducted)}</div>` : ''}
        </div>
        
        ${distributionHTML}
        ${remainingHTML}
        
        <div class="signature-section">
            <div class="signature-block">
                <div class="signature-line">Student/Parent Signature</div>
            </div>
            <div class="signature-block">
                <div class="signature-line">Authorized Signature</div>
            </div>
        </div>
        
        <div class="footer">
            This is a computer-generated receipt. For any queries, please contact the administration.
        </div>
        
        <div class="button-container no-print">
            <button class="print-btn" onclick="window.print()">🖨️ Print Receipt</button>
            <button class="close-btn" onclick="window.close()">✖ Close</button>
        </div>
    </div>
    
    <script>
        // Auto-print after a short delay
        setTimeout(() => {
            window.print();
        }, 500);
    </script>
</body>
</html>
    `;
    
    receiptWindow.document.write(receiptHTML);
    receiptWindow.document.close();
    }, 300); // End setTimeout
}
