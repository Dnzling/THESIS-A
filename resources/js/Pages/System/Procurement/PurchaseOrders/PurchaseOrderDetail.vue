<template>
  <div class="max-w-7xl mx-auto space-y-6 pb-6">
    <!-- Toast notifications -->
    <Toast />
    <ConfirmDialog />
  
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-4">
        <Button icon="pi pi-arrow-left" text rounded @click="router.push({ name: 'procurement.purchase-orders' })" />
        <div>
          <h1 class="text-2xl font-semibold text-gray-900">Purchase Order Details</h1>
          <p class="text-sm text-gray-500 mt-1">View complete purchase order information</p>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <Button v-if="detail" size=small icon="pi pi-print" severity="secondary" text @click="printPO" />
        <Button v-if="canManagePurchaseOrders && detail?.status === 'draft'" label="Edit Purchase Order"
          icon="pi pi-pencil" severity="info" @click="editPO" />
        <Button v-if="canManagePurchaseOrders && detail?.status === 'approved'" label="Send to Supplier" icon="pi pi-send"
          severity="success" @click="confirmSend" />
        <Button v-if="canManagePurchaseOrders && detail?.status === 'sent_to_supplier'" label="Resend to Supplier"
          icon="pi pi-replay" severity="secondary" @click="confirmResend" />
        <Button v-if="canManageReceiving && detail?.status === 'delivered'" label="Receive Supplies" size="small"
          icon="pi pi-inbox" severity="info" @click="createGoodsReceipt" />
        <Tag :value="formatStatus(detail?.status)" :severity="statusSeverity(detail?.status)" />
      </div>
    </div>
  
    <!-- Loading State -->
    <div v-if="loading" class="space-y-4">
      <Skeleton height="120px" class="rounded-lg" />
      <Skeleton height="200px" class="rounded-lg" />
      <Skeleton height="300px" class="rounded-lg" />
    </div>
  
    <!-- Main Content -->
    <div v-else-if="detail" class="space-y-6">
      <!-- Delivery Status Steps -->
    <div v-if="['sent_to_supplier', 'supplier_accepted', 'in_transit', 'out_for_delivery', 'delivered'].includes(detail?.status)" class="space-y-6">
        <Card v-if="steps && steps.length" class="rounded-2xl border border-slate-200/70 shadow-sm">
          <template #content>
            <div class="flex flex-col gap-4">
              <div class="flex items-center justify-between text-sm font-semibold text-slate-600">
                <div class="flex items-center gap-2" v-for="step in steps" :key="step.key">
                  <div :class="['w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold',
                      step.active ? 'bg-emerald-500' : 'bg-slate-300']">
                    {{ step.index }}
                  </div>
                  <span :class="step.active ? 'text-emerald-600' : 'text-slate-500'">{{ step.label }}</span>
                  <div v-if="step.index !== steps.length" class="w-10 h-px bg-slate-200 mx-3"></div>
                </div>
              </div>
            </div>
          </template>
  
        </Card>
  
        <Card class="overflow-hidden rounded-2xl border border-slate-200/70 shadow-sm" v-if="deliveryLogs.length || shipmentInfo">
          <template #header>
            <div class="flex items-center gap-3 border-b border-slate-100 bg-slate-50/80 px-6 py-4">
              <span class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 text-blue-600"><i class="pi pi-truck"></i></span>
              <div>
                <h3 class="font-semibold text-slate-900">Supplier Pickup Progress</h3>
                <p class="text-xs text-slate-500">Driver locations and pickup proof</p>
              </div>
            </div>
          </template>
          <template #content>
            <div v-if="shipmentInfo" class="mx-6 mb-5 grid gap-3 rounded-2xl border border-slate-200 p-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
              <div><p class="text-xs text-slate-500">Assigned driver</p><p class="mt-1 font-semibold">{{ shipmentInfo.driver_name || 'Driver' }}</p></div>
              <div><p class="text-xs text-slate-500">Vehicle</p><p class="mt-1 font-semibold">{{ [shipmentInfo.truck_brand, shipmentInfo.truck_type].filter(Boolean).join(' ') || '-' }}</p></div>
              <div><p class="text-xs text-slate-500">Plate number</p><p class="mt-1 font-semibold">{{ shipmentInfo.plate_number || '-' }}</p></div>
              <div><p class="text-xs text-slate-500">Contact</p><p class="mt-1 font-semibold">{{ shipmentInfo.driver_contact || '-' }}</p></div>
              <div v-if="currentPickupAddress" class="rounded-xl bg-blue-50 p-3 text-blue-900 sm:col-span-2 lg:col-span-4">
                <p class="text-xs font-medium text-blue-600">Latest pickup location</p>
                <p class="mt-1"><i class="pi pi-map-marker mr-1"></i>{{ currentPickupAddress }}</p>
              </div>
            </div>
            <div v-if="shipmentInfo && ['in_transit', 'out_for_delivery'].includes(String(shipmentStatus))" class="mx-6 mb-5 overflow-hidden rounded-2xl border border-slate-200">
              <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 bg-slate-50/80 px-4 py-3">
                <div>
                  <p class="font-semibold text-slate-900">Live Truck Tracking</p>
                  <p class="text-xs text-slate-500">The blue path connects recorded truck locations to the store.</p>
                </div>
                <Tag value="Live" severity="success" />
              </div>
              <div ref="pickupMapElement" class="h-[360px] w-full"></div>
            </div>
            <div v-if="deliveryLogs.length" class="mx-6 space-y-4">
              <div v-for="log in deliveryLogs" :key="log.id" class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <span class="mt-1 flex h-9 w-9 shrink-0 items-center justify-center rounded-full" :class="logIconClass(log.event_type)"><i :class="logIcon(log.event_type)"></i></span>
                <div class="min-w-0 flex-1">
                  <div class="flex flex-wrap items-center justify-between gap-2 text-sm font-semibold text-slate-900">
                    <span>{{ deliveryLogLabel(log.event_type) }}</span>
                    <Tag :value="formatDateWithTime(log.logged_at || log.created_at)" severity="secondary" class="text-xs" />
                  </div>
                  <div class="text-xs text-slate-500">
                    By {{ shipmentInfo?.driver_name || 'Driver' }}<span v-if="log.receiver_name"> • Receiver: {{
                      log.receiver_name }}</span>
                  </div>
                  <p v-if="log.notes" class="text-sm text-slate-700 mt-1">{{ log.notes }}</p>
                  <p class="mt-1 text-xs text-slate-500">{{ deliveryLogDescription(log.event_type) }}</p>
                  <div v-if="logAddress(log)" class="mt-3 rounded-xl bg-blue-50 px-3 py-2 text-sm text-blue-900">
                    <i class="pi pi-map-marker mr-1 text-blue-600"></i>{{ logAddress(log) }}
                  </div>
                  <div v-if="proofAttachments(log).length" class="mt-3 border-t border-slate-100 pt-3">
                    <p class="mb-2 text-xs font-medium uppercase tracking-wide text-slate-500">{{ proofLabel(log.event_type) }}</p>
                    <div class="flex flex-wrap gap-3">
                      <a v-for="attachment in proofAttachments(log)" :key="attachment.id" :href="attachment.public_url" target="_blank" rel="noopener" class="group relative overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                        <img v-if="attachment.public_url" :src="attachment.public_url" :alt="proofLabel(log.event_type)" class="h-32 w-40 object-cover transition group-hover:scale-105" />
                        <span v-else class="flex h-32 w-40 items-center justify-center text-xs text-slate-500">Attachment unavailable</span>
                        <span class="absolute inset-x-0 bottom-0 bg-slate-950/65 px-2 py-1 text-center text-xs text-white">View attachment</span>
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <p v-else class="px-6 pb-2 text-sm text-slate-500">No pickup activity has been recorded yet.</p>
          </template>
        </Card>
      </div>
  
      <div v-if="detail?.status === 'pending_finance_approval'"
        class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
        <i class="pi pi-clock mr-2"></i>
        Awaiting Finance Approval — this PO cannot be sent to supplier yet.
      </div>
      <!-- PO Header Information -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="bg-white p-4 rounded-lg border border-gray-200">
          <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">PO Number</p>
          <p class="text-lg font-semibold text-gray-900 mt-1">{{ detail?.po_number || '-' }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg border border-gray-200">
          <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Order Date</p>
          <p class="text-lg font-semibold text-gray-900 mt-1">{{ formatDate(detail?.order_date) }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg border border-gray-200">
          <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Expected Pickup</p>
          <p class="text-lg font-semibold text-gray-900 mt-1">{{ formatDate(detail?.expected_delivery_date) }}</p>
        </div>
        <!-- <div class="bg-white p-4 rounded-lg border border-gray-200">
              <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Payment Terms</p>
              <p class="text-lg font-semibold text-gray-900 mt-1">{{ formatPaymentTerms(detail?.payment_terms) }}</p>
            </div> -->
      </div>
  
      <!-- Supplier and Branch Info -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white p-5 rounded-lg border border-gray-200">
          <div class="flex items-center gap-2 mb-3">
            <i class="pi pi-building text-gray-400"></i>
            <h3 class="font-medium text-gray-700">Supplier Information</h3>
          </div>
          <div class="space-y-2">
            <p class="font-medium text-gray-900">{{ detail?.supplier?.supplier_name || '-' }}</p>
            <p class="text-sm text-gray-600">{{ detail?.supplier?.email || '-' }}</p>
            <p class="text-sm text-gray-600">{{ detail?.supplier?.phone || '-' }}</p>
            <p class="text-sm text-gray-600">{{ detail?.supplier?.address || '-' }}</p>
          </div>
        </div>
  
        <div class="bg-white p-5 rounded-lg border border-gray-200">
          <div class="flex items-center gap-2 mb-3">
            <i class="pi pi-map-marker text-gray-400"></i>
            <h3 class="font-medium text-gray-700">Delivery Information</h3>
          </div>
          <div class="space-y-2">
            <p class="font-medium text-gray-900">{{ detail?.branch?.name || '-' }}</p>
            <p class="text-sm text-gray-600">{{ detail?.branch?.address || 'No address provided' }}</p>
            <p class="text-sm text-gray-600">{{ detail?.branch?.contact_number || '-' }}</p>
            <div class="pt-2"><span class="text-xs text-gray-500">Fulfillment: </span><Badge :value="formatFulfillment(detail?.fulfillment_method)" :severity="detail?.fulfillment_method === 'supplier_delivery' ? 'info' : 'success'" /></div>
          </div>
        </div>
      </div>
  
      <!-- Created By Info -->
      <div class="bg-gray-50 px-4 py-3 rounded-lg border border-gray-200 text-sm text-gray-600">
        <span class="font-medium text-gray-700">Created by:</span>
        {{ getPersonName(detail?.created_by) }}
        <span class="text-gray-400 mx-2">•</span>
        <span>{{ formatDate(detail?.created_at) }}</span>
      </div>
  
      <!-- Delivery Logs -->
  
  
      <!-- Order Items and Cost Summary -->
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <!-- PO Items Table -->
      <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white lg:col-span-2">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
          <div class="flex items-center gap-2">
            <i class="pi pi-shopping-cart text-gray-500"></i>
            <h3 class="font-medium text-gray-700">Order Items ({{ detail?.items?.length || 0 }})</h3>
          </div>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wider">
              <tr>
                <th class="px-6 py-3 text-left">#</th>
                <th class="px-6 py-3 text-left">Product</th>
                <th class="px-6 py-3 text-center">Quantity / UOM</th>
                <th class="px-6 py-3 text-right">Weight</th>
                <th class="px-6 py-3 text-right">Dimensions</th>
                <th class="px-6 py-3 text-right">Unit Price</th>
                <th class="px-6 py-3 text-right">Line Total</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="(item, index) in detail?.items" :key="item.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 text-gray-500">{{ index + 1 }}</td>
                <td class="px-6 py-4">
                  <div>
                    <p class="font-medium text-gray-900">
                      {{ item.variation?.variation_name
                        ? `${item.product?.product_name || '-'} — ${item.variation.variation_name}`
                        : (item.product?.product_name || '-') }}
                    </p>
                    <p class="text-xs text-gray-500 mt-1">SKU: {{ item.variation?.variation_sku || item.product?.sku || '-' }}</p>
                    <div v-if="item.quoted_variant_snapshot" class="mt-2 rounded-xl border border-orange-100 bg-orange-50 p-2 text-xs">
                      <p class="font-semibold text-orange-800">Quoted variant: {{ item.quoted_variant_snapshot.name }}</p>
                      <p class="mt-1 text-slate-600">{{ [item.quoted_variant_snapshot.size, item.quoted_variant_snapshot.color, item.quoted_variant_snapshot.material, item.quoted_variant_snapshot.texture, item.quoted_variant_snapshot.finish].filter(Boolean).join(' · ') || 'No additional attributes' }}</p>
                      <p class="text-slate-600">Supplier SKU: {{ item.quoted_variant_snapshot.supplier_sku || '—' }} · UOM: {{ item.quoted_variant_snapshot.unit_of_measurement || item.product?.unit_of_measurement || '—' }}</p>
                      <div v-if="item.quoted_variant_snapshot.image_paths?.length" class="mt-2 flex gap-2">
                        <a v-for="path in item.quoted_variant_snapshot.image_paths" :key="path" :href="supplierImageUrl(path)" target="_blank"><img :src="supplierImageUrl(path)" class="h-16 w-20 rounded-lg border object-cover" alt="Quoted variant" /></a>
                      </div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 text-center font-medium">{{ Number(item?.quantity_ordered || 0).toLocaleString() }} {{ item.variation?.unit_of_measurement || item.product?.unit_of_measurement || 'unit' }}</td>
                <td class="px-6 py-4 text-right"><div>{{ formatDecimal(itemWeight(item), 3) }} kg/unit</div><div class="text-xs text-gray-500">{{ formatDecimal(Number(itemWeight(item) || 0) * Number(item.quantity_ordered || 0), 3) }} kg total</div></td>
                <td class="px-6 py-4 text-right">{{ formatDimensions(item) }}</td>
                <td class="px-6 py-4 text-right font-mono">{{ formatCurrency(parseFloat(item?.unit_cost || 0)) }}</td>
                <td class="px-6 py-4 text-right font-mono font-medium text-blue-600">{{
                  formatCurrency(parseFloat(item?.line_total || 0)) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <aside class="h-fit rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:sticky lg:top-6">
        <div class="mb-5 flex items-center gap-2">
          <i class="pi pi-calculator text-slate-500"></i>
          <h3 class="font-semibold text-slate-900">Order Cost Summary</h3>
        </div>
        <div class="space-y-3 text-sm">
          <div class="flex items-center justify-between gap-4 text-slate-600"><span>Subtotal</span><span class="font-medium text-slate-900">{{ formatCurrency(parseFloat(detail?.subtotal || 0)) }}</span></div>
          <div class="flex items-center justify-between gap-4 text-slate-600"><span>Contract Discount ({{ formatDecimal(detail?.contract_discount_percentage) }}%)</span><span class="font-medium text-slate-900">- {{ formatCurrency(parseFloat(detail?.discount_amount || 0)) }}</span></div>
          <div class="flex items-center justify-between gap-4 text-slate-600"><span>Taxable Amount</span><span class="font-medium text-slate-900">{{ formatCurrency(Math.max(0, parseFloat(detail?.subtotal || 0) - parseFloat(detail?.discount_amount || 0))) }}</span></div>
          <div class="flex items-center justify-between gap-4 text-slate-600"><span>Tax ({{ formatDecimal(detail?.contract_tax_rate) }}%)</span><span class="font-medium text-slate-900">{{ formatCurrency(parseFloat(detail?.tax_amount || 0)) }}</span></div>
          <div class="flex items-center justify-between gap-4 text-slate-600"><span>Shipping Fee</span><span class="font-medium text-slate-900">{{ formatCurrency(parseFloat(detail?.shipping_cost || 0)) }}</span></div>
        </div>
        <div class="my-5 border-t border-slate-200"></div>
        <div class="flex items-center justify-between gap-4 rounded-xl bg-slate-900 px-4 py-3 text-white"><span class="font-semibold">Total Amount</span><span class="text-xl font-bold">{{ formatCurrency(parseFloat(detail?.total_amount || 0)) }}</span></div>
      </aside>
      </div>
  
      <!-- Notes -->
      <div v-if="detail?.notes" class="bg-white p-5 rounded-lg border border-gray-200">
        <div class="flex items-center gap-2 mb-3">
          <i class="pi pi-file-text text-gray-400"></i>
          <h3 class="font-medium text-gray-700">Notes</h3>
        </div>
        <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ detail.notes }}</p>
      </div>
  
      <!-- Terms and Conditions -->
      <div v-if="detail?.terms_conditions" class="bg-white p-5 rounded-lg border border-gray-200">
        <div class="flex items-center gap-2 mb-3">
          <i class="pi pi-file text-gray-400"></i>
          <h3 class="font-medium text-gray-700">Terms & Conditions</h3>
        </div>
        <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ detail.terms_conditions }}</p>
      </div>
  
      <!-- View Only Status Message -->
      <div v-if="detail?.status !== 'draft'"
        class="bg-gray-50 px-4 py-3 rounded-lg border border-gray-200 text-sm text-gray-600 text-center">
        <i class="pi pi-info-circle text-gray-400 mr-2"></i>
        This purchase order is in <strong>{{ formatStatus(detail?.status) }}</strong> status and is view-only.
      </div>
  
      <!-- Activity Timeline -->
      <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
          <div class="flex items-center gap-2">
            <i class="pi pi-history text-gray-500"></i>
            <h3 class="font-medium text-gray-700">Activity Timeline</h3>
          </div>
        </div>
        <div class="p-6">
          <ol class="divide-y divide-slate-100">
            <li v-for="(item, index) in timelineItems" :key="index" class="flex gap-4 py-3 first:pt-0 last:pb-0">
              <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-orange-500" />
              <div>
                <p class="text-sm font-medium text-slate-900">{{ item.title }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ item.subtitle }}</p>
              </div>
            </li>
          </ol>
        </div>
      </div>
  
      <!-- Goods Receipt Section -->
      <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
          <div class="flex items-center gap-2">
            <i class="pi pi-inbox text-gray-500"></i>
            <h3 class="font-medium text-gray-700">Goods Receipts</h3>
          </div>
        </div>
        <p v-if="!detail?.goods_receipts?.length" class="px-6 py-5 text-sm text-slate-500">No goods receipts recorded for this purchase order yet.</p>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wider">
              <tr>
                <th class="px-6 py-3 text-left">GR Number</th>
                <th class="px-6 py-3 text-left">Received Date</th>
                <th class="px-6 py-3 text-left">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="receipt in detail.goods_receipts" :key="receipt.id" class="hover:bg-gray-50">
                <td class="px-6 py-4">
                  <RouterLink :to="`/procurement/goods-receipts/${receipt.id}`"
                    class="text-blue-600 hover:text-blue-800 font-medium">
                    {{ receipt.grn_number }}
                  </RouterLink>
                </td>
                <td class="px-6 py-4 text-gray-700">{{ formatDateWithTime(receipt.receipt_date) }}</td>
                <td class="px-6 py-4">
                  <Tag :value="formatStatus(receipt.receipt_status)" :severity="receipt.receipt_status === 'full' ? 'success' : 'warning'" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

     
    </div>
    <!-- Not Found State -->
    <div v-else class="text-center py-12 bg-white rounded-lg border border-gray-200">
      <i class="pi pi-exclamation-circle text-4xl text-gray-300 mb-4"></i>
      <h3 class="text-lg font-medium text-gray-700">Purchase Order Not Found</h3>
      <p class="text-gray-500 mt-2 mb-4">The purchase order you're looking for doesn't exist or you don't have permission
        to view it.</p>
      <Button label="Back to List" icon="pi pi-arrow-left"
        @click="router.push({ name: 'procurement.purchase-orders' })" />
    </div>
  
    <!-- Email Preview Modal -->
    <Dialog v-model:visible="showEmailDialog" modal header="Supplier Email Preview" :style="{ width: '40rem' }">
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-semibold mb-2">To</label>
          <InputText v-model="emailForm.recipient_email" class="w-full" />
        </div>
        <div>
          <label class="block text-sm font-semibold mb-2">Subject</label>
          <InputText v-model="emailForm.subject" class="w-full" />
        </div>
        <div>
          <label class="block text-sm font-semibold mb-2">Message</label>
          <Textarea v-model="emailForm.message" rows="6" class="w-full" />
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" text @click="showEmailDialog = false" />
        <Button :label="emailForm.mode === 'send' ? 'Send' : 'Resend'" :loading="emailSending" @click="submitEmail" />
      </template>
    </Dialog>
  
  
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { useAuthStore } from '../../../../stores/auth'
import procurementService from '../../../../services/procurement.service'
import axiosClient from '@/axios'
import type { Map as MapboxMap, Marker as MapboxMarker } from 'mapbox-gl'
import 'mapbox-gl/dist/mapbox-gl.css'
import { fetchMapboxRoadRoute, requireMapboxToken, reverseGeocodeMapbox } from '@/utils/mapbox'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()
const authStore = useAuthStore()
const canManagePurchaseOrders = computed(() => authStore.hasPermission('procurement.purchase_orders.manage'))
const canManageReceiving = computed(() => authStore.hasPermission('inventory.receiving.manage'))
const poId = Number(route.params.id)
const steps = computed(() => {
  const sent = detail.value?.status === 'sent_to_supplier' || detail.value?.status === 'supplier_accepted'
  const inTransit = ['in_transit', 'out_for_delivery'].includes(String(shipmentStatus.value))
  const outForDelivery = ['out_for_delivery', 'delivered'].includes(String(shipmentStatus.value))
  const delivered = shipmentStatus.value === 'delivered'
  return [
    { key: 'supplier', label: 'Supplier Approval', index: 1, active: sent || inTransit || delivered },
    { key: 'transit', label: 'In Transit', index: 2, active: inTransit || delivered },
    { key: 'out_for_delivery', label: 'Out for Delivery', index: 3, active: outForDelivery },
    { key: 'delivered', label: 'Order Delivered', index: 4, active: delivered },
  ]
})

