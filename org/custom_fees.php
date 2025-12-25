<?php
// org/custom_fees.php
session_start();
require '../config.php';
require '../functions.php';

if (!isOrg()) {
    header('Location: index.php');
    exit;
}

$org_id = $_SESSION['user_id'];

// Check subscription
if (!isSubscribed($org_id)) {
    header('Location: subscribe.php');
    exit;
}

// Fetch distinct classes and batches for filters
$classes = [];
$batches = [];
$qc = $conn->query("SELECT DISTINCT class FROM students WHERE org_id = $org_id AND class IS NOT NULL AND class != '' ORDER BY class ASC");
while ($row = $qc->fetch_assoc()) { $classes[] = $row['class']; }
$qb = $conn->query("SELECT DISTINCT batch FROM students WHERE org_id = $org_id AND batch IS NOT NULL AND batch != '' ORDER BY batch DESC");
while ($row = $qb->fetch_assoc()) { $batches[] = $row['batch']; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custom Fees - Organization</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-50">
<?php include 'navbar.php'; ?>

<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Custom (Ad-hoc) Fees</h1>
        <p class="mt-2 text-gray-600">Create a one-time fee and assign it to selected students.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Fee Form -->
        <div class="lg:col-span-1 bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Fee Details</h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fee Title</label>
                    <input id="fee_title" type="text" placeholder="e.g., Sports Kit, Exam Fee" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-teal-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                    <input id="amount" type="number" step="0.01" min="0" placeholder="e.g., 500" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-teal-500" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Due Month (optional)</label>
                        <select id="due_month" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-teal-500">
                            <option value="">--</option>
                            <?php for ($m=1;$m<=12;$m++): ?>
                                <option value="<?php echo $m; ?>"><?php echo date('F', mktime(0,0,0,$m,1)); ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Due Year (optional)</label>
                        <input id="due_year" type="number" min="2000" max="2100" value="<?php echo date('Y'); ?>" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-teal-500" />
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description (optional)</label>
                    <textarea id="description" rows="3" placeholder="Add a note..." class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-teal-500"></textarea>
                </div>
                <div class="pt-2">
                    <button id="assignBtn" class="w-full bg-teal-600 hover:bg-teal-700 text-white font-semibold py-2 px-4 rounded-lg">Assign Fee to Selected</button>
                </div>
                <p class="text-xs text-gray-500">A credit entry (amount owed) will be created in the student's ledger. Payments recorded later will offset it.</p>
            </div>
        </div>

        <!-- Student Selector -->
        <div class="lg:col-span-2 bg-white rounded-lg shadow p-6">
            <div class="flex flex-col md:flex-row md:items-end md:space-x-4 space-y-3 md:space-y-0 mb-4">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                    <select id="filter_class" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-teal-500">
                        <option value="">All</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?php echo htmlspecialchars($c); ?>"><?php echo htmlspecialchars($c); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Batch</label>
                    <select id="filter_batch" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-teal-500">
                        <option value="">All</option>
                        <?php foreach ($batches as $b): ?>
                            <option value="<?php echo htmlspecialchars($b); ?>"><?php echo htmlspecialchars($b); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <button id="applyFilter" class="bg-gray-800 hover:bg-gray-900 text-white font-medium py-2 px-4 rounded-lg">Apply Filter</button>
                </div>
                <div class="ml-auto">
                    <label class="inline-flex items-center space-x-2 cursor-pointer">
                        <input id="toggleSelectAll" type="checkbox" class="h-4 w-4 border-gray-300 rounded" />
                        <span class="text-sm text-gray-700">Select All</span>
                    </label>
                </div>
            </div>
            <div id="studentList" class="border rounded-lg divide-y max-h-[520px] overflow-auto">
                <div class="p-4 text-gray-500">Use filters and click "Apply Filter" to load students. Or leave filters empty to load all.</div>
            </div>
        </div>
    </div>
</div>

<script>
const studentList = document.getElementById('studentList');
const toggleSelectAll = document.getElementById('toggleSelectAll');

function renderStudents(list) {
    if (!Array.isArray(list) || list.length === 0) {
        studentList.innerHTML = '<div class="p-4 text-gray-500">No students found for the selected filter.</div>';
        return;
    }
    const rows = list.map(s => `
        <label class="flex items-center justify-between px-4 py-3 hover:bg-gray-50">
            <div class="flex items-center space-x-3">
                <input type="checkbox" class="stuChk h-4 w-4" value="${s.id}">
                <div>
                    <div class="font-medium text-gray-900">${escapeHtml(s.name)}${s.roll_number ? ` (Roll: ${escapeHtml(s.roll_number)})` : ''}</div>
                    <div class="text-xs text-gray-500">Class: ${escapeHtml(s.class || '-')}, Batch: ${escapeHtml(s.batch || '-')}</div>
                </div>
            </div>
            <span class="text-xs text-gray-400">ID: ${s.id}</span>
        </label>
    `).join('');
    studentList.innerHTML = rows;
}

function escapeHtml(str){
    return (str||'').toString().replace(/[&<>"']/g, function(m){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[m]);});
}

async function loadStudents() {
    const cls = document.getElementById('filter_class').value.trim();
    const batch = document.getElementById('filter_batch').value.trim();
    try {
        let url;
        if (cls && batch) {
            url = `get_students_by_class_batch.php?class=${encodeURIComponent(cls)}&batch=${encodeURIComponent(batch)}`;
        } else {
            // Fallback: fetch all students when no filter applied
            url = 'api/get_payment_history.php?all_students=1'; // reuse endpoint to list students if supported
        }
        const res = await fetch(url);
        const data = await res.json();
        // Normalize list shape
        let students = [];
        if (data.success && Array.isArray(data.students)) {
            students = data.students;
        } else if (data.success && Array.isArray(data.history)) {
            // get_payment_history may return history; not ideal. Provide minimal all-students fetch if unsupported
            students = [];
        } else {
            // If no endpoint for all, perform a generic fetch via lightweight inline endpoint
            const res2 = await fetch('students.php');
            students = [];
        }
        renderStudents(students);
    } catch (e) {
        console.error(e);
        studentList.innerHTML = '<div class="p-4 text-red-600">Failed to load students.</div>';
    }
}

document.getElementById('applyFilter').addEventListener('click', async () => {
    const cls = document.getElementById('filter_class').value.trim();
    const batch = document.getElementById('filter_batch').value.trim();
    if (!cls && !batch) {
        // Load all students for org via minimal API we define below
        try {
            const res = await fetch('api/get_students_list.php');
            const data = await res.json();
            if (data.success) renderStudents(data.students); else throw new Error('Failed');
        } catch (e) {
            console.error(e);
            studentList.innerHTML = '<div class="p-4 text-red-600">Failed to load students.</div>';
        }
        return;
    }
    try {
        const res = await fetch(`get_students_by_class_batch.php?class=${encodeURIComponent(cls)}&batch=${encodeURIComponent(batch)}`);
        const data = await res.json();
        if (data.success) renderStudents(data.students); else throw new Error('Failed');
    } catch (e) {
        console.error(e);
        studentList.innerHTML = '<div class="p-4 text-red-600">Failed to load students.</div>';
    }
});

toggleSelectAll.addEventListener('change', () => {
    document.querySelectorAll('.stuChk').forEach(cb => cb.checked = toggleSelectAll.checked);
});

async function assignCustomFee() {
    const fee_title = document.getElementById('fee_title').value.trim();
    const amount = parseFloat(document.getElementById('amount').value || '0');
    const description = document.getElementById('description').value.trim();
    const due_monthVal = document.getElementById('due_month').value;
    const due_yearVal = document.getElementById('due_year').value;

    const student_ids = Array.from(document.querySelectorAll('.stuChk:checked')).map(x => parseInt(x.value, 10));

    if (!fee_title) return Swal.fire('Required', 'Please enter a fee title', 'warning');
    if (!(amount > 0)) return Swal.fire('Required', 'Please enter a valid amount', 'warning');
    if (student_ids.length === 0) return Swal.fire('Select Students', 'Choose at least one student', 'info');

    const payload = { fee_title, amount, description, student_ids, skip_existing: true };
    if (due_monthVal) payload.due_month = parseInt(due_monthVal, 10);
    if (due_yearVal) payload.due_year = parseInt(due_yearVal, 10);

    try {
        const res = await fetch('api/custom_fees.php?action=assign', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: `${data.created} fee entries created. ${data.skipped || 0} skipped.`,
                showConfirmButton: false,
                timer: 5000,
                timerProgressBar: true
            });
        } else {
            Swal.fire('Error', data.message || 'Failed to assign custom fee', 'error');
        }
    } catch (e) {
        console.error(e);
        Swal.fire('Error', e.message || 'Request failed', 'error');
    }
}

document.getElementById('assignBtn').addEventListener('click', assignCustomFee);

// Lightweight API to list all active students for the org
// Implemented inline via fetch to a simple endpoint created below.
</script>
</body>
</html>
