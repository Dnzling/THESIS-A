# ✅ Frontend Implementation - Verification Checklist

## 🔍 Quick Verification (5 minutes)

### Code Review
- [ ] **File Exists:** `frontend/src/views/system/procurement/PurchaseOrders/CreateNew.vue`
  - Command: `ls -la frontend/src/views/system/procurement/PurchaseOrders/CreateNew.vue`
  - Expected: File exists, ~700 lines

- [ ] **Service Methods:** `frontend/src/services/procurement.service.ts` has 8 new methods
  - Search for: `// ==================== STOCK ORDER REQUESTS ====================`
  - Should find: 8 new async methods

- [ ] **Routes Updated:** `frontend/src/router/index.ts`
  - Search for: `CreateNew.vue`
  - Expected: Default create route points to CreateNew
  - Also: `create-legacy` route exists for fallback

- [ ] **Backend Routes Fixed:** `backend/routes/procurement_routes.php`
  - Check: Named routes (pending, summary) before wildcard {id}
  - Order should be: index, post, bulk, pending, summary, {id}, approve, reject

### Syntax Check
```bash
# From frontend directory
npm run build

# Should complete WITHOUT errors
# Warning about unused imports is OK
```

---

## 🧪 Step-by-Step Testing (20 minutes)

### Step 1: Prepare Environment
```bash
# Terminal 1 - Backend
cd backend
php artisan serve --port=8000

# Terminal 2 - Frontend  
cd frontend
npm run dev

# Terminal 3 - For running commands (if needed)
```

### Step 2: Test Stock Request Loading
```
1. Navigate to: http://localhost:5173/procurement/purchase-orders/create
2. Expected: See 3-step wizard interface
3. Step 1 header: "Select Approved Stock Order Requests"
4. Check: "Total Available: X" counter shows (should be >0)
5. Expected: DataTable with stock requests appears
```

**If issues:**
- Check browser console (F12) for errors
- Verify backend is running: `php artisan serve`
- Check service method call in network tab

### Step 3: Test Supplier Selection
```
1. In Step 1, select at least 1 stock request (checkbox)
2. Click "Next: Choose Supplier" button
3. Expected: Step 2 loads with items summary
4. Check: Items from requests shown in blue card
5. Select a supplier from dropdown
6. Expected: Supplier details auto-populate below
```

**If issues:**
- Verify suppliers exist in database
- Check getSuppliers() API response
- Look for loading spinner animation

### Step 4: Test Payment Terms Entry
```
1. After selecting supplier, click "Next: Enter Terms"
2. Expected: Step 3 loads with items table
3. Items shown as read-only (greyed out)
4. Select payment terms from dropdown
5. Enter shipping cost (e.g., 500)
6. Enter discount (e.g., 0)
7. Expected: Totals update immediately
   - Subtotal: calculated automatically
   - Charges: shipping - discount
   - Total: subtotal + charges
```

**If issues:**
- Check browser console for calculation errors
- Verify InputNumber components are working
- Check computed properties are updating

### Step 5: Test Form Submission
```
1. Click "Create Purchase Order" button
2. Expected: Loading spinner appears
3. Wait for API response...
4. Expected: Success toast message appears
5. Expected: Redirect to /procurement/purchase-orders list
6. Check: New PO appears in list
7. Check: "Stock Request" badge (blue) appears in Source column
```

**If issues:**
- Check Network tab: POST to /api/procurement/purchase-orders
- Verify payload contains: stock_order_request_ids, supplier_id, payment_terms
- Check backend logs for errors: `php artisan logs`

---

## 🐛 Common Issues & Fixes

### Issue 1: "Cannot GET /procurement/purchase-orders/create"
**Solution:**
- Verify routes file was updated (CreateNew.vue)
- Restart frontend dev server: `npm run dev`
- Clear browser cache: Ctrl+Shift+Delete

### Issue 2: "No stock requests available"
**Solution:**
- Verify stock order requests exist in database
  ```bash
  php artisan tinker
  > \App\Models\Procurement\StockOrder\StockOrderRequest::where('status', 'approved')->count()
  ```
- Create test data if needed:
  ```bash
  php artisan tinker
  > \App\Models\Procurement\StockOrder\StockOrderRequest::create([...])
  ```

### Issue 3: "Service method not found" (500 error)
**Solution:**
- Verify service methods exist in `procurement.service.ts`
- Check method names match exactly
- Verify imports in component: `import procurementService from '@/services/procurement.service'`

### Issue 4: Stock requests load but can't select (checkbox not working)
**Solution:**
- Make sure you're using DataTable with proper setup
- Verify v-model:selection is working
- Check browser console for Vue warnings

### Issue 5: Suppliers not loading in Step 2
**Solution:**
- Verify getSuppliers() endpoint returns data
- Check API response in Network tab
- Ensure suppliers exist in database

### Issue 6: Totals not calculating after entering shipping cost
**Solution:**
- Check browser console for JavaScript errors
- Verify computed properties are defined
- Try refreshing the page
- Check if InputNumber component is properly v-model bound

---

## 🔧 Database Verification

### Check Stock Order Requests Table
```bash
php artisan tinker

# Count total requests
> \App\Models\Procurement\StockOrder\StockOrderRequest::count()

# Count approved requests
> \App\Models\Procurement\StockOrder\StockOrderRequest::where('status', 'approved')->count()

# Check relationships
> $req = \App\Models\Procurement\StockOrder\StockOrderRequest::first()
> $req->product # Should show product
> $req->branch # Should show branch
```

