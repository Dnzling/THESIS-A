<template>
  <div class="supplier-portal-dashboard">
    <PageHeader title="Supplier Portal Dashboard" icon="pi pi-home" />

    <!-- Portal Status Alert -->
    <div class="mb-4" v-if="portal">
      <Message 
        v-if="portal.status === 'pending'" 
        severity="warning" 
        text="Your account is pending verification. Please complete your registration and upload required documents."
        class="w-full"
      />
      <Message 
        v-else-if="portal.status === 'rejected'" 
        severity="error" 
        :text="`Your account was rejected: ${portal.rejection_reason}`"
        class="w-full"
      />
      <Message 
        v-else-if="portal.status === 'approved'" 
        severity="success" 
        text="Your supplier account is verified and active!"
        class="w-full"
      />
    </div>

    <!-- Quick Stats -->
    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <Skeleton v-for="i in 4" :key="i" height="120px" class="rounded-2xl" />
    </div>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6" v-else-if="stats">
      <Card class="rounded-2xl border border-slate-200/70 shadow-sm">
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 text-sm">Portal Status</p>
              <p class="text-2xl font-bold capitalize">{{ stats.portal_status }}</p>
            </div>
            <i class="pi pi-check-circle text-4xl text-blue-500"></i>
          </div>
        </template>
      </Card>

      <Card class="rounded-2xl border border-slate-200/70 shadow-sm">
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 text-sm">Total RFQs</p>
              <p class="text-2xl font-bold">{{ stats.total_rfqs }}</p>
            </div>
            <i class="pi pi-file text-4xl text-green-500"></i>
          </div>
        </template>
      </Card>

      <Card class="rounded-2xl border border-slate-200/70 shadow-sm">
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 text-sm">Total POs</p>
              <p class="text-2xl font-bold">{{ stats.total_pos }}</p>
            </div>
            <i class="pi pi-shopping-cart text-4xl text-orange-500"></i>
          </div>
        </template>
      </Card>

      <Card class="rounded-2xl border border-slate-200/70 shadow-sm">
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 text-sm">Pending POs</p>
              <p class="text-2xl font-bold">{{ stats.pending_pos }}</p>
            </div>
            <i class="pi pi-exclamation-circle text-4xl text-red-500"></i>
          </div>
        </template>
      </Card>
    </div>

    <!-- Actions -->
    <div class="flex gap-3 mb-6">
      <Button 
        v-if="portal?.status === 'pending'" 
        label="Complete Registration" 
        icon="pi pi-arrow-right"
        @click="$router.push('/supplier-portal/registration')"
        class="p-button-primary"
      />
      <Button 
        v-if="portal?.status === 'approved'" 
        label="View RFQs" 
        icon="pi pi-list"
        @click="$router.push('/supplier-portal/rfqs')"
        class="p-button-info"
      />
      <Button 
        v-if="portal?.status === 'approved'" 
        label="View POs" 
        icon="pi pi-shopping-cart"
        @click="$router.push('/supplier-portal/pos')"
        class="p-button-warning"
      />
    </div>

    <!-- Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <Card class="rounded-2xl border border-slate-200/70 shadow-sm" title="Recent RFQ Responses">
        <template #content>
          <div v-if="loading" class="space-y-3">
            <Skeleton v-for="i in 4" :key="i" height="60px" class="rounded-lg" />
          </div>
          <div v-else-if="recentRFQs && recentRFQs.length > 0" class="space-y-3">
            <div 
              v-for="rfq in recentRFQs.slice(0, 5)" 
              :key="rfq.id"
              class="flex justify-between items-center p-3 bg-gray-50 rounded-xl"
            >
              <div>
                <p class="font-semibold">RFQ #{{ rfq.rfq_id }}</p>
                <p class="text-sm text-gray-500">{{ formatDate(rfq.submitted_at) }}</p>
              </div>
              <p class="font-bold text-green-600">₱ {{ formatMoney(rfq.quoted_price) }}</p>
            </div>
          </div>
          <p v-else class="text-gray-500 text-center py-6">No RFQ responses yet</p>
        </template>
      </Card>

      <Card class="rounded-2xl border border-slate-200/70 shadow-sm" title="Recent PO Actions">
        <template #content>
          <div v-if="loading" class="space-y-3">
            <Skeleton v-for="i in 4" :key="i" height="60px" class="rounded-lg" />
          </div>
          <div v-else-if="recentPOs && recentPOs.length > 0" class="space-y-3">
            <div 
              v-for="po in recentPOs.slice(0, 5)" 
              :key="po.id"
              class="flex justify-between items-center p-3 bg-gray-50 rounded-xl"
            >
              <div>
                <p class="font-semibold">PO #{{ po.purchase_order_id }}</p>
                <p class="text-sm text-gray-500">{{ formatDate(po.submitted_at) }}</p>
              </div>
              <Tag 
                :value="po.response" 
                :severity="po.response === 'accepted' ? 'success' : 'danger'"
              />
            </div>
          </div>
          <p v-else class="text-gray-500 text-center py-6">No PO actions yet</p>
        </template>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Message from 'primevue/message'
import Tag from 'primevue/tag'
import Skeleton from 'primevue/skeleton'
import PageHeader from '../../../components/PageHeader.vue'
import supplierService from '../../../services/supplier.service'

const router = useRouter()
const loading = ref(false)
const portal = ref(null)
const stats = ref(null)
const recentRFQs = ref([])
const recentPOs = ref([])

const fetchPortalData = async () => {
  try {
    loading.value = true

    // Fetch portal info
    const portalRes = await supplierService.getMyPortal()
    portal.value = portalRes.data

    // Fetch stats
    const statsRes = await supplierService.getPortalStats()
    stats.value = statsRes.data

    // Fetch recent RFQ feedbacks
    const rfqRes = await supplierService.getMyRFQFeedbacks({ per_page: 5 })
    recentRFQs.value = rfqRes.data.data || []

    // Fetch recent PO feedbacks
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
  return new Date(date).toLocaleDateString()
}

const formatMoney = (value: number) => new Intl.NumberFormat('en-PH', { minimumFractionDigits: 2 }).format(value || 0)

onMounted(() => {
  fetchPortalData()
})
</script>

<style scoped lang="scss">
.supplier-portal-dashboard {
  padding: 20px;
}
</style>
