<template>
  <div class="space-y-6">
    <Button label="Back to Employees" icon="pi pi-arrow-left" severity="secondary" text @click="router.push('/store/employees')" />
    <div v-if="loading" class="space-y-4"><Skeleton height="9rem" /><Skeleton height="16rem" /></div>
    <Message v-else-if="error" severity="error">{{ error }}</Message>
    <template v-else-if="employee">
      <Card class="border border-slate-200 shadow-sm"><template #content>
        <div class="flex flex-wrap items-center gap-5">
          <div class="relative h-24 w-24 shrink-0">
            <img v-if="employee.avatar_url" :src="employee.avatar_url" :alt="`${fullName} photo`" class="h-24 w-24 rounded-full border border-slate-200 object-cover" />
            <div v-else class="flex h-24 w-24 items-center justify-center rounded-full bg-blue-100 text-2xl font-semibold text-blue-700">{{ initials }}</div>
            <label class="absolute -bottom-1 -right-1 flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border-2 border-white bg-orange-500 text-white shadow" title="Upload employee photo">
              <i class="pi pi-camera" /><input type="file" accept="image/jpeg,image/png,image/webp" class="hidden" :disabled="uploading" @change="uploadPhoto" />
            </label>
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ employee.employee_number || 'Employee' }}</p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">{{ fullName }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ employee.role || 'No position' }} · {{ employee.branch || 'No branch' }}</p>
          </div>
          <Tag :value="statusLabel" :severity="employee.status === 'active' ? 'success' : 'warn'" />
          <Button label="Edit Employee" icon="pi pi-pencil" @click="openEdit" />
          <Button v-if="!employee.resignation_date" label="Resign" severity="warn" outlined size="small" @click="showResign = true" />
        </div>
        <p class="mt-4 text-xs text-slate-500">Photo: JPG, PNG, or WebP, up to 2 MB. Displayed as a circle across employee views.</p>
      </template></Card>

      <div class="grid gap-4 md:grid-cols-2">
        <Card class="border border-slate-200 shadow-sm"><template #title>Contact Information</template><template #content><div class="space-y-4 text-sm"><Info label="Email" :value="employee.email" /><Info label="Phone" :value="employee.phone_number" /></div></template></Card>
        <Card class="border border-slate-200 shadow-sm"><template #title>Employment Information</template><template #content><div class="grid grid-cols-2 gap-4 text-sm"><Info label="Position" :value="employee.role" /><Info label="Branch" :value="employee.branch" /><Info label="Employment Type" :value="employmentLabel" /><Info label="Hire Date" :value="displayDate(employee.hire_date)" /><Info v-if="employee.employment_type === 'contract'" label="End Contract" :value="displayDate(employee.contract_end_date)" /><Info v-if="employee.resignation_date" label="Resignation Date" :value="displayDate(employee.resignation_date)" /><Info v-if="employee.last_working_day" label="Last Working Day" :value="displayDate(employee.last_working_day)" /></div></template></Card>
      </div>
    </template>

    <Dialog v-model:visible="showEdit" modal header="Edit Employee" :style="{ width: 'min(36rem, 95vw)' }">
      <div class="grid gap-4 sm:grid-cols-2">
        <div><label class="field-label">First name</label><InputText v-model="form.fname" fluid /></div>
        <div><label class="field-label">Last name</label><InputText v-model="form.lname" fluid /></div>
        <div class="sm:col-span-2"><label class="field-label">Email</label><InputText :model-value="employee?.email" disabled fluid /><small class="text-slate-500">The employee can change their email from their own profile.</small></div>
        <div class="sm:col-span-2"><label class="field-label">Phone</label><InputText v-model="form.phone_number" fluid /></div>
        <div><label class="field-label">Position</label><Select v-model="form.role_id" :options="roles" optionLabel="display_name" optionValue="id" fluid /></div>
        <div><label class="field-label">Branch</label><Select v-model="form.branch_id" :options="branches" optionLabel="name" optionValue="id" fluid /></div>
        <div><label class="field-label">Hire date</label><DatePicker v-model="form.hire_date" dateFormat="MM d, yy" showIcon fluid /></div>
        <div><label class="field-label">Employment type</label><Select v-model="form.employment_type" :options="employmentTypes" optionLabel="label" optionValue="value" fluid /></div>
        <div v-if="form.employment_type === 'contract'"><label class="field-label">End Contract *</label><DatePicker v-model="form.contract_end_date" :minDate="form.hire_date || undefined" dateFormat="MM d, yy" showIcon fluid /></div>
        <div class="sm:col-span-2"><label class="field-label">Status</label><Select v-model="form.status" :options="statuses" optionLabel="label" optionValue="value" fluid /></div>
      </div>
      <template #footer><Button label="Cancel" severity="secondary" outlined @click="showEdit = false" /><Button label="Save Changes" :loading="saving" @click="saveEmployee" /></template>
    </Dialog>
    <Dialog v-model:visible="showResign" modal header="Record Resignation" :style="{ width: 'min(32rem, 95vw)' }">
      <div class="space-y-4 text-sm">
        <p class="text-slate-500">Record the notice date and the employee's final working day. The final day must be at least 7 days after notice.</p>
        <div><label class="field-label">Resignation date *</label><DatePicker v-model="resign.resignation_date" :minDate="new Date()" dateFormat="MM d, yy" showIcon fluid /></div>
        <div><label class="field-label">Last working day *</label><DatePicker v-model="resign.last_working_day" :minDate="minimumLastDay" dateFormat="MM d, yy" showIcon fluid /></div>
        <div><label class="field-label">Reason *</label><Select v-model="resign.resignation_reason" :options="resignationReasons" optionLabel="label" optionValue="value" fluid /></div>
        <div><label class="field-label">Handover status *</label><Select v-model="resign.handover_status" :options="handoverOptions" optionLabel="label" optionValue="value" fluid /></div>
        <div><label class="field-label">Notes</label><InputText v-model="resign.resignation_notes" fluid /></div>
        <div><label class="field-label">Resignation letter (optional)</label><input type="file" accept=".pdf,.jpg,.jpeg,.png" @change="resignationLetter = ($event.target as HTMLInputElement).files?.[0] || null" /></div>
      </div>
      <template #footer><Button label="Cancel" severity="secondary" outlined @click="showResign = false" /><Button label="Record Resignation" severity="warn" :loading="savingResignation" :disabled="!resign.resignation_date || !resign.last_working_day || resign.last_working_day < minimumLastDay || !resign.resignation_reason" @click="recordResignation" /></template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, defineComponent, h, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import axios from '@/axios'
