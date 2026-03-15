# Phase 1 Procurement Module - Deployment Checklist

**Status**: ✅ **READY FOR TESTING**  
**Date**: March 10, 2026  
**Version**: 1.0  

---

## 📋 Phase 1 Implementation Summary

All Phase 1 "Must-Have" features for the Procurement Module have been **implemented and code-complete**. This checklist guides you through final deployment, testing, and verification steps.

### Expected Results:
✅ PO creation time reduced from 5 min → **< 2 minutes**  
✅ One-click approval workflows  
✅ Professional PDF generation with letterhead  
✅ Barcode-based goods receipt  
✅ Smart discrepancy detection  

---

## 🔧 Backend Setup Steps

### Step 1: Install Required PHP Packages
```bash
cd backend

# Install PDF generation library
composer require barryvdh/laravel-dompdf

# If not already installed, add email dependencies
composer require swiftmailer/swiftmailer

# Clear configuration caches
php artisan config:cache
php artisan route:cache
```

### Step 2: Configure Environment (.env)
```env
# Email Configuration - SMTP Settings
MAIL_DRIVER=smtp
MAIL_HOST=smtp.gmail.com                    # Change to your provider
MAIL_PORT=587
MAIL_USERNAME=your-email@company.com        # Your email
MAIL_PASSWORD=your-app-specific-password    # App password (not regular password)
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@furniturestores.com
MAIL_FROM_NAME="Furniture Stores Platform"

# Optional: DomPDF Configuration
DOMPDF_REMOTE_ENABLED=true                  # Allow loading remote images in PDFs
```

### Step 3: Publish DomPDF Configuration
```bash
php artisan vendor:publish --vendor barryvdh/laravel-dompdf
```

### Step 4: Verify Routes Added
Routes have been added to `routes/procurement_routes.php`. Verify they include:
- `GET /api/procurement/purchase-orders/{id}/print` → PDF generation
- `POST /api/procurement/purchase-orders/{id}/email` → Email with PDF
- `GET /api/procurement/purchase-orders/{id}/label` → Warehouse label
- `GET /api/procurement/purchase-orders/approved` → For GR dropdown
- `GET /api/procurement/products/history` → Frequently purchased products
- `GET /api/procurement/suppliers/{id}/delivery-history` → Supplier metrics
- `POST /api/procurement/purchase-orders/{id}/request-revision` → Revision workflow

---

## 🎨 Frontend Setup Steps

### Step 1: Install Dependencies
```bash
cd frontend

# Verify all PrimeVue v4 packages are installed
npm install

# If running for first time
npm install
```

### Step 2: Build Frontend
```bash
# Build for production
npm run build

# Or run in development mode (with hot reload)
npm run dev
```

### Step 3: Verify Build Output
- Check that `dist/` folder is created
- No procurement-related TypeScript errors should appear
- Path aliases in `vite.config.ts` and `tsconfig.app.json` are properly configured

---

## 📁 Files Created/Modified

### Frontend - New Files Created:
✅ `frontend/src/composables/procurement/usePoAutomation.ts` (400+ lines)
- 11 utility functions for smart defaults, calculations, validations
- Auto-population, PO number generation, delivery date calculation
- Budget warnings, supplier status checks, product history

✅ `frontend/src/utils/pdfGenerator.ts` (800+ lines)
- 3 HTML template generators (PO, Goods Receipt, Warehouse Label)
- Professional styling with company letterhead
- Export to PDF functions

✅ `frontend/src/views/system/procurement/PurchaseOrders/Create.vue` (650+ lines)
- 6-section form: Basic Info, Supplier, Line Items, Totals, Notes, Actions
- Auto-population on supplier selection
- Real-time total calculation
- Quick add frequently purchased products
- Form validation and error handling

✅ `frontend/src/views/system/procurement/PurchaseOrders/Detail.vue` (700+ lines)
- Approval timeline visualization (4-step workflow)
- Approval action modals (Approve, Request Revision, Reject, Email)
- Print PO and Print Label buttons
- Email to supplier functionality
- Financial summary cards
- Budget exceeded warnings

✅ `frontend/src/views/system/procurement/GoodsReceipts/Create.vue` (500+ lines)
- PO selection dropdown (approved only)
- Barcode scanner with product search
- Quick add buttons for each PO line
- Editable received items table with variance calculation
- Discrepancy alert banner
- Completion percentage tracking

