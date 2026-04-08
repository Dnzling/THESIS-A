<template>
  <div class="space-y-6">
    <Card class="border border-slate-200 shadow-none">
      <template #content>
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Subscription Plan</p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">{{ form.name || 'Loading…' }}</h1>
            <p class="text-sm text-slate-500">Configure pricing, included modules, and submodule permissions.</p>
          </div>
          <div class="flex items-center gap-2">
            <Button label="Back" icon="pi pi-arrow-left" text @click="goBack" />
            <Button label="Save Changes" icon="pi pi-check" severity="info" :loading="saving" @click="confirmSave" />
          </div>
        </div>
      </template>
    </Card>

    <div class="grid gap-4 md:grid-cols-2">
      <Card class="border border-slate-200 shadow-none">
        <template #title>Plan Details</template>
        <template #content>
          <div class="space-y-3">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
              <div>
                <label class="text-sm font-medium text-slate-700">Name</label>
                <InputText v-model="form.name" fluid />
              </div>
              <div>
                <label class="text-sm font-medium text-slate-700">Key</label>
                <InputText v-model="form.plan_key" fluid disabled />
              </div>
              <div>
                <label class="text-sm font-medium text-slate-700">Monthly Price</label>
                <InputNumber v-model="form.monthly_price" mode="currency" currency="PHP" locale="en-PH" fluid />
              </div>
              <div>
                <label class="text-sm font-medium text-slate-700">Yearly Price</label>
                <InputNumber v-model="form.yearly_price" mode="currency" currency="PHP" locale="en-PH" fluid />
              </div>
              <div>
                <label class="text-sm font-medium text-slate-700">Sort Order</label>
                <InputNumber v-model="form.sort_order" :min="0" :max="999" fluid />
              </div>
              <div class="flex items-center gap-3">
                <InputSwitch v-model="form.is_active" />
                <span class="text-sm text-slate-700">Active</span>
              </div>
              <div class="flex items-center gap-3">
                <InputSwitch v-model="form.is_featured" />
                <span class="text-sm text-slate-700">Featured</span>
              </div>
            </div>
            <div>
              <label class="text-sm font-medium text-slate-700">Description</label>
              <InputText v-model="form.description" fluid />
            </div>
            <div>
              <label class="text-sm font-medium text-slate-700">Features (one per line)</label>
              <Textarea v-model="form.features" rows="5" autoResize fluid />
            </div>
          </div>
        </template>
      </Card>

      <Card class="border border-slate-200 shadow-none">
        <template #title>Modules Included</template>
        <template #content>
          <div class="mb-3 text-sm text-slate-500">Toggle modules for this plan. Submodules/permissions can be tuned below.</div>
          <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <div v-for="mod in modules" :key="mod.key" class="flex items-center justify-between rounded border border-slate-200 px-3 py-2">
              <div>
                <p class="font-semibold text-sm text-slate-900">{{ mod.name }}</p>
              </div>
              <ToggleSwitch :modelValue="moduleIncluded(mod.key)" @update:modelValue="(v:boolean)=>setModule(mod.key,v)" />
            </div>
          </div>
        </template>
      </Card>
    </div>

    <Card class="border border-slate-200 shadow-none">
      <template #title>Submodules & Permissions</template>
      <template #content>
        <div v-if="loadingPermissions" class="py-6 flex justify-center">
          <i class="pi pi-spin pi-spinner text-2xl text-slate-500" />
        </div>
        <div v-else class="space-y-4">
          <div v-for="(group, moduleKey) in permissionsByModule" :key="moduleKey" class="rounded border border-slate-200 p-3">
            <div class="flex items-center justify-between mb-2">
              <div>
                <p class="font-semibold text-slate-800">{{ moduleLabel(moduleKey) }}</p>
                <p class="text-xs text-slate-500">Toggle submodule groups (all actions per group)</p>
              </div>
              <InputSwitch :modelValue="moduleIncluded(moduleKey)" @update:modelValue="(v:boolean)=>setModule(moduleKey,v)" />
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
              <div
                v-for="sub in group"
                :key="`${moduleKey}.${sub.subKey}`"
                class="flex items-center justify-between rounded bg-slate-50 px-3 py-2"
              >
                <div class="space-y-1">
                  <p class="text-sm font-medium text-slate-800">{{ sub.label }}</p>
                  <p class="text-xs text-slate-500 truncate">Actions: {{ sub.actions }}</p>
                </div>
                <InputSwitch
                  :modelValue="submoduleIncluded(moduleKey, sub.subKey)"
                  @update:modelValue="(v:boolean)=>setSubmodule(moduleKey, sub.subKey, v)"
                />
              </div>
            </div>
          </div>
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
import InputSwitch from 'primevue/inputswitch'
import MultiSelect from 'primevue/multiselect'
import Dialog from 'primevue/dialog'
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

