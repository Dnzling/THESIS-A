<template>
  <div class="mx-auto max-w-7xl space-y-6 px-4 py-5 sm:px-6 lg:px-8">
    <section class="relative overflow-hidden rounded-3xl bg-slate-950 px-7 py-8 text-white shadow-xl">
      <div class="absolute -right-20 -top-24 h-64 w-64 rounded-full bg-orange-400/20 blur-3xl"></div>
      <div class="relative grid gap-7 lg:grid-cols-[1fr_auto] lg:items-end">
        <div><p class="text-xs font-bold uppercase tracking-[.22em] text-orange-300">Supplier workspace</p><h1 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">Welcome, {{ portal?.supplier?.supplier_name || portal?.supplier_name || 'Supplier' }}</h1><p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300">Track verification, quotations, purchase orders, and partner activity from one place.</p><div class="mt-6 flex flex-wrap gap-3"><Button v-if="portal?.status !== 'approved'" label="Complete verification" icon="pi pi-arrow-right" iconPos="right" severity="warn" @click="router.push('/supplier-portal/registration')"/><Button v-else label="Browse RFQs" icon="pi pi-file" severity="secondary" @click="router.push('/supplier-portal/rfqs')"/></div></div>
        <div class="min-w-64 rounded-2xl border border-white/10 bg-white/5 p-5 backdrop-blur"><div class="flex items-center justify-between"><span class="text-xs font-bold uppercase tracking-wider text-slate-400">Verification</span><Tag :value="portalStatus" :severity="statusSeverity" rounded/></div><div class="mt-5 flex items-end justify-between"><div><p class="text-3xl font-bold">{{ submittedDocuments }}/3</p><p class="mt-1 text-xs text-slate-400">Documents submitted</p></div><i class="pi pi-verified text-3xl text-orange-300"></i></div><div class="mt-4 h-2 overflow-hidden rounded-full bg-white/10"><div class="h-full rounded-full bg-orange-300 transition-all" :style="{width: `${documentProgress}%`}"></div></div></div>
      </div>
    </section>

    <!-- iOS-style Status Alert -->
    <div v-if="portal" class="space-y-3">
      <Message 
        v-if="portal.status === 'pending'" 
        severity="warn"
        class="rounded-2xl border-0 bg-orange-50 text-orange-800 p-4"
      >
        <div class="flex items-start gap-3">
          <div class="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center shrink-0">
            <i class="pi pi-clock text-orange-600"></i>
          </div>
          <div>
            <h3 class="font-semibold text-orange-800">Account Pending Verification</h3>
            <p class="text-sm text-orange-700 mt-1">Please complete your registration and upload required documents.</p>
          </div>
        </div>
      </Message>

      <Message 
        v-else-if="portal.status === 'rejected'" 
        severity="error"
        class="rounded-2xl border-0 bg-red-50 text-red-800 p-4"
      >
        <div class="flex items-start gap-3">
          <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center shrink-0">
            <i class="pi pi-exclamation-circle text-red-600"></i>
          </div>
          <div>
            <h3 class="font-semibold text-red-800">Account Rejected</h3>
            <p class="text-sm text-red-700 mt-1">{{ portal.rejection_reason }}</p>
          </div>
        </div>
      </Message>

      <Message 
        v-else-if="portal.status === 'approved'" 
        severity="success"
        class="rounded-2xl border-0 bg-green-50 text-green-800 p-4"
      >
        <div class="flex items-start gap-3">
          <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center shrink-0">
            <i class="pi pi-check-circle text-green-600"></i>
          </div>
          <div>
            <h3 class="font-semibold text-green-800">Account Verified</h3>
            <p class="text-sm text-green-700 mt-1">Your supplier account is verified and active!</p>
          </div>
        </div>
      </Message>
    </div>

    <!-- iOS-style Stats Cards -->
    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-4 gap-3">
      <Skeleton v-for="i in 4" :key="i" height="120px" class="rounded-xl" />
    </div>
    
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4" v-else-if="stats">
      <!-- Portal Status Card -->
      <Card class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <template #content>
          <div class="p-4">
            <div class="flex items-center justify-between mb-3">
              <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Portal Status</span>
              <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center">
                <i class="pi pi-shield text-blue-600 text-sm"></i>
              </div>
            </div>
            <p class="text-xl font-bold text-gray-900 capitalize">{{ stats.portal_status }}</p>
            <p class="text-xs text-gray-400 mt-1">Updated today</p>
          </div>
        </template>
      </Card>

      <!-- Total RFQs Card -->
      <Card class="overflow-hidden rounded-2xl border border-slate-200 shadow-sm">
        <template #content>
          <div class="p-4">
            <div class="flex items-center justify-between mb-2">
              <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Total RFQs</span>
              <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                <i class="pi pi-file text-green-600 text-sm"></i>
              </div>
            </div>
            <p class="text-2xl font-semibold text-gray-900">{{ stats.total_rfqs }}</p>
            <p class="text-xs text-gray-500 mt-1">All time responses</p>
          </div>
        </template>
      </Card>

      <!-- Total POs Card -->
      <Card class="overflow-hidden rounded-2xl border border-slate-200 shadow-sm">
        <template #content>
          <div class="p-4">
            <div class="flex items-center justify-between mb-2">
              <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Total POs</span>
              <div class="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center">
                <i class="pi pi-shopping-cart text-orange-600 text-sm"></i>
              </div>
            </div>
            <p class="text-2xl font-semibold text-gray-900">{{ stats.total_pos }}</p>
            <p class="text-xs text-gray-500 mt-1">Purchase orders received</p>
          </div>
        </template>
      </Card>

      <!-- Pending POs Card -->
      <Card class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <template #content>
          <div class="p-4">
            <div class="flex items-center justify-between mb-3">
              <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Pending POs</span>
              <div class="w-8 h-8 rounded-full bg-red-50 flex items-center justify-center">
                <i class="pi pi-exclamation-circle text-red-500 text-sm"></i>
              </div>
            </div>
            <p class="text-2xl font-bold text-red-600">{{ stats.pending_pos }}</p>
            <p class="text-xs text-gray-500 mt-1">Awaiting your response</p>
          </div>
        </template>
      </Card>
    </div>

    <!-- iOS-style Action Buttons -->
    <div class="flex flex-wrap gap-2">
      <Button 
        v-if="portal?.status === 'pending'" 
        label="Complete Registration" 
        icon="pi pi-arrow-right"
        iconPos="right"
        @click="router.push('/supplier-portal/registration')"
        class="bg-blue-500 hover:bg-blue-600 border-none text-white font-medium rounded-lg px-4 py-2 shadow-sm text-sm"
      />
      <Button 
        v-if="portal?.status === 'approved'" 
        label="View RFQs" 
        icon="pi pi-list"
        @click="router.push('/supplier-portal/rfqs')"
        class="bg-white hover:bg-gray-50 text-gray-700 font-medium rounded-lg px-4 py-2 border border-gray-200 shadow-sm text-sm"
      />
      <Button 
        v-if="portal?.status === 'approved'" 
        label="View POs" 
        icon="pi pi-shopping-cart"
        @click="router.push('/supplier-portal/pos')"
        class="bg-white hover:bg-gray-50 text-gray-700 font-medium rounded-lg px-4 py-2 border border-gray-200 shadow-sm text-sm"
      />
      <Button
        v-if="portal?.status === 'approved'"
          label="Linked Stores"
          icon="pi pi-building"
          @click="router.push('/supplier-portal/stores')"
          class="bg-white hover:bg-gray-50 text-gray-700 font-medium rounded-lg px-4 py-2 border border-gray-200 shadow-sm text-sm"
        />
        <Button
          v-if="portal?.status === 'approved'"
          label="Payment Account"
          icon="pi pi-credit-card"
          @click="openPaymentDialog"
        class="bg-white hover:bg-gray-50 text-gray-700 font-medium rounded-lg px-4 py-2 border border-gray-200 shadow-sm text-sm"
      />
    </div>

    <!-- Recent Activity Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Recent RFQ Responses Card -->
      <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <template #header>
          <div class="px-6 pt-6">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center ring-4 ring-green-50">
                <i class="pi pi-file text-green-600 text-base"></i>
              </div>
              <div>
                <h2 class="text-lg font-semibold text-gray-900">Recent RFQ Responses</h2>
                <p class="text-xs text-gray-500 mt-0.5">Your latest quotations</p>
              </div>
            </div>
          </div>
        </template>
        
        <template #content>
          <div class="p-6 pt-2">
            <!-- Loading State -->
            <div v-if="loading" class="space-y-3">
              <div v-for="i in 4" :key="i" class="bg-gray-50 rounded-xl p-4">
                <Skeleton width="60%" height="16px" class="mb-2" />
                <Skeleton width="40%" height="14px" />
              </div>
            </div>

            <!-- Content -->
            <div v-else-if="recentRFQs && recentRFQs.length > 0" class="space-y-3">
              <div 
                v-for="rfq in recentRFQs.slice(0, 5)" 
                :key="rfq.id"
                class="group bg-gray-50 hover:bg-gray-100 rounded-xl p-4 transition-all cursor-pointer"
                @click="router.push(`/supplier-portal/rfqs/${rfq.rfq_id}`)"
              >
                <div class="flex items-center justify-between">
                  <div>
                    <div class="flex items-center gap-2 mb-1">
                      <span class="text-xs font-medium text-gray-400 uppercase tracking-wider">RFQ</span>
                      <span class="font-semibold text-gray-900">#{{ rfq.rfq.rfq_number }}</span>
                    </div>
                    <p class="text-sm text-gray-500">{{ formatDate(rfq.submitted_at) }}</p>
                  </div>
                  <div class="text-right">
                    <p class="text-lg font-semibold text-green-600">₱{{ formatMoney(rfq.quoted_price) }}</p>
                    <Tag 
                      :value="rfq.status" 
                      :severity="rfq.status === 'approved' ? 'success' : rfq.status === 'rejected' ? 'danger' : 'info'"
                      class="rounded-full text-xs px-3 py-1 mt-1"
                    />
                  </div>
                </div>
              </div>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-8">
              <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="pi pi-file text-gray-400 text-xl"></i>
              </div>
              <p class="text-gray-500 text-sm">No RFQ responses yet</p>
              <p class="text-xs text-gray-400 mt-1">Your quotations will appear here</p>
            </div>
          </div>
        </template>
      </Card>

      <!-- Recent PO Actions Card -->
      <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <template #header>
          <div class="px-6 pt-6">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center ring-4 ring-orange-50">
                <i class="pi pi-shopping-cart text-orange-600 text-base"></i>
              </div>
              <div>
                <h2 class="text-lg font-semibold text-gray-900">Recent PO Actions</h2>
                <p class="text-xs text-gray-500 mt-0.5">Purchase order responses</p>
              </div>
            </div>
          </div>
        </template>
        
        <template #content>
          <div class="p-6 pt-2">
            <!-- Loading State -->
            <div v-if="loading" class="space-y-3">
              <div v-for="i in 4" :key="i" class="bg-gray-50 rounded-xl p-4">
                <Skeleton width="60%" height="16px" class="mb-2" />
                <Skeleton width="40%" height="14px" />
              </div>
            </div>

            <!-- Content -->
            <div v-else-if="recentPOs && recentPOs.length > 0" class="space-y-3">
              <div 
                v-for="po in recentPOs.slice(0, 5)" 
                :key="po.id"
                class="group bg-gray-50 hover:bg-gray-100 rounded-xl p-4 transition-all cursor-pointer"
                @click="router.push(`/supplier-portal/pos/${po.purchase_order_id}`)"
              >
                <div class="flex items-center justify-between">
                  <div>
                    <div class="flex items-center gap-2 mb-1">
                      <span class="text-xs font-medium text-gray-400 uppercase tracking-wider">PO</span>
                      <span class="font-semibold text-gray-900">#{{ po.purchase_order.po_number }}</span>
                    </div>
                    <p class="text-sm text-gray-500">{{ formatDate(po.submitted_at) }}</p>
                  </div>
                  <Tag 
                    :value="po.response" 
                    :severity="po.response === 'accepted' ? 'success' : 'danger'"
                    class="rounded-full text-xs px-3 py-1 font-medium"
                  />
                </div>
              </div>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-8">
              <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="pi pi-shopping-cart text-gray-400 text-xl"></i>
              </div>
              <p class="text-gray-500 text-sm">No PO actions yet</p>
              <p class="text-xs text-gray-400 mt-1">Purchase orders will appear here</p>
            </div>
          </div>
        </template>
      </Card>
    </div>

  </div>

  <Dialog v-model:visible="showPaymentDialog" header="Payment Account" :modal="true" :style="{ width: '32rem' }">
    <div class="space-y-3">
      <div>
        <label class="block text-sm font-medium mb-1">Bank Name</label>
        <InputText v-model="payment.bank_name" class="w-full" />
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Account Name</label>
        <InputText v-model="payment.bank_account_name" class="w-full" />
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Account Number</label>
        <InputMask v-model="payment.bank_account_number" mask="9999-9999-9999-9999" class="w-full" />
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Account Type</label>
        <Dropdown
          v-model="payment.bank_account_type"
          :options="[
            { label: 'savings', value: 'savings' },
            { label: 'checking', value: 'checking' },
            { label: 'current', value: 'current' },
            { label: 'other', value: 'other' },
          ]"
          optionLabel="label"
          optionValue="value"
          placeholder="Select account type"
          class="w-full"
        />
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Branch (optional)</label>
        <InputText v-model="payment.bank_branch" class="w-full" />
      </div>
    </div>
    <template #footer>
      <div class="flex justify-end gap-2">
        <button class="px-4 py-2 bg-gray-100 rounded" @click="showPaymentDialog = false">Cancel</button>
        <button class="px-4 py-2 bg-blue-600 text-white rounded" :disabled="submittingPayment" @click="submitPayment">Save</button>
      </div>
    </template>
  </Dialog>

