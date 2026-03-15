<template>
  <div class="p-8 bg-gray-50 min-h-screen">
    <!-- Header Section -->
    <div class="mb-8">
      <button @click="router.back()" class="text-blue-600 hover:text-blue-800 flex items-center gap-2 mb-4 text-sm font-medium">
        <i class="pi pi-arrow-left"></i> Back
      </button>
      
      <div class="flex justify-between items-start gap-6">
        <div>
          <div class="flex items-center gap-3 mb-2">
            <h1 class="text-4xl font-bold text-gray-900">{{ contract?.contract_number }}</h1>
            <Tag :value="contract?.status?.toUpperCase() || 'DRAFT'" :severity="statusSeverity(contract?.status)" />
          </div>
          <p class="text-lg text-gray-600">{{ contract?.contract_title }}</p>
        </div>
        
        <div class="flex gap-2">
          <Button v-if="contract?.status === 'draft'" label="Edit" icon="pi pi-pencil" severity="warning" 
            @click="editContract" class="px-6" />
          <Button v-if="contract?.status === 'draft'" label="Activate" icon="pi pi-check-circle" severity="success" 
            @click="activateContract" :loading="activating" class="px-6" />
          <Button v-if="contract?.status === 'active'" label="Terminate" icon="pi pi-ban" severity="danger" 
            @click="showTerminateDialog = true" class="px-6" />
        </div>
      </div>
    </div>

    <div v-if="loading" class="flex justify-center items-center py-20">
      <ProgressSpinner />
    </div>

    <div v-else-if="contract">
      <!-- Quick Stats -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg p-6 border border-gray-200 shadow-sm hover:shadow-md transition">
          <p class="text-sm font-medium text-gray-600 mb-2">Payment Terms</p>
          <p class="text-2xl font-bold text-gray-900">Net {{ contract?.payment_terms_days }} Days</p>
          <p class="text-xs text-gray-500 mt-2">Due within {{ contract?.payment_terms_days }} calendar days</p>
        </div>

        <div class="bg-white rounded-lg p-6 border border-gray-200 shadow-sm hover:shadow-md transition">
          <p class="text-sm font-medium text-gray-600 mb-2">Minimum Order</p>
          <p class="text-2xl font-bold text-orange-600">₱{{ parseFloat(contract?.minimum_order_value || 0).toLocaleString('en-PH', { minimumFractionDigits: 0 }) }}</p>
          <p class="text-xs text-gray-500 mt-2">Per transaction minimum</p>
        </div>

        <div class="bg-white rounded-lg p-6 border border-gray-200 shadow-sm hover:shadow-md transition">
          <p class="text-sm font-medium text-gray-600 mb-2">Volume Discount</p>
          <p class="text-2xl font-bold text-green-600">{{ contract?.discount_percentage || 0 }}%</p>
          <p class="text-xs text-gray-500 mt-2">On all orders</p>
        </div>

        <div class="bg-white rounded-lg p-6 border border-gray-200 shadow-sm hover:shadow-md transition">
          <p class="text-sm font-medium text-gray-600 mb-2">Validity</p>
          <p class="text-2xl font-bold text-blue-600">{{ calculateDaysRemaining(contract?.end_date) }}</p>
          <p class="text-xs text-gray-500 mt-2">Contract status</p>
        </div>
      </div>

      <!-- Content Sections -->
      <div class="space-y-8">
        <!-- Supplier Information Section -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-8">
          <h2 class="text-xl font-bold text-gray-900 mb-6">Supplier Information</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div>
              <p class="text-sm font-medium text-gray-600 uppercase mb-2">Supplier Name</p>
              <p class="text-lg font-semibold text-gray-900">{{ contract?.supplier?.supplier_name }}</p>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-600 uppercase mb-2">Supplier Code</p>
              <p class="font-mono text-gray-900 bg-gray-50 px-3 py-2 rounded inline-block">{{ contract?.supplier?.supplier_code }}</p>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-600 uppercase mb-2">Contact Person</p>
              <p class="text-gray-900">{{ contract?.supplier?.contact_person || '—' }}</p>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-600 uppercase mb-2">Email</p>
              <a :href="`mailto:${contract?.supplier?.email}`" class="text-blue-600 hover:underline break-all">
                {{ contract?.supplier?.email || '—' }}
              </a>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-600 uppercase mb-2">Phone</p>
              <p class="text-gray-900">{{ contract?.supplier?.phone || '—' }}</p>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-600 uppercase mb-2">Contract Type</p>
              <p class="capitalize text-gray-900">{{ contract?.contract_type?.replace('_', ' ') }}</p>
            </div>
          </div>
        </div>

        <!-- Contract Details Section -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-8">
          <h2 class="text-xl font-bold text-gray-900 mb-6">Contract Details</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div>
              <p class="text-sm font-medium text-gray-600 uppercase mb-2">Contract Number</p>
              <p class="text-lg font-mono text-gray-900 bg-gray-50 px-3 py-2 rounded">{{ contract?.contract_number }}</p>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-600 uppercase mb-2">Created By</p>
              <p class="text-gray-900">{{ contract?.created_by?.fname }} {{ contract?.created_by?.lname }}</p>
              <p class="text-xs text-gray-500 mt-1">{{ formatDate(contract?.created_at) }}</p>
            </div>
          </div>
        </div>

        <!-- Financial Terms Section -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-8">
          <h2 class="text-xl font-bold text-gray-900 mb-6">Financial Terms</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="space-y-6">
              <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-lg p-6 border border-orange-200">
                <p class="text-xs font-semibold text-orange-700 uppercase mb-3">Minimum Order Value</p>
                <p class="text-3xl font-bold text-orange-600">
                  ₱{{ parseFloat(contract?.minimum_order_value || 0).toLocaleString('en-PH', { minimumFractionDigits: 0 }) }}
                </p>
                <p class="text-xs text-orange-600 mt-2">Minimum amount required per order</p>
              </div>

              <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-6 border border-green-200">
                <p class="text-xs font-semibold text-green-700 uppercase mb-3">Volume Discount</p>
                <p class="text-3xl font-bold text-green-600">{{ contract?.discount_percentage || 0 }}% OFF</p>
                <p class="text-xs text-green-600 mt-2">Automatically applied to all orders</p>
              </div>
            </div>

            <div class="space-y-6">
              <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-6 border border-blue-200">
                <p class="text-xs font-semibold text-blue-700 uppercase mb-3">Payment Terms</p>
                <p class="text-3xl font-bold text-blue-600">Net {{ contract?.payment_terms_days }} Days</p>
                <p class="text-xs text-blue-600 mt-2">Payment due {{ contract?.payment_terms_days }} calendar days from invoice</p>
              </div>

              <div class="bg-gray-50 rounded-lg p-6 border border-gray-200">
                <p class="text-xs font-semibold text-gray-700 uppercase mb-4">Sample Calculation</p>
                <div class="space-y-2 text-sm">
                  <div class="flex justify-between">
                    <span class="text-gray-600">Order Amount:</span>
                    <span class="font-semibold text-gray-900">₱{{ parseFloat(contract?.minimum_order_value || 0).toLocaleString('en-PH', { minimumFractionDigits: 0 }) }}</span>
                  </div>
                  <div class="flex justify-between text-green-600">
                    <span>Discount ({{ contract?.discount_percentage || 0 }}%):</span>
                    <span class="font-semibold">-₱{{ (parseFloat(contract?.minimum_order_value || 0) * (contract?.discount_percentage || 0) / 100).toLocaleString('en-PH', { minimumFractionDigits: 0 }) }}</span>
                  </div>
                  <div class="pt-2 border-t border-gray-300 flex justify-between">
                    <span class="font-semibold text-gray-900">Final Amount:</span>
                    <span class="font-bold text-gray-900">₱{{ (parseFloat(contract?.minimum_order_value || 0) * (1 - (contract?.discount_percentage || 0) / 100)).toLocaleString('en-PH', { minimumFractionDigits: 0 }) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Duration & Validity Section -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-8">
          <h2 class="text-xl font-bold text-gray-900 mb-6">Duration & Validity</h2>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div>
              <p class="text-sm font-medium text-gray-600 uppercase mb-3">Start Date</p>
              <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                <p class="text-2xl font-bold text-green-700">{{ formatDate(contract?.start_date) }}</p>
                <p class="text-xs text-green-600 mt-2">Contract becomes effective</p>
              </div>
            </div>

            <div>
              <p class="text-sm font-medium text-gray-600 uppercase mb-3">End Date</p>
              <div class="bg-orange-50 rounded-lg p-4 border border-orange-200">
                <p class="text-2xl font-bold text-orange-700">{{ formatDate(contract?.end_date) }}</p>
                <p class="text-xs text-orange-600 mt-2">Contract expires</p>
              </div>
            </div>

            <div>
              <p class="text-sm font-medium text-gray-600 uppercase mb-3">Duration</p>
              <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                <p class="text-2xl font-bold text-blue-700">{{ contractDurationDays }} <span class="text-sm">days</span></p>
                <p class="text-xs text-blue-600 mt-2">≈ {{ Math.ceil(contractDurationDays / 30) }} months</p>
              </div>
            </div>
          </div>

          <div class="mt-8 border-t pt-8">
            <h3 class="font-semibold text-gray-900 mb-4">Progress Timeline</h3>
            <div class="bg-gray-50 rounded-lg p-4">
              <div class="flex justify-between text-sm text-gray-600 mb-3">
                <span>Started {{ daysFromStart }} days ago</span>
                <span>{{ calculateProgressPercentage }}% complete</span>
              </div>
              <ProgressBar :value="calculateProgressPercentage" class="w-full" />
            </div>
          </div>
        </div>

        <!-- Terms & Conditions Section -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-8">
          <h2 class="text-xl font-bold text-gray-900 mb-6">Terms & Conditions</h2>
          <div class="bg-gray-50 rounded-lg p-6 border border-gray-200 min-h-48 whitespace-pre-wrap text-sm text-gray-700 leading-relaxed">
            {{ contract?.terms_conditions || '✓ No additional terms specified' }}
          </div>
        </div>

        <!-- Audit Information Section -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-8">
          <h2 class="text-xl font-bold text-gray-900 mb-6">Audit Information</h2>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-sm">
            <div>
              <p class="text-gray-600 font-semibold mb-2">Created</p>
              <p class="text-gray-900">{{ formatDatetime(contract?.created_at) }}</p>
              <p class="text-gray-500 text-xs mt-1">by {{ contract?.created_by?.fname }} {{ contract?.created_by?.lname }}</p>
            </div>
            <div>
              <p class="text-gray-600 font-semibold mb-2">Last Updated</p>
              <p class="text-gray-900">{{ formatDatetime(contract?.updated_at) || 'Never' }}</p>
            </div>
            <div>
              <p class="text-gray-600 font-semibold mb-2">Contract ID</p>
              <p class="text-gray-900 font-mono bg-gray-50 px-3 py-2 rounded">{{ contract?.id }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Terminate Dialog -->
    <Dialog v-model:visible="showTerminateDialog" header="Terminate Contract" :modal="true" class="w-96">
      <div class="space-y-4">
        <p class="text-gray-700">Are you sure you want to terminate this contract?</p>
        <p class="text-sm text-gray-600">This action cannot be undone.</p>
        <div class="flex gap-2 justify-end pt-4">
          <Button label="Cancel" severity="secondary" @click="showTerminateDialog = false" />
          <Button label="Terminate" severity="danger" @click="terminateContract" :loading="activating" />
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import procurementService from '../../../../services/procurement.service'

const router = useRouter()
const route = useRoute()
const toast = useToast()
const loading = ref(false)
const activating = ref(false)
const showTerminateDialog = ref(false)
const contract = ref<any>(null)

const formatDate = (date: string | null): string => {
  if (!date) return 'N/A'
  const d = new Date(date)
  return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

const formatDatetime = (date: string | null): string => {
  if (!date) return 'N/A'
  const d = new Date(date)
  return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) + ' ' + 
    d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
}

const calculateDaysRemaining = (deadline: string | null): string => {
  if (!deadline) return 'N/A'
  const deadlineDate = new Date(deadline)
  const today = new Date()
  const days = Math.ceil((deadlineDate.getTime() - today.getTime()) / (1000 * 3600 * 24))
  if (days < 0) return `Expired ${Math.abs(days)}d ago`
  if (days === 0) return 'Expires Today'
  if (days === 1) return 'Expires Tomorrow'
  return `Expires in ${days}d`
}

const daysFromStart = computed(() => {
  if (!contract.value?.start_date) return 0
  const startDate = new Date(contract.value.start_date)
  const today = new Date()
  return Math.ceil((today.getTime() - startDate.getTime()) / (1000 * 3600 * 24))
})

const contractDurationDays = computed(() => {
  if (!contract.value?.start_date || !contract.value?.end_date) return 0
  const start = new Date(contract.value.start_date)
  const end = new Date(contract.value.end_date)
  return Math.ceil((end.getTime() - start.getTime()) / (1000 * 3600 * 24))
})

const calculateProgressPercentage = computed(() => {
  if (contractDurationDays.value === 0) return 0
  return Math.min(Math.round((daysFromStart.value / contractDurationDays.value) * 100), 100)
})

const statusSeverity = (status: string): string => {
  const map: Record<string, string> = {
    active: 'success',
    draft: 'secondary',
    expired: 'danger',
    terminated: 'warning',
  }
  return map[status] || 'secondary'
}

const statusIconClass = (status: string): string => {
  const map: Record<string, string> = {
    active: 'text-green-500 opacity-20',
    draft: 'text-gray-500 opacity-20',
    expired: 'text-red-500 opacity-20',
    terminated: 'text-orange-500 opacity-20',
  }
  return map[status] || 'text-gray-500 opacity-20'
}

const validityStatus = computed(() => {
  if (!contract.value?.end_date) return 'Status Unknown'
  const endDate = new Date(contract.value.end_date)
  const today = new Date()
  if (endDate < today) return 'Expired'
  if (contract.value.status === 'terminated') return 'Terminated'
  if (contract.value.status === 'draft') return 'Draft - Not Yet Active'
  const daysLeft = Math.ceil((endDate.getTime() - today.getTime()) / (1000 * 3600 * 24))
  if (daysLeft < 30) return 'Expiring Soon'
  return 'Active & Valid'
})

const validityStatusClass = computed(() => {
  const status = validityStatus.value
  if (status === 'Expired') return 'bg-red-50 border border-red-200'
  if (status === 'Terminated') return 'bg-orange-50 border border-orange-200'
  if (status === 'Expiring Soon') return 'bg-yellow-50 border border-yellow-200'
  if (status === 'Draft - Not Yet Active') return 'bg-gray-50 border border-gray-200'
  return 'bg-green-50 border border-green-200'
})

const validityTextClass = computed(() => {
  const status = validityStatus.value
  if (status === 'Expired') return 'text-red-700'
  if (status === 'Terminated') return 'text-orange-700'
  if (status === 'Expiring Soon') return 'text-yellow-700'
  if (status === 'Draft - Not Yet Active') return 'text-gray-700'
  return 'text-green-700'
})

const validityIcon = computed(() => {
  const status = validityStatus.value
  if (status === 'Expired' || status === 'Terminated') return 'pi-times-circle'
  if (status === 'Expiring Soon') return 'pi-exclamation-triangle'
  if (status === 'Draft - Not Yet Active') return 'pi-pencil'
  return 'pi-check-circle'
})

const loadContract = async () => {
  loading.value = true
  try {
    const response = await procurementService.getSupplierContract(route.params.id as string)
    contract.value = response.data
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load contract', life: 3000 })
    setTimeout(() => router.back(), 2000)
  } finally {
    loading.value = false
  }
}

const editContract = () => {
  router.push({ name: 'procurement.supplier-contracts.edit', params: { id: route.params.id } })
}

const activateContract = async () => {
  activating.value = true
  try {
    await procurementService.activateSupplierContract(route.params.id as string)
    toast.add({ severity: 'success', summary: 'Success', detail: 'Contract activated', life: 3000 })
    loadContract()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to activate contract',
      life: 3000,
    })
  } finally {
    activating.value = false
  }
}

const terminateContract = async () => {
  activating.value = true
  try {
    await procurementService.terminateSupplierContract(route.params.id as string)
    toast.add({ severity: 'success', summary: 'Success', detail: 'Contract terminated', life: 3000 })
    showTerminateDialog.value = false
    loadContract()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to terminate contract',
      life: 3000,
    })
  } finally {
    activating.value = false
  }
}

const downloadDocument = (filePath: string) => {
  if (filePath) {
    window.location.href = filePath
  }
}

onMounted(() => {
  loadContract()
})
</script>
