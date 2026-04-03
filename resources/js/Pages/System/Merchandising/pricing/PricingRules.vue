<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Pricing Rules</h2>
        <p class="text-sm text-gray-500 mt-1">Set discounts and dynamic pricing strategies</p>
      </div>
      <div class="flex gap-2">
        <Button 
          label="Bulk Update" 
          icon="pi pi-upload" 
          severity="secondary"
          outlined
          @click="$router.push({ name: 'merchandising.pricing.bulk' })"
        />
        <Button 
          v-if="authStore.hasPermission('merchandising.pricing.edit')"
          label="Add Pricing Rule" 
          icon="pi pi-plus" 
          @click="openCreateDialog"
        />
      </div>
    </div>

    <!-- Active Promotions Banner -->
    <div v-if="activePromotions > 0" class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-lg p-4">
      <div class="flex items-center gap-3">
        <div class="bg-green-600 rounded-full p-3">
          <i class="pi pi-check-circle text-white text-xl"></i>
        </div>
        <div class="flex-1">
          <p class="font-semibold text-green-900">{{ activePromotions }} Active Promotions</p>
          <p class="text-sm text-green-700">Currently running pricing rules affecting your products</p>
        </div>
      </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <Card>
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Total Rules</p>
              <h3 class="text-2xl font-bold text-gray-900 mt-1">{{ stats.totalRules }}</h3>
            </div>
            <div class="bg-blue-100 p-3 rounded-full">
              <i class="pi pi-percentage text-blue-600 text-2xl"></i>
            </div>
          </div>
        </template>
      </Card>

      <Card>
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Active Rules</p>
              <h3 class="text-2xl font-bold text-green-600 mt-1">{{ stats.activeRules }}</h3>
            </div>
            <div class="bg-green-100 p-3 rounded-full">
              <i class="pi pi-check-circle text-green-600 text-2xl"></i>
            </div>
          </div>
        </template>
      </Card>

      <Card>
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Scheduled</p>
              <h3 class="text-2xl font-bold text-orange-600 mt-1">{{ stats.scheduledRules }}</h3>
            </div>
            <div class="bg-orange-100 p-3 rounded-full">
              <i class="pi pi-clock text-orange-600 text-2xl"></i>
            </div>
          </div>
        </template>
      </Card>

      <Card>
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Products Affected</p>
              <h3 class="text-2xl font-bold text-purple-600 mt-1">{{ stats.affectedProducts }}</h3>
            </div>
            <div class="bg-purple-100 p-3 rounded-full">
              <i class="pi pi-box text-purple-600 text-2xl"></i>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Filters -->
    <Card>
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <IconField>
            <InputIcon class="pi pi-search" />
            <InputText v-model="searchQuery" placeholder="Search rules..." class="w-full" @input="onSearch" />
          </IconField>

          <Select 
            v-model="filters.rule_type" 
            :options="ruleTypes" 
            optionLabel="label"
            optionValue="value"
            placeholder="All Types" 
            showClear 
            @change="loadPricingRules"
          />

          <Select 
            v-model="filters.status" 
            :options="statusOptions" 
            optionLabel="label"
            optionValue="value"
            placeholder="All Status" 
            showClear 
            @change="loadPricingRules"
          />

          <Select 
            v-model="filters.sort_by" 
            :options="sortOptions" 
            optionLabel="label"
            optionValue="value"
            placeholder="Sort by" 
            @change="loadPricingRules"
          />
        </div>
      </template>
    </Card>

    <!-- Loading State -->
    <div v-if="loading" class="space-y-3">
      <Skeleton v-for="i in 5" :key="i" height="120px" class="rounded-lg" />
    </div>

    <!-- Pricing Rules List -->
    <div v-else-if="pricingRules.length > 0" class="space-y-4">
      <Card 
        v-for="rule in pricingRules" 
        :key="rule.id"
        class="hover:shadow-lg transition-shadow"
      >
        <template #content>
          <div class="space-y-4">
            <!-- Rule Header -->
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <div class="flex items-center gap-3">
                  <h3 class="text-lg font-bold text-gray-900">{{ rule.name }}</h3>
                  <Tag :value="getRuleTypeLabel(rule.rule_type)" :severity="getRuleTypeSeverity(rule.rule_type)" />
                  <Tag :value="getStatusLabel(rule)" :severity="getStatusSeverity(rule)" />
                </div>
                <p class="text-sm text-gray-600 mt-2">{{ rule.description }}</p>
              </div>
              <div class="flex gap-1">
                <Button 
                  icon="pi pi-pencil" 
                  severity="warning"
                  text 
                  rounded 
                  v-tooltip.top="'Edit'"
                  @click="editRule(rule)"
                />
                <Button 
                  icon="pi pi-copy" 
                  severity="secondary"
                  text 
                  rounded 
                  v-tooltip.top="'Duplicate'"
                  @click="duplicateRule(rule)"
                />
                <Button 
                  :icon="rule.is_active ? 'pi pi-pause' : 'pi pi-play'" 
                  :severity="rule.is_active ? 'warning' : 'success'"
                  text 
                  rounded 
                  v-tooltip.top="rule.is_active ? 'Deactivate' : 'Activate'"
                  @click="toggleRuleStatus(rule)"
                />
                <Button 
                  icon="pi pi-trash" 
                  severity="danger"
                  text 
                  rounded 
                  v-tooltip.top="'Delete'"
                  @click="confirmDelete(rule)"
                />
              </div>
            </div>

            <!-- Rule Details -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 p-4 bg-gray-50 rounded-lg">
              <div>
                <p class="text-xs text-gray-600 mb-1">Discount Amount</p>
                <p class="text-lg font-bold text-green-600">
                  {{ rule.discount_type === 'percentage' ? `${rule.discount_value}%` : `₱${formatPrice(rule.discount_value)}` }}
                </p>
              </div>
              <div>
                <p class="text-xs text-gray-600 mb-1">Applies To</p>
                <p class="text-sm font-semibold">{{ rule.applies_to || 'All Products' }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-600 mb-1">Valid Period</p>
                <p class="text-sm font-semibold">
                  {{ rule.start_date ? formatDate(rule.start_date) : 'No start' }} - 
                  {{ rule.end_date ? formatDate(rule.end_date) : 'No end' }}
                </p>
              </div>
              <div>
                <p class="text-xs text-gray-600 mb-1">Priority</p>
                <Badge :value="rule.priority || 0" severity="info" />
              </div>
            </div>

            <!-- Conditions -->
            <div v-if="rule.conditions && rule.conditions.length > 0" class="border-t border-gray-200 pt-4">
              <p class="text-sm font-semibold text-gray-700 mb-2">Conditions:</p>
              <div class="flex flex-wrap gap-2">
                <Tag 
                  v-for="(condition, index) in rule.conditions" 
                  :key="index"
                  :value="formatCondition(condition)" 
                  severity="secondary"
                />
              </div>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Empty State -->
    <Card v-else>
      <template #content>
        <div class="text-center py-12">
          <i class="pi pi-percentage text-6xl text-gray-300"></i>
          <p class="text-gray-600 mt-4 text-lg">No pricing rules found</p>
          <p class="text-gray-500 text-sm mt-2">Create pricing rules to manage discounts and promotions</p>
          <Button 
            label="Create Your First Rule" 
            icon="pi pi-plus" 
            class="mt-4" 
            @click="openCreateDialog"
          />
        </div>
      </template>
    </Card>

    <!-- Create/Edit Dialog -->
    <Dialog 
      v-model:visible="dialogVisible" 
      :header="editMode ? 'Edit Pricing Rule' : 'Create Pricing Rule'" 
      :modal="true" 
      class="w-full max-w-3xl"
    >
      <div class="space-y-4 mt-4">
        <!-- Rule Name -->
        <div class="flex flex-col gap-2">
          <label for="rule_name" class="text-sm font-semibold text-gray-700">
            Rule Name <span class="text-red-500">*</span>
          </label>
          <InputText 
            id="rule_name"
            v-model="formData.name" 
            placeholder="e.g., Summer Sale 2024" 
            :class="{ 'p-invalid': errors.name }"
          />
          <small v-if="errors.name" class="text-red-500">{{ errors.name }}</small>
        </div>

        <!-- Description -->
        <div class="flex flex-col gap-2">
          <label for="description" class="text-sm font-semibold text-gray-700">
            Description
          </label>
          <Textarea 
            id="description"
            v-model="formData.description" 
            rows="2" 
            placeholder="Optional description..."
          />
        </div>

        <!-- Rule Type & Discount -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="flex flex-col gap-2">
            <label for="rule_type" class="text-sm font-semibold text-gray-700">
              Rule Type <span class="text-red-500">*</span>
            </label>
            <Select 
              id="rule_type"
              v-model="formData.rule_type" 
              :options="ruleTypes" 
              optionLabel="label"
              optionValue="value"
              placeholder="Select type" 
              :class="{ 'p-invalid': errors.rule_type }"
            />
          </div>

          <div class="flex flex-col gap-2">
            <label for="discount_type" class="text-sm font-semibold text-gray-700">
              Discount Type <span class="text-red-500">*</span>
            </label>
            <Select 
              id="discount_type"
              v-model="formData.discount_type" 
              :options="discountTypes" 
              optionLabel="label"
              optionValue="value"
              placeholder="Select type" 
            />
          </div>

          <div class="flex flex-col gap-2">
            <label for="discount_value" class="text-sm font-semibold text-gray-700">
              Discount Value <span class="text-red-500">*</span>
            </label>
            <InputNumber 
              id="discount_value"
              v-model="formData.discount_value" 
              :suffix="formData.discount_type === 'percentage' ? '%' : ''"
              :min="0"
              :max="formData.discount_type === 'percentage' ? 100 : undefined"
              :class="{ 'p-invalid': errors.discount_value }"
            />
          </div>
        </div>

        <!-- Date Range -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="flex flex-col gap-2">
            <label for="start_date" class="text-sm font-semibold text-gray-700">
              Start Date
            </label>
            <DatePicker 
              id="start_date"
              v-model="formData.start_date" 
              showTime 
              hourFormat="24"
              placeholder="Select start date"
            />
          </div>

          <div class="flex flex-col gap-2">
            <label for="end_date" class="text-sm font-semibold text-gray-700">
              End Date
            </label>
            <DatePicker 
              id="end_date"
              v-model="formData.end_date" 
              showTime 
              hourFormat="24"
              placeholder="Select end date"
            />
          </div>
        </div>

        <!-- Applies To -->
        <div class="flex flex-col gap-2">
          <label for="applies_to" class="text-sm font-semibold text-gray-700">
            Applies To
          </label>
          <Select 
            id="applies_to"
            v-model="formData.applies_to" 
            :options="appliesOptions" 
            placeholder="All Products" 
            showClear
          />
          <small class="text-gray-500">Leave empty to apply to all products</small>
        </div>

        <!-- Priority & Active Status -->
        <div class="grid grid-cols-2 gap-4">
          <div class="flex flex-col gap-2">
            <label for="priority" class="text-sm font-semibold text-gray-700">
              Priority
            </label>
            <InputNumber 
              id="priority"
              v-model="formData.priority" 
              :min="0"
              showButtons
            />
            <small class="text-gray-500">Higher priority rules apply first</small>
          </div>

          <div class="flex items-center gap-2 pt-6">
            <Checkbox v-model="formData.is_active" inputId="is_active" :binary="true" />
            <label for="is_active" class="text-sm font-semibold text-gray-700 cursor-pointer">
              Active
            </label>
          </div>
        </div>
      </div>

      <template #footer>
        <Button label="Cancel" severity="secondary" outlined @click="dialogVisible = false" />
        <Button :label="editMode ? 'Update' : 'Create'" icon="pi pi-check" @click="saveRule" :loading="saving" />
      </template>
    </Dialog>

    <!-- Delete Confirmation Dialog -->
    <Dialog v-model:visible="deleteDialogVisible" header="Confirm Delete" :modal="true" class="w-96">
      <div class="flex items-center gap-3">
        <i class="pi pi-exclamation-triangle text-4xl text-red-600"></i>
        <div>
          <p class="font-semibold">Are you sure you want to delete this pricing rule?</p>
          <p class="text-sm text-gray-600 mt-1">This action cannot be undone.</p>
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" text @click="deleteDialogVisible = false" />
        <Button label="Delete" severity="danger" @click="deleteRule" :loading="deleting" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '../../../../stores/auth'

import Card from 'primevue/card'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Checkbox from 'primevue/checkbox'
import DatePicker from 'primevue/datepicker'
import Dialog from 'primevue/dialog'
import Skeleton from 'primevue/skeleton'
import Tag from 'primevue/tag'
import Badge from 'primevue/badge'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'

const toast = useToast()
const authStore = useAuthStore()

// State
const pricingRules = ref([])
const loading = ref(false)
const saving = ref(false)
const deleting = ref(false)
const dialogVisible = ref(false)
const deleteDialogVisible = ref(false)
const editMode = ref(false)
const currentRule = ref(null)
const searchQuery = ref('')

const stats = reactive({
  totalRules: 0,
  activeRules: 0,
  scheduledRules: 0,
  affectedProducts: 0
})

const filters = reactive({
  rule_type: null,
  status: null,
  sort_by: 'priority_desc'
})

const formData = reactive({
  name: '',
  description: '',
  rule_type: 'discount',
  discount_type: 'percentage',
  discount_value: 0,
  start_date: null,
  end_date: null,
  applies_to: null,
  priority: 0,
  is_active: true,
  conditions: []
})

const errors = ref<Record<string, string>>({})

const ruleTypes = [
  { label: 'Percentage Discount', value: 'discount' },
  { label: 'Flash Sale', value: 'flash_sale' },
  { label: 'Bulk Pricing', value: 'bulk' },
  { label: 'BOGO (Buy One Get One)', value: 'bogo' },
  { label: 'Bundle Discount', value: 'bundle' }
]

const discountTypes = [
  { label: 'Percentage (%)', value: 'percentage' },
  { label: 'Fixed Amount (₱)', value: 'fixed' }
]

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Scheduled', value: 'scheduled' },
  { label: 'Expired', value: 'expired' },
  { label: 'Inactive', value: 'inactive' }
]

