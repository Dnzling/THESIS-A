<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">Store Settings</h1>
        <p class="text-sm text-slate-600">Manage trial setup, modules, and system configuration.</p>
      </div>
      <Button label="Go to Trial Setup" severity="secondary" outlined @click="goToOnboarding" />
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
      <Card class="lg:col-span-2">
        <template #title>Trial Modules</template>
        <template #content>
          <p class="text-sm text-slate-600 mb-4">
            Choose which modules are enabled for your store. Disabled modules will be hidden from the sidebar and blocked.
          </p>
          <MultiSelect
            v-model="form.modules"
            :options="moduleOptions"
            optionLabel="label"
            optionValue="value"
            display="chip"
            filter
            class="w-full"
            placeholder="Select modules"
          />
          <div class="mt-4 flex items-center justify-between">
            <p class="text-xs text-slate-500">
              You can update these anytime during the trial.
            </p>
            <Button
              label="Save Modules"
              icon="pi pi-check"
              :loading="saving"
              :disabled="form.modules.length === 0 || saving"
              @click="saveModules"
            />
          </div>
        </template>
      </Card>

      <Card>
        <template #title>Trial Status</template>
        <template #content>
          <div class="space-y-3 text-sm text-slate-700">
            <div class="flex items-center justify-between">
              <span>Plan</span>
              <span class="font-semibold">{{ trialPlanLabel }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span>Status</span>
              <Tag :value="subscriptionStatusLabel" :severity="subscriptionSeverity" />
            </div>
            <div class="flex items-center justify-between">
              <span>Ends On</span>
              <span class="font-semibold">{{ subscriptionEndsAtLabel }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span>Days Remaining</span>
              <span class="font-semibold">{{ daysRemainingLabel }}</span>
            </div>
          </div>
          <div class="mt-6 rounded-lg bg-slate-50 p-3 text-xs text-slate-600">
            Upgrade your plan anytime to unlock all modules and remove trial limits.
          </div>
          <Button class="mt-4 w-full" label="Upgrade Plan" severity="info" @click="goToUpgrade" />
        </template>
      </Card>
    </div>

    <Card>
      <template #title>Store Profile</template>
      <template #content>
        <div class="grid gap-4 md:grid-cols-2 text-sm text-slate-700">
          <div>
            <div class="text-xs uppercase text-slate-400">Store Name</div>
            <div class="font-semibold">{{ store.name || 'Not set' }}</div>
          </div>
          <div>
            <div class="text-xs uppercase text-slate-400">Contact</div>
            <div class="font-semibold">{{ store.contact_person || 'Not set' }}</div>
          </div>
          <div>
            <div class="text-xs uppercase text-slate-400">Email</div>
            <div class="font-semibold">{{ store.email || 'Not set' }}</div>
          </div>
          <div>
            <div class="text-xs uppercase text-slate-400">Phone</div>
            <div class="font-semibold">{{ store.phone || 'Not set' }}</div>
          </div>
        </div>
      </template>
    </Card>

    <Card>
      <template #title>Small Store Access Map</template>
      <template #content>
        <p class="text-sm text-slate-600 mb-4">
          This is the frontend permission list for the Small tier (1-5 employees). Use it as the baseline when
          checking feature visibility on the UI.
        </p>

        <div class="grid gap-4 md:grid-cols-2">
          <div
            v-for="module in smallModules"
            :key="module.key"
            class="rounded-lg border border-slate-200 p-4"
          >
            <div class="text-sm font-semibold text-slate-900">{{ module.label }}</div>
            <div class="mt-2 space-y-2">
              <div
                v-for="feature in module.features"
                :key="feature.key"
                class="rounded-md bg-slate-50 px-3 py-2 text-xs text-slate-700"
              >
                <div class="font-medium">{{ feature.label }}</div>
                <div class="mt-1 text-[11px] text-slate-500">
                  {{ feature.permissions.join(', ') }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-6 rounded-lg bg-slate-50 p-3 text-xs text-slate-600">
          Total small-tier permissions: <span class="font-semibold">{{ smallPermissionList.length }}</span>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import axiosClient from '@/axios'
import { router } from '@inertiajs/vue3'
import Button from 'primevue/button'
import Card from 'primevue/card'
import MultiSelect from 'primevue/multiselect'
import Tag from 'primevue/tag'
import { usePermissions } from '@/composables/usePermissions'

const saving = ref(false)
const loading = ref(true)

const store = reactive({
  name: '',
  contact_person: '',
  email: '',
  phone: '',
})

const subscription = reactive({
  tier: 'free',
  status: 'trial',
  ends_at: '',
  days_remaining: null as number | null,
})

const onboarding = reactive({
  plan: 'simple',
  modules: [] as string[],
})

const form = reactive({
  modules: [] as string[],
})

const moduleOptions = [
  { label: 'Inventory', value: 'inventory' },
  { label: 'Procurement', value: 'procurement' },
  { label: 'Sales / POS', value: 'sales' },
  { label: 'HR / Payroll', value: 'hr' },
  { label: 'Logistics / Delivery', value: 'logistics' },
  { label: 'Finance', value: 'finance' },
  { label: 'Supplier Portal', value: 'supplier' },
  { label: 'E-commerce', value: 'ecommerce' },
]

const { permissionCatalog, getTierPermissionFeatures, getTierPermissionList } = usePermissions()

const smallFeatureMap = computed(() => getTierPermissionFeatures('small'))
const smallModules = computed(() =>
  permissionCatalog
    .filter(module => (smallFeatureMap.value[module.key] || []).length > 0)
    .map(module => ({
      ...module,
      features: module.features.filter(feature =>
        (smallFeatureMap.value[module.key] || []).includes(feature.key),
      ),
    })),
)
const smallPermissionList = computed(() => getTierPermissionList('small'))

const trialPlanLabel = computed(() => onboarding.plan === 'unlimited' ? 'Unlimited Trial' : 'Simple Trial')

const subscriptionStatusLabel = computed(() => {
  if (subscription.status === 'expired') return 'Expired'
  if (subscription.status === 'active') return 'Active'
  return 'Trial'
})

const subscriptionSeverity = computed(() => {
  if (subscription.status === 'expired') return 'danger'
  if (subscription.status === 'active') return 'success'
  return 'info'
})

const subscriptionEndsAtLabel = computed(() => subscription.ends_at || 'Not set')
const daysRemainingLabel = computed(() => {
  if (subscription.days_remaining === null) return '—'
  return `${subscription.days_remaining} days`
})

const fetchSettings = async () => {
  loading.value = true
  try {
    const response = await axiosClient.get('/api/store/settings')
    const data = response?.data?.data || {}

    store.name = data.store?.name || ''
    store.contact_person = data.store?.contact_person || ''
    store.email = data.store?.email || ''
    store.phone = data.store?.phone || ''

    subscription.tier = data.subscription?.tier || 'free'
    subscription.status = data.subscription?.status || 'trial'
    subscription.ends_at = data.subscription?.ends_at || ''
    subscription.days_remaining = data.subscription?.days_remaining ?? null

    onboarding.plan = data.onboarding?.plan || 'simple'
    onboarding.modules = data.onboarding?.modules || []
    form.modules = [...onboarding.modules]
  } catch (error) {
    console.error('Failed to load settings', error)
  } finally {
    loading.value = false
  }
}

const saveModules = async () => {
  saving.value = true
  try {
    await axiosClient.put('/api/store/settings/modules', {
      modules: form.modules,
    })
    onboarding.modules = [...form.modules]
  } catch (error) {
    console.error('Failed to update modules', error)
  } finally {
    saving.value = false
  }
}

const goToOnboarding = () => {
  router.visit('/trial-onboarding')
}

const goToUpgrade = () => {
  router.visit('/admin/subscription')
}

onMounted(fetchSettings)
</script>
