# Job Hiring System - Setup & Configuration Guide

**Date:** March 11, 2026  
**Status:** Ready for Configuration & Testing

---

## 1. Database Migration

### Step 1: Run Migration
```bash
cd backend
php artisan migrate
```

This creates all 7 required tables:
- job_postings
- job_posting_screening_stages
- job_applications
- application_timeline
- application_documents
- interviews
- job_offers

### Step 2: Verify Tables
```bash
php artisan tinker
>>> DB::table('job_postings')->get();
```

---

## 2. Permission Setup

### Step 1: Create Permissions in Database

Insert into `permissions` table:

```sql
INSERT INTO permissions (name, display_name, description, is_active, created_at, updated_at) VALUES

-- Job Postings
('view-job-postings', 'View Job Postings', 'View all job postings and details', 1, NOW(), NOW()),
('create-job-postings', 'Create Job Postings', 'Create new job postings', 1, NOW(), NOW()),
('edit-job-postings', 'Edit Job Postings', 'Edit existing job postings', 1, NOW(), NOW()),
('delete-job-postings', 'Delete Job Postings', 'Delete job postings', 1, NOW(), NOW()),

-- Job Applications
('view-job-applications', 'View Job Applications', 'View all job applications', 1, NOW(), NOW()),
('update-application-status', 'Update Application Status', 'Update status of job applications', 1, NOW(), NOW()),
('delete-job-applications', 'Delete Job Applications', 'Delete job applications', 1, NOW(), NOW()),

-- Interviews
('view-interviews', 'View Interviews', 'View interview records', 1, NOW(), NOW()),
('schedule-interviews', 'Schedule Interviews', 'Schedule and create interviews', 1, NOW(), NOW()),
('update-interviews', 'Update Interviews', 'Update interview feedback and scores', 1, NOW(), NOW()),
('delete-interviews', 'Delete Interviews', 'Delete interview records', 1, NOW(), NOW()),

-- Job Offers
('view-job-offers', 'View Job Offers', 'View job offers', 1, NOW(), NOW()),
('create-job-offers', 'Create Job Offers', 'Create job offers for candidates', 1, NOW(), NOW()),
('edit-job-offers', 'Edit Job Offers', 'Edit job offers', 1, NOW(), NOW()),
('delete-job-offers', 'Delete Job Offers', 'Delete job offers', 1, NOW(), NOW()),
('accept-offers', 'Accept Job Offers', 'Accept offers (triggers employee creation)', 1, NOW(), NOW()),
('decline-offers', 'Decline Job Offers', 'Decline job offers', 1, NOW(), NOW());
```

### Step 2: Assign Permissions to Roles

**For HR Manager Role:**
```sql
-- Get permission IDs first
SELECT id FROM permissions WHERE name IN (
  'view-job-postings', 'create-job-postings', 'edit-job-postings', 'delete-job-postings',
  'view-job-applications', 'update-application-status', 'delete-job-applications',
  'view-interviews', 'schedule-interviews', 'update-interviews', 'delete-interviews',
  'view-job-offers', 'create-job-offers', 'edit-job-offers', 'accept-offers', 'decline-offers'
);

-- Get HR Manager role ID
SELECT id FROM roles WHERE name = 'HR Manager';

-- Insert into role_permissions
INSERT INTO role_permissions (role_id, permission_id, created_at, updated_at)
SELECT [HR_MANAGER_ROLE_ID], id, NOW(), NOW()
FROM permissions
WHERE name IN (
  'view-job-postings', 'create-job-postings', 'edit-job-postings', 'delete-job-postings',
  'view-job-applications', 'update-application-status', 'delete-job-applications',
  'view-interviews', 'schedule-interviews', 'update-interviews', 'delete-interviews',
  'view-job-offers', 'create-job-offers', 'edit-job-offers', 'accept-offers', 'decline-offers'
);
```

**For Employees (View Only + Apply):**
```sql
-- View postings and apply for jobs
INSERT INTO role_permissions (role_id, permission_id, created_at, updated_at)
SELECT [EMPLOYEE_ROLE_ID], id, NOW(), NOW()
FROM permissions
WHERE name IN ('view-job-postings');
```

---

## 3. API Routes Configuration

### Verify Routes
```bash
php artisan route:list | grep job
```

