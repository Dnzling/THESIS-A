<template>
  <div class="supplier-detail-container p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center gap-4">
        <Button
          icon="pi pi-arrow-left"
          class="p-button-text"
          @click="$router.back()"
        />
        <div>
          <h1 class="text-3xl font-bold text-gray-800">{{ supplier.supplier_name }}</h1>
          <p class="text-gray-600">{{ supplier.company_name }}</p>
        </div>
      </div>
      <div class="flex gap-2">
        <Button
          icon="pi pi-pencil"
          label="Edit"
          class="p-button-warning"
          @click="openEditDialog"
        />
        <Button
          icon="pi pi-trash"
          label="Delete"
          class="p-button-danger"
          @click="deleteSupplier"
        />
      </div>
    </div>

    <!-- Quick Info Cards -->
    <Skeleton v-if="loading" width="100%" height="100px" class="mb-6" />
    <div v-else class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-blue-50 rounded-lg p-4 border-l-4 border-blue-500">
        <div class="text-sm text-gray-600 font-semibold mb-2">Status</div>
        <Tag :value="supplier.status" :severity="getStatusSeverity(supplier.status)" />
      </div>
      <div class="bg-green-50 rounded-lg p-4 border-l-4 border-green-500">
        <div class="text-sm text-gray-600 font-semibold mb-2">Rating</div>
        <Rating v-model="supplier.rating" :cancel="false" read-only />
      </div>
      <div class="bg-yellow-50 rounded-lg p-4 border-l-4 border-yellow-500">
        <div class="text-sm text-gray-600 font-semibold mb-2">Quality Score</div>
        <div class="text-2xl font-bold text-yellow-600">{{ supplier.quality_score }}/5</div>
      </div>
      <div class="bg-purple-50 rounded-lg p-4 border-l-4 border-purple-500">
        <div class="text-sm text-gray-600 font-semibold mb-2">On-Time %</div>
        <div class="text-2xl font-bold text-purple-600">{{ supplier.on_time_percentage }}%</div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="bg-white rounded-lg shadow-sm">
      <TabView>
        <!-- Overview Tab -->
        <TabPanel header="Overview">
          <Skeleton v-if="loading" width="100%" height="300px" />
          <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-gray-50 rounded-lg p-4">
              <h3 class="font-semibold text-gray-800 mb-4">Contact Information</h3>
              <ul class="space-y-2 text-sm">
                <li>
                  <span class="font-medium text-gray-600">Contact Person:</span>
                  <span class="text-gray-800">{{ supplier.contact_person }}</span>
                </li>
                <li>
                  <span class="font-medium text-gray-600">Email:</span>
                  <span class="text-gray-800">{{ supplier.email }}</span>
                </li>
                <li>
                  <span class="font-medium text-gray-600">Phone:</span>
                  <span class="text-gray-800">{{ supplier.phone }}</span>
                </li>
                <li>
                  <span class="font-medium text-gray-600">Address:</span>
                  <span class="text-gray-800">{{ supplier.address }}</span>
                </li>
                <li v-if="supplier.city || supplier.state">
                  <span class="font-medium text-gray-600">City/State:</span>
                  <span class="text-gray-800">{{ supplier.city }}, {{ supplier.state }}</span>
                </li>
                <li v-if="supplier.postal_code">
                  <span class="font-medium text-gray-600">Postal Code:</span>
                  <span class="text-gray-800">{{ supplier.postal_code }}</span>
                </li>
                <li v-if="supplier.country">
                  <span class="font-medium text-gray-600">Country:</span>
                  <span class="text-gray-800">{{ supplier.country }}</span>
                </li>
              </ul>
            </div>
            <div class="bg-gray-50 rounded-lg p-4">
              <h3 class="font-semibold text-gray-800 mb-4">Business Information</h3>
              <ul class="space-y-2 text-sm">
                <li>
                  <span class="font-medium text-gray-600">Category:</span>
                  <Tag :value="supplier.category" class="ml-2" />
                </li>
                <li>
                  <span class="font-medium text-gray-600">Payment Terms:</span>
                  <span class="text-gray-800">{{ supplier.payment_terms }}</span>
                </li>
                <li v-if="supplier.tax_id">
                  <span class="font-medium text-gray-600">Tax ID:</span>
                  <span class="text-gray-800">{{ supplier.tax_id }}</span>
                </li>
                <li v-if="supplier.bank_details">
                  <span class="font-medium text-gray-600">Bank Details:</span>
                  <span class="text-gray-800">{{ supplier.bank_details }}</span>
                </li>
              </ul>
            </div>
          </div>
        </TabPanel>

        <!-- Performance Tab -->
        <TabPanel header="Performance">
          <Skeleton v-if="loading" width="100%" height="400px" />
          <div v-else class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="bg-blue-50 rounded-lg p-4 border-l-4 border-blue-500">
                <div class="text-sm text-gray-600 font-semibold mb-2">On-Time %</div>
                <div class="text-3xl font-bold text-blue-600">
                  {{ performanceMetrics?.on_time_percentage || 0 }}%
                </div>
              </div>
              <div class="bg-green-50 rounded-lg p-4 border-l-4 border-green-500">
                <div class="text-sm text-gray-600 font-semibold mb-2">Avg Delivery Days</div>
                <div class="text-3xl font-bold text-green-600">
                  {{ performanceMetrics?.avg_delivery_days || 0 }}d
                </div>
              </div>
              <div class="bg-purple-50 rounded-lg p-4 border-l-4 border-purple-500">
                <div class="text-sm text-gray-600 font-semibold mb-2">Risk Score</div>
                <div class="text-3xl font-bold text-purple-600">
                  {{ performanceMetrics?.risk_score || 0 }}/100
                </div>
              </div>
            </div>
            <ChartWrapper
              title="Delivery Trend (Last 12 Months)"
              chart-type="line"
              :chart-data="getDeliveryTrendData()"
              :loading="loading"
            />
            <ChartWrapper
              title="Quality Trend (Last 12 Months)"
              chart-type="line"
              :chart-data="getQualityTrendData()"
              :loading="loading"
            />
          </div>
        </TabPanel>

        <!-- Payments Tab -->
        <TabPanel header="Payments">
          <Skeleton v-if="loading" width="100%" height="400px" />
          <div v-else class="space-y-6">
            <PaymentStatus :payment-status="paymentStatus" />
            <ChartWrapper
              title="Payment Aging Distribution"
              chart-type="bar"
              :chart-data="getAgingDistributionData()"
              :loading="loading"
            />
          </div>
        </TabPanel>

        <!-- Category Tab -->
        <TabPanel header="Category & Capabilities">
          <Skeleton v-if="loading" width="100%" height="200px" />
          <div v-else class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Current Category</label>
              <Tag :value="supplier.category" class="text-base py-2 px-3" />
            </div>
            <Button
              label="View Alternative Suppliers"
              icon="pi pi-external-link"
              class="p-button-text"
              @click="viewAlternatives"
            />
          </div>
        </TabPanel>

        <!-- Risk Assessment Tab -->
        <TabPanel header="Risk Assessment">
          <Skeleton v-if="loading" width="100%" height="200px" />
          <div v-else class="space-y-4">
            <div class="flex items-center gap-4">
              <RiskBadge :risk-score="supplier.risk_score" />
              <div>
                <div class="text-lg font-semibold text-gray-800">Risk Level: {{ getRiskLevel() }}</div>
                <div class="text-sm text-gray-600">Score: {{ supplier.risk_score }}/100</div>
              </div>
            </div>
            <div v-if="riskReasons.length" class="bg-red-50 rounded-lg p-4 border-l-4 border-red-500">
              <div class="font-semibold text-gray-800 mb-3">Risk Factors:</div>
              <ul class="space-y-2">
                <li v-for="(reason, idx) in riskReasons" :key="idx" class="text-sm text-gray-700">
                  • {{ reason }}
                </li>
              </ul>
            </div>
            <div v-else class="bg-green-50 rounded-lg p-4 border-l-4 border-green-500">
              <div class="text-green-700 font-semibold">✓ No risk factors identified</div>
            </div>
          </div>
        </TabPanel>

        <!-- Order History Tab -->
        <TabPanel header="Order History">
          <Skeleton v-if="loading" width="100%" height="200px" />
          <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-blue-50 rounded-lg p-4 border-l-4 border-blue-500">
              <div class="text-sm text-gray-600 font-semibold mb-2">Total Orders</div>
              <div class="text-3xl font-bold text-blue-600">-</div>
            </div>
            <div class="bg-green-50 rounded-lg p-4 border-l-4 border-green-500">
              <div class="text-sm text-gray-600 font-semibold mb-2">On-Time Orders</div>
              <div class="text-3xl font-bold text-green-600">-</div>
            </div>
            <div class="bg-red-50 rounded-lg p-4 border-l-4 border-red-500">
              <div class="text-sm text-gray-600 font-semibold mb-2">Late Orders</div>
              <div class="text-3xl font-bold text-red-600">-</div>
            </div>
          </div>
        </TabPanel>
      </TabView>
    </div>

    <!-- Edit Dialog -->
    <Dialog
      v-model:visible="showEditDialog"
      header="Edit Supplier"
      :modal="true"
      :closable="true"
      style="width: 90vw; max-width: 600px"
    >
      <SupplierForm
        :supplier="supplier"
        mode="edit"
        @save="handleEditSave"
        @close="showEditDialog = false"
      />
    </Dialog>

    <!-- Toast & Confirm -->
    <Toast />
    <ConfirmDialog />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import supplierService from '../../../services/supplier.service'
