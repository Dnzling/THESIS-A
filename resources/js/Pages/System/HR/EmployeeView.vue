<!-- views/system/employees/EmployeeInformation.vue -->
<template>
  <div class="min-h-screen">
    <ConfirmDialog />
    <div class="mx-auto max-w-7xl px-6 py-8">
    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center h-96">
      <ProgressSpinner />
    </div>
  
    <!-- Error State -->
    <div v-else-if="error" class="rounded-2xl border border-red-200 bg-red-50 p-8 text-center">
      <i class="pi pi-exclamation-triangle text-4xl text-red-500 mb-3"></i>
      <h3 class="text-lg font-medium text-red-800 mb-2">Failed to Load Employee Data</h3>
      <p class="text-red-600 mb-4">{{ error }}</p>
      <Button label="Try Again" icon="pi pi-refresh" @click="fetchEmployeeData" severity="danger" />
    </div>
  
    <!-- Main Content -->
    <template v-else>
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <Button icon="pi pi-arrow-left" text rounded severity="secondary" @click="goBack" />
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400">Employee Profile</p>
            <h1 class="text-2xl font-semibold text-slate-900">Employee Information</h1>
            <p class="text-sm text-slate-500">Complete record, activity, and payroll summary in one view.</p>
          </div>
        </div>
        <div class="flex flex-wrap gap-2">
          <Button label="Update Role & Salary" icon="pi pi-id-card" severity="warning" outlined @click="openEditDialog" />
          <!-- <Button label="Edit" icon="pi pi-pencil" severity="info" outlined @click="openEditDialog" /> -->
          <!-- <Button label="Export" icon="pi pi-download" severity="secondary" outlined @click="exportData" /> -->
        </div>
      </div>
  
      <!-- Employee Profile Summary Card -->
      <div class="mt-6 rounded-[1.75rem] border border-slate-200 bg-white/90 p-6 shadow-sm">
        <div class="flex flex-wrap items-start gap-6">
          <div class="relative">
            <Avatar :label="getInitials(employeeInfo.basic_info?.name)" size="xlarge"
              class="bg-blue-100 text-blue-600 text-2xl font-semibold" />
            <div class="absolute -bottom-1 -right-1 h-4 w-4 rounded-full border-2 border-white bg-emerald-500"></div>
          </div>

          <div class="flex-1">
            <div class="flex flex-wrap items-center gap-3">
              <h2 class="text-2xl font-semibold text-slate-900">{{ employeeInfo.basic_info?.name || '-' }}</h2>
              <Tag :value="employeeInfo.employment_details?.status || 'Active'"
                :severity="getStatusSeverity(employeeInfo.employment_details?.status)" rounded />
            </div>
            <div class="mt-2 flex flex-wrap items-center gap-3 text-sm text-slate-500">
              <span>{{ formatLabel(employeeInfo.employment_details?.role) }}</span>
              <span class="text-slate-300">|</span>
              <span>{{ employeeInfo.basic_info?.employee_number || '-' }}</span>
              <span class="text-slate-300">|</span>
              <span>{{ formatLabel(employeeInfo.employment_details?.department) }}</span>
            </div>

            <!-- <div class="mt-5 grid gap-3 md:grid-cols-5">
              <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-4">
                <div class="text-xs text-slate-500">Join Date</div>
                <div class="mt-1 text-sm font-semibold text-slate-900">{{ formatDate(employeeInfo.employment_details?.hire_date) }}</div>
              </div>
              <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-4">
                <div class="text-xs text-slate-500">Employment</div>
                <div class="mt-1 text-sm font-semibold text-slate-900">{{ formatLabel(employeeInfo.employment_details?.type) }}</div>
              </div>
              <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-4">
                <div class="text-xs text-slate-500">Leave Balance</div>
                <div class="mt-1 text-sm font-semibold text-slate-900">{{ leaveBalance }} days</div>
              </div>
              <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-4">
                <div class="text-xs text-slate-500">Attendance Rate</div>
                <div class="mt-1 text-sm font-semibold text-slate-900">{{ attendanceRate }}%</div>
              </div>
              <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-4">
                <div class="text-xs text-slate-500">Current Shift</div>
                <div class="mt-1 text-sm font-semibold text-slate-900">{{ employeeInfo.current_shift?.shift_name || '-' }}</div>
                <div v-if="employeeInfo.current_shift?.time_range" class="text-xs text-slate-500">
                  {{ employeeInfo.current_shift.time_range }}
                </div>
                <div v-if="employeeInfo.current_shift?.covers_days_label" class="text-xs text-slate-500">
                  {{ employeeInfo.current_shift.covers_days_label }}
                </div>
              </div>
            </div> -->
          </div>
        </div>
      </div>

      <!-- Main Tabs -->
      <div class="mt-6 rounded-[1.75rem] border border-slate-200 bg-white/90 shadow-sm">
        <Tabs v-model:value="activeTab">
          <TabList class="px-6 pt-3">
            <Tab value="info">
              <div class="flex items-center gap-2">
                <span>Full Information</span>
              </div>
            </Tab>
            <Tab value="attendance">
              <div class="flex items-center gap-2">
                <span>Attendance History</span>
              </div>
            </Tab>
            <Tab value="overtime">
              <div class="flex items-center gap-2">
                <span>Overtime History</span>
              </div>
            </Tab>
            <Tab value="leave">
              <div class="flex items-center gap-2">
                <span>Leave History</span>
              </div>
            </Tab>
            <Tab value="payslip">
              <div class="flex items-center gap-2">
                <span>Payslip History</span>
              </div>
            </Tab>
            <Tab value="deductions">
              <div class="flex items-center gap-2">
                <span>Contributions</span>
              </div>
            </Tab>
          </TabList>
  
          <TabPanels class="p-6">
            <!-- FULL INFORMATION TAB -->
            <TabPanel value="info">
              <EmployeeInfoTab
                :employee-info="employeeInfo"
                @updated="fetchEmployeeData"
                @edit-id="openGovernmentIdDialog"
                @edit-card="openCreditCardDialog"
                @verify-id="verifyGovernmentIdRecord"
              />
            </TabPanel>
  
            <!-- ATTENDANCE HISTORY TAB -->
            <TabPanel value="attendance">
              <EmployeeAttendanceTab 
                :employee-id="employeeId" 
                :initial-data="employeeInfo.attendance"
                @update:attendance="handleAttendanceUpdate"
                @export="handleAttendanceExport"
                ref="attendanceTabRef"
              />
            </TabPanel>

            <TabPanel value="overtime">
              <EmployeeOvertimeTab :employee-id="employeeId" ref="overtimeTabRef" />
            </TabPanel>
  
            <!-- LEAVE HISTORY TAB -->
            <TabPanel value="leave">
              <EmployeeLeaveTab 
                :employee-id="employeeId"
                :initial-data="employeeInfo.leave_info"
                @update:leave="handleLeaveUpdate"
                @view-details="handleViewLeaveDetails"
                ref="leaveTabRef"
              />
            </TabPanel>
  
            <!-- PAYSLIP TAB -->
            <TabPanel value="payslip">
              <PayslipHistory 
                v-if="employeeInfo?.basic_info?.id" 
                :employee-id="employeeInfo.basic_info?.id"
                :employee-name="employeeInfo?.basic_info?.name" 
                @view-payslip="handleViewPayslip"
                @download-payslip="handleDownloadPayslip" 
                @print-payslip="handlePrintPayslip"
                @generate-payslip="handleGeneratePayslip" 
                @export-all="handleExportAll" 
                ref="payslipHistoryRef" 
              />
            </TabPanel>

            <TabPanel value="deductions">
              <EmployeeDeductionsTab :contributions="employeeInfo.payroll" />
            </TabPanel>
          </TabPanels>
        </Tabs>
      </div>

      <Dialog v-model:visible="showPayslipDialog" header="Payslip Details" :style="{ width: '760px' }" modal>
        <div v-if="selectedPayslip" class="space-y-4 text-sm">
          <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
              <p><span class="font-semibold text-slate-700">Employee:</span> {{ employeeInfo.basic_info?.name || '-' }}</p>
              <p><span class="font-semibold text-slate-700">Employee #:</span> {{ employeeInfo.basic_info?.employee_number || '-' }}</p>
              <p><span class="font-semibold text-slate-700">Pay Period:</span> {{ getPayPeriodLabel(selectedPayslip) }}</p>
              <p><span class="font-semibold text-slate-700">Status:</span> {{ String(selectedPayslip.status || '-').toUpperCase() }}</p>
              <p><span class="font-semibold text-slate-700">Payment Date:</span> {{ formatDate(selectedPayslip.payment_date || selectedPayslip.paid_at || null) }}</p>
              <p><span class="font-semibold text-slate-700">Payment Method:</span> {{ selectedPayslip.payment_method || '-' }}</p>
            </div>
          </div>

          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="rounded-xl border border-emerald-200 bg-emerald-50/70 p-4">
              <p class="mb-2 font-semibold text-emerald-800">Earnings</p>
              <div class="space-y-1">
                <p class="flex justify-between"><span>Base Salary</span><span>{{ formatCurrency(selectedPayslip.base_salary) }}</span></p>
                <p class="flex justify-between"><span>Overtime</span><span>{{ formatCurrency(selectedPayslip.overtime_amount) }}</span></p>
                <p class="flex justify-between"><span>Bonuses</span><span>{{ formatCurrency(selectedPayslip.bonuses_total) }}</span></p>
                <p class="flex justify-between"><span>Allowances</span><span>{{ formatCurrency(selectedPayslip.allowances_total) }}</span></p>
                <p class="flex justify-between border-t border-emerald-300 pt-1 font-semibold">
                  <span>Gross Pay</span>
                  <span>{{ formatCurrency(calculateGrossPay(selectedPayslip)) }}</span>
                </p>
              </div>
            </div>

            <div class="rounded-xl border border-rose-200 bg-rose-50/70 p-4">
              <p class="mb-2 font-semibold text-rose-800">Deductions</p>
              <div v-if="getDeductionItems(selectedPayslip).length" class="space-y-1">
                <p v-for="item in getDeductionItems(selectedPayslip)" :key="`${selectedPayslip.id}-${item.id || item.name}`" class="flex justify-between">
                  <span>{{ item.name }}</span>
                  <span>-{{ formatCurrency(item.amount) }}</span>
                </p>
              </div>
              <p v-else class="text-slate-500">No itemized deductions</p>
              <p class="mt-1 flex justify-between"><span>Tax</span><span>-{{ formatCurrency(selectedPayslip.tax_amount) }}</span></p>
              <p class="flex justify-between border-t border-rose-300 pt-1 font-semibold">
                <span>Total Deductions</span>
                <span>-{{ formatCurrency(Number(selectedPayslip.deductions_total || 0) + Number(selectedPayslip.tax_amount || 0)) }}</span>
              </p>
            </div>
          </div>

          <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
            <p class="flex items-center justify-between text-base font-semibold text-blue-900">
              <span>Net Pay</span>
              <span>{{ formatCurrency(selectedPayslip.net_salary) }}</span>
            </p>
          </div>
        </div>

        <template #footer>
          <Button label="Close" text @click="showPayslipDialog = false" />
          <Button label="Download PDF" icon="pi pi-download" severity="secondary" @click="handleDownloadPayslip(selectedPayslip)" />
          <Button label="Print" icon="pi pi-print" severity="info" @click="handlePrintPayslip(selectedPayslip)" />
        </template>
      </Dialog>

      <Dialog v-model:visible="showEditDialog" header="Edit Employee Profile" :style="{ width: '860px' }" modal>
        <div class="space-y-5">
          <Message v-if="editDialogError" severity="error" :closable="false">
            {{ editDialogError }}
          </Message>
          <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
            <span :class="activeEditStep === 0 ? 'text-slate-900' : ''">Step 1: Role and Salary</span>
            <span>•</span>
            <span :class="activeEditStep === 1 ? 'text-slate-900' : ''">Step 2: Schedule</span>
          </div>

          <div v-if="activeEditStep === 0">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-3">Role and Salary</p>
            <div class="grid gap-3 md:grid-cols-2">
              <div class="space-y-2">
                <label class="text-sm font-semibold text-slate-700">Current Role</label>
                <InputText :model-value="formatLabel(employeeInfo.employment_details?.role)" disabled class="w-full" />
              </div>
              <div class="space-y-2">
                <label class="text-sm font-semibold text-slate-700">Department *</label>
                <Select
                  v-model="editForm.department_id"
                  :options="departmentOptions"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Select department"
                  filter
                  class="w-full"
                  @change="onDepartmentChange"
                />
              </div>
              <div class="space-y-2">
                <label class="text-sm font-semibold text-slate-700">New Role *</label>
                <Select v-model="editForm.role_id" :options="filteredRoleOptions" optionLabel="label" optionValue="value" placeholder="Select role" filter class="w-full" />
              </div>
              <div class="space-y-2">
                <label class="text-sm font-semibold text-slate-700">Branch</label>
                <Select
                  v-model="editForm.branch_id"
                  :options="branchOptions"
                  optionLabel="name"
                  optionValue="id"
                  placeholder="Keep current branch"
                  filter
                  showClear
                  class="w-full"
                />
              </div>
              <div class="space-y-2">
                <label class="text-sm font-semibold text-slate-700">Pay Type</label>
                <Select v-model="editForm.pay_type" :options="payTypeOptions" optionLabel="label" optionValue="value" class="w-full" />
              </div>
              <div class="space-y-2">
                <label class="text-sm font-semibold text-slate-700">Salary</label>
                <InputNumber v-model="editForm.salary" mode="currency" currency="PHP" locale="en-PH" class="w-full" inputClass="w-full" />
              </div>
            </div>
          </div>

          <div v-else-if="activeEditStep === 1">
            <div class="mb-4 grid gap-4 lg:grid-cols-2">
              <div class="space-y-2">
                <label class="text-sm font-medium text-slate-700">Schedule Week Start</label>
                <DatePicker v-model="editWeekStart" class="w-full" fluid />
              </div>
              <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                Fill each day manually. Use 12-hour time like <code>08:00 AM</code>. Start time will auto-calculate a 9-hour shift, and the end time stays editable.
              </div>
            </div>

            <div class="overflow-x-auto">
              <table class="min-w-full border-separate border-spacing-0">
                <thead>
                  <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="border-b border-slate-200 px-3 py-2">Day</th>
                    <th class="border-b border-slate-200 px-3 py-2">Working</th>
                    <th class="border-b border-slate-200 px-3 py-2">Start</th>
                    <th class="border-b border-slate-200 px-3 py-2">End</th>
                    <th class="border-b border-slate-200 px-3 py-2">Hours</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="row in weeklyScheduleForm" :key="row.day_of_week" class="align-top">
                    <td class="border-b border-slate-100 px-3 py-3 font-medium text-slate-800">{{ row.day_label }}</td>
                    <td class="border-b border-slate-100 px-3 py-3">
                      <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input
                          :checked="row.is_off === false"
                          type="checkbox"
                          class="h-4 w-4"
                          @change="(event) => onEditWorkingToggle(row, (event.target as HTMLInputElement).checked)"
                        />
                        Working
                      </label>
                    </td>
                    <td class="border-b border-slate-100 px-3 py-3">
                      <InputText
                        :model-value="row.start_time"
                        placeholder="08:00 AM"
                        class="w-full"
                        :disabled="row.is_off"
                        @update:model-value="(value) => onEditStartTimeChange(row, String(value || ''))"
                      />
                    </td>
                    <td class="border-b border-slate-100 px-3 py-3">
                      <InputText
                        :model-value="row.end_time"
                        placeholder="05:00 PM"
                        class="w-full"
                        :disabled="row.is_off"
                        @update:model-value="(value) => onEditEndTimeChange(row, String(value || ''))"
                      />
                    </td>
                    <td class="border-b border-slate-100 px-3 py-3 text-sm text-slate-700">
                      {{ row.is_off ? 'Off' : `${Number(row.hours || 0).toFixed(2)} hrs` }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="mt-4 grid gap-3 md:grid-cols-3">
              <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm">
                <p class="text-xs uppercase tracking-wide text-slate-500">Total Weekly Hours</p>
                <p class="mt-1 text-lg font-semibold text-slate-900">{{ totalEditWeeklyHours.toFixed(2) }} hrs</p>
              </div>
              <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm">
                <p class="text-xs uppercase tracking-wide text-slate-500">Max Daily</p>
                <p class="mt-1 text-lg font-semibold text-slate-900">9 hrs</p>
              </div>
              <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm">
                <p class="text-xs uppercase tracking-wide text-slate-500">Max Weekly</p>
                <p class="mt-1 text-lg font-semibold text-slate-900">54 hrs</p>
              </div>
            </div>
          </div>
        </div>

        <template #footer>
          <Button label="Cancel" text @click="showEditDialog = false" />
          <Button v-if="activeEditStep > 0" label="Back" severity="secondary" text @click="activeEditStep -= 1" />
          <Button v-if="activeEditStep === 0" label="Next" severity="info" @click="activeEditStep = 1" />
          <Button v-else label="Save Changes" icon="pi pi-save" severity="info" :loading="savingEdit" @click="submitEditEmployee" />
        </template>
      </Dialog>

      <Dialog v-model:visible="showGovernmentIdDialog" header="Edit Government ID" :style="{ width: '720px' }" modal>
        <div class="space-y-4">
          <Message severity="info" :closable="false">
            The employee must have a recorded ID based on the store's government deduction requirements.
          </Message>
          <Message v-if="governmentIdError" severity="error" :closable="false">
            {{ governmentIdError }}
          </Message>
          <div v-if="requiredGovernmentIdLabels.length" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Required ID types</p>
            <div class="mt-2 flex flex-wrap gap-2">
              <Tag v-for="label in requiredGovernmentIdLabels" :key="label" :value="label" severity="info" rounded />
            </div>
          </div>
          <div class="grid gap-3 md:grid-cols-2">
            <div class="space-y-2 md:col-span-2">
              <label class="text-sm font-semibold text-slate-700">ID Type</label>
              <Select
                v-model="governmentIdForm.deduction_type_id"
                :options="governmentIdTypeOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="Select ID type"
                filter
                class="w-full"
              />
            </div>
            <div class="space-y-2 md:col-span-2">
              <label class="text-sm font-semibold text-slate-700">ID Number</label>
              <InputText v-model="governmentIdForm.government_id_number" class="w-full" />
            </div>
            <div class="space-y-2 md:col-span-2">
              <label class="text-sm font-semibold text-slate-700">Upload ID Image / PDF</label>
              <input type="file" accept=".jpg,.jpeg,.png,.pdf" class="block w-full text-sm" @change="onGovernmentIdFileChange" />
              <p v-if="governmentIdFileName" class="text-xs text-slate-500">Selected file: {{ governmentIdFileName }}</p>
              <p v-if="governmentIdPreviewStatus === 'loading'" class="text-xs text-blue-600">Reading ID number from the uploaded file...</p>
              <p v-else-if="governmentIdPreviewMessage" class="text-xs text-slate-500">{{ governmentIdPreviewMessage }}</p>
            </div>
          </div>
        </div>
        <template #footer>
          <Button label="Cancel" text @click="showGovernmentIdDialog = false" />
          <Button label="Save ID" icon="pi pi-save" severity="info" :loading="savingGovernmentId" @click="submitGovernmentId" />
        </template>
      </Dialog>

      <Dialog v-model:visible="showCreditCardDialog" header="Edit Payroll Card" :style="{ width: '720px' }" modal>
        <div class="space-y-4">
        
          <Message v-if="creditCardError" severity="error" :closable="false">
            {{ creditCardError }}
          </Message>
          <div class="grid gap-3 md:grid-cols-2">
            <div class="space-y-2 md:col-span-2">
              <label class="text-sm font-semibold text-slate-700">Card Number</label>
              <InputMask
                v-model="creditCardForm.card_number"
                mask="9999 9999 9999 9999"
                placeholder="0000 0000 0000 0000"
                class="w-full"
                @update:model-value="onCreditCardNumberChange"
              />

            </div>
            <div class="space-y-2">
              <label class="text-sm font-semibold text-slate-700">Card Brand</label>
              <InputText :model-value="detectedCreditCardTypeLabel || 'Unknown'" disabled class="w-full" />
            </div>
            <div class="space-y-2">
              <label class="text-sm font-semibold text-slate-700">Status</label>
              <Select v-model="creditCardForm.status" :options="creditCardStatusOptions" optionLabel="label" optionValue="value" class="w-full" />
            </div>
            <div class="space-y-2">
              <label class="text-sm font-semibold text-slate-700">Expiration Date</label>
              <InputMask v-model="creditCardForm.expiration_date" mask="99/9999" placeholder="MM/YYYY" class="w-full" />
            </div>
            <div class="space-y-2">
              <label class="text-sm font-semibold text-slate-700">Security Code</label>
              <InputMask v-model="creditCardForm.security_code" mask="9999" placeholder="CVV" class="w-full" />
            </div>
          </div>
        </div>
        <template #footer>
          <Button label="Cancel" text @click="showCreditCardDialog = false" />
          <Button label="Save Card" icon="pi pi-save" severity="info" :loading="savingCreditCard" @click="submitCreditCard" />
        </template>
      </Dialog>
    </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import hrService from '../../../services/hr.services'
import type { EmployeeDetails } from '../../../types/hr'
import ConfirmDialog from 'primevue/confirmdialog'
import InputNumber from 'primevue/inputnumber'
import InputMask from 'primevue/inputmask'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'

// Import tab components
import EmployeeInfoTab from './components/tabs/EmployeeInfoTab.vue'
import EmployeeAttendanceTab from './components/tabs/EmployeeAttendanceTab.vue'
import EmployeeLeaveTab from './components/tabs/EmployeeLeaveTab.vue'
import PayslipHistory from './components/tabs/EmployeePayrollTab.vue'
import EmployeeDeductionsTab from './components/tabs/EmployeeDeductionsTab.vue'
import EmployeeOvertimeTab from './components/tabs/EmployeeOvertimeTab.vue'

const router = useRouter()
const route = useRoute()
const toast = useToast()
const confirm = useConfirm()
const employeeId = route.params.id as string

// Refs for child components
const attendanceTabRef = ref<InstanceType<typeof EmployeeAttendanceTab> | null>(null)
const overtimeTabRef = ref<InstanceType<typeof EmployeeOvertimeTab> | null>(null)
const leaveTabRef = ref<InstanceType<typeof EmployeeLeaveTab> | null>(null)
const payslipHistoryRef = ref<InstanceType<typeof PayslipHistory> | null>(null)

// Loading states
const loading = ref(false)
const error = ref('')
const showPayslipDialog = ref(false)
const selectedPayslip = ref<any | null>(null)
const roleOptions = ref<{ label: string; value: number; department?: string }[]>([])
const departmentOptions = ref<{ label: string; value: number; name?: string }[]>([])
const branchOptions = ref<{ id: number; name: string }[]>([])
const showEditDialog = ref(false)
const savingEdit = ref(false)
const editDialogError = ref('')
const activeEditStep = ref(0)
const shiftOptions = ref<{ label: string; value: number; daysLabel: string }[]>([])
const showGovernmentIdDialog = ref(false)
const showCreditCardDialog = ref(false)
const savingGovernmentId = ref(false)
const savingCreditCard = ref(false)
const governmentIdFile = ref<File | null>(null)
const governmentIdFileName = ref('')
const governmentIdPreviewStatus = ref<'idle' | 'loading' | 'ready' | 'error'>('idle')
const governmentIdPreviewMessage = ref('')
const governmentIdError = ref('')
const governmentIdTypeOptions = ref<{ label: string; value: string }[]>([])
const creditCardError = ref('')
const creditCardForm = ref({
  card_number: '',
  card_type: 'payroll',
  expiration_date: '',
  security_code: '',
  status: 'active',
})
const creditCardStatusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Pending', value: 'pending' },
  { label: 'Inactive', value: 'inactive' },
]
const detectedCreditCardTypeLabel = computed(() => detectCardType(creditCardForm.value.card_number)?.label || 'Unknown')
const requiredGovernmentIdTypes = computed(() => governmentIdTypeOptions.value.map((item) => item.value).filter(Boolean))
const requiredGovernmentIdLabels = computed(() => governmentIdTypeOptions.value.map((item) => item.label))
const selectedGovernmentIdOption = computed(() => governmentIdTypeOptions.value.find((item) => String(item.value) === String(governmentIdForm.value.deduction_type_id || '')) || null)

