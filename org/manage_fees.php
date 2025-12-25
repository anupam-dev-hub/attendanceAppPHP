<?php
// org/manage_fees.php
session_start();
require '../config.php';
require '../functions.php';

// Check if org is logged in
if (!isOrg()) {
    header('Location: index.php');
    exit;
}

$org_id = $_SESSION['user_id'];

// Check if organization is subscribed
if (!isSubscribed($org_id)) {
    header('Location: subscribe.php');
    exit;
}

$message = '';
$error = '';

// Fetch all fees for this organization
$feesQuery = "SELECT id, fee_name, is_default FROM org_fees WHERE org_id = ? ORDER BY is_default DESC, fee_name ASC";
$stmt = $conn->prepare($feesQuery);
$stmt->bind_param('i', $org_id);
$stmt->execute();
$feesResult = $stmt->get_result();
$fees = [];
while ($row = $feesResult->fetch_assoc()) {
    $fees[] = $row;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Fees - Organization</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-50">
    <?php include 'navbar.php'; ?>
    
    <div class="max-w-6xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Fee Management</h1>
            <p class="mt-2 text-gray-600">Configure fee types for your organization</p>
        </div>

        <!-- Info Box -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <p class="text-blue-800"><strong>Note:</strong> Define all fee types here. These fees will be available when creating/editing student records.</p>
        </div>

        <!-- Add New Fee Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Add New Fee</h2>
            <form id="addFeeForm" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fee Name</label>
                    <input type="text" id="feeName" name="fee_name" placeholder="e.g., Library Fee, Tuition Fee, Lab Fee" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-teal-600 hover:bg-teal-700 text-white font-semibold py-2 px-4 rounded-lg transition">
                        Add Fee
                    </button>
                </div>
            </form>
        </div>

        <!-- Fees List -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Configured Fees</h2>
            </div>
            
            <?php if (count($fees) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Fee Name</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($fees as $fee): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($fee['fee_name']); ?></td>
                                    <td class="px-6 py-4 text-sm">
                                        <?php if ($fee['is_default']): ?>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-teal-100 text-teal-800">Default</span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">Optional</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm space-x-2">
                                        <?php if (!$fee['is_default']): ?>
                                            <button onclick="editFee(<?php echo $fee['id']; ?>, '<?php echo htmlspecialchars($fee['fee_name'], ENT_QUOTES); ?>')" class="text-blue-600 hover:text-blue-900 font-medium mr-2">Edit</button>
                                            <button onclick="deleteFee(<?php echo $fee['id']; ?>)" class="text-red-600 hover:text-red-900 font-medium">Delete</button>
                                        <?php else: ?>
                                            <button onclick="editFee(<?php echo $fee['id']; ?>, '<?php echo htmlspecialchars($fee['fee_name'], ENT_QUOTES); ?>')" class="text-blue-600 hover:text-blue-900 font-medium mr-2">Edit</button>
                                            <span class="text-gray-400 text-sm">Cannot delete default fee</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="px-6 py-12 text-center">
                    <p class="text-gray-500">No fees configured yet. Create your first fee type above.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit Fee Modal (global, single instance) -->
    <div id="editFeeModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-xl font-semibold text-gray-900">Edit Fee</h3>
            </div>
            <form id="editFeeForm" class="px-6 py-4">
                <input type="hidden" id="editFeeId">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fee Name</label>
                    <input type="text" id="editFeeName" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeEditModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700">Update Fee</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Add new fee
        document.getElementById('addFeeForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const feeName = document.getElementById('feeName').value.trim();
            
            if (!feeName) {
                Swal.fire('Error', 'Fee name is required', 'error');
                return;
            }
            
            try {
                const response = await fetch('api/manage_fees.php?action=add_fee', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        fee_name: feeName
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Fee added successfully!',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    console.log('API Error Response:', data);
                    Swal.fire('Error', data.message || 'Failed to add fee', 'error');
                }
            } catch (error) {
                console.error('Fetch Error:', error);
                Swal.fire('Error', error.message || 'An error occurred while adding fee', 'error');
            }
        });
        
        // Edit fee (global)
        // Decode HTML entities safely
        function decodeHtmlEntities(str) {
            const txt = document.createElement('textarea');
            txt.innerHTML = str;
            return txt.value;
        }

        function editFee(feeId, feeName) {
            document.getElementById('editFeeId').value = feeId;
            document.getElementById('editFeeName').value = decodeHtmlEntities(feeName);
            const modal = document.getElementById('editFeeModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => document.getElementById('editFeeName').focus(), 100);
        }

        // Close edit modal (global)
        function closeEditModal() {
            const modal = document.getElementById('editFeeModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.getElementById('editFeeForm').reset();
        }

        // Update fee submit handler (global)
        document.getElementById('editFeeForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const feeId = document.getElementById('editFeeId').value;
            const feeName = document.getElementById('editFeeName').value.trim();

            if (!feeName) {
                Swal.fire('Error', 'Fee name is required', 'error');
                return;
            }

            try {
                const response = await fetch('api/manage_fees.php?action=update_fee', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        fee_id: feeId,
                        fee_name: feeName
                    })
                });

                const data = await response.json();

                if (data.success) {
                    closeEditModal();
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Fee updated successfully!',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message || 'Failed to update fee', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error', 'An error occurred while updating fee', 'error');
            }
        });

        // Delete fee
        async function deleteFee(feeId) {
            const result = await Swal.fire({
                title: 'Confirm Delete',
                text: 'Are you sure you want to delete this fee type?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, delete'
            });
            
            if (!result.isConfirmed) return;
            
            try {
                const response = await fetch('api/manage_fees.php?action=delete_fee', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        fee_id: feeId
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    Swal.fire('Deleted!', 'Fee type deleted successfully', 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message || 'Failed to delete fee', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error', 'An error occurred while deleting fee', 'error');
            }
        }
    </script>
</body>
</html>
