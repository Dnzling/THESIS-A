<template>
  <div class="space-y-4 text-sm">
    <Card class="border border-slate-200 shadow-sm">
      <template #content>
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div class="flex min-w-0 items-start gap-3">
            <Button icon="pi pi-arrow-left" text rounded severity="secondary" size="small" aria-label="Back to subscriptions" @click="goBack" />
            <div class="min-w-0">
              <p class="text-xs font-semibold uppercase tracking-wider text-orange-600">Subscription plan</p>
              <div class="mt-1 flex flex-wrap items-center gap-2">
                <h1 class="text-xl font-semibold text-slate-950">{{ form.name || 'Plan details' }}</h1>
                <Tag v-if="!loading" :value="form.is_active ? 'Active' : 'Inactive'" :severity="form.is_active ? 'success' : 'secondary'" class="text-xs" />
                <Tag v-if="!loading && form.is_featured" value="Featured" severity="warn" class="text-xs" />
              </div>
              <p class="mt-1 text-xs text-slate-500">{{ form.plan_key || 'Loading plan' }} · Pricing, access, and storefront presentation</p>
            </div>
          </div>
          <Button type="button" label="Save Changes" icon="pi pi-check" severity="warn" size="small" :loading="saving" :disabled="loading || !form.id" @click="confirmSave" />
        </div>
      </template>
    </Card>

    <div v-if="loading" class="grid gap-4 lg:grid-cols-3">
      <Skeleton height="28rem" class="lg:col-span-2" borderRadius="1rem" />
      <Skeleton height="28rem" borderRadius="1rem" />
    </div>
    <Card v-else-if="loadError" class="border border-slate-200 shadow-sm">
      <template #content><div class="py-10 text-center"><p class="text-sm font-medium text-slate-800">{{ loadError }}</p><Button label="Try again" severity="warn" size="small" outlined class="mt-4" @click="loadData" /></div></template>
    </Card>

    <div v-else class="grid gap-4 lg:grid-cols-[minmax(0,1.65fr)_minmax(280px,1fr)]">
      <Card class="min-w-0 border border-slate-200 shadow-sm">
        <template #content>
          <div class="space-y-5">
            <div><h2 class="text-sm font-semibold text-slate-950">Plan details</h2><p class="mt-1 text-xs text-slate-500">Pricing, limits, and how the plan appears to stores.</p></div>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
              <div class="md:col-span-2"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Identity</p></div>
              <div class="md:col-span-2">
                <label class="mb-1 block text-xs font-medium text-slate-700">Name</label>
                <InputText v-model="form.name" size="small" fluid />
              </div>
              <div class="md:col-span-2">
                <label class="mb-1 block text-xs font-medium text-slate-700">Description</label>
                <InputText v-model="form.description" size="small" fluid />
              </div>
              <div class="md:col-span-2">
                <label class="mb-1 block text-xs font-medium text-slate-700">Features (one per line)</label>
                <Textarea v-model="form.features" rows="4" autoResize fluid class="text-sm" />
              </div>
              <div class="md:col-span-2 border-t border-slate-100 pt-4"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pricing</p></div>
              <div>
                <label class="mb-1 block text-xs font-medium text-slate-700">Monthly Price</label>
                <InputNumber v-model="form.monthly_price" mode="currency" currency="PHP" locale="en-PH" :min="0" size="small" fluid />
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-slate-700">Yearly Price</label>
                <InputNumber v-model="form.yearly_price" mode="currency" currency="PHP" locale="en-PH" :min="0" size="small" fluid />
              </div>
              <div class="md:col-span-2 border-t border-slate-100 pt-4"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Plan limits</p><p class="mt-1 text-xs text-slate-500">Leave blank for unlimited usage.</p></div>
              <div>
                <label class="mb-1 block text-xs font-medium text-slate-700">Total Accounts (Admin + Employees)</label>
                <InputNumber v-model="form.max_user_accounts" :min="1" placeholder="Unlimited" size="small" fluid />
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-slate-700">Store Branches</label>
                <InputNumber v-model="form.max_branches" :min="1" placeholder="Unlimited" size="small" fluid />
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-slate-700">Products</label>
                <InputNumber v-model="form.max_products" :min="1" placeholder="Unlimited" size="small" fluid />
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-slate-700">3D Model Requests per Month</label>
                <InputNumber v-model="form.max_3d_model_requests_per_month" :min="0" size="small" fluid />
                <small class="text-xs text-slate-500">Requires 3D manage permission. Set 0 to disable requests.</small>
              </div>
              <div class="md:col-span-2 border-t border-slate-100 pt-4"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Visibility</p></div>
              <div>
                <label class="mb-1 block text-xs font-medium text-slate-700">Sort Order</label>
                <InputNumber v-model="form.sort_order" :min="0" :max="999" size="small" fluid />
              </div>
              <div class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2">
                <span class="text-xs font-medium text-slate-700">Active</span>
                <ToggleSwitch v-model="form.is_active" />
              </div>
              <div class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2">
                <span class="text-xs font-medium text-slate-700">Featured</span>
                <ToggleSwitch v-model="form.is_featured" />
              </div>
            </div>
          </div>
        </template>
      </Card>

      <Card class="min-w-0 self-start border border-slate-200 shadow-sm">
        <template #content>
          <div class="flex items-center justify-between gap-3"><h2 class="text-sm font-semibold text-slate-950">Modules included</h2><Tag :value="`${form.modules.length} selected`" severity="warn" class="text-xs" /></div>
          <p class="mt-1 text-xs text-slate-500">Enable the main modules available to this plan.</p>
          <div class="mt-4 max-h-[520px] space-y-2 overflow-y-auto pr-1">
            <div v-for="mod in modules" :key="mod.key" class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 px-3 py-2.5">
              <span class="min-w-0 truncate text-xs font-medium text-slate-800">{{ mod.name }}</span>
              <ToggleSwitch :modelValue="moduleIncluded(mod.key)" @update:modelValue="(v:boolean)=>setModule(mod.key,v)" />
            </div>
            <div v-if="!modules.length" class="rounded-xl border border-dashed border-slate-200 py-8 text-center text-xs text-slate-500">No modules available.</div>
          </div>
        </template>
      </Card>
    </div>

    <Card v-if="!loading && !loadError" class="border border-slate-200 shadow-sm">
      <template #content>
        <div class="mb-4"><h2 class="text-sm font-semibold text-slate-950">Submodules & permissions</h2><p class="mt-1 text-xs text-slate-500">Fine-tune access within each included module.</p></div>
        <div v-if="loadingPermissions" class="grid gap-3 sm:grid-cols-2"><Skeleton v-for="i in 4" :key="i" height="7rem" borderRadius="0.75rem" /></div>
        <div v-else class="grid gap-3 lg:grid-cols-2">
          <div v-for="(group, moduleKey) in permissionsByModule" :key="moduleKey" class="min-w-0 rounded-xl border border-slate-200 p-4">
            <div class="mb-3 flex items-center justify-between gap-3 border-b border-slate-100 pb-3">
              <div>
                <p class="text-xs font-semibold text-slate-900">{{ moduleLabel(moduleKey) }}</p>
                <p class="text-xs text-slate-500">{{ group.length }} access groups</p>
              </div>
              <ToggleSwitch :modelValue="moduleIncluded(moduleKey)" @update:modelValue="(v:boolean)=>setModule(moduleKey,v)" />
            </div>
            <div class="space-y-2">
              <div
                v-for="sub in group"
                :key="`${moduleKey}.${sub.subKey}`"
                class="flex items-center justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2.5"
              >
                <div class="min-w-0">
                  <p class="text-xs font-medium text-slate-800">{{ sub.label }}</p>
                  <p class="truncate text-xs text-slate-500" :title="`Actions: ${sub.actions}`">{{ sub.actions }}</p>
                </div>
                <ToggleSwitch
                  :modelValue="submoduleIncluded(sub.permNames)"
                  @update:modelValue="(v:boolean)=>setSubmodule(moduleKey, sub.permNames, v)"
                />
              </div>
            </div>
          </div>
          <div v-if="!Object.keys(permissionsByModule).length" class="py-8 text-center text-xs text-slate-500 lg:col-span-2">No submodule permissions available.</div>
        </div>
      </template>
    </Card>

    <ConfirmDialog />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import axiosClient from '@/axios'
