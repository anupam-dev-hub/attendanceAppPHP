<!-- org/modals/expense_history_modal.php -->
<!-- Expense History Modal -->
<div id="expenseHistoryModal" class="fixed z-50 inset-0 overflow-y-auto opacity-0 pointer-events-none transition-opacity duration-200" aria-labelledby="modal-title" role="dialog" aria-modal="true" style="display: none;">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeExpenseHistoryModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-orange-100 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-6 w-6 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Expense History - <span id="historyCategoryName"></span></h3>
                        
                        <div class="mt-4 grid grid-cols-2 gap-4">
                            <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                                <p class="text-xs text-gray-600 mb-1">Total Amount</p>
                                <p class="text-2xl font-bold text-red-600">₹<span id="historyTotalAmount">0.00</span></p>
                            </div>
                            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                                <p class="text-xs text-gray-600 mb-1">Total Records</p>
                                <p class="text-2xl font-bold text-blue-600"><span id="historyTotalRecords">0</span></p>
                            </div>
                        </div>

                        <div class="mt-6 overflow-auto max-h-96">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50 sticky top-0">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Notes</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                                    </tr>
                                </thead>
                                <tbody id="expenseHistoryTableBody" class="bg-white divide-y divide-gray-200">
                                    <!-- Expense rows will be inserted here -->
                                </tbody>
                            </table>
                            <div id="noExpensesMessage" class="hidden text-center py-8 text-gray-500">
                                No expense history found
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="closeExpenseHistoryModal()" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 sm:w-auto sm:text-sm">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function openExpenseHistoryModal(category) {
    // Fetch expense history for this category
    fetch('get_expense_history.php?category=' + encodeURIComponent(category))
        .then(response => response.json())
        .then(data => {
            document.getElementById('historyCategoryName').textContent = category;
            document.getElementById('historyTotalAmount').textContent = data.total_amount.toFixed(2);
            document.getElementById('historyTotalRecords').textContent = data.total_records;
            
            const tbody = document.getElementById('expenseHistoryTableBody');
            const noMessage = document.getElementById('noExpensesMessage');
            
            if (data.expenses.length === 0) {
                tbody.innerHTML = '';
                noMessage.classList.remove('hidden');
            } else {
                noMessage.classList.add('hidden');
                tbody.innerHTML = data.expenses.map(expense => `
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-900">${new Date(expense.expense_date).toLocaleDateString()}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 font-medium">${expense.title}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">${expense.category}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">${expense.notes || '-'}</td>
                        <td class="px-4 py-3 text-sm text-right font-semibold text-red-600">₹${parseFloat(expense.amount).toFixed(2)}</td>
                    </tr>
                `).join('');
            }
            
            const modal = document.getElementById('expenseHistoryModal');
            modal.style.display = 'block';
            modal.style.opacity = '1';
            modal.style.pointerEvents = 'auto';
        })
        .catch(error => {
            console.error('Error fetching expense history:', error);
            alert('Failed to load expense history');
        });
}

function closeExpenseHistoryModal() {
    const modal = document.getElementById('expenseHistoryModal');
    modal.style.opacity = '0';
    modal.style.pointerEvents = 'none';
    setTimeout(() => {
        modal.style.display = 'none';
    }, 200);
}
</script>
