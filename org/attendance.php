<?php
// org/attendance.php
session_start();
require '../config.php';
require '../functions.php';

if (!isOrg()) {
    redirect('../index.php');
}

$org_id = $_SESSION['user_id'];

if (!isSubscribed($org_id)) {
    redirect('dashboard.php');
}

// Calendar month/year navigation
$current_month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$current_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Validate month/year
if ($current_month < 1 || $current_month > 12) $current_month = date('n');
if ($current_year < 2000 || $current_year > 2100) $current_year = date('Y');

// Get first and last day of the month
$first_day_of_month = "$current_year-" . str_pad($current_month, 2, '0', STR_PAD_LEFT) . "-01";
$last_day_of_month = date('Y-m-d', strtotime('last day of ' . $first_day_of_month));

// Defaults: last 7 days (for stats)
$filter_from = isset($_GET['from']) ? $_GET['from'] : date('Y-m-d', strtotime('-6 days'));
$filter_to = isset($_GET['to']) ? $_GET['to'] : date('Y-m-d');
$filter_type = isset($_GET['type']) ? $_GET['type'] : 'all'; // all | students | employees
$filter_search = isset($_GET['search']) ? trim($_GET['search']) : '';

$include_students = ($filter_type === 'all' || $filter_type === 'students');
$include_employees = ($filter_type === 'all' || $filter_type === 'employees');

// Active counts
$total_students = 0;
$total_employees = 0;

if ($include_students) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM students WHERE org_id = ? AND is_active = 1");
    $stmt->bind_param('i', $org_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $total_students = intval($res['total'] ?? 0);
    $stmt->close();
}

if ($include_employees) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM employees WHERE org_id = ? AND is_active = 1");
    $stmt->bind_param('i', $org_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $total_employees = intval($res['total'] ?? 0);
    $stmt->close();
}

// Calendar data for the selected month - all attendance records
$calendar_data = [];

// Student attendance by day for calendar
if ($include_students) {
    $stmt = $conn->prepare("SELECT a.date, SUM(CASE WHEN a.in_time IS NULL AND a.out_time IS NULL THEN 0 ELSE 1 END) as present_count
        FROM attendance a
        JOIN students s ON a.student_id = s.id
        WHERE s.org_id = ? AND a.date BETWEEN ? AND ?
        GROUP BY a.date");
    $stmt->bind_param('iss', $org_id, $first_day_of_month, $last_day_of_month);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $calendar_data[$row['date']] = ($calendar_data[$row['date']] ?? 0) + intval($row['present_count']);
    }
    $stmt->close();
}

// Employee attendance by day for calendar
if ($include_employees) {
    $stmt = $conn->prepare("SELECT ea.date, SUM(CASE WHEN ea.in_time IS NULL AND ea.out_time IS NULL THEN 0 ELSE 1 END) as present_count
        FROM employee_attendance ea
        JOIN employees e ON ea.employee_id = e.id
        WHERE e.org_id = ? AND ea.date BETWEEN ? AND ?
        GROUP BY ea.date");
    $stmt->bind_param('iss', $org_id, $first_day_of_month, $last_day_of_month);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $calendar_data[$row['date']] = ($calendar_data[$row['date']] ?? 0) + intval($row['present_count']);
    }
    $stmt->close();
}

// Collect only dates that actually have attendance entries
$dates = [];
$present_by_date = [];
$detail_rows = [];
$total_present_sum = 0;
$days_count = 0;

// Student attendance by day
if ($include_students) {
    $stmt = $conn->prepare("SELECT a.date, SUM(CASE WHEN a.in_time IS NULL AND a.out_time IS NULL THEN 0 ELSE 1 END) as present_count
        FROM attendance a
        JOIN students s ON a.student_id = s.id
        WHERE s.org_id = ? AND a.date BETWEEN ? AND ?
        GROUP BY a.date");
    $stmt->bind_param('iss', $org_id, $filter_from, $filter_to);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $present_by_date[$row['date']] = ($present_by_date[$row['date']] ?? 0) + intval($row['present_count']);
    }
    $stmt->close();
}

