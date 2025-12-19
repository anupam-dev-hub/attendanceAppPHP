<!-- org/modals/student_form_modal.php -->
<div id="studentModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-2 sm:px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle w-full max-w-xs sm:max-w-2xl md:max-w-3xl lg:max-w-4xl xl:max-w-5xl relative">
            <!-- Loading Overlay -->
            <div id="modalLoadingOverlay" class="hidden absolute inset-0 bg-white bg-opacity-90 z-50 flex items-center justify-center rounded-lg">
                <div class="text-center">
                    <svg class="animate-spin h-12 w-12 text-teal-600 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="mt-4 text-gray-700 font-medium">Loading student data...</p>
                </div>
            </div>
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 md:p-8 lg:p-10 student-form-wrapper">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modalTitle">Add New Student</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-500">
                        <span class="sr-only">Close</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <form method="POST" id="studentForm" class="space-y-6 student-form-grid" enctype="multipart/form-data">
                    <input type="hidden" name="student_id" id="studentId">

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
                                <input type="text" name="name" id="studentName" required>
                            </div>
                            <div class="form-field-group">
                                <label>Roll Number</label>
                                <input type="text" name="roll_number" id="studentRoll">
                            </div>
                            <div class="form-field-group">
                                <label>Phone</label>
                                <input type="text" name="phone" id="studentPhone" required>
                            </div>
                            <div class="form-field-group">
                                <label>Email</label>
                                <input type="email" name="email" id="studentEmail">
                            </div>
                            <div class="form-field-group sm:col-span-2">
                                <label>Address</label>
                                <input type="text" name="address" id="studentAddress">
                            </div>
                            <div class="form-field-group">
                                <label>Sex</label>
                                <select name="sex" id="studentSex" onchange="toggleSexOther()">
                                    <option value="">Select Sex</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other (Specify)</option>
                                </select>
                            </div>
                            <div class="form-field-group" id="sexOtherGroup" style="display: none;">
                                <label>Specify Sex</label>
                                <input type="text" name="sex_other" id="studentSexOther" placeholder="Please specify">
                            </div>
                            <div class="form-field-group">
                                <label>Date of Birth</label>
                                <input type="date" name="date_of_birth" id="studentDOB">
                            </div>
                            <div class="form-field-group">
                                <label>Place of Birth</label>
                                <input type="text" name="place_of_birth" id="studentPlaceOfBirth">
                            </div>
                            <div class="form-field-group">
                                <label>Nationality</label>
                                <input type="text" name="nationality" id="studentNationality" value="Indian">
                            </div>
                            <div class="form-field-group">
                                <label>Mother Tongue</label>
                                <input type="text" name="mother_tongue" id="studentMotherTongue">
                            </div>
                            <div class="form-field-group">
                                <label>Religion</label>
                                <select name="religion" id="studentReligion" onchange="toggleReligionOther()">
                                    <option value="">Select Religion</option>
                                    <option value="Hindu">Hindu</option>
                                    <option value="Muslim">Muslim</option>
                                    <option value="Christian">Christian</option>
                                    <option value="Other">Other (Specify)</option>
                                </select>
                            </div>
                            <div class="form-field-group" id="religionOtherGroup" style="display: none;">
                                <label>Specify Religion</label>
                                <input type="text" name="religion_other" id="studentReligionOther" placeholder="Please specify">
                            </div>
                            <div class="form-field-group">
                                <label>Community</label>
                                <select name="community" id="studentCommunity" onchange="toggleCommunityOther()">
                                    <option value="">Select Community</option>
                                    <option value="ST">ST</option>
                                    <option value="SC">SC</option>
                                    <option value="SC(A)">SC(A)</option>
                                    <option value="BC">BC</option>
                                    <option value="General">General</option>
                                    <option value="Other">Other (Specify)</option>
                                </select>
                            </div>
                            <div class="form-field-group" id="communityOtherGroup" style="display: none;">
                                <label>Specify Community</label>
                                <input type="text" name="community_other" id="studentCommunityOther" placeholder="Please specify">
                            </div>
                            <div class="form-field-group">
                                <label>Native District</label>
                                <input type="text" name="native_district" id="studentNativeDistrict">
                            </div>
                            <div class="form-field-group">
                                <label>Pin Code</label>
                                <input type="text" name="pin_code" id="studentPinCode" maxlength="10">
                            </div>
                        </div>
                    </div>

                    <!-- Parent/Guardian Info Card -->
                    <div class="student-form-card form-span-2">
                        <div class="student-form-section-title">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span>Parent/Guardian Information</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div class="form-field-group">
                                <label>Name of Parent/Guardian</label>
                                <input type="text" name="parent_guardian_name" id="studentParentName">
                            </div>
                            <div class="form-field-group">
                                <label>Parent Contact</label>
                                <input type="text" name="parent_contact" id="studentParentContact">
                            </div>
                        </div>
                    </div>

                    <!-- Previous Examination Info Card -->
                    <div class="student-form-card form-span-2">
                        <div class="student-form-section-title">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Previous Examination Details</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div class="form-field-group sm:col-span-2">
                                <label>Examination Name</label>
                                <input type="text" name="exam_name" id="studentExamName" placeholder="e.g., Class 10 Board Exam">
                            </div>
                            <div class="form-field-group">
                                <label>Total Marks (Out of)</label>
                                <input type="number" name="exam_total_marks" id="studentExamTotalMarks" placeholder="e.g., 500">
                            </div>
                            <div class="form-field-group">
                                <label>Marks Obtained</label>
                                <input type="number" step="0.01" name="exam_marks_obtained" id="studentExamMarksObtained" placeholder="e.g., 425">
                            </div>
                            <div class="form-field-group">
                                <label>Percentage (%)</label>
                                <input type="number" step="0.01" name="exam_percentage" id="studentExamPercentage" placeholder="e.g., 85.00">
                            </div>
                            <div class="form-field-group">
                                <label>Grade</label>
                                <input type="text" name="exam_grade" id="studentExamGrade" placeholder="e.g., A+">
                            </div>
                        </div>
                    </div>

                    <!-- Academic Card -->
                    <div class="student-form-card">
                        <div class="student-form-section-title">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            <span>Academic Details</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div class="form-field-group">
                                <label>Class</label>
                                <input type="text" name="class" id="studentClass" required>
                            </div>
                            <div class="form-field-group">
                                <label>Batch</label>
                                <select name="batch" id="studentBatch" required>
                                    <?php
                                    $currentYear = date('Y');
                                    $nextYear = $currentYear + 1;
                                    echo "<option value='$currentYear-$nextYear'>$currentYear-$nextYear</option>";
                                    $nextBatchYear = $nextYear;
                                    $nextBatchYearEnd = $nextBatchYear + 1;
                                    echo "<option value='$nextBatchYear-$nextBatchYearEnd'>$nextBatchYear-$nextBatchYearEnd</option>";
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Card -->
                    <div class="student-form-card form-span-2">
                        <div class="student-form-section-title">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Financial</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div class="form-field-group">
                                <label>Admission Amount</label>
                                <input type="number" step="0.01" name="admission_amount" id="studentAdmission" placeholder="0.00">
                            </div>
                        </div>
                        
                        <!-- Dynamic Fees Section -->
                        <div id="feesContainer" class="mt-5">
                            <p class="text-sm font-semibold text-gray-700 mb-3">Fees</p>
                            <div id="feeInputsWrapper" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Fee inputs will be populated here dynamically -->
                            </div>
                        </div>
                    </div>

                    <!-- Status / Remarks Card -->
                    <div class="student-form-card">
                        <div class="student-form-section-title">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                            </svg>
                            <span>Status & Remarks</span>
                        </div>
                        <div class="flex items-center mb-4">
                            <input type="checkbox" name="is_active" id="studentIsActive" checked class="form-checkbox-modern">
                            <label for="studentIsActive" class="ml-2 text-xs font-semibold text-gray-700 tracking-wide">Active Student</label>
                        </div>
                        <div class="form-field-group">
                            <label>Remark</label>
                            <textarea name="remark" id="studentRemark" rows="3" placeholder="Any additional notes..."></textarea>
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
                            <input type="file" name="photo" id="studentPhoto" accept="image/*" onchange="previewPhoto(event)">
                            <p class="form-hint">Accepted: JPG, PNG, GIF</p>
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
                            <input type="file" name="documents[]" id="studentDocuments" multiple onchange="previewDocuments(event)">
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
                            <span id="submitBtnText">Save Student</span>
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