### Check Purchase Orders Table
```bash
# Verify FK column exists
> \DB::table('purchase_orders')->first()
# Look for: stock_order_request_id column

# Check PO from stock requests
> \App\Models\Procurement\PurchaseOrder::where('stock_order_request_id', '!=', null)->count()
```

---

## 📡 API Testing (Postman/cURL)

### Test 1: Get Pending Stock Requests
```bash
curl -X GET "http://localhost:8000/api/procurement/stock-order-requests/pending/for-conversion" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"

# Expected Response: 200 OK with array of requests
```

### Test 2: Create PO from Stock Requests
```bash
curl -X POST "http://localhost:8000/api/procurement/purchase-orders" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "stock_order_request_ids": [1, 2, 3],
    "supplier_id": 5,
    "payment_terms": "net_30",
    "shipping_cost": 500,
    "discount_amount": 0,
    "notes": "Test order"
  }'

# Expected Response: 201 Created with PO data
```

---

## 📋 Production Readiness Checklist

### Code Quality
- [ ] No console errors when running Step 1-3
- [ ] No "undefined" values displayed
- [ ] All loading spinners appear and disappear correctly
- [ ] No 404 or 500 errors in Network tab

### User Experience
- [ ] Can't proceed from Step 1 without selecting requests
- [ ] Can go back from Step 2 to Step 1 (Back button works)
- [ ] Can go back from Step 3 to Step 2 (Back button works)
- [ ] Error messages are clear and helpful
- [ ] Success message appears after submission

### Data Integrity
- [ ] Selected stock requests match those shown at checkout
- [ ] Supplier details match database
- [ ] Totals calculated correctly (manual verification)
- [ ] Created PO has correct items and amounts
- [ ] Stock requests marked as "converted_to_po" in database

### Performance
- [ ] Stock requests load within 2 seconds
- [ ] Suppliers load within 1 second
- [ ] Form submission responds within 3 seconds
- [ ] No lag when entering values
- [ ] Smooth transitions between steps

### Browser Compatibility
- [ ] Works in Chrome/Chromium
- [ ] Works in Firefox
- [ ] Works in Safari (if available)
- [ ] Responsive on tablet size (768px)
- [ ] Responsive on mobile size (375px)

---

## 📝 Documentation Status

Files Created:
- [ ] `PROCUREMENT_FRONTEND_REFACTOR_COMPLETE.md` - Component guide
- [ ] `PROCUREMENT_INTEGRATION_COMPLETE.md` - Integration guide
- [ ] `SESSION_SUMMARY_FRONTEND_IMPLEMENTATION.md` - This session summary
- [ ] This verification checklist

---

## 🚀 Deployment Readiness

### Before Deploying to Production

```bash
# 1. Build the frontend
cd frontend
npm run build

# Expected: Build completes without errors
# Files in: frontend/dist/

# 2. Run tests (if tests exist)
npm run test

# Expected: All tests pass

# 3. Check for console errors
npm run dev
# Navigate through app, check browser console (F12)

# 4. Verify API integration
# Make test PO creation

# 5. Check database
php artisan tinker
> \App\Models\Procurement\PurchaseOrder::latest()->first()
# Should see: stock_order_request_id populated

# 6. Verify routes
php artisan route:list | grep stock-order-requests
# Should show 7 routes in correct order
```

---

## 🎯 Success Criteria

**Mark as ✅ READY FOR PRODUCTION when:**

Frontend:
- ✅ No compilation errors: `npm run build` succeeds
- ✅ 3-step wizard displays correctly
- ✅ All steps function without errors
- ✅ Form submission successful
- ✅ PO appears in list with "Stock Request" badge

Backend:
- ✅ All endpoints respond correctly
- ✅ Data validation working
- ✅ Stock requests marked as converted
- ✅ No database errors

Integration:
- ✅ End-to-end workflow works
- ✅ Data flows correctly
- ✅ Calculations are accurate
- ✅ UI matches expected behavior

Testing:
- ✅ Manual tests all pass
- ✅ No console errors
- ✅ Performance acceptable
- ✅ Error handling works

---

## 📞 Support & Troubleshooting

### If Something Breaks

1. **Check Logs:**
   ```bash
   # Backend
   tail -f storage/logs/laravel.log
   
   # Frontend (browser console)
   F12 → Console tab → Look for red errors
   ```

2. **Verify Service Methods:**
   - Search `procurement.service.ts` for method name
   - Verify endpoint path is correct
   - Check parameter names match

3. **Check API Response:**
   - Open Network tab (F12)
   - Click action that failed
   - Check response: Is it 200/201 or error?
   - Read error message

4. **Database Verification:**
   - Verify tables exist
   - Verify migrations ran
   - Check data integrity

5. **Route Verification:**
   - Verify routes.php has correct order
   - Test endpoints separately with cURL

---

## 🔄 Next Steps After Verification

### If All Tests Pass ✅
1. Create user documentation
2. Deploy to staging
3. Perform UAT with team
4. Deploy to production
5. Monitor for errors
6. Gather feedback

### If Issues Found ❌
1. Document issue clearly
2. Check logs for root cause
3. Fix in code
4. Test fix locally
5. Re-run verification
6. Repeat until all tests pass

---

**Keep this checklist handy during testing!**

*Last Updated: January 17, 2025*  
*Ready for: Testing Phase*
