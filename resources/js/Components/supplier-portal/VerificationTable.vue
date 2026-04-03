<template>
  <div>
    <div v-if="!loading && verifications && verifications.length > 0">
      <DataTable :value="verifications" striped-rows responsive-layout="scroll">
        <Column header="Supplier Name">
          <template #body="{ data }">
            <div>
              <p class="font-semibold">{{ data.user?.name }}</p>
              <p class="text-sm text-gray-500">{{ data.user?.email }}</p>
            </div>
          </template>
        </Column>

        <Column header="Company">
          <template #body="{ data }">
            {{ data.company_name || '-' }}
          </template>
        </Column>

        <Column header="Status">
          <template #body="{ data }">
            <Tag :value="data.status" :severity="getStatusSeverity(data.status)" />
          </template>
        </Column>

        <Column header="Submitted">
          <template #body="{ data }">
            {{ formatDate(data.last_submission_at) }}
          </template>
        </Column>

        <Column header="Documents">
          <template #body="{ data }">
            <Tag 
              v-if="data.verification_documents"
              :value="`${data.verification_documents.length}/4`"
              severity="info"
            />
          </template>
        </Column>

        <Column header="Actions" :exportable="false">
          <template #body="{ data }">
            <div class="flex gap-2">
              <Button 
                icon="pi pi-eye"
                class="p-button-rounded p-button-info p-button-sm"
                @click="$emit('view', data.id)"
                v-tooltip.top="'View Details'"
              />
              <Button 
                v-if="status === 'pending'"
                icon="pi pi-check"
                class="p-button-rounded p-button-success p-button-sm"
                @click="$emit('approve', data.id)"
                v-tooltip.top="'Approve'"
              />
              <Button 
                v-if="status === 'pending'"
                icon="pi pi-times"
                class="p-button-rounded p-button-danger p-button-sm"
                @click="$emit('reject', data)"
                v-tooltip.top="'Reject'"
              />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <Message 
      v-else-if="!loading && (!verifications || verifications.length === 0)"
      severity="info"
      text="No supplier verification requests found."
      class="w-full"
    />

    <ProgressSpinner v-if="loading" />
  </div>
</template>

<script setup lang="ts">
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Message from 'primevue/message'
import ProgressSpinner from 'primevue/progressspinner'

defineProps({
  verifications: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
  status: {
    type: String,
    default: 'pending',
  },
})

defineEmits(['approve', 'reject', 'view'])

const getStatusSeverity = (status: string) => {
  const map: { [key: string]: string } = {
    pending: 'warning',
    approved: 'success',
    rejected: 'danger',
  }
  return map[status] || 'info'
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString()
}
</script>
