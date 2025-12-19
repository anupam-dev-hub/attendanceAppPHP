# Fee Management System Documentation

## Overview
A comprehensive fee management system that allows organizations to define custom fee types (Monthly Fee, Library Fee, Tuition Fee, etc.) and assign them to students as JSON data.

## Database Schema

### Tables Created/Modified

#### 1. **org_fees** (New Table)
Stores fee types/configurations per organization
```
- id: Primary Key
- org_id: Foreign Key to organizations
- fee_name: VARCHAR(100) - Name of the fee (e.g., "Monthly Fee", "Library Fee")
- fee_type: ENUM - Type classification
- is_default: BOOLEAN - Marks Monthly Fee as default
- created_at, updated_at: Timestamps
- UNIQUE: (org_id, fee_name) - Prevent duplicate fee names per org
```

#### 2. **students** (Modified)
Added new column to store fees as JSON:
```
- fees_json: JSON - Stores fees as JSON object
  Example: {"Monthly Fee": 1000, "Library Fee": 500, "Tuition Fee": 2000}
```

## Features

### 1. Fee Configuration Page
**File:** `org/manage_fees.php`

**Functionality:**
- View all configured fees for the organization
- Add new fee types with custom names
- Delete optional fee types (Monthly Fee marked as default, cannot be deleted)
- Display fee type and status (Default/Optional)

**Navbar Integration:**
- Added link under Finance dropdown menu
- Mobile responsive navigation

### 2. Student Form - Dynamic Fees
**File:** `org/modals/student_form_modal.php`

**Features:**
- Dynamically loaded fee inputs based on organization's configured fees
- Shows all fee types with input fields
- Supports multiple fee amounts
- Fees are displayed only in Financial section (admission amount + all fees)

### 3. API Endpoints
**File:** `org/api/manage_fees.php`

**Available Actions:**
```
GET api/manage_fees.php?action=get_fees
- Returns all fees for current organization

POST api/manage_fees.php?action=add_fee
- Body: {fee_name, fee_type}
- Adds new fee type

POST api/manage_fees.php?action=delete_fee
- Body: {fee_id}
- Deletes a fee type (with permission checks)

POST api/manage_fees.php?action=update_fee
- Body: {fee_id, fee_name, fee_type}
- Updates fee details
```

## JavaScript Functions

### Load Fees
```javascript
loadOrgFees()
```
Fetches all fees from the API and dynamically creates input fields in the form

### Collect Fee Data
```javascript
collectFeeData()
```
Gathers all fee values from form inputs and returns as JSON string
- Example output: `{"Monthly Fee": "1000", "Library Fee": "500"}`

### Populate Fees in Modal
```javascript
populateFeesInModal(feesJson)
```
When editing a student, populates fee fields with previously saved values

## Backend Processing

### students_logic.php Updates
1. **Fee Data Collection:** Processes fees from form submission
2. **JSON Conversion:** Converts fee array to JSON for database storage
3. **INSERT Statement:** Added fees_json to insert query
4. **UPDATE Statement:** Added fees_json to update query (both with and without photo)
5. **Bind Parameters:** Updated to include fees_json as string (s)

### Database Operations
- Fees are stored as JSON in `fees_json` column
- Original `fee` column maintained for backward compatibility
- Payment records created based on admission amount (not individual fees)

## Student View Modal

### Fee Display
**File:** `org/modals/student_view_modal.php`

- Shows all fees as a formatted list
- Displays individual fee amounts
- Shows total fees calculation
- Format: "Fee Name: ₹Amount" with total

**Example Display:**
```
Fees
Monthly Fee: ₹1000.00
Library Fee: ₹500.00
Tuition Fee: ₹2000.00
Total Fees: ₹3500.00
```

## Database Migration

**File:** `create_fee_system.php`

**Executed Actions:**
- Creates `org_fees` table
- Adds `fees_json` column to students table
- Establishes relationships and constraints

**Run:** `php create_fee_system.php`

## Installation

### For Fresh Installs
Use `setup_db.php` - includes complete fee system:
- org_fees table definition
- fees_json column in students table

### For Existing Installations
Run the migration:
```bash
php create_fee_system.php
```

## Workflow

### Admin/Organization Setup
1. Navigate to Finance > Manage Fees
2. Create fee types:
   - Monthly Fee (created by default)
   - Library Fee
   - Tuition Fee
   - Custom fees as needed

### Student Creation/Editing
1. Click "Add New Student" or Edit existing student
2. Fill in basic and personal information
3. In Financial section:
   - Enter Admission Amount
   - Enter amounts for each configured fee
4. Save student
5. Fees stored as JSON: `{"Monthly Fee": 1000, "Library Fee": 500, ...}`

### Student View
1. Click "More" to view student details
2. Navigate to Financial Information section
3. See all fees with:
   - Individual amounts
   - Total fees calculation

## Data Format

### JSON Structure
```json
{
  "Monthly Fee": 1000.00,
  "Library Fee": 500.00,
  "Tuition Fee": 2000.00
}
```

### Database Storage
- Type: JSON
- Example Query: `SELECT JSON_EXTRACT(fees_json, '$.Monthly Fee') FROM students`
- Queryable: Can filter by specific fees using JSON functions

## Backward Compatibility
- Original `fee` column retained
- New `fees_json` column for comprehensive fee storage
- Can migrate old data from single `fee` to `fees_json` if needed
- Supports both single and multiple fees

## Future Enhancements
1. Auto-initialize Monthly Fee for new organizations
2. Fee templates/presets for organizations
3. Payment collection against individual fees
4. Fee reports and analytics
5. Recurring fee schedules
6. Fee discount/adjustment system

## Troubleshooting

### Fees not showing in form
- Check if organization has fees configured in `manage_fees.php`
- Verify API is returning fees correctly
- Check browser console for JavaScript errors

### Fees not saving
- Ensure `fees_json` column exists in students table
- Check bind parameter count in prepared statements
- Verify fees are being collected by `collectFeeData()` function

### Payment Integration
- Current system: Fees stored as JSON for reference
- Payments created based on `admission_amount` field
- Future: Extend payment system to track individual fee payments
