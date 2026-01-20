# Database Schema Comparison Report

## Summary
- **Existing DB Tables:** 26
- **setup_db.php Tables:** 24
- **Missing in setup_db.php:** 2 tables
- **Schema Differences:** Multiple column mismatches

## Missing Tables in setup_db.php

1. **advance_payment_adjustments** - tracks advance payment deductions
2. **org_password_resets** - organization password reset tokens (separate from general password_resets)

## Schema Differences (Existing DB vs setup_db.php)

### admins
- Existing: `username VARCHAR(50)` (no created_at/updated_at timestamps)
- setup_db: `username VARCHAR(100)`, includes `created_at`, `updated_at`

### advance_payments
- Existing: `payment_date`, `description`
- setup_db: `payment_date`, `amount_used`, `amount_remaining`, `payment_method`, `notes`, `is_active`, `org_id`, `updated_at`

### app_versions
- Existing: `version_code`, `platform`, `release_date`, `apk_url`, `apk_size`, `changelog`, `mandatory`, `active`, `min_sdk_version`
- setup_db: `build_number`, `release_notes`, `file_path`, `file_size`, `checksum`, `is_mandatory`, `is_active`

### attendance
- Existing: Missing `org_id`, `status`, `notes`, `updated_at`
- setup_db: Includes all above fields

### employee_attendance
- Existing: Missing `org_id`, `status`, `notes`, `updated_at`
- setup_db: Includes all above fields

### employee_documents
- Existing: `file_name`, `file_path`, `document_type` (enum), `uploaded_at`
- setup_db: `file_name`, `file_path`, `document_type` (varchar), `file_size`, `org_id`, `created_at`

### employee_payments
- Existing: `transaction_type` (debit/credit), `category`, `description`, `payment_date`
- setup_db: `amount`, `payment_month`, `payment_status`, `payment_date`, `payment_method`, `transaction_id`, `notes`, `org_id`

### employees
- Existing: `phone`, `email`, `address`, `designation`, `department`, `salary`, `photo`, `is_active`, `updated_at`
- setup_db: Adds `employee_id`, `date_of_joining`, `date_of_birth`, `gender`, `emergency_contact`, `salary_type`, `base_salary`

### org_documents
- Existing: `file_path` only
- setup_db: Adds `document_type`, `file_name`, `file_size`

### org_fees
- Existing: `fee_name`, `is_default`, `updated_at`
- setup_db: Adds `fee_type`, `amount`, `is_mandatory`, `is_active`

### organizations
- Existing: `name`, `address`, `principal_name`, `owner_name`, `email`, `phone`, `alt_phone`, `logo`, `password`
- setup_db: Adds `city`, `state`, `zip_code`, `country`, `website`, `registration_number`, `gst_number`, `is_active`, `updated_at`

### settings
- Existing: `setting_key` (PK), `setting_value` only
- setup_db: Adds `id` (PK), `description`, `updated_at` with `setting_key` as unique

### student_documents
- Existing: `filename`, `filepath`, `uploaded_at`
- setup_db: `file_path`, `file_name`, `document_type`, `file_size`, `org_id`, `created_at`

### student_payments
- Existing: `amount`, `transaction_type`, `category`, `description`
- setup_db: Adds `org_id`, `notes` instead of `description`, `reference_id`

### students
- Existing: Very detailed with 40+ fields including `sex`, `sex_other`, all exam details, admission details
- setup_db: Simplified with core fields only

### subscription_plans
- Existing: `duration_months`, `amount`, `is_active`, `updated_at`
- setup_db: Adds `name`, `description`, `price` (instead of amount), `max_students`, `max_employees`, `features` (JSON)

### subscriptions
- Existing: `plan_months`, `amount`, `payment_proof`, `status` (pending/active/expired), `from_date`, `to_date`
- setup_db: Adds `plan_id` (FK), changes status enum to include 'Cancelled'

## Recommendation
Update setup_db.php to match existing production schema exactly.