// State
const activeTab = ref('info')
const employeeInfo = ref<EmployeeDetails | any>({
  basic_info: {},
  employment_details: {},
  contact_info: {},
  leave_info: {},
  attendance: {},
  payroll: {},
  deductions: {},
  quick_stats: {}
})

const editForm = ref({
  fname: '',
  lname: '',
  phone: '',
  address: '',
  branch_id: null as number | null,
  department_id: null as number | null,
  pay_type: 'monthly',
  salary: 0,
  shift_id: null as number | null,
  shift_effective_date: new Date(),
  shift_change_reason: '',
})
const editWeekStart = ref<Date | null>(new Date())
const weeklyScheduleForm = ref<Array<{
  day_of_week: string
  day_label: string
  shift_id: number | null
  start_time: string
  end_time: string
  is_off: boolean
  effective_from: string
  effective_to: string
  notes: string
  hours: number
}>>([])
const payTypeOptions = [
  { label: 'Monthly', value: 'monthly' },
  { label: 'Hourly', value: 'hourly' },
  { label: 'Hybrid', value: 'hybrid' },
]
const filteredRoleOptions = computed(() => {
  if (!editForm.value.department_id) return roleOptions.value
  return roleOptions.value.filter((role) => {
    const departmentName = (role.department || '').toLowerCase()
    return departmentName && departmentName === String(selectedDepartmentName.value || '').toLowerCase()
  })
})
const selectedDepartmentName = computed(() => {
  const selected = departmentOptions.value.find((department) => Number(department.value) === Number(editForm.value.department_id || 0))
  return selected?.name || ''
})
const selectedRoleDepartment = computed(() => {
  const selected = roleOptions.value.find((role) => Number(role.value) === Number(editForm.value.role_id || 0))
  return selected?.department || selectedDepartmentName.value || employeeInfo.value?.employment_details?.department || ''
})

