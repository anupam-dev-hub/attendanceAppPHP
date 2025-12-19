# Payment System - Quick Reference Guide

## How to Record Payments with Pending Payment Display

### Step 1: Open Payment Modal
Navigate to the Students page, find a student, and click the **"Record Payment"** button.

### Step 2: View Pending Payments
When you open the payment form, you'll see:

```
🎓 Student Name: [Disabled]
💰 Current Balance: ₹5,000

📋 Pending Payments (Click to pay)
┌─────────────────────────────────────┐
│ Admission Fee            ₹5,000.00  │
│ 1 item(s)               │
├─────────────────────────────────────┤
│ Monthly Fee              ₹3,500.00  │
│ 3 item(s) - Feb, Jan    │
├─────────────────────────────────────┤
│ Library Fee              ₹500.00    │
│ 1 item(s)               │
└─────────────────────────────────────┘
Total Pending: ₹9,000.00

Amount: [____________]
Category: [-- Select Category --]
Description: [_____________]
```

### Step 3: Quick Pay Pending Items
**Option A: Click a Pending Payment**
- Click any pending payment item (e.g., "Admission Fee")
- Form automatically populates:
  - ✅ Amount: Pre-filled with pending amount
  - ✅ Category: Pre-selected with correct fee type
- The item is highlighted in green to show it's a pending payment
- You can adjust the amount if paying partially
- Click "Record Payment" to submit

**Option B: Manual Entry**
- Leave the pending payments section alone
- Manually enter:
  - Amount: Custom amount
  - Category: Select from dropdown
  - Description: Optional notes
- Click "Record Payment" to submit

### Step 4: Verify Payment
- Payment is recorded in the payment history
- Pending payments list updates automatically
- Paid items are removed from pending list

## Payment Categories

The system supports these fee categories:
- **Admission Fee** - One-time admission payment
- **Monthly Fee** - Regular monthly charges
- **Tuition Fee** - Educational tuition costs
- **Exam Fee** - Examination-related charges
- **Transport Fee** - Transportation services
- **Library Fee** - Library membership/usage
- **Lab Fee** - Laboratory facility charges
- **Other** - Miscellaneous payments

## Understanding Pending Payments

### What Shows as Pending?
Payments are listed as "pending" when:
- They have been initialized/created but not yet received
- Transaction type is "credit" (pending amount owed)
- They appear in the `student_payments` table with `transaction_type = 'credit'`

### How Payments are Grouped
Pending payments are automatically grouped by **fee type**:
- **Same Type:** "Monthly Fee - January 2025" and "Monthly Fee - February 2025" → Group as "Monthly Fee" with total ₹2,000
- **Different Types:** "Admission Fee", "Library Fee" → Show separately

Each group shows:
- **Fee Type Name:** e.g., "Monthly Fee"
- **Total Amount:** Sum of all items in that category
- **Item Count:** How many individual payment entries exist
- **Clickable:** Click to auto-pay that category

## API Endpoint

### Fetch Pending Payments
```
GET /org/api/get_pending_payments.php?student_id=123
```

**Response:**
```json
{
  "success": true,
  "pending_payments": [
    {
      "fee_type": "Monthly Fee",
      "items": [
        {
          "id": 1,
          "amount": 1000,
          "category": "Monthly Fee - January 2025",
          "description": "",
          "date": "Jan 2025"
        }
      ],
      "total_amount": 1000
    }
  ],
  "total_pending": 1000.00,
  "count": 1
}
```

## Workflow Examples

### Example 1: Pay a Single Pending Admission Fee
1. Click "Record Payment" for student
2. See pending payment: "Admission Fee - ₹5,000"
3. Click the Admission Fee item
4. Form shows: Amount=₹5,000, Category="Admission Fee"
5. Click "Record Payment"
6. Done! Payment recorded

### Example 2: Pay Multiple Monthly Fees at Once
1. Click "Record Payment" for student
2. See pending: "Monthly Fee - ₹3,000" (Jan + Feb combined)
3. Click the Monthly Fee item
4. Form shows: Amount=₹3,000, Category="Monthly Fee"
5. Submit payment of ₹3,000
6. Both monthly fees are marked as paid

### Example 3: Partial Payment on Grouped Items
1. Click "Record Payment" for student
2. See pending: "Monthly Fee - ₹2,000" (Jan ₹1,000 + Feb ₹1,000)
3. Click Monthly Fee item → Amount auto-fills as ₹2,000
4. Change amount to ₹1,000 (pay only January)
5. Submit payment of ₹1,000
6. Remaining ₹1,000 still shown as pending

### Example 4: Custom Payment Entry
1. Click "Record Payment" for student
2. See pending payments (for reference only)
3. Ignore pending section
4. Manually enter:
   - Amount: ₹2,500
   - Category: "Admission Fee"
   - Description: "Partial payment"
5. Submit payment

## Key Features

✅ **Organized View** - See all pending amounts grouped by category
✅ **Quick Payment** - One click to populate form with pending amount
✅ **Flexible Amount** - Can adjust amount for partial payments
✅ **Clear Totals** - Shows total pending and item count per category
✅ **Visual Grouping** - Color-coded (yellow/amber) for easy identification
✅ **No Database Changes** - Works with existing payment structure
✅ **Mobile Friendly** - Responsive design works on all devices

## Troubleshooting

### "Pending Payments" section doesn't appear
- Student may have no pending payments (all paid)
- Check if payments have been initialized
- Verify payments are in `student_payments` table with `transaction_type='credit'`

### Amount doesn't auto-populate when clicking pending item
- Check browser console for JavaScript errors
- Verify API endpoint is accessible
- Ensure student ID is valid

### Wrong category selected for grouped items
- Click again - each category has its own grouped item
- Or manually select from dropdown

## Performance Notes
- Pending payments API returns cached results (no real-time calculation)
- Grouping happens server-side for efficiency
- Modal loads within 1-2 seconds typically
- Supports up to 100+ pending items efficiently
