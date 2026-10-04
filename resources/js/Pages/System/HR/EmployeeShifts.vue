<template>
  <div class="mx-auto max-w-7xl space-y-5 px-4 py-5 text-sm sm:px-6 lg:px-8">
    <header class="flex items-center gap-3">
      <Button icon="pi pi-arrow-left" text rounded size="small" severity="secondary" aria-label="Back to shifts" @click="router.push('/hr/shifts')" />
      <div>
        <h1 class="text-xl font-semibold text-slate-900">Employee Shifts</h1>
        <p class="mt-0.5 text-xs text-slate-500">Find an employee's schedule by branch or department.</p>
      </div>
    </header>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
      <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-[minmax(16rem,1.5fr)_repeat(3,minmax(0,1fr))_auto] xl:items-center">
        <IconField class="w-full">
          <InputIcon class="pi pi-search" />
          <InputText v-model="filters.search" placeholder="Search name or employee ID" size="small" fluid />
        </IconField>
        <Select v-model="filters.branch" :options="branches" optionLabel="label" optionValue="value" placeholder="All branches" size="small" showClear fluid />
        <Select v-model="filters.department" :options="departments" optionLabel="label" optionValue="value" placeholder="All departments" size="small" showClear fluid />
        <Select v-model="filters.shiftType" :options="shiftTypeOptions" optionLabel="label" optionValue="value" placeholder="All shifts" size="small" showClear fluid />
        <Button v-if="hasFilters" label="Reset" icon="pi pi-filter-slash" severity="secondary" text size="small" @click="resetFilters" />
      </div>
      <p class="mt-3 text-xs text-slate-500">Showing {{ filteredEmployees.length }} of {{ employees.length }} employees</p>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
      <Tabs v-model:value="activeTab">
        <TabList class="border-b border-slate-100 px-4 pt-1">
          <Tab value="branches">By Branch</Tab>
          <Tab value="departments">By Department</Tab>
          <Tab value="summary">Summary</Tab>
        </TabList>
        <TabPanels class="p-4 sm:p-5">
          <TabPanel value="branches">
            <div class="mb-4">
              <h2 class="font-semibold text-slate-900">Branches</h2>
              <p class="mt-0.5 text-xs text-slate-500">Open an employee to see their full shift details.</p>
            </div>
            <EmployeeShiftGroups :groups="branchGroups" context="branch" @select="openEmployee" />
          </TabPanel>
          <TabPanel value="departments">
            <div class="mb-4">
              <h2 class="font-semibold text-slate-900">Departments</h2>
              <p class="mt-0.5 text-xs text-slate-500">Compare schedules across teams without switching branches.</p>
            </div>
            <EmployeeShiftGroups :groups="departmentGroups" context="department" @select="openEmployee" />
          </TabPanel>
          <TabPanel value="summary">
            <div v-if="filteredEmployees.length" class="grid gap-4 lg:grid-cols-2">
              <section class="rounded-2xl border border-slate-200 p-4 sm:p-5">
                <h2 class="font-semibold text-slate-900">Shift mix</h2>
                <p class="mt-0.5 text-xs text-slate-500">Employees in each shift type for the current filters</p>
                <div class="mt-5 space-y-4">
                  <div v-for="shift in shiftDistribution" :key="shift.name">
                    <div class="mb-1.5 flex items-center justify-between gap-2 text-xs">
                      <span class="font-medium text-slate-700">{{ shift.name }}</span>
                      <span class="text-slate-500">{{ shift.count }} {{ shift.count === 1 ? 'employee' : 'employees' }}</span>
                    </div>
                    <ProgressBar :value="Math.round(shift.count / filteredEmployees.length * 100)" :showValue="false" class="h-2" />
                  </div>
                </div>
              </section>
              <section class="rounded-2xl border border-slate-200 p-4 sm:p-5">
                <h2 class="font-semibold text-slate-900">Team coverage</h2>
                <p class="mt-0.5 text-xs text-slate-500">Where the filtered employees are assigned</p>
                <div class="mt-4 grid gap-5 sm:grid-cols-2">
                  <div>
                    <h3 class="mb-2 text-xs font-medium uppercase tracking-wide text-slate-500">Branches</h3>
                    <div v-for="group in branchGroups" :key="group.value" class="flex justify-between gap-2 border-b border-slate-100 py-2 text-xs last:border-b-0">
                      <span class="truncate text-slate-700">{{ group.label }}</span><strong class="text-slate-900">{{ group.employees.length }}</strong>
                    </div>
                  </div>
                  <div>
                    <h3 class="mb-2 text-xs font-medium uppercase tracking-wide text-slate-500">Departments</h3>
                    <div v-for="group in departmentGroups" :key="group.value" class="flex justify-between gap-2 border-b border-slate-100 py-2 text-xs last:border-b-0">
                      <span class="truncate text-slate-700">{{ group.label }}</span><strong class="text-slate-900">{{ group.employees.length }}</strong>
                    </div>
                  </div>
                </div>
              </section>
            </div>
            <div v-else class="rounded-2xl border border-dashed border-slate-200 py-12 text-center text-xs text-slate-500">No employees match the current filters.</div>
          </TabPanel>
        </TabPanels>
      </Tabs>
    </section>

    <Dialog v-model:visible="showEmployeeModal" modal header="Employee shift" :style="{ width: 'min(32rem, calc(100vw - 2rem))' }">
      <div v-if="selectedEmployee" class="space-y-4 text-sm">
        <div class="border-b border-slate-100 pb-4">
          <p class="font-semibold text-slate-900">{{ selectedEmployee.name }}</p>
          <p class="mt-1 text-xs text-slate-500">{{ selectedEmployee.employeeId }} · {{ selectedEmployee.departmentLabel }} · {{ selectedEmployee.branchLabel }}</p>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
          <div><p class="text-xs text-slate-500">Shift</p><Tag :value="selectedEmployee.shiftType" :severity="shiftSeverity(selectedEmployee.shiftType)" class="mt-1" /></div>
          <div><p class="text-xs text-slate-500">Status</p><Tag :value="selectedEmployee.status" :severity="selectedEmployee.status === 'Active' ? 'success' : 'secondary'" class="mt-1" /></div>
          <div><p class="text-xs text-slate-500">Hours</p><p class="mt-1 font-medium text-slate-900">{{ selectedEmployee.shiftStart }} – {{ selectedEmployee.shiftEnd }}</p></div>
          <div><p class="text-xs text-slate-500">Working days</p><p class="mt-1 font-medium text-slate-900">{{ selectedEmployee.workingDays.join(' · ') }}</p></div>
        </div>
      </div>
      <template #footer><Button label="Close" severity="secondary" outlined size="small" @click="showEmployeeModal = false" /></template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputText from 'primevue/inputtext'