import Card from 'primevue/card'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import ToggleSwitch from 'primevue/toggleswitch'
import Tag from 'primevue/tag'
import Skeleton from 'primevue/skeleton'
import ConfirmDialog from 'primevue/confirmdialog'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'

const page = usePage()
const toast = useToast()
const confirm = useConfirm()
const planId = computed(() => {
  const fromProps = page?.props?.params?.id ?? page?.props?.plan_id
  if (fromProps) return Number(fromProps)
  const parts = (window.location.pathname || '').split('/').filter(Boolean)
  const last = parts[parts.length - 1]
  return Number(last || 0)
})

const loading = ref(true)
const loadError = ref('')
const saving = ref(false)
const deleting = ref(false)
const modules = ref<any[]>([])
const permissions = ref<any[]>([])
const loadingPermissions = ref(false)

const form = reactive({
  id: 0,
  plan_key: '',
  name: '',
  description: '',
  monthly_price: 0,
  yearly_price: 0,
  max_user_accounts: null as number | null,
  max_branches: null as number | null,
  max_products: null as number | null,
  max_3d_model_requests_per_month: 0,
  is_featured: false,
  is_active: true,
  sort_order: 0,
  features: '',
  modules: [] as string[],
  permissions: [] as string[],
})

const permissionsByModule = computed(() => {
  const grouped: Record<string, any[]> = {}
  permissions.value.forEach((p) => {
    const parts = (p.name || '').split('.')
    const module = parts[0] || p.module || 'general'
    const subKey = parts[1] || 'general'
    if (!grouped[module]) grouped[module] = {}
    if (!grouped[module][subKey]) grouped[module][subKey] = []
    grouped[module][subKey].push(p)
  })

  // flatten submodules per module into array with labels/actions text
  const result: Record<string, any[]> = {}
  Object.entries(grouped).forEach(([module, submodules]) => {
    result[module] = Object.entries(submodules).map(([subKey, perms]: any) => {
      const actions = perms
        .map((p: any) => (p.name || '').split('.')[2] || (p.display_name || '').split(' ').pop() || 'action')
        .filter(Boolean)
        .join(', ')
      return {
        subKey,
        label: deriveSubmoduleLabel(`${module}.${subKey}`, module),
        actions,
        permNames: perms.map((p: any) => p.name),
      }
    })
  })
  return result
})