Expected routes:
```
GET       /api/job-postings
POST      /api/job-postings
GET       /api/job-postings/{posting}
PUT       /api/job-postings/{posting}
DELETE    /api/job-postings/{posting}
PUT       /api/job-postings/{posting}/screening-stages
GET       /api/job-postings/{posting}/applications
POST      /api/job-postings/{posting}/applications
GET       /api/job-applications/{application}
PUT       /api/job-applications/{application}/status
GET       /api/job-applications/{application}/documents/{document}
DELETE    /api/job-applications/{application}
GET       /api/interviews/application/{application}
POST      /api/interviews
GET       /api/interviews/{interview}
PUT       /api/interviews/{interview}
DELETE    /api/interviews/{interview}
PUT       /api/interviews/application/{application}/bulk-update
POST      /api/job-offers
GET       /api/job-offers/{offer}
PUT       /api/job-offers/{offer}
DELETE    /api/job-offers/{offer}
POST      /api/job-offers/{offer}/accept
POST      /api/job-offers/{offer}/decline
```

---

## 4. Frontend Route Configuration

### Add to Router

File: `/frontend/src/router/index.ts` (or main route file)

```typescript
{
  path: '/hiring',
  component: () => import('@/layouts/SystemLayout.vue'),
  children: [
    {
      path: 'job-postings',
      name: 'job-postings',
      component: () => import('@/pages/JobPostingsList.vue'),
      meta: {
        title: 'Job Postings',
        breadcrumbs: [
          { label: 'HR', to: '/hiring' },
          { label: 'Job Postings', to: '/hiring/job-postings' }
        ]
      }
    },
    {
      path: 'job-postings/:postingId/screening',
      name: 'screening-pipeline',
      component: () => import('@/pages/ScreeningPipeline.vue'),
      meta: {
        title: 'Screening Pipeline',
        breadcrumbs: [
          { label: 'HR', to: '/hiring' },
          { label: 'Job Postings', to: '/hiring/job-postings' },
          { label: 'Screening Pipeline' }
        ]
      }
    },
    {
      path: 'job-postings/:postingId/apply',
      name: 'apply-job',
      component: () => import('@/pages/JobApplicationForm.vue'),
      meta: {
        title: 'Apply for Job',
        public: true
      }
    }
  ]
}
```

### Add to Navigation Menu

File: `/frontend/src/components/NavigationMenu.vue` or similar:

```vue
<template>
  <!-- Under HR Section -->
  <div class="nav-section" v-if="hasPermission('view-job-postings')">
    <h3>HR & Recruitment</h3>
    <router-link to="/hiring/job-postings">Job Postings</router-link>
  </div>
</template>
```

---

## 5. Database Seeding (Optional)

### Create Sample Data

File: `/backend/database/seeders/JobHiringSeeder.php`

```php
<?php

namespace Database\Seeders;

use App\Models\JobPosting;
use App\Models\JobPostingScreeningStage;
use Illuminate\Database\Seeder;

class JobHiringSeeder extends Seeder
{
    public function run(): void
    {
        // Create sample job posting
        $posting = JobPosting::create([
            'store_id' => 1,
            'created_by' => 1,
            'title' => 'Senior Furniture Designer',
            'description' => 'Looking for experienced furniture designer with 5+ years experience',
            'department' => 'Design',
            'salary_min' => 50000,
            'salary_max' => 70000,
            'requirements' => ['CAD Skills', 'Portfolio', '5+ years experience'],
            'benefits' => ['Health Insurance', 'Retirement Plan', 'Flexible Hours'],
            'status' => 'Open'
        ]);

        // Create screening stages
        $stages = ['Initial Review', 'Phone Screen', 'Interview', 'Offer'];
        foreach ($stages as $index => $stage) {
            JobPostingScreeningStage::create([
                'job_posting_id' => $posting->id,
                'name' => $stage,
                'order' => $index + 1
            ]);
        }
    }
}
```

Run seeder:
```bash
php artisan db:seed --class=JobHiringSeeder
```

---

## 6. Testing API Endpoints

### Using Postman or curl

#### 1. Create Job Posting
```bash
POST /api/job-postings
Authorization: Bearer {token}
Content-Type: application/json

{
  "store_id": 1,
  "title": "Senior Designer",
  "description": "Design furniture",
  "department": "Design",
  "salary_min": 50000,
  "salary_max": 70000,
  "status": "Open",
  "screening_stages": [
    {"name": "Initial Review"},
    {"name": "Phone Screen"},
    {"name": "Interview"},
    {"name": "Offer"}
  ]
}
```

#### 2. Submit Application
```bash
POST /api/job-postings/1/applications
Authorization: Bearer {token}
Content-Type: multipart/form-data

{
  "job_posting_id": 1,
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "phone": "+63 9123456789",
  "is_internal": false,
  "documents": [file selector]
}
```

