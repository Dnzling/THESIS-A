<template>
  <div class="pos-container">
    <ConfirmDialog />

    <!-- Main Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <!-- Products Section -->
      <Card class="lg:col-span-2">
        <template #title>
          <div class="flex items-center justify-between flex-wrap gap-2">
            <span>Products</span>
            <div class="relative w-full sm:w-64">
              <InputText 
                v-model="search" 
                placeholder="Search products..." 
                fluid
                class="pl-9"
              />
            </div>
          </div>
        </template>
        <template #content>
          <div v-if="loadingProducts" class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <div v-for="n in 6" :key="n" class="h-64 animate-pulse rounded-xl border border-slate-200 bg-slate-100" />
          </div>
          <div v-else-if="!products.length" class="rounded-xl border border-dashed border-slate-300 py-12 text-center text-sm text-slate-500">
            <template v-if="productLoadError">
              <p class="text-red-600">{{ productLoadError }}</p>
              <Button label="Retry" size="small" text class="mt-2" @click="loadProducts" />
            </template>
            <template v-else>No in-stock products found.</template>
          </div>
          <div v-else class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <article
              v-for="row in products"
              :key="row.id"
              class="group cursor-pointer overflow-hidden rounded-xl border border-slate-200 bg-white transition duration-200 hover:-translate-y-0.5 hover:shadow-md"
              role="button"
              tabindex="0"
              @click="openProductDetails(row)"
              @keydown.enter="openProductDetails(row)"
            >
              <div class="relative flex h-40 items-center justify-center bg-slate-50">
                <img v-if="productImage(row)" :src="productImage(row)" :alt="row.product?.product_name || 'Product image'" class="h-full w-full object-contain p-3" loading="lazy" />
                <i v-else class="pi pi-image text-3xl text-slate-300" />
                <Badge class="absolute left-2 top-2" :value="`${Number(row.quantity_available || 0)} in stock`" :severity="getStockSeverity(row.quantity_available)" />
                <Button
                  v-if="productModel(row)"
                  icon="pi pi-box"
                  rounded
                  severity="secondary"
                  class="!absolute right-2 top-2"
                  aria-label="View 3D model"
                  @click.stop="openProduct3D(row)"
                />
              </div>
              <div class="space-y-2 p-3">
                <div class="min-h-10">
                  <h3 class="line-clamp-2 text-sm font-semibold text-slate-900">{{ row.product?.product_name }}<span v-if="row.variation?.variation_name"> - {{ row.variation.variation_name }}</span></h3>
                  <p class="mt-0.5 truncate text-xs text-slate-500">SKU: {{ productSku(row) }}</p>
                </div>
                <div class="flex min-h-7 items-end justify-between gap-2">
                  <div>
                    <p v-if="hasDiscount(row)" class="text-xs text-slate-400 line-through">{{ money(productBasePrice(row)) }}</p>
                    <p class="font-semibold text-orange-600">{{ money(productSellingPrice(row)) }}</p>
                  </div>
                  <Button
                    :icon="productIsCustomizable(row) ? 'pi pi-palette' : 'pi pi-plus'"
                    rounded
                    :aria-label="productIsCustomizable(row) ? 'Customize and add to cart' : 'Add to cart'"
                    :disabled="!canManagePos || cartQuantity(row.id) >= Number(row.quantity_available || 0)"
                    @click.stop="productIsCustomizable(row) ? openCustomizationDialog(row) : addToCart(row)"
                  />
                </div>
              </div>
            </article>
          </div>
        </template>
      </Card>

      <!-- Cart Section -->
      <Card>
        <template #title>
          <div class="flex items-center gap-2">
            <span>Cart</span>
            <Badge v-if="cart.length" :value="cart.length" class="ml-auto" />
          </div>
        </template>
        <template #content>
          <div v-if="!cart.length" class="text-center py-8 text-gray-500">
            <i class="pi pi-inbox text-4xl mb-2 text-gray-300"></i>
            <p>No items in cart</p>
          </div>
          <div v-else class="space-y-2 max-h-96 overflow-y-auto mb-4">
            <div v-for="item in cart" :key="item.cart_line_key" class="border rounded-lg p-3">
              <div class="flex justify-between items-start mb-2">
                <div>
                  <p class="font-medium">{{ item.product_name }}</p>
                  <p class="text-sm text-gray-600">{{ money(Number(item.unit_price) + Number(item.customization_fee || 0)) }} / unit</p>
                  <p v-if="Number(item.customization_fee || 0) > 0" class="text-xs text-orange-700">Includes {{ money(item.customization_fee) }} customization fee per unit</p>
                  <p class="text-xs text-slate-500">{{ item.stock_available }} in inventory</p>
                </div>
                <Button 
                  text 
                  severity="danger" 
                  icon="pi pi-trash" 
                  :disabled="!canManagePos" 
                  @click="removeCart(item)"
                  size="small"
                />
              </div>
              <div v-if="item.customization_request" class="mt-3 rounded-lg border border-orange-100 bg-orange-50 p-2 text-xs text-slate-700">
                <p class="font-semibold text-orange-800"><i class="pi pi-palette mr-1" />Upholstery customization</p>
                <p class="mt-1 whitespace-pre-line">{{ item.customization_request }}</p>
                <p class="mt-1 text-slate-600">Estimated lead time: {{ item.customization_lead_time_days }} days</p>
              </div>
              <Button v-if="item.is_customizable" type="button" :label="item.customization_request ? 'Edit customization' : 'Add customization'"
                icon="pi pi-pencil" text size="small" class="mt-2" :disabled="!canManagePos"
                @click="openCustomizationDialog(item, item)" />
              <div class="flex w-full items-stretch" role="group" :aria-label="`Quantity for ${item.product_name}`">
                <Button
                  icon="pi pi-minus"
                  severity="secondary"
                  outlined
                  class="shrink-0 rounded-r-none"
                  :disabled="!canManagePos || item.quantity <= 1"
                  :aria-label="`Decrease ${item.product_name} quantity`"
                  @click="changeCartQuantity(item, -1)"
                />
                <InputNumber
                  v-model="item.quantity"
                  :min="1"
                  :max="remainingStockForCartItem(item)"
                  :useGrouping="false"
                  inputClass="w-full text-center rounded-none"
                  class="min-w-0 flex-1 [&_.p-inputnumber-input]:rounded-none"
                  @update:model-value="normalizeCartQuantity(item)"
                />
                <Button
                  icon="pi pi-plus"
                  
                  class="shrink-0 rounded-l-none"
                  :disabled="!canManagePos || item.quantity >= remainingStockForCartItem(item)"
                  :aria-label="`Increase ${item.product_name} quantity`"
                  @click="changeCartQuantity(item, 1)"
                />
              </div>
            </div>
          </div>

          <Divider />

          <!-- Delivery Option -->
          <div class="flex items-center gap-2 mb-3">
            <Checkbox v-model="deliveryRequired" binary inputId="delivery" />
            <label for="delivery" class="text-sm">For Delivery</label>
          </div>

          <!-- Customer Info Button -->
          <Button 
            label="Customer Information" 
            icon="pi pi-user" 
            severity="secondary" 
            outlined 
            @click="customerValidationAttempted = false; customerDialog = true"
            fluid
            class="mb-3"
          />
          
          <div v-if="customerForm.name || customerForm.phone" class="bg-gray-50 rounded-lg p-2 mb-3 text-sm">
            <i class="pi pi-user-circle mr-2"></i>
            {{ customerForm.name || 'Customer' }} · {{ customerForm.phone || 'No phone' }}
          </div>

          <!-- Payment Method -->
          <label for="payment-mode" class="mb-1 block text-sm font-medium text-gray-700">Payment Mode</label>
          <Select 
            inputId="payment-mode"
            v-model="paymentMethod" 
            :options="paymentOptions" 
            optionLabel="label" 
            optionValue="value" 
            fluid
            class="mb-3"
          />

          <!-- Cash tendered -->
          <InputNumber 
            v-if="paymentMethod === 'cash'"
            v-model="amountTendered" 
            fluid 
            mode="currency" 
            currency="PHP" 
            :min="0"
            :invalid="tenderedAmountInvalid || (cashAmountAttempted && amountTendered === null)"
            placeholder="Amount tendered"
            class="mb-3"
          />
          <Message v-if="tenderedAmountInvalid" severity="error" :closable="false" class="-mt-2 mb-3">
            Amount tendered must be at least {{ money(total) }}.
          </Message>
          <Message v-else-if="cashAmountAttempted && paymentMethod === 'cash' && amountTendered === null" severity="error" :closable="false" class="-mt-2 mb-3">
            Enter the amount tendered to complete checkout.
          </Message>

          <!-- Hosted online payment info -->
          <Message v-if="paymentMethod !== 'cash'" class="mb-3">
            <i class="pi pi-info-circle mr-2"></i>
            {{ paymentLabel(paymentMethod) }} checkout opens securely after you submit. Do not release the order until payment is confirmed.
          </Message>

          <!-- Totals -->
          <div class="bg-gray-50 rounded-lg p-3 mb-3">
            <div class="flex justify-between py-1 text-sm">
              <span>Subtotal</span>
              <span class="font-medium">{{ money(subtotal) }}</span>
            </div>
            <div v-if="deliveryRequired" class="flex justify-between py-1 text-sm">
              <span>Shipping fee</span>
              <span class="font-medium">{{ estimatingShipping ? 'Calculating...' : money(shippingFee) }}</span>
            </div>
            <Message v-if="deliveryRequired && shippingEstimateError" severity="error" :closable="false" class="mt-2 text-xs">
              {{ shippingEstimateError }}
            </Message>
            <div class="flex justify-between py-1 text-base font-semibold border-t mt-1 pt-2">
              <span>Total</span>
              <span class="text-blue-600">{{ money(total) }}</span>
            </div>
            <div v-if="paymentMethod === 'cash' && amountTendered" class="flex justify-between py-1 text-sm text-green-600">
              <span>Change</span>
              <span>{{ money(changeAmount) }}</span>
            </div>
          </div>

          <!-- Checkout Button -->
          <Button 
             
            fluid 
            :loading="checkingOut" 
            :disabled="!canManagePos || !cart.length || checkingOut"
            label="Checkout"
            @click="checkout"
          />
        </template>
      </Card>
    </div>

    <Dialog v-model:visible="productDetailVisible" modal header="Product Details" :style="{ width: 'min(900px, 95vw)' }" :breakpoints="{ '640px': '96vw' }">
      <div v-if="selectedProduct" class="grid gap-5 md:grid-cols-2">
        <div class="flex min-h-64 items-center justify-center rounded-xl bg-slate-50 p-4">
          <img v-if="productImage(selectedProduct)" :src="productImage(selectedProduct)" :alt="selectedProduct.product?.product_name || 'Product image'" class="max-h-80 w-full object-contain" />
          <i v-else class="pi pi-image text-5xl text-slate-300" />
        </div>
        <div class="space-y-3">
          <div>
            <h2 class="text-xl font-semibold text-slate-900">{{ selectedProduct.product?.product_name }}<span v-if="selectedProduct.variation?.variation_name"> - {{ selectedProduct.variation.variation_name }}</span></h2>
            <p class="mt-1 text-sm text-slate-500">SKU: {{ productSku(selectedProduct) }}</p>
          </div>
          <div class="flex items-end gap-2">
            <span class="text-xl font-bold text-orange-600">{{ money(productSellingPrice(selectedProduct)) }}</span>
            <span v-if="hasDiscount(selectedProduct)" class="text-sm text-slate-400 line-through">{{ money(productBasePrice(selectedProduct)) }}</span>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <Badge :value="`${Number(selectedProduct.quantity_available || 0)} in stock`" :severity="getStockSeverity(selectedProduct.quantity_available)" />
            <Tag v-if="selectedProduct.variation?.color" :value="selectedProduct.variation.color" />
            <Tag v-if="selectedProduct.variation?.size" :value="selectedProduct.variation.size" />
            <Tag v-if="selectedProduct.variation?.material" :value="selectedProduct.variation.material" />
          </div>
          <dl class="grid grid-cols-2 gap-x-4 gap-y-2 border-y border-slate-100 py-3 text-sm">
            <template v-for="detail in productDetails(selectedProduct)" :key="detail.label">
              <dt class="text-slate-500">{{ detail.label }}</dt><dd class="text-right font-medium text-slate-800">{{ detail.value }}</dd>
            </template>
          </dl>
          <p v-if="plainDescription(selectedProduct.product?.description)" class="whitespace-pre-line text-sm leading-6 text-slate-600">{{ plainDescription(selectedProduct.product?.description) }}</p>
          <div class="flex flex-wrap justify-end gap-2 pt-2">
            <Button v-if="productModel(selectedProduct)" label="View 3D" icon="pi pi-box" severity="secondary" outlined @click="openProduct3D(selectedProduct)" />
            <Button label="Add to Cart" icon="pi pi-plus" :disabled="!canManagePos || cartQuantity(selectedProduct.id) >= Number(selectedProduct.quantity_available || 0)" @click="addToCart(selectedProduct)" />
            <Button v-if="productIsCustomizable(selectedProduct)" label="Customize & Add" icon="pi pi-palette" severity="warn"
              :disabled="!canManagePos || cartQuantity(selectedProduct.id) >= Number(selectedProduct.quantity_available || 0)"
              @click="openCustomizationDialog(selectedProduct)" />
          </div>
        </div>
      </div>
    </Dialog>

    <Dialog v-model:visible="customizationDialogVisible" modal header="Upholstery Customization" :style="{ width: 'min(640px, 95vw)' }" :breakpoints="{ '640px': '96vw' }">
      <div v-if="customizationSource" class="space-y-4">
        <div class="rounded-xl border border-orange-100 bg-orange-50 p-3">
          <p class="font-semibold text-slate-900">{{ customizationSource.product_name || customizationSource.product?.product_name }}</p>
          <p class="mt-1 text-xs text-slate-600">Describe the agreed fabric, color, pattern, or other upholstery details. Confirm the added fee and lead time with the customer before saving.</p>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Customization specifications <span class="text-red-600">*</span></label>
          <Textarea v-model="customizationForm.request" rows="4" auto-resize fluid maxlength="1000"
            placeholder="Example: Replace the seat upholstery with customer-selected charcoal gray fabric." />
          <small class="text-slate-500">{{ customizationForm.request.length }}/1000 characters</small>
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Additional fee per unit</label>
            <InputNumber v-model="customizationForm.fee" mode="currency" currency="PHP" locale="en-PH" :min="0" fluid />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Estimated lead time (days) <span class="text-red-600">*</span></label>
            <InputNumber v-model="customizationForm.leadTimeDays" :min="1" :max="365" :useGrouping="false" suffix=" days" fluid />
          </div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm">
          <div class="flex justify-between gap-3"><span class="text-slate-600">Base price per unit</span><span>{{ money(customizationBasePrice) }}</span></div>
          <div class="mt-1 flex justify-between gap-3"><span class="text-slate-600">Customization fee per unit</span><span>{{ money(customizationFee) }}</span></div>
          <div class="mt-2 flex justify-between gap-3 border-t border-slate-200 pt-2 font-semibold"><span>Agreed price per unit</span><span class="text-orange-700">{{ money(customizationBasePrice + customizationFee) }}</span></div>
          <p v-if="customizationForm.leadTimeDays" class="mt-1 text-right text-xs text-slate-500">Estimated completion in {{ customizationForm.leadTimeDays }} days</p>
        </div>
        <div class="flex items-start gap-2 rounded-lg border border-slate-200 p-3">
          <Checkbox v-model="customerConfirmedCustomization" inputId="customer-confirmed-customization" binary />
          <label for="customer-confirmed-customization" class="cursor-pointer text-sm text-slate-700">The customer confirmed these specifications, the additional fee, and the estimated lead time.</label>
        </div>
        <Message v-if="customizationError" severity="error" :closable="false">{{ customizationError }}</Message>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" text @click="customizationDialogVisible = false" />
        <Button :label="customizationEditing ? 'Save Changes' : 'Add Customized Item'" icon="pi pi-check" severity="warn"
          :disabled="!canManagePos" @click="saveCustomizationToCart" />
      </template>
    </Dialog>

    <Dialog v-model:visible="product3DVisible" modal header="3D Product View" :style="{ width: 'min(800px, 95vw)' }" :breakpoints="{ '640px': '96vw' }" :unmount-on-hide="false">
      <Model3DPreview v-if="selectedModel" :model-url="selectedModel.url" :model-format="selectedModel.model_format" :camera-x="selectedModel.camera_settings?.angle_x" :camera-y="selectedModel.camera_settings?.angle_y" :zoom="selectedModel.camera_settings?.zoom" height="min(65vh, 560px)" allow-bottom-view />
    </Dialog>

    <!-- Customer Dialog -->
    <Dialog 
      v-model:visible="customerDialog" 
      header="Customer Information" 
      modal 
      :style="{ width: '90%', maxWidth: '800px' }"
      @show="initMap"
      @hide="destroyMap"
    >
      <Message v-if="customerValidationAttempted && customerInformationError" severity="error" :closable="false" class="mb-4">
        {{ customerInformationError }}
      </Message>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-medium text-gray-700 block mb-1">Name <span class="text-red-600">*</span></label>
          <InputText v-model="customerForm.name" :invalid="customerValidationAttempted && !customerForm.name.trim()" fluid placeholder="Customer name" />
        </div>
        <div>
          <label class="text-sm font-medium text-gray-700 block mb-1">Email <span class="text-red-600">*</span></label>
          <InputText v-model="customerForm.email" type="email" :invalid="customerValidationAttempted && !isValidCustomerEmail" fluid placeholder="Email address" />
        </div>
        <div>
          <label class="text-sm font-medium text-gray-700 block mb-1">Contact Number <span class="text-red-600">*</span></label>
          <InputMask
            v-model="customerForm.phone"
            mask="0999 999 9999"
            placeholder="09XX XXX XXXX"
            :autoClear="false"
            :invalid="customerValidationAttempted && !isValidCustomerPhone"
            fluid
          />
        </div>
        <div>
          <label class="text-sm font-medium text-gray-700 block mb-1">Province <span class="text-red-600">*</span></label>
          <Select 
            v-model="addressSelection.provinceId" 
            :options="provinceOptions" 
             optionLabel="label" optionValue="value"
            filter 
            placeholder="Select Province" 
            fluid
            :invalid="customerValidationAttempted && !addressSelection.provinceId"
            @change="onProvinceChange"
          />
        </div>
        <div>
          <label class="text-sm font-medium text-gray-700 block mb-1">City / Municipality <span class="text-red-600">*</span></label>
          <Select 
            v-model="addressSelection.cityId" 
            :options="cityOptions" 
             optionLabel="label" optionValue="value"
            filter 
            placeholder="Select City" 
            fluid
            :invalid="customerValidationAttempted && !addressSelection.cityId"
            :disabled="!addressSelection.provinceId" 
            @change="onCityChange"
          />
        </div>
        <div>
          <label class="text-sm font-medium text-gray-700 block mb-1">Barangay <span class="text-red-600">*</span></label>
          <Select 
            v-model="addressSelection.barangayCode" 
            :options="barangayOptions" 
             optionLabel="label" optionValue="value"
            filter 
            placeholder="Select Barangay" 
            fluid
            :invalid="customerValidationAttempted && !addressSelection.barangayCode"
            :disabled="!addressSelection.cityId"
          />
        </div>
        <div class="md:col-span-2">
          <label class="text-sm font-medium text-gray-700 block mb-1">Address Line <span class="text-red-600">*</span></label>
          <Textarea v-model="customerForm.addressLine" rows="2" auto-resize fluid :invalid="customerValidationAttempted && !customerForm.addressLine.trim()" placeholder="Street, building, landmark" />
        </div>
        <div class="md:col-span-2">
          <label class="text-sm font-medium text-gray-700 block mb-1">Pin Location</label>
          <div class="flex gap-2 mb-2">
            <InputText v-model="addressSearchText" placeholder="Search address to pin" fluid />
            <Button text icon="pi pi-search" @click="geocodeCustomerAddress" />
          </div>
          <div ref="mapEl" class="h-64 w-full rounded-lg border border-gray-200"></div>
          <div class="text-xs text-gray-500 mt-2">
            Lat: {{ customerForm.latitude?.toFixed(6) || '-' }} · Lng: {{ customerForm.longitude?.toFixed(6) || '-' }}
          </div>
        </div>
        <div class="md:col-span-2">
          <label class="text-sm font-medium text-gray-700 block mb-1">Delivery Notes</label>
          <Textarea v-model="customerForm.deliveryNotes" rows="2" auto-resize fluid placeholder="Gate, landmark, instructions (optional)" />
        </div>
      </div>
      <template #footer>
        <Button label="Close" severity="secondary" text @click="customerDialog = false" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import salesService from '@/services/sales.service'