### Frontend - Modified Files:
✅ `frontend/src/services/procurement.service.ts` (10+ new methods)
- `generatePOPdf(id)` - PDF blob response
- `emailPurchaseOrder(id, data)` - Email with PDF attachment
- `generateGRPdf(id)` - Goods receipt PDF
- `generatePOLabel(id)` - Warehouse label PDF  
- `getProductHistory(params)` - Frequently purchased products
- `getSupplierDeliveryHistory(supplierId)` - Supplier performance metrics
- `getBranchBudget(branchId)` - Branch budget checking
- `getAlternativeSuppliers(productId, params)` - Alternative supplier suggestions
- `getApprovedPurchaseOrders(params)` - For goods receipt dropdown
- `requestRevision(id, data)` - Revision workflow
- `getAlternativeSuppliers(productId, params)` - Alternative suppliers (Phase 2)
- `getBranchBudget(branchId)` - Budget checks

### Backend - New Files Created:
✅ `backend/app/Http/Controllers/Api/Procurement/PurchaseOrder/PurchaseOrderPrintEmailController.php` (350+ lines)
- 7 core methods for print, email, labels, and automation
- `generatePdf(id)` - Creates PO PDF with professional formatting
- `emailPo(Request, id)` - Sends PDF via email with SMTP
- `generateLabel(id)` - A6 warehouse label generation
- `getProductHistory()` - Groups by product, returns top N by frequency
- `getSupplierDeliveryHistory(supplierId)` - Calculates metrics (on-time %, delivery days)
- `getApprovedOrders()` - Returns approved POs for GR selection
- `requestRevision(id, data)` - Creates revision record
- Additional helper methods for budget and alternative suppliers

### Backend - Modified Files:
✅ `backend/routes/procurement_routes.php` (All new routes added)
- Import statement for PurchaseOrderPrintEmailController
- New routes for print, email, labels, products, branches
- All endpoints properly parameterized

### Configuration:
✅ `frontend/vite.config.ts` - Added path alias configuration
✅ `frontend/tsconfig.app.json` - Added path alias configuration

---

## ✅ Testing Checklist

### Unit Tests for PO Creation (Create.vue)
- [ ] Form loads with auto-generated PO number
- [ ] Today's date and +7 day delivery date are pre-filled
- [ ] Selecting a supplier auto-fills: contact person, email, phone, address
- [ ] Selecting a product auto-fills unit cost from history
- [ ] Running total updates in real-time as items are added
- [ ] Budget warning appears when total exceeds limit
- [ ] Quick add buttons add products with one click
- [ ] "Save as Draft" saves without submission
- [ ] "Create & Submit" validates all required fields
- [ ] Form prevents submission with missing items or branch

### Unit Tests for PO Approval (Detail.vue)
- [ ] Timeline shows all 4 approval steps
- [ ] Current status message displays correctly
- [ ] Financial summary cards calculate correctly
- [ ] Red warning banner appears if budget exceeded
- [ ] "PRINT PO" button generates professional PDF
- [ ] "EMAIL TO SUPPLIER" opens modal with recipient field
- [ ] "APPROVE" button opens comments dialog
- [ ] "REQUEST REVISION" requires comments before sending
- [ ] "REJECT" requires reason before confirming
- [ ] Approval actions update PO status

### Unit Tests for Goods Receipt (GoodsReceipts/Create.vue)
- [ ] Dropdown shows only approved POs
- [ ] Selecting PO auto-populates expected items
- [ ] Barcode scanner finds products by SKU
- [ ] Quick add buttons instantly add items
- [ ] Variance calculation shows received - ordered
- [ ] Rows color-code: red=damaged, yellow=short, blue=wrong
- [ ] Completion percentage calculates correctly
- [ ] Discrepancy banner alerts when items don't match
- [ ] "Complete Receipt" validates before saving

### Integration Tests
- [ ] Backend API endpoints respond with correct data
- [ ] PDF generation creates valid PDF file
- [ ] Email sends with PDF attachment
- [ ] Supplier metrics calculate from purchase history
- [ ] Product history returns most purchased items
- [ ] Date formatting is consistent (yy-mm-dd)
- [ ] Currency displays as PHP throughout
- [ ] Error toasts appear on validation failures

### Performance Tests
- [ ] Form loads within 2 seconds
- [ ] PO list loads 100+ records smoothly
- [ ] PDF generation takes <3 seconds
- [ ] Email sending doesn't block UI (async)
- [ ] Barcode lookup is instant (<100ms)

### Browser Compatibility
- [ ] Chrome/Chromium ≥ v90
- [ ] Firefox ≥ v88  
- [ ] Safari ≥ v14
- [ ] Edge ≥ v90
- [ ] Mobile responsive (1, 2, 4 column layouts)

---

## 🚀 Deployment Steps

