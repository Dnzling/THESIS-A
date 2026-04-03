<template>
  <div class="min-h-screen bg-slate-50">
    <div class="mx-auto max-w-5xl px-6 py-8">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-400">Supplier Profile</p>
          <h1 class="text-2xl font-semibold text-slate-900">Supplier Portal</h1>
          <p class="text-sm text-slate-500">Manage your verification and supplier details.</p>
        </div>
        <div class="flex flex-wrap gap-2">
          <Button
            :label="verificationLabel"
            icon="pi pi-check-circle"
            severity="success"
            class="small-pill"
            :disabled="isVerified"
            @click="goToVerification"
          />
        </div>
      </div>

      <div v-if="loading" class="mt-10 flex items-center justify-center">
        <ProgressSpinner />
      </div>

      <template v-else>
        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-[1.2fr_0.8fr]">
          <section class="rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm">
            <div class="flex items-start justify-between gap-4">
              <div>
                <p class="text-lg font-semibold text-slate-900">{{ supplierName }}</p>
                <p class="text-sm text-slate-500">{{ supplierTypeLabel }}</p>
              </div>
              <Tag :value="statusLabel" :severity="statusSeverity" rounded />
            </div>

            <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
              <InfoRow label="Company Name" :value="supplier?.company_name || supplier?.supplier_name || '-'" />
              <InfoRow label="Contact Person" :value="supplier?.contact_person || '-'" />
              <InfoRow label="Email" :value="supplier?.email || user?.email || '-'" />
              <InfoRow label="Phone" :value="supplier?.phone || '-'" />
              <InfoRow label="Address" :value="fullAddress" />
              <InfoRow label="Payment Terms" :value="paymentTermsLabel" />
            </div>

            <div v-if="portal?.rejection_reason" class="mt-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
              <p class="font-semibold">Verification Feedback</p>
              <p class="mt-1 text-rose-700">{{ portal?.rejection_reason }}</p>
            </div>
          </section>

          <section class="rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm space-y-4">
            <div>
              <p class="text-sm font-semibold text-slate-800">Verification Status</p>
              <p class="text-xs text-slate-500">Upload or update documents to verify your account.</p>
            </div>

            <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-4">
              <div class="flex items-center justify-between gap-3">
                <div>
                  <p class="text-sm font-semibold text-slate-700">Account Status</p>
                  <p class="text-xs text-slate-500">{{ statusHint }}</p>
                </div>
                <Tag :value="statusLabel" :severity="statusSeverity" rounded />
              </div>
            </div>

            <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-4">
              <div class="flex items-center justify-between gap-3">
                <div>
                  <p class="text-sm font-semibold text-slate-700">Verification</p>
                  <p class="text-xs text-slate-500">{{ verificationLabel }}</p>
                </div>
                <Button
                  :label="verificationLabel"
                  severity="info"
                  class="small-pill"
                  :disabled="isVerified"
                  @click="goToVerification"
                />
              </div>
            </div>
          </section>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { useAuthStore } from '@/stores/auth'
import supplierService from '@/services/supplier.service'
import SystemLayout from '@/Layouts/SystemLayout.vue'

defineOptions({ layout: SystemLayout })

const authStore = useAuthStore()
const loading = ref(true)
const portal = ref<any | null>(null)
const supplier = ref<any | null>(null)
const user = computed(() => authStore.user)

const isSupplier = computed(() => String(authStore.user?.role || '').toLowerCase().includes('supplier'))

const supplierName = computed(() => supplier.value?.company_name || supplier.value?.supplier_name || 'Supplier')
const supplierTypeLabel = computed(() => {
  const raw = supplier.value?.supplier_type || ''
  return raw ? raw.toString().replace(/_/g, ' ').replace(/\b\w/g, (c: string) => c.toUpperCase()) : 'Supplier Type'
})

const fullAddress = computed(() => {
  const parts = [
    supplier.value?.address,
    supplier.value?.city,
    supplier.value?.province,
    supplier.value?.postal_code,
    supplier.value?.country,
  ].filter(Boolean)
  return parts.length ? parts.join(', ') : '-'
})

const statusLabel = computed(() => {
  const status = portal.value?.status || 'pending'
  return status.toString().toUpperCase()
})

const statusSeverity = computed(() => {
  const status = (portal.value?.status || '').toLowerCase()
  if (status === 'approved') return 'success'
  if (status === 'rejected') return 'danger'
  return 'warning'
})

const paymentTermsLabel = computed(() => {
  const raw = supplier.value?.payment_terms || '-'
  return raw.toString().replace(/_/g, ' ').replace(/\b\w/g, (c: string) => c.toUpperCase())
})

const isVerified = computed(() => (portal.value?.status || '').toLowerCase() === 'approved')
const verificationLabel = computed(() => (isVerified.value ? 'Verified' : 'Verify Account'))
const statusHint = computed(() => {
  if (isVerified.value) return 'Your account is verified.'
  if ((portal.value?.status || '').toLowerCase() === 'rejected') return 'Please review the feedback and resubmit.'
  return 'Verification pending. Upload the required documents.'
})

const goToVerification = () => {
  if (isVerified.value) return
  router.visit('/supplier-portal/registration')
}

const loadPortal = async () => {
  loading.value = true
  try {
    const response = await supplierService.getMyPortal()
    if (!response?.success) {
      router.visit('/supplier-portal/registration')
      return
    }
    portal.value = response.data
    supplier.value = response.data?.supplier || null
  } catch (error) {
    router.visit('/supplier-portal/registration')
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  if (!isSupplier.value) {
    router.visit('/profile')
    return
  }
  await loadPortal()
})
const InfoRow = {
  props: {
    label: { type: String, required: true },
    value: { type: String, required: true },
  },
  template: `
    <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-4">
      <div class="text-xs text-slate-500">{{ label }}</div>
      <div class="mt-1 text-sm font-semibold text-slate-900">{{ value }}</div>
    </div>
  `,
}
</script>

<style scoped>
.small-pill {
  border-radius: 999px;
  font-size: 0.85rem;
  padding: 0.45rem 0.9rem;
}
</style>