// State
const loading = ref(false)
const detail = ref<any>(null)
const shipmentStatus = ref<string | null>(null)
const deliveryLogs = ref<any[]>([])
const shipmentInfo = ref<any>(null)
const resolvedShipmentAddress = ref('')
const resolvedLogAddresses = ref<Record<number, string>>({})
const pickupMapElement = ref<HTMLElement | null>(null)
let pickupMap: MapboxMap | null = null
let mapboxgl: typeof import('mapbox-gl').default | null = null
let pickupTruckMarker: MapboxMarker | null = null
let pickupDestinationMarker: MapboxMarker | null = null
let pickupRefreshTimer: number | null = null
const showEmailDialog = ref(false)
const emailSending = ref(false)
const emailForm = ref({
  recipient_email: '',
  subject: '',
  message: '',
  mode: 'send' as 'send' | 'resend',
})

// Methods
const loadDetail = async () => {
  loading.value = true
  try {
    const response = await procurementService.getPurchaseOrder(poId)
    // Handle nested data structure
    if (response.data?.success && response.data?.data) {
      detail.value = response.data.data
    } else if (response.data?.data) {
      detail.value = response.data.data
    } else {
      detail.value = response.data
    }
  } catch (error) {
    console.error('Failed to load purchase order detail', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load purchase order',
      life: 3000
    })
    detail.value = null
  } finally {
    loading.value = false
  }
}

