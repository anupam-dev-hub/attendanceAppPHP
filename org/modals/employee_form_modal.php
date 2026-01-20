<!-- Employee Form Modal -->
<div id="employeeModal" class="fixed inset-0 overflow-y-auto hidden z-[2001]" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-2 sm:px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle w-full max-w-xs sm:max-w-2xl md:max-w-3xl lg:max-w-4xl xl:max-w-5xl relative">
            <!-- Loading Overlay -->
            <div id="employeeModalLoadingOverlay" class="hidden absolute inset-0 bg-white bg-opacity-90 z-50 flex items-center justify-center rounded-lg">
                <div class="text-center">
                    <svg class="animate-spin h-12 w-12 text-teal-600 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="mt-4 text-gray-700 font-medium">Loading employee data...</p>
                </div>
            </div>
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 md:p-8 lg:p-10 student-form-wrapper">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modalTitle">Add New Employee</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-500">
                        <span class="sr-only">Close</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <form method="POST" id="employeeForm" class="space-y-6 student-form-grid" enctype="multipart/form-data">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="employee_id" id="employeeId">

                    <!-- Personal Info Card -->
                    <div class="student-form-card form-span-2">
                        <div class="student-form-section-title">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <span>Personal Information</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div class="form-field-group">
                                <label>Name</label>
                                <input type="text" name="name" id="employeeName" required>
                            </div>
                            <div class="form-field-group">
                                <label>Phone</label>
                                <input type="text" name="phone" id="employeePhone" required>
                            </div>
                            <div class="form-field-group">
                                <label>Email</label>
                                <input type="email" name="email" id="employeeEmail">
                            </div>
                            <div class="form-field-group">
                                <label>Address</label>
                                <input type="text" name="address" id="employeeAddress">
                            </div>
                        </div>
                    </div>

                    <!-- Job Details Card -->
                    <div class="student-form-card">
                        <div class="student-form-section-title">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <span>Job Details</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div class="form-field-group">
                                <label>Designation</label>
                                <input type="text" name="designation" id="employeeDesignation">
                            </div>
                            <div class="form-field-group">
                                <label>Department</label>
                                <input type="text" name="department" id="employeeDepartment">
                            </div>
                        </div>
                    </div>

                    <!-- Financial Card -->
                    <div class="student-form-card">
                        <div class="student-form-section-title">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Financial</span>
                        </div>
                        <div class="form-field-group">
                            <label>Salary</label>
                            <input type="number" step="0.01" name="salary" id="employeeSalary" placeholder="0.00">
                        </div>
                    </div>

                    <!-- Status Card -->
                    <div class="student-form-card">
                        <div class="student-form-section-title">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Status</span>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" name="is_active" id="employeeIsActive" checked class="form-checkbox-modern">
                            <label for="employeeIsActive" class="ml-2 text-xs font-semibold text-gray-700 tracking-wide">Active Employee</label>
                        </div>
                    </div>

                    <!-- Photo Card -->
                    <div class="student-form-card">
                        <div class="student-form-section-title">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <span>Photo</span>
                        </div>
                        <div class="form-field-group">
                            <label>Upload Photo</label>
                            <input type="file" name="photo" id="employeePhoto" accept="image/*" onchange="previewPhoto(event)">
                            <p class="form-hint">Accepted: JPG, PNG, GIF, WebP</p>
                            <div id="photoPreview" class="mt-3 hidden">
                                <img id="photoPreviewImg" src="" alt="Photo Preview" class="photo-preview-frame">
                            </div>
                        </div>
                    </div>

                    <!-- Documents Card -->
                    <div class="student-form-card form-span-2">
                        <div class="student-form-section-title">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span>Supporting Documents</span>
                        </div>
                        <div class="form-field-group">
                            <label>Upload Documents</label>
                            <input type="file" name="documents[]" id="employeeDocuments" multiple onchange="previewDocuments(event)">
                            <p class="form-hint">Multiple files allowed</p>
                            <div id="existingDocumentsPreview" class="mt-3 hidden">
                                <p class="form-subtitle">Existing:</p>
                                <ul id="existingDocumentsList" class="document-list"></ul>
                            </div>
                            <div id="newDocumentsPreview" class="mt-3 hidden">
                                <p class="form-subtitle">New Selected:</p>
                                <ul id="newDocumentsList" class="document-list"></ul>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="form-actions form-span-2">
                        <button type="submit" id="submitBtn" class="primary-action-btn">
                            <span id="submitBtnText">Save Employee</span>
                            <svg id="submitBtnSpinner" class="hidden animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                        </button>
                        <button type="button" onclick="closeModal()" class="secondary-action-btn">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