const loading = ref(false)
const saving = ref(false)
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
    // Hide supplier module permissions (legacy/unused)
    if (module === 'supplier') return
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

const moduleIncluded = (key: string) => form.modules.includes(key)
const permissionIncluded = (name: string) => form.permissions.includes(name)
const submoduleIncluded = (moduleKey: string, subKey: string) =>
  form.permissions.some((p) => p.startsWith(`${moduleKey}.${subKey}.`))

const moduleLabel = (key: string) =>
  String(key || '')
    .replace(/_/g, ' ')
    .split(' ')
    .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
    .join(' ')

const setModule = (key: string, val: boolean) => {
  if (val) {
    if (!form.modules.includes(key)) form.modules.push(key)
  } else {
    form.modules = form.modules.filter((k) => k !== key)
    // also drop permissions of this module
    form.permissions = form.permissions.filter((p) => !p.startsWith(`${key}.`))
  }
}

const setPermission = (name: string, val: boolean) => {
  if (val) {
    if (!form.permissions.includes(name)) form.permissions.push(name)
    // ensure module is on
    const moduleKey = name.split('.')[0]
    if (moduleKey && !form.modules.includes(moduleKey)) {
      form.modules.push(moduleKey)
    }
  } else {
    form.permissions = form.permissions.filter((p) => p !== name)
  }
}

const setSubmodule = (moduleKey: string, subKey: string, val: boolean) => {
  const prefix = `${moduleKey}.${subKey}.`
  const names = permissions.value.filter((p) => (p.name || '').startsWith(prefix)).map((p) => p.name)
  if (val) {
    names.forEach((n) => {
      if (!form.permissions.includes(n)) form.permissions.push(n)
    })
    if (!form.modules.includes(moduleKey)) form.modules.push(moduleKey)
  } else {
    form.permissions = form.permissions.filter((p) => !p.startsWith(prefix))
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
    form.is_featured = !!plan.is_featured
    form.is_active = plan.is_active !== false
    form.sort_order = Number(plan.sort_order || 0)
    form.features = Array.isArray(plan.features) ? plan.features.join('\n') : ''
    form.modules = mods.filter((m: any) => m.included).map((m: any) => m.key)
    form.permissions = perms.filter((p: any) => p.included).map((p: any) => p.name)
    modules.value = mods
    permissions.value = perms
  } catch (e) {
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

const save = async () => {
  if (!planId.value) return
  saving.value = true
  try {
    const features = form.features
      .split(/\r?\n/)
      .map((l) => l.trim())
      .filter(Boolean)
    await axiosClient.put(`/api/admin/subscription-plans/${planId.value}`, {
      name: form.name,
      description: form.description || null,
      monthly_price: form.monthly_price,
      yearly_price: form.yearly_price,
      features,
      is_featured: form.is_featured,
      is_active: form.is_active,
      sort_order: form.sort_order,
      modules: form.modules,
      permissions: form.permissions,
    })
    toast.add({ severity: 'success', summary: 'Saved', detail: 'Plan updated', life: 2000 })
  } catch (e: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: e?.response?.data?.message || 'Failed to save', life: 3000 })
  } finally {
    saving.value = false
  }
}

const goBack = () => {
  router.visit('/admin/subscription')
}

onMounted(loadData)
</script>
