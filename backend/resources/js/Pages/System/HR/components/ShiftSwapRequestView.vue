<!-- views/system/shifts/components/ShiftSwapRequestsView.vue -->
<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div class="flex items-center gap-3">
        <h3 class="text-lg font-medium">Shift Swap Requests</h3>
        <Badge v-if="pendingCount" :value="pendingCount" severity="warning" />
      </div>
      <Button label="New Swap Request" icon="pi pi-plus" severity="info" @click="showCreateDialog = true" />
    </div>

    <!-- Filters -->
    <div class="flex gap-3">
      <IconField>
        <InputIcon class="pi pi-search" />
        <InputText v-model="filters.search" placeholder="Search employee..." size="small" />
      </IconField>
      <Select v-model="filters.status" :options="statusOptions" placeholder="All Status" showClear size="small" />
      <Select v-model="filters.type" :options="swapTypeOptions" placeholder="Swap Type" showClear size="small" />
    </div>

    <!-- Swap Requests Table -->
    <DataTable :value="filteredSwapRequests" :loading="loading" :paginator="true" :rows="10" class="text-sm">
      <Column field="requestor_name" header="Requestor" :sortable="true">
        <template #body="{ data }">
          <div class="flex items-center gap-2">
            <Avatar :label="getInitials(data.requestor_name)" size="small" class="bg-blue-100 text-blue-600" />
            <div>
              <div>{{ data.requestor_name }}</div>
              <div class="text-xs text-gray-400">{{ data.requestor_shift }}</div>
            </div>
          </div>
        </template>
      </Column>
      <Column header="Swap Type">
        <template #body="{ data }">
          <Tag :value="data.swap_type" :severity="getSwapTypeSeverity(data.swap_type)" rounded />
        </template>
      </Column>
      <Column field="receiver_name" header="Receiver" :sortable="true">
        <template #body="{ data }">
          <div class="flex items-center gap-2">
            <Avatar :label="getInitials(data.receiver_name)" size="small" class="bg-purple-100 text-purple-600" />
            <div>
              <div>{{ data.receiver_name }}</div>
              <div class="text-xs text-gray-400">{{ data.receiver_shift }}</div>
            </div>
          </div>
        </template>
      </Column>
      <Column field="swap_date" header="Date">
        <template #body="{ data }">
          {{ data.swap_date }}
        </template>
      </Column>
      <Column field="reason" header="Reason">
        <template #body="{ data }">
          <span class="text-sm">{{ data.reason || '—' }}</span>
        </template>
      </Column>
      <Column field="status_badge" header="Status">
        <template #body="{ data }">
          <Tag :value="data.status_badge.label" :severity="data.status_badge.color" rounded />
        </template>
      </Column>
      <Column header="Actions" style="width: 150px">
        <template #body="{ data }">
          <div class="flex gap-1">
            <Button v-if="canAccept(data)" icon="pi pi-check" text rounded severity="success" size="small"
              @click="acceptRequest(data)" tooltip="Accept" />
            <Button v-if="canReject(data)" icon="pi pi-times" text rounded severity="danger" size="small"
              @click="showRejectDialog(data)" tooltip="Reject" />
            <Button v-if="canCancel(data)" icon="pi pi-undo" text rounded severity="warning" size="small"
              @click="cancelRequest(data)" tooltip="Cancel" />
            <Button icon="pi pi-eye" text rounded severity="info" size="small" @click="viewDetails(data)"
              tooltip="View" />
          </div>
        </template>
      </Column>
    </DataTable>

    <!-- Create Swap Request Dialog -->
    <Dialog v-model:visible="showCreateDialog" header="New Shift Swap Request" modal :style="{ width: '600px' }">
      <div class="space-y-4 p-2">
        <div class="field">
          <label class="block text-sm font-medium mb-1">Receiver <span class="text-red-500">*</span></label>
          <Select v-model="swapForm.receiver_id" :options="employees" optionLabel="label" optionValue="value"
            placeholder="Select employee to swap with" class="w-full" filter />
        </div>
        <div class="field">
          <label class="block text-sm font-medium mb-1">Swap Type <span class="text-red-500">*</span></label>
          <div class="flex gap-3">
            <div class="flex-1">
              <SelectButton v-model="swapForm.swap_type" :options="swapTypeOptions" optionLabel="label" optionValue="value" />
            </div>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div class="field">
            <label class="block text-sm font-medium mb-1">Your Shift <span class="text-red-500">*</span></label>
            <Select v-model="swapForm.requestor_schedule_id" :options="myUpcomingShifts" optionLabel="label"
              optionValue="value" placeholder="Select your shift" class="w-full" />
          </div>
          <div class="field">
            <label class="block text-sm font-medium mb-1">Their Shift <span class="text-red-500">*</span></label>
            <Select v-model="swapForm.receiver_schedule_id" :options="receiverShifts" optionLabel="label"
              optionValue="value" placeholder="Select their shift" class="w-full" />
          </div>
        </div>
        <div class="field">
          <label class="block text-sm font-medium mb-1">Reason (Optional)</label>
          <Textarea v-model="swapForm.reason" rows="3" class="w-full" placeholder="Why do you want to swap?" />
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" icon="pi pi-times" text @click="showCreateDialog = false" />
        <Button label="Submit Request" icon="pi pi-send" severity="info" @click="submitSwapRequest"
          :loading="submitting" />
      </template>
    </Dialog>

    <!-- Reject Dialog -->
    <Dialog v-model:visible="showRejectDialogFlag" header="Reject Swap Request" modal :style="{ width: '400px' }">
      <div class="space-y-3 p-2">
        <p>Are you sure you want to reject this swap request?</p>
        <div class="field">
          <label class="block text-sm font-medium mb-1">Reason (Optional)</label>
          <Textarea v-model="rejectReason" rows="2" class="w-full" placeholder="Optional rejection reason" />
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" icon="pi pi-times" text @click="showRejectDialogFlag = false" />
        <Button label="Reject Request" icon="pi pi-times" severity="danger" @click="rejectRequest" :loading="rejecting" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