</template>

<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import Card from 'primevue/card'
import Message from 'primevue/message'
import Button from 'primevue/button'
import Skeleton from 'primevue/skeleton'
import Tag from 'primevue/tag'
import supplierService from '../../../services/supplier.service'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Dropdown from 'primevue/dropdown'

const router = useRouter()
const loading = ref(false)
const portal = ref<any>(null)
const stats = ref<any>(null)
const recentRFQs = ref<any[]>([])
const recentPOs = ref<any[]>([])
const showPaymentDialog = ref(false)
const submittingPayment = ref(false)
const payment = ref({ bank_name: '', bank_account_name: '', bank_account_number: '', bank_account_type: null as any, bank_branch: '' })
const submittedDocuments = computed(() => portal.value?.verification_documents?.length || portal.value?.verificationDocuments?.length || 0)
const documentProgress = computed(() => Math.min(100, Math.round((submittedDocuments.value / 3) * 100)))
const portalStatus = computed(() => String(portal.value?.status || 'pending').replace(/\b\w/g, letter => letter.toUpperCase()))
const statusSeverity = computed(() => portal.value?.status === 'approved' ? 'success' : portal.value?.status === 'rejected' ? 'danger' : 'warn')

const fetchPortalData = async () => {
  try {
    loading.value = true

    const portalRes = await supplierService.getMyPortal()
    portal.value = portalRes.data

    const statsRes = await supplierService.getPortalStats()
    stats.value = statsRes.data

    const rfqRes = await supplierService.getMyRFQFeedbacks({ per_page: 5 })
    recentRFQs.value = rfqRes.data.data || []

    const poRes = await supplierService.getMyPOFeedbacks({ per_page: 5 })
    recentPOs.value = poRes.data.data || []
  } catch (error) {
    console.error('Error fetching portal data:', error)
    router.push('/supplier-portal/registration')
  } finally {
    loading.value = false
  }
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-PH', { 
    month: 'short', 
    day: 'numeric', 
    year: 'numeric' 
  })
}

