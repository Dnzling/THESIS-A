<template>
  <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
     
        <h1 class="text-2xl font-semibold text-slate-900">Delivery Settings</h1>
        <p class="mt-1 text-sm text-slate-500">Configure the store's delivery charges, coverage, and operating rules.</p>
      </div>
      <Button icon="pi pi-refresh" text :loading="loading" @click="loadSettings" />
    </div>

    <Message v-if="loadError" severity="error" :closable="false">
      {{ loadError }}
    </Message>

    <template v-if="loading && !loaded">
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <Skeleton height="24rem" class="lg:col-span-2" />
        <Skeleton height="24rem" />
      </div>
    </template>

    <div v-else class="grid grid-cols-1 items-start gap-6 lg:grid-cols-3">
      <div class="space-y-6 lg:col-span-2">
        <Card class="overflow-hidden rounded-2xl border border-slate-200 shadow-sm">
          <template #content>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
              <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                  <i class="pi pi-calculator text-lg" />
                </div>
                <div>
                  <h2 class="font-semibold text-slate-900">Delivery fee calculation</h2>
                  <p class="mt-1 text-sm text-slate-500">Apply these fees to ecommerce deliveries from this store.</p>
                </div>
              </div>
              <div class="flex items-center gap-3 rounded-xl bg-slate-50 px-4 py-3">
                <span class="text-sm font-medium text-slate-700">Pricing enabled</span>
                <InputSwitch v-model="form.is_active" input-id="delivery-pricing-active" />
              </div>
            </div>
          </template>
        </Card>

        <Card class="rounded-2xl border border-slate-200 shadow-sm">
          <template #title>
            <div>
              <h2 class="text-base font-semibold text-slate-900">Pricing components</h2>
              <p class="mt-1 text-sm font-normal text-slate-500">Shipping fee = base fee + distance fee + weight fee + applicable surcharges.</p>
            </div>
          </template>
          <template #content>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
              <div class="field-group">
                <label for="base-fee">Base fee <span class="text-red-500">*</span></label>
                <InputNumber id="base-fee" v-model="form.base_fee" fluid mode="currency" currency="PHP" locale="en-PH" :min="0" />
                <small>Fixed amount charged for every delivery.</small>
              </div>
              <div class="field-group">
                <label for="minimum-fee">Minimum delivery fee <span class="text-red-500">*</span></label>
                <InputNumber id="minimum-fee" v-model="form.min_delivery_fee" fluid mode="currency" currency="PHP" locale="en-PH" :min="0" />
                <small>The lowest fee the customer may be charged.</small>
              </div>
              <div class="field-group">
                <label for="per-km-fee">Rate per kilometer <span class="text-red-500">*</span></label>
                <InputNumber id="per-km-fee" v-model="form.per_km_fee" fluid mode="currency" currency="PHP" locale="en-PH" :min="0" />
                <small>Multiplied by the delivery distance.</small>
              </div>
              <div class="field-group">
                <label for="per-kg-fee">Rate per kilogram <span class="text-red-500">*</span></label>
                <InputNumber id="per-kg-fee" v-model="form.per_kg_fee" fluid mode="currency" currency="PHP" locale="en-PH" :min="0" />
                <small>Multiplied by the total recorded product weight.</small>
              </div>
            </div>
          </template>
        </Card>

        <Card class="rounded-2xl border border-slate-200 shadow-sm">
          <template #title>
            <div>
              <h2 class="text-base font-semibold text-slate-900">Coverage and incentives</h2>
              <p class="mt-1 text-sm font-normal text-slate-500">Set the service limit and optional customer incentives.</p>
            </div>
          </template>
          <template #content>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
              <div class="field-group">
                <label for="max-distance">Maximum delivery distance</label>
                <InputNumber id="max-distance" v-model="form.max_delivery_distance_km" fluid suffix=" km" :min="0" :max-fraction-digits="2" />
                <small>Leave empty for no distance limit.</small>
              </div>
              <div class="field-group">
                <label for="free-shipping">Free shipping minimum order</label>
                <InputNumber id="free-shipping" v-model="form.free_shipping_min_order" fluid mode="currency" currency="PHP" locale="en-PH" :min="0" />
                <small>Leave empty to disable free shipping.</small>
              </div>
              <div class="field-group sm:col-span-2">
                <label for="bulk-discount">Grouped trip discount</label>
                <InputNumber id="bulk-discount" v-model="form.bulk_discount_rate" fluid suffix="%" :min="5" :max="25" :max-fraction-digits="2" />
                <small>Discount applied when eligible orders share one delivery trip.</small>
              </div>
            </div>
          </template>
        </Card>

        <Card class="rounded-2xl border border-slate-200 shadow-sm">
          <template #title>
            <div>
              <h2 class="text-base font-semibold text-slate-900">Surcharges and notes</h2>
              <p class="mt-1 text-sm font-normal text-slate-500">Optional charges for special delivery conditions.</p>
            </div>
          </template>
          <template #content>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
              <div class="field-group">
                <label for="bulky-surcharge">Bulky item surcharge</label>
                <InputNumber id="bulky-surcharge" v-model="form.bulky_item_surcharge" fluid mode="currency" currency="PHP" locale="en-PH" :min="0" />
              </div>
              <div class="field-group">
                <label for="remote-surcharge">Remote area surcharge</label>
                <InputNumber id="remote-surcharge" v-model="form.remote_area_surcharge" fluid mode="currency" currency="PHP" locale="en-PH" :min="0" />
              </div>
              <div class="field-group sm:col-span-2">
                <label for="delivery-notes">Internal policy notes</label>
                <Textarea id="delivery-notes" v-model="form.notes" rows="4" fluid maxlength="2000" placeholder="Add instructions or policy notes for the logistics team..." />
              </div>
            </div>
          </template>
        </Card>

        <div class="flex justify-end border-t border-slate-200 pt-5">
          <Button label="Save Delivery Settings" icon="pi pi-check" :loading="saving" @click="saveSettings" />
        </div>
      </div>

      <Card class="rounded-2xl border border-slate-200 shadow-sm lg:sticky lg:top-6">
        <template #title>
          <div>
            <h2 class="text-base font-semibold text-slate-900">Fee estimator</h2>
            <p class="mt-1 text-sm font-normal text-slate-500">Test the currently saved pricing rules.</p>
          </div>
        </template>
        <template #content>
          <div class="space-y-4">
            <div class="field-group">
              <label for="estimate-subtotal">Order subtotal</label>
              <InputNumber id="estimate-subtotal" v-model="estimateForm.subtotal" fluid mode="currency" currency="PHP" locale="en-PH" :min="0" />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div class="field-group">
                <label for="estimate-distance">Distance</label>
                <InputNumber id="estimate-distance" v-model="estimateForm.distance_km" fluid suffix=" km" :min="0" :max-fraction-digits="2" />
              </div>
              <div class="field-group">
                <label for="estimate-weight">Weight</label>
                <InputNumber id="estimate-weight" v-model="estimateForm.total_weight_kg" fluid suffix=" kg" :min="0" :max-fraction-digits="2" />
              </div>
            </div>
            <div class="space-y-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
              <div class="flex items-center justify-between gap-3">
                <label for="estimate-bulky" class="text-sm text-slate-700">Bulky items</label>
                <InputSwitch id="estimate-bulky" v-model="estimateForm.has_bulky_items" />
              </div>
              <div class="flex items-center justify-between gap-3">
                <label for="estimate-remote" class="text-sm text-slate-700">Remote area</label>
                <InputSwitch id="estimate-remote" v-model="estimateForm.is_remote_area" />
              </div>
            </div>

            <Button label="Calculate Fee" icon="pi pi-calculator"  outlined fluid :loading="estimating" @click="estimateFee" />

            <div class="rounded-2xl bg-slate-900 p-5 text-white">
              <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Estimated shipping fee</p>
              <p class="mt-1 text-3xl font-semibold">{{ money(estimateResult.shipping_fee) }}</p>
              <div v-if="estimateResult.breakdown" class="mt-5 space-y-2 border-t border-slate-700 pt-4 text-sm">
                <div class="fee-row"><span>Base fee</span><span>{{ money(estimateResult.breakdown.base_fee) }}</span></div>
                <div class="fee-row"><span>Distance fee</span><span>{{ money(estimateResult.breakdown.distance_fee) }}</span></div>
                <div class="fee-row"><span>Weight fee</span><span>{{ money(estimateResult.breakdown.weight_fee) }}</span></div>
                <div v-if="estimateResult.breakdown.bulky_item_surcharge" class="fee-row"><span>Bulky surcharge</span><span>{{ money(estimateResult.breakdown.bulky_item_surcharge) }}</span></div>
                <div v-if="estimateResult.breakdown.remote_area_surcharge" class="fee-row"><span>Remote surcharge</span><span>{{ money(estimateResult.breakdown.remote_area_surcharge) }}</span></div>
                <div v-if="estimateResult.breakdown.free_shipping_applied" class="mt-3 rounded-lg bg-emerald-500/15 px-3 py-2 text-emerald-300">Free shipping threshold applied.</div>
                <div v-else-if="estimateResult.breakdown.minimum_applied" class="mt-3 rounded-lg bg-amber-500/15 px-3 py-2 text-amber-200">Minimum delivery fee applied.</div>
              </div>
            </div>

            <Message v-if="estimateResult.delivery_available === false" severity="warn" :closable="false">
              This destination exceeds the configured {{ estimateResult.max_delivery_distance_km }} km delivery limit.
            </Message>

            <Message severity="secondary" :closable="false">
              Product weight must be recorded for weight-based fees to calculate correctly.
            </Message>
          </div>
        </template>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import logisticsService from '@/services/logistics.service'
