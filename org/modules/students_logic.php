<?php
// org/modules/students_logic.php

$success = '';
$error = '';
$conflict_student = null;

// Handle Add/Update Student (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['cancel_conflict'])) {
    $name = $_POST['name'];
    $class = $_POST['class'];
    $batch = $_POST['batch'];
    $roll_number = isset($_POST['roll_number']) && trim($_POST['roll_number']) !== '' ? trim($_POST['roll_number']) : null;
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    
    // New personal fields
    $sex = isset($_POST['sex']) && trim($_POST['sex']) !== '' ? trim($_POST['sex']) : null;
    $sex_other = isset($_POST['sex_other']) && trim($_POST['sex_other']) !== '' ? trim($_POST['sex_other']) : null;
    $date_of_birth = isset($_POST['date_of_birth']) && trim($_POST['date_of_birth']) !== '' ? trim($_POST['date_of_birth']) : null;
    $place_of_birth = isset($_POST['place_of_birth']) && trim($_POST['place_of_birth']) !== '' ? trim($_POST['place_of_birth']) : null;
    $nationality = isset($_POST['nationality']) && trim($_POST['nationality']) !== '' ? trim($_POST['nationality']) : 'Indian';
    $mother_tongue = isset($_POST['mother_tongue']) && trim($_POST['mother_tongue']) !== '' ? trim($_POST['mother_tongue']) : null;
    $religion = isset($_POST['religion']) && trim($_POST['religion']) !== '' ? trim($_POST['religion']) : null;
    $religion_other = isset($_POST['religion_other']) && trim($_POST['religion_other']) !== '' ? trim($_POST['religion_other']) : null;
    $community = isset($_POST['community']) && trim($_POST['community']) !== '' ? trim($_POST['community']) : null;
    $community_other = isset($_POST['community_other']) && trim($_POST['community_other']) !== '' ? trim($_POST['community_other']) : null;
    $native_district = isset($_POST['native_district']) && trim($_POST['native_district']) !== '' ? trim($_POST['native_district']) : null;
    $pin_code = isset($_POST['pin_code']) && trim($_POST['pin_code']) !== '' ? trim($_POST['pin_code']) : null;
    
    // Parent/Guardian fields
    $parent_guardian_name = isset($_POST['parent_guardian_name']) && trim($_POST['parent_guardian_name']) !== '' ? trim($_POST['parent_guardian_name']) : null;
    $parent_contact = isset($_POST['parent_contact']) && trim($_POST['parent_contact']) !== '' ? trim($_POST['parent_contact']) : null;
    
    // Examination fields
    $exam_name = isset($_POST['exam_name']) && trim($_POST['exam_name']) !== '' ? trim($_POST['exam_name']) : null;
    $exam_total_marks = isset($_POST['exam_total_marks']) && $_POST['exam_total_marks'] !== '' ? intval($_POST['exam_total_marks']) : null;
    $exam_marks_obtained = isset($_POST['exam_marks_obtained']) && $_POST['exam_marks_obtained'] !== '' ? floatval($_POST['exam_marks_obtained']) : null;
    $exam_percentage = isset($_POST['exam_percentage']) && $_POST['exam_percentage'] !== '' ? floatval($_POST['exam_percentage']) : null;
    $exam_grade = isset($_POST['exam_grade']) && trim($_POST['exam_grade']) !== '' ? trim($_POST['exam_grade']) : null;
    
    // Handle fees - collect from form and store as JSON
    $fees_json = null;
    
    // If fees_json comes from the form
    if (isset($_POST['fees_json'])) {
        $posted_fees = trim($_POST['fees_json']);
        // Only use it if it's not empty string, 'null', or '0'
        if ($posted_fees !== '' && $posted_fees !== 'null' && $posted_fees !== '0') {
            $fees_json = $posted_fees;
        }
    }
    
    $admission_amount = isset($_POST['admission_amount']) && $_POST['admission_amount'] !== '' ? floatval($_POST['admission_amount']) : 0.00;
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

    // Get stream value
    $stream = isset($_POST['stream']) && trim($_POST['stream']) !== '' ? trim($_POST['stream']) : null;

    // Check for duplicate roll number in same class, stream & batch (for both add and edit)
    // Skip conflict check if bypass_once flag is set (user already saw conflict and chose to proceed)
    if ($roll_number && !$force_add && !isset($_POST['bypass_once'])) {
        // Check for duplicate roll number in same class, stream AND batch
        // Exclude current student if editing
        // Stream can be NULL, so we need to handle that properly
        $sql = "SELECT * FROM students WHERE org_id = ? AND class = ? AND batch = ? AND roll_number = ? AND roll_number IS NOT NULL AND roll_number != ''";
        
        // Add stream condition - handle NULL case
        if ($stream !== null) {
            $sql .= " AND stream = ?";
        } else {
            $sql .= " AND stream IS NULL";
        }
        
        if ($student_id) {
            $sql .= " AND id != ?";
        }

        $checkStmt = $conn->prepare($sql);

        if ($student_id) {
            // Edit mode: exclude current student from conflict check
            if ($stream !== null) {
                $checkStmt->bind_param('issssi', $org_id, $class, $batch, $roll_number, $stream, $student_id);
            } else {
                $checkStmt->bind_param('isssi', $org_id, $class, $batch, $roll_number, $student_id);
            }
        } else {
            // Add mode: check for any existing conflict
            if ($stream !== null) {
                $checkStmt->bind_param('issss', $org_id, $class, $batch, $roll_number, $stream);
            } else {
                $checkStmt->bind_param('isss', $org_id, $class, $batch, $roll_number);
            }
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
                $stmt = $conn->prepare("UPDATE students SET name=?, class=?, stream=?, batch=?, roll_number=?, address=?, phone=?, email=?, photo=?, sex=?, sex_other=?, date_of_birth=?, place_of_birth=?, nationality=?, mother_tongue=?, religion=?, religion_other=?, community=?, community_other=?, native_district=?, pin_code=?, parent_guardian_name=?, parent_contact=?, exam_name=?, exam_total_marks=?, exam_marks_obtained=?, exam_percentage=?, exam_grade=?, admission_amount=?, fees_json=?, is_active=?, remark=? WHERE id=? AND org_id=?");
                // Type: s s s s s s s s s s s s s s s s s s s s s s s i d d s d s i s i i
                $stmt->bind_param("sssssssssssssssssssssssiddsdsisii", $name, $class, $stream, $batch, $roll_number, $address, $phone, $email, $photo_path, $sex, $sex_other, $date_of_birth, $place_of_birth, $nationality, $mother_tongue, $religion, $religion_other, $community, $community_other, $native_district, $pin_code, $parent_guardian_name, $parent_contact, $exam_name, $exam_total_marks, $exam_marks_obtained, $exam_percentage, $exam_grade, $admission_amount, $fees_json, $is_active, $remark, $student_id, $org_id);
            } else {
                $stmt = $conn->prepare("UPDATE students SET name=?, class=?, stream=?, batch=?, roll_number=?, address=?, phone=?, email=?, sex=?, sex_other=?, date_of_birth=?, place_of_birth=?, nationality=?, mother_tongue=?, religion=?, religion_other=?, community=?, community_other=?, native_district=?, pin_code=?, parent_guardian_name=?, parent_contact=?, exam_name=?, exam_total_marks=?, exam_marks_obtained=?, exam_percentage=?, exam_grade=?, admission_amount=?, fees_json=?, is_active=?, remark=? WHERE id=? AND org_id=?");
                $stmt->bind_param("sssssssssssssssssssssssiddsdsisii", $name, $class, $stream, $batch, $roll_number, $address, $phone, $email, $sex, $sex_other, $date_of_birth, $place_of_birth, $nationality, $mother_tongue, $religion, $religion_other, $community, $community_other, $native_district, $pin_code, $parent_guardian_name, $parent_contact, $exam_name, $exam_total_marks, $exam_marks_obtained, $exam_percentage, $exam_grade, $admission_amount, $fees_json, $is_active, $remark, $student_id, $org_id);
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
                    // Verify student exists before creating payment
                    $verifyStmt = $conn->prepare("SELECT id FROM students WHERE id = ? AND org_id = ?");
                    $verifyStmt->bind_param("ii", $student_id, $org_id);
                    $verifyStmt->execute();
                    $verifyRes = $verifyStmt->get_result();
                    
                    if ($verifyRes->num_rows > 0) {
                        // Check if admission payment exists
                        $payCheck = $conn->prepare("SELECT id FROM student_payments WHERE student_id = ? AND category = 'Admission'");
                        $payCheck->bind_param("i", $student_id);
                        $payCheck->execute();
                        $payRes = $payCheck->get_result();

                        $admission_due = -abs($admission_amount);

                        if ($payRes->num_rows > 0) {
                            // Update existing payment to reflect negative due
                            $payRow = $payRes->fetch_assoc();
                            $payUpdate = $conn->prepare("UPDATE student_payments SET amount = ? WHERE id = ?");
                            $payUpdate->bind_param("di", $admission_due, $payRow['id']);
                            $payUpdate->execute();
                        } else {
                            // Create new payment if not exists (and amount > 0)
                            if ($admission_amount > 0) {
                                $payInsert = $conn->prepare("INSERT INTO student_payments (student_id, amount, transaction_type, category, description) VALUES (?, ?, 'credit', 'Admission', 'Admission Fee')");
                                $payInsert->bind_param("id", $student_id, $admission_due);
                                $payInsert->execute();
                            }
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
            $stmt = $conn->prepare("INSERT INTO students (org_id, name, class, stream, batch, roll_number, address, phone, email, photo, sex, sex_other, date_of_birth, place_of_birth, nationality, mother_tongue, religion, religion_other, community, community_other, native_district, pin_code, parent_guardian_name, parent_contact, exam_name, exam_total_marks, exam_marks_obtained, exam_percentage, exam_grade, admission_amount, fees_json, is_active, remark) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssssssssssssssssssssssiddsdsis", $org_id, $name, $class, $stream, $batch, $roll_number, $address, $phone, $email, $photo_path, $sex, $sex_other, $date_of_birth, $place_of_birth, $nationality, $mother_tongue, $religion, $religion_other, $community, $community_other, $native_district, $pin_code, $parent_guardian_name, $parent_contact, $exam_name, $exam_total_marks, $exam_marks_obtained, $exam_percentage, $exam_grade, $admission_amount, $fees_json, $is_active, $remark);

            if ($stmt->execute()) {
                $new_student_id = $stmt->insert_id;

                // Create Admission Payment as negative due
                if ($admission_amount > 0) {
                    $admission_due = -abs($admission_amount);
                    $payStmt = $conn->prepare("INSERT INTO student_payments (student_id, amount, transaction_type, category, description) VALUES (?, ?, 'credit', 'Admission', 'Admission Fee')");
                    $payStmt->bind_param("id", $new_student_id, $admission_due);
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
                $_SESSION['error'] = "Error adding student: " . $stmt->error . " | SQL: " . $stmt->sqlstate;
                header("Location: students.php");
                exit();
            }
        }
    }
}
?>
