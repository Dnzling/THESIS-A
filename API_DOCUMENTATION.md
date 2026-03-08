# API DOCUMENTATION

**Version:** 1.0.0  
**Base URL:** `https://your-domain.com/api`  
**Authentication:** Bearer Token (Sanctum)  

---

## 📋 TABLE OF CONTENTS

1. [Authentication](#authentication)
2. [Notifications API](#notifications-api)
3. [Alerts API](#alerts-api)
4. [Configuration API](#configuration-api)
5. [Reports API](#reports-api)
6. [Dashboard API](#dashboard-api)
7. [Stock Transfer API](#stock-transfer-api)
8. [Error Handling](#error-handling)
9. [Rate Limiting](#rate-limiting)

---

## 🔐 AUTHENTICATION

All API endpoints require authentication using Bearer tokens (Sanctum).

### Getting a Token
```http
POST /api/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password"
}

Response:
{
  "token": "1|AbCdEfGhIjKlMnOpQrStUvWxYz...",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "role": "warehouse_manager"
  }
}
```

### Using Token
```http
GET /api/inventory/notifications
Authorization: Bearer 1|AbCdEfGhIjKlMnOpQrStUvWxYz...
```

---

## 📬 NOTIFICATIONS API

### List Notifications
```http
GET /api/inventory/notifications?page=1&limit=15&status=unread
Authorization: Bearer {token}
```

**Query Parameters:**
- `page` (int) - Page number, default: 1
- `limit` (int) - Items per page, default: 15
- `status` (string) - Filter: 'all', 'unread', 'read'
- `type` (string) - Filter by type
- `sort` (string) - Sort: 'newest', 'oldest', 'unread_first'

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "type": "alert",
      "title": "Low Stock Alert",
      "message": "Product SKU-001 is low in stock",
      "action_url": "/inventory/alerts/1",
      "read_at": null,
      "created_at": "2026-03-08T10:30:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 45,
    "per_page": 15,
    "unread_count": 5
  }
}
```

### Get Unread Count
```http
GET /api/inventory/notifications/unread
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "unread_count": 5
}
```

### Get Single Notification
```http
GET /api/inventory/notifications/{id}
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "id": 1,
  "type": "alert",
  "title": "Low Stock Alert",
  "message": "Product SKU-001 is low in stock",
  "action_url": "/inventory/alerts/1",
  "read_at": null,
  "created_at": "2026-03-08T10:30:00Z"
}
```

### Mark as Read
```http
PUT /api/inventory/notifications/{id}/read
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "message": "Notification marked as read",
  "read_at": "2026-03-08T10:35:00Z"
}
```

### Mark All as Read
```http
PUT /api/inventory/notifications/mark-all-read
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "message": "All notifications marked as read",
  "marked_count": 5
}
```

### Delete Notification
```http
DELETE /api/inventory/notifications/{id}
Authorization: Bearer {token}
```

**Response (204 No Content)**

### Batch Delete
```http
POST /api/inventory/notifications/batch-delete
Authorization: Bearer {token}
Content-Type: application/json

{
  "ids": [1, 2, 3, 4, 5]
}
```

**Response (200 OK):**
```json
{
  "message": "Notifications deleted",
  "deleted_count": 5
}
```

---

## 🚨 ALERTS API

### List Alerts
```http
GET /api/inventory/alert-management?page=1&status=active&severity=critical
Authorization: Bearer {token}
```

**Query Parameters:**
- `page` (int) - Page number
- `status` (string) - Filter: active, acknowledged, resolved
- `severity` (string) - Filter: critical, high, medium, low
- `type` (string) - Filter by alert type
- `search` (string) - Search by product name/SKU
- `branch_id` (int) - Filter by branch

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "sku": "SOFA-MOD-3S",
      "product_name": "Modern 3-Seater Sofa",
      "branch_name": "Main Store",
      "alert_type": "low_stock",
      "severity": "critical",
      "current_stock": 2,
      "reorder_point": 10,
      "maximum_stock": 30,
      "safety_stock": 5,
      "status": "active",
      "created_at": "2026-03-08T10:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 12,
    "per_page": 10
  }
}
```

### Get Alert Statistics
```http
GET /api/inventory/alert-management/statistics
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "active": 5,
  "critical": 2,
  "high": 3,
  "medium": 0,
  "low": 0,
  "acknowledged": 8,
  "resolved": 15,
  "by_type": {
    "low_stock": 5,
    "out_of_stock": 2,
    "overstock": 1
  }
}
```

### Get Active Alerts
```http
GET /api/inventory/alert-management/active
Authorization: Bearer {token}
```

**Response (200 OK):** Same as list alerts

### Filter by Type
```http
GET /api/inventory/alert-management/by-type?type=low_stock
Authorization: Bearer {token}
```

**Response (200 OK):** Same as list alerts

### Get Single Alert
```http
GET /api/inventory/alert-management/{id}
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "id": 1,
  "sku": "SOFA-MOD-3S",
  "product_name": "Modern 3-Seater Sofa",
  "branch_name": "Main Store",
  "alert_type": "low_stock",
  "severity": "critical",
  "current_stock": 2,
  "reorder_point": 10,
  "maximum_stock": 30,
  "safety_stock": 5,
  "status": "active",
  "created_at": "2026-03-08T10:00:00Z"
}
```

### Acknowledge Alert
```http
POST /api/inventory/alert-management/{id}/acknowledge
Authorization: Bearer {token}
Content-Type: application/json

{
  "notes": "Ordered more stock from supplier"
}
```

**Response (200 OK):**
```json
{
  "message": "Alert acknowledged",
  "status": "acknowledged",
  "acknowledged_at": "2026-03-08T10:35:00Z",
  "acknowledged_by": "John Doe"
}
```

### Resolve Alert
```http
POST /api/inventory/alert-management/{id}/resolve
Authorization: Bearer {token}
Content-Type: application/json

{
  "notes": "Stock replenished to safe levels"
}
```

**Response (200 OK):**
```json
{
  "message": "Alert resolved",
  "status": "resolved",
  "resolved_at": "2026-03-08T11:00:00Z",
  "resolved_by": "John Doe"
}
```

---

## ⚙️ CONFIGURATION API

### Get Configuration
```http
GET /api/inventory/configuration
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "default_reorder_point": 50,
  "default_reorder_qty": 100,
  "safety_stock_percentage": 20,
  "max_stock_multiplier": 3,
  "low_stock_alert_percentage": 30,
  "out_of_stock_quantity": 0,
  "overstock_alert_percentage": 150,
  "finance_approval_threshold": 5000,
  "notify_low_stock": true,
  "enable_email_notifications": false,
  "notification_recipients": "manager@store.com"
}
```

### Update Configuration
```http
PUT /api/inventory/configuration
Authorization: Bearer {token}
Content-Type: application/json

{
  "default_reorder_point": 60,
  "safety_stock_percentage": 25,
  "low_stock_alert_percentage": 35,
  "notify_low_stock": true,
  "enable_email_notifications": true,
  "notification_recipients": "manager@store.com,admin@store.com"
}
```

**Response (200 OK):**
```json
{
  "message": "Configuration updated successfully",
  "data": {
    "default_reorder_point": 60,
    "safety_stock_percentage": 25,
    "low_stock_alert_percentage": 35
  }
}
```

### Get Field Schema
```http
GET /api/inventory/configuration/schema
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "fields": {
    "default_reorder_point": {
      "type": "number",
      "min": 1,
      "max": 1000,
      "label": "Default Reorder Point",
      "description": "Default minimum stock level"
    }
  }
}
```

---

## 📊 REPORTS API

### Branch Summary Report
```http
GET /api/inventory/reports/branch-summary?from_date=2026-02-08&to_date=2026-03-08
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "data": {
    "summary": [
      {
        "label": "Total Inventory Value",
        "value": 125000,
        "type": "currency"
      },
      {
        "label": "Low Stock Items",
        "value": 12,
        "type": "number"
      }
    ],
    "items": [
      {
        "branch_name": "Main Store",
        "total_items": 150,
        "total_value": 75000,
        "low_stock_count": 8
      }
    ]
  }
}
```

### Store Summary Report
```http
GET /api/inventory/reports/store-summary
Authorization: Bearer {token}
```

**Response:** Similar to branch summary

### Stock Movements Report
```http
GET /api/inventory/reports/movements?group_by=daily&from_date=2026-02-08&to_date=2026-03-08
Authorization: Bearer {token}
```

**Query Parameters:**
- `group_by` (string) - daily, weekly, monthly
- `from_date` (string) - YYYY-MM-DD
- `to_date` (string) - YYYY-MM-DD

**Response (200 OK):**
```json
{
  "data": {
    "summary": [
      {
        "label": "Total Inbound",
        "value": 500,
        "type": "number"
      }
    ],
    "items": [
      {
        "date": "2026-03-08",
        "inbound": 50,
        "outbound": 30,
        "net": 20
      }
    ],
    "chart_data": {
      "labels": ["2026-03-01", "2026-03-02"],
      "datasets": [
        {
          "label": "Inbound",
          "data": [50, 45]
        }
      ]
    }
  }
}
```

### Category Breakdown Report
```http
GET /api/inventory/reports/value-by-category
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "data": {
    "summary": [
      {
        "label": "Total Categories",
        "value": 5,
        "type": "number"
      }
    ],
    "items": [
      {
        "category_name": "Sofas",
        "product_count": 15,
        "inventory_value": 50000,
        "percentage": 40
      }
    ]
  }
}
```

### Slow Movers Report
```http
GET /api/inventory/reports/slow-movers?period_days=90
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "data": {
    "items": [
      {
        "sku": "CHAIR-VTG",
        "product_name": "Vintage Chair",
        "quantity_on_hand": 45,
        "units_sold": 2,
        "days_in_stock": 180
      }
    ]
  }
}
```

### Fast Movers Report
```http
GET /api/inventory/reports/fast-movers?period_days=90
Authorization: Bearer {token}
```

### Transfer Metrics Report
```http
GET /api/inventory/reports/transfers?from_date=2026-02-08&to_date=2026-03-08
Authorization: Bearer {token}
```

### Stock Aging Report
```http
GET /api/inventory/reports/aging?threshold_days=90
Authorization: Bearer {token}
```

### Export Report
```http
GET /api/inventory/reports/branch-summary/export?export_format=csv&from_date=2026-02-08&to_date=2026-03-08
Authorization: Bearer {token}
```

**Response:** CSV file download

---

## 📈 DASHBOARD API

### Get Dashboard Data
```http
POST /api/inventory/dashboard
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "role": "warehouse_manager",
  "dashboard": {
    "kpis": {
      "total_items": 1250,
      "low_stock_count": 12,
      "out_of_stock_count": 3,
      "total_value": 250000,
      "pending_transfers": 5
    },
    "recent_activity": [
      {
        "type": "transfer_completed",
        "message": "Transfer TR-001 received",
        "timestamp": "2026-03-08T10:30:00Z"
      }
    ],
    "alerts": [
      {
        "id": 1,
        "message": "Low stock for SOFA-MOD-3S",
        "severity": "critical"
      }
    ]
  }
}
```

---

## 📦 STOCK TRANSFER API

### List Stock Transfers
```http
GET /api/inventory/transfers?page=1&status=pending
Authorization: Bearer {token}
```

**Query Parameters:**
- `page` (int) - Page number
- `status` (string) - draft, submitted, approved, shipped, received, cancelled
- `from_date` (string) - YYYY-MM-DD
- `to_date` (string) - YYYY-MM-DD

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "transfer_no": "TR-001",
      "from_branch": "Main Store",
      "to_branch": "Branch Store",
      "status": "pending_approval",
      "item_count": 5,
      "created_at": "2026-03-08T10:00:00Z",
      "approval_status": {
        "sender_approved": true,
        "finance_approved_at": null,
        "awaiting_finance": true
      }
    }
  ],
  "meta": {
    "total": 15,
    "per_page": 10
  }
}
```

### Create Stock Transfer
```http
POST /api/inventory/transfers
Authorization: Bearer {token}
Content-Type: application/json