import SupplierForm from './SupplierForm.vue'
import RiskBadge from './RiskBadge.vue'
import PaymentStatus from './PaymentStatus.vue'
import ChartWrapper from './ChartWrapper.vue'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Skeleton from 'primevue/skeleton'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'
import Rating from 'primevue/rating'

const route = useRoute()
const router = useRouter()
const confirm = useConfirm()
const toast = useToast()


const supplier = ref<any>({})
const performanceMetrics = ref<any>(null)
const paymentStatus = ref<any>(null)
const agingReport = ref<any>(null)
const performanceHistory = ref<any[]>([])
const paymentHistory = ref<any[]>([])
const loading = ref(true)
const showEditDialog = ref(false)

const riskReasons = computed(() => {
  const reasons = []
  if (supplier.value.on_time_percentage < 90) {
    reasons.push(`On-time delivery: ${supplier.value.on_time_percentage}% (Below target)`)
  }
  if (supplier.value.quality_score < 3) {
    reasons.push(`Quality issues: ${supplier.value.quality_score}/5 stars`)
  }
  if (supplier.value.risk_score > 50) {
    reasons.push('High risk score detected')
  }
  return reasons
})

const getStatusSeverity = (status: string) => {
  const map: any = {
    'active': 'success',
    'inactive': 'warning',
    'blacklisted': 'danger',
  }
  return map[status] || 'info'
}

