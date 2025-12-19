# Fee Management System - Quick Start Guide

## What's New?
Organizations can now:
- Define custom fee types (Monthly Fee, Library Fee, Tuition Fee, etc.)
- Assign multiple fees to each student
- Store fees as JSON format: `{"Monthly Fee": 1000, "Library Fee": 500, ...}`
- View all fees when viewing student details

## Quick Setup (3 Steps)

### Step 1: Access Fee Management Page
1. Log in as Organization
2. Go to **Finance** menu → **Manage Fees**

### Step 2: Create Fee Types
Click "Add New Fee Type" and enter:
- **Fee Name:** e.g., "Monthly Fee", "Library Fee", "Tuition Fee"
- **Fee Type:** Select from dropdown (Monthly Fee, Library Fee, Tuition Fee, Other)
- Click "Add Fee Type"

**Note:** "Monthly Fee" is created by default and cannot be deleted.

### Step 3: Assign Fees to Students
When creating/editing a student:
1. Go to **Financial** section
2. Enter **Admission Amount**
3. Enter amounts for each configured fee:
   - Monthly Fee: 1000
   - Library Fee: 500
   - Tuition Fee: 2000
4. Save Student

## Viewing Fees
1. Go to Students page
2. Click **More** on any student
3. Navigate to **Financial Information**
4. See all fees with individual amounts and total

## Fee Format in Database
Fees are stored as JSON:
```json
{
  "Monthly Fee": 1000.00,
  "Library Fee": 500.00,
  "Tuition Fee": 2000.00
}
```

## Features

### Fee Management Page (Finance → Manage Fees)
- ✅ View all configured fees
- ✅ Add new fee types
- ✅ Delete optional fees
- ✅ See status (Default/Optional)

### Student Form
- ✅ Dynamic fee inputs based on organization's fees
- ✅ All fees displayed in one section
- ✅ Automatic JSON conversion on save

### Student View
- ✅ Display all fees with amounts
- ✅ Calculate total fees
- ✅ Professional formatting with currency symbols

## Default Behavior
- **First Time:** Monthly Fee created automatically (default)
- **Mandatory:** Organization must add Monthly Fee (exists by default)
- **Optional:** Can add Library Fee, Tuition Fee, or custom fees
- **Flexible:** Each student can have different fee combinations

## Example Scenarios

### Scenario 1: School with Multiple Fees
Organization adds:
- Monthly Fee (default)
- Library Fee
- Sports Fee
- Tuition Fee

Each student can have all or some of these fees assigned.

### Scenario 2: Course Institute
Organization adds:
- Monthly Fee (default)
- Course Material Fee
- Certification Fee
- Lab Fee

### Scenario 3: Tuition Center (Simple)
Organization keeps:
- Monthly Fee only (default)

Can add more fees anytime.

## Tips & Best Practices

1. **Plan Your Fees:** Define all fee types before adding students
2. **Naming:** Use clear, consistent fee names
3. **Deletion:** Only delete fees that are not assigned to active students
4. **Updates:** Edit student to change fee amounts anytime
5. **Reporting:** Fees are stored as JSON for easy data export

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Fees not showing in form | Check if fees are configured in Manage Fees page |
| Cannot delete Monthly Fee | It's marked as default. Use another fee as primary if needed |
| Fees showing as "No fees configured" | Go to Finance > Manage Fees and add fee types |
| Old students showing no fees | Manually edit and resave to populate fees_json |

## Technical Details (For Developers)

### Database
- Table: `org_fees` - Stores fee configurations
- Column: `fees_json` in `students` table - Stores student fees

### API Endpoints
```
GET /org/api/manage_fees.php?action=get_fees
POST /org/api/manage_fees.php?action=add_fee
POST /org/api/manage_fees.php?action=delete_fee
POST /org/api/manage_fees.php?action=update_fee
```

### Files Modified/Created
- Created: `org/manage_fees.php` - Fee management page
- Created: `org/api/manage_fees.php` - API endpoints
- Created: `create_fee_system.php` - Database migration
- Modified: `org/modals/student_form_modal.php` - Added dynamic fees
- Modified: `org/modals/student_view_modal.php` - Display fees
- Modified: `org/js/students.js` - Fee handling functions
- Modified: `org/modules/students_logic.php` - Save fees as JSON
- Modified: `setup_db.php` - Database schema
- Modified: `org/navbar.php` - Navigation link

## Support
For issues or questions, refer to:
- `FEE_SYSTEM_DOCUMENTATION.md` - Complete documentation
- Code comments in relevant files