import ecommerceService from '@/services/ecommerce.service'
import paymongoService from '@/services/paymongo.service'
import Card from 'primevue/card'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputMask from 'primevue/inputmask'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import Divider from 'primevue/divider'
import Checkbox from 'primevue/checkbox'
import Textarea from 'primevue/textarea'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'
import Badge from 'primevue/badge'
import Message from 'primevue/message'
import ConfirmDialog from 'primevue/confirmdialog'
import Model3DPreview from '@/Components/merchandising/Model3DPreview.vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { useAuthStore } from '@/stores/auth'
import { onBeforeUnmount } from 'vue'
import type { Map as MapboxMap, Marker as MapboxMarker } from 'mapbox-gl'
import 'mapbox-gl/dist/mapbox-gl.css'
import { forwardGeocodeMapbox, requireMapboxToken } from '@/utils/mapbox'

const toast = useToast()
const confirm = useConfirm()
const router = useRouter()
const authStore = useAuthStore()
const search = ref('')
const products = ref<any[]>([])
const productLoadError = ref('')
const productDetailVisible = ref(false)
const product3DVisible = ref(false)
const selectedProduct = ref<any | null>(null)
const selectedModel = ref<any | null>(null)
const customizationDialogVisible = ref(false)
const customizationSource = ref<any | null>(null)
const customizationEditing = ref(false)
const customizationError = ref('')
const customerConfirmedCustomization = ref(false)
const customizationForm = ref({ request: '', fee: 0 as number | null, leadTimeDays: null as number | null })
const loadingProducts = ref(false)
const checkingOut = ref(false)
const cart = ref<any[]>([])
const paymentMethod = ref<'cash' | 'card' | 'gcash' | 'paymaya'>('cash')
const amountTendered = ref<number | null>(null)
const deliveryRequired = ref(false)
const shippingFee = ref(0)
const estimatingShipping = ref(false)
const shippingEstimateError = ref('')
const customerDialog = ref(false)
const customerValidationAttempted = ref(false)
const cashAmountAttempted = ref(false)
const customerForm = ref({
  name: '',
  email: '',
  phone: '',
  addressLine: '',
  latitude: null as number | null,
  longitude: null as number | null,
  deliveryNotes: '',
})
const addressSearchText = ref('')
const addressSelection = ref({
  provinceId: null as string | null,
  cityId: null as string | null,
  barangayCode: null as string | null,
})
const provinces = ref<any[]>([])
const cities = ref<any[]>([])
const barangays = ref<any[]>([])
const provinceOptions = computed(() => provinces.value.map((p: any) => ({ label: p.name, value: p.province_id })))
const cityOptions = computed(() => cities.value.map((c: any) => ({ label: c.name, value: c.city_id })))
const barangayOptions = computed(() => barangays.value.map((b: any) => ({ label: b.name, value: b.code })))
const isValidCustomerEmail = computed(() => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(customerForm.value.email.trim()))
const isValidCustomerPhone = computed(() => /^09\d{9}$/.test(customerForm.value.phone.replace(/\D/g, '')))
const customerInformationError = computed(() => {
  const missing = [
    !customerForm.value.name.trim() && 'name',
    !customerForm.value.email.trim() && 'email',
    !customerForm.value.phone.trim() && 'contact number',
    !addressSelection.value.provinceId && 'province',
    !addressSelection.value.cityId && 'city',
    !addressSelection.value.barangayCode && 'barangay',
    !customerForm.value.addressLine.trim() && 'address line',
  ].filter(Boolean)
  if (missing.length) return `Complete the following before checkout: ${missing.join(', ')}.`
  if (!isValidCustomerEmail.value) return 'Enter a valid customer email address.'
  if (!isValidCustomerPhone.value) return 'Enter a valid 11-digit customer contact number.'
  return ''
})

