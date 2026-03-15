# Supplier Module - API Integration Guide

## Prerequisites
- Laravel 11 backend running on `http://localhost:8000`
- Database migrations executed: `php artisan migrate`
- Sanctum authentication configured
- Postman or similar API testing tool

## Base URL
```
http://localhost:8000/api/suppliers
```

## Authentication
All endpoints require:
```
Authorization: Bearer {sanctum_token}
Content-Type: application/json
```

## Supplier Management Endpoints

### 1. List Suppliers
**GET** `/`

Query Parameters:
- `page` (integer, default: 1)
- `per_page` (integer, default: 25)
- `status` (enum: active, inactive, blacklisted)
- `category` (string: Raw Materials, Furniture, Accessories, Services, Equipment)
- `search` (string: search in name, company, email)
- `sort_by` (enum: name, rating, quality, on_time)
- `sort_order` (enum: asc, desc)

**Example Request**:
```bash
GET /api/suppliers?page=1&per_page=25&status=active&sort_by=rating&sort_order=desc
```

**Expected Response** (200 OK):
```json
{
  "data": [
    {
      "id": 1,
      "supplier_name": "Acme Materials Inc",
      "company_name": "Acme Inc",
      "contact_person": "John Doe",
      "email": "john@acme.com",
      "phone": "(555) 123-4567",
      "category": "Raw Materials",
      "status": "active",
      "rating": 4.5,
      "quality_score": 4.2,
      "on_time_percentage": 95.5,
      "created_at": "2024-03-10T10:00:00Z",
      "updated_at": "2024-03-10T10:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 50,
    "per_page": 25,
    "last_page": 2
  }
}
```

---

### 2. Search Suppliers
**GET** `/search?q={query}`

Query Parameters:
- `q` (string, required): Search term

**Example Request**:
```bash
GET /api/suppliers/search?q=acme
```

**Expected Response** (200 OK):
```json
{
  "data": [
    {
      "id": 1,
      "supplier_name": "Acme Materials Inc",
      "company_name": "Acme Inc",
      "email": "john@acme.com"
    },
    {
      "id": 5,
      "supplier_name": "Acme Furniture Co",
      "company_name": "Acme Furniture",
      "email": "sales@acmefurniture.com"
    }
  ]
}
```

---

### 3. Create Supplier
**POST** `/`

**Required Fields**:
- supplier_name (string)
- company_name (string)
- contact_person (string)
- email (string, valid email format)
- phone (string, valid phone format)
- address (string)
- category (enum: Raw Materials, Furniture, Accessories, Services, Equipment)
- payment_terms (enum: Net 30, Net 60, Net 90, COD, Advance Payment, Installment)

**Optional Fields**:
- city (string)
- state (string)
- postal_code (string)
- country (string)
- tax_id (string)
- bank_details (text)

**Example Request**:
```bash
POST /api/suppliers
Content-Type: application/json

{
  "supplier_name": "Global Supplies Ltd",
  "company_name": "Global Supplies",
  "contact_person": "Jane Smith",
  "email": "jane@globalsupplies.com",
  "phone": "(555) 987-6543",
  "address": "123 Business Park",
  "city": "New York",
  "state": "NY",
  "postal_code": "10001",
  "country": "USA",
  "category": "Furniture",
  "payment_terms": "Net 60",
  "tax_id": "12-3456789",
  "bank_details": "Account: 123456789, Bank: First National"
}
```

**Expected Response** (201 Created):
```json
{
  "data": {
    "id": 10,
    "supplier_name": "Global Supplies Ltd",
    "company_name": "Global Supplies",
    "contact_person": "Jane Smith",
    "email": "jane@globalsupplies.com",
    "phone": "(555) 987-6543",
    "address": "123 Business Park",
    "city": "New York",
    "state": "NY",
    "postal_code": "10001",
    "country": "USA",
    "category": "Furniture",
    "payment_terms": "Net 60",
    "status": "active",
    "tax_id": "12-3456789",
    "bank_details": "Account: 123456789, Bank: First National",
    "rating": 0,
    "quality_score": 0,
    "on_time_percentage": 0,
    "created_at": "2024-03-10T11:00:00Z",
    "updated_at": "2024-03-10T11:00:00Z"
  },
  "message": "Supplier created successfully"
}
```