const editPO = () => {
  router.push({
    name: 'procurement.purchase-orders.edit',
    params: { id: poId }
  })
}

const openEmailDialog = (mode: 'send' | 'resend') => {
  if (!detail.value?.supplier?.email) {
    toast.add({
      severity: 'warn',
      summary: 'Missing Email',
      detail: 'Supplier email is not available.',
      life: 3000
    })
    return
  }

  emailForm.value = {
    recipient_email: detail.value.supplier.email,
    subject: `Purchase Order ${detail.value.po_number}`,
    message: `Hello ${detail.value.supplier.supplier_name},\n\nPlease find the attached Purchase Order ${detail.value.po_number}.\n\nThank you.`,
    mode,
  }
  showEmailDialog.value = true
}

const confirmSend = () => openEmailDialog('send')
const confirmResend = () => openEmailDialog('resend')

const submitEmail = async () => {
  if (!detail.value) return
  emailSending.value = true
  try {
    await procurementService.emailPurchaseOrder(poId, {
      recipient_email: emailForm.value.recipient_email,
      subject: emailForm.value.subject,
      message: emailForm.value.message,
    })

    if (emailForm.value.mode === 'send' && detail.value.status === 'approved') {
      await procurementService.sendPurchaseOrder(poId)
    }

    toast.add({
      severity: 'success',
      summary: 'Sent',
      detail: emailForm.value.mode === 'send' ? 'PO sent to supplier.' : 'PO resent to supplier.',
      life: 2500
    })
    showEmailDialog.value = false
    await loadDetail()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to send email.',
      life: 3000
    })
  } finally {
    emailSending.value = false
  }
}

