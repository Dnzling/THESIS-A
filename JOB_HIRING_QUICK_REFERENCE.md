# Job Hiring System - Developer Quick Reference

**Last Updated:** March 11, 2026

---

## 🎯 Quick Start

### Project Status
```
Backend:  ✅ 100% Complete (Models, Controllers, Services, Routes)
Frontend: ✅ 80% Complete (Pages, Components created, styling pending)
Database: ✅ 100% Complete (Migration ready)
Docs:     ✅ 100% Complete
```

### File Locations

| Component | Path |
|-----------|------|
| **Models** | `/backend/app/Models/` |
| **Controllers** | `/backend/app/Http/Controllers/Api/` |
| **Services** | `/backend/app/Services/` |
| **Migrations** | `/backend/database/migrations/2026_03_11_000001_create_job_postings_tables.php` |
| **Routes** | `/backend/routes/job_hiring_routes.php` |
| **Vue Pages** | `/frontend/src/pages/` (JobPostingsList, ScreeningPipeline, JobApplicationForm) |
| **Components** | `/frontend/src/components/` (JobPostingFormModal, ApplicationDetailModal) |

---

## 🛠️ Development Commands

```bash
# Backend Setup
cd backend
php artisan migrate                    # Run database migration
php artisan tinker                     # Interactive shell
php artisan route:list                 # View all routes

# Test API
curl -X GET http://localhost:8000/api/job-postings \
  -H "Authorization: Bearer {token}"

# Frontend Setup
cd frontend
npm install                            # Install dependencies
npm run dev                            # Development server
npm run build                          # Production build
```

---

## 📊 Data Flow

```
User Application Submission
  ↓
JobApplication Model Created
  ↓
ApplicationTimeline Entry (Applied)
  ↓
ApplicationDocument Records (for files)
  ↓
HR Reviews in Screening Pipeline
  ↓
Update Status → ApplicationTimeline Updated
  ↓
Schedule Interviews → Interview Records
  ↓
Create Offer → JobOffer Record
  ↓
Accept Offer → AUTO: Employee Created (ID: 2026-XXXXX)
  ↓
Deductions Auto-populated
  ↓
Application Status = Hired
```

---

## 🔑 Key Models & Attributes

### JobPosting
```php
$posting = JobPosting::find(1);
$posting->title;                      // "Senior Designer"
$posting->salary_min;                 // 50000
$posting->screening_stages;           // Collection of stages
$posting->applications;               // Collection of applications
```

### JobApplication
```php
$app = JobApplication::find(1);
$app->full_name;                      // "John Doe" (accessor)
$app->status;                         // "Applied"
$app->timeline;                       // Ordered by created_at DESC
$app->documents;                      // Files uploaded
$app->interviews;                     // Ordered by date DESC
$app->offer;                          // Single offer
```

### JobOffer (When Accepted)
```php
// Accept endpoint triggers:
$offer->accept();
  ↓
EmployeeIdGenerationService::createEmployeeFromOffer($offer)
  ↓
New Employee Created:
{
  employee_id: "2026-00001",
  first_name: "John",
  last_name: "Doe",
  position: "Senior Designer",
  salary: 65000,
  start_date: "2026-04-01"
}
```

---

## 🔐 Permission Names

| Permission | Action |
|-----------|--------|
| `view-job-postings` | Read job listings |
| `create-job-postings` | Create new jobs |
| `edit-job-postings` | Modify job listings |
| `delete-job-postings` | Remove job postings |
| `view-job-applications` | See applications |
| `update-application-status` | Change app status |
| `schedule-interviews` | Create interviews |
| `accept-offers` | Accept offers (triggers employee creation) |
| `decline-offers` | Reject offers |

---

## 🔄 Status Workflow

```
Applied
   ↓
Screening (at initial review)
   ↓
Interview (after interviews scheduled)
   ↓
Offer (when offer created)
   ↓
Accepted / Hired (when offer accepted)
OR
Rejected (at any stage)
```

---

