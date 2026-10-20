<template>
  <div class="space-y-4 p-4 text-xs md:p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div><h1 class="text-xl font-semibold text-slate-900">Branch employees</h1><p class="mt-1 text-slate-500">People assigned to this branch. Select a row to open the HR profile.</p></div>
      <Button label="Back to branch" size="small" severity="secondary" outlined @click="router.visit(`/store/branches/${branchId}`)" />
    </div>
    <Card class="border border-slate-200 shadow-sm"><template #content>
      <div class="mb-4 max-w-sm"><IconField><InputIcon class="pi pi-search" /><InputText v-model="search" size="small" fluid placeholder="Search name or employee number" @input="searchChanged" /></IconField></div>
      <Message v-if="error" severity="error" :closable="false" class="mb-3">{{ error }}</Message>
      <div v-if="loading" class="space-y-2"><Skeleton v-for="n in 5" :key="n" height="2.5rem" /></div>
      <DataTable v-else :value="employees" rowHover size="small" dataKey="id" class="text-xs" @row-click="router.visit(`/hr/employees/view/${$event.data.id}`)">
        <template #empty><div class="py-10 text-center text-slate-500">No employees are assigned to this branch.</div></template>
        <Column field="employee_number" header="Employee no." />
        <Column header="Employee"><template #body="{ data }"><div class="font-semibold text-slate-900">{{ data.fname }} {{ data.lname }}</div><div class="text-slate-500">{{ data.user?.email || '' }}</div></template></Column>
        <Column header="Role"><template #body="{ data }">{{ data.role?.display_name || data.role?.name || 'Not assigned' }}</template></Column>
        <Column header="Hired"><template #body="{ data }">{{ date(data.hire_date) }}</template></Column>
        <Column header="Status"><template #body="{ data }"><Badge :value="data.status" :severity="data.status === 'active' ? 'success' : 'secondary'" /></template></Column>
      </DataTable>
      <Paginator v-if="total > 15" :rows="15" :first="(page - 1) * 15" :totalRecords="total" @page="page = $event.page + 1; load()" />
    </template></Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import axiosClient from '@/axios'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Badge from 'primevue/badge'
import Skeleton from 'primevue/skeleton'
import Paginator from 'primevue/paginator'
import Message from 'primevue/message'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'

const branchId = Number(window.location.pathname.match(/\/store\/branches\/(\d+)/)?.[1] || 0)
const employees = ref<any[]>([])
const search = ref('')
const page = ref(1)
const total = ref(0)
const loading = ref(true)
const error = ref('')
const date = (value?: string) => value ? new Intl.DateTimeFormat('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(value)) : 'Not set'
let searchTimer: ReturnType<typeof setTimeout> | null = null
function searchChanged() { if (searchTimer) clearTimeout(searchTimer); searchTimer = setTimeout(() => { page.value = 1; load() }, 300) }
async function load() {
  loading.value = true
  error.value = ''
  try {
    const response = await axiosClient.get(`/api/branches/${branchId}/employees`, { params: { search: search.value, page: page.value } })
    employees.value = response.data?.data?.data || []
    total.value = response.data?.data?.total || 0
  } catch (err: any) {
    employees.value = []
    error.value = err.response?.data?.message || 'Unable to load branch employees.'
  } finally { loading.value = false }
}
onMounted(load)
</script>