const printPO = async () => {
  if (!detail.value) return

  try {
    const response = await procurementService.generatePOPdf(poId)
    const blob = new Blob([response.data], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    window.open(url, '_blank')
    window.setTimeout(() => window.URL.revokeObjectURL(url), 1000)
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'Failed to generate PO PDF.',
      life: 3000,
    })
  }
}

const timelineItems = computed(() => {
  if (!detail.value) return []
  const logs = detail.value.activity_logs || []

  if (!logs.length) {
    return [
      {
        title: 'PO Created',
        subtitle: detail.value.created_at ? formatDateWithTime(detail.value.created_at) : 'Date not available',
      },
    ]
  }

  return logs.map((log: any) => {
    const actor = log.user ? `${log.user.fname ?? ''} ${log.user.lname ?? ''}`.trim() : 'System'
    const action = log.action || ''
    const titleMap: Record<string, string> = {
      po_created: 'PO Created',
      po_approved: 'Finance Approved',
      po_sent_to_supplier: 'Sent to Supplier',
      po_email_sent: 'Email Sent to Supplier',
      po_supplier_accepted: 'Supplier Accepted',
      po_supplier_declined: 'Supplier Declined',
      po_shipment_created: 'Delivery Form Created',
      po_invoice_created: 'Invoice Created',
      po_rejected: 'Rejected by Finance',
      po_cancelled: 'Cancelled',
    }
    return {
      title: titleMap[action] || log.description || 'Activity',
      subtitle: `${formatDateWithTime(log.created_at)} | ${actor}`,
    }
  })
})

