<template>
  <div class="max-w-3xl mx-auto space-y-6 py-6 px-4 sm:px-6 lg:px-8">
    <ConfirmDialog />
    <Card class="border border-gray-100 shadow-sm rounded-2xl">
      <template #content>
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-semibold text-gray-900">Create Voucher</h1>
            <p class="text-sm text-gray-500">Create as draft first, then activate when ready.</p>
          </div>
          <Button text icon="pi pi-arrow-left" label="Back" @click="router.visit('/sales/vouchers')" />
        </div>
      </template>
    </Card>

    <Card class="border border-gray-100 shadow-sm rounded-2xl">
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="text-sm text-gray-600">Voucher Name</label>
            <InputText v-model="form.voucher_name" class="w-full" placeholder="Summer Sale 2026" />
          </div>
          <div>
            <label class="text-sm text-gray-600">Code</label>
            <InputText :model-value="autoCode" readonly class="w-full" />
          </div>
          <div>
            <label class="text-sm text-gray-600">Voucher Slots</label>
            <InputNumber v-model="form.voucher_slots" :min="0" :useGrouping="false" class="w-full" />
          </div>
          <div>
            <label class="text-sm text-gray-600">Discount Type</label>
            <Dropdown v-model="form.discount_type" :options="discountTypeOptions" optionLabel="label" optionValue="value" class="w-full" />
          </div>
          <div>
            <label class="text-sm text-gray-600">Discount Value</label>
            <InputNumber v-model="form.discount_value" :min="0.01" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
          </div>
          <div>
            <label class="text-sm text-gray-600">Minimum Order Amount</label>
            <InputNumber v-model="form.min_order_amount" :min="0" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
          </div>
          <div>
            <label class="text-sm text-gray-600">Max Discount Amount (optional)</label>
            <InputNumber v-model="form.max_discount_amount" :min="0" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
          </div>
          <div class="flex items-center gap-2 pt-6">
            <Checkbox v-model="form.is_active" binary inputId="is_active" />
            <label for="is_active" class="text-sm text-gray-700">Set as active now</label>
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
          <div>
            <label class="text-sm text-gray-600">Start Date</label>
            <Calendar v-model="form.starts_at" showTime hourFormat="12" class="w-full" />
          </div>
          <div>
            <label class="text-sm text-gray-600">End Date</label>
            <Calendar v-model="form.ends_at" showTime hourFormat="12" class="w-full" />
          </div>
        </div>

        <div v-if="errorMessage" class="mt-4 p-3 text-sm rounded-lg bg-red-50 text-red-700 border border-red-200">
          {{ errorMessage }}
        </div>

        <div class="mt-6 flex justify-end gap-2">
          <Button text label="Cancel" @click="router.visit('/sales/vouchers')" />
          <Button icon="pi pi-save" label="Create Voucher" :loading="submitting" @click="submit" />
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import salesService from '@/services/sales.service'
import Card from 'primevue/card'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Dropdown from 'primevue/dropdown'
import InputNumber from 'primevue/inputnumber'
import Checkbox from 'primevue/checkbox'
import Calendar from 'primevue/calendar'
import ConfirmDialog from 'primevue/confirmdialog'
import { useConfirm } from 'primevue/useconfirm'

const submitting = ref(false)
const errorMessage = ref('')
const confirm = useConfirm()
const codeSuffix = ref(randomSuffix())

const form = reactive<any>({
  voucher_name: '',
  voucher_slots: 0,
  code: '',
  discount_type: 'fixed',
  discount_value: 0,
  min_order_amount: 0,
  max_discount_amount: null,
  starts_at: new Date(),
  ends_at: null,
  is_active: false,
})

const discountTypeOptions = [
  { label: 'Fixed Amount', value: 'fixed' },
  { label: 'Percent', value: 'percent' },
]

const isoOrNull = (value: Date | null) => (value ? new Date(value).toISOString() : null)
const autoCode = computed(() => {
  const raw = String(form.voucher_name || '').trim().toUpperCase()
  const compact = raw.replace(/[^A-Z0-9]+/g, '')
  if (!compact) return `VOUCHER-${codeSuffix.value}`
  return `${compact.slice(0, 8)}-${codeSuffix.value}`
})

function randomSuffix(): string {
  return Math.random().toString(36).slice(2, 6).toUpperCase()
}

watch(
  () => form.voucher_name,
  () => {
    codeSuffix.value = randomSuffix()
  }
)

const confirmSave = () =>
  new Promise<boolean>((resolve) => {
    confirm.require({
      message: 'Save this voucher now?',
      header: 'Confirm Save',
      icon: 'pi pi-exclamation-triangle',
      acceptLabel: 'Save',
      rejectLabel: 'Cancel',
      accept: () => resolve(true),
      reject: () => resolve(false),
    })
  })

const submit = async () => {
  const confirmed = await confirmSave()
  if (!confirmed) {
    return
  }

  errorMessage.value = ''
  submitting.value = true
  try {
    const res = await salesService.createVoucher({
      ...form,
      // Let backend generate the final unique code.
      code: '',
      starts_at: isoOrNull(form.starts_at),
      ends_at: isoOrNull(form.ends_at),
    })
    const id = res?.data?.id
    if (id) {
      router.visit(`/sales/vouchers/${id}`)
      return
    }
    router.visit('/sales/vouchers')
  } catch (error: any) {
    errorMessage.value = error?.response?.data?.message || 'Failed to create voucher.'
  } finally {
    submitting.value = false
  }
}
</script>
