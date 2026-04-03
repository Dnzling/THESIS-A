import axios from 'axios'
import axiosClient, { attachInterceptors } from '../axios'
import type { EmployeeDetails, HrApiResponse } from '../types/hr'

const portalClient = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
  withCredentials: true,
  timeout: 30000,
})

attachInterceptors(portalClient)

portalClient.interceptors.request.use((config) => {
  const token = localStorage.getItem('job_portal_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Deduplicate in-flight portal GETs to avoid duplicate requests (e.g., Vue dev/strict re-renders)
const portalInFlight = new Map<string, Promise<any>>()

const buildKey = (method: string, url: string, config?: any, data?: any) => {
  return `${method}:${url}|params=${JSON.stringify(config?.params || {})}|data=${JSON.stringify(data || {})}`
}

const portalGet = (url: string, config?: any) => {
  const key = buildKey('GET', url, config)
  if (portalInFlight.has(key)) return portalInFlight.get(key)!
  const promise = portalClient.get(url, config).finally(() => portalInFlight.delete(key))
  portalInFlight.set(key, promise)
  return promise
}

const portalPost = (url: string, data?: any, config?: any) => {
  const key = buildKey('POST', url, config, data)
  if (portalInFlight.has(key)) return portalInFlight.get(key)!
  const promise = portalClient.post(url, data, config).finally(() => portalInFlight.delete(key))
  portalInFlight.set(key, promise)
  return promise
}

export interface JobPostingStage {
  id?: number
  name?: string
  stage_name?: string
  description?: string | null
  order?: number
}

export interface JobPosting {
  id?: number
  store_id?: number
  role_id?: number | null
  title: string
  description: string
  department: string
  salary_min: number
  salary_max: number
  requirements?: string[] | string | null
  benefits?: string[] | string | null
  status: 'Open' | 'Closed' | 'On Hold'
  screening_stages?: JobPostingStage[]
  screeningStages?: JobPostingStage[]
  applications?: any[]
  store?: {
    id: number
    store_name?: string
    business_name?: string
    name?: string
  }
  role?: {
    id: number
    name?: string
    display_name?: string
    code?: string
  }
}

export interface JobApplication {
  id?: number
  job_posting_id: number
  user_id?: number | null
  first_name: string
  last_name: string
  email: string
  phone: string
  current_position?: string | null
  current_company?: string | null
  employee_id?: number | null
  status?: 'Applied' | 'Screening' | 'Interview' | 'Offer' | 'Accepted' | 'Hired' | 'Rejected'
  jobPosting?: JobPosting
  documents?: any[]
  interviews?: any[]
  offer?: any
  timeline?: any[]
}

export interface ApplicationStatusUpdate {
  status: JobApplication['status']
  stage_id?: number | null
  notes?: string
}

export interface PortalRegisterPayload {
  fname: string
  lname: string
  email: string
  password: string
}

export interface PortalLoginPayload {
  email: string
  password: string
}

export interface InterviewSchedulePayload {
  interview_date: string
  interview_type: string
  notes?: string
  interviewer_id?: number
}

export interface HireApplicantPayload {
  branch_id: number
  department_id: number
  role_id: number
  hire_date: string
  employment_type: 'full_time' | 'part_time' | 'contract' | 'intern'
  salary: number
  position: string
  phone?: string
  address?: string
}

export interface ApplicantProfile {
  id?: number
  first_name: string
  last_name: string
  email: string
  phone: string
  birthday: string
  city: string
  province: string
  barangay: string
  address: string
  current_position?: string | null
  current_company?: string | null
  documents?: any[]
}

export interface RejectApplicantPayload {
  reason: string
  notes?: string
}

// shared in-flight guard for commonly reused HR endpoints
let employeesInFlight: Promise<any> | null = null

const hrService = {
  normalizeUrl: (url: string) => {
    if (!url) return url
    if (url.startsWith('http://') || url.startsWith('https://')) return url
    if (url.startsWith('/')) return url
    return `/${url}`
  },
  api: {
    get: (url: string, config?: any) => axiosClient.get(hrService.normalizeUrl(url), config),
    post: (url: string, data?: any, config?: any) => axiosClient.post(hrService.normalizeUrl(url), data, config),
    put: (url: string, data?: any, config?: any) => axiosClient.put(hrService.normalizeUrl(url), data, config),
    delete: (url: string, config?: any) => axiosClient.delete(hrService.normalizeUrl(url), config),
  },

  async getJobPostings(params?: any) {
    const response = await axiosClient.get('/api/job-postings', { params })
    return response.data
  },

  async getJobPosting(id: number | string) {
    const response = await axiosClient.get(`/api/job-postings/${id}`)
    return response.data
  },

  async createJobPosting(payload: JobPosting) {
    const response = await axiosClient.post('/api/job-postings', payload)
    return response.data
  },

  async updateJobPosting(id: number, payload: Partial<JobPosting>) {
    const response = await axiosClient.put(`/api/job-postings/${id}`, payload)
    return response.data
  },

  async deleteJobPosting(id: number) {
    const response = await axiosClient.delete(`/api/job-postings/${id}`)
    return response.data
  },

  async updateScreeningStages(id: number, stages: JobPostingStage[]) {
    const response = await axiosClient.put(`/api/job-postings/${id}/screening-stages`, { stages })
    return response.data
  },

  async getJobPostingApplications(postingId: number | string, params?: any) {
    const response = await axiosClient.get(`/api/job-postings/${postingId}/applications`, { params })
    return response.data
  },

  async submitJobApplication(postingId: number | string, formData: FormData) {
    const response = await axiosClient.post(`/api/job-postings/${postingId}/applications`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return response.data
  },

  async getJobApplication(id: number | string) {
    const response = await axiosClient.get(`/api/job-applications/${id}`)
    return response.data
  },

  async updateJobApplicationStatus(id: number | string, payload: ApplicationStatusUpdate) {
    const response = await axiosClient.put(`/api/job-applications/${id}/status`, payload)
    return response.data
  },

  async deleteJobApplication(id: number | string) {
    const response = await axiosClient.delete(`/api/job-applications/${id}`)
    return response.data
  },

  async getEmployees(params?: any) {
    if (employeesInFlight) return employeesInFlight
    employeesInFlight = axiosClient.get('/api/employees', { params })
      .then((res) => res.data)
      .finally(() => { employeesInFlight = null })
    return employeesInFlight
  },

  async getEmployeeDetails(id: number | string, params?: any): Promise<HrApiResponse<EmployeeDetails>> {
    const response = await axiosClient.get(`/api/employees/${id}/details`, { params })
    return response.data
  },

  async getHrDashboardTodayStats() {
    const response = await axiosClient.get('/api/hr/dashboard/today-stats')
    return response.data
  },

  async getHrDashboardWeeklyAttendance() {
    const response = await axiosClient.get('/api/hr/dashboard/weekly-attendance')
    return response.data
  },

  async getHrDashboardMonthlySummary(params?: any) {
    const response = await axiosClient.get('/api/hr/dashboard/monthly-summary', { params })
    return response.data
  },

  async getInterviewSettings() {
    const response = await axiosClient.get('/api/store/settings/hr')
    return response.data
  },

  async updateInterviewSettings(payload: { daily_interview_limit: number }) {
    const response = await axiosClient.put('/api/store/settings/hr', payload)
    return response.data
  },

  async getHrSettings() {
    const response = await axiosClient.get('/api/store/settings/hr')
    return response.data
  },

  async updateHrSettings(payload: any) {
    const response = await axiosClient.put('/api/store/settings/hr', payload)
    return response.data
  },

  async getLeaveBalances(params?: any) {
    const response = await axiosClient.get('/api/leave-balances', { params })
    return response.data
  },

  async getEmployeeLeaveBalances(employeeId: number | string, year?: number | string) {
    const yearSegment = year ? `/${year}` : ''
    const response = await axiosClient.get(`/api/employees/${employeeId}/leave-balance${yearSegment}`)
    return response.data
  },

  async updateLeaveBalance(id: number | string, payload: any) {
    const response = await axiosClient.put(`/api/leave-balances/${id}`, payload)
    return response.data
  },

  async getDepartments(params?: any) {
    const response = await axiosClient.get('/api/departments', { params })
    return response.data
  },

  async getDepartmentOptions() {
    const response = await axiosClient.get('/api/departments-options')
    return response.data
  },

  async getRoles(params?: any) {
    const response = await axiosClient.get('/api/store/roles', { params })
    return response.data
  },

  async getBranches(params?: any) {
    const response = await axiosClient.get('/api/branches', { params })
    return response.data
  },

  async scheduleInterview(applicationId: number | string, payload: InterviewSchedulePayload) {
    const response = await axiosClient.post(`/api/job-portal/recruitment/applications/${applicationId}/schedule-interview`, payload)
    return response.data
  },

  async hireApplicant(applicationId: number | string, payload: HireApplicantPayload) {
    const response = await axiosClient.post(`/api/job-portal/recruitment/applications/${applicationId}/hire`, payload)
    return response.data
  },

  async rejectApplicant(applicationId: number | string, payload: RejectApplicantPayload) {
    const response = await axiosClient.post(`/api/job-portal/recruitment/applications/${applicationId}/reject`, payload)
    return response.data
  },

  async getShifts(params?: any) {
    const response = await axiosClient.get('/api/shifts', { params })
    return response.data
  },

  async createShift(payload: any) {
    const response = await axiosClient.post('/api/shifts', payload)
    return response.data
  },

  async createShiftScheduleBulk(payload: any) {
    const response = await axiosClient.post('/api/shift-schedules/bulk', payload)
    return response.data
  },

  async getPortalJobPostings(params?: any) {
    const response = await portalGet('/api/job-portal/postings', { params })
    return response.data
  },

  async getPortalJobPosting(id: number | string) {
    const response = await portalGet(`/api/job-portal/postings/${id}`)
    return response.data
  },

  async getPortalApplications(params?: any) {
    const response = await portalGet('/api/job-portal/applications', { params })
    return response.data
  },

  async getPortalApplication(id: number | string) {
    const response = await portalGet(`/api/job-portal/applications/${id}`)
    return response.data
  },

  async portalRegister(payload: PortalRegisterPayload) {
    const response = await portalPost('/api/job-portal/auth/register', payload)
    return response.data
  },

  async portalLogin(payload: PortalLoginPayload) {
    const response = await portalPost('/api/job-portal/auth/login', payload)
    return response.data
  },

  async portalVerifyOtp(otp: string) {
    const response = await portalPost('/api/job-portal/auth/verify-otp', { otp })
    return response.data
  },

  async portalResendOtp() {
    const response = await portalPost('/api/job-portal/auth/resend-otp')
    return response.data
  },

  async portalMe() {
    const response = await portalGet('/api/job-portal/auth/me')
    return response.data
  },

  async portalLogout() {
    const response = await portalPost('/api/job-portal/auth/logout')
    return response.data
  },

  async getApplicantApplications(params?: any) {
    return this.getPortalApplications(params)
  },

  async getApplicantApplication(id: number | string) {
    return this.getPortalApplication(id)
  },

  async applyToPortalJob(postingId: number | string, formData: FormData | Record<string, any>) {
    const isFormData = typeof FormData !== 'undefined' && formData instanceof FormData
    const response = await portalClient.post(
      `/api/job-portal/postings/${postingId}/apply`,
      formData,
      isFormData ? { headers: { 'Content-Type': 'multipart/form-data' } } : undefined
    )
    return response.data
  },

  async getApplicantProfile() {
    const response = await portalGet('/api/job-portal/profile')
    return response.data
  },

  async saveApplicantProfile(payload: ApplicantProfile) {
    const response = await portalClient.put('/api/job-portal/profile', payload)
    return response.data
  },

  async uploadApplicantProfileDocument(document: File, documentType?: string) {
    const formData = new FormData()
    formData.append('document', document)
    if (documentType) formData.append('document_type', documentType)
    const response = await portalClient.post('/api/job-portal/profile/documents', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return response.data
  },

  async deleteApplicantProfileDocument(documentId: number | string) {
    const response = await portalClient.delete(`/api/job-portal/profile/documents/${documentId}`)
    return response.data
  },

  async requestApplicantEmailChange(email: string) {
    const response = await portalClient.post('/api/job-portal/profile/email-change', { email })
    return response.data
  },

  async verifyApplicantEmailChange(otp: string) {
    const response = await portalClient.post('/api/job-portal/profile/email-verify', { otp })
    return response.data
  },

  async downloadApplicantDocument(applicationId: number | string, documentId: number | string) {
    const response = await portalClient.get(`/api/job-portal/applications/${applicationId}/documents/${documentId}`, {
      responseType: 'blob',
    })
    return response.data
  },
}

export default hrService