const moduleIncluded = (key: string) => {
  if (validModuleKeys.value.has(key)) {
    return form.modules.includes(key)
  }

  // Support permission-only groups (e.g. `store.*`) that are not real module keys.
  return form.permissions.some((p) => p.startsWith(`${key}.`))
}
const permissionIncluded = (name: string) => form.permissions.includes(name)
const submoduleIncluded = (names: string[]) =>
  names.length > 0 && names.every((name) => form.permissions.includes(name))
const validModuleKeys = computed(() => new Set(modules.value.map((m: any) => String(m.key))))
const validPermissionNames = computed(() => new Set(permissions.value.map((p: any) => String(p.name))))

const moduleLabel = (key: string) =>
  String(key || '')
    .replace(/_/g, ' ')
    .split(' ')
    .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
    .join(' ')

const setModule = (key: string, val: boolean) => {
  if (val) {
    if (validModuleKeys.value.has(key) && !form.modules.includes(key)) form.modules.push(key)
    // Keep module and grouped submodule toggles in sync by enabling all permissions in this module.
    const modulePermNames = permissions.value
      .filter((p) => String(p.name || '').startsWith(`${key}.`))
      .map((p) => String(p.name))
    modulePermNames.forEach((name) => {
      if (!form.permissions.includes(name)) form.permissions.push(name)
    })
  } else {
    if (validModuleKeys.value.has(key)) {
      form.modules = form.modules.filter((k) => k !== key)
    }
    // also drop permissions of this module
    form.permissions = form.permissions.filter((p) => !p.startsWith(`${key}.`))
  }
}

const setPermission = (name: string, val: boolean) => {
  if (!validPermissionNames.value.has(name)) {
    return
  }

  if (val) {
    if (!form.permissions.includes(name)) form.permissions.push(name)
    // ensure module is on
    const moduleKey = name.split('.')[0]
    if (moduleKey && validModuleKeys.value.has(moduleKey) && !form.modules.includes(moduleKey)) {
      form.modules.push(moduleKey)
    }
  } else {
    form.permissions = form.permissions.filter((p) => p !== name)
  }
}

const setSubmodule = (moduleKey: string, names: string[], val: boolean) => {
  if (val) {
    names.forEach((n) => {
      if (validPermissionNames.value.has(n) && !form.permissions.includes(n)) form.permissions.push(n)
    })
    if (validModuleKeys.value.has(moduleKey) && !form.modules.includes(moduleKey)) form.modules.push(moduleKey)
  } else {
    form.permissions = form.permissions.filter((p) => !names.includes(p))
  }
}

const deriveSubmoduleLabel = (perm: string, module: string) => {
  const parts = perm.split('.')
  if (parts.length >= 2) {
    const sub = parts.slice(1, 2).join(' ')
    return sub.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
  }
  return perm
}

