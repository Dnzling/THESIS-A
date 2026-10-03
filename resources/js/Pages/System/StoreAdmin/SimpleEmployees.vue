<template>
  <div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">Employees</h1>
        <p class="mt-1 text-sm text-slate-500">Keep a staff directory and a simple daily attendance record.</p>
      </div>
      <div class="flex gap-2">
        <Button label="Payment Records" icon="pi pi-wallet" severity="secondary" outlined size="small"
          @click="router.push('/store/employees/payments')" />
        <Button label="Add Employee" icon="pi pi-plus" @click="showAdd = true" size="small"/>
      </div>
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
          <Column field="employee_number" header="Employee ID" />
          <Column field="name" header="Employee" />
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
        <div class="border-t border-slate-200 pt-6">
        <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
          <div>
            <h2 class="text-lg font-semibold text-slate-900">Manual Daily Attendance</h2>
            <p class="text-sm text-slate-500">For employees who do not clock in. Clocked records cannot be overwritten here.</p>
          </div>
          <div class="w-56"><label class="mb-1 block text-sm text-slate-600">Date</label>
            <DatePicker v-model="attendanceDate" dateFormat="MM d, yy" showIcon fluid @date-select="loadAttendance" :maxDate="new Date()" />
          </div>
        </div>
        <DataTable :value="employees" :loading="loading || loadingAttendance" stripedRows>
          <Column field="name" header="Employee" />
          <Column field="branch" header="Branch" />
          <Column header="Status"><template #body="{ data }"><Select v-model="attendanceDraft[data.id]"
                :options="attendanceOptions" optionLabel="label" optionValue="value" placeholder="Not recorded"
                showClear class="w-44" /></template>
          </Column>
          <Column header="Note"><template #body="{ data }">
              <InputText v-model="attendanceNotes[data.id]" placeholder="Optional note" class="w-full"
                maxlength="500" />
            </template>
          </Column>
          <Column header="Action"><template #body="{ data }"><Button label="Save" size="small"
                :disabled="!attendanceDraft[data.id] || clockedAttendance[data.id]" :loading="savingAttendanceId === data.id"
                @click="saveAttendance(data.id)" /></template>
          </Column>
          <template #empty>
            <div class="py-10 text-center text-slate-500">Add an employee to start recording attendance.</div>
          </template>
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
        <div><label class="field-label">Position</label><Select v-model="form.role_id" :options="roles"
            optionLabel="display_name" optionValue="id" placeholder="Select position" fluid /></div>
        <div><label class="field-label">Branch</label><Select v-model="form.branch_id" :options="branches"
            optionLabel="name" optionValue="id" placeholder="Select branch" fluid /></div>
        <div><label class="field-label">Hire date</label>
          <DatePicker v-model="form.hire_date" dateFormat="MM d, yy" showIcon fluid />
        </div>
        <div><label class="field-label">Employment type</label><Select v-model="form.employment_type"
            :options="employmentTypes" optionLabel="label" optionValue="value" fluid /></div>
      </div>
      <template #footer><Button label="Cancel" severity="secondary" outlined @click="showAdd = false" /><Button
          label="Add Employee" :loading="savingEmployee" @click="addEmployee" /></template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import axios from '@/axios'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import DatePicker from 'primevue/datepicker'
import Dialog from 'primevue/dialog'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import Tab from 'primevue/tab'
import TabList from 'primevue/tablist'
import Tabs from 'primevue/tabs'

