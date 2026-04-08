<template>
  <div class="p-4 min-h-screen max-w-4xl mx-auto">
    <div class="mb-4 flex items-center gap-3">
      <Button icon="pi pi-arrow-left" text severity="secondary" @click="goBack" />
      <div>
        <h1 class="text-xl font-bold text-gray-800">Create Reorder Rule</h1>
        <p class="text-xs text-gray-500">Configure threshold-based replenishment for your branch.</p>
      </div>
    </div>

    <Card>
      <template #content>
        <form class="space-y-4" @submit.prevent="submit">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="text-xs font-semibold text-gray-600">Branch</label>
              <div class="mt-1">
                <Skeleton v-if="autoFillLoading" height="2.5rem" />
                <InputText v-else :value="displayBranchLabel" readonly class="w-full" />
              </div>
            </div>

            <div>
              <label class="text-xs font-semibold text-gray-600">Product <span class="text-red-500">*</span></label>
              <div class="mt-1">
                <Skeleton v-if="autoFillLoading" height="2.5rem" />
                <Select
                  v-else
                  v-model="form.product_id"
                  :options="productOptions"
                  optionLabel="label"
                  optionValue="value"
                  class="w-full"
                  filter
                  placeholder="Select product"
                  :class="{ 'p-invalid': errors.product_id }"
                />
              </div>
              <small v-if="errors.product_id" class="p-error">{{ errors.product_id[0] }}</small>
            </div>

            <div>
              <label class="text-xs font-semibold text-gray-600">Basis Type <span class="text-red-500">*</span></label>
              <Select v-model="form.basis_type" :options="basisTypeOptions" optionLabel="label" optionValue="value" class="w-full mt-1" />
            </div>

            <div>
              <label class="text-xs font-semibold text-gray-600">Reorder Point <span class="text-red-500">*</span></label>
              <InputNumber v-model="form.reorder_point" class="w-full mt-1" :min="0" :minFractionDigits="0" :maxFractionDigits="2" />
            </div>

            <div>
              <label class="text-xs font-semibold text-gray-600">Reorder Quantity</label>
              <InputNumber v-model="form.reorder_quantity" class="w-full mt-1" :min="0" :minFractionDigits="0" :maxFractionDigits="2" />
            </div>

            <div>
              <label class="text-xs font-semibold text-gray-600">Safety Stock</label>
              <InputNumber v-model="form.safety_stock" class="w-full mt-1" :min="0" :minFractionDigits="0" :maxFractionDigits="2" />
            </div>

            <div>
              <label class="text-xs font-semibold text-gray-600">Maximum Stock</label>
              <InputNumber v-model="form.maximum_stock" class="w-full mt-1" :min="0" :minFractionDigits="0" :maxFractionDigits="2" />
            </div>

            <div>
              <label class="text-xs font-semibold text-gray-600">Lead Time (Days)</label>
              <InputNumber v-model="form.lead_time_days" class="w-full mt-1" :min="1" :max="365" />
            </div>

            <div v-if="form.basis_type === 'demand_lead_time'">
              <label class="text-xs font-semibold text-gray-600">Review Period (Days)</label>
              <InputNumber v-model="form.review_period_days" class="w-full mt-1" :min="1" :max="365" />
            </div>

            <div>
              <label class="text-xs font-semibold text-gray-600">Priority <span class="text-red-500">*</span></label>
              <Select v-model="form.priority" :options="priorityOptions" optionLabel="label" optionValue="value" class="w-full mt-1" />
            </div>
          </div>

          <Message v-if="form.basis_type === 'demand_lead_time'" severity="info" :closable="false">
            Average Daily Demand is auto-computed by the backend from recent sales history.
          </Message>

          <div class="flex items-center gap-6 pt-1">
            <div class="flex items-center gap-2">
              <Checkbox v-model="form.is_active" binary inputId="is_active" />
              <label for="is_active" class="text-sm text-gray-700">Active</label>
            </div>
            <div class="flex items-center gap-2">
              <Checkbox v-model="form.auto_generate_po" binary inputId="auto_generate_po" />
              <label for="auto_generate_po" class="text-sm text-gray-700">Auto-generate PR/PO</label>
            </div>
          </div>

          <div>
            <label class="text-xs font-semibold text-gray-600">Notes</label>
            <Textarea v-model="form.notes" rows="3" class="w-full mt-1" />
          </div>

          <div class="flex justify-end gap-2 pt-2 border-t">
            <Button label="Cancel" severity="secondary" outlined @click="goBack" type="button" />
            <Button label="Create Rule" :loading="saving" type="submit" />
          </div>
        </form>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/stores/auth'
import inventoryService from '@/services/inventory.service'
import axios from 'axios'

const router = useRouter()
const route = useRoute()
const toast = useToast()
const authStore = useAuthStore()

const saving = ref(false)
const autoFillLoading = ref(true)
const errors = ref<any>({})
const productOptions = ref<any[]>([])
const fetchedBranchName = ref('')
const resolvedBranchId = ref(0)

const branchId = Number(
  (authStore.user as any)?.employee?.branch_id ||
  (authStore.user as any)?.branch?.id ||
  (authStore.user as any)?.branch_id ||
  (authStore.user as any)?.employee_branch_id ||
  0
)

const branchLabel =
  (authStore.user as any)?.branch?.name ||
  (authStore.user as any)?.employee?.branch?.name ||
  (authStore.user as any)?.branch_name ||
  (authStore.user as any)?.employee?.branch_name ||
  `Branch #${branchId || 'N/A'}`

const displayBranchLabel = computed(
  () => fetchedBranchName.value || branchLabel || `Branch #${form.branch_id || 'N/A'}`
)

