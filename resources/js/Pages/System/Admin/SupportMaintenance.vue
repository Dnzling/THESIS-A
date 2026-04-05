<template>
  <div class="space-y-6">
    <Card class="border border-slate-200 shadow-none">
      <template #content>
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 class="text-2xl font-semibold text-slate-900">Support & Maintenance</h1>
            <p class="text-sm text-slate-500">Manage super admin access and platform support accounts.</p>
          </div>
          <div class="flex items-center gap-2">
            <Button icon="pi pi-refresh" label="Refresh" severity="info" outlined @click="loadAdmins" />
            <Button icon="pi pi-plus" label="Add Super Admin" severity="info" @click="openDialog" />
          </div>
        </div>
      </template>
    </Card>

    <Card class="border border-slate-200 shadow-none">
      <template #content>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
          <div class="md:col-span-10">
            <IconField>
              <InputIcon class="pi pi-search" />
              <InputText v-model="search" placeholder="Search super admin by name or email..." fluid />
            </IconField>
          </div>
          <div class="md:col-span-2 flex items-center justify-end">
            <Button icon="pi pi-filter-slash" text severity="secondary" @click="clearFilters" />
          </div>
        </div>
      </template>
    </Card>

    <Card class="border border-slate-200 shadow-none">
      <template #content>
        <DataTable :value="admins" :loading="loading" stripedRows dataKey="id">
          <Column field="full_name" header="Super Admin">
            <template #body="{ data }">
              <div>
                <p class="font-medium text-slate-900">{{ data.full_name || '-' }}</p>
                <p class="text-xs text-slate-500">{{ data.email || '-' }}</p>
              </div>
            </template>
          </Column>
          <Column field="is_active" header="Status">
            <template #body="{ data }">
              <Tag :value="data.is_active ? 'Active' : 'Inactive'" :severity="data.is_active ? 'success' : 'secondary'" />
            </template>
          </Column>
          <Column field="created_at" header="Created">
            <template #body="{ data }">
              {{ formatDateTime(data.created_at) }}
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <Dialog v-model:visible="dialogVisible" header="Add Super Admin" :style="{ width: '520px' }" modal>
      <div class="space-y-4">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">First Name</label>
            <InputText v-model="form.fname" fluid />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Last Name</label>
            <InputText v-model="form.lname" fluid />
          </div>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Email</label>
          <InputText v-model="form.email" type="email" fluid />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Password</label>
          <InputText v-model="form.password" type="password" fluid />
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" text @click="dialogVisible = false" />
        <Button label="Create" icon="pi pi-check" severity="info" :loading="saving" @click="saveAdmin" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import axiosClient from '@/axios'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'

const toast = useToast()
const loading = ref(false)
const saving = ref(false)
const dialogVisible = ref(false)
const admins = ref<any[]>([])
const search = ref('')

const form = ref({
  fname: '',
  lname: '',
  email: '',
  password: '',
})

const formatDateTime = (value: string | null | undefined) => {
  if (!value) return '-'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return '-'
  return new Intl.DateTimeFormat('en-PH', {
    month: 'short',
    day: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date)
}

const loadAdmins = async () => {
  loading.value = true
  try {
    const response = await axiosClient.get('/api/admin/super-admins', {
      params: { search: search.value || undefined },
    })
    admins.value = response.data?.data || []
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to load super admins', life: 3000 })
  } finally {
    loading.value = false
  }
}

const openDialog = () => {
  form.value = { fname: '', lname: '', email: '', password: '' }
  dialogVisible.value = true
}

const saveAdmin = async () => {
  saving.value = true
  try {
    await axiosClient.post('/api/admin/super-admins', form.value)
    toast.add({ severity: 'success', summary: 'Saved', detail: 'Super admin created successfully', life: 2500 })
    dialogVisible.value = false
    await loadAdmins()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to create super admin', life: 3000 })
  } finally {
    saving.value = false
  }
}

const clearFilters = () => {
  search.value = ''
}

watch(search, () => {
  loadAdmins()
})

onMounted(loadAdmins)
</script>