// Employee attendance by day
if ($include_employees) {
    $stmt = $conn->prepare("SELECT ea.date, SUM(CASE WHEN ea.in_time IS NULL AND ea.out_time IS NULL THEN 0 ELSE 1 END) as present_count
        FROM employee_attendance ea
        JOIN employees e ON ea.employee_id = e.id
        WHERE e.org_id = ? AND ea.date BETWEEN ? AND ?
        GROUP BY ea.date");
    $stmt->bind_param('iss', $org_id, $filter_from, $filter_to);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $present_by_date[$row['date']] = ($present_by_date[$row['date']] ?? 0) + intval($row['present_count']);
    }
    $stmt->close();
}

// Detail list (students + employees)
$detail_query_parts = [];
$detail_params = [];
$detail_types = '';
$search_param = !empty($filter_search) ? "%{$filter_search}%" : null;

if ($include_students) {
    if (!empty($filter_search)) {
        $detail_query_parts[] = "SELECT a.date, 'Student' as person_type, s.name as person_name, s.class as extra, a.in_time, a.out_time FROM attendance a JOIN students s ON a.student_id = s.id WHERE s.org_id = ? AND a.date BETWEEN ? AND ? AND s.name LIKE ?";
        $detail_params[] = $org_id;
        $detail_params[] = $filter_from;
        $detail_params[] = $filter_to;
        $detail_params[] = $search_param;
        $detail_types .= 'isss';
    } else {
        $detail_query_parts[] = "SELECT a.date, 'Student' as person_type, s.name as person_name, s.class as extra, a.in_time, a.out_time FROM attendance a JOIN students s ON a.student_id = s.id WHERE s.org_id = ? AND a.date BETWEEN ? AND ?";
        $detail_params[] = $org_id;
        $detail_params[] = $filter_from;
        $detail_params[] = $filter_to;
        $detail_types .= 'iss';
    }
}
if ($include_employees) {
    if (!empty($filter_search)) {
        $detail_query_parts[] = "SELECT ea.date, 'Employee' as person_type, e.name as person_name, NULL as extra, ea.in_time, ea.out_time FROM employee_attendance ea JOIN employees e ON ea.employee_id = e.id WHERE e.org_id = ? AND ea.date BETWEEN ? AND ? AND e.name LIKE ?";
        $detail_params[] = $org_id;
        $detail_params[] = $filter_from;
        $detail_params[] = $filter_to;
        $detail_params[] = $search_param;
        $detail_types .= 'isss';
    } else {
        $detail_query_parts[] = "SELECT ea.date, 'Employee' as person_type, e.name as person_name, NULL as extra, ea.in_time, ea.out_time FROM employee_attendance ea JOIN employees e ON ea.employee_id = e.id WHERE e.org_id = ? AND ea.date BETWEEN ? AND ?";
        $detail_params[] = $org_id;
        $detail_params[] = $filter_from;
        $detail_params[] = $filter_to;
        $detail_types .= 'iss';
    }
}

if (!empty($detail_query_parts)) {
    $union_query = implode(" UNION ALL ", $detail_query_parts) . " ORDER BY date DESC, person_type, person_name";
    $stmt = $conn->prepare($union_query);
    $stmt->bind_param($detail_types, ...$detail_params);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $detail_rows[$row['date']][] = $row;
    }
    $stmt->close();
}

// Build date list from actual records
$dates = array_unique(array_merge(array_keys($present_by_date), array_keys($detail_rows)));
rsort($dates);
$days_count = count($dates);