const mapEl = ref<HTMLDivElement | null>(null)
let map: MapboxMap | null = null
let marker: MapboxMarker | null = null
let mapboxgl: typeof import('mapbox-gl').default | null = null
const paymentOptions = ref([{ label: 'Cash', value: 'cash' }])
const canManagePos = authStore.hasPermission('sales.pos.manage')
const customizationFee = computed(() => Math.max(0, Number(customizationForm.value.fee || 0)))
const customizationBasePrice = computed(() => {
  const source = customizationSource.value
  if (!source) return 0
  return Number(source.unit_price ?? productSellingPrice(source))
})

const getStockSeverity = (stock: number) => {
  if (stock <= 0) return 'danger'
  if (stock < 10) return 'warning'
  return 'success'
}

const productBasePrice = (row: any) => Number(row.variation?.base_price ?? row.product?.base_price ?? 0)
const productSellingPrice = (row: any) => Number(row.variation?.discounted_price ?? row.product?.discounted_price ?? productBasePrice(row))
const hasDiscount = (row: any) => productSellingPrice(row) > 0 && productSellingPrice(row) < productBasePrice(row)
const productSku = (row: any) => row.variation?.variation_sku || row.product?.sku || '—'
const productIsCustomizable = (row: any) => Boolean(row?.is_customizable ?? row?.product?.is_customizable)
const productImage = (row: any) => {
  const images = (row.product?.assets || []).filter((item: any) => ['Image_Main', 'Image_Gallery'].includes(item.asset_type))
  const asset = row.variation?.custom_image || images.find((item: any) => item.is_primary || item.asset_type === 'Image_Main') || images[0]
  return asset?.url || asset?.thumbnail_url || null
}
const productModel = (row: any) => row.variation?.custom3dModel || row.product?.assets?.find((item: any) => item.asset_type === '3D_Model') || null
const productDetails = (row: any) => {
  const product = row.product || {}
  const variation = row.variation || {}
  const dimensions = [variation.length_cm ?? product.length_cm, variation.width_cm ?? product.width_cm, variation.height_cm ?? product.height_cm]
  return [
    { label: 'Brand', value: product.brand },
    { label: 'Unit', value: variation.unit_of_measurement || product.unit_of_measurement },
    { label: 'Dimensions', value: dimensions.every((value: any) => value !== null && value !== undefined) ? `${dimensions.join(' × ')} cm` : null },
    { label: 'Weight', value: variation.weight_kg ?? product.weight_kg ? `${variation.weight_kg ?? product.weight_kg} kg` : null },
  ].filter((detail) => detail.value)
}
const plainDescription = (value: any) => String(value || '').replace(/<[^>]*>/g, ' ').replace(/&nbsp;/g, ' ').replace(/\s+/g, ' ').trim()
const openProductDetails = (row: any) => {
  selectedProduct.value = row
  productDetailVisible.value = true
}
const openProduct3D = (row: any) => {
  selectedProduct.value = row
  selectedModel.value = productModel(row)
  product3DVisible.value = true
}

