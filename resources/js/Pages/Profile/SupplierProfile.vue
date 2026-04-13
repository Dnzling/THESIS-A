<template>
  <div class="min-h-screen">
    <div class="mx-auto min-w-7xl px-6">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-400">Supplier Profile</p>
          <h1 class="text-2xl font-semibold text-slate-900">Supplier Portal</h1>
          <p class="text-sm text-slate-500">Manage your verification and supplier details.</p>
        </div>
        <div class="flex flex-wrap gap-2">
          <Button :label="verificationLabel" icon="pi pi-check-circle" severity="success" class="small-pill"
            :disabled="isVerified" @click="goToVerification" />
        </div>
      </div>
  
      <div v-if="loading" class="mt-10 flex items-center justify-center">
        <ProgressSpinner />
      </div>
  
      <template v-else>
        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-[1.2fr_0.8fr]">
          <section v-if="!isPaymentAccountView" class="rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm">
            <div class="flex items-start justify-between gap-4">
              <div>
                <p class="text-lg font-semibold text-slate-900">{{ supplierName }}</p>
                <p class="text-sm text-slate-500">{{ supplierTypeLabel }}</p>
              </div>
              <Tag :value="statusLabel" :severity="statusSeverity" rounded />
            </div>
  
            <div class="mt-5 overflow-x-auto rounded-xl border border-slate-200">
              <table class="min-w-full text-sm">
                <tbody class="divide-y divide-slate-200">
                  <tr class="bg-white">
                    <th class="w-44 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Company Name</th>
                    <td class="px-4 py-3 text-slate-800">{{ supplier?.company_name || supplier?.supplier_name || '-' }}</td>
                  </tr>
                  <tr class="bg-slate-50/70">
                    <th class="w-44 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Contact Person</th>
                    <td class="px-4 py-3 text-slate-800">{{ supplier?.contact_person || '-' }}</td>
                  </tr>
                  <tr class="bg-white">
                    <th class="w-44 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Email</th>
                    <td class="px-4 py-3 text-slate-800">{{ supplier?.email || user?.email || '-' }}</td>
                  </tr>
                  <tr class="bg-slate-50/70">
                    <th class="w-44 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Phone</th>
                    <td class="px-4 py-3 text-slate-800">{{ supplier?.phone || '-' }}</td>
                  </tr>
                  <tr class="bg-white">
                    <th class="w-44 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Address</th>
                    <td class="px-4 py-3 text-slate-800">{{ fullAddress }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
  
            <div v-if="portal?.rejection_reason"
              class="mt-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
              <p class="font-semibold">Verification Feedback</p>
              <p class="mt-1 text-rose-700">{{ portal?.rejection_reason }}</p>
            </div>
          </section>
  
          <section v-if="!isPaymentAccountView" class="rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm space-y-4">
            <div>
              <p class="text-sm font-semibold text-slate-800">Verification Status</p>
              <p class="text-xs text-slate-500">Upload or update documents to verify your account.</p>
            </div>
  
            <div class="overflow-x-auto rounded-xl border border-slate-200">
              <table class="min-w-full text-sm">
                <tbody class="divide-y divide-slate-200">
                  <tr class="bg-white">
                    <th class="w-44 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Account Status</th>
                    <td class="px-4 py-3">
                      <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-700">{{ statusHint }}</span>
                        <Tag :value="statusLabel" :severity="statusSeverity" rounded />
                      </div>
                    </td>
                  </tr>
                  <tr class="bg-slate-50/70">
                    <th class="w-44 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Verification</th>
                    <td class="px-4 py-3">
                      <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-700">{{ verificationLabel }}</span>
                        <Button :label="verificationLabel" severity="info" class="small-pill" :disabled="isVerified" @click="goToVerification" />
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>

          <section v-if="isPaymentAccountView" ref="paymentAccountSectionEl" class="rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm space-y-4 lg:col-span-2">
            <div>
              <p class="text-lg font-semibold text-slate-900">Payment Account Records</p>
              <p class="text-xs text-slate-500">Payment history only.</p>
            </div>

            <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-4">
              <div class="text-xs text-slate-500">Supplier Balance (Total)</div>
              <div class="mt-1 text-lg font-semibold text-slate-900">PHP {{ formatMoney(supplier?.current_balance || 0) }}</div>
            </div>

            <div class="rounded-xl border border-slate-100 bg-white p-4">
              <div class="flex items-center justify-between mb-3">
                <p class="text-sm font-semibold text-slate-800">Payment History</p>
                <div class="flex items-center gap-2">
                  <span class="text-xs text-slate-500">Latest payments from finance and other payers</span>
                  <Button
                    label="Refresh"
                    size="small"
                    text
                    :loading="loadingPaymentHistory"
                    @click="loadPaymentHistory"
                  />
                </div>
              </div>
              <DataTable
                :value="paymentHistoryRows"
                :loading="loadingPaymentHistory"
                responsiveLayout="scroll"
                stripedRows
                size="small"
                class="text-sm"
              >
                <Column field="payer" header="Store Name" style="min-width: 180px" />
                <Column field="invoice" header="Invoice" style="min-width: 130px" />
                <Column field="method" header="Method" style="min-width: 120px" />
                <Column header="Amount" style="min-width: 120px">
                  <template #body="{ data }">
                    <span class="font-semibold text-slate-800">PHP {{ formatMoney(data.amount) }}</span>
                  </template>
                </Column>
                <Column header="Status" style="min-width: 120px">
                  <template #body="{ data }">
                    <Tag :value="String(data.status || 'pending').toUpperCase()" :severity="paymentStatusSeverity(data.status)" rounded />
                  </template>
                </Column>
                <Column header="Date" style="min-width: 150px">
                  <template #body="{ data }">
                    {{ formatDateTime(data.created_at) }}
                  </template>
                </Column>
                <template #empty>
                  <div class="py-4 text-center text-slate-500 text-xs">No payment records yet.</div>
                </template>
              </DataTable>
            </div>
          </section>
  
        </div>
      </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, nextTick, onBeforeUnmount, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { useAuthStore } from '@/stores/auth'
import supplierService from '@/services/supplier.service'
import SystemLayout from '@/Layouts/SystemLayout.vue'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'

defineOptions({ layout: SystemLayout })

const authStore = useAuthStore()
const loading = ref(true)
const portal = ref<any | null>(null)
const supplier = ref<any | null>(null)
const paymentAccountSectionEl = ref<HTMLElement | null>(null)
const loadingPaymentHistory = ref(false)
const paymentHistoryRows = ref<any[]>([])
const paymentHistoryPolling = ref<ReturnType<typeof setInterval> | null>(null)
const user = computed(() => authStore.user)

const isSupplier = computed(() => String(authStore.user?.role || '').toLowerCase().includes('supplier'))
const isPaymentAccountView = computed(() => {
  if (typeof window === 'undefined') return false
  const pathname = window.location.pathname || ''
  const params = new URLSearchParams(window.location.search || '')
  const section = (params.get('section') || '').toLowerCase()
  return pathname === '/supplier-portal/payment-account' || section === 'payment-account'
})

const supplierName = computed(() => supplier.value?.company_name || supplier.value?.supplier_name || 'Supplier')
const supplierTypeLabel = computed(() => {
  const raw = supplier.value?.supplier_type || ''
  return raw ? raw.toString().replace(/_/g, ' ').replace(/\b\w/g, (c: string) => c.toUpperCase()) : 'Supplier Type'
})

const fullAddress = computed(() => {
  const parts = [
    supplier.value?.address,
    supplier.value?.city,
    supplier.value?.province,
    supplier.value?.postal_code,
    supplier.value?.country,
  ].filter(Boolean)
  return parts.length ? parts.join(', ') : '-'
})

const statusLabel = computed(() => {
  const status = portal.value?.status || 'pending'
  return status.toString().toUpperCase()
})

const statusSeverity = computed(() => {
  const status = (portal.value?.status || '').toLowerCase()
  if (status === 'approved') return 'success'
  if (status === 'rejected') return 'danger'
  return 'warning'
})

const paymentTermsLabel = computed(() => {
  const raw = supplier.value?.payment_terms || '-'
  return raw.toString().replace(/_/g, ' ').replace(/\b\w/g, (c: string) => c.toUpperCase())
})

const isVerified = computed(() => (portal.value?.status || '').toLowerCase() === 'approved')
const verificationLabel = computed(() => (isVerified.value ? 'Verified' : 'Verify Account'))
const statusHint = computed(() => {
  if (isVerified.value) return 'Your account is verified.'
  if ((portal.value?.status || '').toLowerCase() === 'rejected') return 'Please review the feedback and resubmit.'
  return 'Verification pending. Upload the required documents.'
})

const goToVerification = () => {
  if (isVerified.value) return
  router.visit('/supplier-portal/registration')
}

const loadPortal = async () => {
  loading.value = true
  try {
    const response = await supplierService.getMyPortal()
    if (!response?.success) {
      router.visit('/supplier-portal/registration')
      return
    }
    portal.value = response.data
    supplier.value = response.data?.supplier || null
    await loadPaymentHistory()
  } catch (error) {
    router.visit('/supplier-portal/registration')
  } finally {
    loading.value = false
    await nextTick()
  }
}

const loadPaymentHistory = async () => {
  const supplierId = Number(supplier.value?.id || portal.value?.supplier_id || 0)
  if (!supplierId) {
    paymentHistoryRows.value = []
    return
  }

  loadingPaymentHistory.value = true
  try {
    const res = await supplierService.getPaymentHistory(supplierId, {
      page: 1,
      per_page: 20,
      sort_by: 'created_at',
      sort_order: 'desc',
    })
    const root = res?.data ?? res
    const rows = Array.isArray(root)
      ? root
      : Array.isArray(root?.data)
        ? root.data
        : Array.isArray(res?.payments)
          ? res.payments
          : []

    paymentHistoryRows.value = rows.map((row: any) => ({
      payer: row?.payer_name || row?.store_name || row?.paid_by_name || row?.created_by_name || 'Store',
      invoice: row?.invoice_number || row?.invoice?.invoice_number || `INV-${row?.invoice_id ?? '-'}`,
      method: row?.payment_method || '-',
      amount: Number(row?.payment_amount ?? row?.amount ?? 0),
      status: row?.status || 'pending',
      created_at: row?.created_at || row?.payment_date || null,
    }))
  } catch (error) {
    paymentHistoryRows.value = []
  } finally {
    loadingPaymentHistory.value = false
  }
}

const toastSuccess = (msg: string) => {
  // lazy import primevue toast if needed later
  console.log(msg)
}
const toastError = (msg: string) => {
  console.warn(msg)
}

const formatMoney = (value: number | string) => {
  return new Intl.NumberFormat('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(value || 0))
}

const formatDateTime = (value: string | null) => {
  if (!value) return '-'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return '-'
  return date.toLocaleString('en-PH', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const paymentStatusSeverity = (status: string) => {
  const normalized = String(status || '').toLowerCase()
  if (normalized === 'paid' || normalized === 'processed' || normalized === 'completed') return 'success'
  if (normalized === 'cancelled' || normalized === 'failed' || normalized === 'rejected') return 'danger'
  if (normalized === 'pending' || normalized === 'pending_approval') return 'warning'
  return 'info'
}

onMounted(async () => {
  if (!isSupplier.value) {
    router.visit('/profile')
    return
  }
  await loadPortal()

  if (isPaymentAccountView.value) {
    await nextTick()
    paymentAccountSectionEl.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
    await loadPaymentHistory()
    if (!paymentHistoryPolling.value) {
      paymentHistoryPolling.value = setInterval(() => {
        loadPaymentHistory()
      }, 12000)
    }
  }
})

watch(isPaymentAccountView, async (isActive) => {
  if (!isActive) return
  await nextTick()
  await loadPaymentHistory()
})

onBeforeUnmount(() => {
  if (paymentHistoryPolling.value) {
    clearInterval(paymentHistoryPolling.value)
    paymentHistoryPolling.value = null
  }
})
</script>

<style scoped>
.small-pill {
  border-radius: 999px;
  font-size: 0.85rem;
  padding: 0.45rem 0.9rem;
}
</style>
