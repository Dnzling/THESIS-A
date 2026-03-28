<template>
  <div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">Employees</h1>
        <p class="text-sm text-slate-600">Manage your employee list and simple payroll.</p>
      </div>
      <Button label="Add Employee" icon="pi pi-plus" @click="showEmployeeDialog = true" />
    </div>

    <Card>
      <template #content>
        <DataTable :value="employees" :loading="loading" class="p-datatable-sm">
          <Column field="employee_number" header="ID" />
          <Column header="Name">
            <template #body="{ data }">
              {{ data.fname }} {{ data.lname }}
            </template>
          </Column>
          <Column field="department" header="Department" />
          <Column field="status" header="Status" />
          <Column field="email" header="Email" />
        </DataTable>
      </template>
    </Card>

    <Card ref="payrollCard">
      <template #title>Payroll</template>
      <template #content>
        <div class="text-sm text-slate-600 mb-4">
          Generate a simple payroll batch for a selected pay period. Advanced approvals are available in the full Payroll module.
        </div>
        <div class="grid gap-4 md:grid-cols-2">
          <Select
            v-model="payrollForm.payPeriodId"
            :options="payPeriods"
            optionLabel="name"
            optionValue="id"
            placeholder="Select pay period"
            class="w-full"
          />
          <Select
            v-model="payrollForm.initialStatus"
            :options="payrollStatuses"
            placeholder="Initial status"
            class="w-full"
          />
        </div>
        <div class="mt-4 flex justify-end">
          <Button label="Save Payroll Batch" icon="pi pi-check" :loading="savingPayroll" @click="submitPayroll" />
        </div>
      </template>
    </Card>

    <Dialog v-model:visible="showEmployeeDialog" header="Add Employee" :style="{ width: '520px' }" modal>
      <div class="grid gap-3">
        <div class="grid gap-3 md:grid-cols-2">
          <InputText v-model="employeeForm.fname" placeholder="First name" />
          <InputText v-model="employeeForm.lname" placeholder="Last name" />
        </div>
        <InputText v-model="employeeForm.email" placeholder="Email" />
        <Password v-model="employeeForm.password" toggleMask placeholder="Temporary password" />
        <div class="grid gap-3 md:grid-cols-2">
          <Select v-model="employeeForm.role_id" :options="roles" optionLabel="display_name" optionValue="id" placeholder="Role" />
          <Select v-model="employeeForm.department" :options="departments" editable placeholder="Department" />
        </div>
        <div class="grid gap-3 md:grid-cols-2">
          <Select v-model="employeeForm.employment_type" :options="employmentTypes" placeholder="Employment type" />
          <Select v-model="employeeForm.status" :options="statusOptions" placeholder="Status" />
        </div>
        <div class="grid gap-3 md:grid-cols-2">
          <Calendar v-model="employeeForm.hire_date" dateFormat="yy-mm-dd" showIcon placeholder="Hire date" />
          <InputNumber v-model="employeeForm.salary" mode="currency" currency="PHP" locale="en-PH" placeholder="Salary" />
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" text @click="showEmployeeDialog = false" />
        <Button label="Save" icon="pi pi-check" :loading="savingEmployee" @click="submitEmployee" />
      </template>
    </Dialog>

    <Dialog v-model:visible="responseDialog.visible" :header="responseDialog.title" :style="{ width: '420px' }" modal>
      <div class="text-sm text-slate-700">{{ responseDialog.message }}</div>
      <template #footer>
        <Button label="OK" @click="responseDialog.visible = false" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import axiosClient from '@/axios'
import Button from 'primevue/button'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Select from 'primevue/Select'
import InputNumber from 'primevue/inputnumber'

const employees = ref<any[]>([])
const roles = ref<any[]>([])
const loading = ref(false)
const savingEmployee = ref(false)
const savingPayroll = ref(false)
const showEmployeeDialog = ref(false)
const payPeriods = ref<any[]>([])
const payrollStatuses = ['draft', 'processing']

const departments = ['Sales', 'Inventory', 'Finance', 'HR', 'Operations']
const employmentTypes = ['full_time', 'part_time', 'contract', 'intern']
const statusOptions = ['active', 'on_leave', 'suspended', 'terminated']

const employeeForm = ref({
  fname: '',
  lname: '',
  email: '',
  password: '',
  role_id: null as number | null,
  department: '',
  employment_type: 'full_time',
  status: 'active',
  hire_date: '',
  salary: 0,
})

const payrollForm = ref({
  payPeriodId: null as number | null,
  initialStatus: 'draft',
})

const responseDialog = ref({
  visible: false,
  title: 'Success',
  message: '',
})

const showResponse = (title: string, message: string) => {
  responseDialog.value = { visible: true, title, message }
}

const loadEmployees = async () => {
  loading.value = true
  try {
    const response = await axiosClient.get('/api/employees')
    employees.value = response?.data?.data || []
  } catch (error: any) {
    showResponse('Error', error?.response?.data?.message || 'Failed to load employees.')
  } finally {
    loading.value = false
  }
}

const loadRoles = async () => {
  try {
    const response = await axiosClient.get('/api/store/roles')
    roles.value = response?.data?.data || response?.data || []
    if (!employeeForm.value.role_id && roles.value.length > 0) {
      employeeForm.value.role_id = roles.value[0].id
    }
  } catch (error) {
    roles.value = []
  }
}

const submitEmployee = async () => {
  savingEmployee.value = true
  try {
    await axiosClient.post('/api/employees', {
      ...employeeForm.value,
      hire_date: employeeForm.value.hire_date,
    })
    showEmployeeDialog.value = false
    showResponse('Success', 'Employee created successfully.')
    await loadEmployees()
  } catch (error: any) {
    showResponse('Failed', error?.response?.data?.message || 'Unable to save employee.')
  } finally {
    savingEmployee.value = false
  }
}

const submitPayroll = async () => {
  if (!payrollForm.value.payPeriodId) {
    showResponse('Failed', 'Please select a pay period.')
    return
  }
  savingPayroll.value = true
  try {
    await axiosClient.post('/api/payroll/generate', {
      pay_period_id: payrollForm.value.payPeriodId,
      initial_status: payrollForm.value.initialStatus,
    })
    showResponse('Success', 'Payroll batch saved.')
  } catch (error: any) {
    showResponse('Failed', error?.response?.data?.message || 'Unable to save payroll batch.')
  } finally {
    savingPayroll.value = false
  }
}

onMounted(() => {
  loadEmployees()
  loadRoles()
  loadPayPeriods()
})

const loadPayPeriods = async () => {
  try {
    const response = await axiosClient.get('/api/payroll/periods')
    payPeriods.value = response?.data?.data || response?.data || []
  } catch (error) {
    payPeriods.value = []
  }
}
</script>