const openCustomizationDialog = (row: any, cartItem: any | null = null) => {
  const existing = cartItem || null
  customizationSource.value = existing || row
  customizationEditing.value = Boolean(existing)
  customizationError.value = ''
  customerConfirmedCustomization.value = false
  customizationForm.value = {
    request: existing?.customization_request || '',
    fee: Number(existing?.customization_fee || 0),
    leadTimeDays: existing?.customization_lead_time_days ? Number(existing.customization_lead_time_days) : null,
  }
  productDetailVisible.value = false
  customizationDialogVisible.value = true
}

const loadProducts = async () => {
  loadingProducts.value = true
  productLoadError.value = ''
  try {
    const res = await salesService.getPosProducts({ per_page: 40, search: search.value || undefined })
    products.value = res?.data?.data || []
  } catch (error: any) {
    products.value = []
    productLoadError.value = error?.response?.data?.message || 'Unable to load POS products. Please try again.'
  } finally { loadingProducts.value = false }
}

const updateCartTotal = () => {
  cart.value = [...cart.value]
}

const remainingStockForCartItem = (item: any) => {
  const reservedByOtherLines = cart.value
    .filter((line) => line.cart_line_key !== item.cart_line_key && Number(line.branch_inventory_id) === Number(item.branch_inventory_id))
    .reduce((total, line) => total + Number(line.quantity || 0), 0)
  return Math.max(0, Number(item.stock_available || 0) - reservedByOtherLines)
}