const form = reactive({
  product_id: null as number | null,
  branch_id: branchId || null,
  rule_type: 'automatic',
  trigger_type: 'reorder_point',
  basis_type: 'reorder_point',
  reorder_point: 10,
  reorder_quantity: 20,
  lead_time_days: 7,
  review_period_days: 7,
  safety_stock: 5,
  maximum_stock: 30,
  economic_order_quantity: null as number | null,
  avg_daily_demand: null as number | null,
  priority: 'medium',
  auto_generate_po: false,
  is_active: true,
  notes: '',
})

const basisTypeOptions = [
  { label: 'Reorder Point', value: 'reorder_point' },
  { label: 'Demand + Lead Time', value: 'demand_lead_time' },
]

const priorityOptions = [
  { label: 'Critical', value: 'critical' },
  { label: 'High', value: 'high' },
  { label: 'Medium', value: 'medium' },
  { label: 'Low', value: 'low' },
]

const extractRows = (payload: any): any[] => {
  if (Array.isArray(payload)) return payload
  if (Array.isArray(payload?.data)) return payload.data
  if (Array.isArray(payload?.data?.data)) return payload.data.data
  return []
}

const resolveAssignedBranchIdFromEmployee = async (): Promise<number> => {
  try {
    const response = await axios.get('/api/employees/me')
    const payload: any = response?.data || {}
    const data = payload?.data || payload
    const candidate = Number(
      data?.branch_id ??
      data?.employee?.branch_id ??
      data?.branch?.id ??
      0
    )
    return Number.isFinite(candidate) ? candidate : 0
  } catch {
    return 0
  }
}

const loadBranchName = async (targetBranchId: number) => {
  try {
    const response = await inventoryService.getBranches()
    const rows = extractRows(response?.data ?? response)
    let matched = rows.find((b: any) => Number(b.id) === Number(targetBranchId))
    if (!matched && rows.length === 1) {
      matched = rows[0]
      form.branch_id = Number(matched.id)
    }
    const name = matched?.name || matched?.branch_name || matched?.branch_label
    if (name) fetchedBranchName.value = name
  } catch {
    // keep fallback label
  }
}

const loadProducts = async () => {
  try {
    const activeBranchId = form.branch_id || resolvedBranchId.value || undefined
    const [itemsRes, rulesRes] = await Promise.all([
      inventoryService.getInventoryItems({ branch_id: activeBranchId, per_page: 1000 }),
      inventoryService.getReorderRules({ branch_id: activeBranchId, per_page: 1000 }),
    ])

    const rows = extractRows(itemsRes?.data)
    const existingRules = extractRows(rulesRes?.data)
    const ruledProductIds = new Set(
      existingRules
        .map((r: any) => Number(r.product_id))
        .filter((id: number) => Number.isFinite(id) && id > 0)
    )

    const map = new Map<number, any>()
    rows.forEach((row: any) => {
      const id = Number(row.product_id || row.product?.id || 0)
      if (!id || map.has(id) || ruledProductIds.has(id)) return
      const sku = row.product?.sku || row.sku || ''
      const name = row.product?.product_name || row.product_name || row.name || `Product #${id}`
      map.set(id, { value: id, label: `${name}${sku ? ` (${sku})` : ''}` })
    })
    productOptions.value = Array.from(map.values())
  } catch {
    productOptions.value = []
  }
}

const submit = async () => {
  saving.value = true
  errors.value = {}
  try {
    const payload = {
      ...form,
      rule_type: form.basis_type === 'demand_lead_time' ? 'demand_based' : 'automatic',
      trigger_type: form.basis_type === 'demand_lead_time' ? 'forecast' : 'reorder_point',
      branch_id: form.branch_id || branchId || undefined
    }
    const res = await inventoryService.createReorderRule(payload)
    toast.add({ severity: 'success', summary: 'Success', detail: res?.message || 'Reorder rule created.', life: 2500 })
    router.push({ name: 'inventory.reorder-rules' })
  } catch (error: any) {
    if (error?.response?.data?.errors) {
      errors.value = error.response.data.errors
    }
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to create reorder rule', life: 3500 })
  } finally {
    saving.value = false
  }
}

const goBack = () => router.push({ name: 'inventory.reorder-rules' })

onMounted(() => {
  ;(async () => {
    try {
      autoFillLoading.value = true
      let targetBranchId = branchId
      if (!targetBranchId) {
        targetBranchId = await resolveAssignedBranchIdFromEmployee()
      }

      resolvedBranchId.value = targetBranchId || 0
      if (resolvedBranchId.value) {
        form.branch_id = resolvedBranchId.value
      }

      const queryBranchId = Number(route.query.branch_id || 0)
      if (queryBranchId) {
        form.branch_id = queryBranchId
      }

      await loadBranchName(form.branch_id || resolvedBranchId.value || 0)
      await loadProducts()

      const queryProductId = Number(route.query.product_id || 0)
      if (queryProductId) {
        const exists = productOptions.value.some((p: any) => Number(p.value) === queryProductId)
        if (exists) {
          form.product_id = queryProductId
        }
      }
    } finally {
      autoFillLoading.value = false
    }
  })()
})

watch(
  () => form.basis_type,
  (basis) => {
    if (basis === 'demand_lead_time') {
      form.rule_type = 'demand_based'
      form.trigger_type = 'forecast'
      if (!form.review_period_days) form.review_period_days = 7
    } else {
      form.trigger_type = 'reorder_point'
      if (form.rule_type === 'demand_based') form.rule_type = 'automatic'
    }
  }
)
</script>