import Button from 'primevue/button'
import Card from 'primevue/card'
import InputNumber from 'primevue/inputnumber'
import InputSwitch from 'primevue/inputswitch'
import Message from 'primevue/message'
import Skeleton from 'primevue/skeleton'
import Textarea from 'primevue/textarea'

interface DeliverySettingsForm {
  is_active: boolean
  base_fee: number
  per_km_fee: number
  per_kg_fee: number
  min_delivery_fee: number
  free_shipping_min_order: number | null
  bulky_item_surcharge: number
  remote_area_surcharge: number
  max_delivery_distance_km: number | null
  bulk_discount_rate: number | null
  notes: string
}

const toast = useToast()
const loading = ref(false)
const loaded = ref(false)
const saving = ref(false)
const estimating = ref(false)
const loadError = ref('')

const form = reactive<DeliverySettingsForm>({
  is_active: true,
  base_fee: 100,
  per_km_fee: 10,
  per_kg_fee: 0,
  min_delivery_fee: 80,
  free_shipping_min_order: null,
  bulky_item_surcharge: 0,
  remote_area_surcharge: 0,
  max_delivery_distance_km: null,
  bulk_discount_rate: 10,
  notes: '',
})

const estimateForm = reactive({
  subtotal: 0,
  distance_km: 0,
  total_weight_kg: 0,
  has_bulky_items: false,
  is_remote_area: false,
})