const normalizeCartQuantity = (item: any) => {
  item.quantity = Math.max(1, Math.min(remainingStockForCartItem(item), Number(item.quantity || 1)))
  updateCartTotal()
}

const changeCartQuantity = (item: any, amount: number) => {
  item.quantity = Number(item.quantity || 1) + amount
  normalizeCartQuantity(item)
}

const cartQuantity = (inventoryId: number) => cart.value
  .filter((item) => Number(item.branch_inventory_id) === Number(inventoryId))
  .reduce((total, item) => total + Number(item.quantity || 0), 0)

const addToCart = (row: any) => {
  const id = row.id
  const existing = cart.value.find((i) => Number(i.branch_inventory_id) === Number(id) && !i.customization_request)
  if (existing) { 
    if (cartQuantity(id) >= Number(row.quantity_available || 0)) return
    existing.quantity += 1
    updateCartTotal()
    productDetailVisible.value = false
    return 
  }
  cart.value.push({
    cart_line_key: `pos-${id}-standard-${Date.now()}-${Math.random().toString(36).slice(2, 7)}`,
    branch_inventory_id: id,
    product_id: row.product_id,
    product_name: [row.product?.product_name, row.variation?.variation_name].filter(Boolean).join(' - ') || 'Product',
    unit_price: productSellingPrice(row),
    quantity: 1,
    stock_available: Number(row.quantity_available || 0),
    is_customizable: productIsCustomizable(row),
  })
  updateCartTotal()
  productDetailVisible.value = false
}