## 📁 File Upload Handling

### Location
```
storage/app/public/job-applications/{application_id}/{filename}
```

### Supported Types
- PDF, DOC, DOCX, JPG, PNG
- Maximum 5MB per file

### Download Endpoint
```
GET /api/job-applications/{application_id}/documents/{document_id}
```

---

## 🚀 Common API Calls

### Create Job Posting
```bash
POST /api/job-postings
{
  "store_id": 1,
  "title": "Designer",
  "department": "Design",
  "salary_min": 50000,
  "salary_max": 70000,
  "description": "Design furniture",
  "status": "Open",
  "screening_stages": [
    {"name": "Initial Review"},
    {"name": "Phone Screen"},
    {"name": "Interview"},
    {"name": "Offer"}
  ]
}
```

### Submit Application
```bash
POST /api/job-postings/{posting_id}/applications
Content-Type: multipart/form-data
{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "phone": "+63 9123456789",
  "is_internal": false,
  "documents": [files...]
}
```

### Update Status
```bash
PUT /api/job-applications/{app_id}/status
{
  "status": "Screening",
  "stage_id": 1,
  "notes": "Review passed"
}
```

### Schedule Interview
```bash
POST /api/interviews
{
  "application_id": 1,
  "interviewer_id": 2,
  "interview_date": "2026-03-20T10:00:00",
  "interview_type": "Phone Screen"
}
```

### Create Offer
```bash
POST /api/job-offers
{
  "application_id": 1,
  "salary": 65000,
  "position": "Senior Designer",
  "department": "Design",
  "start_date": "2026-04-01",
  "expiry_date": "2026-03-25"
}
```

### Accept Offer (Auto-creates Employee!)
```bash
POST /api/job-offers/{offer_id}/accept
{}

Response:
{
  "message": "Offer accepted and employee created successfully",
  "employee_id": "2026-00001"
}
```

---

## 💾 Database Tables Summary

| Table | Rows | Purpose |
|-------|------|---------|
| `job_postings` | 1-100 | Job listings |
| `job_posting_screening_stages` | 4-10 | Workflow stages |
| `job_applications` | 10-1000 | All applications |
| `application_timeline` | 20-2000 | Status history |
| `application_documents` | 5-5000 | Uploaded files |
| `interviews` | 10-500 | Interview records |
| `job_offers` | 1-100 | Offer records |

---

## 🧪 Testing Scenarios

### Scenario 1: Complete Hiring Flow
```
1. Create job posting with 4 stages
2. Submit external application
3. Update to "Screening" stage
4. Schedule 3 interviews
5. Update to "Offer" stage
6. Create job offer
7. Accept offer → AUTO: Employee 2026-00001 created
8. Verify employee exists in employees table
```

### Scenario 2: Internal Candidate
```
1. Create job posting
2. Submit application as INTERNAL (link to employee ID 5)
3. Schedule interview
4. Create offer
5. Accept offer → AUTO: Create NEW employee record
```

### Scenario 3: Rejection Path
```
1. Submit application
2. Update to "Screening"
3. Update to "Rejected"
4. Verify timeline shows all changes
5. Delete application → Files cleaned up
```

---

## ⚠️ Common Gotchas

### 1. Employee ID Generation
- ✅ Automatically handles year rollover (2026-00001, 2027-00001)
- ❌ Don't manually set employee_id outside offer acceptance
- ⚡ Happens in transaction - if fails, entire offer fails

### 2. File Uploads
- ✅ Multipart form data required
- ❌ Base64 upload not supported yet
- ⚡ Files stored in public directory - accessible via URL

### 3. Permission Checks
- ✅ All endpoints check permissions
- ❌ Guest users get 403 Forbidden
- ⚡ Admin users bypass most checks (if role configured)

### 4. Status Transitions
- ✅ Any status can transition to "Rejected"
- ❌ Cannot go backwards (Applied → Screening, NOT Screening → Applied)
- ⚡ Timeline tracks all changes

---

## 🔍 Debugging Tips