const loadData = async () => {
  if (!planId.value) return
  loading.value = true
  loadError.value = ''
  loadingPermissions.value = true
  try {
    const res = await axiosClient.get(`/api/admin/subscription-plans/${planId.value}`)
    const payload = res.data?.data || {}
    const plan = payload.plan || {}
    const mods = payload.modules || []
    const perms = payload.permissions || []
    form.id = plan.id
    form.plan_key = plan.plan_key
    form.name = plan.name
    form.description = plan.description || ''
    form.monthly_price = Number(plan.monthly_price || 0)
    form.yearly_price = Number(plan.yearly_price || 0)
    form.max_user_accounts = plan.max_user_accounts == null ? null : Number(plan.max_user_accounts)
    form.max_branches = plan.max_branches == null ? null : Number(plan.max_branches)
    form.max_products = plan.max_products == null ? null : Number(plan.max_products)
    form.max_3d_model_requests_per_month = Number(plan.max_3d_model_requests_per_month || 0)
    form.is_featured = !!plan.is_featured
    form.is_active = plan.is_active !== false
    form.sort_order = Number(plan.sort_order || 0)
    form.features = Array.isArray(plan.features) ? plan.features.join('\n') : ''
    form.modules = mods.filter((m: any) => m.included).map((m: any) => m.key)
    form.permissions = perms.filter((p: any) => p.included).map((p: any) => p.name)
    modules.value = mods
    permissions.value = perms
  } catch (e) {
    loadError.value = 'Unable to load this subscription plan.'
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load plan', life: 3000 })
  } finally {
    loading.value = false
    loadingPermissions.value = false
  }
}

const confirmSave = () => {
  confirm.require({
    message: 'Save changes to this plan?',
    header: 'Confirm',
    icon: 'pi pi-check',
    accept: save,
  })
}

const confirmDelete = () => {
  confirm.require({
    message: 'Delete this subscription plan? This action can be undone by restoring the record from the database.',
    header: 'Delete Plan',
    icon: 'pi pi-exclamation-triangle',
    accept: deletePlan,
  })
}

const save = async () => {
  if (!planId.value) return
  saving.value = true
  try {
    const features = form.features
      .split(/\r?\n/)
      .map((l) => l.trim())
      .filter(Boolean)
    const sanitizedModules = Array.from(new Set(form.modules.filter((m) => validModuleKeys.value.has(m))))
    const sanitizedPermissions = Array.from(new Set(form.permissions.filter((p) => validPermissionNames.value.has(p))))

    const response = await axiosClient.put(`/api/admin/subscription-plans/${planId.value}`, {
      name: form.name,
      description: form.description || null,
      monthly_price: form.monthly_price,
      yearly_price: form.yearly_price,
      max_user_accounts: form.max_user_accounts,
      max_branches: form.max_branches,
      max_products: form.max_products,
      max_3d_model_requests_per_month: form.max_3d_model_requests_per_month,
      features,
      is_featured: form.is_featured,
      is_active: form.is_active,
      sort_order: form.sort_order,
      modules: sanitizedModules,
      permissions: sanitizedPermissions,
    })
    const savedModules = response?.data?.saved_modules
    const savedPermissions = response?.data?.saved_permissions
    const sameNames = (saved: unknown, selected: string[]) =>
      Array.isArray(saved) && saved.length === selected.length &&
      selected.every((name) => saved.includes(name))
    if (!sameNames(savedModules, sanitizedModules) || !sameNames(savedPermissions, sanitizedPermissions)) {
      throw new Error('The saved modules or permissions do not match your selection. Please try again.')
    }
    toast.add({ severity: 'success', summary: 'Saved', detail: response?.data?.message || 'Plan updated', life: 2000 })
  } catch (e: any) {
    const firstValidationError = Object.values(e?.response?.data?.errors || {})?.[0]
    const validationMessage = Array.isArray(firstValidationError) ? firstValidationError[0] : undefined
    toast.add({ severity: 'error', summary: 'Error', detail: validationMessage || e?.response?.data?.message || e?.message || 'Failed to save', life: 3000 })
  } finally {
    saving.value = false
  }
}

const deletePlan = async () => {
  if (!planId.value) return
  deleting.value = true
  try {
    const response = await axiosClient.delete(`/api/admin/subscription-plans/${planId.value}`)
    toast.add({ severity: 'success', summary: 'Deleted', detail: response?.data?.message || 'Plan deleted', life: 2000 })
    router.visit('/admin/subscription')
  } catch (e: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: e?.response?.data?.message || 'Failed to delete plan', life: 3000 })
  } finally {
    deleting.value = false
  }
}

const goBack = () => {
  router.visit('/admin/subscription')
}

onMounted(loadData)
</script>
