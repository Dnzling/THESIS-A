<template>
  <div class="space-y-6 pb-8">
    <div class="rounded-2xl border border-slate-200 bg-white">
      <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 px-4 py-3 md:px-6">
        <Button icon="pi pi-chevron-left" severity="secondary" text rounded aria-label="Back to shop" @click="router.push({ name: 'ecommerce.home' })" />
        <div class="ml-auto flex items-center gap-2">
          <InputText v-model="search" placeholder="Search code or store" class="w-[16rem]" />
        </div>
      </div>

      <div class="px-4 py-4 md:px-6">
        <div v-if="loading" class="grid grid-cols-1 gap-3 md:grid-cols-2">
          <Card v-for="idx in 6" :key="idx" class="border border-slate-200 shadow-none">
            <template #content>
              <Skeleton height="1.4rem" class="mb-2" />
              <Skeleton height="1rem" width="65%" class="mb-2" />
              <Skeleton height="1rem" width="45%" />
            </template>
          </Card>
        </div>

        <template v-else>
          <div class="flex flex-wrap items-start justify-between gap-2">
            <div>
              <p class="text-xs uppercase tracking-[0.16em] text-slate-500">Vouchers</p>
              <h1 class="mt-1 text-2xl font-bold text-slate-900">Available Vouchers</h1>
              <p class="mt-1 text-sm text-slate-500">Use a code at checkout for the store shown on each offer.</p>
            </div>
            <Tag v-if="filteredVouchers.length" :value="`${filteredVouchers.length} available`" severity="success" />
          </div>

          <div v-if="!filteredVouchers.length" class="mt-4 text-sm text-slate-500">No active vouchers.</div>
          <div v-else class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
            <Card
              v-for="voucher in filteredVouchers"
              :key="voucher.id"
              class="cursor-pointer border border-slate-200 shadow-none transition hover:border-sky-300"
              @click="openVoucher(voucher)"
            >
              <template #content>
                <div class="flex items-start justify-between gap-3">
                  <div class="min-w-0">
                    <p class="truncate text-lg font-semibold text-slate-900">{{ voucher.voucher_name || voucher.code }}</p>
                    <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-orange-700">{{ voucher.store_name }} · {{ voucher.code }}</p>
                    <p class="mt-1 text-sm text-slate-600">
                      {{
                        voucher.discount_type === 'percent'
                          ? `${voucher.discount_value}% off`
                          : `PHP ${Number(voucher.discount_value).toFixed(2)} off`
                      }}
                    </p>
                    <p v-if="Number(voucher.min_order_amount || 0) > 0" class="mt-1 text-xs text-slate-500">
                      Min order: PHP {{ Number(voucher.min_order_amount).toFixed(2) }}
                    </p>
                    <p v-if="voucher.ends_at" class="mt-1 text-xs text-slate-500">Valid until {{ new Date(voucher.ends_at).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' }) }}</p>
                  </div>
                  <i class="pi pi-chevron-right mt-1 text-slate-400" />
                </div>
              </template>
            </Card>
          </div>
        </template>
      </div>
    </div>

    <Dialog v-model:visible="voucherDialog" modal header="Voucher Details" :style="{ width: '32rem', maxWidth: '95vw' }">
      <div v-if="selectedVoucher" class="space-y-3 text-sm">
        <div>
          <p class="text-xs text-slate-500">Code</p>
          <p class="text-lg font-semibold text-slate-900">{{ selectedVoucher.code }}</p>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <div class="rounded-xl border border-slate-200 p-3">
            <p class="text-xs text-slate-500">Discount</p>
            <p class="font-semibold text-slate-900">
              {{
                selectedVoucher.discount_type === 'percent'
                  ? `${selectedVoucher.discount_value}%`
                  : `PHP ${Number(selectedVoucher.discount_value).toFixed(2)}`
              }}
            </p>
          </div>
          <div class="rounded-xl border border-slate-200 p-3">
            <p class="text-xs text-slate-500">Min Order</p>
            <p class="font-semibold text-slate-900">PHP {{ Number(selectedVoucher.min_order_amount || 0).toFixed(2) }}</p>
          </div>
          <div class="rounded-xl border border-slate-200 p-3">
            <p class="text-xs text-slate-500">Max Discount</p>
            <p class="font-semibold text-slate-900">
              {{ selectedVoucher.max_discount_amount == null ? '—' : `PHP ${Number(selectedVoucher.max_discount_amount).toFixed(2)}` }}
            </p>
          </div>
          <div class="rounded-xl border border-slate-200 p-3">
            <p class="text-xs text-slate-500">Validity</p>
            <p class="font-semibold text-slate-900">
              {{ formatValidity(selectedVoucher.starts_at, selectedVoucher.ends_at) }}
            </p>
          </div>
        </div>
      </div>

      <template #footer>
        <Button label="Close" severity="secondary" outlined @click="voucherDialog = false" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import EcommerceMobileWrapper from '@/Layouts/EcommerceMobileWrapper.vue'
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import ecommerceService from '@/services/ecommerce.service'
import { showAlert } from '@/utils/swal'
import InputText from 'primevue/inputtext'
import Dialog from 'primevue/dialog'

defineOptions({
  layout: EcommerceMobileWrapper,
})

const router = useRouter()

const loading = ref(false)
const vouchers = ref<any[]>([])
const search = ref('')

const voucherDialog = ref(false)
const selectedVoucher = ref<any | null>(null)

const filteredVouchers = computed(() => {
  const q = search.value.trim().toLowerCase()
  if (!q) return vouchers.value
  return vouchers.value.filter((v) => `${v.code || ''} ${v.store_name || ''} ${v.voucher_name || ''}`.toLowerCase().includes(q))
})

function formatValidity(startsAt: any, endsAt: any) {
  const start = startsAt ? new Date(startsAt) : null
  const end = endsAt ? new Date(endsAt) : null
  const options: Intl.DateTimeFormatOptions = { dateStyle: 'medium', timeStyle: 'short' }
  const startText = start ? start.toLocaleString('en-PH', options) : 'Now'
  const endText = end ? end.toLocaleString('en-PH', options) : 'No expiry'
  return `${startText} - ${endText}`
}

function openVoucher(voucher: any) {
  selectedVoucher.value = voucher
  voucherDialog.value = true
}

async function loadStore() {
  loading.value = true
  try {
    const response = await ecommerceService.getAvailableVouchers()
    vouchers.value = response.data?.data || []
  } catch {
    showAlert({ severity: 'error', summary: 'Vouchers', detail: 'Failed to load store vouchers.' })
  } finally {
    loading.value = false
  }
}

onMounted(loadStore)
</script>
