<template>
  <div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">Employees</h1>
        <p class="mt-1 text-sm text-slate-500">Keep a staff directory and a simple daily attendance record.</p>
      </div>
      <div class="flex gap-2">
        <Button label="Add Employee" icon="pi pi-plus" @click="openAddEmployee" size="small"/>
      </div>
    </div>

    <div v-if="!loading && employees.length <= 1" class="flex flex-col gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 sm:flex-row sm:items-center sm:justify-between">
      <div class="flex items-start gap-3 text-amber-900">
        <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-amber-500 text-sm font-bold text-white">!</span>
        <div><p class="text-sm font-semibold">{{ employees.length === 1 ? 'Add another employee' : 'Add your first employee' }}</p><p class="mt-1 text-xs">{{ employees.length === 1 ? 'Only the store owner is recorded. Add an employee to build your staff directory and use attendance tracking.' : 'Add an employee to build your staff directory and use attendance tracking.' }}</p></div>
      </div>
      <Button label="Add Employee" icon="pi pi-plus" size="small" class="shrink-0" @click="openAddEmployee" />
    </div>

    <Tabs v-model:value="tab">
      <TabList>
        <Tab value="attendance">Attendance</Tab>
          <Tab value="directory">Employees</Tab>
      </TabList>
    </Tabs>

    <Card v-if="tab === 'directory'" class="border border-slate-200 shadow-sm">
      <template #content>
        <div class="mb-4">
          <IconField>
            <InputIcon class="pi pi-search" />
            <InputText v-model="search" placeholder="Search staff" class="w-full sm:w-80" />
          </IconField>
        </div>
        <DataTable :value="filteredEmployees" :loading="loading" paginator :rows="10" rowHover class="text-sm" @row-click="({ data }) => router.push(`/store/employees/${data.id}`)">
          <Column field="name" header="Employee" style="min-width: 13rem">
            <template #body="{ data }">
              <div class="flex items-center gap-3">
                <Avatar :image="data.avatar_url || undefined" :label="initials(data.name)" shape="circle" size="normal" class="bg-orange-100 text-orange-700" />
                <div class="min-w-0"><p class="truncate font-medium text-slate-900">{{ data.name }}</p><p class="text-xs text-slate-500">{{ data.employee_number || 'No employee ID' }}</p></div>
              </div>
            </template>
          </Column>
          <Column header="Salary / Rate">
            <template #body="{ data }">{{ money(data.salary) }} <span class="text-xs text-slate-500">/ day</span></template>
          </Column>
          <Column field="role" header="Position"><template #body="{ data }">{{ data.role || '—' }}</template></Column>
          <Column field="branch" header="Branch"><template #body="{ data }">{{ data.branch || '—' }}</template></Column>
          <Column field="email" header="Email" />
          <Column field="status" header="Status" class="capitalize"><template #body="{ data }">
              <Tag :value="data.status" :severity="data.status === 'active' ? 'success' : 'secondary'" />
            </template>
          </Column>
          <template #empty>
            <div class="py-10 text-center text-slate-500">No employees found.</div>
          </template>
        </DataTable>
      </template>
    </Card>

    <Card v-else class="border border-slate-200 shadow-sm">
      <template #content>
        <div class="mb-6 space-y-4">
          <div><h2 class="text-lg font-semibold text-slate-900">Attendance Records</h2><p class="text-sm text-slate-500">Clocked time is recorded separately from payroll.</p></div>
          <div class="flex flex-wrap items-end gap-3">
            <div class="min-w-52 flex-1"><label class="mb-1 block text-sm text-slate-600">Search employee</label><InputText v-model="attendanceSearch" placeholder="Name or employee ID" fluid @keyup.enter="loadAttendanceRows" /></div>
            <div class="w-72"><label class="mb-1 block text-sm text-slate-600">Date range</label><DatePicker v-model="attendanceRange" selectionMode="range" dateFormat="MM d, yy" showIcon fluid @date-select="loadAttendanceRows" /></div>
            <Button label="Apply" icon="pi pi-search" outlined @click="loadAttendanceRows" />
          </div>
          <DataTable :value="attendanceRows" :loading="loadingAttendanceRows" paginator :rows="10" stripedRows>
            <Column header="Date"><template #body="{ data }">{{ formatRecordDate(data.attendance_date) }}</template></Column>
            <Column header="Employee"><template #body="{ data }"><div class="font-medium">{{ data.fname }} {{ data.lname }}</div><small class="text-slate-500">{{ data.employee_number }}</small></template></Column>
            <Column header="Clock In"><template #body="{ data }"><div>{{ formatClock(data.clock_in_at) }}</div><small v-if="data.late_minutes > 0" class="text-amber-600">Late {{ duration(data.late_minutes) }}</small></template></Column>
            <Column header="Clock Out"><template #body="{ data }"><div>{{ formatClock(data.clock_out_at) }}</div><small v-if="data.overtime_minutes > 0" class="text-orange-600">Overtime {{ duration(data.overtime_minutes) }}</small></template></Column>
            <Column header="Worked"><template #body="{ data }">{{ data.worked_minutes == null ? '—' : duration(data.worked_minutes) }}</template></Column>
            <Column header="Status"><template #body="{ data }"><Tag :value="statusLabel(data.status)" :severity="data.status === 'present' ? 'success' : 'secondary'" /></template></Column>
            <template #empty><div class="py-8 text-center text-slate-500">No attendance records match the filters.</div></template>
          </DataTable>
        </div>
      </template>
    </Card>

    <Dialog v-model:visible="showAdd" modal header="Add Employee" :style="{ width: 'min(34rem, 95vw)' }">
      <div class="space-y-4">
        <p class="text-sm text-slate-500">An account invitation will be emailed to this employee. Payroll and shifts are
          not
          configured here.</p>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="field-label">First name</label>
            <InputText v-model="form.fname" fluid />
          </div>
          <div><label class="field-label">Last name</label>
            <InputText v-model="form.lname" fluid />
          </div>
        </div>
        <div><label class="field-label">Email</label>
          <InputText v-model="form.email" type="email" fluid />
        </div>
        <div><label class="field-label">Contact number</label>
          <InputMask v-model="form.phone_number" mask="0999-999-9999" placeholder="09XX-XXX-XXXX" fluid />
          <small v-if="form.phone_number && !isValidPhone(form.phone_number)" class="text-red-600">Enter a complete Philippine mobile number starting with 09.</small>
        </div>
        <div><label class="field-label">Position</label><Select v-model="form.role_id" :options="roles" :loading="loadingEmployeeOptions"
            optionLabel="display_name" optionValue="id" placeholder="Select position" fluid /></div>
        <div><label class="field-label">Branch</label><Select v-model="form.branch_id" :options="branches" :loading="loadingEmployeeOptions"
            optionLabel="name" optionValue="id" placeholder="Select branch" fluid /></div>
        <div><label class="field-label">Hire date</label>
          <DatePicker v-model="form.hire_date" dateFormat="MM d, yy" showIcon fluid />
        </div>
        <div><label class="field-label">Employment type</label><Select v-model="form.employment_type"
            :options="employmentTypes" optionLabel="label" optionValue="value" fluid /></div>
        <div><label class="field-label">Daily salary *</label><InputNumber v-model="form.salary" mode="currency" currency="PHP" locale="en-PH" :min="0.01" fluid /></div>
        <p class="-mt-2 text-xs text-slate-500">This amount is paid per workday. Its hourly equivalent uses the store's paid-hours setting (8 hours if unset); unpaid breaks are excluded.</p>
        <div v-if="form.employment_type === 'contract'"><label class="field-label">End Contract *</label><DatePicker v-model="form.contract_end_date" :minDate="form.hire_date" dateFormat="MM d, yy" showIcon fluid /></div>
      </div>
      <template #footer><Button label="Cancel" severity="secondary" outlined @click="showAdd = false" /><Button
          label="Add Employee" :loading="savingEmployee" :disabled="savingEmployee || !isValidPhone(form.phone_number)" @click="addEmployee" /></template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import axios from '@/axios'
