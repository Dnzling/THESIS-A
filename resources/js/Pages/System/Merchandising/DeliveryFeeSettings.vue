<template>
  <div class="max-w-6xl mx-auto space-y-6 py-6 px-4 sm:px-6 lg:px-8">
    <Card class="rounded-2xl border border-gray-100 shadow-sm">
      <template #content>
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-semibold text-gray-900">Delivery Charge Settings</h1>
            <p class="text-sm text-gray-500">Hybrid model: fixed base + distance + surcharges.</p>
          </div>
          <Button severity="info" outlined icon="pi pi-refresh" label="Refresh" @click="loadSettings" />
        </div>
      </template>
    </Card>

    <Card class="rounded-2xl border border-gray-100 shadow-sm">
      <template #title>Configuration</template>
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="flex items-center gap-2 md:col-span-2">
            <Checkbox v-model="form.is_active" binary inputId="deliveryActive" />
            <label for="deliveryActive" class="text-sm text-gray-700">Enable delivery charge computation</label>
          </div>
          <div>
            <label class="text-sm text-gray-600">Base Fee</label>
            <InputNumber v-model="form.base_fee" fluid mode="currency" currency="PHP" locale="en-PH" :min="0" />
          </div>
          <div>
            <label class="text-sm text-gray-600">Per KM Fee</label>
            <InputNumber v-model="form.per_km_fee" fluid mode="currency" currency="PHP" locale="en-PH" :min="0" />
          </div>
          <div>
            <label class="text-sm text-gray-600">Minimum Delivery Fee</label>
            <InputNumber v-model="form.min_delivery_fee" fluid mode="currency" currency="PHP" locale="en-PH" :min="0" />
          </div>
          <div>
            <label class="text-sm text-gray-600">Free Shipping Min Order (optional)</label>
            <InputNumber v-model="form.free_shipping_min_order" fluid mode="currency" currency="PHP" locale="en-PH" :min="0" />
          </div>
          <div>
            <label class="text-sm text-gray-600">Bulky Item Surcharge</label>
            <InputNumber v-model="form.bulky_item_surcharge" fluid mode="currency" currency="PHP" locale="en-PH" :min="0" />
          </div>
          <div>
            <label class="text-sm text-gray-600">Remote Area Surcharge</label>
            <InputNumber v-model="form.remote_area_surcharge" fluid mode="currency" currency="PHP" locale="en-PH" :min="0" />
          </div>
          <div>
            <label class="text-sm text-gray-600">Max Delivery Distance KM (optional)</label>
            <InputNumber v-model="form.max_delivery_distance_km" fluid :min="0" :maxFractionDigits="2" />
          </div>
          <div>
            <label class="text-sm text-gray-600">Bulk Trip Discount Rate (enterprise only)</label>
            <InputNumber v-model="form.bulk_discount_rate" fluid suffix="%" :min="5" :max="25" :maxFractionDigits="2" />
          </div>
          <div class="md:col-span-2">
            <label class="text-sm text-gray-600">Notes</label>
            <Textarea v-model="form.notes" rows="3" fluid placeholder="Policy notes for operations team..." />
          </div>
        </div>
        <div class="mt-4 flex justify-end">
          <Button severity="info" :loading="saving" label="Save Settings" @click="saveSettings" />
        </div>
      </template>
    </Card>

    <Card class="rounded-2xl border border-gray-100 shadow-sm">
      <template #title>Estimator</template>
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="text-sm text-gray-600">Subtotal</label>
            <InputNumber v-model="estimateForm.subtotal" fluid mode="currency" currency="PHP" locale="en-PH" :min="0" />
          </div>
          <div>
            <label class="text-sm text-gray-600">Distance (KM)</label>
            <InputNumber v-model="estimateForm.distance_km" fluid :min="0" :maxFractionDigits="2" />
          </div>
          <div class="flex items-center gap-2">
            <Checkbox v-model="estimateForm.has_bulky_items" binary inputId="bulkyItems" />
            <label for="bulkyItems" class="text-sm text-gray-700">Has bulky items</label>
          </div>
          <div class="flex items-center gap-2">
            <Checkbox v-model="estimateForm.is_remote_area" binary inputId="remoteArea" />
            <label for="remoteArea" class="text-sm text-gray-700">Remote area</label>
          </div>
        </div>
        <div class="mt-4 flex justify-between items-center">
          <Button severity="info" outlined :loading="estimating" label="Estimate Fee" @click="estimateFee" />
          <div class="text-right">
            <p class="text-xs text-gray-500 uppercase">Estimated Shipping Fee</p>
            <p class="text-2xl font-semibold text-gray-900">{{ money(estimateResult.shipping_fee) }}</p>
          </div>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import merchandisingService from '@/services/merchandising.service'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Checkbox from 'primevue/checkbox'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'

const toast = useToast()
const saving = ref(false)
const estimating = ref(false)

const form = reactive<any>({
  is_active: true,
  base_fee: 100,
  per_km_fee: 10,
  min_delivery_fee: 80,
  free_shipping_min_order: null,
  bulky_item_surcharge: 0,
  remote_area_surcharge: 0,
  max_delivery_distance_km: null,
  bulk_discount_rate: 10,
  notes: '',
})

const estimateForm = reactive<any>({
  subtotal: 0,
  distance_km: 0,
  has_bulky_items: false,
  is_remote_area: false,
})

const estimateResult = reactive<any>({
  shipping_fee: 0,
  breakdown: null,
})

const loadSettings = async () => {
  const res = await merchandisingService.getDeliveryFeeSettings()
  Object.assign(form, res?.data || {})
}

const saveSettings = async () => {
  saving.value = true
  try {
    await merchandisingService.updateDeliveryFeeSettings(form)
    toast.add({ severity: 'success', summary: 'Saved', detail: 'Delivery fee settings updated.', life: 2200 })
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Save failed', detail: error?.response?.data?.message || 'Failed to save settings', life: 3000 })
  } finally {
    saving.value = false
  }
}

const estimateFee = async () => {
  estimating.value = true
  try {
    const res = await merchandisingService.estimateDeliveryFee(estimateForm)
    Object.assign(estimateResult, res?.data || {})
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Estimate failed', detail: error?.response?.data?.message || 'Failed to estimate', life: 3000 })
  } finally {
    estimating.value = false
  }
}

const money = (v: number | string) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(v || 0))

onMounted(loadSettings)
</script>