### Step 1: Backend Deployment
```bash
cd backend

# 1. Install dependencies
composer install --optimize-autoloader

# 2. Clear all caches
php artisan cache:clear
php artisan config:cache
php artisan route:cache

# 3. Publish assets if needed
php artisan vendor:publish --forced

# 4. Run migrations (if schema changes)
php artisan migrate --force

# 5. Restart queue jobs (if using queues)
php artisan queue:restart
```

### Step 2: Frontend Deployment
```bash
cd frontend

# 1. Install dependencies (fresh)
npm ci  # or npm install if first time

# 2. Build for production
npm run build

# 3. Verify build output
ls -la dist/

# 4. Deploy dist/ contents to web server (usually /public/app)
```

### Step 3: Verify Deployment
1. **Backend Health Check**:
   ```bash
   curl http://your-backend.com/api/health
   ```

2. **Frontend Load Check**:
   - Open http://your-app.com/procurement/purchase-orders
   - Verify page loads without console errors

3. **API Connectivity Check**:
   - Check browser console (F12) for network errors
   - Verify all API calls return 200 status

4. **PDF Generation Test**:
   - Create a test PO
   - Click "PRINT PO" button
   - Verify PDF downloads with company letterhead

---

## 📊 Monitoring & Support

### Key Metrics to Monitor
- **Form completion time**: Target < 2 minutes
- **PDF generation time**: Target < 3 seconds
- **Email delivery rate**: Target 99%+
- **GR barcode accuracy**: Target 99%+ match rate
- **User error rate**: Track validation failures for patterns

### Common Issues & Solutions

**Issue**: "Cannot find module @/services"
```
Solution: Use relative paths (../../services) instead of @ alias
File: Check vite.config.ts and tsconfig.app.json for alias configuration
```

**Issue**: "Email not sending"
```
Solution: 
1. Verify .env MAIL_* settings are correct
2. Test with php artisan tinker: Mail::raw('Test', fn($m) => $m->to('test@email.com'));
3. Check server firewall allows SMTP port 587
4. Enable "Less secure app access" if using Gmail
```

**Issue**: "PDF generation timeout"
```
Solution:
1. Increase script timeout in php.ini: max_execution_time = 60
2. Check DomPDF cache permissions: chmod 777 storage/app/dompdf  
3. Simplify PDF template if too complex
```

**Issue**: "Barcode not scanning"
```
Solution:
1. Ensure barcode format matches SKU in database
2. Test with text input: type SKU manually
3. Check scanner encoding (USB vs Bluetooth)
4. Verify product exists in PO items
```

---

## 📈 Next Steps (Phase 2)

These features are ready for Phase 2 implementation:

1. **Alternative Supplier Suggestions** (partially implemented)
   - Show cheaper alternatives during PO creation
   - API endpoint ready: `getAlternativeSuppliers(productId)`

2. **Damage Photo Uploads**
   - Add file upload to GR discrepancy items
   - Store in storage/goods-receipts/damage-photos/

3. **Supplier Communication Auto-Draft**
   - Generate templated messages for revisions
   - Auto-populate with PO details

4. **Advanced Analytics Dashboard**
   - Supplier performance trends
   - Purchase spend analysis
   - Delivery timeliness charts

5. **Previous PO Comparison**
   - Show last 3 POs from same supplier
   - Compare prices and quantities

---

## 📞 Support & Questions

**Technical Issues**: Review PROCUREMENT_ENHANCEMENT_GUIDE.md for detailed technical documentation

**User Training**: Start with PROCUREMENT_QUICK_REFERENCE.md for user-facing guide

**Component Code**: Review comments in each Vue component for implementation details

---

## ✅ Sign-Off Checklist

Before marking Phase 1 as ready for production:

- [ ] All backend routes tested and responding
- [ ] All frontend components load without console errors
- [ ] PDF generation working with sample data
- [ ] Email sending verified (check spam folder)
- [ ] Database has test data: suppliers, products, branches
- [ ] User roles and permissions configured
- [ ] Date formatting consistent across app
- [ ] Currency displays correctly (PHP)
- [ ] Mobile responsiveness verified
- [ ] Accessibility tested (keyboard navigation, screen readers)
- [ ] Performance benchmarks met (load times, responsiveness)
- [ ] Error messages are clear and actionable
- [ ] Toast notifications working properly
- [ ] Form validation prevents invalid submissions
- [ ] All API responses include proper error handling

---

**Status**: ✅ **Phase 1 Complete & Ready**  
**Last Updated**: March 10, 2026  
**Next Review**: After Phase 1 testing completion  