const props = defineProps<{
  swapRequests: any[]
  loading: boolean
  employees: any[]
  pendingCount: number
}>()

const emit = defineEmits<{
  (e: 'refresh'): void
  (e: 'create', data: any): void
  (e: 'accept', id: number): void
  (e: 'reject', id: number, reason?: string): void
  (e: 'cancel', id: number): void
}>()

// State
const filters = ref({
  search: '',
  status: null as string | null,
  type: null as string | null
})

const showCreateDialog = ref(false)
const showRejectDialogFlag = ref(false)
const selectedRequestId = ref<number | null>(null)
const rejectReason = ref('')
const submitting = ref(false)
const rejecting = ref(false)

const swapForm = ref({
  receiver_id: null as number | null,
  requestor_schedule_id: null as number | null,
  receiver_schedule_id: null as number | null,
  swap_type: 'full_swap',
  reason: ''
})

// Options
const statusOptions = [
  { label: 'Pending', value: 'pending' },
  { label: 'Accepted', value: 'accepted' },
  { label: 'Rejected', value: 'rejected' },
  { label: 'Cancelled', value: 'cancelled' }
]

const swapTypeOptions = [
  { label: 'Full Swap', value: 'full_swap' },
  { label: 'Give Away', value: 'give_away' },
  { label: 'Pick Up', value: 'pick_up' }
]

// Mock data - replace with actual API calls
const myUpcomingShifts = ref([
  { label: 'Morning Shift - Mar 15, 2026 (9AM-6PM)', value: 1 },
  { label: 'Evening Shift - Mar 16, 2026 (2PM-10PM)', value: 2 }
])

const receiverShifts = computed(() => {
  if (!swapForm.value.receiver_id) return []
  return [
    { label: 'Evening Shift - Mar 15, 2026 (2PM-10PM)', value: 3 },
    { label: 'Morning Shift - Mar 16, 2026 (9AM-6PM)', value: 4 }
  ]
})

// Computed
const filteredSwapRequests = computed(() => {
  let filtered = props.swapRequests

  if (filters.value.search) {
    const search = filters.value.search.toLowerCase()
    filtered = filtered.filter(sr => 
      sr.requestor_name?.toLowerCase().includes(search) ||
      sr.receiver_name?.toLowerCase().includes(search)
    )
  }

  if (filters.value.status) {
    filtered = filtered.filter(sr => sr.status === filters.value.status)
  }

  if (filters.value.type) {
    filtered = filtered.filter(sr => sr.swap_type === filters.value.type)
  }

  return filtered
})

// Methods
const getInitials = (name: string): string => {
  if (!name) return '?'
  return name.split(' ').map(n => n[0]).join('').toUpperCase()
}

const getSwapTypeSeverity = (type: string): string => {
  const map: Record<string, string> = {
    'full_swap': 'info',
    'give_away': 'warning',
    'pick_up': 'success'
  }
  return map[type] || 'secondary'
}

const canAccept = (request: any): boolean => {
  // Add logic based on user role and request status
  return request.status === 'pending'
}

const canReject = (request: any): boolean => {
  return request.status === 'pending'
}

const canCancel = (request: any): boolean => {
  // Only requestor can cancel pending requests
  return request.status === 'pending' // Add user check
}

const acceptRequest = (request: any) => {
  emit('accept', request.id)
}

const showRejectDialog = (request: any) => {
  selectedRequestId.value = request.id
  showRejectDialogFlag.value = true
}

const rejectRequest = async () => {
  if (!selectedRequestId.value) return
  rejecting.value = true
  try {
    await emit('reject', selectedRequestId.value, rejectReason.value)
    showRejectDialogFlag.value = false
    rejectReason.value = ''
    selectedRequestId.value = null
  } finally {
    rejecting.value = false
  }
}

const cancelRequest = (request: any) => {
  emit('cancel', request.id)
}

const viewDetails = (request: any) => {
  console.log('View details:', request)
}

const submitSwapRequest = async () => {
  submitting.value = true
  try {
    await emit('create', swapForm.value)
    showCreateDialog.value = false
    swapForm.value = {
      receiver_id: null,
      requestor_schedule_id: null,
      receiver_schedule_id: null,
      swap_type: 'full_swap',
      reason: ''
    }
  } finally {
    submitting.value = false
  }
}
</script>