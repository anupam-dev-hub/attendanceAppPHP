<!-- org/modals/conflict_modal.php -->
<?php if ($conflict_student): ?>
    <div class="fixed z-20 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Roll Number Conflict
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    The roll number <strong><?php echo htmlspecialchars($_POST['roll_number']); ?></strong> is already assigned to:
                                </p>
                                <ul class="list-disc list-inside text-sm text-gray-700 mt-2">
                                    <li><strong>Name:</strong> <?php echo htmlspecialchars($conflict_student['name']); ?></li>
                                    <li><strong>Class:</strong> <?php echo htmlspecialchars($conflict_student['class']); ?></li>
                                    <li><strong>Stream:</strong> <?php echo htmlspecialchars($conflict_student['stream']); ?></li>
                                    <li><strong>Batch:</strong> <?php echo htmlspecialchars($conflict_student['batch']); ?></li>
                                </ul>
                                <p class="text-sm text-gray-500 mt-2">
                                    Do you want to <?php echo isset($_POST['student_id']) ? 'update' : 'add'; ?> this student without a roll number instead?
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <form method="POST">
                        <?php foreach ($_POST as $key => $value): ?>
                            <?php if ($key !== 'roll_number'): ?>
                                <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>">
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <input type="hidden" name="roll_number" value=""> <!-- Empty roll number -->
                        <input type="hidden" name="force_add" value="1">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-teal-600 text-base font-medium text-white hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 sm:ml-3 sm:w-auto sm:text-sm">
                            <?php echo isset($_POST['student_id']) ? 'Update as No Assigned' : 'Add as No Assigned'; ?>
                        </button>
                    </form>
                    <button type="button" onclick="handleConflictCancel()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                    <script>
                    function handleConflictCancel() {
                        // Store the form data
                        const conflictData = <?php echo json_encode($_POST); ?>;
                        
                        // Hide conflict modal by reloading without POST
                        window.location.href = 'students.php?reopen_form=1&form_data=' + encodeURIComponent(JSON.stringify(conflictData));
                    }
                    </script>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
