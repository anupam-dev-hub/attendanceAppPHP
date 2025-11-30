<!-- org/modals/payment_modal.php -->
<div id="paymentModal" class="fixed z-30 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closePaymentModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-teal-100 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-6 w-6 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="paymentModalTitle">Record Payment</h3>
                        <div class="mt-2">
                            <form id="paymentForm" class="space-y-4">
                                <input type="hidden" name="student_id" id="paymentStudentId">
                                <div>
                                    <label class="block text-sm  font-medium text-gray-700">Student Name</label>
                                    <input type="text" id="paymentStudentName" disabled class="px-4 py-2 mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Amount</label>
                                    <div class="mt-1 relative rounded-md shadow-sm">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 sm:text-sm">₹</span>
                                        </div>
                                        <input type="number" name="amount" id="paymentAmount" required min="0.01" step="0.01" class="px-4 py-2 focus:ring-teal-500 focus:border-teal-500 block w-full pl-7 pr-12 sm:text-sm border-gray-300 rounded-md" placeholder="0.00">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Category</label>
                                    <select name="category" id="paymentCategory" required class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-teal-500 focus:border-teal-500 sm:text-sm rounded-md">
                                        <option value="Admission Fee">Admission Fee</option>
                                        <option value="Monthly Fee">Monthly Fee</option>
                                        <option value="Tuition Fee">Tuition Fee</option>
                                        <option value="Exam Fee">Exam Fee</option>
                                        <option value="Transport Fee">Transport Fee</option>
                                        <option value="Library Fee">Library Fee</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Description</label>
                                    <textarea name="description" id="paymentDescription" rows="3" class="px-4 py-2 shadow-sm focus:ring-teal-500 focus:border-teal-500 mt-1 block w-full sm:text-sm border border-gray-300 rounded-md" placeholder="Optional description..."></textarea>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="submitPayment()" id="submitPaymentBtn" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-teal-600 text-base font-medium text-white hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 sm:ml-3 sm:w-auto sm:text-sm">
                    Record Payment
                </button>
                <button type="button" onclick="closePaymentModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>