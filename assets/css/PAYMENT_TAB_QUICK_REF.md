# Payment Tab - Quick Reference

## 🎨 Color Variables
```css
--payment-primary: #0d9488        /* Teal */
--payment-secondary: #6366f1      /* Indigo */
--payment-success: #10b981        /* Green (Debit) */
--payment-danger: #ef4444         /* Red (Credit) */
```

## 📝 Common Classes

### Layout
- `.payment-gradient` - Main container with soft gradient
- `.payment-summary-grid` - Responsive 3-column grid
- `.payment-table-wrapper` - Table container with border & shadow

### Components
- `.payment-summary-card` - Summary stat card with hover animation
- `.payment-record-btn-secondary` - Modern gradient button
- `.secure-badge` - Green security pill badge
- `.filter-select` - Styled dropdown with focus ring

### Typography
- `.payments-heading` - Main section heading (1.25rem, bold)
- `.payment-summary-label` - Card label (0.7rem, uppercase)
- `.payment-summary-value` - Large number (1.25rem, bold)

### Amounts
- `.amount-debit` - Green positive amount (+)
- `.amount-credit` - Red negative amount (-)

## 🔧 JavaScript Functions

### Display Payment History
```javascript
viewStudent(student)  // Opens modal & loads payment data
renderPaymentRows(payments)  // Populates table with styled rows
```

### Filter Payments
```javascript
$('#paymentTypeFilter').onchange()  // Filters by debit/credit
```

### DataTable Setup
```javascript
$('#paymentHistoryTable').DataTable({
    responsive: true,
    pageLength: 10,
    order: [[0, 'desc']]
})
```

## 📱 Responsive Breakpoints
- **≤768px**: Single column, stacked filters, full-width buttons
- **≤480px**: Horizontal scroll tables, smaller badges

## ♿ Accessibility
- Use `aria-label` for icon buttons
- Add `aria-live="polite"` for dynamic content
- Ensure `.payment-focus-ring` class on interactive elements
- Minimum color contrast: 4.5:1 for text

## 🚀 Quick Start

1. **Link CSS**:
   ```html
   <link rel="stylesheet" href="../assets/css/payment_tab.css">
   ```

2. **HTML Structure**:
   ```html
   <div class="payment-gradient">
     <div class="payment-summary-grid">
       <!-- Summary cards -->
     </div>
     <div class="payment-table-wrapper">
       <table id="paymentHistoryTable">...</table>
     </div>
   </div>
   ```

3. **Initialize DataTable**:
   ```javascript
   ensurePaymentDataTable();
   renderPaymentRows(paymentsData);
   ```

## 🎯 Common Tasks

### Change Primary Color
Update `--payment-primary` in `:root` selector

### Add New Summary Card
Copy `.payment-summary-card` structure, auto-fits to grid

### Modify Table Columns
Edit `columnDefs` in DataTable config + update `renderPaymentRows()`

### Adjust Mobile Layout
Modify `@media (max-width: 768px)` section

## 📚 Full Documentation
See `PAYMENT_TAB_DESIGN_GUIDE.md` for complete design system details.