import Button from 'primevue/button'
import Card from 'primevue/card'
import DatePicker from 'primevue/datepicker'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Select from 'primevue/select'
import Skeleton from 'primevue/skeleton'
import Tag from 'primevue/tag'

const Info = defineComponent({ props: { label: String, value: [String, Number] }, setup(props) { return () => h('div', [h('p', { class: 'text-xs font-medium uppercase tracking-wide text-slate-500' }, props.label), h('p', { class: 'mt-1 font-medium text-slate-900' }, String(props.value || '—'))]) } })
const route = useRoute()
const router = useRouter()
const toast = useToast()
const employee = ref<any>(null)
const roles = ref<any[]>([])
const branches = ref<any[]>([])
const loading = ref(true)
const saving = ref(false)
const uploading = ref(false)
const showEdit = ref(false)
const showResign = ref(false)
const savingResignation = ref(false)
const resignationLetter = ref<File | null>(null)
const resign = reactive({ resignation_date: new Date(), last_working_day: null as Date | null, resignation_reason: null as string | null, handover_status: 'not_started', resignation_notes: '' })
const resignationReasons = [{ label: 'Personal', value: 'personal' }, { label: 'Better Opportunity', value: 'better_opportunity' }, { label: 'Relocation', value: 'relocation' }, { label: 'Health', value: 'health' }, { label: 'End of Contract', value: 'end_of_contract' }, { label: 'Other', value: 'other' }]
const handoverOptions = [{ label: 'Not Started', value: 'not_started' }, { label: 'In Progress', value: 'in_progress' }, { label: 'Complete', value: 'complete' }]
const minimumLastDay = computed(() => { const day = new Date(resign.resignation_date); day.setDate(day.getDate() + 7); return day })
const error = ref('')
const form = reactive({ fname: '', lname: '', phone_number: '', role_id: null as number | null, branch_id: null as number | null, hire_date: null as Date | null, employment_type: 'full_time', contract_end_date: null as Date | null, status: 'active' })
const employmentTypes = [{ label: 'Full Time', value: 'full_time' }, { label: 'Part Time', value: 'part_time' }, { label: 'Contract', value: 'contract' }, { label: 'Intern', value: 'intern' }]
const statuses = [{ label: 'Active', value: 'active' }, { label: 'On Leave', value: 'on_leave' }, { label: 'Suspended', value: 'suspended' }]
const fullName = computed(() => `${employee.value?.fname || ''} ${employee.value?.lname || ''}`.trim())
const initials = computed(() => `${employee.value?.fname?.[0] || ''}${employee.value?.lname?.[0] || ''}`.toUpperCase())
const statusLabel = computed(() => statuses.find(item => item.value === employee.value?.status)?.label || employee.value?.status || 'Unknown')
const employmentLabel = computed(() => employmentTypes.find(item => item.value === employee.value?.employment_type)?.label || '—')
const displayDate = (value?: string) => value ? new Date(`${value.slice(0, 10)}T00:00:00`).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) : '—'
const dateString = (date: Date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
const errorText = (err: any) => String(Object.values(err?.response?.data?.errors || {}).flat()[0] || err?.response?.data?.message || 'Please try again.')

async function loadEmployee() {
  try { employee.value = (await axios.get(`/api/store/simple-staff/employees/${route.params.id}`)).data.data }
  catch (err: any) { error.value = errorText(err) }
  finally { loading.value = false }
}
function openEdit() {
  Object.assign(form, { fname: employee.value.fname || '', lname: employee.value.lname || '', phone_number: employee.value.phone_number || '', role_id: employee.value.role_id, branch_id: employee.value.branch_id, hire_date: employee.value.hire_date ? new Date(`${employee.value.hire_date.slice(0, 10)}T00:00:00`) : new Date(), employment_type: employee.value.employment_type, contract_end_date: employee.value.contract_end_date ? new Date(`${employee.value.contract_end_date}T00:00:00`) : null, status: employee.value.status })
  showEdit.value = true
}
async function saveEmployee() {
  if (!form.hire_date || (form.employment_type === 'contract' && (!form.contract_end_date || form.contract_end_date < form.hire_date))) return
  saving.value = true
  try {
    employee.value = (await axios.put(`/api/store/simple-staff/employees/${route.params.id}`, { ...form, hire_date: dateString(form.hire_date), contract_end_date: form.employment_type === 'contract' && form.contract_end_date ? dateString(form.contract_end_date) : null })).data.data
    showEdit.value = false
    toast.add({ severity: 'success', summary: 'Employee updated', life: 2500 })
  } catch (err: any) { toast.add({ severity: 'error', summary: 'Unable to save', detail: errorText(err), life: 3500 }) }
  finally { saving.value = false }
}
async function recordResignation() {
  if (!resign.last_working_day || !resign.resignation_reason) return
  savingResignation.value = true
  try {
    const body = new FormData()
    body.append('resignation_date', dateString(resign.resignation_date))
    body.append('last_working_day', dateString(resign.last_working_day))
    body.append('resignation_reason', resign.resignation_reason)
    body.append('handover_status', resign.handover_status)
    body.append('resignation_notes', resign.resignation_notes)
    if (resignationLetter.value) body.append('resignation_letter', resignationLetter.value)
    await axios.post(`/api/employees/${route.params.id}/resignation`, body)
    await loadEmployee()
    showResign.value = false
    toast.add({ severity: 'success', summary: 'Resignation recorded', life: 2500 })
  } catch (err: any) { toast.add({ severity: 'error', summary: 'Unable to record resignation', detail: errorText(err), life: 3500 }) }
  finally { savingResignation.value = false }
}
async function uploadPhoto(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return
  const body = new FormData()
  body.append('avatar', file)
  uploading.value = true
  try {
    const response = await axios.post(`/api/employees/${route.params.id}/avatar`, body)
    employee.value.avatar_url = response.data.data.avatar_url
    toast.add({ severity: 'success', summary: 'Photo updated', life: 2500 })
  } catch (err: any) { toast.add({ severity: 'error', summary: 'Unable to upload photo', detail: errorText(err), life: 3500 }) }
  finally { uploading.value = false; input.value = '' }
}
onMounted(async () => {
  const results = await Promise.allSettled([
    loadEmployee(),
    axios.get('/api/store/roles/scoped').then(r => { roles.value = r.data.data || r.data || [] }),
    axios.get('/api/branches').then(r => { branches.value = r.data.data || [] }),
  ])
  if (results[1].status === 'rejected' || results[2].status === 'rejected') {
    toast.add({ severity: 'warn', summary: 'Edit options unavailable', detail: 'Refresh before editing this employee.', life: 3500 })
  }
})
</script>

<style scoped>.field-label { display: block; margin-bottom: .3rem; font-size: .875rem; font-weight: 500; color: #334155; }</style>