// Computed Properties
const leaveBalance = computed(() => {
  return employeeInfo.value.leave_info?.summary?.total_remaining || 0
})

const attendanceRate = computed(() => {
  return employeeInfo.value.quick_stats?.attendance_rate || 0
})

const currentShiftId = computed(() => {
  return employeeInfo.value?.current_shift?.shift_id || null
})

const isShiftChangePending = computed(() => {
  return !!editForm.value.shift_id && Number(editForm.value.shift_id) !== Number(currentShiftId.value || 0)
})

const selectedShiftOption = computed(() => {
  return shiftOptions.value.find((option) => Number(option.value) === Number(editForm.value.shift_id || 0)) || null
})

const totalEditWeeklyHours = computed(() => weeklyScheduleForm.value.reduce((sum, row) => sum + Number(row.hours || 0), 0))

const detectMatchingShift = (start?: string, end?: string) => {
  const normalizedStart = String(start || '').trim()
  const normalizedEnd = String(end || '').trim()
  if (!normalizedStart || !normalizedEnd) return null

  return shiftOptions.value.find((shift: any) => {
    const shiftStart = formatShiftTime(shift.start_time)
    const shiftEnd = formatShiftTime(shift.end_time)
    return shiftStart === normalizedStart && shiftEnd === normalizedEnd
  }) || null
}