const formatStatus = (status: string) => {
  if (!status) return 'DRAFT'
  return status.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')
}

const getPersonName = (person: any): string => {
  const source = person?.user || person
  const name = [source?.fname, source?.lname].filter(Boolean).join(' ').trim()
  return name || source?.full_name || 'N/A'
}

const formatDateWithTime = (value?: string) => {
  if (!value) return '-'
  return new Date(value).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' })
}

const logDotColor = (eventType: string) => {
  const map: Record<string, string> = {
    pickup_assigned: 'bg-blue-500',
    in_transit: 'bg-amber-500',
    out_for_delivery: 'bg-blue-600',
    delivered: 'bg-emerald-600',
    cancelled: 'bg-red-500',
    note: 'bg-sky-500',
  }
  return map[normalizeEventType(eventType)] || 'bg-slate-300'
}

const normalizeEventType = (eventType?: string) => String(eventType || '').trim().toLowerCase().replaceAll(' ', '_')
const deliveryLogLabel = (eventType?: string) => ({
  pickup_assigned: 'Pickup Assigned',
  in_transit: 'Supplies Picked Up',
  out_for_delivery: 'Out for Delivery',
  delivered: 'Arrived at Store',
  cancelled: 'Pickup Cancelled',
  note: 'Driver Location Update',
}[normalizeEventType(eventType)] || formatStatus(eventType || 'pickup_update'))
const deliveryLogDescription = (eventType?: string) => ({
  pickup_assigned: 'A driver and vehicle were assigned to this purchase order.',
  in_transit: 'The supplies were collected from the supplier and are now in transit.',
  out_for_delivery: 'The supplies are on the final delivery leg to the store.',
  delivered: 'The driver arrived at the store with the supplies.',
  cancelled: 'The assigned supplier pickup was cancelled.',
  note: 'The driver shared a pickup location update.',
}[normalizeEventType(eventType)] || 'Pickup activity was recorded.')
const logIcon = (eventType?: string) => ({
  pickup_assigned: 'pi pi-user-plus',
  in_transit: 'pi pi-truck',
  out_for_delivery: 'pi pi-map-marker',
  delivered: 'pi pi-check-circle',
  cancelled: 'pi pi-times-circle',
  note: 'pi pi-map-marker',
}[normalizeEventType(eventType)] || 'pi pi-info-circle')
const logIconClass = (eventType?: string) => ({
  pickup_assigned: 'bg-blue-100 text-blue-600',
  in_transit: 'bg-amber-100 text-amber-700',
  out_for_delivery: 'bg-blue-100 text-blue-700',
  delivered: 'bg-emerald-100 text-emerald-700',
  cancelled: 'bg-red-100 text-red-700',
  note: 'bg-sky-100 text-sky-700',
}[normalizeEventType(eventType)] || 'bg-slate-100 text-slate-600')
const proofAttachments = (log: any) => ['in_transit', 'delivered'].includes(normalizeEventType(log?.event_type)) ? (log?.attachments || []) : []
const proofLabel = (eventType?: string) => normalizeEventType(eventType) === 'delivered' ? 'Arrival proof' : 'Supplier pickup proof'
const logAddress = (log: any) => log?.location_address || resolvedLogAddresses.value[Number(log?.id)] || ''
const currentPickupAddress = computed(() => shipmentInfo.value?.current_address || resolvedShipmentAddress.value || deliveryLogs.value.map(logAddress).find(Boolean) || '')