### Check Database Connection
```bash
php artisan tinker
>>> DB::connection()->getPDO()->getAttribute(PDO::ATTR_DRIVER_NAME)
=> "mysql"
```

### List All Permissions
```bash
php artisan tinker
>>> DB::table('permissions')->where('name', 'like', 'job%')->get();
```

### Check User Permissions
```php
$user = User::find(1);
$user->permissions->pluck('name'); // See all user permissions
auth()->user()->can('view-job-postings'); // Boolean check
```

### Debug API Response
```bash
php artisan tinker
>>> JobPosting::with('screeningStages')->first()->toArray();
```

### Check Job Offers in Pending State
```bash
php artisan tinker
>>> JobOffer::where('status', 'Pending')->with('application')->get();
```

---

## 🎨 Frontend Component Usage

### JobPostingsList.vue Props
```vue
<!-- No props - loads from API -->
<JobPostingsList />

<!-- Automatically handles:
  - Search/filter
  - CRUD operations
  - Permission checks
  - Pagination
-->
```

### ScreeningPipeline.vue Props
```vue
<!-- Requires route param to be set -->
<!-- Route: /hiring/job-postings/:postingId/screening -->
<ScreeningPipeline />

<!-- Features:
  - Kanban board
  - Drag & drop ready
  - Live refresh
-->
```

### JobApplicationForm.vue Props
```vue
<!-- Requires route param to be set -->
<!-- Route: /hiring/job-postings/:postingId/apply -->
<JobApplicationForm />

<!-- Features:
  - Multi-step form
  - File upload
  - Internal/External toggle
  - Validation
-->
```

---

## 📈 Scaling Considerations

### When You Have 1000+ Applications

1. **Pagination** - Already implemented (15 per page)
2. **Caching** - Add Redis for job posting list
3. **Indexing** - Already in migration, add more as needed
4. **Async Jobs** - Consider queue for file processing
5. **Filters** - Add advanced search/filter UI

### When You Have Multiple Stores

1. **Store Filter** - Add store_id filter to responses
2. **Multi-tenant** - Already supported via store_id
3. **Permissions** - Define per-store permissions

---

## 🔗 Related Systems Integration

### With Payroll System
- Auto-populate salary field in deductions
- Trigger first payroll when hired
- Link to PayPeriod starting

### With Notifications
- Notify candidate: Application received
- Notify HR: New applicant
- Notify candidate: Interview scheduled
- Notify candidate: Offer received
- Notify employee: Welcome (when hired)

### With Attendance
- Set start date for new employee
- Activate attendance tracking
- Link to employee profile

---

## 📚 Documentation Map

| Document | Purpose |
|----------|---------|
| `JOB_HIRING_SYSTEM_IMPLEMENTATION.md` | Complete architecture & features |
| `JOB_HIRING_SETUP_GUIDE.md` | Setup, configuration, testing |
| `JOB_HIRING_SYSTEM_QUICK_REFERENCE.md` | This file - quick lookups |

---

## 🚀 What's Complete ✅

- ✅ Database schema (7 tables)
- ✅ Models with relationships
- ✅ API controllers (40+ endpoints)
- ✅ Employee ID generation service
- ✅ Auto-employee creation on offer acceptance
- ✅ File upload/download
- ✅ Status tracking & timeline
- ✅ Permission system
- ✅ Frontend pages & components
- ✅ Full documentation

---

## 🔮 What's Next

- 🚀 Database migration execution
- 🚀 Permission database seeding
- 🚀 Frontend styling/theming
- 🚀 Integration testing
- 🚀 Notification system integration
- 🚀 Analytics dashboard
- 🚀 Bulk operations (export, import)

---

## 📞 Questions?

Refer to the main documentation files:
1. `JOB_HIRING_SYSTEM_IMPLEMENTATION.md` - Architecture questions
2. `JOB_HIRING_SETUP_GUIDE.md` - Setup/configuration questions
3. Code comments in models & controllers

---

**System Ready for Development! 🎉**