const sortOptions = [
  { label: 'Priority: High to Low', value: 'priority_desc' },
  { label: 'Priority: Low to High', value: 'priority_asc' },
  { label: 'Newest First', value: 'created_desc' },
  { label: 'Oldest First', value: 'created_asc' }
]

const appliesOptions = [
  'All Products',
  'Specific Category',
  'Specific Products',
  'Minimum Purchase Amount'
]

const activePromotions = computed(() => {
  return pricingRules.value.filter((rule: any) => rule.is_active && isRuleActive(rule)).length
})

// Methods
const loadPricingRules = async () => {
  loading.value = true
  try {
    // Mock data - replace with actual API call
    pricingRules.value = [
      {
        id: 1,
        name: 'Summer Sale 2024',
        description: '20% off on all sofas and chairs',
        rule_type: 'discount',
        discount_type: 'percentage',
        discount_value: 20,
        start_date: '2024-06-01T00:00:00',
        end_date: '2024-08-31T23:59:59',
        applies_to: 'Sofas & Chairs',
        priority: 10,
        is_active: true,
        conditions: ['Category: Sofas', 'Category: Chairs']
      },
      {
        id: 2,
        name: 'Bulk Purchase Discount',
        description: 'Buy 3 or more, get 15% off',
        rule_type: 'bulk',
        discount_type: 'percentage',
        discount_value: 15,
        start_date: null,
        end_date: null,
        applies_to: 'All Products',
        priority: 5,
        is_active: true,
        conditions: ['Minimum Quantity: 3']
      }
    ]

    calculateStats()
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load pricing rules',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const calculateStats = () => {
  stats.totalRules = pricingRules.value.length
  stats.activeRules = pricingRules.value.filter((rule: any) => rule.is_active).length
  stats.scheduledRules = pricingRules.value.filter((rule: any) => isScheduled(rule)).length
  stats.affectedProducts = 125 // Mock data
}

const onSearch = () => {
  loadPricingRules()
}

const openCreateDialog = () => {
  resetForm()
  editMode.value = false
  dialogVisible.value = true
}

const editRule = (rule: any) => {
  currentRule.value = rule
  Object.assign(formData, {
    name: rule.name,
    description: rule.description || '',
    rule_type: rule.rule_type,
    discount_type: rule.discount_type,
    discount_value: rule.discount_value,
    start_date: rule.start_date ? new Date(rule.start_date) : null,
    end_date: rule.end_date ? new Date(rule.end_date) : null,
    applies_to: rule.applies_to,
    priority: rule.priority || 0,
    is_active: rule.is_active,
    conditions: rule.conditions || []
  })
  editMode.value = true
  dialogVisible.value = true
}

const saveRule = async () => {
  if (!validate()) return

  saving.value = true
  try {
    // Mock save - replace with actual API call
    await new Promise(resolve => setTimeout(resolve, 1000))

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: editMode.value ? 'Pricing rule updated' : 'Pricing rule created',
      life: 3000
    })

    dialogVisible.value = false
    loadPricingRules()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to save pricing rule',
      life: 3000
    })
  } finally {
    saving.value = false
  }
}