const router = useRouter()
const toast = useToast()
const tab = ref<'directory' | 'attendance'>('attendance')
const search = ref('')
const loading = ref(false)
const loadingAttendance = ref(false)
const savingEmployee = ref(false)
const savingAttendanceId = ref<number | null>(null)
const showAdd = ref(false)
const employees = ref<any[]>([])
const roles = ref<any[]>([])
const branches = ref<any[]>([])
const attendanceDate = ref(new Date())
const attendanceRange = ref<Date[]>([new Date(new Date().getFullYear(), new Date().getMonth(), 1), new Date()])
const attendanceSearch = ref('')
const attendanceRows = ref<any[]>([])
const loadingAttendanceRows = ref(false)
const attendanceDraft = reactive<Record<number, string | null>>({})
const attendanceNotes = reactive<Record<number, string>>({})
const clockedAttendance = reactive<Record<number, boolean>>({})
const form = reactive({ fname: '', lname: '', email: '', role_id: null as number | null, branch_id: null as number | null, hire_date: new Date(), employment_type: 'full_time' })
const attendanceOptions = [{ label: 'Present', value: 'present' }, { label: 'Absent', value: 'absent' }, { label: 'Day Off', value: 'day_off' }]
const employmentTypes = [{ label: 'Full Time', value: 'full_time' }, { label: 'Part Time', value: 'part_time' }, { label: 'Contract', value: 'contract' }, { label: 'Intern', value: 'intern' }]
const dateString = (date: Date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
const filteredEmployees = computed(() => employees.value.filter(employee => `${employee.name} ${employee.employee_number} ${employee.email}`.toLowerCase().includes(search.value.toLowerCase())))
const errorText = (error: any) => Object.values(error?.response?.data?.errors || {}).flat()[0] || error?.response?.data?.message || 'Please try again.'
const duration = (value: number) => `${Math.floor(Number(value || 0) / 60)}h ${String(Number(value || 0) % 60).padStart(2, '0')}m`
const formatClock = (value?: string) => value ? new Date(value).toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' }) : '—'
const formatRecordDate = (value?: string) => value ? new Date(`${value.slice(0, 10)}T00:00:00`).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '—'
const statusLabel = (value: string) => ({ present: 'Present', absent: 'Absent', day_off: 'Day Off' }[value] || value)

async function loadAttendanceRows() {
  loadingAttendanceRows.value = true
  try {
    const [start, end] = attendanceRange.value || []
    attendanceRows.value = (await axios.get('/api/store/simple-staff/attendances', { params: { start_date: start ? dateString(start) : undefined, end_date: end ? dateString(end) : undefined, search: attendanceSearch.value || undefined } })).data.data || []
  } catch (error: any) { toast.add({ severity: 'error', summary: 'Unable to load attendance', detail: String(errorText(error)), life: 3500 }) }
  finally { loadingAttendanceRows.value = false }
}

async function loadEmployees() {
  loading.value = true
  try { employees.value = (await axios.get('/api/store/simple-staff/employees')).data.data || [] }
  catch (error: any) { toast.add({ severity: 'error', summary: 'Unable to load employees', detail: String(errorText(error)), life: 3500 }) }
  finally { loading.value = false }
}

async function loadAttendance() {
  loadingAttendance.value = true
  try {
    const rows = (await axios.get('/api/store/simple-staff/attendances', { params: { date: dateString(attendanceDate.value) } })).data.data || []
    for (const employee of employees.value) { attendanceDraft[employee.id] = null; attendanceNotes[employee.id] = ''; clockedAttendance[employee.id] = false }
    for (const row of rows) { attendanceDraft[row.employee_id] = row.status; attendanceNotes[row.employee_id] = row.note || ''; clockedAttendance[row.employee_id] = Boolean(row.clock_in_at) }
  } catch (error: any) { toast.add({ severity: 'error', summary: 'Unable to load attendance', detail: String(errorText(error)), life: 3500 }) }
  finally { loadingAttendance.value = false }
}

async function saveAttendance(employeeId: number) {
  savingAttendanceId.value = employeeId
  try {
    await axios.put('/api/store/simple-staff/attendances', { employee_id: employeeId, attendance_date: dateString(attendanceDate.value), status: attendanceDraft[employeeId], note: attendanceNotes[employeeId] || null })
    await loadAttendanceRows()
    toast.add({ severity: 'success', summary: 'Attendance saved', life: 2500 })
  } catch (error: any) { toast.add({ severity: 'error', summary: 'Unable to save', detail: String(errorText(error)), life: 3500 }) }
  finally { savingAttendanceId.value = null }
}

async function addEmployee() {
  if (!form.fname.trim() || !form.lname.trim() || !form.email.trim() || !form.role_id || !form.branch_id) {
    toast.add({ severity: 'warn', summary: 'Complete required fields', life: 3000 }); return
  }
  savingEmployee.value = true
  try {
    await axios.post('/api/employees/invite', { ...form, hire_date: dateString(form.hire_date), simple_staff: true, status: 'active' })
    showAdd.value = false
    Object.assign(form, { fname: '', lname: '', email: '', role_id: null, hire_date: new Date() })
    await loadEmployees()
    toast.add({ severity: 'success', summary: 'Employee added', life: 2500 })
  } catch (error: any) { toast.add({ severity: 'error', summary: 'Unable to add employee', detail: String(errorText(error)), life: 4000 }) }
  finally { savingEmployee.value = false }
}

onMounted(async () => {
  const results = await Promise.allSettled([
    loadEmployees(),
    axios.get('/api/store/roles/scoped').then(r => { roles.value = r.data.data || r.data || [] }),
    axios.get('/api/branches').then(r => { branches.value = r.data.data || [] }),
  ])
  if (results[1].status === 'rejected' || results[2].status === 'rejected') {
    toast.add({ severity: 'warn', summary: 'Employee form options unavailable', detail: 'Refresh the page before adding an employee.', life: 3500 })
  }
  await loadAttendance()
  await loadAttendanceRows()
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
