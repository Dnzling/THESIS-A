<template>
  <div class="space-y-6">
    <div class="bg-white shadow rounded-xl p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Customer Management</h1>
          <p class="text-sm text-gray-500 mt-1">Manage customer verification requirements</p>
        </div>
        <Button label="Refresh" icon="pi pi-refresh" severity="secondary" @click="loadCustomers" />
      </div>
    </div>

    <div class="bg-white shadow rounded-xl p-6">
      <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
        <div class="flex items-center gap-2">
          <Select v-model="statusFilter" :options="statusOptions" optionLabel="name" placeholder="Status"
            class="w-48" />
          <Button label="Clear" severity="secondary" outlined @click="clearFilters" />
        </div>
        <div class="flex items-center gap-2">
          <Button label="Require Verification" icon="pi pi-exclamation-triangle" severity="warning"
            :disabled="selectedCustomers.length === 0" @click="showBulkRequireDialog = true" />
          <div class="w-72">
            <IconField>
              <InputIcon>
                <i class="pi pi-search" />
              </InputIcon>
              <InputText v-model="searchTerm" placeholder="Search customers..." class="w-full" />
            </IconField>
          </div>
        </div>
      </div>

      <DataTable :value="filteredCustomers" dataKey="id" v-model:selection="selectedCustomers" selectionMode="multiple"
        paginator :rows="10" :rowsPerPageOptions="[10, 20, 50]" tableStyle="min-width: 60rem" sortMode="multiple">
        <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>
        <Column field="fullName" header="Customer" sortable style="width: 20%">
          <template #body="slotProps">
            <div class="flex items-center space-x-3">
              <div :class="`w-10 h-10 rounded-full flex items-center justify-center ${getStatusColor(slotProps.data.status)}`">
                <i :class="`pi ${getStatusIcon(slotProps.data.status)}`"></i>
              </div>
              <div>
                <p class="font-medium">{{ slotProps.data.fullName }}</p>
                <p class="text-xs text-gray-500">ID: {{ slotProps.data.customerId }}</p>
              </div>
            </div>
          </template>
        </Column>

        <Column field="email" header="Contact" sortable style="width: 18%">
          <template #body="slotProps">
            <div>
              <p class="font-medium">{{ slotProps.data.email }}</p>
              <p class="text-xs text-gray-500">{{ slotProps.data.phone }}</p>
            </div>
          </template>
        </Column>

        <Column field="status" header="Verification Status" sortable style="width: 14%">
          <template #body="slotProps">
            <Tag :value="slotProps.data.status" :severity="getStatusSeverity(slotProps.data.status)" rounded />
          </template>
        </Column>

        <Column field="verificationRequired" header="Required" sortable style="width: 10%">
          <template #body="slotProps">
            <Tag :value="slotProps.data.verificationRequired ? 'Yes' : 'No'"
              :severity="slotProps.data.verificationRequired ? 'warning' : 'success'" rounded />
          </template>
        </Column>

        <Column field="triggerAmount" header="Triggered Amount" sortable style="width: 12%">
          <template #body="slotProps">
            <span>{{ formatCurrency(slotProps.data.triggerAmount) }}</span>
          </template>
        </Column>

        <Column header="Actions" style="width: 16%">
          <template #body="slotProps">
            <div class="flex items-center gap-2">
              <Button icon="pi pi-eye" size="small" severity="info" @click="viewCustomer(slotProps.data)" />
              <Button v-if="slotProps.data.status === 'Unverified' && !slotProps.data.verificationRequired"
                label="Require" size="small" severity="warning" @click="confirmRequire(slotProps.data)" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <Dialog v-model:visible="showViewDialog" modal
      :header="selectedCustomer ? `Customer Details: ${selectedCustomer.fullName}` : 'Customer Details'"
      :style="{ width: '800px' }">
      <div v-if="selectedCustomer" class="space-y-6">
        <div class="bg-gray-50 p-4 rounded-lg">
          <h4 class="font-medium text-gray-800 mb-3">Customer Information</h4>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-gray-500">Full Name</p>
              <p class="font-medium">{{ selectedCustomer.fullName }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Date of Birth</p>
              <p class="font-medium">{{ formatDate(selectedCustomer.dateOfBirth) }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Gender</p>
              <p class="font-medium">{{ selectedCustomer.gender || '—' }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Nationality</p>
              <p class="font-medium">{{ selectedCustomer.nationality || '—' }}</p>
            </div>
          </div>
        </div>

        <div>
          <h4 class="font-medium text-gray-800 mb-3">Contact Information</h4>
          <div class="bg-gray-50 p-4 rounded-lg">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-sm text-gray-500">Email Address</p>
                <p class="font-medium">{{ selectedCustomer.email }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500">Phone Number</p>
                <p class="font-medium">{{ selectedCustomer.phone }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500">Address</p>
                <p class="font-medium">{{ selectedCustomer.address }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500">Registration Date</p>
                <p class="font-medium">{{ formatDate(selectedCustomer.registrationDate) }}</p>
              </div>
            </div>
          </div>
        </div>

        <div>
          <h4 class="font-medium text-gray-800 mb-3">Documents</h4>
          <div v-if="selectedCustomer.documents?.length" class="space-y-3">
            <div v-for="doc in selectedCustomer.documents" :key="doc.name"
              class="flex items-center justify-between p-3 bg-gray-50 rounded">
              <div class="flex items-center space-x-3">
                <i :class="`pi ${getDocumentTypeIcon(doc.type)} ${getDocumentTypeColor(doc.type)}`"></i>
                <div>
                  <p class="font-medium">{{ doc.name }}</p>
                  <p class="text-xs text-gray-500">Status: {{ doc.status || 'Pending' }}</p>
                </div>
              </div>
              <Button label="View" size="small" icon="pi pi-eye" @click="viewDocument(doc)" />
            </div>
          </div>
          <div v-else class="text-sm text-gray-500">No documents uploaded.</div>
        </div>
      </div>
      <template #footer>
        <Button label="Close" severity="secondary" @click="showViewDialog = false" />
      </template>
    </Dialog>

    <Dialog v-model:visible="showRequireDialog" header="Require Verification" :style="{ width: '500px' }">
      <div class="space-y-4">
        <p class="text-gray-600">
          Require verification for
          <span class="font-semibold">{{ customerToRequire?.fullName }}</span>?
        </p>
        <div class="bg-yellow-50 p-4 rounded-lg text-sm text-yellow-800">
          This will set the customer verification status to pending.
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" @click="showRequireDialog = false" />
        <Button label="Confirm" severity="warning" @click="requireVerification" />
      </template>
    </Dialog>

    <Dialog v-model:visible="showBulkRequireDialog" header="Bulk Require Verification" :style="{ width: '500px' }">
      <div class="space-y-4">
        <p class="text-gray-600">You are about to require verification for {{ selectedCustomers.length }} customer(s).</p>
        <div class="bg-yellow-50 p-4 rounded-lg text-sm text-yellow-800">
          This will set the customer verification status to pending.
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" @click="showBulkRequireDialog = false" />
        <Button label="Confirm" severity="warning" @click="bulkRequireVerification" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import axiosClient from '../../../axios'
import { useToast } from 'primevue/usetoast'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Select from 'primevue/select'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Dialog from 'primevue/dialog'

const toast = useToast()

const customers = ref<any[]>([])
const searchTerm = ref('')
const statusFilter = ref<any>(null)
const showViewDialog = ref(false)
const showRequireDialog = ref(false)
const showBulkRequireDialog = ref(false)
const selectedCustomers = ref<any[]>([])
const selectedCustomer = ref<any>(null)
const customerToRequire = ref<any>(null)

const statusOptions = ref([
  { name: 'Unverified', value: 'unverified' },
  { name: 'Pending', value: 'pending' },
  { name: 'Verified', value: 'verified' },
  { name: 'Rejected', value: 'rejected' }
])

const mapCustomer = (user: any) => {
  const status = user.customer_verification_status
    ? user.customer_verification_status.charAt(0).toUpperCase() + user.customer_verification_status.slice(1)
    : 'Unverified'
  const documents = (user.documents || []).map((doc: any) => ({
    name: doc.name || doc.document_type || 'Document',
    type: doc.document_type || doc.type || 'file',
    status: doc.status || 'Pending',
    url: doc.path ? toFileUrl(doc.path) : doc.url || ''
  }))

  return {
    id: user.id,
    customerId: `CUST-${String(user.id).padStart(6, '0')}`,
    fullName: `${user.fname || ''} ${user.lname || ''}`.trim() || user.name || 'Unknown',
    email: user.email,
    phone: user.phone_number || user.phone || 'N/A',
    dateOfBirth: user.date_of_birth || '',
    gender: user.gender || '',
    nationality: user.nationality || '',
    address: user.address || 'N/A',
    registrationDate: user.created_at,
    status,
    verificationRequired: !!user.customer_verification_required,
    triggerAmount: user.customer_verification_trigger_amount || 0,
    documents
  }
}

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || ''
const toFileUrl = (path: string) => {
  if (!path) return ''
  if (path.startsWith('http://') || path.startsWith('https://')) return path
  return `${apiBaseUrl}/storage/${path.replace(/^\/+/, '')}`
}

const loadCustomers = async () => {
  try {
    const res = await axiosClient.get('/api/admin/customers', { params: { per_page: 200 } })
    const rows = res.data?.data?.data || res.data?.data || []
    customers.value = rows.map(mapCustomer)
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load customers', life: 3000 })
  }
}

const filteredCustomers = computed(() => {
  let filtered = customers.value

  if (searchTerm.value) {
    const term = searchTerm.value.toLowerCase()
    filtered = filtered.filter(customer =>
      customer.fullName.toLowerCase().includes(term) ||
      customer.email.toLowerCase().includes(term) ||
      customer.customerId.toLowerCase().includes(term)
    )
  }

  if (statusFilter.value) {
    filtered = filtered.filter(customer => customer.status.toLowerCase() === statusFilter.value.value)
  }

  return filtered
})

const clearFilters = () => {
  searchTerm.value = ''
  statusFilter.value = null
}

const viewCustomer = (customer: any) => {
  selectedCustomer.value = customer
  showViewDialog.value = true
}

const confirmRequire = (customer: any) => {
  customerToRequire.value = customer
  showRequireDialog.value = true
}

const requireVerification = async () => {
  if (!customerToRequire.value) return
  try {
    await axiosClient.post(`/api/admin/customers/${customerToRequire.value.id}/require-verification`)
    toast.add({ severity: 'success', summary: 'Verification Required', detail: 'Customer flagged for verification.', life: 3000 })
    showRequireDialog.value = false
    customerToRequire.value = null
    await loadCustomers()
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to require verification', life: 3000 })
  }
}

const viewDocument = (doc: any) => {
  if (doc?.url) {
    window.open(doc.url, '_blank', 'noopener,noreferrer')
    return
  }
  toast.add({ severity: 'info', summary: 'No Document', detail: 'No document file available.', life: 2500 })
}

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(value || 0)
}

const formatDate = (date: string) => {
  if (!date) return '—'
  return new Date(date).toLocaleDateString()
}

const getStatusSeverity = (status: string) => {
  switch (status.toLowerCase()) {
    case 'pending': return 'warning'
    case 'verified': return 'success'
    case 'rejected': return 'danger'
    case 'unverified': return 'info'
    default: return 'info'
  }
}

const getStatusIcon = (status: string) => {
  switch (status.toLowerCase()) {
    case 'pending': return 'pi-user-clock'
    case 'verified': return 'pi-user-check'
    case 'rejected': return 'pi-user-times'
    case 'unverified': return 'pi-user'
    default: return 'pi-user'
  }
}

const getStatusColor = (status: string) => {
  switch (status.toLowerCase()) {
    case 'pending': return 'bg-yellow-100 text-yellow-600'
    case 'verified': return 'bg-green-100 text-green-600'
    case 'rejected': return 'bg-red-100 text-red-600'
    case 'unverified': return 'bg-blue-100 text-blue-600'
    default: return 'bg-blue-100 text-blue-600'
  }
}

const getDocumentTypeIcon = (type: string) => {
  switch (type) {
    case 'government_id': return 'pi-id-card'
    case 'proof_of_address': return 'pi-home'
    case 'selfie': return 'pi-camera'
    case 'business_permit': return 'pi-briefcase'
    case 'tax_id': return 'pi-credit-card'
    default: return 'pi-file'
  }
}

const getDocumentTypeColor = (type: string) => {
  switch (type) {
    case 'government_id': return 'text-blue-500'
    case 'proof_of_address': return 'text-green-500'
    case 'selfie': return 'text-purple-500'
    case 'business_permit': return 'text-orange-500'
    case 'tax_id': return 'text-indigo-500'
    default: return 'text-gray-500'
  }
}

const bulkRequireVerification = async () => {
  if (selectedCustomers.value.length === 0) return
  try {
    const ids = selectedCustomers.value.map(c => c.id)
    await axiosClient.post('/api/admin/customers/require-verification-bulk', { ids })
    toast.add({ severity: 'success', summary: 'Verification Required', detail: 'Customers flagged for verification.', life: 3000 })
    showBulkRequireDialog.value = false
    selectedCustomers.value = []
    await loadCustomers()
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to require verification', life: 3000 })
  }
}

onMounted(() => {
  loadCustomers()
})
</script>
