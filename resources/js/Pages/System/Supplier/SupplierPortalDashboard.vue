<template>
  <div class="mx-auto max-w-7xl space-y-5 px-4 py-5 sm:px-6 lg:px-8">
    <section class="rounded-2xl border border-orange-100 bg-gradient-to-r from-orange-50 via-white to-white p-5 sm:p-6">
      <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
        <div class="min-w-0">
          <p class="text-xs font-semibold uppercase tracking-wider text-orange-700">Supplier workspace</p>
          <h1 class="mt-1 break-words text-2xl font-semibold tracking-tight text-slate-900">
            Welcome, {{ portal?.supplier?.supplier_name || portal?.supplier_name || 'Supplier' }}
          </h1>
          <p class="mt-1 max-w-2xl text-sm text-slate-600">Review quotations, respond to purchase orders, and manage your supplier account.</p>
        </div>
        <div class="flex shrink-0 flex-wrap gap-2">
          <Button v-if="portal?.status !== 'approved'" label="Complete verification" icon="pi pi-arrow-right" iconPos="right" size="small" @click="router.push('/supplier-portal/registration')" />
          <Button v-else label="Browse RFQs" icon="pi pi-file" size="small" @click="router.push('/supplier-portal/rfqs')" />
          <Button v-if="portal?.status === 'approved'" label="View purchase orders" icon="pi pi-shopping-cart" severity="secondary" outlined size="small" @click="router.push('/supplier-portal/pos')" />
        </div>
      </div>
      <div class="mt-5 flex flex-col gap-3 border-t border-orange-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-2">
          <span class="text-xs font-medium text-slate-600">Account verification</span>
          <Tag :value="portalStatus" :severity="statusSeverity" />
        </div>
        <div class="flex min-w-0 items-center gap-3 sm:w-1/2">
          <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-200">
            <div class="h-full rounded-full bg-orange-500 transition-all" :style="{ width: `${documentProgress}%` }"></div>
          </div>
          <span class="whitespace-nowrap text-xs text-slate-600">{{ submittedDocuments }}/3 documents</span>
        </div>
      </div>
    </section>

    <Message v-if="portal?.status === 'pending'" severity="warn" :closable="false">
      Your supplier account is awaiting verification. Complete your registration and submit the required documents.
    </Message>
    <Message v-else-if="portal?.status === 'rejected'" severity="error" :closable="false">
      <span>Verification was rejected.</span>
      <span v-if="portal.rejection_reason" class="ml-1">{{ portal.rejection_reason }}</span>
    </Message>
    <Message v-else-if="portal?.status === 'approved'" severity="success" :closable="false">
      Your supplier account is verified and active.
    </Message>

    <div v-if="loading" class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <Skeleton v-for="item in 4" :key="item" height="6rem" borderRadius="0.75rem" />
    </div>
    <section v-else-if="stats" class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <Card v-for="card in statCards" :key="card.label" class="border border-slate-200 shadow-none">
        <template #content>
          <div class="flex items-start justify-between gap-3">
            <div>
              <p class="text-xs font-medium text-slate-500">{{ card.label }}</p>
              <p class="mt-2 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
              <p class="mt-1 text-xs text-slate-500">{{ card.hint }}</p>
            </div>
            <i :class="[card.icon, 'text-lg text-orange-600']"></i>
          </div>
        </template>
      </Card>
    </section>

    <section v-if="portal?.status === 'approved'" class="rounded-xl border border-slate-200 bg-white p-4">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 class="text-sm font-semibold text-slate-900">Account shortcuts</h2>
          <p class="mt-0.5 text-xs text-slate-500">Manage your business connections and payout details.</p>
        </div>
        <div class="flex flex-wrap gap-2">
          <Button label="Linked stores" icon="pi pi-building" severity="secondary" outlined size="small" @click="router.push('/supplier-portal/stores')" />
          <Button label="Payment account" icon="pi pi-credit-card" severity="secondary" outlined size="small" @click="openPaymentDialog" />
        </div>
      </div>
    </section>

    <section class="grid grid-cols-1 gap-4 xl:grid-cols-2">
      <Card class="border border-slate-200 shadow-none">
        <template #title>
          <div class="flex items-center justify-between gap-2">
            <div>
              <h2 class="text-base font-semibold text-slate-900">Recent RFQ responses</h2>
              <p class="mt-0.5 text-xs font-normal text-slate-500">Your latest submitted quotations</p>
            </div>
            <Button label="View all" text size="small" @click="router.push('/supplier-portal/rfqs')" />
          </div>
        </template>
        <template #content>
          <DataTable v-if="!loading" :value="recentRFQs.slice(0, 5)" rowHover responsiveLayout="scroll" class="text-sm">
            <Column header="RFQ" style="min-width: 9rem">
              <template #body="{ data }"><span class="font-medium text-slate-900">{{ data.rfq?.rfq_number || `#${data.rfq_id}` }}</span></template>
            </Column>
            <Column field="submitted_at" header="Submitted" style="min-width: 8rem">
              <template #body="{ data }">{{ formatDate(data.submitted_at) }}</template>
            </Column>
            <Column field="quoted_price" header="Quoted price" style="min-width: 8rem">
              <template #body="{ data }">₱{{ formatMoney(data.quoted_price) }}</template>
            </Column>
            <Column field="status" header="Status" style="min-width: 7rem">
              <template #body="{ data }"><Badge :value="formatStatus(data.status)" :severity="statusValueSeverity(data.status)" /></template>
            </Column>
            <Column header="" style="width: 4rem">
              <template #body="{ data }"><Button icon="pi pi-eye" aria-label="View RFQ" rounded text size="small" @click="router.push(`/supplier-portal/rfqs/${data.rfq_id}`)" /></template>
            </Column>
            <template #empty><p class="py-6 text-center text-sm text-slate-500">No RFQ responses yet.</p></template>
          </DataTable>
          <div v-else class="space-y-2"><Skeleton v-for="item in 3" :key="item" height="2.75rem" borderRadius="0.5rem" /></div>
        </template>
      </Card>

      <Card class="border border-slate-200 shadow-none">
        <template #title>
          <div class="flex items-center justify-between gap-2">
            <div>
              <h2 class="text-base font-semibold text-slate-900">Recent purchase orders</h2>
              <p class="mt-0.5 text-xs font-normal text-slate-500">Latest PO responses and actions</p>
            </div>
            <Button label="View all" text size="small" @click="router.push('/supplier-portal/pos')" />
          </div>
        </template>
        <template #content>
          <DataTable v-if="!loading" :value="recentPOs.slice(0, 5)" rowHover responsiveLayout="scroll" class="text-sm">
            <Column header="Purchase order" style="min-width: 10rem">
              <template #body="{ data }"><span class="font-medium text-slate-900">{{ data.purchase_order?.po_number || `#${data.purchase_order_id}` }}</span></template>
            </Column>
            <Column field="submitted_at" header="Received" style="min-width: 8rem">
              <template #body="{ data }">{{ formatDate(data.submitted_at) }}</template>
            </Column>
            <Column field="response" header="Response" style="min-width: 7rem">
              <template #body="{ data }"><Badge :value="formatStatus(data.response)" :severity="statusValueSeverity(data.response)" /></template>
            </Column>
            <Column header="" style="width: 4rem">
              <template #body="{ data }"><Button icon="pi pi-eye" aria-label="View purchase order" rounded text size="small" @click="router.push(`/supplier-portal/pos/${data.purchase_order_id}`)" /></template>
            </Column>
            <template #empty><p class="py-6 text-center text-sm text-slate-500">No purchase orders yet.</p></template>
          </DataTable>
          <div v-else class="space-y-2"><Skeleton v-for="item in 3" :key="item" height="2.75rem" borderRadius="0.5rem" /></div>
        </template>
      </Card>
    </section>
  </div>

  <Dialog v-model:visible="showPaymentDialog" header="Payment Account" modal :style="{ width: 'min(32rem, 94vw)' }">
    <div class="space-y-3">
      <div>
        <label class="mb-1 block text-xs font-medium text-slate-600">Bank Name</label>
        <InputText v-model="payment.bank_name" class="w-full" />
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium text-slate-600">Account Name</label>
        <InputText v-model="payment.bank_account_name" class="w-full" />
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium text-slate-600">Account Number</label>
        <InputMask v-model="payment.bank_account_number" mask="9999-9999-9999-9999" class="w-full" />
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium text-slate-600">Account Type</label>
        <Select v-model="payment.bank_account_type" :options="accountTypes" optionLabel="label" optionValue="value" placeholder="Select account type" class="w-full" />
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium text-slate-600">Branch (optional)</label>
        <InputText v-model="payment.bank_branch" class="w-full" />
      </div>
    </div>
    <template #footer>
      <div class="flex justify-end gap-2">
        <Button label="Cancel" severity="secondary" outlined size="small" @click="showPaymentDialog = false" />
        <Button label="Save account" size="small" :loading="submittingPayment" @click="submitPayment" />
      </div>
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import Card from 'primevue/card'
import Message from 'primevue/message'
import Button from 'primevue/button'
import Skeleton from 'primevue/skeleton'
import Tag from 'primevue/tag'
import Badge from 'primevue/badge'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import InputMask from 'primevue/inputmask'
import Select from 'primevue/select'
import supplierService from '../../../services/supplier.service'