// API Functions
const fetchEmployeeData = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await hrService.getEmployeeDetails(employeeId)

    if (response.success) {
      employeeInfo.value = response.data
    }
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Failed to fetch employee data'
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.value,
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const loadRoles = async () => {
  try {
    const response = await hrService.api.get('/api/store/roles/store-specific')
    const roles = response?.data?.data || response?.data || response || []
    roleOptions.value = roles.map((role: any) => ({
      label: role.display_name || role.name || `Role ${role.id}`,
      value: role.id,
      department: role.department || role.department_name || role.department_label || '',
    }))
  } catch (err) {
    console.error('Failed to load roles', err)
  }
}

const loadBranches = async () => {
  try {
    const response = await hrService.getBranches()
    const raw = response?.data || response || []
    branchOptions.value = Array.isArray(raw) ? raw : []
  } catch (err) {
    console.error('Failed to load branches', err)
  }
}

const loadDepartments = async () => {
  try {
    const response = await hrService.api.get('/api/departments-options')
    const raw = response?.data?.data || response?.data || []
    const items = Array.isArray(raw) ? raw : []
    departmentOptions.value = items.map((department: any) => ({
      label: department.name,
      value: Number(department.id),
      name: department.name,
    }))
  } catch (err) {
    console.error('Failed to load departments', err)
  }
}

