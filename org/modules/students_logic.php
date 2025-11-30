<?php
// org/modules/students_logic.php

$success = '';
$error = '';
$conflict_student = null;

// Handle Add/Update Student (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $class = $_POST['class'];
    $batch = $_POST['batch'];
    $roll_number = isset($_POST['roll_number']) && trim($_POST['roll_number']) !== '' ? trim($_POST['roll_number']) : null;
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $admission_amount = isset($_POST['admission_amount']) && $_POST['admission_amount'] !== '' ? floatval($_POST['admission_amount']) : 0.00;
    $fee = isset($_POST['fee']) && $_POST['fee'] !== '' ? floatval($_POST['fee']) : 0.00;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $remark = isset($_POST['remark']) && trim($_POST['remark']) !== '' ? trim($_POST['remark']) : null;
    $force_add = isset($_POST['force_add']) ? true : false;
    $student_id = isset($_POST['student_id']) && !empty($_POST['student_id']) ? $_POST['student_id'] : null;

    // Handle photo upload
    $photo_path = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (in_array($_FILES['photo']['type'], $allowed_types)) {
            $photo_name = uniqid() . '_' . $_FILES['photo']['name'];
            $photo_path = '../uploads/students/' . $photo_name;

            // Create directory if it doesn't exist
            if (!file_exists('../uploads/students')) {
                mkdir('../uploads/students', 0777, true);
            }

            move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path);
        }
    }

    // Check for duplicate roll number in same class & batch (for both add and edit)
    if ($roll_number && !$force_add) {
        // Check for duplicate roll number in same class AND same batch
        // Exclude current student if editing
        $sql = "SELECT * FROM students WHERE org_id = ? AND class = ? AND batch = ? AND roll_number = ? AND roll_number IS NOT NULL AND roll_number != ''";
        if ($student_id) {
            $sql .= " AND id != ?";
        }

        $checkStmt = $conn->prepare($sql);

        if ($student_id) {
            // Edit mode: exclude current student from conflict check
            $checkStmt->bind_param("isssi", $org_id, $class, $batch, $roll_number, $student_id);
        } else {
            // Add mode: check for any existing conflict
            $checkStmt->bind_param("isss", $org_id, $class, $batch, $roll_number);
        }

        $checkStmt->execute();
        $result = $checkStmt->get_result();

        if ($result->num_rows > 0) {
            $conflict_student = $result->fetch_assoc();
        }
    }

    if (!$conflict_student) {
        if ($student_id) {
            // Fetch current admission amount before update
            $old_amount = 0;
            $oldStmt = $conn->prepare("SELECT admission_amount FROM students WHERE id = ?");
            $oldStmt->bind_param("i", $student_id);
            $oldStmt->execute();
            $oldRes = $oldStmt->get_result();
            if ($row = $oldRes->fetch_assoc()) {
                $old_amount = floatval($row['admission_amount']);
            }

            // Update existing student
            if ($photo_path) {
                $stmt = $conn->prepare("UPDATE students SET name=?, class=?, batch=?, roll_number=?, address=?, phone=?, email=?, photo=?, admission_amount=?, fee=?, is_active=?, remark=? WHERE id=? AND org_id=?");
                $stmt->bind_param("ssssssssddiisi", $name, $class, $batch, $roll_number, $address, $phone, $email, $photo_path, $admission_amount, $fee, $is_active, $remark, $student_id, $org_id);
            } else {
                $stmt = $conn->prepare("UPDATE students SET name=?, class=?, batch=?, roll_number=?, address=?, phone=?, email=?, admission_amount=?, fee=?, is_active=?, remark=? WHERE id=? AND org_id=?");
                $stmt->bind_param("sssssssddiisi", $name, $class, $batch, $roll_number, $address, $phone, $email, $admission_amount, $fee, $is_active, $remark, $student_id, $org_id);
            }

            if ($stmt->execute()) {
                // Handle supporting documents
                if (isset($_FILES['documents'])) {
                    $total = count($_FILES['documents']['name']);
                    for ($i = 0; $i < $total; $i++) {
                        $tmpFilePath = $_FILES['documents']['tmp_name'][$i];
                        if ($tmpFilePath != "") {
                            $newFilePath = "../uploads/students/" . uniqid() . '_' . $_FILES['documents']['name'][$i];
                            if (move_uploaded_file($tmpFilePath, $newFilePath)) {
                                $docStmt = $conn->prepare("INSERT INTO student_documents (student_id, filename, filepath) VALUES (?, ?, ?)");
                                $docStmt->bind_param("iss", $student_id, $_FILES['documents']['name'][$i], $newFilePath);
                                $docStmt->execute();
                            }
                        }
                    }
                }

                // Update payment if admission amount changed
                if (abs($admission_amount - $old_amount) > 0.01) {
                    // Check if admission payment exists
                    $payCheck = $conn->prepare("SELECT id FROM student_payments WHERE student_id = ? AND category = 'Admission'");
                    $payCheck->bind_param("i", $student_id);
                    $payCheck->execute();
                    $payRes = $payCheck->get_result();

                    if ($payRes->num_rows > 0) {
                        // Update existing payment
                        $payRow = $payRes->fetch_assoc();
                        $payUpdate = $conn->prepare("UPDATE student_payments SET amount = ? WHERE id = ?");
                        $payUpdate->bind_param("di", $admission_amount, $payRow['id']);
                        $payUpdate->execute();
                    } else {
                        // Create new payment if not exists (and amount > 0)
                        if ($admission_amount > 0) {
                            $payInsert = $conn->prepare("INSERT INTO student_payments (student_id, amount, transaction_type, category, description) VALUES (?, ?, 'credit', 'Admission', 'Admission Fee')");
                            $payInsert->bind_param("id", $student_id, $admission_amount);
                            $payInsert->execute();
                        }
                    }
                }

                $_SESSION['success'] = "Student updated successfully.";
                header("Location: students.php");
                exit();
            } else {
                $_SESSION['error'] = "Error updating student: " . $stmt->error;
                header("Location: students.php");
                exit();
            }
        } else {
            // Add new student
            $stmt = $conn->prepare("INSERT INTO students (org_id, name, class, batch, roll_number, address, phone, email, photo, admission_amount, fee, is_active, remark) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssssssddis", $org_id, $name, $class, $batch, $roll_number, $address, $phone, $email, $photo_path, $admission_amount, $fee, $is_active, $remark);

            if ($stmt->execute()) {
                $new_student_id = $stmt->insert_id;

                // Create Admission Payment
                if ($admission_amount > 0) {
                    $payStmt = $conn->prepare("INSERT INTO student_payments (student_id, amount, transaction_type, category, description) VALUES (?, ?, 'credit', 'Admission', 'Admission Fee')");
                    $payStmt->bind_param("id", $new_student_id, $admission_amount);
                    $payStmt->execute();
                }

                // Handle supporting documents
                if (isset($_FILES['documents'])) {
                    $total = count($_FILES['documents']['name']);
                    for ($i = 0; $i < $total; $i++) {
                        $tmpFilePath = $_FILES['documents']['tmp_name'][$i];
                        if ($tmpFilePath != "") {
                            $newFilePath = "../uploads/students/" . uniqid() . '_' . $_FILES['documents']['name'][$i];
                            if (move_uploaded_file($tmpFilePath, $newFilePath)) {
                                $docStmt = $conn->prepare("INSERT INTO student_documents (student_id, filename, filepath) VALUES (?, ?, ?)");
                                $docStmt->bind_param("iss", $new_student_id, $_FILES['documents']['name'][$i], $newFilePath);
                                $docStmt->execute();
                            }
                        }
                    }
                }

                $_SESSION['success'] = "Student added successfully.";
                header("Location: students.php");
                exit();
            } else {
                $_SESSION['error'] = "Error adding student: " . $stmt->error;
                header("Location: students.php");
                exit();
            }
        }
    }
}
?>
