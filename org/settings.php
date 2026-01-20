<?php
// org/settings.php
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

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'update_sex_enum') {
            // Get current enum values
            $sex_values = isset($_POST['sex_values']) ? array_filter(array_map('trim', explode(',', $_POST['sex_values']))) : [];
            
            if (empty($sex_values) || count($sex_values) < 2) {
                throw new Exception('At least 2 values are required for Sex enum');
            }
            
            // Build new enum string
            $enum_str = "'" . implode("','", array_map(function($v) use ($conn) { return $conn->real_escape_string($v); }, $sex_values)) . "'";
            
            // Alter table
            $conn->query("ALTER TABLE students MODIFY COLUMN sex ENUM($enum_str)");
            $success_message = 'Sex enum values updated successfully!';
        }
        
        if ($_POST['action'] === 'update_religion_enum') {
            // Get current enum values
            $religion_values = isset($_POST['religion_values']) ? array_filter(array_map('trim', explode(',', $_POST['religion_values']))) : [];
            
            if (empty($religion_values) || count($religion_values) < 2) {
                throw new Exception('At least 2 values are required for Religion enum');
            }
            
            // Build new enum string
            $enum_str = "'" . implode("','", array_map(function($v) use ($conn) { return $conn->real_escape_string($v); }, $religion_values)) . "'";
            
            // Alter table
            $conn->query("ALTER TABLE students MODIFY COLUMN religion ENUM($enum_str)");
            $success_message = 'Religion enum values updated successfully!';
        }
        
        if ($_POST['action'] === 'update_community_enum') {
            // Get current enum values
            $community_values = isset($_POST['community_values']) ? array_filter(array_map('trim', explode(',', $_POST['community_values']))) : [];
            
            if (empty($community_values) || count($community_values) < 2) {
                throw new Exception('At least 2 values are required for Community enum');
            }
            
            // Build new enum string
            $enum_str = "'" . implode("','", array_map(function($v) use ($conn) { return $conn->real_escape_string($v); }, $community_values)) . "'";
            
            // Alter table
            $conn->query("ALTER TABLE students MODIFY COLUMN community ENUM($enum_str)");
            $success_message = 'Community enum values updated successfully!';
        }

        if ($_POST['action'] === 'update_class_enum') {
            // Get current enum values
            $class_values = isset($_POST['class_values']) ? array_filter(array_map('trim', explode(',', $_POST['class_values']))) : [];
            
            if (empty($class_values) || count($class_values) < 1) {
                throw new Exception('At least 1 value is required for Class enum');
            }
            
            // Build new enum string
            $enum_str = "'" . implode("','", array_map(function($v) use ($conn) { return $conn->real_escape_string($v); }, $class_values)) . "'";
            
            // Alter table
            $conn->query("ALTER TABLE students MODIFY COLUMN class ENUM($enum_str) NOT NULL");
            $success_message = 'Class enum values updated successfully!';
        }

        if ($_POST['action'] === 'update_stream_enum') {
            // Get current enum values
            $stream_values = isset($_POST['stream_values']) ? array_filter(array_map('trim', explode(',', $_POST['stream_values']))) : [];
            
            if (empty($stream_values) || count($stream_values) < 1) {
                throw new Exception('At least 1 value is required for Stream enum');
            }
            
            // Build new enum string
            $enum_str = "'" . implode("','", array_map(function($v) use ($conn) { return $conn->real_escape_string($v); }, $stream_values)) . "'";
            
            // Alter table
            $conn->query("ALTER TABLE students MODIFY COLUMN stream ENUM($enum_str)");
            $success_message = 'Stream enum values updated successfully!';
        }
    } catch (Exception $e) {
        $error_message = 'Error: ' . $e->getMessage();
    }
}

// Get current enum values
$get_enum_values = function($column_name) use ($conn) {
    $result = $conn->query("SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='students' AND TABLE_SCHEMA=DATABASE() AND COLUMN_NAME='$column_name'");
    if ($result && $row = $result->fetch_assoc()) {
        $type = $row['COLUMN_TYPE'];
        // Extract values from enum('value1','value2',...)
        preg_match("/enum\((.*)\)/i", $type, $matches);
        if (isset($matches[1])) {
            $values = str_getcsv($matches[1], ",", "'");
            return $values;
        }
    }
    return [];
};

$sex_values = $get_enum_values('sex');
$religion_values = $get_enum_values('religion');
$community_values = $get_enum_values('community');
$class_values = $get_enum_values('class');
$stream_values = $get_enum_values('stream');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Student Form Fields</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif;
            padding-top: 140px !important;
        }
    </style>