const saveCustomizationToCart = () => {
  const source = customizationSource.value
  const request = customizationForm.value.request.trim()
  const leadTimeDays = Number(customizationForm.value.leadTimeDays || 0)
  const fee = customizationFee.value
  if (!request) {
    customizationError.value = 'Enter the agreed upholstery specifications.'
    return
  }
  if (!Number.isInteger(leadTimeDays) || leadTimeDays < 1 || leadTimeDays > 365) {
    customizationError.value = 'Enter an estimated lead time between 1 and 365 days.'
    return
  }
  if (!customerConfirmedCustomization.value) {
    customizationError.value = 'Confirm that the customer agreed to the specifications, fee, and lead time.'
    return
  }

  const inventoryId = Number(source?.branch_inventory_id ?? source?.id)
  const existingLine = customizationEditing.value
    ? cart.value.find((line) => line.cart_line_key === source.cart_line_key)
    : null
  const values = {
    customization_request: request,
    customization_fee: fee,
    customization_lead_time_days: leadTimeDays,
    is_customizable: true,
  }

  if (existingLine) {
    Object.assign(existingLine, values)
  } else {
    cart.value.push({
      cart_line_key: `pos-${inventoryId}-custom-${Date.now()}-${Math.random().toString(36).slice(2, 7)}`,
      branch_inventory_id: inventoryId,
      product_id: source?.product_id,
      product_name: source?.product_name || [source?.product?.product_name, source?.variation?.variation_name].filter(Boolean).join(' - ') || 'Product',
      unit_price: Number(source?.unit_price ?? productSellingPrice(source)),
      quantity: 1,
      stock_available: Number(source?.stock_available ?? source?.quantity_available ?? 0),
      ...values,
    })
  }

  updateCartTotal()
  customizationDialogVisible.value = false
}

const removeCart = (item: any) => {
  if (!item) return
  const lineKey = item.cart_line_key
  if (!lineKey) return

  confirm.require({
    header: 'Remove Item',
    message: `Remove ${item.product_name || 'this item'} from the cart?`,
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Remove',
    rejectLabel: 'Cancel',
    acceptClass: 'p-button-danger',
    accept: () => {
      cart.value = cart.value.filter((i) => i.cart_line_key !== lineKey)
      updateCartTotal()
    },
  })
}

const subtotal = computed(() => cart.value.reduce((sum, item) =>
  sum + ((Number(item.unit_price || 0) + Number(item.customization_fee || 0)) * Number(item.quantity || 0)), 0))
const total = computed(() => subtotal.value + (deliveryRequired.value ? shippingFee.value : 0))
const tenderedAmountInvalid = computed(() =>
  paymentMethod.value === 'cash'
  && amountTendered.value !== null
  && Number(amountTendered.value) < total.value
)
const changeAmount = computed(() => Math.max(0, Number(amountTendered.value || 0) - total.value))