const duplicateRule = (rule: any) => {
  editRule({ ...rule, name: `${rule.name} (Copy)` })
  editMode.value = false
}

const toggleRuleStatus = async (rule: any) => {
  try {
    rule.is_active = !rule.is_active
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: rule.is_active ? 'Rule activated' : 'Rule deactivated',
      life: 2000
    })
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to update rule status',
      life: 3000
    })
  }
}

const confirmDelete = (rule: any) => {
  currentRule.value = rule
  deleteDialogVisible.value = true
}

const deleteRule = async () => {
  deleting.value = true
  try {
    await new Promise(resolve => setTimeout(resolve, 1000))
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Pricing rule deleted',
      life: 3000
    })
    deleteDialogVisible.value = false
    loadPricingRules()
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to delete rule',
      life: 3000
    })
  } finally {
    deleting.value = false
  }
}

const validate = () => {
  errors.value = {}
  if (!formData.name) errors.value.name = 'Rule name is required'
  if (!formData.rule_type) errors.value.rule_type = 'Rule type is required'
  if (!formData.discount_value) errors.value.discount_value = 'Discount value is required'
  return Object.keys(errors.value).length === 0
}

const resetForm = () => {
  formData.name = ''
  formData.description = ''
  formData.rule_type = 'discount'
  formData.discount_type = 'percentage'
  formData.discount_value = 0
  formData.start_date = null
  formData.end_date = null
  formData.applies_to = null
  formData.priority = 0
  formData.is_active = true
  formData.conditions = []
  errors.value = {}
}

