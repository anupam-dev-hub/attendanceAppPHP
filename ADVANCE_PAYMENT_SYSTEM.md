# Advance Payment System - Implementation Guide

## Overview
The advance payment system allows students to make advance payments that will be automatically deducted from future fee payments. This is useful for:
- Collecting deposits or advance payments
- Ensuring consistent cash flow
- Automatic adjustment of fees when payments are made

## Features

### 1. **Record Advance Payment**
- Click the "Advance" button next to any student in the students list
- Enter the advance payment amount
- Add an optional description (e.g., "Advance for next month")
- The system automatically updates the student's advance payment balance

### 2. **Automatic Deduction**
- When a student makes a fee payment, the system automatically deducts the advance payment balance first
- Only the remaining amount (if any) needs to be paid by the student
- A record of the deduction is maintained for auditing purposes

### 3. **View Advance Payment Balance**
- The "Advance" column in the students list shows each student's current advance payment balance
- Click "More" to view detailed advance payment history in the student information modal
- The advance balance is displayed in the Financial Information section

## Database Structure

### New Tables

#### `advance_payments`
Stores all advance payment records:
```sql
CREATE TABLE advance_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_date DATE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);
```

#### `advance_payment_adjustments`
Tracks when advance payments are deducted from fee payments:
```sql
CREATE TABLE advance_payment_adjustments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    advance_payment_id INT NOT NULL,
    student_payment_id INT NOT NULL,
    deduction_amount DECIMAL(10, 2) NOT NULL,
    adjustment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (advance_payment_id) REFERENCES advance_payments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_payment_id) REFERENCES student_payments(id) ON DELETE CASCADE
);
```

### Modified Tables

#### `students`
Added new column:
```sql
ALTER TABLE students ADD COLUMN advance_payment DECIMAL(10, 2) DEFAULT 0.00 AFTER admission_amount;
```

## API Endpoints

### 1. **Save Advance Payment**
**Endpoint:** `org/api/save_advance_payment.php`

**Method:** POST

**Parameters:**
- `student_id` (required, integer): ID of the student
- `amount` (required, float): Advance payment amount
- `description` (optional, string): Description of the advance payment

**Response:**
```json
{
  "success": true,
  "message": "Advance payment recorded successfully",
  "advance_payment": 5000.00
}
```

### 2. **Get Advance Payment**
**Endpoint:** `org/api/get_advance_payment.php`

**Method:** GET

**Parameters:**
- `student_id` (required, integer): ID of the student

**Response:**
```json
{
  "success": true,
  "advance_balance": 5000.00,
  "history": [
    {
      "id": 1,
      "amount": "5000.00",
      "payment_date": "2025-12-19",
      "description": "Advance Payment"
    }
  ],
  "adjustments": [
    {
      "id": 1,
      "amount": "5000.00",
      "adjustment_date": "2025-12-19 10:30:00",
      "category": "Tuition Fee",
      "deduction_amount": "3000.00"
    }
  ]
}
```

### 3. **Save Payment (Updated)**
**Endpoint:** `org/api/save_payment.php`

**Method:** POST

**Enhanced Features:**
- Automatically checks for advance payment balance
- Deducts advance payment first if available
- Creates adjustment record
- Updates advance payment balance

**Response now includes:**
```json
{
  "success": true,
  "message": "Payment recorded successfully",
  "payment_id": 123,
  "advance_deducted": 3000.00,
  "remaining_advance": 2000.00
}
```

## User Interface Changes

### Students List Table
- Added "Advance" column showing each student's advance payment balance
- New "Advance" button in the Actions column to record advance payments

### Student Information Modal
- Added "Advance Payment Balance" display in Financial Information section
- Shows current balance with purple color for better visibility

### Advance Payment Modal
- Displays current advance payment balance
- Shows advance payment history
- Allows recording new advance payments
- Provides optional description field

## Setup Instructions

### 1. **Initialize Database Schema**

Run the setup script to add tables:
```bash
php add_advance_payment_system.php
```

Or manually execute the SQL in your database:
```sql
ALTER TABLE students ADD COLUMN advance_payment DECIMAL(10, 2) DEFAULT 0.00;

CREATE TABLE advance_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_date DATE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

CREATE TABLE advance_payment_adjustments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    advance_payment_id INT NOT NULL,
    student_payment_id INT NOT NULL,
    deduction_amount DECIMAL(10, 2) NOT NULL,
    adjustment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (advance_payment_id) REFERENCES advance_payments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_payment_id) REFERENCES student_payments(id) ON DELETE CASCADE
);
```

### 2. **Update Application Files**

The following files have been updated:
- `org/api/save_advance_payment.php` - New endpoint for recording advance payments
- `org/api/get_advance_payment.php` - New endpoint for retrieving advance payment info
- `org/api/save_payment.php` - Updated to handle automatic advance payment deduction
- `org/students.php` - Added advance payment column and button
- `org/js/students.js` - Added JavaScript functions for advance payment management
- `org/modals/student_view_modal.php` - Added advance payment display

## Workflow Example

### Scenario: Student pays advance
1. Organization clicks "Advance" button for a student
2. Enters ₹5000 as advance payment
3. System records the advance payment and updates student's balance

### Scenario: Advance payment deduction
1. Student has ₹5000 advance payment
2. Student needs to pay ₹3000 tuition fee
3. Organization records ₹3000 payment
4. System automatically deducts ₹3000 from advance balance
5. Advance balance becomes ₹2000
6. A record of the deduction is created for audit trail

## Security Considerations

- All transactions use prepared statements to prevent SQL injection
- Database transactions ensure data consistency
- Authorization checks verify student belongs to organization
- Audit trail maintained for all advance payment adjustments

## Error Handling

- Invalid student ID: Returns error message
- Unauthorized access: Returns authorization error
- Database errors: Includes error details in response
- Transactions: Automatically rolled back on error

## Testing

### Test Cases

1. **Add Advance Payment**
   - Navigate to students list
   - Click "Advance" button
   - Enter amount and save
   - Verify balance updated

2. **View Advance Payment**
   - Click "More" on student
   - Check "Advance Payment Balance" in Financial Information
   - Verify history displays in modal

3. **Automatic Deduction**
   - Record advance payment for student
   - Click "Pay" and enter fee amount
   - Verify advance was deducted
   - Check remaining balance

4. **Multiple Payments**
   - Test with multiple advance payments
   - Verify amounts accumulate correctly
   - Test partial deductions

## Future Enhancements

- Refund advance payment balance
- Generate advance payment reports
- Batch advance payment processing
- Email notifications for advance payments
- Adjustment reason tracking
- Configurable advance payment policies

## Support

For issues or questions:
1. Check the error messages in SweetAlert modals
2. Review browser console for JavaScript errors
3. Check database logs for SQL errors
4. Verify all files are uploaded correctly