let shippingEstimateRequest = 0
const updateShippingFee = async () => {
  const requestId = ++shippingEstimateRequest
  if (!deliveryRequired.value || !cart.value.length) {
    shippingFee.value = 0
    shippingEstimateError.value = ''
    return
  }

  estimatingShipping.value = true
  shippingEstimateError.value = ''
  try {
    const response = await salesService.estimatePosShipping({
      subtotal: subtotal.value,
      delivery_latitude: customerForm.value.latitude,
      delivery_longitude: customerForm.value.longitude,
    })
    if (requestId === shippingEstimateRequest) {
      shippingFee.value = Number(response?.data?.shipping_fee || 0)
    }
  } catch (error: any) {
    if (requestId === shippingEstimateRequest) {
      shippingFee.value = 0
      shippingEstimateError.value = error?.response?.data?.message || 'Unable to calculate the shipping fee. Please check the delivery location.'
    }
  } finally {
    if (requestId === shippingEstimateRequest) estimatingShipping.value = false
  }
}

const checkout = async () => {
  if (!cart.value.length) {
    toast.add({ severity: 'warn', summary: 'Empty Cart', detail: 'Please add items to cart first.', life: 2500 })
    return
  }

  if (customerInformationError.value) {
    customerValidationAttempted.value = true
    customerDialog.value = true
    return
  }

  cashAmountAttempted.value = true
  if (paymentMethod.value === 'cash' && (amountTendered.value === null || Number(amountTendered.value) < total.value)) {
    return
  }
  
  checkingOut.value = true
  let createdOrderId = 0
  try {
    const provinceLabel = provinceOptions.value.find(p => p.value === addressSelection.value.provinceId)?.label
    const cityLabel = cityOptions.value.find(c => c.value === addressSelection.value.cityId)?.label
    const barangayLabel = barangayOptions.value.find(b => b.value === addressSelection.value.barangayCode)?.label
    const deliveryAddress = deliveryRequired.value
      ? `${provinceLabel || ''}${provinceLabel ? ', ' : ''}${cityLabel || ''}${cityLabel ? ', ' : ''}${barangayLabel || ''}${barangayLabel ? ', ' : ''}${customerForm.value.addressLine}`.trim()
      : undefined

    const response = await salesService.checkout({
      customer_name: customerForm.value.name || undefined,
      customer_phone: customerForm.value.phone || undefined,
      payment_method: paymentMethod.value,
      amount_tendered: amountTendered.value || 0,
      return_url: window.location.href,
      delivery_required: deliveryRequired.value,
      delivery_address: deliveryAddress,
      delivery_notes: deliveryRequired.value ? customerForm.value.deliveryNotes : undefined,
      delivery_province: deliveryRequired.value ? provinceLabel : undefined,
      delivery_city: deliveryRequired.value ? cityLabel : undefined,
      delivery_barangay: deliveryRequired.value ? barangayLabel : undefined,
      delivery_address_line: deliveryRequired.value ? customerForm.value.addressLine : undefined,
      delivery_latitude: deliveryRequired.value ? customerForm.value.latitude : undefined,
      delivery_longitude: deliveryRequired.value ? customerForm.value.longitude : undefined,
      delivery_email: customerForm.value.email,
      items: cart.value.map((item) => ({
        branch_inventory_id: item.branch_inventory_id,
        quantity: item.quantity,
        customization_request: item.customization_request || null,
        customization_fee: Number(item.customization_fee || 0),
        customization_lead_time_days: item.customization_lead_time_days || null,
      })),
    })
    const orderId = Number(response?.data?.id || 0)
    createdOrderId = orderId
    if (paymentMethod.value !== 'cash') {
      const storeId = Number(response?.data?.store_id || authStore.user?.store_id || 0)
      if (!orderId || !storeId) throw new Error('The order was created, but its payment session could not be initialized. Open the order and retry payment.')
      const successUrl = new URL(router.resolve({ name: 'sales.pos.order-detail', params: { id: orderId }, query: { paymongo_success: '1' } }).href, window.location.origin).toString()
      const cancelUrl = new URL(router.resolve({ name: 'sales.pos.order-detail', params: { id: orderId }, query: { paymongo_cancel: '1' } }).href, window.location.origin).toString()
      const sessionResponse = await paymongoService.createCheckoutSession({
        amount: Math.max(1, Math.round(Number(response?.data?.total_amount || total.value) * 100)),
        currency: 'PHP',
        description: `POS Order ${response?.data?.order_number || orderId}`,
        store_id: storeId,
        payable_type: 'sales_order',
        payable_id: orderId,
        payment_method_allowed: [paymentMethod.value],
        metadata: { order_id: orderId, source: 'sales_pos' },
        success_url: successUrl,
        cancel_url: cancelUrl,
      })
      const checkoutUrl = String(sessionResponse?.data?.checkout_url || '')
      if (!checkoutUrl.startsWith('https://checkout.paymongo.com/')) throw new Error('The order was created, but the secure payment page was unavailable. Open the order and retry payment.')
      window.location.assign(checkoutUrl)
      return
    }

    toast.add({ severity: 'success', summary: 'Success', detail: 'POS checkout completed.', life: 2500 })

    cart.value = []
    amountTendered.value = null
    cashAmountAttempted.value = false
    customerValidationAttempted.value = false
    deliveryRequired.value = false
    customerForm.value = { name: '', email: '', phone: '', addressLine: '', latitude: null, longitude: null, deliveryNotes: '' }
    addressSelection.value = { provinceId: null, cityId: null, barangayCode: null }
    if (orderId) {
      await router.push({ name: 'sales.pos.order-detail', params: { id: orderId } })
    } else {
      loadProducts()
    }
  } catch (error: any) {
    if (createdOrderId) {
      toast.add({ severity: 'warn', summary: 'Order Created', detail: 'The order was created, but payment checkout could not open. Continue payment from the order details.', life: 5000 })
      await router.push({ name: 'sales.pos.order-detail', params: { id: createdOrderId } })
    } else {
      toast.add({ severity: 'error', summary: 'Checkout failed', detail: error?.response?.data?.message || 'Failed checkout.', life: 3000 })
    }
  } finally { checkingOut.value = false }
}

