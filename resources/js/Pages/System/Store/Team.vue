<template>
  <div class="space-y-5 text-xs">
    <header><p class="font-semibold uppercase tracking-widest text-orange-600">Store operations</p><h1 class="mt-1 text-2xl font-semibold text-slate-900">Branch Team</h1><p class="mt-1 text-sm text-slate-500">Employees and upcoming shifts for {{ data?.branch?.name || 'your branch' }}.</p></header>
    <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>
    <div class="grid gap-4 xl:grid-cols-2">
      <Card class="border border-slate-200 shadow-sm"><template #content>
        <h2 class="mb-3 text-sm font-semibold text-slate-900">Employees</h2>
        <div v-if="loading" class="py-10 text-center text-slate-500">Loading team...</div>
        <DataTable v-else :value="data?.employees || []" size="small" rowHover paginator :rows="10">
          <template #empty><div class="py-8 text-center text-slate-500">No employees assigned to this branch.</div></template>
          <Column header="Employee"><template #body="{ data: row }"><div class="font-medium">{{ row.fname }} {{ row.lname }}</div><div class="text-slate-500">{{ row.employee_number }}</div></template></Column>
          <Column header="Role"><template #body="{ data: row }">{{ row.role?.display_name || row.role?.name || 'Unassigned' }}</template></Column>
          <Column header="Status"><template #body="{ data: row }"><Badge :value="row.status" :severity="row.status === 'active' ? 'success' : 'secondary'" /></template></Column>
        </DataTable>
      </template></Card>
      <Card class="border border-slate-200 shadow-sm"><template #content>
        <h2 class="mb-3 text-sm font-semibold text-slate-900">Upcoming shifts</h2>
        <div v-if="loading" class="py-10 text-center text-slate-500">Loading shifts...</div>
        <DataTable v-else :value="data?.schedules || []" size="small" rowHover paginator :rows="10">
          <template #empty><div class="py-8 text-center text-slate-500">No shifts scheduled in the next seven days.</div></template>
          <Column header="Date"><template #body="{ data: row }">{{ row.schedule_date }}</template></Column>
          <Column header="Employee"><template #body="{ data: row }">{{ row.fname }} {{ row.lname }}</template></Column>
          <Column field="status" header="Status" />
        </DataTable>
      </template></Card>
    </div>
    <Button label="View branch attendance" icon="pi pi-calendar" size="small" severity="warn" outlined @click="router.visit('/store/attendance')" />
    <Card class="border border-slate-200 shadow-sm"><template #content>
      <h2 class="mb-1 text-sm font-semibold text-slate-900">Pending shift swaps</h2><p class="mb-3 text-xs text-slate-500">Requests between employees of this branch only.</p>
      <DataTable :value="data?.pending_swaps || []" size="small" rowHover>
        <template #empty><div class="py-8 text-center text-slate-500">No pending shift swaps.</div></template>
        <Column header="From"><template #body="{ data: row }">{{ row.requestor?.fname }} {{ row.requestor?.lname }}</template></Column>
        <Column header="To"><template #body="{ data: row }">{{ row.receiver?.fname }} {{ row.receiver?.lname }}</template></Column>
        <Column field="reason" header="Reason" />
        <Column v-if="data?.can_manage_swaps" header="Actions"><template #body="{ data: row }"><div v-if="row.requestor_id !== data.manager_employee_id && row.receiver_id !== data.manager_employee_id" class="flex gap-2"><Button label="Approve" size="small" severity="warn" :disabled="acting" @click="decide(row.id, 'approve')" /><Button label="Reject" size="small" severity="secondary" outlined :disabled="acting" @click="decide(row.id, 'reject')" /></div><span v-else class="text-slate-400">Your request</span></template></Column>
      </DataTable>
    </template></Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from '@/axios'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Badge from 'primevue/badge'
import Message from 'primevue/message'

const data = ref<any>(null)
const loading = ref(true)
const error = ref('')
const acting = ref(false)
const load = async () => {
  try { data.value = (await axios.get('/api/store-module/team')).data.data }
  catch (err: any) { error.value = err.response?.data?.message || 'Unable to load branch team.' }
  finally { loading.value = false }
}
const decide = async (id: number, action: 'approve' | 'reject') => {
  if (!window.confirm(`${action === 'approve' ? 'Approve' : 'Reject'} this shift swap request?`)) return
  acting.value = true
  error.value = ''
  try { await axios.put(`/api/shift-swaps/${id}/${action}`); await load() }
  catch (err: any) { error.value = err.response?.data?.message || `Unable to ${action} this request.` }
  finally { acting.value = false }
}
onMounted(load)
</script>
