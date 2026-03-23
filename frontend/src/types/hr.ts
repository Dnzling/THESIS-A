export interface HrApiResponse<T> {
  success: boolean
  message?: string
  data: T
}

export interface EmployeeDeductionItem {
  name: string
  code: string
  amount: number
  formatted?: string
}

export interface EmployeeDeductions {
  total_monthly: number
  total_yearly: number
  items: EmployeeDeductionItem[]
}

export interface EmployeeDetails {
  basic_info: Record<string, any>
  employment_details: Record<string, any>
  contact_info: Record<string, any>
  leave_info: Record<string, any>
  attendance: Record<string, any>
  payroll: Record<string, any>
  deductions: EmployeeDeductions
  quick_stats: Record<string, any>
}