---

### 4. Get Supplier Detail
**GET** `/{id}`

**Example Request**:
```bash
GET /api/suppliers/1
```

**Expected Response** (200 OK):
```json
{
  "data": {
    "id": 1,
    "supplier_name": "Acme Materials Inc",
    "company_name": "Acme Inc",
    "contact_person": "John Doe",
    "email": "john@acme.com",
    "phone": "(555) 123-4567",
    "address": "789 Industrial Ave",
    "city": "Chicago",
    "state": "IL",
    "postal_code": "60601",
    "country": "USA",
    "category": "Raw Materials",
    "payment_terms": "Net 30",
    "status": "active",
    "tax_id": "98-7654321",
    "bank_details": "Account: 987654321, Bank: First Trust",
    "rating": 4.5,
    "quality_score": 4.2,
    "on_time_percentage": 95.5,
    "avg_delivery_days": 5,
    "risk_score": 12,
    "created_at": "2024-03-10T10:00:00Z",
    "updated_at": "2024-03-10T10:00:00Z"
  }
}
```

---

### 5. Update Supplier
**PUT** `/{id}`

**Example Request**:
```bash
PUT /api/suppliers/1
Content-Type: application/json

{
  "contact_person": "John Smith",
  "phone": "(555) 123-4567",
  "category": "Accessories"
}
```

**Expected Response** (200 OK):
```json
{
  "data": {
    "id": 1,
    "supplier_name": "Acme Materials Inc",
    "company_name": "Acme Inc",
    "contact_person": "John Smith",
    "phone": "(555) 123-4567",
    "category": "Accessories",
    "status": "active",
    "updated_at": "2024-03-10T12:00:00Z"
  },
  "message": "Supplier updated successfully"
}
```

---

### 6. Delete Supplier
**DELETE** `/{id}`

**Example Request**:
```bash
DELETE /api/suppliers/1
```

**Expected Response** (200 OK):
```json
{
  "message": "Supplier deleted successfully"
}
```

**Error Response** (409 Conflict - if active orders exist):
```json
{
  "error": "Cannot delete supplier with active purchase orders"
}
```

---

### 7. Get Suppliers by Category
**GET** `/category/{category}`

Path Parameters:
- `category` (enum: Raw Materials, Furniture, Accessories, Services, Equipment)

**Example Request**:
```bash
GET /api/suppliers/category/Furniture
```

**Expected Response** (200 OK):
```json
{
  "data": [
    {
      "id": 2,
      "supplier_name": "Furniture Plus",
      "company_name": "Furniture Plus Inc",
      "category": "Furniture",
      "status": "active",
      "rating": 4.8,
      "quality_score": 4.5
    }
  ]
}
```

---

## Performance Endpoints

### 1. Get Current Performance Metrics
**GET** `/{id}/performance`

**Example Request**:
```bash
GET /api/suppliers/1/performance
```

**Expected Response** (200 OK):
```json
{
  "data": {
    "supplier_id": 1,
    "on_time_percentage": 95.5,
    "quality_score": 4.2,
    "avg_delivery_days": 5,
    "risk_score": 12,
    "risk_level": "Low",
    "last_updated": "2024-03-10T23:00:00Z"
  }
}
```

---

### 2. Get Performance History
**GET** `/{id}/performance-history`

Query Parameters:
- `months` (integer, default: 12): Number of months to retrieve

**Example Request**:
```bash
GET /api/suppliers/1/performance-history?months=12
```

**Expected Response** (200 OK):
```json
{
  "data": [
    {
      "date": "2024-02-01",
      "on_time_percentage": 93.2,
      "quality_score": 4.1,
      "avg_delivery_days": 6,
      "risk_score": 15
    },
    {
      "date": "2024-03-01",
      "on_time_percentage": 95.5,
      "quality_score": 4.2,
      "avg_delivery_days": 5,
      "risk_score": 12
    }
  ]
}
```