</head>
<body class="bg-gray-50 antialiased">
    <?php include 'navbar.php'; ?>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <!-- Header -->
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-900">Settings</h2>
            <p class="mt-2 text-sm text-gray-600">Configure student form field options (Sex, Religion, Community, Class, Stream)</p>
        </div>

        <!-- Messages -->
        <?php if ($success_message): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg">
                <div class="flex">
                    <svg class="h-5 w-5 text-green-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg">
                <div class="flex">
                    <svg class="h-5 w-5 text-red-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Settings Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Sex Enum Settings -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Sex</h3>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="update_sex_enum">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Current Values (comma-separated)
                        </label>
                        <textarea name="sex_values" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500" placeholder="e.g., Male, Female, Other"><?php echo htmlspecialchars(implode(', ', $sex_values)); ?></textarea>
                        <p class="mt-2 text-xs text-gray-500">Separate each value with a comma</p>
                    </div>
                    
                    <button type="submit" class="w-full bg-teal-600 hover:bg-teal-700 text-white font-semibold py-2 px-4 rounded-lg transition">
                        Update Sex Options
                    </button>
                </form>
            </div>

            <!-- Religion Enum Settings -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Religion</h3>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="update_religion_enum">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Current Values (comma-separated)
                        </label>
                        <textarea name="religion_values" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500" placeholder="e.g., Hindu, Muslim, Christian, Other"><?php echo htmlspecialchars(implode(', ', $religion_values)); ?></textarea>
                        <p class="mt-2 text-xs text-gray-500">Separate each value with a comma</p>
                    </div>
                    
                    <button type="submit" class="w-full bg-teal-600 hover:bg-teal-700 text-white font-semibold py-2 px-4 rounded-lg transition">
                        Update Religion Options
                    </button>
                </form>
            </div>

            <!-- Community Enum Settings -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Community</h3>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="update_community_enum">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Current Values (comma-separated)
                        </label>
                        <textarea name="community_values" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500" placeholder="e.g., ST, SC, BC, General, Other"><?php echo htmlspecialchars(implode(', ', $community_values)); ?></textarea>
                        <p class="mt-2 text-xs text-gray-500">Separate each value with a comma</p>
                    </div>
                    
                    <button type="submit" class="w-full bg-teal-600 hover:bg-teal-700 text-white font-semibold py-2 px-4 rounded-lg transition">
                        Update Community Options
                    </button>
                </form>
            </div>

            <!-- Class Enum Settings -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Class</h3>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="update_class_enum">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Current Values (comma-separated)
                        </label>
                        <textarea name="class_values" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500" placeholder="e.g., 10, 11, 12"><?php echo htmlspecialchars(implode(', ', $class_values)); ?></textarea>
                        <p class="mt-2 text-xs text-gray-500">Separate each value with a comma</p>
                    </div>
                    
                    <button type="submit" class="w-full bg-teal-600 hover:bg-teal-700 text-white font-semibold py-2 px-4 rounded-lg transition">
                        Update Class Options
                    </button>
                </form>
            </div>

            <!-- Stream Enum Settings -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Stream</h3>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="update_stream_enum">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Current Values (comma-separated)
                        </label>
                        <textarea name="stream_values" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500" placeholder="e.g., Science, Commerce, Arts"><?php echo htmlspecialchars(implode(', ', $stream_values)); ?></textarea>
                        <p class="mt-2 text-xs text-gray-500">Separate each value with a comma</p>
                    </div>
                    
                    <button type="submit" class="w-full bg-teal-600 hover:bg-teal-700 text-white font-semibold py-2 px-4 rounded-lg transition">
                        Update Stream Options
                    </button>
                </form>
            </div>
        </div>

        <!-- Information Card -->
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h4 class="text-lg font-semibold text-blue-900 mb-3">How to Use</h4>
            <ul class="space-y-2 text-blue-800 text-sm">
                <li class="flex items-start">
                    <svg class="h-5 w-5 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span>Enter the options separated by commas (e.g., "Male, Female, Other")</span>
                </li>
                <li class="flex items-start">
                    <svg class="h-5 w-5 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span>Each field must have at least 2 options</span>
                </li>
                <li class="flex items-start">
                    <svg class="h-5 w-5 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span>Changes are reflected immediately in student add/edit forms</span>
                </li>
                <li class="flex items-start">
                    <svg class="h-5 w-5 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span>Existing student records are not affected by these changes</span>
                </li>
            </ul>
        </div>
    </div>

    <script>
        // Show success/error message
        <?php if ($success_message || $error_message): ?>
            Swal.fire({
                title: '<?php echo $success_message ? "Success" : "Error"; ?>',
                text: '<?php echo htmlspecialchars($success_message ?: $error_message); ?>',
                icon: '<?php echo $success_message ? "success" : "error"; ?>',
                confirmButtonColor: '#0d9488'
            });
        <?php endif; ?>
    </script>
</body>
</html>
<?php $conn->close(); ?>