const loadRolesByDepartment = async () => {
  try {
    const params: Record<string, any> = {}
    if (editForm.value.department_id) {
      params.department_id = editForm.value.department_id
    }
    const response = await hrService.api.get('/api/store/roles/scoped-by-department', { params })
    const roles = response?.data?.data || response?.data || response || []
    const items = Array.isArray(roles) ? roles : []
    roleOptions.value = items.map((role: any) => ({
      label: role.display_name || role.name || `Role ${role.id}`,
      value: Number(role.id),
      department: role.department || role.department_name || role.department_label || selectedDepartmentName.value || '',
    }))
  } catch (err) {
    console.error('Failed to load scoped roles', err)
  }
}

const loadGovernmentIdTypes = async () => {
  try {
    const response = await hrService.api.get('/api/deductions/deduction-types', { params: { category: 'government', is_active: true } })
    const raw = response?.data?.data || response?.data || []
    const items = Array.isArray(raw) ? raw : []
    governmentIdTypeOptions.value = items
      .filter((item: any) => item?.is_mandatory !== false)
      .map((item: any) => ({
        label: item.name || item.code || 'Government ID',
        value: Number(item.id || 0),
      }))
  } catch (err) {
    console.error('Failed to load government ID types', err)
  }
}

const loadShifts = async () => {
  try {
    const response = await hrService.getShifts({ per_page: 200 })
    const payload = response?.data?.data || response?.data || response || []
    shiftOptions.value = payload.map((shift: any) => ({
      label: `${shift.name} (${formatShiftTime(shift.start_time)} - ${formatShiftTime(shift.end_time)})`,
      value: Number(shift.id),
      daysLabel: formatShiftDays(shift.week_days),
    }))
  } catch (err) {
    console.error('Failed to load shifts', err)
  }
}

// Helper functions
const getInitials = (name: string) => {
  if (!name) return ''
  return name.split(' ').map(n => n[0]).join('').toUpperCase()
}

const formatDate = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
}

const formatLabel = (value: string | null | undefined) => {
  if (!value) return '-'
  return value
    .toString()
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase())
}

const formatValidationError = (err: any, fallback = 'Validation failed.') => {
  const errors = err?.response?.data?.errors
  const message = err?.response?.data?.message

  if (errors && typeof errors === 'object') {
    const messages = Array.from(new Set(Object.entries(errors)
      .flatMap(([field, value]) => {
        if (Array.isArray(value)) {
          return value.map((item) => `${formatLabel(field)}: ${String(item)}`)
        }
        return [`${formatLabel(field)}: ${String(value)}`]
      })
      .map((item) => item.trim())
      .filter(Boolean)))
    if (messages.length) return messages.join(' ')
  }

  if (message) return message
  return fallback
}

const formatCurrency = (value: number | string | null | undefined) => {
  const amount = typeof value === 'string' ? parseFloat(value) : Number(value || 0)
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(Number.isFinite(amount) ? amount : 0)
}

const formatShiftTime = (value: string | null | undefined) => {
  if (!value) return '-'
  const raw = String(value).trim()
  const normalized = raw.toUpperCase()

  const twelveHourMatch = normalized.match(/^(\d{1,2}):(\d{2})\s*([AP]M)$/)
  if (twelveHourMatch) {
    const hours = Number(twelveHourMatch[1])
    const minutes = twelveHourMatch[2]
    const period = twelveHourMatch[3]
    if (Number.isFinite(hours) && hours >= 1 && hours <= 12) {
      return `${String(hours).padStart(2, '0')}:${minutes} ${period}`
    }
  }

  const twentyFourMatch = raw.match(/^(\d{1,2}):(\d{2})(?::\d{2})?$/)
  if (twentyFourMatch) {
    const hours24 = Number(twentyFourMatch[1])
    const minutes = twentyFourMatch[2]
    if (!Number.isNaN(hours24)) {
      const period = hours24 >= 12 ? 'PM' : 'AM'
      const hours12 = ((hours24 + 11) % 12) + 1
      return `${String(hours12).padStart(2, '0')}:${minutes} ${period}`
    }
  }

  const date = new Date(raw)
  if (!Number.isNaN(date.getTime())) {
    return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true })
  }

  return raw
}

const formatShiftDays = (days: string[] | string | null | undefined) => {
  let dayList: string[] = []
  if (Array.isArray(days)) {
    dayList = days
  } else if (typeof days === 'string' && days.trim()) {
    try {
      const parsed = JSON.parse(days)
      dayList = Array.isArray(parsed) ? parsed : []
    } catch {
      dayList = []
    }
  }

  if (!dayList.length) return 'No days set'
  return dayList
    .map((day) => day.charAt(0).toUpperCase() + day.slice(1))
    .join(', ')
}

const toIsoDate = (value: Date | string) => {
  if (!value) return ''
  const date = value instanceof Date ? value : new Date(value)
  if (Number.isNaN(date.getTime())) return ''
  return date.toISOString().slice(0, 10)
}

const addDays = (value: Date | string, days: number) => {
  const date = value instanceof Date ? new Date(value) : new Date(value)
  date.setDate(date.getDate() + days)
  return date
}

const calculateGrossPay = (payslip: any) => {
  return Number(payslip?.base_salary || 0)
    + Number(payslip?.overtime_amount || 0)
    + Number(payslip?.bonuses_total || 0)
    + Number(payslip?.allowances_total || 0)
}

const getDeductionItems = (payslip: any) => {
  const deductionItems = Array.isArray(payslip?.items?.deductions) ? payslip.items.deductions : []
  return deductionItems
}

const getPayPeriodLabel = (payslip: any) => {
  const name = payslip?.pay_period?.name
  const start = payslip?.pay_period?.start_date
  const end = payslip?.pay_period?.end_date
  if (name) return name
  if (start && end) return `${formatDate(start)} - ${formatDate(end)}`
  return '-'
}

const getStatusSeverity = (status: string) => {
  const map: Record<string, string> = {
    'active': 'success',
    'on_leave': 'info',
    'suspended': 'warning',
    'terminated': 'danger'
  }
  return map[status?.toLowerCase()] || 'info'
}