---

### 3. Get At-Risk Suppliers
**GET** `/at-risk`

**Example Request**:
```bash
GET /api/suppliers/at-risk
```

**Expected Response** (200 OK):
```json
{
  "data": [
    {
      "id": 5,
      "supplier_name": "Risky Supplies Inc",
      "company_name": "Risky Supplies",
      "category": "Raw Materials",
      "risk_score": 68,
      "risk_level": "High",
      "risk_reasons": [
        "On-time delivery: 75%",
        "Payment delays average: 8 days",
        "Quality issues: 2 complaints"
      ]
    }
  ]
}
```

---

### 4. Get Top Performers
**GET** `/top-performers`

**Example Request**:
```bash
GET /api/suppliers/top-performers
```

**Expected Response** (200 OK):
```json
{
  "data": [
    {
      "id": 1,
      "supplier_name": "Acme Materials Inc",
      "company_name": "Acme Inc",
      "rating": 4.8,
      "quality_score": 4.7,
      "on_time_percentage": 98.5,
      "risk_score": 5
    },
    {
      "id": 3,
      "supplier_name": "Quality Goods Ltd",
      "company_name": "Quality Goods",
      "rating": 4.7,
      "quality_score": 4.6,
      "on_time_percentage": 97.2,
      "risk_score": 8
    }
  ]
}
```

---

## Payment Endpoints

### 1. Get Payment History
**GET** `/{id}/payments`

Query Parameters:
- `page` (integer, default: 1)
- `per_page` (integer, default: 15)
- `status` (enum: paid, pending, overdue)

**Example Request**:
```bash
GET /api/suppliers/1/payments?status=pending&per_page=10
```

**Expected Response** (200 OK):
```json
{
  "data": [
    {
      "id": 101,
      "supplier_id": 1,
      "amount": 5000,
      "payment_date": null,
      "due_date": "2024-03-20",
      "status": "pending",
      "days_overdue": 0,
      "payment_method": null
    },
    {
      "id": 100,
      "supplier_id": 1,
      "amount": 3500,
      "payment_date": "2024-03-05",
      "due_date": "2024-03-05",
      "status": "paid",
      "days_overdue": 0,
      "payment_method": "Bank Transfer"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 25,
    "per_page": 10
  }
}
```

---

### 2. Record Payment
**POST** `/{id}/payments/record`

**Required Fields**:
- amount (numeric, > 0)
- payment_date (date: YYYY-MM-DD)
- payment_method (string: Bank Transfer, Card, Check, Cash)

**Optional Fields**:
- reference_number (string)
- notes (text)

**Example Request**:
```bash
POST /api/suppliers/1/payments/record
Content-Type: application/json

{
  "amount": 3500,
  "payment_date": "2024-03-10",
  "payment_method": "Bank Transfer",
  "reference_number": "TXN-123456",
  "notes": "Payment for PO-001"
}
```

**Expected Response** (201 Created):
```json
{
  "data": {
    "id": 102,
    "supplier_id": 1,
    "amount": 3500,
    "payment_date": "2024-03-10",
    "due_date": "2024-03-20",
    "status": "paid",
    "days_overdue": 0,
    "payment_method": "Bank Transfer",
    "reference_number": "TXN-123456",
    "notes": "Payment for PO-001",
    "created_at": "2024-03-10T14:00:00Z"
  },
  "message": "Payment recorded successfully"
}
```

---

### 3. Get Aging Report
**GET** `/{id}/payments/aging`

**Example Request**:
```bash
GET /api/suppliers/1/payments/aging
```

**Expected Response** (200 OK):
```json
{
  "data": {
    "current": 2500,
    "current_count": 1,
    "days_30": 1500,
    "days_30_count": 1,
    "days_60": 0,
    "days_60_count": 0,
    "days_90": 750,
    "days_90_count": 1,
    "over_90": 1250,
    "over_90_count": 1,
    "total_due": 6000,
    "total_count": 4,
    "urgency": "Medium"
  }
}
```

---

### 4. Get Payment Status Summary
**GET** `/{id}/payment-status`