// Build per-day summaries
$daily_summary = [];
$total_expected_daily = ($include_students ? $total_students : 0) + ($include_employees ? $total_employees : 0);
foreach ($dates as $date_key) {
    $present = $present_by_date[$date_key] ?? 0;
    $absent = $total_expected_daily > 0 ? max($total_expected_daily - $present, 0) : 0;
    $daily_summary[] = [
        'date' => $date_key,
        'present' => $present,
        'absent' => $absent,
        'details' => $detail_rows[$date_key] ?? []
    ];
    $total_present_sum += $present;
}

// Stats
$avg_attendance_pct = ($total_expected_daily > 0 && $days_count > 0)
    ? round(($total_present_sum / ($total_expected_daily * $days_count)) * 100, 1)
    : 0;
$today = date('Y-m-d');
$today_present = $present_by_date[$today] ?? 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/ux-improvements.css">
    <style>
        body { padding-top: 140px; }
    </style>
</head>
<body class="bg-gray-50 antialiased">
    <?php include 'navbar.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Attendance</h1>
                <p class="mt-2 text-sm text-gray-600">Daily attendance summary with student and employee coverage.</p>
            </div>
        </div>

        <!-- Calendar Section -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900"><?php echo date('F Y', strtotime($first_day_of_month)); ?></h2>
                <div class="flex gap-2">
                    <?php
                        $prev_month = $current_month - 1;
                        $prev_year = $current_year;
                        if ($prev_month < 1) {
                            $prev_month = 12;
                            $prev_year--;
                        }
                        $next_month = $current_month + 1;
                        $next_year = $current_year;
                        if ($next_month > 12) {
                            $next_month = 1;
                            $next_year++;
                        }
                    ?>
                    <a href="?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>&type=<?php echo htmlspecialchars($filter_type); ?>" class="bg-gradient-to-r from-gray-200 to-gray-300 hover:from-gray-300 hover:to-gray-400 text-gray-700 px-5 py-2.5 rounded-lg transition-all duration-200 font-semibold shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2">
                        <i class="fas fa-chevron-left mr-2"></i> Previous
                    </a>
                    <a href="?month=<?php echo date('n'); ?>&year=<?php echo date('Y'); ?>&type=<?php echo htmlspecialchars($filter_type); ?>" class="bg-gradient-to-r from-teal-500 to-teal-600 hover:from-teal-600 hover:to-teal-700 text-white px-5 py-2.5 rounded-lg transition-all duration-200 font-semibold shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
                        <i class="fas fa-calendar-day mr-2"></i> Today
                    </a>
                    <a href="?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>&type=<?php echo htmlspecialchars($filter_type); ?>" class="bg-gradient-to-r from-gray-200 to-gray-300 hover:from-gray-300 hover:to-gray-400 text-gray-700 px-5 py-2.5 rounded-lg transition-all duration-200 font-semibold shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2">
                        Next <i class="fas fa-chevron-right ml-2"></i>
                    </a>
                </div>
            </div>

            <!-- Calendar Grid -->
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="border border-gray-300 p-2 text-sm font-semibold text-gray-700 text-center">Sun</th>
                            <th class="border border-gray-300 p-2 text-sm font-semibold text-gray-700 text-center">Mon</th>
                            <th class="border border-gray-300 p-2 text-sm font-semibold text-gray-700 text-center">Tue</th>
                            <th class="border border-gray-300 p-2 text-sm font-semibold text-gray-700 text-center">Wed</th>
                            <th class="border border-gray-300 p-2 text-sm font-semibold text-gray-700 text-center">Thu</th>
                            <th class="border border-gray-300 p-2 text-sm font-semibold text-gray-700 text-center">Fri</th>
                            <th class="border border-gray-300 p-2 text-sm font-semibold text-gray-700 text-center">Sat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $first_weekday = date('w', strtotime($first_day_of_month));
                            $days_in_month = (int)date('d', strtotime($last_day_of_month));
                            $day = 1;

                            for ($week = 0; $week < 6; $week++) {
                                echo '<tr>';
                                for ($weekday = 0; $weekday < 7; $weekday++) {
                                    if ($week == 0 && $weekday < $first_weekday) {
                                        echo '<td class="border border-gray-300 p-3 bg-gray-50"></td>';
                                    } elseif ($day <= $days_in_month) {
                                        $date_str = "$current_year-" . str_pad($current_month, 2, '0', STR_PAD_LEFT) . "-" . str_pad($day, 2, '0', STR_PAD_LEFT);
                                        $has_records = isset($calendar_data[$date_str]);
                                        $present_count = $calendar_data[$date_str] ?? 0;
                                        $is_today = ($date_str === date('Y-m-d'));

                                        if ($has_records) {
                                            $bg_color = 'bg-green-50';
                                            $border_color = 'border-green-300';
                                            $text_color = 'text-green-700';
                                        } else {
                                            $bg_color = 'bg-gray-100';
                                            $border_color = 'border-gray-300';
                                            $text_color = 'text-gray-400';
                                        }

                                        if ($is_today) {
                                            $bg_color = 'bg-blue-100';
                                            $border_color = 'border-blue-500';
                                        }

                                        echo "<td class='border $border_color p-3 $bg_color h-24 hover:bg-opacity-75 transition cursor-pointer' onclick=\"scrollToDate('$date_str')\">";
                                        echo "  <div class='h-full flex flex-col justify-between'>";
                                        echo "    <div class='text-lg font-bold text-gray-900'>$day</div>";
                                        
                                        if ($has_records) {
                                            echo "    <div class='text-center'>";
                                            echo "      <div class='text-sm font-semibold $text_color'>$present_count Present</div>";
                                            echo "      <div class='text-xs text-green-600 font-medium'>Open</div>";
                                            echo "    </div>";
                                        } else {
                                            echo "    <div class='text-center'>";
                                            echo "      <div class='text-xs text-gray-400 font-medium'>Closed</div>";
                                            echo "    </div>";
                                        }

                                        echo "  </div>";
                                        echo "</td>";
                                        $day++;
                                    } else {
                                        echo '<td class="border border-gray-300 p-3 bg-gray-50"></td>';
                                    }
                                }
                                echo '</tr>';
                                if ($day > $days_in_month) break;
                            }
                        ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex gap-4 text-sm">
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 bg-green-100 border border-green-300 rounded"></div>
                    <span class="text-gray-600">Open (Has Records)</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 bg-gray-100 border border-gray-300 rounded"></div>
                    <span class="text-gray-600">Closed (No Records)</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 bg-blue-100 border border-blue-500 rounded"></div>
                    <span class="text-gray-600">Today</span>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-gray-600 text-sm font-medium">Total Present (Range)</p>
                <p class="text-3xl font-bold text-teal-600 mt-2"><?php echo number_format($total_present_sum); ?></p>
                <p class="text-xs text-gray-500 mt-1">Across <?php echo $days_count; ?> day<?php echo $days_count > 1 ? 's' : ''; ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-gray-600 text-sm font-medium">Avg Daily Attendance</p>
                <p class="text-3xl font-bold text-blue-600 mt-2"><?php echo $avg_attendance_pct; ?>%</p>
                <p class="text-xs text-gray-500 mt-1">Expected per day: <?php echo $total_expected_daily; ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-gray-600 text-sm font-medium">Today Present</p>
                <p class="text-3xl font-bold text-purple-600 mt-2"><?php echo number_format($today_present); ?></p>
                <p class="text-xs text-gray-500 mt-1">Today: <?php echo date('M d, Y'); ?></p>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Filters</h3>
            <form method="GET" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                    <input type="text" name="search" placeholder="Search by student or employee name..." value="<?php echo htmlspecialchars($filter_search); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                        <input type="date" name="from" value="<?php echo htmlspecialchars($filter_from); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                        <input type="date" name="to" value="<?php echo htmlspecialchars($filter_to); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                        <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500">
                            <option value="all" <?php echo $filter_type === 'all' ? 'selected' : ''; ?>>Students & Employees</option>
                            <option value="students" <?php echo $filter_type === 'students' ? 'selected' : ''; ?>>Students Only</option>
                            <option value="employees" <?php echo $filter_type === 'employees' ? 'selected' : ''; ?>>Employees Only</option>
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="flex-1 bg-teal-600 text-white px-4 py-2 rounded-md hover:bg-teal-700 transition font-medium">
                            <i class="fas fa-filter mr-2"></i> Apply
                        </button>
                        <a href="attendance.php" class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 transition font-medium text-center">
                            <i class="fas fa-undo mr-2"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Daily List -->
        <div class="space-y-4">
            <?php foreach ($daily_summary as $day): ?>
                <div class="bg-white rounded-lg shadow border border-gray-100">
                    <button type="button" class="w-full flex items-center justify-between px-4 py-4 focus:outline-none" onclick="toggleDetails('<?php echo $day['date']; ?>')">
                        <div>
                            <p class="text-sm text-gray-500"><?php echo date('l', strtotime($day['date'])); ?></p>
                            <p class="text-lg font-semibold text-gray-900"><?php echo date('M d, Y', strtotime($day['date'])); ?></p>
                        </div>
                        <div class="flex items-center gap-6">
                            <div class="text-left">
                                <p class="text-xs text-gray-500">Present</p>
                                <p class="text-green-600 font-bold text-lg"><?php echo $day['present']; ?></p>
                            </div>
                            <div class="text-left">
                                <p class="text-xs text-gray-500">Absent (est.)</p>
                                <p class="text-red-600 font-bold text-lg"><?php echo $day['absent']; ?></p>
                            </div>
                            <i class="fas fa-chevron-down text-gray-500 transition" id="chevron-<?php echo $day['date']; ?>"></i>
                        </div>
                    </button>
                    <div id="details-<?php echo $day['date']; ?>" class="hidden border-t border-gray-100">
                        <?php if (!empty($day['details'])): ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Person</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Type</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">In</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Out</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <?php foreach ($day['details'] as $row): ?>
                                            <?php
                                                $present_flag = (!empty($row['in_time']) || !empty($row['out_time']));
                                            ?>
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-4 py-3 text-sm text-gray-900">
                                                    <div class="font-medium"><?php echo htmlspecialchars($row['person_name']); ?></div>
                                                    <?php if (!empty($row['extra'])): ?>
                                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($row['extra']); ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-4 py-3 text-sm text-gray-600"><?php echo htmlspecialchars($row['person_type']); ?></td>
                                                <td class="px-4 py-3 text-sm text-gray-600"><?php echo $row['in_time'] ? htmlspecialchars($row['in_time']) : '—'; ?></td>
                                                <td class="px-4 py-3 text-sm text-gray-600"><?php echo $row['out_time'] ? htmlspecialchars($row['out_time']) : '—'; ?></td>
                                                <td class="px-4 py-3 text-sm">
                                                    <?php if ($present_flag): ?>
                                                        <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">Present</span>
                                                    <?php else: ?>
                                                        <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-xs font-medium">Absent</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="p-4 text-sm text-gray-500">No attendance records for this date.</div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        function toggleDetails(date) {
            const panel = document.getElementById('details-' + date);
            const chevron = document.getElementById('chevron-' + date);
            const isHidden = panel.classList.contains('hidden');
            document.querySelectorAll('[id^="details-"]').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('[id^="chevron-"]').forEach(el => el.classList.remove('rotate-180'));
            if (isHidden) {
                panel.classList.remove('hidden');
                chevron.classList.add('rotate-180');
            }
        }

        function scrollToDate(dateStr) {
            // Scroll to the details section for that date
            const element = document.getElementById('details-' + dateStr);
            if (element) {
                // Auto-open if closed
                const panel = document.getElementById('details-' + dateStr);
                if (panel.classList.contains('hidden')) {
                    toggleDetails(dateStr);
                }
                // Smooth scroll
                element.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
