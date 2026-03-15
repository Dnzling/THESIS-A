# Supplier Module - Implementation Plan

## 📋 Overview
Comprehensive supplier management system with performance tracking, payment management, risk assessment, and intelligent recommendations.

---

## 🎯 Phase Scope (All 7 Features)

### 1. ✅ Supplier List & Search
- **Features**: View all suppliers, filter by status, search, pagination
- **Components**: `SupplierList.vue`
- **API Endpoints**: `GET /suppliers`, `GET /suppliers/search`
- **UI Elements**: DataTable, Search input, Status filters, Pagination

### 2. ✅ Supplier Management (CRUD)
- **Features**: Create, update, delete - contact info, payment terms, ratings
- **Components**: `SupplierForm.vue`, `SupplierDetail.vue`
- **API Endpoints**: `POST /suppliers`, `PUT /suppliers/{id}`, `DELETE /suppliers/{id}`
- **Validation**: Required fields, email format, phone format

### 3. ✅ Supplier Performance Tracking
- **Features**: On-time/late deliveries, quality ratings, performance trends
- **Components**: `SupplierPerformance.vue`, `PerformanceChart.vue`
- **API Endpoints**: `GET /suppliers/{id}/performance`, `GET /suppliers/{id}/metrics`
- **Metrics**: On-time %, Late deliveries, Quality score, Avg delivery days

### 4. ✅ Payment Management
- **Features**: Track payment terms, history, aging reports
- **Components**: `PaymentTracking.vue`, `PaymentHistory.vue`
- **API Endpoints**: `GET /suppliers/{id}/payments`, `POST /suppliers/{id}/payment-record`
- **Data**: Payment terms, due dates, aging (current/30/60/90+)

### 5. ✅ Supplier Categorization & Recommendations
- **Features**: Categorize by product type, auto-recommend
- **Components**: `SupplierCategorization.vue`
- **API Endpoints**: `GET /suppliers/by-category/{category}`, `GET /products/{id}/recommended-suppliers`
- **Logic**: Match supplier capabilities to product needs

### 6. ✅ Risk Assessment & Alerts
- **Features**: Flag at-risk suppliers, alert on patterns
- **Components**: `RiskAssessment.vue`, `AlertBadge.vue`
- **API Endpoints**: `GET /suppliers/{id}/risk-score`, `GET /suppliers/at-risk`
- **Triggers**: Late delivery %, Payment delays, Quality issues, Blacklist status

### 7. ✅ Supplier Dashboard
- **Features**: Overview of active/inactive, top performers, risk metrics
- **Components**: `SupplierDashboard.vue` (with multiple cards & charts)
- **Metrics**: Total suppliers, Active count, Top 5 performers, At-risk count, Avg quality

---

## 🗄️ Database Schema

### suppliers (Enhanced)
```sql
id, supplier_name, company_name, contact_person, email, phone, 
address, city, state, postal_code, country,
payment_terms, tax_id, bank_details,
status (active/inactive/blacklisted), rating (1-5),
category (raw_materials/furniture/accessories/services),
average_delivery_days, recent_delay_percentage,
quality_score, total_orders, on_time_deliveries,
late_deliveries, created_at, updated_at
```

### supplier_performance_metrics (New)
```sql
id, supplier_id, metric_date, on_time_count, late_count, 
quality_score, average_delivery_days, delivered_orders, 
issues_reported, created_at
```

### supplier_payments (New)
```sql
id, supplier_id, payment_amount, payment_method, payment_date, 
due_date, status (pending/partial/paid), invoice_number, 
po_number, days_overdue, created_at, updated_at
```

### supplier_ratings (New)
```sql
id, supplier_id, rated_by_user_id, rating (1-5), category 
(delivery/quality/communication/price), comment, created_at
```

---

## 🏗️ Backend Structure

### Controller: `SupplierController.php`
- `index()` - List all suppliers (paginated)
- `store()` - Create supplier
- `show()` - Get supplier details
- `update()` - Update supplier
- `destroy()` - Delete supplier
- `search()` - Search suppliers by name/category

### Controller: `SupplierPerformanceController.php`
- `getPerformanceMetrics()` - Get performance data
- `calculateRiskScore()` - Calculate risk rating
- `getPerformanceHistory()` - Chart data
- `getAtRiskSuppliers()` - List at-risk