const reverseGeocode = async (latitude: number, longitude: number) => {
  return reverseGeocodeMapbox(latitude, longitude)
}

const resolveRecordedAddresses = async () => {
  resolvedShipmentAddress.value = ''
  resolvedLogAddresses.value = {}
  const tasks: Promise<void>[] = []

  if (!shipmentInfo.value?.current_address && shipmentInfo.value?.current_latitude && shipmentInfo.value?.current_longitude) {
    tasks.push(reverseGeocode(Number(shipmentInfo.value.current_latitude), Number(shipmentInfo.value.current_longitude))
      .then(address => { resolvedShipmentAddress.value = address })
      .catch(() => undefined))
  }

  deliveryLogs.value.forEach((log: any) => {
    if (!log.location_address && log.latitude && log.longitude) {
      tasks.push(reverseGeocode(Number(log.latitude), Number(log.longitude)).then(address => {
        if (address) resolvedLogAddresses.value = { ...resolvedLogAddresses.value, [Number(log.id)]: address }
      }).catch(() => undefined))
    }
  })

  await Promise.all(tasks)
}

const loadDeliveryLogs = async () => {
  try {
    const res = await axiosClient.get(`/api/procurement/purchase-orders/${poId}/delivery-logs`)
    const payload = res?.data ?? res ?? {}
    const data = payload.data ?? {}
    shipmentStatus.value = data.shipment_status || data.shipment?.status || null
    shipmentInfo.value = data.shipment || null
    deliveryLogs.value = data.logs || []
    await resolveRecordedAddresses()
    await nextTick()
    renderPickupMap()
    startPickupRefresh()
  } catch (e) {
    toast.add({
      severity: 'warn',
      summary: 'Delivery logs unavailable',
      detail: 'Could not load shipment status/logs for this PO.',
      life: 2000,
    })
    shipmentStatus.value = null
    shipmentInfo.value = null
    deliveryLogs.value = []
  }
}