// Navigation
const goBack = () => {
  router.push('/hr/employees')
}

const openEditDialog = () => {
  activeEditStep.value = 0
  editWeekStart.value = new Date()
  editForm.value = {
    fname: employeeInfo.value?.basic_info?.first_name || '',
    lname: employeeInfo.value?.basic_info?.last_name || '',
    phone: employeeInfo.value?.contact_info?.phone || '',
    address: employeeInfo.value?.contact_info?.address || '',
    branch_id: employeeInfo.value?.employment_details?.branch_id || null,
    department_id: departmentOptions.value.find((department) => department.name === employeeInfo.value?.employment_details?.department)?.value || null,
    pay_type: employeeInfo.value?.employment_details?.pay_type || 'monthly',
    salary: Number(employeeInfo.value?.employment_details?.monthly_salary || 0),
    shift_id: employeeInfo.value?.current_shift?.shift_id || null,
    shift_effective_date: new Date(),
    shift_change_reason: '',
  }
  const existingWeekly = Array.isArray(employeeInfo.value?.weekly_schedule) ? employeeInfo.value.weekly_schedule : []
  const byDay = new Map(existingWeekly.map((row: any) => [String(row.day_of_week || '').toLowerCase(), row]))
  weeklyScheduleForm.value = [
    { day_of_week: 'monday', day_label: 'Monday', shift_id: null, start_time: '', end_time: '', is_off: true, effective_from: '', effective_to: '', notes: '', hours: 0 },
    { day_of_week: 'tuesday', day_label: 'Tuesday', shift_id: null, start_time: '', end_time: '', is_off: true, effective_from: '', effective_to: '', notes: '', hours: 0 },
    { day_of_week: 'wednesday', day_label: 'Wednesday', shift_id: null, start_time: '', end_time: '', is_off: true, effective_from: '', effective_to: '', notes: '', hours: 0 },
    { day_of_week: 'thursday', day_label: 'Thursday', shift_id: null, start_time: '', end_time: '', is_off: true, effective_from: '', effective_to: '', notes: '', hours: 0 },
    { day_of_week: 'friday', day_label: 'Friday', shift_id: null, start_time: '', end_time: '', is_off: true, effective_from: '', effective_to: '', notes: '', hours: 0 },
    { day_of_week: 'saturday', day_label: 'Saturday', shift_id: null, start_time: '', end_time: '', is_off: true, effective_from: '', effective_to: '', notes: '', hours: 0 },
    { day_of_week: 'sunday', day_label: 'Sunday', shift_id: null, start_time: '', end_time: '', is_off: true, effective_from: '', effective_to: '', notes: '', hours: 0 },
  ].map((row) => {
    const existing = byDay.get(row.day_of_week)
    return existing ? {
      ...row,
      shift_id: existing.shift_id ?? null,
      start_time: formatShiftTime(existing.start_time || existing.shift?.start_time || ''),
      end_time: formatShiftTime(existing.end_time || existing.shift?.end_time || ''),
      is_off: Boolean(existing.is_off),
      effective_from: existing.effective_from || '',
      effective_to: existing.effective_to || '',
      notes: existing.notes || '',
      hours: existing.is_off ? 0 : calculateHours(
        formatShiftTime(existing.start_time || existing.shift?.start_time || ''),
        formatShiftTime(existing.end_time || existing.shift?.end_time || '')
      ),
    } : row
  })
  weeklyScheduleForm.value.forEach((row, index) => {
    const effectiveDate = addDays(editWeekStart.value || new Date(), index)
    row.effective_from = row.effective_from || toIsoDate(effectiveDate)
    row.effective_to = row.effective_to || ''
  })
  showEditDialog.value = true
}

const onDepartmentChange = async () => {
  editForm.value.role_id = null
  await loadRolesByDepartment()
}

const parseTimeToMinutes = (value?: string) => {
  if (!value) return null
  const raw = String(value).trim().toUpperCase()
  const match = raw.match(/^(\d{1,2}):(\d{2})\s*([AP]M)?$/)
  if (!match) return null
  let hours = Number(match[1])
  const minutes = Number(match[2])
  const meridiem = match[3]
  if (Number.isNaN(hours) || Number.isNaN(minutes) || minutes > 59) return null

  if (meridiem) {
    if (hours < 1 || hours > 12) return null
    if (meridiem === 'AM') hours = hours === 12 ? 0 : hours
    if (meridiem === 'PM') hours = hours === 12 ? 12 : hours + 12
  } else if (hours > 23) {
    return null
  }

  return hours * 60 + minutes
}

const formatMinutesToTime12h = (minutesTotal: number) => {
  const normalized = ((minutesTotal % 1440) + 1440) % 1440
  const hours24 = Math.floor(normalized / 60)
  const minutes = normalized % 60
  const period = hours24 >= 12 ? 'PM' : 'AM'
  const hours12 = ((hours24 + 11) % 12) + 1
  return `${String(hours12).padStart(2, '0')}:${String(minutes).padStart(2, '0')} ${period}`
}

const calculateHours = (start?: string, end?: string) => {
  const startMinutes = parseTimeToMinutes(start)
  const endMinutes = parseTimeToMinutes(end)
  if (startMinutes === null || endMinutes === null) return 0
  const diff = endMinutes - startMinutes
  return diff > 0 ? diff / 60 : 0
}

const onEditStartTimeChange = (row: any, value: string) => {
  const formattedValue = formatShiftTime(value)
  row.start_time = formattedValue
  const startMinutes = parseTimeToMinutes(formattedValue)
  if (startMinutes === null) {
    row.end_time = ''
    row.hours = 0
    return
  }

  row.is_off = false
  row.end_time = formatMinutesToTime12h(startMinutes + 9 * 60)
  row.hours = 9
}

const onEditEndTimeChange = (row: any, value: string) => {
  row.end_time = formatShiftTime(value)
  row.is_off = false
  row.hours = calculateHours(row.start_time, row.end_time)
}

const onEditWorkingToggle = (row: any, checked: boolean) => {
  row.is_off = !checked
  if (checked) {
    const defaultStart = formatShiftTime(row.start_time || '09:00 AM')
    row.start_time = defaultStart
    const startMinutes = parseTimeToMinutes(defaultStart)
    row.end_time = startMinutes === null ? '06:00 PM' : formatMinutesToTime12h(startMinutes + 9 * 60)
    row.hours = calculateHours(row.start_time, row.end_time) || 9
    return
  }

  row.start_time = ''
  row.end_time = ''
  row.hours = 0
}

const openGovernmentIdDialog = () => {
  governmentIdError.value = ''
  governmentIdPreviewStatus.value = 'idle'
  governmentIdPreviewMessage.value = ''
  governmentIdFileName.value = ''
  governmentIdForm.value = {
    deduction_type_id: null,
    government_id_number: employeeInfo.value?.employment_details?.government_id_number || '',
  }
  governmentIdFile.value = null
  showGovernmentIdDialog.value = true
}

