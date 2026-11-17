<template>
  <div class="max-w-7xl mx-auto space-y-6 py-6 px-4 sm:px-6 lg:px-8">
    <!-- iOS-style Header -->
    <div class="flex items-start justify-between">
      <div class="flex items-start gap-4">
        <button 
          @click="router.push('/supplier-portal/pos')"
          class="w-10 h-10 rounded-full bg-white hover:bg-gray-100 flex items-center justify-center transition-colors shadow-sm border border-gray-200"
        >
          <i class="pi pi-chevron-left text-gray-600 text-lg"></i>
        </button>
        <div>
          <h1 class="text-2xl font-semibold text-gray-900 tracking-tight">Purchase Order Details</h1>
          <p class="text-sm text-gray-500 mt-1">Full PO details with delivery and shipment information</p>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <Button
          v-if="canRespondToPO"
          label="Approve"
          icon="pi pi-check"
          severity="warn"
          size="small"
          :loading="poApprovalSaving"
          @click="confirmApprovePO"
        />
        <Button v-if="canRespondToPO" label="Reject" icon="pi pi-times" severity="secondary" outlined size="small" :disabled="poApprovalSaving" @click="poRejectionDialogVisible = true" />
        <Button v-if="po" label="Timeline" icon="pi pi-history" severity="secondary" outlined size="small" @click="showTimelineDialog = true" />
        <Button
          v-if="existingInvoice"
          label="View Invoice"
          icon="pi pi-eye"
          severity="info"
          text
          @click="router.push({ name: 'supplier.pos.invoice-view', params: { id: po?.id } })"
        />
        <Tag 
          v-if="po" 
          :value="po.supplier_fulfillment_status ? supplierFulfillmentLabel(po.supplier_fulfillment_status) : formatStatus(po.status)"
          :severity="getStatusSeverity(po.status)"
          class="rounded-full px-3 py-1 text-xs font-medium"
        />
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2 space-y-6">
        <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
          <template #content>
            <div class="p-6">
              <Skeleton width="150px" height="20px" class="mb-4" />
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <Skeleton height="60px" class="rounded-xl" />
                <Skeleton height="60px" class="rounded-xl" />
                <Skeleton height="60px" class="rounded-xl" />
              </div>
            </div>
          </template>
        </Card>
        <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
          <template #content>
            <div class="p-6">
              <Skeleton width="150px" height="20px" class="mb-4" />
              <Skeleton height="200px" class="rounded-xl" />
            </div>
          </template>
        </Card>
      </div>
      <div class="space-y-6">
        <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
          <template #content>
            <div class="p-6">
              <Skeleton width="150px" height="20px" class="mb-4" />
              <div class="space-y-3">
                <Skeleton height="20px" v-for="i in 5" :key="i" />
              </div>
            </div>
          </template>
        </Card>
      </div>
    </div>

    <!-- Main Content -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Left Column - PO Details -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Rejection Banner -->
        <Card v-if="isDeclined" class="rounded-2xl border border-red-200 bg-red-50/60 shadow-sm overflow-hidden">
          <template #content>
            <div class="p-6">
              <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                  <i class="pi pi-exclamation-circle text-red-600 text-lg"></i>
                </div>
                <div>
                  <h3 class="font-semibold text-red-800">Rejected by Supplier</h3>
                  <p class="mt-2 text-sm text-red-700">{{ rejectionReason || 'No rejection reason provided.' }}</p>
                </div>
              </div>
            </div>
          </template>
        </Card>

        <!-- PO Summary Card -->
        <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
          <template #header>
            <div class="px-6 pt-6">
              <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                  <i class="pi pi-file text-blue-600 text-sm"></i>
                </div>
                <h2 class="text-lg font-semibold text-gray-900">PO Summary</h2>
              </div>
            </div>
          </template>
          <template #content>
            <div class="p-6 pt-0">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-gray-50 rounded-xl p-4">
                  <p class="text-xs text-gray-500 mb-1">PO Number</p>
                  <p class="font-semibold text-gray-900">{{ po?.po_number || '-' }}</p>
                </div>
                <div class="bg-gray-50 rounded-xl p-4">
                  <p class="text-xs text-gray-500 mb-1">Created</p>
                  <p class="font-medium text-gray-900">{{ formatDate(po?.created_at) }}</p>
                </div>
                <div class="bg-gray-50 rounded-xl p-4">
                  <p class="text-xs text-gray-500 mb-1">Supplier</p>
                  <p class="font-medium text-gray-900">{{ po?.supplier?.supplier_name || '-' }}</p>
                </div>
              </div>
            </div>
          </template>
        </Card>

        <!-- Branch Information Card -->
        <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
          <template #header>
            <div class="px-6 pt-6">
              <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                  <i class="pi pi-map-marker text-green-600 text-sm"></i>
                </div>
                <h2 class="text-lg font-semibold text-gray-900">Branch Information</h2>
              </div>
            </div>
          </template>
          <template #content>
            <div class="p-6 pt-0">
              <div class="bg-gray-50 rounded-xl p-4">
                <p class="font-semibold text-gray-900">{{ po?.branch?.name || '-' }}</p>
                <p class="text-sm text-gray-600 mt-1">{{ po?.branch?.address || '-' }} {{ po?.branch?.city || '' }}</p>
                <p v-if="po?.branch?.contact_number" class="mt-2 flex items-center gap-2 text-sm text-gray-600"><i class="pi pi-phone" aria-hidden="true"></i><span>{{ po.branch.contact_number }}</span></p>
              </div>
            </div>
          </template>
        </Card>

        <!-- Items Card -->
        <Card v-if="!isDeclined" class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
          <template #header>
            <div class="px-6 pt-6">
              <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center">
                  <i class="pi pi-box text-purple-600 text-sm"></i>
                </div>
                <h2 class="text-lg font-semibold text-gray-900">Items</h2>
              </div>
            </div>
          </template>
          <template #content>
            <div class="p-6 pt-0">
              <div class="overflow-x-auto rounded-xl border border-gray-200">
                <table class="w-full text-sm">
                  <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                    <tr>
                      <th class="px-4 py-3 text-left">Item</th>
                      <th class="px-4 py-3 text-right">Qty / UOM</th>
                      <th class="px-4 py-3 text-right">Weight</th>
                      <th class="px-4 py-3 text-right">Dimensions</th>
                      <th class="px-4 py-3 text-right">Price</th>
                      <th class="px-4 py-3 text-right">Line Total</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-200">
                    <tr v-for="item in po?.items || []" :key="item.id" class="hover:bg-gray-50 transition-colors">
                      <td class="px-4 py-3">
                        <div class="font-medium text-gray-900">
                          {{ item.variation?.variation_name
                            ? `${item.product?.product_name || 'Item'} - ${item.variation.variation_name}`
                            : (item.product?.product_name || 'Item') }}
                        </div>
                        <div class="text-xs text-gray-500 mt-0.5">{{ item.variation?.variation_sku || item.product?.sku || '' }}</div>
                      </td>
                      <td class="px-4 py-3 text-right font-medium">{{ formatQuantity(item.quantity_ordered) }} {{ item.variation?.unit_of_measurement || item.product?.unit_of_measurement || 'unit' }}</td>
                      <td class="px-4 py-3 text-right">
                        <div>{{ formatDecimal(itemWeight(item), 3) }} kg/unit</div>
                        <div class="text-xs text-slate-500">{{ formatDecimal(Number(itemWeight(item) || 0) * Number(item.quantity_ordered || 0), 3) }} kg total</div>
                      </td>
                      <td class="px-4 py-3 text-right">{{ formatDimensions(item) }}</td>
                      <td class="px-4 py-3 text-right">{{ formatCurrency(item.unit_cost) }}</td>
                      <td class="px-4 py-3 text-right font-semibold text-green-600">{{ formatCurrency(item.line_total) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>

            </div>
          </template>
        </Card>
      </div>

      <!-- Right Column - Shipment Information -->
      <div v-if="!isDeclined" class="space-y-6">
        <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
          <template #header><div class="px-6 pt-6"><h3 class="text-lg font-semibold text-gray-900">All Fees</h3></div></template>
          <template #content>
            <div class="space-y-3 p-6 pt-0 text-sm">
              <div class="flex justify-between"><span class="text-gray-500">Items subtotal (VAT inclusive)</span><span class="font-medium">{{ formatCurrency(po?.subtotal) }}</span></div>
              <template v-if="po?.fulfillment_method === 'supplier_delivery'">
                <div class="flex justify-between"><span class="text-gray-500">Shipment weight</span><span class="font-medium">{{ formatDecimal(totalOrderWeight, 3) }} kg</span></div>
                <div v-if="po?.shipping_fee_breakdown?.distance_km" class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs">
                  <p class="mb-2 font-semibold text-slate-800">Delivery fee rundown</p>
                  <div class="flex justify-between gap-3"><span class="text-slate-500">Road distance</span><span>{{ formatDecimal(po.shipping_fee_breakdown.distance_km) }} km × {{ formatMoney(po.shipping_fee_breakdown.rate_per_km) }}/km</span></div>
                  <div class="mt-1 flex justify-between gap-3"><span class="text-slate-500">Weight</span><span>{{ formatDecimal(po.shipping_fee_breakdown.total_weight_kg, 3) }} kg × {{ formatMoney(po.shipping_fee_breakdown.rate_per_kg) }}/kg</span></div>
                  <div class="mt-2 flex justify-between border-t border-slate-200 pt-2"><span>Distance fee</span><span>{{ formatMoney(po.shipping_fee_breakdown.distance_fee) }}</span></div>
                  <div class="mt-1 flex justify-between"><span>Weight fee</span><span>{{ formatMoney(po.shipping_fee_breakdown.weight_fee) }}</span></div>
                </div>
                <div class="flex justify-between"><span class="text-gray-500">Supplier delivery fee</span><span class="font-medium">PHP {{ formatMoney(po?.shipping_cost) }}</span></div>
              </template>
              <div class="flex justify-between"><span class="text-gray-500">Taxable amount (VAT exclusive)</span><span class="font-medium">{{ formatCurrency(taxableAmount) }}</span></div>
              <div class="flex justify-between"><span class="text-gray-500">VAT included ({{ formatDecimal(contractTaxRate) }}%)</span><span class="font-medium">{{ formatCurrency(po?.tax_amount) }}</span></div>
              <div class="flex justify-between border-t border-gray-200 pt-3 text-base font-semibold"><span>Total</span><span class="text-emerald-600">{{ formatCurrency(po?.total_amount) }}</span></div>
            </div>
          </template>
        </Card>

        <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
          <template #header><div class="px-6 pt-6"><h3 class="text-lg font-semibold text-gray-900">Fulfillment</h3></div></template>
          <template #content><div class="p-6 pt-0"><Tag :value="fulfillmentLabel" :severity="po?.fulfillment_method === 'supplier_delivery' ? 'info' : 'success'" /><p class="mt-3 text-sm text-gray-600">{{ fulfillmentDescription }}</p></div></template>
        </Card>

        <Card v-if="po?.supplier_fulfillment_status" class="rounded-2xl border border-orange-200 shadow-sm">
          <template #header><div class="px-6 pt-6"><h3 class="text-lg font-semibold text-gray-900">Fulfillment Updates</h3></div></template>
          <template #content>
            <div class="space-y-4 p-6 pt-0">
              <Tag :value="supplierFulfillmentLabel(po.supplier_fulfillment_status)" severity="info" />
              <div v-if="canUpdateSupplierFulfillment" class="flex flex-wrap gap-2">
                <Button v-if="po.fulfillment_method === 'store_pickup' && po.supplier_fulfillment_status === 'preparing'" label="Ready for Pickup" icon="pi pi-check" severity="warn" :loading="fulfillmentSaving" @click="submitFulfillmentUpdate('ready_for_pickup')" />
                <template v-if="po.fulfillment_method === 'supplier_delivery' && po.supplier_fulfillment_status === 'preparing'">
                  <Button label="Dispatch (Attach Photo)" icon="pi pi-camera" severity="warn" :loading="fulfillmentSaving" @click="selectFulfillmentPhotos('dispatched')" />
                </template>
                <Button v-if="po.fulfillment_method === 'supplier_delivery' && po.supplier_fulfillment_status === 'dispatched'" label="Mark Delivered (Attach Photos)" icon="pi pi-images" severity="warn" :loading="fulfillmentSaving" @click="selectFulfillmentPhotos('delivered')" />
                <input ref="fulfillmentPhotoInput" type="file" accept="image/jpeg,image/png,image/webp" multiple class="hidden" @change="onFulfillmentPhotosSelected" />
              </div>
              <p v-if="fulfillmentError" class="text-sm text-red-600">{{ fulfillmentError }}</p>
              <div v-for="update in fulfillmentUpdates" :key="update.id" class="rounded-xl border border-slate-200 p-3">
                <div class="flex flex-wrap items-center justify-between gap-2"><span class="font-medium text-slate-800">{{ supplierFulfillmentLabel(update.status) }}</span><span class="text-xs text-slate-500">{{ formatDate(update.created_at, true) }}</span></div>
                <p v-if="update.notes" class="mt-1 text-sm text-slate-600">{{ update.notes }}</p>
                <div v-if="update.photos?.length" class="mt-3 flex flex-wrap gap-2"><a v-for="photo in update.photos" :key="photo" :href="photo" target="_blank" rel="noopener"><img :src="photo" alt="Supplier fulfillment proof" class="h-24 w-28 rounded-lg border border-slate-200 object-cover" /></a></div>
              </div>
            </div>
          </template>
        </Card>

        <Card v-if="isStoreLogisticsOnTheWay" class="overflow-hidden rounded-2xl border border-blue-200 shadow-sm">
          <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 bg-slate-50 px-6 py-4">
              <div><h3 class="font-semibold text-slate-900">Store Logistics Live Map</h3><p class="text-xs text-slate-500">Live driver location for this store pickup.</p></div>
              <div class="flex items-center gap-2"><Tag value="On the way" severity="info" /><Button label="Order Timeline" icon="pi pi-history" size="small" severity="secondary" outlined @click="showTimelineDialog = true" /></div>
            </div>
          </template>
          <template #content>
            <div v-if="currentStoreLogisticsAddress" class="px-6 pt-4 text-sm text-slate-600"><i class="pi pi-map-marker mr-1 text-blue-600" />{{ currentStoreLogisticsAddress }}</div>
            <div ref="storeLogisticsMapElement" class="h-[360px] w-full" />
            <p v-if="!hasStoreLogisticsCoordinates" class="px-6 pb-4 text-xs text-amber-700">The map will show once the driver shares a location.</p>
          </template>
        </Card>

        <!-- Delivery Information Card -->
        <Card v-if="shipment" class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
          <template #header>
            <div class="px-6 pt-6">
              <div class="flex items-center gap-2">
                <h3 class="text-lg font-semibold text-gray-900">Delivery Information</h3>
              </div>
            </div>
          </template>
          <template #content>
            <div class="p-6 pt-0">
              <div class="space-y-3">
                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                  <span class="text-sm text-gray-500">Driver</span>
                  <span class="font-medium text-gray-900">{{ assignedDriverName }}</span>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-gray-100"><span class="text-sm text-gray-500">Employee Number</span><span class="font-medium text-gray-900">{{ shipment?.driver_employee?.employee_number || '-' }}</span></div>
                <div class="flex items-center justify-between py-2 border-b border-gray-100"><span class="text-sm text-gray-500">Contact</span><span class="font-medium text-gray-900">{{ shipment?.driver_contact || shipment?.driver_user?.phone_number || '-' }}</span></div>
                <div class="flex items-center justify-between py-2 border-b border-gray-100"><span class="text-sm text-gray-500">Email</span><span class="font-medium text-gray-900">{{ shipment?.driver_user?.email || shipment?.driver_employee?.user?.email || '-' }}</span></div>
                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                  <span class="text-sm text-gray-500">Plate Number</span>
                  <span class="font-medium text-gray-900">{{ shipment?.plate_number || '-' }}</span>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                  <span class="text-sm text-gray-500">Truck</span>
                  <span class="font-medium text-gray-900">{{ vehicleDescription }}</span>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-gray-100"><span class="text-sm text-gray-500">Capacity</span><span class="font-medium text-gray-900">{{ shipment?.vehicle?.capacity_kg ? `${formatDecimal(shipment.vehicle.capacity_kg)} kg` : '-' }}</span></div>
                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                  <span class="text-sm text-gray-500">Distance</span>
                  <span class="font-medium text-gray-900">{{ shipment?.distance_km || '-' }} km</span>
                </div>
                <div class="flex items-center justify-between py-2">
                  <span class="text-sm text-gray-500">Cost per KM</span>
                  <span class="font-medium text-gray-900">{{ formatCurrency(shipment?.cost_per_km) }}</span>
                </div>
              </div>
            </div>
          </template>
        </Card>

        <!-- Shipment Summary Card -->
        <Card v-if="shipment" class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
          <template #header>
            <div class="px-6 pt-6">
              <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                  <i class="pi pi-send text-blue-600 text-sm"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">Shipment Summary</h3>
              </div>
            </div>
          </template>
          <template #content>
            <div class="p-6 pt-0">
              <div class="space-y-3">
                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                  <span class="text-sm text-gray-500">Status</span>
                  <Tag 
                    :value="formatStatus(shipment?.status)" 
                    :severity="getShipmentStatusSeverity(shipment?.status)"
                    class="rounded-full text-xs px-3 py-1"
                  />
                </div>
                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                  <span class="text-sm text-gray-500">Shipping Cost</span>
                  <span class="font-medium text-emerald-600">{{ formatCurrency(shipment?.shipping_cost) }}</span>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                  <span class="text-sm text-gray-500">Tax Rate</span>
                  <span class="font-medium text-gray-900">{{ Number(shipment?.tax_rate || 0).toFixed(2) }}%</span>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                  <span class="text-sm text-gray-500">Expected Delivery</span>
                  <span class="font-medium text-gray-900">{{ formatDate(shipment?.expected_delivery_date) }}</span>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                  <span class="text-sm text-gray-500">Dispatched</span>
                  <span class="font-medium text-gray-900">{{ formatDate(shipment?.dispatched_at, true) }}</span>
                </div>
              </div>

              <!-- Addresses -->
              <div class="mt-4 pt-4 border-t border-gray-200">
                <p class="text-xs text-gray-500 mb-2">Origin</p>
                <p class="text-sm text-gray-700 bg-gray-50 rounded-xl p-3">{{ shipment?.origin_address || '-' }}</p>
              </div>
              <div class="mt-3">
                <p class="text-xs text-gray-500 mb-2">Destination</p>
                <p class="text-sm text-gray-700 bg-gray-50 rounded-xl p-3">{{ shipment?.destination_address || '-' }}</p>
              </div>
            </div>
          </template>
        </Card>
        <Card v-if="goodsReceipt" class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
          <template #header>
            <div class="px-6 pt-6">
              <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center">
                  <i class="pi pi-check-circle text-emerald-600 text-sm"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">Goods Receipt</h3>
              </div>
            </div>
          </template>
          <template #content>
            <div class="p-6 pt-0 space-y-5">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="space-y-1">
                  <p class="text-xs text-gray-500 uppercase tracking-wide">GRN Number</p>
                  <p class="font-semibold text-gray-900">{{ goodsReceipt?.grn_number || '-' }}</p>
                </div>
                <div class="space-y-1">
                  <p class="text-xs text-gray-500 uppercase tracking-wide">Status</p>
                  <Tag
                    :value="formatGoodsReceiptStatus(goodsReceipt?.receipt_status)"
                    :severity="goodsReceiptStatusSeverity(goodsReceipt?.receipt_status)"
                    class="rounded-full text-xs px-3 py-1"
                  />
                </div>
                <div class="space-y-1">
                  <p class="text-xs text-gray-500 uppercase tracking-wide">Received Date</p>
                  <p class="font-semibold text-gray-900">{{ formatDate(goodsReceiptReceivedDate) }}</p>
                </div>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="space-y-1">
                  <p class="text-xs text-gray-500 uppercase tracking-wide">Qty Expected</p>
                  <p class="font-semibold text-gray-900">{{ formatQuantity(goodsReceiptTotals.expected) }}</p>
                </div>
                <div class="space-y-1">
                  <p class="text-xs text-gray-500 uppercase tracking-wide">Qty Received</p>
                  <p class="font-semibold text-emerald-600">{{ formatQuantity(goodsReceiptTotals.received) }}</p>
                </div>
                <div class="space-y-1">
                  <p class="text-xs text-gray-500 uppercase tracking-wide">Qty Damaged</p>
                  <p class="font-semibold text-red-600">{{ formatQuantity(goodsReceiptTotals.damaged) }}</p>
                </div>
              </div>
              <div class="overflow-x-auto rounded-xl border border-gray-200">
                <table class="min-w-full text-sm">
                  <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                    <tr>
                      <th class="px-4 py-3 text-left">Item</th>
                      <th class="px-4 py-3 text-right">Expected</th>
                      <th class="px-4 py-3 text-right">Received</th>
                      <th class="px-4 py-3 text-right">Damaged</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-200">
                    <tr
                      v-for="item in goodsReceiptItems"
                      :key="item.id"
                      class="hover:bg-gray-50 transition-colors"
                    >
                      <td class="px-4 py-3">
                        <div class="font-medium text-gray-900">{{ getGoodsReceiptProductName(item) }}</div>
                        <div class="text-xs text-gray-500 mt-0.5">{{ item.product?.sku || 'SKU unavailable' }}</div>
                      </td>
                      <td class="px-4 py-3 text-right font-medium">{{ formatQuantity(item.quantity_expected) }}</td>
                      <td class="px-4 py-3 text-right font-medium text-emerald-600">{{ formatQuantity(item.quantity_received) }}</td>
                      <td class="px-4 py-3 text-right font-medium text-red-600">{{ formatQuantity(item.quantity_damaged) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </template>
        </Card>

        <Card v-for="resolution in receiptResolutions" :key="resolution.id" class="rounded-2xl border border-amber-200 shadow-sm overflow-hidden">
          <template #header>
            <div class="flex items-center justify-between px-6 pt-6">
              <div class="flex items-center gap-2"><i class="pi pi-flag text-amber-600"></i><h3 class="font-semibold text-gray-900">Receipt Resolution</h3></div>
              <Tag :value="formatStatus(resolution.status)" :severity="resolutionSeverity(resolution.status)" />
            </div>
          </template>
          <template #content>
            <div class="space-y-4 p-6 pt-0 text-sm">
              <div class="grid grid-cols-2 gap-3 rounded-xl bg-amber-50 p-4">
                <div><p class="text-xs text-gray-500">Reference</p><p class="font-semibold">{{ resolution.resolution_number }}</p></div>
                <div><p class="text-xs text-gray-500">Required Action</p><p class="font-semibold">{{ formatStatus(resolution.resolution_type) }}</p></div>
              </div>
              <p v-if="resolution.procurement_notes" class="text-gray-600">{{ resolution.procurement_notes }}</p>
              <p v-if="resolution.supplier_action" class="font-medium text-slate-800">Supplier response: {{ formatStatus(resolution.supplier_action) }}</p>
              <p v-if="resolution.supplier_responded_at" class="text-xs text-slate-500">Submitted {{ formatDate(resolution.supplier_responded_at, true) }}</p>
              <div class="overflow-hidden rounded-xl border border-gray-200">
                <table class="w-full text-sm">
                  <thead class="bg-gray-50"><tr><th class="p-3 text-left">Item</th><th class="p-3 text-right">Quantity Due</th></tr></thead>
                  <tbody><tr v-for="item in resolution.items || []" :key="`${resolution.id}-${item.product_id}`" class="border-t"><td class="p-3">{{ resolutionProductName(resolution, item.product_id) }}</td><td class="p-3 text-right font-semibold">{{ item.quantity_due }}</td></tr></tbody>
                </table>
              </div>
              <div v-if="resolution.status === 'pending_supplier'" class="space-y-3 rounded-xl border border-slate-200 p-4">
                <p class="font-medium text-slate-900">Choose how you will resolve this discrepancy</p>
                <Select v-model="resolutionResponses[resolution.id].action" :options="supplierResolutionActions" optionLabel="label" optionValue="value" fluid />
                <div v-if="['issue_credit', 'dispute'].includes(resolutionResponses[resolution.id].action)" class="space-y-2">
                  <p class="text-xs text-slate-600">Credit for the reported quantity: {{ formatMoney(resolution.estimated_credit_amount) }}. This is recorded against the PO for Finance review; it does not initiate a payment.</p>
                </div>
                <Textarea v-model="resolutionResponses[resolution.id].notes" rows="3" fluid placeholder="Explain your response" />
                <input type="file" accept="image/jpeg,image/png,image/webp,application/pdf" class="block w-full rounded-lg border border-gray-200 p-2 text-sm" @change="onResolutionResponseProof($event, resolution.id)" />
                <div class="flex justify-end"><Button label="Submit Response" icon="pi pi-send" size="small" :loading="resolutionSavingId === resolution.id" @click="submitResolutionResponse(resolution)" /></div>
              </div>
              <div v-if="resolution.status === 'credit_submitted'" class="rounded-lg bg-blue-50 p-3 text-sm text-blue-800">
                Credit of PHP {{ formatMoney(resolution.supplier_credit_amount) }} submitted and awaiting the store's review.
              </div>
              <div v-if="resolution.status === 'disputed'" class="rounded-lg bg-amber-50 p-3 text-sm text-amber-800">Your dispute is awaiting Procurement review.</div>
              <p v-if="resolution.supplier_response_notes" class="whitespace-pre-wrap text-sm text-slate-600">Supplier response: {{ resolution.supplier_response_notes }}</p>
              <a v-if="resolution.proof_path" :href="`/storage/${resolution.proof_path}`" target="_blank" rel="noopener" class="text-sm text-blue-600 underline">View supplier evidence</a>
              <div v-if="resolution.follow_up_receipts?.length || resolution.follow_up_receipt" class="flex flex-wrap gap-2 text-xs text-slate-600">
                <span>Follow-up receipt:</span>
                <span v-for="receipt in (resolution.follow_up_receipts?.length ? resolution.follow_up_receipts : [resolution.follow_up_receipt])" :key="receipt.id" class="font-semibold">{{ receipt.grn_number || `GRN #${receipt.id}` }}</span>
              </div>
              <div v-if="resolution.status === 'accepted' && ['send_missing', 'replace_damaged'].includes(resolution.supplier_action)" class="space-y-4 rounded-xl border border-gray-200 p-4">
                <p class="text-sm text-slate-600">No new PO, payment, or shipping fee is created for this correction. Submit the follow-up delivery details; the store will record arrival against the original PO.</p>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                  <div><label class="mb-2 block font-medium">Promised delivery date</label><DatePicker v-model="deliveryForms[resolution.id].promised_delivery_date" :minDate="new Date()" dateFormat="M d, yy" fluid /></div>
                  <div><label class="mb-2 block font-medium">Delivery note number</label><InputText v-model="deliveryForms[resolution.id].delivery_note_number" fluid /></div>
                </div>
                <div><label class="mb-2 block font-medium">Delivery notes</label><Textarea v-model="deliveryForms[resolution.id].supplier_delivery_notes" rows="3" fluid /></div>
                <div><label class="mb-2 block font-medium">Proof attachment</label><input type="file" accept="image/jpeg,image/png,image/webp,application/pdf" class="block w-full rounded-lg border border-gray-200 p-2 text-sm" @change="onResolutionProof($event, resolution.id)" /></div>
                <div class="flex justify-end"><Button label="Confirm Delivery Details" icon="pi pi-send" size="small" :loading="resolutionSavingId === resolution.id" @click="submitResolutionDelivery(resolution)" /></div>
              </div>
              <div v-if="resolution.status === 'delivery_submitted'" class="rounded-lg bg-blue-50 p-3 text-sm text-blue-800">Follow-up delivery details submitted. The store will record the delivery as a new goods receipt under this PO.</div>
              <p v-if="resolution.supplier_rejection_reason" class="rounded-lg bg-red-50 p-3 text-red-700">Rejected: {{ resolution.supplier_rejection_reason }}</p>
            </div>
          </template>
        </Card>

        <Card v-if="existingInvoice" class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
          <template #header>
            <div class="px-6 pt-6">
              <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-sky-100 flex items-center justify-center">
                  <i class="pi pi-file text-sky-600 text-sm"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">Invoice Payment</h3>
              </div>
            </div>
          </template>
          <template #content>
            <div class="p-6 pt-0 space-y-2 text-sm text-gray-700">
              <p>Invoice #: <span class="font-semibold text-gray-900">{{ existingInvoice?.invoice_number || '-' }}</span></p>
              <p>Status: <span class="font-semibold text-gray-900">{{ formatStatus(existingInvoice?.status) }}</span></p>
              <p>Payment Status: <span class="font-semibold text-gray-900">{{ formatStatus(existingInvoice?.payment_status || 'pending') }}</span></p>
              <p>Total Paid: <span class="font-semibold text-emerald-600">PHP {{ formatMoney(existingInvoice?.payment_amount || 0) }}</span></p>
              <p>Payment Date: <span class="font-semibold text-gray-900">{{ formatDate(existingInvoice?.payment_date) }}</span></p>
              <p>Account Balance: <span class="font-semibold text-gray-900">PHP {{ formatMoney(po?.supplier?.current_balance || 0) }}</span></p>
            </div>
          </template>
        </Card>
      </div>
    </div>

    <ConfirmDialog />
    <Dialog v-model:visible="showTimelineDialog" modal header="Purchase Order Timeline" :style="{ width: 'min(36rem, calc(100vw - 2rem))' }" :breakpoints="{ '640px': 'calc(100vw - 2rem)' }">
      <p class="mb-4 text-sm text-slate-500">{{ po?.po_number }} order and fulfillment activity.</p>
      <ol v-if="timelineItems.length" class="max-h-[65vh] space-y-0 overflow-y-auto pr-1">
        <li v-for="(item, index) in timelineItems" :key="`${item.title}-${index}`" class="flex gap-3 border-l border-slate-200 py-3 pl-4 first:pt-0 last:pb-0">
          <span class="-ml-[1.35rem] mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full bg-orange-500 ring-4 ring-white" />
          <div class="min-w-0"><p class="text-sm font-semibold text-slate-900">{{ item.title }}</p><p class="mt-1 text-xs text-slate-500">{{ formatDate(item.date, true) }}</p><p v-if="item.description" class="mt-1 whitespace-pre-line text-sm text-slate-600">{{ item.description }}</p></div>
        </li>
      </ol>
      <p v-else class="py-5 text-sm text-slate-500">No order activity has been recorded yet.</p>
      <template #footer><Button label="Close" size="small" severity="secondary" outlined @click="showTimelineDialog = false" /></template>
    </Dialog>
    <Dialog v-model:visible="rejectResolutionVisible" modal header="Reject Receipt Resolution" :style="{ width: 'min(92vw, 480px)' }">
      <div class="space-y-2">
        <label class="block text-sm font-medium">Reason for rejection</label>
        <Textarea v-model="resolutionRejectReason" rows="5" fluid autofocus />
        <small v-if="resolutionRejectError" class="text-red-500">{{ resolutionRejectError }}</small>
      </div>
      <template #footer>
        <Button label="No" severity="secondary" text @click="rejectResolutionVisible = false" />
        <Button label="Reject" severity="danger" :loading="resolutionSavingId === selectedResolution?.id" @click="confirmRejectResolution" />
      </template>
    </Dialog>
    <Dialog v-model:visible="poRejectionDialogVisible" modal header="Reject Purchase Order" :style="{ width: 'min(92vw, 480px)' }">
      <div class="space-y-2">
        <label for="po-rejection-reason" class="block text-sm font-medium">Reason for rejection</label>
        <Textarea id="po-rejection-reason" v-model="poRejectionReason" rows="4" fluid autofocus placeholder="Explain why you cannot accept this order" />
        <small v-if="poRejectionError" class="text-red-600">{{ poRejectionError }}</small>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" outlined @click="poRejectionDialogVisible = false" />
        <Button label="Confirm Rejection" severity="warn" :loading="poApprovalSaving" @click="rejectPO" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import type { Map as MapboxMap, Marker as MapboxMarker } from 'mapbox-gl'
import 'mapbox-gl/dist/mapbox-gl.css'
import { fetchMapboxRoadRoute, requireMapboxToken } from '@/utils/mapbox'
import supplierService from '../../../services/supplier.service'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()
const poApprovalSaving = ref(false)
const poRejectionDialogVisible = ref(false)
const poRejectionReason = ref('')
const poRejectionError = ref('')

const loading = ref(false)
const po = ref<any>(null)
const fulfillmentUpdates = ref<any[]>([])
const fulfillmentPhotoInput = ref<HTMLInputElement | null>(null)
const pendingFulfillmentStatus = ref('')
const fulfillmentSaving = ref(false)
const fulfillmentError = ref('')
const shipment = ref<any>(null)
const goodsReceipt = ref<any>(null)
const existingInvoice = ref<any>(null)
const rejectionReason = ref<string | null>(null)
const supplierFeedback = ref<any>(null)
const supplierLocation = ref<any>(null)
const deliveryLogs = ref<any[]>([])
const shipmentInfo = ref<any>(null)
const shipmentStatus = ref('')
const showTimelineDialog = ref(false)
const storeLogisticsMapElement = ref<HTMLElement | null>(null)
const storeLogisticsMap = ref<MapboxMap | null>(null)
const storeTruckMarker = ref<MapboxMarker | null>(null)
const storeDestinationMarker = ref<MapboxMarker | null>(null)
let trackingRefreshTimer: number | null = null
let mapboxgl: typeof import('mapbox-gl').default | null = null
const contractTaxRate = ref(0)
const contractDiscountPercent = ref(0)
const totalOrderWeight = computed(() => (po.value?.items || []).reduce((sum: number, item: any) => sum + itemWeight(item) * Number(item.quantity_ordered || 0), 0))
const receiptResolutions = ref<any[]>([])
const deliveryForms = ref<Record<number, any>>({})
const resolutionResponses = ref<Record<number, any>>({})
const supplierResolutionActions = [
  { label: 'Send missing items', value: 'send_missing' },
  { label: 'Replace damaged items', value: 'replace_damaged' },
  { label: 'Issue a credit', value: 'issue_credit' },
  { label: 'Dispute the discrepancy', value: 'dispute' },
]
const resolutionSavingId = ref<number | null>(null)
const rejectResolutionVisible = ref(false)
const selectedResolution = ref<any>(null)
const resolutionRejectReason = ref('')
const resolutionRejectError = ref('')

// Computed properties
const deliveryCharge = computed(() => {
  return Number(shipment.value?.shipping_cost || 0)
})

const canRespondToPO = computed(() => {
  const status = String(po.value?.status || '').trim().toLowerCase().replace(/[ -]+/g, '_')
  return status === 'sent_to_supplier'
    && !['accepted', 'rejected'].includes(String(supplierFeedback.value?.response || '').trim().toLowerCase())
})

const confirmApprovePO = () => {
  if (!po.value || !canRespondToPO.value) return
  confirm.require({
    header: 'Approve purchase order?',
    message: `Confirm that ${po.value.po_number} can be fulfilled using the selected delivery arrangement.`,
    icon: 'pi pi-exclamation-triangle',
    rejectLabel: 'Cancel',
    acceptLabel: 'Approve',
    accept: approvePO,
  })
}

const approvePO = async () => {
  if (!po.value || !canRespondToPO.value) return
  poApprovalSaving.value = true
  try {
    await supplierService.submitPOFeedback({ purchase_order_id: po.value.id, response: 'accepted' })
    await loadDetail()
    toast.add({ severity: 'success', summary: 'Order approved', detail: 'The order is now being prepared.', life: 3000 })
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Unable to approve order', detail: error.response?.data?.message || 'Please try again.', life: 4000 })
  } finally {
    poApprovalSaving.value = false
  }
}

const rejectPO = async () => {
  const reason = poRejectionReason.value.trim()
  poRejectionError.value = ''
  if (!po.value || !canRespondToPO.value) return
  if (reason.length < 5) {
    poRejectionError.value = 'Please provide a reason with at least 5 characters.'
    return
  }

  poApprovalSaving.value = true
  try {
    await supplierService.submitPOFeedback({ purchase_order_id: po.value.id, response: 'rejected', rejection_reason: reason })
    poRejectionDialogVisible.value = false
    await loadDetail()
    toast.add({ severity: 'success', summary: 'Order rejected', detail: 'The store has been notified.', life: 3000 })
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Unable to reject order', detail: error.response?.data?.message || 'Please try again.', life: 4000 })
  } finally {
    poApprovalSaving.value = false
  }
}

const totalWithDelivery = computed(() => {
  const subtotal = Number(po.value?.subtotal || 0)
  return subtotal + deliveryCharge.value
})

const taxableAmount = computed(() => {
  const gross = Math.max(0, Number(po.value?.subtotal || 0) - Number(po.value?.discount_amount || 0))
  return contractTaxRate.value > 0 ? gross / (1 + contractTaxRate.value / 100) : gross
})
const isStoreLogisticsOnTheWay = computed(() => po.value?.fulfillment_method === 'store_pickup' && ['en_route_to_supplier', 'in_transit', 'out_for_delivery'].includes(String(shipmentStatus.value || shipmentInfo.value?.status)))
const shouldRefreshTracking = computed(() => po.value?.fulfillment_method === 'store_pickup' && ['supplier_accepted', 'in_transit', 'out_for_delivery'].includes(String(po.value?.status)))
const coordinate = (value: any) => value !== null && value !== undefined && value !== '' && Number.isFinite(Number(value)) ? Number(value) : null
const hasStoreLogisticsCoordinates = computed(() => coordinate(shipmentInfo.value?.current_latitude) !== null && coordinate(shipmentInfo.value?.current_longitude) !== null || deliveryLogs.value.some(log => coordinate(log.latitude) !== null && coordinate(log.longitude) !== null))
const currentStoreLogisticsAddress = computed(() => shipmentInfo.value?.current_address || deliveryLogs.value.find(log => log.location_address)?.location_address || '')
const timelineItems = computed(() => {
  const items: Array<{ title: string; description?: string; date: string | null }> = []
  if (po.value?.created_at) items.push({ title: 'Purchase order created', date: po.value.created_at })
  for (const approval of po.value?.approvals_received || []) {
    if (approval.approved_at) items.push({ title: 'Purchase order approval recorded', description: [approval.approver_name, approval.notes].filter(Boolean).join(' - '), date: approval.approved_at })
  }
  if (supplierFeedback.value?.submitted_at) items.push({ title: supplierFeedback.value.response === 'accepted' ? 'Order approved by supplier' : 'Order rejected by supplier', description: supplierFeedback.value.rejection_reason || supplierFeedback.value.delivery_notes || '', date: supplierFeedback.value.submitted_at })
  for (const update of fulfillmentUpdates.value) items.push({ title: `Supplier fulfillment: ${supplierFulfillmentLabel(update.status)}`, description: update.notes || '', date: update.created_at })
  for (const log of deliveryLogs.value) items.push({ title: deliveryLogLabel(log.event_type), description: [log.notes, log.location_address, log.receiver_name ? `Receiver: ${log.receiver_name}` : ''].filter(Boolean).join('\n'), date: log.logged_at || log.created_at })
  for (const resolution of receiptResolutions.value) {
    if (resolution.created_at) items.push({ title: `Receipt discrepancy sent: ${formatStatus(resolution.resolution_type)}`, description: resolution.procurement_notes || '', date: resolution.created_at })
    if (resolution.supplier_responded_at) items.push({ title: `Supplier response: ${formatStatus(resolution.supplier_action || resolution.status)}`, description: resolution.supplier_response_notes || '', date: resolution.supplier_responded_at })
    if (resolution.credit_reviewed_at) items.push({ title: 'Procurement reviewed discrepancy', description: resolution.procurement_notes || '', date: resolution.credit_reviewed_at })
    for (const receipt of resolution.follow_up_receipts || []) items.push({ title: `Follow-up receipt recorded: ${receipt.grn_number || 'Goods receipt'}`, date: receipt.created_at })
  }
  return items.filter(item => item.date).sort((a, b) => new Date(a.date as string).getTime() - new Date(b.date as string).getTime())
})
const fulfillmentLabel = computed(() => po.value?.fulfillment_method === 'supplier_delivery' ? 'Supplier Delivery' : po.value?.fulfillment_method === 'store_pickup' ? 'Store Pickup' : 'Not selected')
const fulfillmentDescription = computed(() => po.value?.fulfillment_method === 'supplier_delivery'
  ? 'Your supplier team is responsible for delivering this order to the store.'
  : po.value?.fulfillment_method === 'store_pickup'
    ? 'The store will assign a driver and vehicle to collect this order.'
    : 'The fulfillment method has not been selected yet.')
const canUpdateSupplierFulfillment = computed(() => po.value?.status === 'supplier_accepted' || po.value?.status === 'in_transit')
const assignedDriverName = computed(() => {
  const user = shipment.value?.driver_employee?.user || shipment.value?.driver_user
  return [user?.fname, user?.lname].filter(Boolean).join(' ') || shipment.value?.driver_name || '-'
})
const vehicleDescription = computed(() => {
  const vehicle = shipment.value?.vehicle
  return [vehicle?.brand, vehicle?.model, vehicle?.vehicle_type].filter(Boolean).join(' ') || shipment.value?.truck_brand || shipment.value?.truck_type || '-'
})

const isDeclined = computed(() => {
  const status = po.value?.status
  return status === 'declined_supplier' || status === 'declined_by_supplier'
})

const goodsReceiptItems = computed(() => goodsReceipt.value?.items || [])

const goodsReceiptTotals = computed(() => {
  const items = goodsReceiptItems.value
  return items.reduce<{ expected: number; received: number; damaged: number }>(
    (acc, item) => {
      acc.expected += Number(item.quantity_expected || 0)
      acc.received += Number(item.quantity_received || 0)
      acc.damaged += Number(item.quantity_damaged || 0)
      return acc
    },
    { expected: 0, received: 0, damaged: 0 }
  )
})

const goodsReceiptReceivedDate = computed(() => {
  return goodsReceipt.value?.receipt_date || goodsReceipt.value?.received_date || goodsReceipt.value?.created_at
})


// Helper functions
const getStatusSeverity = (status: string): 'success' | 'info' | 'warn' | 'danger' | 'secondary' => {
  const map: Record<string, any> = {
    draft: 'secondary',
    pending_finance_approval: 'warn',
    approved: 'success',
    sent_to_supplier: 'warn',
    supplier_accepted: 'success',
    in_transit: 'warn',
    delivered: 'success',
    rejected_finance: 'danger',
    declined_supplier: 'danger',
    cancelled: 'danger',
  }
  return map[status] || 'info'
}

const getShipmentStatusSeverity = (status: string): 'success' | 'info' | 'warn' | 'danger' | 'secondary' => {
  const map: Record<string, any> = {
    pending: 'secondary',
    dispatched: 'info',
    in_transit: 'warn',
    delivered: 'success',
    cancelled: 'danger',
  }
  return map[status] || 'info'
}

const formatGoodsReceiptStatus = (status: string): string => {
  if (!status) return 'Pending'
  const map: Record<string, string> = {
    full: 'Full',
    partial: 'Partial',
    damaged: 'Damaged',
    rejected: 'Rejected',
  }
  return map[status] || status.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')
}

const goodsReceiptStatusSeverity = (status: string): 'success' | 'warn' | 'danger' | 'info' => {
  if (!status) return 'info'
  const map: Record<string, 'success' | 'warn' | 'danger' | 'info'> = {
    full: 'success',
    partial: 'warn',
    damaged: 'danger',
    rejected: 'danger',
  }
  return map[status] || 'info'
}

const getGoodsReceiptProductName = (item: any): string => {
  const parent = item?.product?.product_name || 'Item'
  return item?.variation?.variation_name ? `${parent} - ${item.variation.variation_name}` : parent
}

const formatQuantity = (value?: number): string => {
  return Number(value ?? 0).toLocaleString('en-PH')
}

const formatDecimal = (value?: number, digits = 2): string => Number(value ?? 0).toLocaleString('en-PH', { maximumFractionDigits: digits })
const itemWeight = (item: any): number => Number(item?.variation?.weight_kg ?? item?.product?.weight_kg ?? item?.weight_kg ?? 0)
const formatDimensions = (item: any): string => {
  const length = item?.variation?.length_cm ?? item?.product?.length_cm ?? item?.length_cm
  const width = item?.variation?.width_cm ?? item?.product?.width_cm ?? item?.width_cm
  const height = item?.variation?.height_cm ?? item?.product?.height_cm ?? item?.height_cm
  return length != null && width != null && height != null
    ? `${formatDecimal(length)} x ${formatDecimal(width)} x ${formatDecimal(height)} cm`
    : '-'
}

const formatStatus = (status: string): string => {
  if (!status) return '-'
  if (status === 'sent_to_supplier') return 'Pending'
  return status.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')
}

const formatDate = (date?: string, withTime = false): string => {
  if (!date) return '-'
  const parsed = new Date(date)
  if (isNaN(parsed.getTime())) return date
  
  const options: Intl.DateTimeFormatOptions = { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric' 
  }
  if (withTime) {
    options.hour = '2-digit'
    options.minute = '2-digit'
  }
  return parsed.toLocaleDateString('en-PH', options)
}

const formatMoney = (value: number): string => {
  return new Intl.NumberFormat('en-PH', { 
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(value || 0)
}

const formatCurrency = (value: number): string => `PHP ${formatMoney(value)}`

const goToInvoice = () => {
  if (!po.value) return
  router.push({ name: 'supplier.pos.invoice', params: { id: po.value.id } })
}

// Load detail
  const loadDetail = async () => {
    try {
      loading.value = true
      const id = Number(route.params.id)
      const res = await supplierService.getSupplierPODetail(id)
      const payload = res.data || res
      po.value = payload?.data?.po || payload?.po || null
      fulfillmentUpdates.value = payload?.data?.fulfillment_updates || po.value?.supplier_fulfillment_updates || []
      supplierFeedback.value = payload?.data?.supplier_feedback || null
      rejectionReason.value = payload?.data?.rejection_reason || payload?.rejection_reason || null
      shipment.value = payload?.data?.shipment || null
      shipmentInfo.value = shipment.value
      shipmentStatus.value = String(shipment.value?.status || '')
      deliveryLogs.value = payload?.data?.delivery_logs || shipment.value?.logs || []
      supplierLocation.value = payload?.data?.supplier_location || null
      goodsReceipt.value = payload?.data?.goods_receipt || null
      existingInvoice.value = payload?.data?.invoice || null
      contractTaxRate.value = Number(payload?.data?.contract_tax_rate || po.value?.contract_tax_rate || 0)
      contractDiscountPercent.value = Number(payload?.data?.contract_discount_percent || po.value?.contract_discount_percentage || 0)
      const resolutionResponse = await supplierService.getReceiptResolutions(id)
      receiptResolutions.value = resolutionResponse?.data || []
      receiptResolutions.value.forEach((resolution: any) => {
        deliveryForms.value[resolution.id] = {
          promised_delivery_date: resolution.promised_delivery_date ? new Date(resolution.promised_delivery_date) : null,
          delivery_note_number: resolution.delivery_note_number || '',
          supplier_delivery_notes: resolution.supplier_delivery_notes || '',
          proof: null,
        }
        resolutionResponses.value[resolution.id] = {
          action: resolution.resolution_type === 'replacement' ? 'replace_damaged' : 'send_missing',
          notes: '',
          credit_amount: null,
          proof: null,
        }
      })

      await nextTick()
      await renderStoreLogisticsMap()
      startTrackingRefresh()
    } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load PO details.',
      life: 3000,
    })
  } finally {
      loading.value = false
    }
  }

const refreshTracking = async () => {
  if (!po.value?.id) return
  try {
    const response = await supplierService.getPODeliveryTracking(Number(po.value.id))
    const payload = response?.data || response
    shipmentInfo.value = payload?.data?.shipment || null
    shipment.value = shipmentInfo.value
    shipmentStatus.value = String(shipmentInfo.value?.status || '')
    deliveryLogs.value = payload?.data?.delivery_logs || []
    supplierLocation.value = payload?.data?.supplier_location || supplierLocation.value
    await nextTick()
    await renderStoreLogisticsMap()
    if (!isStoreLogisticsOnTheWay.value && trackingRefreshTimer !== null) {
      window.clearInterval(trackingRefreshTimer)
      trackingRefreshTimer = null
    }
  } catch {
    // Keep the last known location visible if a periodic refresh is temporarily unavailable.
  }
}

const startTrackingRefresh = () => {
  if (!shouldRefreshTracking.value) {
    if (trackingRefreshTimer !== null) window.clearInterval(trackingRefreshTimer)
    trackingRefreshTimer = null
    return
  }
  if (trackingRefreshTimer === null) trackingRefreshTimer = window.setInterval(refreshTracking, 15000)
}

const trackingDestination = (): [number, number] | null => {
  const location = shipmentStatus.value === 'en_route_to_supplier'
    ? (supplierLocation.value || shipmentInfo.value?.supplier || po.value?.supplier)
    : (shipmentInfo.value?.branch || po.value?.branch)
  const latitude = coordinate(location?.latitude)
  const longitude = coordinate(location?.longitude)
  return latitude !== null && longitude !== null ? [latitude, longitude] : null
}

const renderStoreLogisticsMap = async () => {
  const container = storeLogisticsMapElement.value
  if (!container || !isStoreLogisticsOnTheWay.value) return
  try {
    if (!mapboxgl) {
      mapboxgl = (await import('mapbox-gl')).default
      mapboxgl.accessToken = requireMapboxToken()
    }
    const destination = trackingDestination()
    const latestLogWithCoordinates = deliveryLogs.value.find(log => coordinate(log.latitude) !== null && coordinate(log.longitude) !== null)
    const currentLatitude = coordinate(shipmentInfo.value?.current_latitude) ?? coordinate(latestLogWithCoordinates?.latitude)
    const currentLongitude = coordinate(shipmentInfo.value?.current_longitude) ?? coordinate(latestLogWithCoordinates?.longitude)
    const current: [number, number] | null = currentLatitude !== null && currentLongitude !== null ? [currentLatitude, currentLongitude] : null
    if (!storeLogisticsMap.value) {
      storeLogisticsMap.value = new mapboxgl.Map({
        container,
        style: 'mapbox://styles/mapbox/streets-v12',
        center: current ? [current[1], current[0]] : destination ? [destination[1], destination[0]] : [120.9842, 14.5995],
        zoom: current || destination ? 12 : 6,
      })
      await new Promise<void>(resolve => storeLogisticsMap.value!.once('load', () => resolve()))
    }
    storeTruckMarker.value?.remove()
    storeDestinationMarker.value?.remove()
    if (current) {
      const truck = document.createElement('img')
      truck.src = '/images/truck-map-marker-orange.png'
      truck.alt = 'Store logistics vehicle location'
      truck.style.cssText = 'width:48px;height:48px;object-fit:contain;'
      storeTruckMarker.value = new mapboxgl.Marker({ element: truck, anchor: 'center' })
        .setLngLat([current[1], current[0]])
        .setPopup(new mapboxgl.Popup({ offset: 24 }).setText('Store logistics vehicle'))
        .addTo(storeLogisticsMap.value)
    }
    if (destination) {
      storeDestinationMarker.value = new mapboxgl.Marker({ color: '#2563eb' })
        .setLngLat([destination[1], destination[0]])
        .setPopup(new mapboxgl.Popup({ offset: 16 }).setText(shipmentStatus.value === 'en_route_to_supplier' ? 'Supplier pickup location' : 'Store branch'))
        .addTo(storeLogisticsMap.value)
    }
    if (current && destination) {
      let routePoints: [number, number][] = [current, destination]
      try {
        const roadPoints = await fetchMapboxRoadRoute(current, destination)
        if (roadPoints.length > 1) routePoints = roadPoints
      } catch { /* The straight line still gives a useful live location view. */ }
      const sourceData = { type: 'Feature' as const, properties: {}, geometry: { type: 'LineString' as const, coordinates: routePoints.map(([lat, lng]) => [lng, lat]) } }
      if (storeLogisticsMap.value.getSource('supplier-po-live-route')) {
        (storeLogisticsMap.value.getSource('supplier-po-live-route') as import('mapbox-gl').GeoJSONSource).setData(sourceData)
      } else {
        storeLogisticsMap.value.addSource('supplier-po-live-route', { type: 'geojson', data: sourceData })
        storeLogisticsMap.value.addLayer({ id: 'supplier-po-live-route-line', type: 'line', source: 'supplier-po-live-route', paint: { 'line-color': '#2563eb', 'line-width': 5, 'line-opacity': 0.8 } })
      }
      const bounds = new mapboxgl.LngLatBounds()
      routePoints.forEach(([lat, lng]) => bounds.extend([lng, lat]))
      storeLogisticsMap.value.fitBounds(bounds, { padding: 40, maxZoom: 15 })
    } else if (current) {
      storeLogisticsMap.value.flyTo({ center: [current[1], current[0]], zoom: 14 })
    }
    storeLogisticsMap.value.resize()
  } catch {
    toast.add({ severity: 'warn', summary: 'Map unavailable', detail: 'Live tracking could not load right now.', life: 2500 })
  }
}

const deliveryLogLabel = (eventType?: string) => ({
  pickup_assigned: 'Driver assigned',
  en_route_to_supplier: 'Driver en route to supplier',
  in_transit: 'Pickup confirmed; order in transit',
  out_for_delivery: 'Order out for delivery',
  delivered: 'Delivered to store',
  cancelled: 'Pickup cancelled',
  note: 'Driver location update',
} as Record<string, string>)[String(eventType || '').toLowerCase().replaceAll(' ', '_')] || formatStatus(eventType || 'pickup update')

const supplierFulfillmentLabel = (status: string) => ({ preparing: 'Preparing', ready_for_pickup: 'Ready for Pickup', dispatched: 'Dispatched', delivered: 'Delivered' } as Record<string, string>)[status] || status
const selectFulfillmentPhotos = (status: string) => { pendingFulfillmentStatus.value = status; fulfillmentPhotoInput.value?.click() }
const onFulfillmentPhotosSelected = async (event: Event) => {
  const input = event.target as HTMLInputElement
  const photos = Array.from(input.files || [])
  if (!photos.length) return
  await submitFulfillmentUpdate(pendingFulfillmentStatus.value, photos)
  input.value = ''
}
const submitFulfillmentUpdate = async (status: string, photos: File[] = []) => {
  if (!po.value || fulfillmentSaving.value) return
  fulfillmentSaving.value = true
  fulfillmentError.value = ''
  try {
    const payload = new FormData()
    payload.append('status', status)
    photos.forEach(photo => payload.append('photos[]', photo))
    const response = await supplierService.updatePOFulfillment(Number(po.value.id), payload)
    po.value = response?.data?.purchase_order || po.value
    await loadDetail()
    toast.add({ severity: 'success', summary: 'Updated', detail: 'PO fulfillment status updated.', life: 2500 })
  } catch (error: any) {
    const message = error?.response?.data?.message || 'Unable to update fulfillment status.'
    await loadDetail()
    if (po.value?.supplier_fulfillment_status === status) {
      fulfillmentError.value = ''
      toast.add({ severity: 'success', summary: 'Updated', detail: 'PO fulfillment status updated.', life: 2500 })
    } else {
      fulfillmentError.value = message
    }
  } finally { fulfillmentSaving.value = false }
}

const resolutionSeverity = (status: string) => status === 'resolved' ? 'success' : status === 'rejected' ? 'danger' : status === 'delivery_submitted' ? 'info' : 'warn'
const resolutionProductName = (resolution: any, productId: number) => resolution.original_receipt?.items?.find((item: any) => Number(item.product_id) === Number(productId))?.product?.product_name || `Product #${productId}`

const confirmAcceptResolution = (resolution: any) => confirm.require({
  header: 'Accept Resolution',
  message: `Accept ${resolution.resolution_number} and prepare the required delivery?`,
  rejectLabel: 'No',
  acceptLabel: 'Accept',
  acceptClass: 'p-button-success',
  accept: async () => {
    resolutionSavingId.value = resolution.id
    try {
      await supplierService.acceptReceiptResolution(resolution.id)
      await loadDetail()
      toast.add({ severity: 'success', summary: 'Accepted', detail: 'Resolution accepted.', life: 3000 })
    } catch (error: any) {
      toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Unable to accept resolution', life: 4000 })
    } finally { resolutionSavingId.value = null }
  },
})

const onResolutionResponseProof = (event: Event, resolutionId: number) => {
  resolutionResponses.value[resolutionId].proof = (event.target as HTMLInputElement).files?.[0] || null
}

const submitResolutionResponse = async (resolution: any) => {
  const form = resolutionResponses.value[resolution.id]
  if (!form?.notes?.trim()) {
    toast.add({ severity: 'warn', summary: 'Response required', detail: 'Add a note explaining your response.', life: 3000 })
    return
  }
  const payload = new FormData()
  payload.append('action', form.action)
  payload.append('notes', form.notes.trim())
  if (['issue_credit', 'dispute'].includes(form.action)) payload.append('credit_amount', String(resolution.estimated_credit_amount))
  if (form.proof) payload.append('proof', form.proof)
  resolutionSavingId.value = resolution.id
  try {
    await supplierService.respondReceiptResolution(resolution.id, payload)
    await loadDetail()
    toast.add({ severity: 'success', summary: 'Response submitted', detail: 'Your response was added to this PO.', life: 3000 })
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Unable to respond', detail: error.response?.data?.message || 'Unable to submit supplier response.', life: 4000 })
  } finally { resolutionSavingId.value = null }
}

const openRejectResolution = (resolution: any) => {
  selectedResolution.value = resolution
  resolutionRejectReason.value = ''
  resolutionRejectError.value = ''
  rejectResolutionVisible.value = true
}

const confirmRejectResolution = async () => {
  if (resolutionRejectReason.value.trim().length < 5) {
    resolutionRejectError.value = 'Please provide at least 5 characters.'
    return
  }
  resolutionSavingId.value = selectedResolution.value.id
  try {
    await supplierService.rejectReceiptResolution(selectedResolution.value.id, resolutionRejectReason.value.trim())
    rejectResolutionVisible.value = false
    await loadDetail()
    toast.add({ severity: 'success', summary: 'Rejected', detail: 'The store has been informed.', life: 3000 })
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Unable to reject resolution', life: 4000 })
  } finally { resolutionSavingId.value = null }
}

const onResolutionProof = (event: Event, resolutionId: number) => {
  deliveryForms.value[resolutionId].proof = (event.target as HTMLInputElement).files?.[0] || null
}

const submitResolutionDelivery = async (resolution: any) => {
  const form = deliveryForms.value[resolution.id]
  if (!form?.promised_delivery_date || !form?.proof) {
    toast.add({ severity: 'warn', summary: 'Required', detail: 'Select the delivery date and proof attachment.', life: 3000 })
    return
  }
  const payload = new FormData()
  payload.append('promised_delivery_date', new Date(form.promised_delivery_date).toISOString().slice(0, 10))
  if (form.delivery_note_number) payload.append('delivery_note_number', form.delivery_note_number)
  if (form.supplier_delivery_notes) payload.append('supplier_delivery_notes', form.supplier_delivery_notes)
  payload.append('proof', form.proof)
  resolutionSavingId.value = resolution.id
  try {
    await supplierService.submitResolutionDelivery(resolution.id, payload)
    await loadDetail()
    toast.add({ severity: 'success', summary: 'Submitted', detail: 'Delivery details and proof submitted.', life: 3000 })
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Unable to submit delivery details', life: 4000 })
  } finally { resolutionSavingId.value = null }
}

onMounted(loadDetail)
onBeforeUnmount(() => {
  if (trackingRefreshTimer !== null) window.clearInterval(trackingRefreshTimer)
  storeTruckMarker.value?.remove()
  storeDestinationMarker.value?.remove()
  storeLogisticsMap.value?.remove()
  storeLogisticsMap.value = null
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
