# Job Hiring Process System - Implementation Plan

## Overview
Replace manual employee creation with a structured job hiring workflow for flexible furniture store operations (small to medium).

---

## 1. Database Schema

### Tables to Create:

#### `job_postings`
- id, store_id, title, department, description, requirements, salary_min, salary_max, status (Active/Closed), created_by, created_at, updated_at

#### `job_posting_screening_stages`
- id, job_posting_id, stage_name (configurable), order, description, created_at
- Example: Initial Review → Phone Screen → Interview → Offer

#### `job_applications`
- id, job_posting_id, employee_id (nullable for external), first_name, last_name, email, phone, current_position, application_date, status (Applied/Screening/Rejected/Interview/Offer/Accepted/Declined/Hired)

#### `application_timeline`
- id, application_id, stage_id, status, changed_by, changed_at, notes, feedback

#### `application_documents`
- id, application_id, document_type (Resume/CoverLetter/ID/Certificate/Portfolio), file_path, file_size, uploaded_at

#### `interviews`
- id, application_id, interviewer_id, interview_date, interview_type (Phone/Video/In-person), feedback, score, notes, created_at

#### `job_offers`
- id, application_id, salary, position, department, start_date, benefits, status (Pending/Accepted/Declined), offer_date, expiry_date, created_at

#### `employee_deductions` (Reference existing)
- Link to generated employee after offer acceptance

---

## 2. Workflow

```
Job Posting Created
    ↓
Employee Applies (Internal/External)
    ↓
HR/Manager Reviews (Screening)
    ↓
Interview Scheduled & Conducted
    ↓
Offer Made
    ↓
Offer Accepted → Employee Created with ID: 2026-000001
    ↓
Deductions Auto-populated based on company rules
    ↓
Active Employee
```

---

## 3. Employee ID Generation

**Format**: `YYYY-XXXXX` (Year + 5-digit incremental)
- Example: `2026-00001`, `2026-00002`, etc.
- Resets annually
- Generated when: Offer Accepted + Start Date

---

## 4. Status Flow

```
Applied 
  → Screening (HR reviews)
    → Interview (Interview completed) OR Rejected
      → Offer (Candidate selected) OR Back to Screening
        → Accepted (Offer signed) OR Declined
          → Hired (Employee created on start date)
```

---

## 5. Permissions (Fixed System-wide)

- `hr.jobs.create` - Create job postings
- `hr.jobs.edit` - Edit/close job postings
- `hr.jobs.delete` - Delete job postings
- `hr.jobs.view` - View all job postings
- `hr.applications.view` - View applications
- `hr.applications.screen` - Move to screening stage
- `hr.applications.interview` - Schedule & conduct interviews
- `hr.applications.make_offer` - Create job offers
- `hr.applications.approve_hiring` - Approve candidate hiring
- `hr.applications.manage_documents` - View candidate documents
- `employees.apply_jobs` - Apply for jobs (employees)

---

## 6. Frontend Components to Build

### HR Module:
1. **Job Postings Management** - CRUD interface
2. **Screening Pipeline** - Kanban board by stages
3. **Candidate Profiles** - View applications & documents
4. **Interview Management** - Schedule, feedback, scoring
5. **Offer Management** - Create, send, track
6. **Reports** - Analytics on hiring metrics

### Employee Portal:
1. **Job Listings** - Browse available positions
2. **Apply** - Submit application with documents
3. **Application Status** - Track progress

---

## 7. Notifications

- New Application Received (to HR/Manager)
- Application Screened (to candidate)
- Interview Scheduled (to candidate)
- Offer Sent (to candidate)
- Offer Accepted (to HR/Manager)
- Employee Created (to candidate)

---

## 8. Implementation Phases

**Phase 1**: Database migration + Models + Basic API
**Phase 2**: Job Posting Management (CRUD)
**Phase 3**: Application Management + Screening Pipeline
**Phase 4**: Interview & Offer Management
**Phase 5**: Employee auto-creation + Deductions
**Phase 6**: Employee Portal & Notifications
**Phase 7**: Reports & Analytics

---

## 9. Key Features

✅ Configurable screening stages per job
✅ Multiple document uploads from candidates
✅ Interview scheduling & feedback tracking
✅ Automatic employee ID generation (2026-00001)
✅ Auto-populate deductions on offer acceptance
✅ Permission-based access control
✅ Full timeline/audit history
✅ Flexible for small to medium stores
✅ Internal employee applications supported
✅ Notifications system