const fetchRoadRoute = fetchMapboxRoadRoute

const renderPickupMap = async () => {
  if (!pickupMapElement.value || !shipmentInfo.value || !['in_transit', 'out_for_delivery'].includes(String(shipmentStatus.value))) return
  const branch = detail.value?.branch || {}
  const destination: [number, number] | null = Number.isFinite(Number(branch.latitude)) && Number.isFinite(Number(branch.longitude))
    ? [Number(branch.latitude), Number(branch.longitude)]
    : null
  if (!pickupMap) {
    mapboxgl = (await import('mapbox-gl')).default
    mapboxgl.accessToken = requireMapboxToken()
    pickupMap = new mapboxgl.Map({ container: pickupMapElement.value, style: 'mapbox://styles/mapbox/streets-v12', center: destination ? [destination[1], destination[0]] : [120.9842, 14.5995], zoom: destination ? 13 : 10 })
    await new Promise<void>((resolve) => pickupMap!.once('load', () => resolve()))
  }
  const points: [number, number][] = deliveryLogs.value.slice().reverse()
    .filter((log: any) => log.latitude !== null && log.longitude !== null)
    .map((log: any) => [Number(log.latitude), Number(log.longitude)])
    .filter(([lat, lng]) => Number.isFinite(lat) && Number.isFinite(lng))
  if (shipmentInfo.value.current_latitude && shipmentInfo.value.current_longitude) {
    points.push([Number(shipmentInfo.value.current_latitude), Number(shipmentInfo.value.current_longitude)])
  }
  const lastPoint = points[points.length - 1]
  pickupTruckMarker?.remove()
  pickupTruckMarker = null
  pickupDestinationMarker?.remove()
  pickupDestinationMarker = null
  if (lastPoint) {
    const truckElement = document.createElement('img')
    truckElement.src = '/images/truck-map-marker-orange.png'
    truckElement.alt = 'Truck location'
    truckElement.style.cssText = 'width:56px;height:56px;object-fit:contain;'
    pickupTruckMarker = new mapboxgl!.Marker({ element: truckElement, anchor: 'center' }).setLngLat([lastPoint[1], lastPoint[0]]).setPopup(new mapboxgl!.Popup({ offset: 25 }).setText('Truck location')).addTo(pickupMap!)
  }
  if (destination) {
    const destinationElement = document.createElement('div')
    destinationElement.style.cssText = 'width:18px;height:18px;border:3px solid white;border-radius:50%;background:#2563eb;box-shadow:0 1px 5px #0008;'
    pickupDestinationMarker = new mapboxgl!.Marker({ element: destinationElement, anchor: 'center' }).setLngLat([destination[1], destination[0]]).setPopup(new mapboxgl!.Popup({ offset: 15 }).setText('Store destination')).addTo(pickupMap!)
    let routePoints: [number, number][] = lastPoint ? [lastPoint, destination] : [destination]
    if (lastPoint) {
      try {
        const roadPoints = await fetchRoadRoute(lastPoint, destination)
        if (roadPoints.length > 1) routePoints = roadPoints
      } catch {
        // Use the direct fallback line if OSRM is temporarily unavailable.
      }
    }
    if (!pickupMap) return
    const routeFeature = { type: 'Feature' as const, properties: {}, geometry: { type: 'LineString' as const, coordinates: routePoints.map(([lat, lng]) => [lng, lat]) } }
    if (pickupMap.getSource('po-delivery-route')) {
      (pickupMap.getSource('po-delivery-route') as import('mapbox-gl').GeoJSONSource).setData(routeFeature)
    } else {
      pickupMap.addSource('po-delivery-route', { type: 'geojson', data: routeFeature })
      pickupMap.addLayer({ id: 'po-delivery-route-line', type: 'line', source: 'po-delivery-route', paint: { 'line-color': '#2563eb', 'line-width': 5, 'line-opacity': 0.8 } })
    }
    const bounds = new mapboxgl!.LngLatBounds()
    routePoints.forEach(([lat, lng]) => bounds.extend([lng, lat]))
    pickupMap.fitBounds(bounds, { padding: 40, maxZoom: 15 })
  }
  pickupMap.resize()
}

