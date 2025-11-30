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
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 md:p-8 lg:p-10">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modalTitle">Add New Student</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-500">
                        <span class="sr-only">Close</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <form method="POST" id="studentForm" class="space-y-4" enctype="multipart/form-data">
                    <input type="hidden" name="student_id" id="studentId">

                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-1">Name</label>
                        <input type="text" name="name" id="studentName" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-teal-500">
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-1">Class</label>
                            <input type="text" name="class" id="studentClass" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-teal-500">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-1">Batch</label>
                            <select name="batch" id="studentBatch" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-teal-500">
                                <?php
                                $startYear = 2025;
                                for ($i = 0; $i <= 5; $i++) {
                                    $y1 = $startYear + $i;
                                    $y2 = $y1 + 1;
                                    $batchOption = "$y1-$y2";
                                    echo "<option value='$batchOption'>$batchOption</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-1">Roll Number</label>
                        <input type="text" name="roll_number" id="studentRoll" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-teal-500">
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-1">Phone</label>
                            <input type="text" name="phone" id="studentPhone" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-teal-500">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-1">Email</label>
                            <input type="email" name="email" id="studentEmail" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-teal-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-1">Address</label>
                        <input type="text" name="address" id="studentAddress" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-teal-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-1">Admission Amount</label>
                        <input type="number" step="0.01" name="admission_amount" id="studentAdmission" placeholder="0.00" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-teal-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-1">Monthly/Course Fee</label>
                        <input type="number" step="0.01" name="fee" id="studentFee" placeholder="0.00" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-teal-500">
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="flex items-center">
                            <input type="checkbox" name="is_active" id="studentIsActive" checked class="h-4 w-4 text-teal-600 focus:ring-teal-500 border-gray-300 rounded">
                            <label for="studentIsActive" class="ml-2 block text-sm text-gray-700 font-bold">Active Student</label>
                        </div>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-1">Remark</label>
                        <textarea name="remark" id="studentRemark" rows="3" placeholder="Any additional notes or remarks..." class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-teal-500"></textarea>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-1">Photo</label>
                        <input type="file" name="photo" id="studentPhoto" accept="image/*" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none p-2" onchange="previewPhoto(event)">
                        <p class="text-xs text-gray-500 mt-1">Accepted formats: JPG, PNG, GIF</p>
                        <div id="photoPreview" class="mt-2 hidden">
                            <img id="photoPreviewImg" src="" alt="Photo Preview" class="w-32 h-32 object-cover rounded border-2 border-gray-300">
                        </div>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-1">Supporting Documents (Multiple)</label>
                        <input type="file" name="documents[]" id="studentDocuments" multiple class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none p-2" onchange="previewDocuments(event)">
                        <p class="text-xs text-gray-500 mt-1">You can upload multiple documents</p>
                        <div id="existingDocumentsPreview" class="mt-2 hidden">
                            <p class="text-xs font-semibold text-gray-700 mb-1">Existing Documents:</p>
                            <ul id="existingDocumentsList" class="text-xs text-gray-600 list-disc list-inside"></ul>
                        </div>
                        <div id="newDocumentsPreview" class="mt-2 hidden">
                            <p class="text-xs font-semibold text-gray-700 mb-1">New Selected Files:</p>
                            <ul id="newDocumentsList" class="text-xs text-gray-600 list-disc list-inside"></ul>
                        </div>
                    </div>

                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                        <button type="submit" id="submitBtn" class="w-full inline-flex justify-center items-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-teal-600 text-base font-medium text-white hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 sm:col-start-2 sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                            <span id="submitBtnText">Save Student</span>
                            <svg id="submitBtnSpinner" class="hidden animate-spin -mr-1 ml-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </button>
                        <button type="button" onclick="closeModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:col-start-1 sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