const getRiskLevel = () => {
  const score = supplier.value.risk_score || 0
  if (score < 20) return 'Low'
  if (score < 50) return 'Medium'
  if (score < 75) return 'High'
  return 'Critical'
}

const getDeliveryTrendData = () => {
  return {
    labels: performanceHistory.value.map(p => p.date),
    datasets: [
      {
        label: 'On-Time %',
        data: performanceHistory.value.map(p => p.on_time_percentage),
        borderColor: '#10b981',
        fill: false,
        tension: 0.4,
      },
    ],
  }
}

const getQualityTrendData = () => {
  return {
    labels: performanceHistory.value.map(p => p.date),
    datasets: [
      {
        label: 'Quality Score',
        data: performanceHistory.value.map(p => p.quality_score),
        borderColor: '#f59e0b',
        fill: false,
        tension: 0.4,
      },
    ],
  }
}

const getAgingDistributionData = () => {
  return {
    labels: ['Current', '30 Days', '60 Days', '90 Days', '90+'],
    datasets: [
      {
        label: 'Amount Outstanding',
        data: [
          agingReport.value?.current || 0,
          agingReport.value?.days_30 || 0,
          agingReport.value?.days_60 || 0,
          agingReport.value?.days_90 || 0,
          agingReport.value?.over_90 || 0,
        ],
        backgroundColor: ['#10b981', '#f59e0b', '#ef4444', '#dc2626', '#991b1b'],
      },
    ],
  }
}

const viewAlternatives = () => {
  toast.add({
    severity: 'info',
    summary: 'Info',
    detail: 'Alternative suppliers will be shown here',
    life: 3000,
  })
}

const handleEditSave = () => {
  showEditDialog.value = false
  loadSupplierData()
}

const deleteSupplier = () => {
  confirm.require({
    message: `Are you sure you want to delete ${supplier.value.supplier_name}?`,
    header: 'Confirm Deletion',
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      try {
        await supplierService.deleteSupplier(supplier.value.id)
        toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Supplier deleted successfully',
          life: 3000,
        })
        setTimeout(() => router.push('/suppliers/list'), 1000)
      } catch (error) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to delete supplier',
          life: 3000,
        })
      }
    },
  })
}

const openEditDialog = () => {
  showEditDialog.value = true
}

const loadSupplierData = async () => {
  loading.value = true

  try {
    const supplierId = route.params.id

    // TODO: Replace with actual API call
    supplier.value = {
      id: supplierId,
      supplier_name: 'Acme Materials Inc',
      company_name: 'Acme Inc',
      contact_person: 'John Doe',
      email: 'john@acme.com',
      phone: '(555) 123-4567',
      address: '789 Industrial Ave',
      city: 'Chicago',
      state: 'IL',
      postal_code: '60601',
      country: 'USA',
      category: 'Raw Materials',
      payment_terms: 'Net 30',
      status: 'active',
      rating: 4.8,
      quality_score: 4.7,
      on_time_percentage: 98.5,
      avg_delivery_days: 3,
      risk_score: 5,
    }

    performanceMetrics.value = {
      on_time_percentage: 98.5,
      quality_score: 4.7,
      avg_delivery_days: 3,
      risk_score: 5,
    }

    paymentStatus.value = {
      total_due: 5000,
      total_paid: 45000,
      due_count: 2,
      paid_count: 15,
      overdue_count: 0,
      overdue_amount: 0,
    }

    agingReport.value = {
      current: 2500,
      days_30: 1500,
      days_60: 0,
      days_90: 750,
      over_90: 750,
    }

    performanceHistory.value = Array.from({ length: 12 }, (_, i) => ({
      date: new Date(Date.now() - (11 - i) * 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
      on_time_percentage: 95 + Math.random() * 5,
      quality_score: 4.5 + Math.random() * 0.3,
    }))
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load supplier data',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadSupplierData()
})
</script>

<style scoped>
.supplier-detail-container {
  background-color: #f8f9fa;
}
</style>