import Button from 'primevue/button'
import Avatar from 'primevue/avatar'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import DatePicker from 'primevue/datepicker'
import Dialog from 'primevue/dialog'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import InputMask from 'primevue/inputmask'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import Tab from 'primevue/tab'
import TabList from 'primevue/tablist'
import Tabs from 'primevue/tabs'

const router = useRouter()
const toast = useToast()
const tab = ref<'directory' | 'attendance'>('directory')
const search = ref('')
const loading = ref(false)
const savingEmployee = ref(false)
const loadingEmployeeOptions = ref(false)
const employeeOptionsLoaded = ref(false)
const attendanceLoaded = ref(false)
const showAdd = ref(false)
const employees = ref<any[]>([])
const roles = ref<any[]>([])
const branches = ref<any[]>([])
const attendanceRange = ref<Date[]>([new Date(new Date().getFullYear(), new Date().getMonth(), 1), new Date()])
const attendanceSearch = ref('')
const attendanceRows = ref<any[]>([])
const loadingAttendanceRows = ref(false)
const form = reactive({ fname: '', lname: '', email: '', phone_number: '', role_id: null as number | null, branch_id: null as number | null, hire_date: new Date(), employment_type: 'full_time', contract_end_date: null as Date | null, pay_type: 'daily', salary: null as number | null })
const employmentTypes = [{ label: 'Full Time', value: 'full_time' }, { label: 'Part Time', value: 'part_time' }, { label: 'Contract', value: 'contract' }, { label: 'Intern', value: 'intern' }]
const dateString = (date: Date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
const filteredEmployees = computed(() => employees.value.filter(employee => `${employee.name} ${employee.employee_number} ${employee.email}`.toLowerCase().includes(search.value.toLowerCase())))
const errorText = (error: any) => Object.values(error?.response?.data?.errors || {}).flat()[0] || error?.response?.data?.message || 'Please try again.'
const initials = (value: string) => value.split(/\s+/).filter(Boolean).slice(0, 2).map(part => part[0]?.toUpperCase()).join('') || 'E'
const money = (value: number | string) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value || 0))
const duration = (value: number) => `${Math.floor(Number(value || 0) / 60)}h ${String(Number(value || 0) % 60).padStart(2, '0')}m`
const formatClock = (value?: string) => value ? new Date(value).toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' }) : '—'
const formatRecordDate = (value?: string) => value ? new Date(`${value.slice(0, 10)}T00:00:00`).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '—'
const statusLabel = (value: string) => ({ present: 'Present', absent: 'Absent', day_off: 'Day Off' }[value] || value)
const isValidPhone = (value?: string | null) => !value || /^09\d{2}-\d{3}-\d{4}$/.test(value)

