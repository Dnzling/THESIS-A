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
          <div class="flex flex-col gap-5 sm:flex-row sm:items-start">
            <div class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
              <img v-if="detail.logo_url" :src="detail.logo_url" :alt="`${detail.company_name || detail.supplier_name} logo`" class="h-full w-full object-contain" />
              <i v-else class="pi pi-building text-3xl text-slate-400" />
            </div>
            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                <h3 class="text-lg font-semibold text-slate-900">{{ detail.company_name || detail.supplier_name || 'Supplier' }}</h3>
                <Tag value="Verified" severity="success" />
              </div>
              <p v-if="detail.company_name && detail.supplier_name" class="mt-1 text-sm text-slate-500">{{ detail.supplier_name }}</p>
              <div class="mt-3 flex flex-wrap items-center gap-2">
                <div v-if="detail.rating !== null" class="flex items-center gap-1.5">
                  <Rating :modelValue="Number(detail.rating)" :cancel="false" :readonly="true" />
                  <span class="text-sm font-medium text-slate-700">{{ Number(detail.rating).toFixed(1) }}/5</span>
                  <span class="text-xs text-slate-500">({{ detail.rating_count }} ratings from other stores)</span>
                </div>
                <span v-else class="text-sm text-slate-500">No ratings from other stores yet</span>
              </div>
            </div>
          </div>
          <div class="mt-5 grid grid-cols-1 gap-4 border-t border-slate-100 pt-4 text-sm md:grid-cols-2">
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
          <div class="flex items-center justify-between px-4 pt-4">
            <span class="font-semibold text-slate-800">Supplier Products</span>
            <Tag :value="`${detail.products?.length || 0} listed`" severity="secondary" />
          </div>
        </template>
        <template #content>
          <DataTable :value="detail.products || []" dataKey="id" size="small" stripedRows>
            <template #empty>
              <div class="py-5 text-center text-sm text-slate-500">This supplier has not listed products yet.</div>
            </template>
            <Column field="name" header="Product" />
            <Column field="sku" header="SKU"><template #body="{ data }">{{ data.sku || '-' }}</template></Column>
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
import Rating from 'primevue/rating'
import Tag from 'primevue/tag'
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