#### 3. Update Application Status
```bash
PUT /api/job-applications/1/status
Authorization: Bearer {token}
Content-Type: application/json

{
  "status": "Screening",
  "stage_id": 1,
  "notes": "Passed initial review"
}
```

#### 4. Schedule Interview
```bash
POST /api/interviews
Authorization: Bearer {token}
Content-Type: application/json

{
  "application_id": 1,
  "interviewer_id": 2,
  "interview_date": "2026-03-20T10:00:00",
  "interview_type": "Phone Screen",
  "notes": "Discuss background"
}
```

#### 5. Create Job Offer
```bash
POST /api/job-offers
Authorization: Bearer {token}
Content-Type: application/json

{
  "application_id": 1,
  "salary": 65000,
  "position": "Senior Designer",
  "department": "Design",
  "start_date": "2026-04-01",
  "benefits": ["Health Insurance", "Retirement Plan"],
  "expiry_date": "2026-03-25"
}
```

#### 6. Accept Offer (Auto-creates Employee)
```bash
POST /api/job-offers/1/accept
Authorization: Bearer {token}
Content-Type: application/json

{}
```

Response will include:
```json
{
  "message": "Offer accepted and employee created successfully",
  "employee_id": "2026-00001",
  "offer": {...}
}
```

---

## 7. File Upload Configuration

### Ensure Storage is Configured

File: `/backend/config/filesystems.php`

```php
'disks' => [
    'public' => [
        'driver' => 'local',
        'root' => storage_path('app/public'),
        'url' => env('APP_URL').'/storage',
        'visibility' => 'public',
    ],
],
```

### Create Symbolic Link
```bash
php artisan storage:link
```

### Ensure Directory Exists
```bash
mkdir -p storage/app/public/job-applications
chmod -R 755 storage/app/public
```

---

## 8. Troubleshooting

### If API endpoints return 403 (Forbidden)
- Check user has required permissions
- Verify role_permissions table entries
- Test with admin user first

### If file uploads fail
- Check storage disk configuration
- Verify directory permissions
- Ensure public storage link exists

### If employee creation fails
- Check Employee model exists and has correct schema
- Verify employee_id field exists in employees table
- Check deduction system is configured (if auto-deductions enabled)

### If screening stages don't show
- Verify JobPostingScreeningStage model relationships
- Check order field is set correctly
- Ensure job_posting_id foreign key is set

---

## 9. Performance Optimization

### Database Indexes
Already created in migration:
- job_posting_id on job_applications
- application_id on application_timeline
- stage_id on application_timeline
- application_id on application_documents
- application_id on interviews
- application_id on job_offers

### API Response Optimization
Controllers use `with()` for eager loading:
```php
JobPosting::with(['store', 'screeningStages', 'applications'])->get();
```

### Frontend Optimization
- Lazy load pages with dynamic imports
- Pagination on lists (15 items per page)
- Memoized computed properties

---

## 10. Security Considerations

### Data Protection
- ✅ Bearer token authentication required
- ✅ Permission-based authorization
- ✅ CORS configured for same-origin
- ✅ Rate limiting (1000 req/min)
- ✅ File upload validation (type, size)

### File Upload Security
- Files stored in `storage/app/public/job-applications/{application_id}/`
- Maximum file size: 5MB
- Allowed types: PDF, DOC, DOCX, JPG, PNG
- File validation in form and controller

---

## 11. Next Steps Checklist

- [ ] Run database migration
- [ ] Create permissions in database
- [ ] Assign permissions to HR role
- [ ] Configure storage link
- [ ] Test API endpoints with Postman
- [ ] Add routes to frontend router
- [ ] Add navigation menu items
- [ ] Test full workflow (posting → apply → hire)
- [ ] Configure file upload directory
- [ ] Test employee ID generation
- [ ] Verify permission checks work
- [ ] Load test with sample data

---

## 12. Support & Documentation

### Key Files to Review
1. `/backend/app/Models/` - Model definitions
2. `/backend/app/Http/Controllers/Api/` - API controllers
3. `/backend/app/Services/EmployeeIdGenerationService.php` - Employee creation logic
4. `/backend/database/migrations/2026_03_11_000001_create_job_postings_tables.php` - Schema

### API Documentation
- SwaggerUI (optional): Can generate from controller comments
- Postman Collection (recommended): Export route definitions

### Contact Support
For issues with:
- **Database**: Check migration run successfully
- **Permissions**: Verify role_permissions entries
- **File Uploads**: Check storage configuration
- **Employee Creation**: Check Employee model schema

---

**Setup completed!** System ready for testing and integration testing.