**Example Request**:
```bash
GET /api/suppliers/1/payment-status
```

**Expected Response** (200 OK):
```json
{
  "data": {
    "total_due": 6000,
    "due_count": 4,
    "total_paid": 45000,
    "paid_count": 15,
    "overdue_count": 2,
    "overdue_amount": 2000,
    "upcoming_amount": 2500,
    "upcoming_days": 10
  }
}
```

---

## Recommendation Endpoints

### 1. Get Recommended Suppliers for Product
**GET** `/product/{productId}/recommended`

**Example Request**:
```bash
GET /api/suppliers/product/50/recommended
```

**Expected Response** (200 OK):
```json
{
  "data": [
    {
      "id": 1,
      "supplier_name": "Acme Materials Inc",
      "category": "Raw Materials",
      "rating": 4.8,
      "quality_score": 4.7,
      "on_time_percentage": 98.5,
      "price_rating": 4.5,
      "recommendation_score": 95
    },
    {
      "id": 3,
      "supplier_name": "Quality Goods Ltd",
      "category": "Raw Materials",
      "rating": 4.7,
      "quality_score": 4.6,
      "on_time_percentage": 97.2,
      "price_rating": 4.3,
      "recommendation_score": 92
    }
  ]
}
```

---

### 2. Get Suppliers by Category
**GET** `/category/{category}/list`

**Example Request**:
```bash
GET /api/suppliers/category/Furniture/list
```

**Expected Response** (200 OK):
```json
{
  "data": [
    {
      "id": 2,
      "supplier_name": "Furniture Plus",
      "company_name": "Furniture Plus Inc",
      "rating": 4.8,
      "quality_score": 4.5,
      "on_time_percentage": 96.5,
      "status": "active"
    }
  ]
}
```

---

### 3. Get Alternative Suppliers
**GET** `/{id}/alternatives`

Query Parameters:
- `category` (optional, enum): Filter by specific category

**Example Request**:
```bash
GET /api/suppliers/2/alternatives?category=Furniture
```

**Expected Response** (200 OK):
```json
{
  "data": {
    "current_supplier": {
      "id": 2,
      "supplier_name": "Furniture Plus",
      "rating": 4.8,
      "quality_score": 4.5,
      "on_time_percentage": 96.5,
      "price_rating": 4.2
    },
    "alternatives": [
      {
        "id": 7,
        "supplier_name": "Premium Furniture Co",
        "rating": 4.9,
        "quality_score": 4.8,
        "on_time_percentage": 98.0,
        "price_rating": 3.8,
        "quality_delta": "+0.3",
        "price_delta": "-0.4"
      },
      {
        "id": 11,
        "supplier_name": "Budget Furniture Ltd",
        "rating": 4.2,
        "quality_score": 3.9,
        "on_time_percentage": 93.5,
        "price_rating": 4.8,
        "quality_delta": "-0.6",
        "price_delta": "+0.6"
      }
    ]
  }
}
```

---

## Dashboard Endpoint

### Get Dashboard Overview
**GET** `/suppliers/dashboard`

**Example Request**:
```bash
GET /api/suppliers/suppliers/dashboard
```

**Expected Response** (200 OK):
```json
{
  "data": {
    "summary": {
      "total_suppliers": 50,
      "active_suppliers": 48,
      "inactive_suppliers": 2,
      "blacklisted_suppliers": 0,
      "at_risk_count": 5
    },
    "averages": {
      "avg_quality_score": 4.3,
      "avg_rating": 4.5
    },
    "top_performers": [
      {
        "id": 1,
        "supplier_name": "Acme Materials Inc",
        "rating": 4.8,
        "quality_score": 4.7,
        "on_time_percentage": 98.5,
        "rank": 1
      }
    ],
    "risk_distribution": {
      "low": 40,
      "medium": 8,
      "high": 2,
      "critical": 0
    },
    "quality_distribution": {
      "excellent": 25,
      "good": 18,
      "average": 6,
      "below_average": 1
    },
    "category_breakdown": {
      "Raw Materials": 18,
      "Furniture": 15,
      "Accessories": 12,
      "Services": 4,
      "Equipment": 1
    }
  }
}
```