const estimateResult = reactive<any>({ shipping_fee: 0, breakdown: null })

const loadSettings = async () => {
  loading.value = true
  loadError.value = ''
  try {
    const response = await logisticsService.getDeliverySettings()
    Object.assign(form, response?.data || {})
    loaded.value = true
  } catch (error: any) {
    loadError.value = error?.response?.data?.message || 'Delivery settings could not be loaded.'
  } finally {
    loading.value = false
  }
}

const saveSettings = async () => {
  saving.value = true
  try {
    const response = await logisticsService.updateDeliverySettings({ ...form })
    Object.assign(form, response?.data || {})
    toast.add({ severity: 'success', summary: 'Settings saved', detail: 'Delivery pricing and limits were updated.', life: 2500 })
  } catch (error: any) {
    const validationErrors = error?.response?.data?.errors
    const firstError = validationErrors ? Object.values(validationErrors).flat()[0] : null
    toast.add({ severity: 'error', summary: 'Unable to save', detail: String(firstError || error?.response?.data?.message || 'Please review the settings and try again.'), life: 3500 })
  } finally {
    saving.value = false
  }
}

const estimateFee = async () => {
  estimating.value = true
  try {
    const response = await logisticsService.estimateDeliveryFee(estimateForm)
    Object.assign(estimateResult, response?.data || {})
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Unable to estimate', detail: error?.response?.data?.message || 'Please check the estimate values.', life: 3000 })
  } finally {
    estimating.value = false
  }
}

const money = (value: number | string | null | undefined) => new Intl.NumberFormat('en-PH', {
  style: 'currency',
  currency: 'PHP',
  minimumFractionDigits: 2,
}).format(Number(value || 0))

onMounted(loadSettings)
</script>

<style scoped>
.field-group {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
}

.field-group label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #334155;
}

.field-group small {
  color: #64748b;
  font-size: 0.75rem;
  line-height: 1.35;
}

.fee-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  color: #cbd5e1;
}
</style>
