# Monthly Fee Initialization System

## Overview
This system automatically initializes and manages monthly tuition fees for active students in the attendance application. It prevents duplicate fee entries and ensures proper tracking of student payments.

## Features

### 1. Automatic Fee Initialization on Student Activation
- When a student is activated (toggle status from inactive to active), the system automatically initializes their monthly fee for the current month
- Checks for duplicate entries to prevent double billing
- Only initializes if the student has a fee amount greater than ₹0

### 2. Bulk Fee Initialization
- Organization admins can initialize fees for all active students at once
- Select any month/year to initialize fees
- Shows detailed results of the operation
- Automatically skips students who already have fees for that month

### 3. Duplicate Prevention
- The system checks if a fee entry already exists before creating a new one
- Uses the category format: "Monthly Fee - [Month Year]" (e.g., "Monthly Fee - December 2025")
- Prevents accidental double-billing

### 4. Fee Status Tracking
- View fee status for each student across multiple months
- Color-coded status indicators:
  - **Green**: Fee paid
  - **Yellow**: Fee initialized but pending payment
  - **Gray**: Fee not initialized

## Files Created/Modified

### New Files
1. **org/monthly_fee_functions.php** - Core functions for fee management
   - `hasMonthlyFee()` - Check if fee exists
   - `initializeMonthlyFee()` - Initialize a single fee entry
   - `initializeCurrentMonthFee()` - Initialize current month
   - `initializeMultipleMonthlyFees()` - Initialize multiple months
   - `initializeMonthlyFeesForAllActiveStudents()` - Bulk initialization
   - `getMonthlyFeeStatus()` - Get fee status for a student

2. **org/initialize_monthly_fees.php** - Admin interface for fee management
   - Bulk fee initialization form
   - Results display with detailed statistics
   - Active students list with fee status
   - Visual status indicators

3. **org/test_monthly_fees.php** - Testing script
   - Test duplicate detection
   - Test fee status retrieval
   - Test invalid input handling

### Modified Files
1. **org/toggle_student_status.php**
   - Added automatic fee initialization when student is activated
   - Includes monthly_fee_functions.php
   - Enhanced response messages with fee initialization status

2. **org/students.php**
   - Added "Monthly Fees" link to navigation

3. **org/dashboard.php**
   - Added "Monthly Fees" card to dashboard

## Database Schema

The system uses the existing `student_payments` table:

```sql
CREATE TABLE student_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    transaction_type ENUM('credit', 'debit') NOT NULL,
    category VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);
```

### Transaction Types
- **credit**: Money owed by student (fees, charges)
- **debit**: Money paid by student (payments)

### Monthly Fee Entries
- **Category**: "Monthly Fee - [Month Year]"
- **Transaction Type**: credit
- **Amount**: Student's monthly fee amount from `students.fee`
- **Description**: "Monthly tuition fee for [Month Year]"

## Usage Guide

### For Organization Admins

#### Initialize Fees for Current Month (All Active Students)
1. Navigate to "Monthly Fees" from the dashboard or navigation menu
2. Select the current month and year (pre-selected by default)
3. Click "Initialize Fees"
4. Review the results showing success/skip/failure counts

#### Initialize Fees for Future/Past Months
1. Navigate to "Monthly Fees"
2. Select desired month and year
3. Click "Initialize Fees"
4. System will skip students who already have fees for that month

#### Activate Individual Students
1. Go to "Students" page
2. Toggle the status switch for any student
3. System automatically initializes current month fee if:
   - Student is being activated (inactive → active)
   - Student has a fee amount > ₹0
   - Current month fee doesn't already exist

### View Fee Status
1. Navigate to "Monthly Fees" page
2. Scroll to "Active Students" section
3. View last 3 months status for each student
4. Hover over status badges to see detailed amounts

## How It Works

### Fee Initialization Flow
```
1. Check if student is active
2. Check if student has fee amount > 0
3. Check if fee already exists for the month
4. If no duplicate:
   - Create credit entry in student_payments
   - Category: "Monthly Fee - [Month Year]"
   - Amount: Student's monthly fee
5. Return result (success/skip/error)
```

### Duplicate Detection
```php
// Check for existing fee
SELECT id FROM student_payments 
WHERE student_id = ? 
AND category = 'Monthly Fee - December 2025' 
AND transaction_type = 'credit'
```

### Payment Tracking
- Net Balance = Total Debit - Total Credit
- Positive balance = Student has paid more than due
- Negative balance = Student owes money
- Zero balance = Fully paid

## API Functions Reference

### hasMonthlyFee($conn, $student_id, $month, $year)
Returns true if monthly fee exists for the specified month/year.

### initializeMonthlyFee($conn, $student_id, $fee_amount, $month, $year)
Initializes a single monthly fee entry.

**Returns:**
```php
[
    'success' => bool,
    'message' => string
]
```

### initializeCurrentMonthFee($conn, $student_id, $fee_amount)
Convenience function to initialize current month's fee.

### initializeMonthlyFeesForAllActiveStudents($conn, $org_id, $month, $year)
Bulk initialize fees for all active students.

**Returns:**
```php
[
    'total' => int,
    'success' => int,
    'failed' => int,
    'skipped' => int,
    'details' => array
]
```

### getMonthlyFeeStatus($conn, $student_id, $months_back = 6)
Get fee status for last N months.

**Returns array of:**
```php
[
    'month' => int,
    'year' => int,
    'month_name' => string,
    'has_fee' => bool,
    'total_due' => float,
    'total_paid' => float,
    'balance' => float,
    'status' => 'paid'|'pending'|'not_initialized'
]
```

## Testing

Run the test script to verify functionality:
1. Navigate to: `org/test_monthly_fees.php`
2. Review test results for:
   - Duplicate detection
   - Fee status retrieval
   - Invalid input handling

## Best Practices

1. **Initialize fees at the start of each month** for all active students
2. **Don't manually create fee entries** - use the initialization system
3. **Review skipped entries** - they may indicate students who already paid
4. **Monitor fee status regularly** to identify students with pending payments
5. **Keep student fee amounts updated** in the student profile

## Troubleshooting

### Fee not initializing on student activation
- Check if student has fee amount > 0 in their profile
- Verify student is being activated (not deactivated)
- Check if fee already exists for current month

### Duplicate fees created
- This should not happen due to duplicate checking
- If it occurs, contact system administrator
- Check category naming format

### Cannot see fee status
- Ensure student has at least one initialized fee
- Check if months_back parameter is sufficient
- Verify student_payments table has data

## Future Enhancements

- Email notifications for fee initialization
- Auto-initialize fees on 1st of each month (cron job)
- Fee reminder notifications
- Payment deadline tracking
- Late fee calculation
- Bulk SMS for pending fees
- Fee receipt generation
- Multi-currency support

## Support

For issues or questions, refer to the main application documentation or contact the development team.