const router = useRouter()
const loading = ref(false)
const portal = ref<any>(null)
const stats = ref<any>(null)
const recentRFQs = ref<any[]>([])
const recentPOs = ref<any[]>([])
const showPaymentDialog = ref(false)
const submittingPayment = ref(false)
const payment = ref({ bank_name: '', bank_account_name: '', bank_account_number: '', bank_account_type: null as any, bank_branch: '' })
const accountTypes = [
  { label: 'Savings', value: 'savings' },
  { label: 'Checking', value: 'checking' },
  { label: 'Current', value: 'current' },
  { label: 'Other', value: 'other' },
]
const submittedDocuments = computed(() => portal.value?.verification_documents?.length || portal.value?.verificationDocuments?.length || 0)
const documentProgress = computed(() => Math.min(100, Math.round((submittedDocuments.value / 3) * 100)))
const portalStatus = computed(() => formatStatus(portal.value?.status || 'pending'))
const statusSeverity = computed(() => statusValueSeverity(portal.value?.status))
const statCards = computed(() => [
  { label: 'Account status', value: formatStatus(stats.value?.portal_status || portal.value?.status || 'pending'), hint: 'Supplier verification', icon: 'pi pi-shield' },
  { label: 'RFQ responses', value: Number(stats.value?.total_rfqs || 0).toLocaleString(), hint: 'All submitted quotations', icon: 'pi pi-file' },
  { label: 'Purchase orders', value: Number(stats.value?.total_pos || 0).toLocaleString(), hint: 'Orders received', icon: 'pi pi-shopping-cart' },
  { label: 'Awaiting response', value: Number(stats.value?.pending_pos || 0).toLocaleString(), hint: 'Purchase orders requiring action', icon: 'pi pi-inbox' },
])