const startPickupRefresh = () => {
  if (!['in_transit', 'out_for_delivery'].includes(String(shipmentStatus.value))) {
    if (pickupRefreshTimer !== null) window.clearInterval(pickupRefreshTimer)
    pickupRefreshTimer = null
    return
  }
  if (pickupRefreshTimer !== null) return
  pickupRefreshTimer = window.setInterval(loadDeliveryLogs, 15000)
}

const statusSeverity = (status: string): 'success' | 'info' | 'warn' | 'danger' | 'secondary' | 'contrast' => {
  const statusMap: Record<string, any> = {
    approved: 'success',
    sent_to_supplier: 'info',
    supplier_accepted: 'success',
    in_transit: 'warn',
    out_for_delivery: 'info',
    delivered: 'success',
    pending_finance_approval: 'warn',
    rejected_finance: 'danger',
    declined_supplier: 'danger',
    cancelled: 'danger',
    revision_requested: 'warn',
    draft: 'contrast'
  }
  return statusMap[status] || 'contrast'
}

const formatDate = (date: string | Date | undefined) => {
  if (!date) return '-'
  return new Intl.DateTimeFormat('en-PH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  }).format(new Date(date))
}

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(value)
}
const supplierImageUrl = (path: string) => path?.startsWith('http') ? path : `/storage/${String(path || '').replace(/^\/+/, '')}`

const formatDecimal = (value: any, digits = 2) => Number(value || 0).toLocaleString('en-PH', { maximumFractionDigits: digits })
const itemWeight = (item: any) => item?.variation?.weight_kg ?? item?.product?.weight_kg ?? item?.weight_kg ?? 0
const formatDimensions = (item: any) => {
  const length = item?.variation?.length_cm ?? item?.product?.length_cm ?? item?.length_cm
  const width = item?.variation?.width_cm ?? item?.product?.width_cm ?? item?.width_cm
  const height = item?.variation?.height_cm ?? item?.product?.height_cm ?? item?.height_cm
  return length != null && width != null && height != null
    ? `${formatDecimal(length)} × ${formatDecimal(width)} × ${formatDecimal(height)} cm`
    : '-'
}
const formatFulfillment = (value?: string) => value === 'supplier_delivery' ? 'Supplier Delivery' : value === 'store_pickup' ? 'Store Pickup' : 'Not selected'

const formatPaymentTerms = (term: string) => {
  const terms: Record<string, string> = {
    cash_on_delivery: 'Cash on Delivery',
    net_7: 'Net 7 Days',
    net_15: 'Net 15 Days',
    net_30: 'Net 30 Days',
    net_60: 'Net 60 Days',
    advance_payment: 'Advance Payment'
  }
  return terms[term] || term?.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ') || '-'
}

const calculateNetCharges = () => {
  const shipping = parseFloat(detail.value?.shipping_cost || 0)
  const discount = parseFloat(detail.value?.discount_amount || 0)
  return shipping - discount
}

const approvePO = async () => {
  // Show a simple dialog for approval notes
  const notes = prompt('Add approval notes (optional):')
  if (notes === null) return

  try {
    await procurementService.approvePurchaseOrder(poId, {
      notes: notes || undefined
    })
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Purchase order approved successfully',
      life: 2000
    })
    await loadDetail()
  } catch (error) {
    console.error('Failed to approve purchase order', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to approve purchase order',
      life: 3000
    })
  }
}

const rejectPO = async () => {
  const reason = prompt('Please provide a reason for rejection:')
  if (!reason) {
    toast.add({
      severity: 'warn',
      summary: 'Warning',
      detail: 'Rejection reason is required',
      life: 2000
    })
    return
  }

  try {
    await procurementService.rejectPurchaseOrder(poId, reason)
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Purchase order rejected',
      life: 2000
    })
    await loadDetail()
  } catch (error) {
    console.error('Failed to reject purchase order', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to reject purchase order',
      life: 3000
    })
  }
}

const createGoodsReceipt = () => {
  router.push({ name: 'inventory.goods-receipts.create', query: { po_id: poId } })
}

onMounted(() => {
  loadDetail()
  loadDeliveryLogs()
})

onBeforeUnmount(() => {
  if (pickupRefreshTimer !== null) window.clearInterval(pickupRefreshTimer)
  pickupRefreshTimer = null
  pickupMap?.remove()
  pickupMap = null
  pickupTruckMarker = null
  pickupDestinationMarker = null
})
</script>

<style scoped>
/* Remove any emoji-related styles */
</style>