const paymentLabel = (value: string) => ({ cash: 'Cash', card: 'Credit Card', gcash: 'GCash', paymaya: 'Maya' }[value] || 'Online payment')

const money = (v: number | string) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(v || 0))

watch(search, () => loadProducts())
watch([deliveryRequired, subtotal, () => customerForm.value.latitude, () => customerForm.value.longitude], updateShippingFee)
onMounted(async () => {
  loadProducts()
  try {
    const storeId = Number(authStore.user?.store_id || 0)
    if (storeId) {
      const response = await paymongoService.getAvailableCheckoutMethods(storeId)
      const available = new Set<string>(response?.data?.methods || [])
      paymentOptions.value = [
        { label: 'Cash', value: 'cash' },
        ...[
          { label: 'Credit Card', value: 'card' },
          { label: 'GCash', value: 'gcash' },
          { label: 'Maya', value: 'paymaya' },
        ].filter(method => available.has(method.value)),
      ]
    }
  } catch {
    // Keep cash available if hosted payment methods cannot be loaded.
  }
  await fetchProvinces()
})

async function initMap() {
  if (!mapEl.value) return
  if (map) {
    map.resize()
    return
  }

  const startLat = customerForm.value.latitude ?? 14.5995
  const startLng = customerForm.value.longitude ?? 120.9842
  try {
    mapboxgl = (await import('mapbox-gl')).default
    mapboxgl.accessToken = requireMapboxToken()
    const currentMap = new mapboxgl.Map({ container: mapEl.value, style: 'mapbox://styles/mapbox/streets-v12', center: [startLng, startLat], zoom: 12 })
    map = currentMap
    await new Promise<void>((resolve) => currentMap.once('load', () => resolve()))
    if (map !== currentMap || !customerDialog.value) return
    marker = new mapboxgl.Marker({ draggable: true, color: '#f97316' }).setLngLat([startLng, startLat]).addTo(map)
    marker.on('dragend', () => {
      const position = marker?.getLngLat()
      if (!position) return
      customerForm.value.latitude = position.lat
      customerForm.value.longitude = position.lng
    })
    map.on('click', (event) => {
      marker?.setLngLat(event.lngLat)
      customerForm.value.latitude = event.lngLat.lat
      customerForm.value.longitude = event.lngLat.lng
    })
    map.resize()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Mapbox Unavailable', detail: error?.message || 'Unable to load the location map.', life: 3500 })
  }
}

const geocodeAddressText = async (addressText: string): Promise<{ latitude: number; longitude: number } | null> => {
  const query = String(addressText || '').trim()
  if (!query) return null

  try {
    const result = await forwardGeocodeMapbox(query)
    return result ? { latitude: result.latitude, longitude: result.longitude } : null
  } catch {
    return null
  }
}

async function geocodeCustomerAddress() {
  const built = `${customerForm.value.addressLine || ''}, ${barangayOptions.value.find(b => b.value === addressSelection.value.barangayCode)?.label || ''}, ${cityOptions.value.find(c => c.value === addressSelection.value.cityId)?.label || ''}, ${provinceOptions.value.find(p => p.value === addressSelection.value.provinceId)?.label || ''}, Philippines ${addressSearchText.value ? ` ${addressSearchText.value}` : ''}`
  const geocoded = await geocodeAddressText(built)
  if (!geocoded) {
    toast.add({ severity: 'warn', summary: 'Not Found', detail: 'Unable to find location for that address.' })
    return
  }
  customerForm.value.latitude = geocoded.latitude
  customerForm.value.longitude = geocoded.longitude
  if (marker) marker.setLngLat([geocoded.longitude, geocoded.latitude])
  if (map) map.flyTo({ center: [geocoded.longitude, geocoded.latitude], zoom: 14 })
}

async function fetchProvinces() {
  try {
    const response = await ecommerceService.getProvinces()
    provinces.value = response?.data || []
  } catch (error) {
    provinces.value = []
  }
}

async function fetchCities(provinceId: string) {
  try {
    const response = await ecommerceService.getCities(provinceId)
    cities.value = response?.data || []
  } catch (error) {
    cities.value = []
  }
}

async function fetchBarangays(cityId: string) {
  try {
    const response = await ecommerceService.getBarangays(cityId)
    barangays.value = response?.data || []
  } catch (error) {
    barangays.value = []
  }
}

async function onProvinceChange() {
  addressSelection.value.cityId = null
  addressSelection.value.barangayCode = null
  barangays.value = []
  if (addressSelection.value.provinceId) {
    await fetchCities(addressSelection.value.provinceId)
  }
}

async function onCityChange() {
  addressSelection.value.barangayCode = null
  if (addressSelection.value.cityId) {
    await fetchBarangays(addressSelection.value.cityId)
  }
}

const destroyMap = () => {
  if (map) {
    map.remove()
    map = null
    marker = null
  }
}

onBeforeUnmount(destroyMap)
</script>

<style scoped>
.pos-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 20px;
}

@media (max-width: 768px) {
  .pos-container {
    padding: 16px;
  }
}

.mb-4 {
  margin-bottom: 20px;
}
</style>