async function fetchPortalData() {
  try {
    loading.value = true
    const [portalRes, statsRes, rfqRes, poRes] = await Promise.all([
      supplierService.getMyPortal(),
      supplierService.getPortalStats(),
      supplierService.getMyRFQFeedbacks({ per_page: 5 }),
      supplierService.getMyPOFeedbacks({ per_page: 5 }),
    ])
    portal.value = portalRes.data
    stats.value = statsRes.data
    recentRFQs.value = rfqRes.data?.data || []
    recentPOs.value = poRes.data?.data || []
  } catch (error) {
    console.error('Error fetching portal data:', error)
    router.push('/supplier-portal/registration')
  } finally {
    loading.value = false
  }
}

function formatDate(date: string | null) {
  if (!date) return '-'
  const parsed = new Date(date)
  return Number.isNaN(parsed.getTime()) ? '-' : parsed.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
}

function formatMoney(value: number | string | null) {
  return new Intl.NumberFormat('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(value || 0))
}

function formatStatus(status: string | null | undefined) {
  return String(status || 'Unknown').replaceAll('_', ' ').replace(/\b\w/g, letter => letter.toUpperCase())
}

function statusValueSeverity(status: string | null | undefined) {
  const value = String(status || '').toLowerCase()
  if (['approved', 'accepted', 'active', 'completed'].includes(value)) return 'success'
  if (['rejected', 'declined', 'failed'].includes(value)) return 'danger'
  if (['pending', 'submitted', 'awaiting_response'].includes(value)) return 'warn'
  return 'secondary'
}

function openPaymentDialog() {
  const supplier = portal.value?.supplier || portal.value
  payment.value.bank_name = supplier?.bank_name || ''
  payment.value.bank_account_name = supplier?.bank_account_name || ''
  payment.value.bank_account_number = supplier?.bank_account_number || ''
  payment.value.bank_account_type = supplier?.bank_account_type || null
  payment.value.bank_branch = supplier?.bank_branch || ''
  showPaymentDialog.value = true
}

async function submitPayment() {
  submittingPayment.value = true
  try {
    await supplierService.updatePaymentAccount({
      bank_name: payment.value.bank_name,
      bank_account_name: payment.value.bank_account_name,
      bank_account_number: payment.value.bank_account_number,
      bank_account_type: payment.value.bank_account_type,
      bank_branch: payment.value.bank_branch,
    })
    await fetchPortalData()
    showPaymentDialog.value = false
  } catch (error) {
    console.error('Failed to update payment account', error)
  } finally {
    submittingPayment.value = false
  }
}

onMounted(fetchPortalData)
</script>