---

## Error Responses

### 400 Bad Request
```json
{
  "error": "Validation failed",
  "errors": {
    "email": ["The email field must be a valid email"],
    "phone": ["The phone field must be a valid phone number"]
  }
}
```

### 401 Unauthorized
```json
{
  "error": "Unauthenticated"
}
```

### 403 Forbidden
```json
{
  "error": "Unauthorized action"
}
```

### 404 Not Found
```json
{
  "error": "Supplier not found"
}
```

### 422 Unprocessable Entity
```json
{
  "error": "Validation failed",
  "errors": {
    "supplier_name": ["The supplier name field is required"]
  }
}
```

### 500 Internal Server Error
```json
{
  "error": "Server error",
  "message": "An unexpected error occurred"
}
```

---

## Testing Workflow

1. **Create Test Supplier**
   ```bash
   POST /api/suppliers
   ```

2. **Retrieve Supplier**
   ```bash
   GET /api/suppliers/{id}
   ```

3. **Update Supplier**
   ```bash
   PUT /api/suppliers/{id}
   ```

4. **Get Performance Metrics**
   ```bash
   GET /api/suppliers/{id}/performance
   ```

5. **Record Payment**
   ```bash
   POST /api/suppliers/{id}/payments/record
   ```

6. **Get Payment Status**
   ```bash
   GET /api/suppliers/{id}/payment-status
   ```

7. **Get Dashboard**
   ```bash
   GET /api/suppliers/suppliers/dashboard
   ```

8. **List All Suppliers**
   ```bash
   GET /api/suppliers?status=active
   ```

9. **Delete Supplier**
   ```bash
   DELETE /api/suppliers/{id}
   ```

---

## cURL Examples

**List Suppliers**:
```bash
curl -X GET "http://localhost:8000/api/suppliers" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

**Create Supplier**:
```bash
curl -X POST "http://localhost:8000/api/suppliers" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "supplier_name": "New Supplier",
    "company_name": "New Co",
    "contact_person": "John Doe",
    "email": "john@newco.com",
    "phone": "(555) 123-4567",
    "address": "123 Main St",
    "category": "Furniture",
    "payment_terms": "Net 30"
  }'
```

**Record Payment**:
```bash
curl -X POST "http://localhost:8000/api/suppliers/1/payments/record" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 3500,
    "payment_date": "2024-03-10",
    "payment_method": "Bank Transfer"
  }'
```

---

## Postman Collection Import

Save the following as `supplier-api.json` and import into Postman:

```json
{
  "info": {
    "name": "Supplier Module API",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "auth": {
    "type": "bearer",
    "bearer": [
      {
        "key": "token",
        "value": "{{API_TOKEN}}",
        "type": "string"
      }
    ]
  },
  "item": [
    {
      "name": "Suppliers",
      "item": [
        {
          "name": "List Suppliers",
          "request": {
            "method": "GET",
            "url": {
              "raw": "{{BASE_URL}}/api/suppliers?page=1&per_page=25&status=active",
              "host": ["{{BASE_URL}}"],
              "path": ["api", "suppliers"]
            }
          }
        },
        {
          "name": "Create Supplier",
          "request": {
            "method": "POST",
            "header": [
              {
                "key": "Content-Type",
                "value": "application/json"
              }
            ],
            "url": {
              "raw": "{{BASE_URL}}/api/suppliers",
              "host": ["{{BASE_URL}}"],
              "path": ["api", "suppliers"]
            },
            "body": {
              "mode": "raw",
              "raw": "{}"
            }
          }
        }
      ]
    }
  ]
}
```

Set Postman environment variables:
- `BASE_URL`: `http://localhost:8000`
- `API_TOKEN`: Your Sanctum bearer token

---

## Next Steps

1. Execute migrations: `php artisan migrate`
2. Run API tests using provided examples
3. Verify all endpoints return expected responses
4. Create seed data for demo/testing
5. Integrate frontend with backend APIs
6. Perform UAT testing