async function loadAttendanceRows() {
  if (loadingAttendanceRows.value) return
  loadingAttendanceRows.value = true
  try {
    const [start, end] = attendanceRange.value || []
    attendanceRows.value = (await axios.get('/api/store/simple-staff/attendances', { params: { start_date: start ? dateString(start) : undefined, end_date: end ? dateString(end) : undefined, search: attendanceSearch.value || undefined } })).data.data || []
    attendanceLoaded.value = true
  } catch (error: any) { toast.add({ severity: 'error', summary: 'Unable to load attendance', detail: String(errorText(error)), life: 3500 }) }
  finally { loadingAttendanceRows.value = false }
}

async function loadEmployees() {
  loading.value = true
  try { employees.value = (await axios.get('/api/store/simple-staff/employees')).data.data || [] }
  catch (error: any) { toast.add({ severity: 'error', summary: 'Unable to load employees', detail: String(errorText(error)), life: 3500 }) }
  finally { loading.value = false }
}

async function openAddEmployee() {
  showAdd.value = true
  if (employeeOptionsLoaded.value || loadingEmployeeOptions.value) return

  loadingEmployeeOptions.value = true
  const results = await Promise.allSettled([
    axios.get('/api/store/roles/scoped').then(response => { roles.value = response.data.data || response.data || [] }),
    axios.get('/api/branches').then(response => { branches.value = response.data.data || [] }),
  ])
  employeeOptionsLoaded.value = results.every(result => result.status === 'fulfilled')
  if (!employeeOptionsLoaded.value) {
    toast.add({ severity: 'warn', summary: 'Employee form options unavailable', detail: 'Close and reopen the form to try loading positions and branches again.', life: 3500 })
  }
  loadingEmployeeOptions.value = false
}


async function addEmployee() {
  if (!isValidPhone(form.phone_number)) return
  if (!form.fname.trim() || !form.lname.trim() || !form.email.trim() || !form.role_id || !form.branch_id || !Number(form.salary) || (form.employment_type === 'contract' && (!form.contract_end_date || form.contract_end_date < form.hire_date))) {
    toast.add({ severity: 'warn', summary: 'Complete required fields', life: 3000 }); return
  }
  savingEmployee.value = true
  try {
    await axios.post('/api/employees/invite', { ...form, pay_type: 'daily', phone_number: form.phone_number || null, hire_date: dateString(form.hire_date), contract_end_date: form.employment_type === 'contract' && form.contract_end_date ? dateString(form.contract_end_date) : null, simple_staff: true, status: 'active' })
    showAdd.value = false
    Object.assign(form, { fname: '', lname: '', email: '', phone_number: '', role_id: null, hire_date: new Date(), contract_end_date: null, employment_type: 'full_time', pay_type: 'daily', salary: null })
    await loadEmployees()
    window.dispatchEvent(new Event('setup-attention-refresh'))
    toast.add({ severity: 'success', summary: 'Employee added', life: 2500 })
  } catch (error: any) { toast.add({ severity: 'error', summary: 'Unable to add employee', detail: String(errorText(error)), life: 4000 }) }
  finally { savingEmployee.value = false }
}

onMounted(() => {
  void loadEmployees()
})

watch(tab, (value) => {
  if (value === 'attendance' && !attendanceLoaded.value) void loadAttendanceRows()
})
</script>

<style
  scoped>
  .field-label {
    display: block;
    margin-bottom: .3rem;
    font-size: .875rem;
    font-weight: 500;
    color: #334155;
  }
</style>
