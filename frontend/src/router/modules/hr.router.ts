import type { RouteRecordRaw } from 'vue-router'

const hrPortalRoutes: RouteRecordRaw[] = [
  {
    path: '/job-portal',
    component: () => import('../../views/system/hr/JobPortal/JobPortalLayout.vue'),
    children: [
      {
        path: '',
        name: 'job-portal.index',
        component: () => import('../../views/system/hr/JobPortal/JobPortalIndex.vue'),
        meta: { title: 'Job Portal', portalGuest: true },
      },
      {
        path: 'login',
        name: 'job-portal.login',
        component: () => import('../../views/system/hr/JobPortal/JobPortalLogin.vue'),
        meta: { title: 'Applicant Login', portalGuestOnly: true },
      },
      {
        path: 'register',
        name: 'job-portal.register',
        component: () => import('../../views/system/hr/JobPortal/JobPortalRegister.vue'),
        meta: { title: 'Applicant Register', portalGuestOnly: true },
      },
      {
        path: 'verify-otp',
        name: 'job-portal.verify-otp',
        component: () => import('../../views/system/hr/JobPortal/JobPortalVerifyOtp.vue'),
        meta: { title: 'Verify Applicant Email', portalGuest: true },
      },
      {
        path: 'postings/:id',
        name: 'job-portal.detail',
        component: () => import('../../views/system/hr/JobPortal/JobPortalDetail.vue'),
        meta: { title: 'Job Details', portalAuth: true },
      },
      {
        path: 'postings/:id/apply',
        name: 'job-portal.apply',
        component: () => import('../../views/system/hr/Applicant/ApplicantApplicationCreate.vue'),
        meta: { title: 'Apply for Job', portalAuth: true },
      },
      {
        path: 'applications',
        name: 'job-portal.dashboard',
        component: () => import('../../views/system/hr/Applicant/ApplicantDashboard.vue'),
        meta: { title: 'My Applications', portalAuth: true },
      },
      {
        path: 'applications/:id',
        name: 'job-portal.applications.detail',
        component: () => import('../../views/system/hr/Applicant/ApplicantApplicationDetail.vue'),
        meta: { title: 'Application Details', portalAuth: true },
      },
    ],
  },
]

export default hrPortalRoutes