const openCreditCardDialog = () => {
  creditCardError.value = ''
  const existingCard = employeeInfo.value?.credit_card || {}
  creditCardForm.value = {
    card_number: formatCardNumber(existingCard.card_number || ''),
    card_type: existingCard.card_type || detectCardType(existingCard.card_number || '')?.value || 'payroll',
    expiration_date: existingCard.expiry_label || (existingCard.expiration_month && existingCard.expiration_year ? `${existingCard.expiration_month}/${existingCard.expiration_year}` : ''),
    security_code: existingCard.security_code || '',
    status: existingCard.status || 'active',
  }
  showCreditCardDialog.value = true
}

const governmentIdForm = ref({
  deduction_type_id: null as number | null,
  government_id_number: '',
})

const onGovernmentIdFileChange = (event: Event) => {
  const input = event.target as HTMLInputElement
  governmentIdFile.value = input.files?.[0] || null
  governmentIdFileName.value = governmentIdFile.value?.name || ''
  governmentIdPreviewMessage.value = ''

  if (!governmentIdFile.value) {
    governmentIdPreviewStatus.value = 'idle'
    return
  }

  governmentIdPreviewStatus.value = 'loading'
  governmentIdForm.value.government_id_number = ''

  const formData = new FormData()
  formData.append('id_document', governmentIdFile.value)

  hrService.api.post('/api/employees/id-preview', formData)
    .then((response) => {
      const extracted = String(response?.data?.data?.likely_id_number || '').trim()
      if (extracted) {
        governmentIdForm.value.government_id_number = extracted
        governmentIdPreviewMessage.value = `Detected ID number: ${extracted}. Please confirm or edit it before saving.`
        governmentIdPreviewStatus.value = 'ready'
      } else {
        governmentIdPreviewMessage.value = 'No clear ID number was detected. Please enter it manually.'
        governmentIdPreviewStatus.value = 'error'
      }
    })
    .catch((err: any) => {
      governmentIdPreviewMessage.value = err?.response?.data?.message || 'Unable to read the ID number from the uploaded file.'
      governmentIdPreviewStatus.value = 'error'
    })
}

const onCreditCardNumberChange = () => {
  creditCardForm.value.card_type = detectCardType(creditCardForm.value.card_number)?.value || 'payroll'
}

const submitGovernmentId = async () => {
  governmentIdError.value = ''

  if (!governmentIdForm.value.deduction_type_id) {
    governmentIdError.value = 'Please choose a government ID type.'
    return
  }

  if (!governmentIdForm.value.government_id_number || !governmentIdFile.value) {
    governmentIdError.value = 'ID number and ID file are required.'
    return
  }

  savingGovernmentId.value = true
  try {
    const formData = new FormData()
    formData.append('deduction_type_id', String(governmentIdForm.value.deduction_type_id))
    formData.append('government_id_type', selectedGovernmentIdOption.value?.label || '')
    formData.append('government_id_number', governmentIdForm.value.government_id_number)
    formData.append('id_document', governmentIdFile.value)
    formData.append('_method', 'PUT')
    const response = await hrService.api.post(`/api/employees/${employeeId}`, formData)
    if (response?.data?.success) {
      toast.add({ severity: 'success', summary: 'Saved', detail: 'Government ID updated successfully.', life: 2800 })
      showGovernmentIdDialog.value = false
      await fetchEmployeeData()
    }
  } catch (err: any) {
    governmentIdError.value = formatValidationError(err, 'Unable to save ID.')
  } finally {
    savingGovernmentId.value = false
  }
}

const submitCreditCard = async () => {
  creditCardError.value = ''

  const rawCardNumber = String(creditCardForm.value.card_number || '').replace(/\D+/g, '')
  if (!rawCardNumber) {
    creditCardError.value = 'Card number is required.'
    return
  }

  const expiry = String(creditCardForm.value.expiration_date || '').replace(/\s+/g, '')
  const expiryMatch = expiry.match(/^(\d{2})\/(\d{4})$/)
  if (!expiryMatch) {
    creditCardError.value = 'Please enter a valid expiration date in MM/YYYY format.'
    return
  }

  if (!creditCardForm.value.security_code || !/^\d{3,4}$/.test(String(creditCardForm.value.security_code))) {
    creditCardError.value = 'Please enter a valid security code.'
    return
  }

  const detected = detectCardType(rawCardNumber)
  creditCardForm.value.card_type = detected?.value || 'unknown'

  savingCreditCard.value = true
  try {
    const payload = {
      card_number: rawCardNumber,
      card_type: creditCardForm.value.card_type,
      expiration_month: expiryMatch[1],
      expiration_year: expiryMatch[2],
      security_code: String(creditCardForm.value.security_code),
      status: creditCardForm.value.status,
    }

    const response = await hrService.api.post(`/api/employees/${employeeInfo.value?.basic_info?.id || employeeId}/credit-card`, payload)
    if (response?.data?.success) {
      toast.add({
        severity: 'success',
        summary: 'Saved',
        detail: 'Payroll card updated successfully.',
        life: 2800,
      })
      showCreditCardDialog.value = false
      await fetchEmployeeData()
    }
  } catch (err: any) {
    creditCardError.value = formatValidationError(err, 'Unable to save payroll card.')
  } finally {
    savingCreditCard.value = false
  }
}

const verifyGovernmentIdRecord = async (item: any) => {
  if (!item?.id) return

  savingGovernmentId.value = true
  try {
    const response = await hrService.api.post(`/api/employees/${employeeId}/government-ids/${item.id}/verify`)
    if (response?.data?.success) {
      toast.add({
        severity: 'success',
        summary: 'Verified',
        detail: 'Government ID verified successfully.',
        life: 2800,
      })
      await fetchEmployeeData()
    }
  } catch (err: any) {
    toast.add({
      severity: 'error',
      summary: 'Verification Failed',
      detail: formatValidationError(err, 'Unable to verify this ID.'),
      life: 5000,
    })
  } finally {
    savingGovernmentId.value = false
  }
}

const submitEditEmployee = async () => {
  savingEdit.value = true
  editDialogError.value = ''
  try {
    if (!validateEditWeeklySchedule()) {
      return
    }

    const payload: any = {
      fname: editForm.value.fname,
      lname: editForm.value.lname,
      phone: editForm.value.phone || null,
      address: editForm.value.address || null,
      branch_id: editForm.value.branch_id || null,
      role_id: editForm.value.role_id,
      department: selectedRoleDepartment.value,
      pay_type: editForm.value.pay_type,
      salary: Number(editForm.value.salary || 0),
    }

    const response = await hrService.api.put(`/api/employees/${employeeId}`, payload)
    if (response?.data?.success) {
      toast.add({
        severity: 'success',
        summary: 'Saved',
        detail: 'Employee details updated successfully.',
        life: 2800,
      })
      try {
        await saveWeeklySchedule()
      } catch (scheduleErr: any) {
        editDialogError.value = formatValidationError(scheduleErr, 'Employee saved, but schedule changes could not be saved.')
        return
      }
      showEditDialog.value = false
      await fetchEmployeeData()
      return
    }

    editDialogError.value = response?.data?.message || 'Unable to save employee changes.'
  } catch (err: any) {
    editDialogError.value = formatValidationError(err, 'Unable to save employee changes.')
  } finally {
    savingEdit.value = false
  }
}