### Controller: `SupplierPaymentController.php`
- `getPaymentHistory()` - Payment records
- `recordPayment()` - Log new payment
- `getAgingReport()` - Payment aging
- `getPaymentStatus()` - Current status

---

## 🎨 Frontend Components

### Core Pages
1. **SupplierList.vue** (650 lines)
   - DataTable with suppliers
   - Search/filter bar
   - Status badges (Active/Inactive/Blacklisted)
   - Quick actions (View, Edit, Delete)
   - Create new supplier button

2. **SupplierForm.vue** (600 lines)
   - Form sections: Basic Info, Contact, Payment Terms, Category
   - Validation on submit
   - Success/error toasts
   - Auto-calculate average delivery days

3. **SupplierDetail.vue** (800 lines)
   - 6-tab layout:
     - Overview (basic info + quick metrics)
     - Performance (delivery trends, quality score)
     - Payments (payment history, aging)
     - Category (product categories, capabilities)
     - Risk Assessment (risk score, alerts)
     - Order History (PO count, total spend)

4. **SupplierDashboard.vue** (900 lines)
   - Summary cards: Total, Active, Inactive, At-risk, Avg rating
   - Top 5 performers chart
   - Risk distribution chart
   - Category breakdown
   - On-time vs Late delivery chart

### Utility Components
- `SupplierSearch.vue` - Reusable search component
- `RiskBadge.vue` - Risk level indicator
- `PerformanceChart.vue` - Chart component wrapper
- `PaymentStatus.vue` - Payment status display

---

## 🔄 Service Layer

### Methods to Add to `procurementService`
- `getSuppliers(params)` - List with filters
- `createSupplier(data)` - Create
- `updateSupplier(id, data)` - Update
- `deleteSupplier(id)` - Delete
- `searchSuppliers(query)` - Search
- `getSupplierPerformance(id)` - Performance metrics
- `getSupplierPayments(id)` - Payment history
- `getSupplierRiskScore(id)` - Risk calculation
- `getRecommendedSuppliers(productId)` - Recommendations
- `getAtRiskSuppliers()` - At-risk list
- `getSupplierDashboard()` - Dashboard data

---

## 📊 Key Metrics & Calculations

### Risk Score (0-100)
```
= (late_delivery_pct * 40) + 
  (payment_delay_pct * 30) + 
  (quality_issues_pct * 20) + 
  (rating_penalty * 10)

Risk Level:
< 20: Green (Low)
20-50: Yellow (Medium)
50-75: Orange (High)
> 75: Red (Critical)
```

### Performance Score (0-100)
```
= (on_time_pct * 50) + 
  (quality_rating * 30) + 
  (response_time * 20)
```

### Payment Aging Categories
- Current (0 days)
- 30 days overdue
- 60 days overdue
- 90+ days overdue

---

## 📁 File Structure

```
frontend/src/
  composables/
    supplier/
      useSupplierManagement.ts (400 lines)
      usePerformanceTracking.ts (300 lines)
      useRiskAssessment.ts (250 lines)
  
  components/
    system/
      supplier/
        SupplierList.vue (650 lines)
        SupplierForm.vue (600 lines)
        SupplierDetail.vue (800 lines)
        SupplierDashboard.vue (900 lines)
        RiskBadge.vue (100 lines)
        PerformanceChart.vue (150 lines)
        PaymentStatus.vue (120 lines)

backend/app/Http/Controllers/Api/
  Supplier/
    SupplierController.php (400 lines)
    SupplierPerformanceController.php (350 lines)
    SupplierPaymentController.php (300 lines)

routes/
  supplier_routes.php (50+ endpoints)
```

---

## ⏱️ Estimated Timeline
- Backend:  2-3 hours (migrations, controllers, logic)
- Frontend: 4-5 hours (components, utilities, integration)
- Testing:  1-2 hours
- **Total: 7-10 hours for full-featured module**

---

## 🚀 Next Steps
1. Create database migrations
2. Build backend controllers & logic
3. Create service layer (composables)
4. Build frontend components (List → Detail → Dashboard)
5. Integration testing
6. Documentation

**Ready to start?**
