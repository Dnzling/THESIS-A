<template>
  <div class="p-6 max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">Leave Details</h2>
        <p class="text-sm text-gray-500">Review leave request information and status.</p>
      </div>
      <Button label="Back to History" icon="pi pi-arrow-left" severity="secondary" outlined
        @click="router.push({ name: 'hr.leaves.history' })" />
    </div>

    <div v-if="loading" class="space-y-3">
      <Skeleton height="4rem" />
      <Skeleton height="10rem" />
      <Skeleton height="10rem" />
    </div>

    <div v-else-if="leave" class="space-y-6">
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <Avatar :label="getInitials(leave.employeeName)" size="large"
              class="bg-blue-100 text-blue-600 font-medium" />
            <div>
              <div class="font-semibold text-gray-800">{{ leave.employeeName }}</div>
              <div class="text-xs text-gray-500">{{ leave.department }}</div>
            </div>
          </div>
          <Tag :value="leave.statusLabel" :severity="statusSeverity(leave.status)" rounded />
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="grid grid-cols-2 gap-4">
          <div class="bg-gray-50 rounded-lg p-3">
            <div class="text-xs text-gray-500 mb-1">Leave Type</div>
            <Tag :value="leave.leaveType" severity="info" rounded />
          </div>
          <div class="bg-gray-50 rounded-lg p-3">
            <div class="text-xs text-gray-500 mb-1">Total Days</div>
            <div class="font-medium text-gray-800">{{ leave.duration }} days</div>
          </div>
          <div class="bg-gray-50 rounded-lg p-3">
            <div class="text-xs text-gray-500 mb-1">Start Date</div>
            <div class="font-medium text-gray-800">{{ formatDate(leave.startDate) }}</div>
          </div>
          <div class="bg-gray-50 rounded-lg p-3">
            <div class="text-xs text-gray-500 mb-1">End Date</div>
            <div class="font-medium text-gray-800">{{ formatDate(leave.endDate) }}</div>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
        <div>
          <div class="text-xs text-gray-500 mb-1">Reason</div>
          <div class="text-sm text-gray-700">{{ leave.reason || 'N/A' }}</div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <div class="text-xs text-gray-500 mb-1">Submitted</div>
            <div class="text-sm text-gray-700">{{ formatDateTime(leave.submittedDate) }}</div>
          </div>
          <div>
            <div class="text-xs text-gray-500 mb-1">Paid Leave</div>
            <div class="text-sm text-gray-700">{{ leave.isPaid ? 'Yes' : 'No' }}</div>
          </div>
        </div>

        <div v-if="leave.approverName" class="grid grid-cols-2 gap-4">
          <div>
            <div class="text-xs text-gray-500 mb-1">Approved By</div>
            <div class="text-sm text-gray-700">{{ leave.approverName }}</div>
          </div>
          <div>
            <div class="text-xs text-gray-500 mb-1">Approved At</div>
            <div class="text-sm text-gray-700">{{ formatDateTime(leave.approvedAt) }}</div>
          </div>
        </div>

        <div v-if="leave.rejectedReason" class="bg-red-50 border border-red-100 rounded-lg p-3 text-sm text-red-700">
          <div class="text-xs uppercase text-red-600 mb-1">Rejection Reason</div>
          {{ leave.rejectedReason }}
        </div>

        <div v-if="leave.attachment" class="flex items-center gap-2 text-blue-600">
          <i class="pi pi-paperclip"></i>
          <a :href="leave.attachment" target="_blank" class="text-sm font-medium">View Attachment</a>
        </div>
      </div>
    </div>

    <div v-else class="text-center py-10 text-gray-400">
      <i class="pi pi-file text-4xl mb-2"></i>
      <p>Leave request not found.</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import hrService from '@/services/hr.services'
import Avatar from 'primevue/avatar'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import Skeleton from 'primevue/skeleton'

const route = useRoute()
const router = useRouter()
const loading = ref(false)
const leave = ref<any | null>(null)

const getInitials = (name: string): string => {
  if (!name) return '?'
  return name.split(' ').map(n => n[0]).join('').toUpperCase()
}

const formatDate = (date: string | null): string => {
  if (!date) return 'N/A'
  try {
    return new Date(date).toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric'
    })
  } catch {
    return date
  }
}

const formatDateTime = (date: string | null): string => {
  if (!date) return 'N/A'
  try {
    return new Date(date).toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  } catch {
    return date
  }
}

const statusSeverity = (status: string) => {
  const map: Record<string, string> = {
    pending: 'warning',
    approved: 'success',
    rejected: 'danger',
    cancelled: 'secondary',
  }
  return map[status] || 'info'
}

const fetchLeaveDetail = async () => {
  loading.value = true
  try {
    const response = await hrService.api.get(`/api/leaves/${route.params.id}`)
    if (response.data.success) {
      const item = response.data.data
      const employee = item.employee || {}
      leave.value = {
        id: item.id,
        employeeName: employee.fname && employee.lname
          ? `${employee.fname} ${employee.lname}`.trim()
          : 'Unknown',
        department: employee.department || 'N/A',
        leaveType: item.leave_type
          ? item.leave_type.charAt(0).toUpperCase() + item.leave_type.slice(1).replace(/_/g, ' ')
          : 'Unknown',
        startDate: item.start_date,
        endDate: item.end_date,
        duration: item.total_days || 0,
        status: item.status || 'pending',
        statusLabel: item.status ? item.status.charAt(0).toUpperCase() + item.status.slice(1) : 'Pending',
        submittedDate: item.created_at,
        isPaid: item.is_paid,
        approverName: item.approver ? (item.approver.full_name || `${item.approver.fname} ${item.approver.lname}`.trim()) : null,
        approvedAt: item.approved_at,
        rejectedReason: item.rejected_reason,
        reason: item.reason,
        attachment: item.attachment_path,
      }
    }
  } catch (error) {
    leave.value = null
  } finally {
    loading.value = false
  }
}

onMounted(fetchLeaveDetail)
</script>
