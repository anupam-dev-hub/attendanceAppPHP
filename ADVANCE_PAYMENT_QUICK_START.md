# Advance Payment System - Quick Start Guide

## Installation (5 minutes)

### Step 1: Update Database
Run this script in your terminal:
```bash
php add_advance_payment_system.php
```

This will:
- Add `advance_payment` column to `students` table
- Create `advance_payments` table
- Create `advance_payment_adjustments` table

### Step 2: Clear Browser Cache
Press `Ctrl+Shift+Delete` (or `Cmd+Shift+Delete` on Mac) to clear browser cache and reload the application.

## Using the System

### Recording an Advance Payment

1. Go to **Manage Students** page
2. Find the student in the list
3. Click the **"Advance"** button (purple color)
4. Enter the advance payment amount
5. Add a description (optional)
6. Click **"Record Advance Payment"**
7. Success! The balance updates immediately

### Viewing Advance Payment Balance

**In the Students List:**
- Look at the **"Advance"** column
- Shows current balance for each student
- Purple color indicates balance > 0

**In Student Details:**
1. Click **"More"** button for any student
2. Go to **"Overview"** tab
3. Look for **"Advance Payment Balance"** in Financial Information section

### Automatic Deduction During Payment

When a student makes a payment:
1. Click **"Pay"** button
2. Enter the payment amount
3. System automatically deducts from advance balance first
4. Only excess amount is added to their regular balance
5. A deduction record is created automatically

**Example:**
- Student has ₹5000 advance payment
- Student needs to pay ₹3000 tuition fee
- After payment: ₹5000 - ₹3000 = ₹2000 remaining advance

## Key Features

### ✅ Automatic Deduction
- Advance payments are automatically deducted when fees are paid
- No manual intervention needed
- Seamless integration with existing payment system

### ✅ Full Audit Trail
- All transactions are recorded
- View history of all advance payments
- Track all deductions

### ✅ Real-time Updates
- Balance updates immediately
- Visible in multiple places (list, modal, etc.)
- No page refresh needed

### ✅ Flexible Amount Entry
- Accept any amount as advance
- Multiple advance payments can accumulate
- Partial deductions supported

## Common Tasks

### Task 1: Record Multiple Advance Payments for a Student
1. Click "Advance" button
2. Enter first amount (e.g., ₹2000)
3. Record it
4. Click "Advance" again
5. Enter second amount (e.g., ₹3000)
6. Record it
7. Total balance: ₹5000

### Task 2: See How Much Was Deducted
1. Click "More" on student
2. Go to Overview tab
3. Scroll to Financial Information
4. See "Advance Payment Balance"
5. Calculate: Previous - Current = Deducted amount

### Task 3: Process Payment with Advance Deduction
1. Click "Pay" button for student with advance payment
2. Enter the fee amount to be paid
3. System shows deduction details in response
4. Check the success message for deduction amount

## Important Notes

⚠️ **Database Backup**
- Always backup your database before running setup script
- Test in development environment first

📋 **Zero Balance**
- Students with no advance payment show ₹0.00
- Advance column will show in gray color

💡 **Partial vs Full Deduction**
- If advance = ₹5000 and fee = ₹3000: Deducts ₹3000, ₹2000 remains
- If advance = ₹5000 and fee = ₹7000: Deducts ₹5000, ₹2000 still owed
- System handles both automatically

🔄 **Multiple Organizations**
- Each organization's data is isolated
- Advance payments work independently per org
- No cross-organization conflicts

## Troubleshooting

### Problem: "Advance" column not showing
**Solution:** Clear browser cache (Ctrl+Shift+Delete) and refresh page

### Problem: Advance payment button not working
**Solution:** 
- Check browser console for errors (F12)
- Verify database tables exist (run setup script again)
- Check if JavaScript is enabled

### Problem: Advance not deducting from payment
**Solution:**
- Verify advance payment was recorded (check in modal)
- Check student's advance balance is > 0
- Ensure payment amount is correct

### Problem: Database error during setup
**Solution:**
- Check MySQL is running
- Verify database connection credentials in config.php
- Manually run SQL queries from ADVANCE_PAYMENT_SYSTEM.md

## API Reference (for developers)

### Record Advance Payment
```
POST org/api/save_advance_payment.php
Parameters: student_id, amount, description
```

### Get Advance Payment Info
```
GET org/api/get_advance_payment.php?student_id=123
Returns: balance, history, adjustments
```

### Save Payment (with auto-deduction)
```
POST org/api/save_payment.php
Parameters: student_id, amount, category, description
Returns: payment_id, advance_deducted, remaining_advance
```

## Files Modified

- ✅ `org/students.php` - Added Advance column and button
- ✅ `org/js/students.js` - Added advance payment functions
- ✅ `org/modals/student_view_modal.php` - Added advance balance display
- ✅ `org/api/save_payment.php` - Updated with auto-deduction logic
- ✅ `org/api/save_advance_payment.php` - NEW
- ✅ `org/api/get_advance_payment.php` - NEW
- ✅ `add_advance_payment_system.php` - NEW (setup script)

## Next Steps

1. ✅ Run the setup script
2. ✅ Refresh your browser
3. ✅ Test recording an advance payment
4. ✅ Test making a payment to see deduction
5. ✅ View student details to confirm balance

---

**Need Help?** Check ADVANCE_PAYMENT_SYSTEM.md for detailed documentation