const formatMoney = (value: number) => {
  return new Intl.NumberFormat('en-PH', { 
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(value || 0)
}

const openPaymentDialog = () => {
  // prefill from loaded portal supplier if available
  const s = portal.value?.supplier || portal.value
  payment.value.bank_name = s?.bank_name || ''
  payment.value.bank_account_name = s?.bank_account_name || ''
  payment.value.bank_account_number = s?.bank_account_number || ''
  payment.value.bank_account_type = s?.bank_account_type || null
  payment.value.bank_branch = s?.bank_branch || ''
  showPaymentDialog.value = true
}

const submitPayment = async () => {
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
  } catch (err) {
    console.error('Failed to update payment account', err)
  } finally {
    submittingPayment.value = false
  }
}

onMounted(() => {
  fetchPortalData()
})
</script>

<style scoped>
/* Smooth transitions */
* {
  transition: background-color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
}

/* iOS-style shadows */
:deep(.p-card) {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.02);
}

:deep(.p-card .p-card-content) {
  padding: 0;
}

/* iOS-style messages */
:deep(.p-message) {
  border-radius: 16px;
  padding: 1rem;
}

/* iOS-style buttons */
:deep(.p-button) {
  border-radius: 12px;
  font-weight: 500;
  transition: all 0.2s ease;
}

/* iOS-style tags */
:deep(.p-tag) {
  border-radius: 9999px;
  font-weight: 500;
}

/* Custom scrollbar */
::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
  background: #a1a1a1;
}
</style>
