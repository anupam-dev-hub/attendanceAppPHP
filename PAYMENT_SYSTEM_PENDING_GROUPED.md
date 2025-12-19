# Payment System Enhancement - Pending Payments by Category

## Overview
Modified the payment recording system to show pending payments grouped by fee category/type, allowing users to see what payments are pending and quickly pay them.

## Changes Made

### 1. New API Endpoint: `org/api/get_pending_payments.php`
**Purpose:** Fetch pending payments for a student, grouped by fee category

**Features:**
- Retrieves all `credit` (pending) transactions from `student_payments` table
- Groups payments by fee type (extracts prefix before " - ")
- Returns:
  - Grouped pending payments by category
  - Total pending amount for each category
  - Individual payment items within each category
  - Date information for each payment

**Response Example:**
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
    },
    {
      "fee_type": "Library Fee",
      "items": [
        {
          "id": 2,
          "amount": 500,
          "category": "Library Fee - February 2025",
          "description": "",
          "date": "Feb 2025"
        }
      ],
      "total_amount": 500
    }
  ],
  "total_pending": 1500.00,
  "count": 2
}
```

### 2. Updated `org/modals/payment_modal.php`
**Changes:**
- Increased max-width from `max-w-lg` to `max-w-2xl`
- Added `max-h-[90vh] overflow-y-auto` for scrolling support
- Added pending payments section (initially hidden)
- Added "Pending Payments by Category" section with amber styling
- Added default empty option to category dropdown: `-- Select Category --`
- Added new categories: `Lab Fee`

**Visual Structure:**
1. Student name field
2. **NEW:** Pending Payments section (shows when there are pending payments)
   - Icon and title
   - Clickable pending payment items grouped by category
   - Shows total pending amount and item count per category
3. Amount field
4. Category dropdown
5. Description field

### 3. Updated `org/js/students.js`

#### Enhanced `openPaymentModal()` Function
**Changes:**
- Now fetches both student balance AND pending payments using `Promise.all()`
- Calls new API endpoint: `api/get_pending_payments.php`
- Builds HTML for pending payments section with:
  - Fee type grouped view
  - Click handler to quickly populate form
  - Total amount display in red
- Includes pending payments section in SweetAlert modal when payments exist
- Added `Lab Fee` to category options
- Made category dropdown optional with empty default

**New Features:**
- Displays pending payments in yellow-tinted box
- Each pending item is clickable
- Clicking a pending payment calls `quickPayPending()` function
- Shows item count and total amount for each fee type

#### New `quickPayPending()` Function
**Purpose:** Pre-populate payment form with pending payment details

**Functionality:**
1. Takes parameters: `studentId`, `feeType`, `amount`
2. Closes current alert/modal
3. Fetches fresh student data
4. Opens new SweetAlert with:
   - Pre-filled amount (from pending payment)
   - Pre-selected category (matching fee type)
   - Category dropdown with selected fee type at top
   - Green highlighting to show this is a pending payment
5. Allows user to modify amount if needed
6. Submits payment on confirmation

## User Experience Flow

### Recording a Payment
1. **Old Flow:** Click "Record Payment" → Open form → Manual entry
2. **New Flow:** Click "Record Payment" → See:
   - Pending payments grouped by type (if any)
   - Can click any pending payment to auto-populate amount and category
   - Or manually enter custom payment

### Example Scenario
**Student has pending payments:**
- Admission Fee: ₹5,000 (1 item)
- Monthly Fee: ₹2,000 (2 items - Jan & Feb)
- Library Fee: ₹500 (1 item)

**User sees:**
```
Student Name: [John Doe]

📋 Pending Payments (Click to pay)
┌─────────────────────────────────────┐
│ Admission Fee            ₹5,000.00  │ ← Click to pay
│ 1 item(s)               │
├─────────────────────────────────────┤
│ Monthly Fee              ₹2,000.00  │ ← Click to pay
│ 2 item(s)               │
├─────────────────────────────────────┤
│ Library Fee              ₹500.00    │ ← Click to pay
│ 1 item(s)               │
├─────────────────────────────────────┤
│ Total Pending: ₹7,500.00           │
└─────────────────────────────────────┘

Amount: [    ]
Category: [Select Category]
Description: [    ]
```

## Database Schema
No changes to database schema required. Uses existing:
- `student_payments` table
- Columns: `id`, `student_id`, `amount`, `transaction_type`, `category`, `description`, `created_at`

## Categories Supported
- Admission Fee
- Monthly Fee
- Tuition Fee
- Exam Fee
- Transport Fee
- Library Fee
- Lab Fee
- Other

## Technical Details

### Payment Grouping Logic
Extracts fee type by splitting on " - ":
- "Monthly Fee - January 2025" → "Monthly Fee"
- "Library Fee - February 2025" → "Library Fee"
- "Admission Fee" → "Admission Fee" (no " - " present)

### Sorting
Pending payments grouped by category are sorted by total amount (descending) - highest pending amount shows first.

### Security
- Uses session validation via `isOrg()` function
- Verifies student belongs to organization
- Uses JSON response format
- Handles authorization properly

## Files Modified
1. ✅ Created: `org/api/get_pending_payments.php` (NEW)
2. ✅ Modified: `org/modals/payment_modal.php`
3. ✅ Modified: `org/js/students.js` (added pending payments display & quickPayPending function)

## Testing Checklist
- [ ] Navigate to Students page as Organization
- [ ] Click on a student with pending payments
- [ ] Click "Record Payment" button
- [ ] Verify pending payments section appears with correct grouping
- [ ] Click on a pending payment item
- [ ] Verify form is pre-populated with:
  - Correct amount from pending payment
  - Correct category/fee type
- [ ] Modify amount if desired
- [ ] Submit payment
- [ ] Verify payment is recorded
- [ ] Verify pending payments list updates (removed paid items)

## Benefits
✅ **Clear Payment Status:** Users see exactly what payments are pending
✅ **Organized by Category:** Pending payments grouped by fee type
✅ **Quick Payment:** Click to auto-populate form with pending amount
✅ **Flexible:** Can still manually enter custom amounts
✅ **Visual Feedback:** Amber/yellow highlighting for pending items
✅ **No Database Changes:** Works with existing schema

## Future Enhancements
- Allow partial payments on grouped pending items
- Bulk payment recording (pay multiple items at once)
- Payment history per category
- Recurring payment templates
- Auto-payment reminders