import ProgressBar from 'primevue/progressbar'
import Select from 'primevue/select'
import Tab from 'primevue/tab'
import TabList from 'primevue/tablist'
import TabPanel from 'primevue/tabpanel'
import TabPanels from 'primevue/tabpanels'
import Tabs from 'primevue/tabs'
import Tag from 'primevue/tag'
import EmployeeShiftGroups from './components/EmployeeShiftGroups.vue'

const router = useRouter()
const activeTab = ref('branches')
const showEmployeeModal = ref(false)
const selectedEmployee = ref<any>(null)
const filters = ref({ search: '', branch: null as string | null, department: null as string | null, shiftType: null as string | null })

const branches = [
  { label: 'Main Branch', value: 'main' },
  { label: 'North Branch', value: 'north' },
  { label: 'South Branch', value: 'south' },
  { label: 'East Branch', value: 'east' },
]
const departments = [
  { label: 'Production', value: 'production' },
  { label: 'Warehouse', value: 'warehouse' },
  { label: 'Sales', value: 'sales' },
  { label: 'Finance', value: 'finance' },
  { label: 'HR', value: 'hr' },
  { label: 'IT', value: 'it' },
]
const shiftTypeOptions = ['Morning', 'Mid', 'Evening', 'Night'].map((value) => ({ label: `${value} Shift`, value }))
const employees = [
  { id: 1, employeeId: 'EMP-001', name: 'John Smith', department: 'production', branch: 'main', shiftType: 'Morning', shiftStart: '08:00', shiftEnd: '17:00', workingDays: ['M', 'T', 'W', 'T', 'F'], status: 'Active' },
  { id: 2, employeeId: 'EMP-002', name: 'Sarah Johnson', department: 'warehouse', branch: 'north', shiftType: 'Mid', shiftStart: '12:00', shiftEnd: '21:00', workingDays: ['M', 'T', 'W', 'T', 'F'], status: 'Active' },
  { id: 3, employeeId: 'EMP-003', name: 'Michael Chen', department: 'sales', branch: 'south', shiftType: 'Evening', shiftStart: '15:00', shiftEnd: '00:00', workingDays: ['T', 'W', 'T', 'F', 'S'], status: 'Active' },
  { id: 4, employeeId: 'EMP-004', name: 'Emily Davis', department: 'finance', branch: 'main', shiftType: 'Morning', shiftStart: '09:00', shiftEnd: '18:00', workingDays: ['M', 'T', 'W', 'T', 'F'], status: 'Active' },
  { id: 5, employeeId: 'EMP-005', name: 'James Wilson', department: 'it', branch: 'east', shiftType: 'Night', shiftStart: '22:00', shiftEnd: '07:00', workingDays: ['M', 'T', 'W', 'T', 'F'], status: 'Active' },
]

const hasFilters = computed(() => Object.values(filters.value).some(Boolean))
const filteredEmployees = computed(() => employees
  .filter((employee) => !filters.value.search || `${employee.name} ${employee.employeeId}`.toLowerCase().includes(filters.value.search.trim().toLowerCase()))
  .filter((employee) => !filters.value.branch || employee.branch === filters.value.branch)
  .filter((employee) => !filters.value.department || employee.department === filters.value.department)
  .filter((employee) => !filters.value.shiftType || employee.shiftType === filters.value.shiftType)
  .map((employee) => ({
    ...employee,
    branchLabel: branches.find((branch) => branch.value === employee.branch)?.label || employee.branch,
    departmentLabel: departments.find((department) => department.value === employee.department)?.label || employee.department,
  })))
const branchGroups = computed(() => branches.map((branch) => ({
  ...branch,
  employees: filteredEmployees.value.filter((employee) => employee.branch === branch.value),
})).filter((group) => group.employees.length > 0))
const departmentGroups = computed(() => departments.map((department) => ({
  ...department,
  employees: filteredEmployees.value.filter((employee) => employee.department === department.value),
})).filter((group) => group.employees.length > 0))
const shiftDistribution = computed(() => shiftTypeOptions.map((shift) => ({
  name: shift.value,
  count: filteredEmployees.value.filter((employee) => employee.shiftType === shift.value).length,
})).filter((shift) => shift.count > 0))

const shiftSeverity = (type: string) => ({ Morning: 'info', Mid: 'warn', Evening: 'help', Night: 'secondary' } as Record<string, string>)[type] || 'secondary'
const resetFilters = () => { filters.value = { search: '', branch: null, department: null, shiftType: null } }
const openEmployee = (employee: any) => { selectedEmployee.value = employee; showEmployeeModal.value = true }
</script>
