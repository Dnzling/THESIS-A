<template>
  <div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <Button label="Back to Employees" icon="pi pi-arrow-left" severity="secondary" text class="mb-2" @click="router.push('/store/employees')" />
        <h1 class="text-2xl font-semibold text-slate-900">Payment Records</h1>
        <p class="mt-1 text-sm text-slate-500">A manual record of payments made to staff. This is not payroll and does not calculate wages or deductions.</p>
      </div>
      <Button label="Record Payment" icon="pi pi-plus" @click="showCreate = true" />
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
      <Card class="border border-slate-200 shadow-sm"><template #content><p class="text-sm text-slate-500">Payments recorded</p><p class="mt-2 text-2xl font-semibold text-slate-900">{{ payments.length }}</p></template></Card>
      <Card class="border border-slate-200 shadow-sm"><template #content><p class="text-sm text-slate-500">Total recorded</p><p class="mt-2 text-2xl font-semibold text-slate-900">{{ money(payments.reduce((sum, row) => sum + Number(row.amount || 0), 0)) }}</p></template></Card>
    </div>

    <Card class="border border-slate-200 shadow-sm"><template #content>
      <div class="mb-4"><IconField><InputIcon class="pi pi-search" /><InputText v-model="search" placeholder="Search employee or note" class="w-full sm:w-80" /></IconField></div>
      <DataTable :value="filteredPayments" :loading="loading" paginator :rows="10" stripedRows>
        <Column field="payment_date" header="Payment Date" sortable><template #body="{ data }">{{ displayDate(data.payment_date) }}</template></Column>
        <Column header="Employee"><template #body="{ data }"><div class="font-medium text-slate-900">{{ data.fname }} {{ data.lname }}</div><div class="text-xs text-slate-500">{{ data.employee_number }}</div></template></Column>
        <Column field="amount" header="Amount" sortable><template #body="{ data }"><span class="font-semibold text-slate-900">{{ money(data.amount) }}</span></template></Column>
        <Column field="note" header="Note"><template #body="{ data }">{{ data.note || '—' }}</template></Column>
        <template #empty><div class="py-10 text-center text-slate-500">No payment records found.</div></template>
      </DataTable>
    </template></Card>

    <Dialog v-model:visible="showCreate" modal header="Record Payment" :style="{ width: 'min(32rem, 95vw)' }">
      <div class="space-y-4">
        <div><label class="field-label">Employee</label><Select v-model="form.employee_id" :options="employees" optionLabel="name" optionValue="id" filter placeholder="Select employee" fluid /></div>
        <div><label class="field-label">Payment date</label><DatePicker v-model="form.payment_date" dateFormat="MM d, yy" showIcon fluid /></div>
        <div><label class="field-label">Amount</label><InputNumber v-model="form.amount" mode="currency" currency="PHP" locale="en-PH" :min="0" fluid /></div>
        <div><label class="field-label">Note (optional)</label><Textarea v-model="form.note" rows="3" maxlength="500" fluid /></div>
        <Message severity="secondary" :closable="false">This records a payment only. It does not create a payslip or affect payroll.</Message>
      </div>
      <template #footer><Button label="Cancel" severity="secondary" outlined @click="showCreate = false" /><Button label="Save Payment" :loading="saving" @click="savePayment" /></template>
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
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'

const router = useRouter()
const toast = useToast()
const loading = ref(false)
const saving = ref(false)
const showCreate = ref(false)
const search = ref('')
const employees = ref<any[]>([])
const payments = ref<any[]>([])
const form = reactive({ employee_id: null as number | null, payment_date: new Date(), amount: null as number | null, note: '' })
const filteredPayments = computed(() => payments.value.filter(row => `${row.fname} ${row.lname} ${row.employee_number} ${row.note || ''}`.toLowerCase().includes(search.value.toLowerCase())))
const money = (value: number | string) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value || 0))
const displayDate = (value: string) => value ? new Date(`${value}T00:00:00`).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) : '—'
const dateString = (date: Date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
const errorText = (error: any) => Object.values(error?.response?.data?.errors || {}).flat()[0] || error?.response?.data?.message || 'Please try again.'

async function loadData() {
  loading.value = true
  try {
    const [employeesResponse, paymentsResponse] = await Promise.all([axios.get('/api/store/simple-staff/employees'), axios.get('/api/store/simple-staff/payments')])
    employees.value = employeesResponse.data.data || []
    payments.value = paymentsResponse.data.data || []
  } catch (error: any) { toast.add({ severity: 'error', summary: 'Unable to load records', detail: String(errorText(error)), life: 3500 }) }
  finally { loading.value = false }
}

async function savePayment() {
  if (!form.employee_id || !form.payment_date || !form.amount || form.amount <= 0) {
    toast.add({ severity: 'warn', summary: 'Enter employee, date, and amount', life: 3000 }); return
  }
  saving.value = true
  try {
    await axios.post('/api/store/simple-staff/payments', { employee_id: form.employee_id, payment_date: dateString(form.payment_date), amount: form.amount, note: form.note || null })
    showCreate.value = false
    Object.assign(form, { employee_id: null, payment_date: new Date(), amount: null, note: '' })
    await loadData()
    toast.add({ severity: 'success', summary: 'Payment recorded', life: 2500 })
  } catch (error: any) { toast.add({ severity: 'error', summary: 'Unable to save payment', detail: String(errorText(error)), life: 3500 }) }
  finally { saving.value = false }
}

onMounted(loadData)
</script>

<style scoped>.field-label { display: block; margin-bottom: .3rem; font-size: .875rem; font-weight: 500; color: #334155; }</style>