{
  "to_branch_id": 2,
  "transfer_date": "2026-03-08",
  "expected_receive_date": "2026-03-10",
  "remarks": "Regular stock replenishment",
  "items": [
    {
      "inventory_item_id": 1,
      "quantity": 10
    },
    {
      "inventory_item_id": 2,
      "quantity": 5
    }
  ]
}
```

**Response (201 Created):**
```json
{
  "id": 1,
  "transfer_no": "TR-001",
  "status": "draft",
  "message": "Transfer created successfully"
}
```

### Approve Transfer
```http
POST /api/inventory/transfers/{id}/approve
Authorization: Bearer {token}
Content-Type: application/json

{
  "notes": "Approved - stock available"
}
```

### Ship Transfer
```http
POST /api/inventory/transfers/{id}/ship
Authorization: Bearer {token}
```

### Receive Transfer
```http
POST /api/inventory/transfers/{id}/receive
Authorization: Bearer {token}
Content-Type: application/json

{
  "received_items": [
    {
      "inventory_item_id": 1,
      "received_quantity": 10
    }
  ]
}
```

---

## ❌ ERROR HANDLING

### Error Response Format
```json
{
  "message": "The given data was invalid",
  "errors": {
    "field_name": [
      "This field is required",
      "This value must be unique"
    ]
  }
}
```

### Status Codes
- `200 OK` - Success
- `201 Created` - Resource created
- `204 No Content` - Success (no content)
- `400 Bad Request` - Invalid request
- `401 Unauthorized` - Authentication required
- `403 Forbidden` - Permission denied
- `404 Not Found` - Resource not found
- `422 Unprocessable Entity` - Validation error
- `429 Too Many Requests` - Rate limit exceeded
- `500 Internal Server Error` - Server error

---

## 🚦 RATE LIMITING

Rate limits are applied per user:
- 60 requests per minute for general endpoints
- 30 requests per minute for report generation
- 10 requests per minute for heavy operations

**Rate Limit Headers:**
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1646131200
```

---

## 📝 COMMON REQUEST PATTERNS

### Pagination
```http
GET /api/inventory/notifications?page=2&limit=20
```

### Filtering
```http
GET /api/inventory/alerts?status=active&severity=critical&type=low_stock
```

### Date Range
```http
GET /api/inventory/reports/movements?from_date=2026-02-08&to_date=2026-03-08
```

### Sorting
```http
GET /api/inventory/alerts?sort=-created_at
GET /api/inventory/alerts?sort=severity
```

---

## 🔗 WEBHOOK EVENTS (Future)

The following events can trigger webhooks:
- `inventory.alert.created`
- `inventory.transfer.completed`
- `inventory.notification.created`
- `inventory.configuration.updated`

---

**API Documentation: Complete**  
**Last Updated:** March 8, 2026  
**Status:** Production Ready
