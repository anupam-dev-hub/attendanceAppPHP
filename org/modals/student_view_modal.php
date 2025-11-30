<!-- org/modals/student_view_modal.php -->
<div id="viewModal" class="fixed z-20 inset-0 overflow-y-auto hidden" aria-labelledby="view-modal-title" role="dialog" aria-modal="true">
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
                    <p class="mt-4 text-gray-700 font-medium">Loading student information...</p>
                </div>
            </div>
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="flex justify-between items-center mb-4 border-b pb-3">
                    <h3 class="text-xl leading-6 font-bold text-gray-900" id="viewModalTitle">Student Information</h3>
                    <button onclick="closeViewModal()" class="text-gray-400 hover:text-gray-500">
                        <span class="sr-only">Close</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Tabs -->
                <div class="mb-4 border-b border-gray-200">
                    <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" id="viewTabs" role="tablist">
                        <li class="mr-2" role="presentation">
                            <button class="inline-block p-4 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300 active-tab border-teal-600 text-teal-600" id="overview-tab" data-tabs-target="#overview" type="button" role="tab" aria-controls="overview" aria-selected="true" onclick="switchTab('overview')">Overview</button>
                        </li>

                        <li class="mr-2" role="presentation">
                            <button class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300 text-gray-500" id="attendance-tab" data-tabs-target="#attendance" type="button" role="tab" aria-controls="attendance" aria-selected="false" onclick="switchTab('attendance')">Attendance</button>
                        </li>
                        <li class="mr-2" role="presentation">
                            <button class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300 text-gray-500" id="payments-tab" data-tabs-target="#payments" type="button" role="tab" aria-controls="payments" aria-selected="false" onclick="switchTab('payments')">Payments</button>
                        </li>
                    </ul>
                </div>

                <!-- Tab Content -->
                <div id="viewTabContent">
                    <!-- Overview Tab -->
                    <div class="hidden p-4 rounded-lg bg-gray-50 block" id="overview" role="tabpanel" aria-labelledby="overview-tab">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Photo Section -->
                            <div class="md:col-span-1 flex flex-col items-center">
                                <div id="viewPhotoContainer" class="mb-4">
                                    <img id="viewStudentPhoto" src="" alt="Student Photo" class="w-40 h-40 rounded-lg object-cover border-4 border-gray-200 shadow-md">
                                </div>
                                <div id="viewNoPhoto" class="hidden w-40 h-40 rounded-lg bg-gray-100 flex items-center justify-center border-4 border-gray-200 mb-4">
                                    <span class="text-gray-400 text-sm">No Photo</span>
                                </div>
                                <div id="viewPhotoActions" class="flex flex-col space-y-2">
                                    <button onclick="viewFullPhoto()" id="viewPhotoBtn" class="text-sm text-teal-600 hover:text-teal-800 font-medium flex items-center justify-center space-x-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        <span>Preview</span>
                                    </button>
                                    <button onclick="downloadPhoto()" id="viewPhotoDownloadBtn" class="text-sm text-blue-600 hover:text-blue-800 font-medium flex items-center justify-center space-x-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                        </svg>
                                        <span>Download</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Details Section -->
                            <div class="md:col-span-2 space-y-4">
                                <!-- Personal Information -->
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-800 mb-3 border-b pb-2">Personal Information</h4>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <p class="text-xs text-gray-500 uppercase">Name</p>
                                            <p class="text-sm font-medium text-gray-900" id="viewName">-</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500 uppercase">Roll Number</p>
                                            <p class="text-sm font-medium text-gray-900" id="viewRoll">-</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500 uppercase">Phone</p>
                                            <p class="text-sm font-medium text-gray-900" id="viewPhone">-</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500 uppercase">Email</p>
                                            <p class="text-sm font-medium text-gray-900" id="viewEmail">-</p>
                                        </div>
                                        <div class="col-span-2">
                                            <p class="text-xs text-gray-500 uppercase">Address</p>
                                            <p class="text-sm font-medium text-gray-900" id="viewAddress">-</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Academic Information -->
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-800 mb-3 border-b pb-2">Academic Information</h4>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <p class="text-xs text-gray-500 uppercase">Class</p>
                                            <p class="text-sm font-medium text-gray-900" id="viewClass">-</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500 uppercase">Batch</p>
                                            <p class="text-sm font-medium text-gray-900" id="viewBatch">-</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500 uppercase">Status</p>
                                            <p class="text-sm font-medium text-gray-900" id="viewStatus">-</p>
                                        </div>
                                        <div class="col-span-2">
                                            <p class="text-xs text-gray-500 uppercase">Remark</p>
                                            <p class="text-sm font-medium text-gray-900" id="viewRemark">-</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Financial Information -->
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-800 mb-3 border-b pb-2">Financial Information</h4>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <p class="text-xs text-gray-500 uppercase">Admission Amount</p>
                                            <p class="text-sm font-medium text-gray-900" id="viewAdmission">-</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500 uppercase">Monthly/Course Fee</p>
                                            <p class="text-sm font-medium text-gray-900" id="viewFee">-</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500 uppercase">Student ID</p>
                                            <p class="text-sm font-medium text-gray-900" id="viewStudentId">-</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Supporting Documents -->
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-800 mb-3 border-b pb-2">Supporting Documents</h4>
                                    <div id="viewDocumentsList" class="space-y-2">
                                        <!-- Documents will be populated here -->
                                    </div>
                                    <div id="viewNoDocuments" class="hidden text-sm text-gray-500 italic">
                                        No supporting documents available
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>



                    <!-- Attendance Tab -->
                    <div class="hidden p-4 rounded-lg bg-gray-50" id="attendance" role="tabpanel" aria-labelledby="attendance-tab">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <h4 class="text-lg font-semibold text-gray-800 mb-4">Attendance Overview</h4>
                                <div class="h-64">
                                    <canvas id="attendanceChart"></canvas>
                                </div>
                            </div>
                            <div>
                                <h4 class="text-lg font-semibold text-gray-800 mb-4">Recent Attendance</h4>
                                <div class="overflow-y-auto h-64">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-100 sticky top-0">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">In Time</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Out Time</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200" id="attendanceHistoryBody">
                                            <!-- Populated by JS -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payments Tab (Revamped) -->
                    <div class="hidden p-4 rounded-lg payment-gradient" id="payments" role="tabpanel" aria-labelledby="payments-tab">
                        <div class="space-y-4" aria-live="polite">
                            <!-- Header / Controls -->
                            <div class="payments-flex-wrap flex items-center justify-between gap-4">
                                <div class="payments-heading" aria-label="Payment history section">
                                    <div class="payments-heading-icon payment-animated-icon" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 12h19.5m-19.5 3.75h19.5" />
                                        </svg>
                                    </div>
                                    <span class="tracking-wide">Payment History</span>
                                    <span class="secure-badge" aria-label="Secure payments indicator">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h11.25a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                        </svg>
                                        SECURE
                                    </span>
                                </div>
                                <div class="payment-filter-bar" role="group" aria-label="Filters and actions">
                                    <label for="paymentTypeFilter">Type</label>
                                    <select id="paymentTypeFilter" class="filter-select payment-focus-ring" aria-label="Filter payments by transaction type">
                                        <option value="all">All</option>
                                        <option value="debit">Debit (+)</option>
                                        <option value="credit">Credit (-)</option>
                                    </select>
                                    <button type="button" class="payment-record-btn-secondary payment-focus-ring" onclick="if(currentViewStudent){openPaymentModal(currentViewStudent);}" aria-label="Record a new payment for this student">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" />
                                        </svg>
                                        <span>Record Payment</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Summary Cards -->
                            <div class="payment-summary-grid" aria-label="Payment summary statistics">
                                <div class="payment-summary-card" role="group" aria-label="Total debit">
                                    <span class="payment-summary-label">Total Debit</span>
                                    <span id="totalDebit" class="payment-summary-value" aria-live="polite">₹0.00</span>
                                </div>
                                <div class="payment-summary-card" role="group" aria-label="Total credit">
                                    <span class="payment-summary-label">Total Credit</span>
                                    <span id="totalCredit" class="payment-summary-value" aria-live="polite">₹0.00</span>
                                </div>
                                <div class="payment-summary-card" role="group" aria-label="Current balance">
                                    <span class="payment-summary-label">Balance</span>
                                    <span id="netBalance" class="payment-summary-value" aria-live="polite">₹0.00</span>
                                </div>
                            </div>

                            <!-- Table Wrapper -->
                            <div class="payment-table-wrapper" aria-label="Detailed payment history table">
                                <table id="paymentHistoryTable" class="payments-table display" aria-describedby="noPaymentsMsg">
                                    <thead>
                                        <tr>
                                            <th scope="col">Date</th>
                                            <th scope="col">Amount</th>
                                            <th scope="col">Type</th>
                                            <th scope="col">Category</th>
                                            <th scope="col">Description</th>
                                        </tr>
                                    </thead>
                                    <tbody id="paymentHistoryBody">
                                        <!-- Populated by JS -->
                                    </tbody>
                                </table>
                            </div>
                            <div id="noPaymentsMsg" class="hidden text-xs text-gray-500 italic" aria-live="polite">No payments recorded</div>
                            <div class="security-hint" aria-hidden="false">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 6h.01M16.5 9V6.75a4.5 4.5 0 10-9 0V9m-.75 12h11.25a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75A2.25 2.25 0 006.75 21z" />
                                </svg>
                                Payments are securely processed and encrypted.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <!-- <button onclick="editFromView()" class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-6 rounded transition">
                        Edit Student
                    </button> -->
                    <!-- <button onclick="closeViewModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-6 rounded transition">
                        Close
                    </button> -->
                </div>
            </div>
        </div>
    </div>
</div>