const validateEditWeeklySchedule = () => {
  if (!editForm.value.role_id) {
    editDialogError.value = 'Please select a role before saving changes.'
    return false
  }

  if (!selectedRoleDepartment.value) {
    editDialogError.value = 'The selected role does not have an assigned department.'
    return false
  }

  for (const row of weeklyScheduleForm.value) {
    if (row.is_off) continue

    if (!row.start_time || !row.end_time) {
      editDialogError.value = `Please fill the start and end time for ${row.day_label}.`
      return false
    }

    const hours = calculateHours(row.start_time, row.end_time)
    if (hours <= 0) {
      editDialogError.value = `${row.day_label} needs a valid end time after the start time.`
      return false
    }

    if (hours > 9) {
      editDialogError.value = `${row.day_label} cannot exceed 9 hours.`
      return false
    }
  }

  if (totalEditWeeklyHours.value > 54) {
    editDialogError.value = 'The weekly schedule cannot exceed 54 hours.'
    return false
  }

  return true
}

const saveWeeklySchedule = async () => {
  const payload = {
    schedules: weeklyScheduleForm.value.map((row, index) => {
      const effectiveDate = addDays(editWeekStart.value || new Date(), index)
      const matchedShift = row.is_off ? null : detectMatchingShift(row.start_time, row.end_time)

      return {
        day_of_week: row.day_of_week,
        shift_id: row.is_off ? null : (matchedShift?.value || row.shift_id || null),
        start_time: row.is_off ? null : row.start_time || null,
        end_time: row.is_off ? null : row.end_time || null,
        is_off: row.is_off,
        effective_from: row.effective_from || toIsoDate(effectiveDate),
        effective_to: row.effective_to || null,
        notes: row.notes || null,
      }
    }),
  }

  const response = await hrService.api.put(`/api/employees/${employeeId}/weekly-schedule`, payload)
  if (!response?.data?.success) {
    throw new Error(response?.data?.message || 'Unable to save weekly schedule.')
  }
}

const exportData = () => {
  window.open(`/api/employees/${employeeId}/export`, '_blank')
}

// Event Handlers
const handleAttendanceUpdate = (data: any) => {
  employeeInfo.value.attendance = data
}

const handleAttendanceExport = (params: { month: number; year: number }) => {
  toast.add({
    severity: 'info',
    summary: 'Exporting',
    detail: `Exporting attendance for ${params.month}/${params.year}`,
    life: 2000
  })
}

const handleLeaveUpdate = (data: any) => {
  employeeInfo.value.leave_info = data
}

const formatCardNumber = (value: string) => {
  const digits = String(value || '').replace(/\D+/g, '').slice(0, 19)
  return digits.replace(/(.{4})/g, '$1 ').trim()
}

const detectCardType = (value: string) => {
  const digits = String(value || '').replace(/\D+/g, '')
  if (/^4\d{12}(\d{3}){0,2}$/.test(digits)) return { value: 'visa', label: 'Visa' }
  if (/^5[1-5]\d{14}$/.test(digits) || /^2(2[2-9]\d|[3-6]\d{2}|7[01]\d|720)\d{12}$/.test(digits)) return { value: 'mastercard', label: 'Mastercard' }
  if (/^3[47]\d{13}$/.test(digits)) return { value: 'amex', label: 'American Express' }
  if (/^6(?:011|5\d{2}|4[4-9]\d)\d{12}$/.test(digits)) return { value: 'discover', label: 'Discover' }
  if (/^35\d{14}$/.test(digits)) return { value: 'jcb', label: 'JCB' }
  if (/^3(?:0[0-5]|[68]\d)\d{11}$/.test(digits)) return { value: 'diners', label: 'Diners Club' }
  if (/^(?:5[0678]\d{14}|6\d{15})$/.test(digits)) return { value: 'maestro', label: 'Maestro' }
  if (/^62\d{14,17}$/.test(digits)) return { value: 'unionpay', label: 'UnionPay' }
  if (/^9\d{15,18}$/.test(digits)) return { value: 'paypal', label: 'PayPal' }
  if (!digits) return null
  return { value: 'unknown', label: 'Unknown' }
}

const handleViewLeaveDetails = (leave: any) => {
  console.log('Viewing leave details:', leave)
}

const handleViewPayslip = async (payslip: any) => {
  try {
    const response = await hrService.api.get(`/api/payrolls/${payslip.id}/payslip`)
    selectedPayslip.value = {
      ...payslip,
      ...(response?.data?.payslip || {}),
    }
  } catch {
    selectedPayslip.value = payslip
  }

  showPayslipDialog.value = true
}

const handleDownloadPayslip = async (payslip: any) => {
  if (!payslip?.id) return

  try {
    const response = await hrService.api.get(`/api/payrolls/${payslip.id}/payslip/pdf`, {
      responseType: 'blob'
    })

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    const periodLabel = String(getPayPeriodLabel(payslip)).replace(/\s+/g, '_')
    link.setAttribute('download', `payslip_${periodLabel || payslip.id}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch {
    toast.add({
      severity: 'error',
      summary: 'Download Failed',
      detail: 'Unable to download payslip PDF.',
      life: 2500,
    })
  }
}

const handlePrintPayslip = async (payslip: any) => {
  if (!payslip?.id) return

  try {
    const response = await hrService.api.get(`/api/payrolls/${payslip.id}/payslip/print`, {
      responseType: 'blob'
    })
    const file = new Blob([response.data], { type: 'application/pdf' })
    const fileURL = window.URL.createObjectURL(file)
    window.open(fileURL, '_blank')
    setTimeout(() => window.URL.revokeObjectURL(fileURL), 60_000)
  } catch {
    toast.add({
      severity: 'error',
      summary: 'Print Failed',
      detail: 'Unable to open payslip print preview.',
      life: 2500,
    })
  }
}

const handleGeneratePayslip = (year: number, month: number) => {
  toast.add({
    severity: 'info',
    summary: 'Generating',
    detail: `Generating payslip for ${month}/${year}`,
    life: 2000
  })

  setTimeout(() => {
    payslipHistoryRef.value?.refresh()
  }, 1000)
}

const handleExportAll = (year: number, month: number) => {
  console.log('Exporting all payslips for:', year, month)
}

// Watchers
watch(activeTab, (newTab) => {
  // Refresh data when switching to specific tabs
  if (newTab === 'attendance' && attendanceTabRef.value) {
    attendanceTabRef.value.refresh()
  } else if (newTab === 'overtime' && overtimeTabRef.value) {
    overtimeTabRef.value.refresh()
  } else if (newTab === 'leave' && leaveTabRef.value) {
    leaveTabRef.value.refresh()
  } else if (newTab === 'payslip' && payslipHistoryRef.value) {
    payslipHistoryRef.value.refresh()
  }
})

// Lifecycle
onMounted(() => {
  fetchEmployeeData()
  loadDepartments().then(() => loadRoles())
  loadBranches()
  loadShifts()
  loadGovernmentIdTypes()
})
</script>
