<template>
  <div class="max-w-5xl mx-auto space-y-6 pb-6">
    <div class="flex items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <Button icon="pi pi-arrow-left" text rounded @click="router.push({ name: 'procurement.suppliers.create' })" />
        <div>
          <h2 class="text-2xl font-bold text-gray-800">Verified Supplier Details</h2>
          <p class="text-sm text-gray-500 mt-1">Review the supplier profile before linking to your store.</p>
        </div>
      </div>
      <Button
        :label="detail?.already_linked ? 'View in Procurement' : 'Link Supplier'"
        :icon="detail?.already_linked ? 'pi pi-eye' : 'pi pi-link'"
        severity="success"
        :disabled="detail?.already_linked ? !detail?.linked_supplier_id : false"
        :loading="linking"
        @click="detail?.already_linked ? viewLinkedSupplier() : linkSupplier()"
      />
    </div>

    <Card v-if="loading">
      <template #content>
        <div class="py-8 text-center text-slate-500">Loading supplier details...</div>
      </template>
    </Card>

    <template v-else-if="detail">
      <Card>
        <template #content>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
              <p class="text-slate-500">Supplier Name</p>
              <p class="font-semibold text-slate-900">{{ detail.supplier_name || '-' }}</p>
            </div>
            <div>
              <p class="text-slate-500">Company Name</p>
              <p class="font-semibold text-slate-900">{{ detail.company_name || '-' }}</p>
            </div>
            <div>
              <p class="text-slate-500">Contact Person</p>
              <p class="font-semibold text-slate-900">{{ detail.contact_person || '-' }}</p>
            </div>
            <div>
              <p class="text-slate-500">Email</p>
              <p class="font-semibold text-slate-900">{{ detail.email || '-' }}</p>
            </div>
            <div>
              <p class="text-slate-500">Phone</p>
              <p class="font-semibold text-slate-900">{{ detail.phone || '-' }}</p>
            </div>
            <div>
              <p class="text-slate-500">Verified At</p>
              <p class="font-semibold text-slate-900">{{ formatDate(detail.verified_at) }}</p>
            </div>
            <div class="md:col-span-2">
              <p class="text-slate-500">Address</p>
              <p class="font-semibold text-slate-900">{{ fullAddress }}</p>
            </div>
          </div>

          <div v-if="detail.already_linked" class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-amber-700 text-sm">
            This supplier is already linked to your store.
          </div>
        </template>
      </Card>

      <Card>
        <template #header>
          <div class="px-4 pt-4 font-semibold text-slate-800">Verification Documents</div>
        </template>
        <template #content>
          <DataTable :value="detail.verification_documents || []" dataKey="id" size="small" stripedRows>
            <template #empty>
              <div class="py-4 text-center text-slate-500 text-sm">No verification documents found.</div>
            </template>
            <Column field="document_type" header="Document" />
            <Column field="status" header="Status" />
            <Column field="rejection_reason" header="Review Notes">
              <template #body="{ data }">{{ data.rejection_reason || '-' }}</template>
            </Column>
            <Column field="reviewed_at" header="Reviewed At">
              <template #body="{ data }">{{ formatDate(data.reviewed_at) }}</template>
            </Column>
          </DataTable>
        </template>
      </Card>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import procurementService from '../../../../services/procurement.service'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const loading = ref(false)
const linking = ref(false)
const detail = ref<any>(null)

const portalId = computed(() => Number(route.params.portalId))

const fullAddress = computed(() => {
  if (!detail.value) return '-'
  return [detail.value.address, detail.value.city, detail.value.province, detail.value.country].filter(Boolean).join(', ') || '-'
})

const formatDate = (value: string | null) => {
  if (!value) return '-'
  const d = new Date(value)
  return Number.isNaN(d.getTime()) ? '-' : d.toLocaleString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' })
}

const loadDetail = async () => {
  if (!portalId.value) return
  loading.value = true
  try {
    const response = await procurementService.getVerifiedSupplierDirectoryItem(portalId.value)
    const payload = response?.data ?? response ?? {}
    detail.value = payload?.data ?? payload ?? null
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to load supplier details', life: 3500 })
  } finally {
    loading.value = false
  }
}

const linkSupplier = async () => {
  if (!detail.value || detail.value.already_linked) return
  linking.value = true
  try {
    await procurementService.createSupplier({ supplier_portal_id: detail.value.supplier_portal_id })
    toast.add({ severity: 'success', summary: 'Success', detail: 'Supplier linked successfully.', life: 2500 })
    router.push({ name: 'procurement.suppliers' })
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to link supplier', life: 3500 })
  } finally {
    linking.value = false
  }
}

const viewLinkedSupplier = () => {
  const id = Number(detail.value?.linked_supplier_id || 0)
  if (!id) return
  router.push({ name: 'procurement.suppliers.detail', params: { id } })
}

onMounted(loadDetail)
</script>
