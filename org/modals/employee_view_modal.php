<!-- Employee View Modal -->
<div id="viewModal" class="fixed z-40 inset-0 overflow-y-auto opacity-0 pointer-events-none transition-opacity duration-200" aria-labelledby="view-modal-title" role="dialog" aria-modal="true" style="display: none;">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeViewModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full relative">
            <!-- Loading Overlay -->
            <div id="viewModalLoadingOverlay" class="hidden absolute inset-0 bg-white bg-opacity-90 z-50 flex items-center justify-center rounded-lg">
                <div class="text-center">
                    <svg class="animate-spin h-12 w-12 text-teal-600 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="mt-4 text-gray-700 font-medium">Loading employee information...</p>
                </div>
            </div>
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="flex justify-between items-center mb-4 border-b pb-3">
                    <h3 class="text-xl leading-6 font-bold text-gray-900" id="viewModalTitle">Employee Information</h3>
                    <button onclick="closeViewModal()" class="text-gray-400 hover:text-gray-500">
                        <span class="sr-only">Close</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Overview Content -->
                <div class="p-4 rounded-lg bg-gray-50">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                        <!-- Photo Card -->
                        <div class="overview-card">
                            <div class="flex flex-col items-center">
                                <div id="viewPhotoContainer" class="mb-4">
                                    <img id="viewEmployeePhoto" src="" alt="Employee Photo" class="w-40 h-40 rounded-lg object-cover border-4 border-teal-200 shadow-md">
                                </div>
                                <div id="viewNoPhoto" class="hidden w-40 h-40 rounded-lg bg-gray-100 flex items-center justify-center border-4 border-gray-200 mb-4">
                                    <span class="text-gray-400 text-sm">No Photo</span>
                                </div>
                                <div id="viewPhotoActions" class="flex gap-2 w-full">
                                    <button onclick="viewFullPhoto()" id="viewPhotoBtn" class="flex-1 text-xs text-teal-600 hover:text-teal-800 font-medium flex items-center justify-center space-x-1 py-2 px-3 border border-teal-200 rounded-lg hover:bg-teal-50 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        <span>Preview</span>
                                    </button>
                                    <button onclick="downloadPhoto()" id="viewPhotoDownloadBtn" class="flex-1 text-xs text-blue-600 hover:text-blue-800 font-medium flex items-center justify-center space-x-1 py-2 px-3 border border-blue-200 rounded-lg hover:bg-blue-50 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                        </svg>
                                        <span>Download</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Personal Information Card -->
                        <div class="overview-card lg:col-span-2">
                            <div class="overview-card-header">
                                <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <h4>Personal Information</h4>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="overview-info-item">
                                    <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Name</p>
                                    <p class="text-sm font-medium text-gray-900" id="viewName">-</p>
                                </div>
                                <div class="overview-info-item">
                                    <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Phone</p>
                                    <p class="text-sm font-medium text-gray-900" id="viewPhone">-</p>
                                </div>
                                <div class="overview-info-item">
                                    <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Email</p>
                                    <p class="text-sm font-medium text-gray-900" id="viewEmail">-</p>
                                </div>
                                <div class="overview-info-item">
                                    <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Address</p>
                                    <p class="text-sm font-medium text-gray-900" id="viewAddress">-</p>
                                </div>
                            </div>
                        </div>

                        <!-- Job Details Card -->
                        <div class="overview-card">
                            <div class="overview-card-header">
                                <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                <h4>Job Details</h4>
                            </div>
                            <div class="space-y-3">
                                <div class="overview-info-item">
                                    <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Designation</p>
                                    <p class="text-sm font-medium text-gray-900" id="viewDesignation">-</p>
                                </div>
                                <div class="overview-info-item">
                                    <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Department</p>
                                    <p class="text-sm font-medium text-gray-900" id="viewDepartment">-</p>
                                </div>
                                <div class="overview-info-item">
                                    <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Status</p>
                                    <p class="text-sm font-medium text-gray-900" id="viewStatus">-</p>
                                </div>
                            </div>
                        </div>

                        <!-- Financial Information Card -->
                        <div class="overview-card lg:col-span-2">
                            <div class="overview-card-header">
                                <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <h4>Financial Information</h4>
                            </div>
                            <div class="overview-info-item">
                                <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Salary</p>
                                <p class="text-sm font-medium text-teal-600 font-semibold" id="viewSalary">-</p>
                            </div>
                        </div>

                        <!-- Supporting Documents Card -->
                        <div class="overview-card lg:col-span-3">
                            <div class="overview-card-header">
                                <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <h4>Supporting Documents</h4>
                            </div>
                            <div id="viewDocumentsList" class="flex flex-col gap-2">
                                <!-- Documents will be populated here -->
                            </div>
                            <div id="viewNoDocuments" class="hidden text-sm text-gray-500 italic text-center py-4">
                                No supporting documents available
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-gray-200 flex justify-between items-center bg-gray-50">
                    <div class="flex gap-2">
                        <button onclick="const empId = document.getElementById('viewName').getAttribute('data-employee-id'); const empName = document.getElementById('viewName').innerText; openEmployeePaymentModal(empId, empName);" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Record Payment
                        </button>
                        <button onclick="viewEmployeePaymentHistory()" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            View Payment History
                        </button>
                    </div>
                    <button onclick="closeViewModal()" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                        Close
                    </button>
                </div>