const getRuleTypeLabel = (type: string) => {
  const rule = ruleTypes.find(r => r.value === type)
  return rule?.label || type
}

const getRuleTypeSeverity = (type: string) => {
  const severities: Record<string, string> = {
    'discount': 'info',
    'flash_sale': 'danger',
    'bulk': 'success',
    'bogo': 'warning',
    'bundle': 'secondary'
  }
  return severities[type] || 'info'
}

const getStatusLabel = (rule: any) => {
  if (!rule.is_active) return 'Inactive'
  if (isScheduled(rule)) return 'Scheduled'
  if (isExpired(rule)) return 'Expired'
  return 'Active'
}

const getStatusSeverity = (rule: any) => {
  if (!rule.is_active) return 'secondary'
  if (isScheduled(rule)) return 'warning'
  if (isExpired(rule)) return 'danger'
  return 'success'
}

const isRuleActive = (rule: any) => {
  const now = new Date()
  const start = rule.start_date ? new Date(rule.start_date) : null
  const end = rule.end_date ? new Date(rule.end_date) : null
  
  if (start && now < start) return false
  if (end && now > end) return false
  return rule.is_active
}

const isScheduled = (rule: any) => {
  if (!rule.start_date) return false
  return new Date(rule.start_date) > new Date()
}

const isExpired = (rule: any) => {
  if (!rule.end_date) return false
  return new Date(rule.end_date) < new Date()
}

const formatCondition = (condition: string) => {
  return condition
}

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('en-PH', { 
    minimumFractionDigits: 2,
    maximumFractionDigits: 2 
  }).format(price)
}

const formatDate = (date: string) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

onMounted(() => {
  loadPricingRules()
})
</script>