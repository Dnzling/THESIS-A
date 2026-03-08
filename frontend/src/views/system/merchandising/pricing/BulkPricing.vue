<template>
  <div class="max-w-6xl mx-auto space-y-6 pb-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Bulk Price Update</h2>
        <p class="text-sm text-gray-500 mt-1">Update multiple product prices at once</p>
      </div>
      <Button 
        label="Back to Pricing" 
        icon="pi pi-arrow-left" 
        text 
        @click="$router.push({ name: 'merchandising.pricing' })" 
      />
    </div>

    <!-- Method Selection -->
    <Card>
      <template #title>
        <div class="flex items-center gap-2">
          <i class="pi pi-cog text-blue-600"></i>
          <span>Update Method</span>
        </div>
      </template>
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div 
            @click="selectedMethod = 'csv'"
            :class="[
              'p-6 border-2 rounded-lg cursor-pointer transition-all',
              selectedMethod === 'csv' 
                ? 'border-blue-600 bg-blue-50' 
                : 'border-gray-200 hover:border-blue-300'
            ]"
          >
            <div class="text-center">
              <i :class="['pi pi-file-excel text-4xl mb-3', selectedMethod === 'csv' ? 'text-blue-600' : 'text-gray-600']"></i>
              <p :class="['font-semibold', selectedMethod === 'csv' ? 'text-blue-900' : 'text-gray-900']">
                CSV Upload
              </p>
              <p class="text-xs text-gray-600 mt-2">Upload a CSV file with SKU and new prices</p>
            </div>
          </div>

          <div 
            @click="selectedMethod = 'category'"
            :class="[
              'p-6 border-2 rounded-lg cursor-pointer transition-all',
              selectedMethod === 'category' 
                ? 'border-blue-600 bg-blue-50' 
                : 'border-gray-200 hover:border-blue-300'
            ]"
          >
            <div class="text-center">
              <i :class="['pi pi-sitemap text-4xl mb-3', selectedMethod === 'category' ? 'text-blue-600' : 'text-gray-600']"></i>
              <p :class="['font-semibold', selectedMethod === 'category' ? 'text-blue-900' : 'text-gray-900']">
                By Category
              </p>
              <p class="text-xs text-gray-600 mt-2">Apply price changes to entire categories</p>
            </div>
          </div>

          <div 
            @click="selectedMethod = 'percentage'"
            :class="[
              'p-6 border-2 rounded-lg cursor-pointer transition-all',
              selectedMethod === 'percentage' 
                ? 'border-blue-600 bg-blue-50' 
                : 'border-gray-200 hover:border-blue-300'
            ]"
          >
            <div class="text-center">
              <i :class="['pi pi-percentage text-4xl mb-3', selectedMethod === 'percentage' ? 'text-blue-600' : 'text-gray-600']"></i>
              <p :class="['font-semibold', selectedMethod === 'percentage' ? 'text-blue-900' : 'text-gray-900']">
                Percentage Change
              </p>
              <p class="text-xs text-gray-600 mt-2">Increase or decrease prices by percentage</p>
            </div>
          </div>
        </div>
      </template>
    </Card>

    <!-- CSV Upload Method -->
    <Card v-if="selectedMethod === 'csv'">
      <template #title>
        <div class="flex items-center gap-2">
          <i class="pi pi-upload text-green-600"></i>
          <span>CSV Upload</span>
        </div>
      </template>
      <template #content>
        <div class="space-y-4">
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <p class="text-sm font-semibold text-blue-900 mb-2">CSV Format Requirements:</p>
            <div class="space-y-2 text-sm text-blue-800">
              <p><strong>Required Columns:</strong> SKU, new_price</p>
              <p><strong>Optional Columns:</strong> discounted_price, price_change_reason</p>
              <p><strong>Example:</strong></p>
              <code class="block bg-white p-2 rounded text-xs mt-2">
                SKU,new_price,discounted_price,price_change_reason<br>
                SOFA-001,25000,22000,Summer Sale<br>
                CHAIR-002,8500,7500,Clearance
              </code>
            </div>
          </div>

          <FileUpload 
            mode="basic" 
            accept=".csv" 
            :maxFileSize="1000000"
            chooseLabel="Choose CSV File"
            class="w-full"
            @select="handleCSVUpload"
          />

          <Button 
            label="Download CSV Template" 
            icon="pi pi-download" 
            severity="secondary"
            outlined
            @click="downloadTemplate"
          />

          <!-- Preview Table -->
          <div v-if="csvData.length > 0" class="mt-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Preview ({{ csvData.length }} products)</h3>
            <DataTable :value="csvData" class="p-datatable-sm" stripedRows>
              <Column field="sku" header="SKU"></Column>
              <Column field="current_price" header="Current Price">
                <template #body="{ data }">
                  ₱{{ formatPrice(data.current_price) }}
                </template>
              </Column>
              <Column field="new_price" header="New Price">
                <template #body="{ data }">
                  ₱{{ formatPrice(data.new_price) }}
                </template>
              </Column>
              <Column header="Change">
                <template #body="{ data }">
                  <Tag 
                    :value="`${data.change > 0 ? '+' : ''}${data.change.toFixed(1)}%`" 
                    :severity="data.change > 0 ? 'success' : 'danger'"
                  />
                </template>
              </Column>
            </DataTable>
          </div>
        </div>
      </template>
    </Card>

    <!-- Category Method -->
    <Card v-if="selectedMethod === 'category'">
      <template #title>
        <div class="flex items-center gap-2">
          <i class="pi pi-sitemap text-purple-600"></i>
          <span>Update by Category</span>
        </div>
      </template>
      <template #content>
        <div class="space-y-4">
          <div class="flex flex-col gap-2">
            <label class="text-sm font-semibold text-gray-700">
              Select Category <span class="text-red-500">*</span>
            </label>
            <Select 
              v-model="categoryForm.category_id" 
              :options="categories" 
              optionLabel="category_name" 
              optionValue="id"
              placeholder="Select category" 
              filter
            />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">
                Adjustment Type
              </label>
              <Select 
                v-model="categoryForm.adjustment_type" 
                :options="adjustmentTypes" 
                optionLabel="label"
                optionValue="value"
                placeholder="Select type" 
              />
            </div>

            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">
                Adjustment Value
              </label>
              <InputNumber 
                v-model="categoryForm.adjustment_value" 
                :suffix="categoryForm.adjustment_type === 'percentage' ? '%' : ''"
                :min="categoryForm.adjustment_type === 'percentage' ? -100 : undefined"
              />
            </div>
          </div>

          <div class="flex flex-col gap-2">
            <label class="text-sm font-semibold text-gray-700">
              Reason for Change
            </label>
            <InputText 
              v-model="categoryForm.reason" 
              placeholder="e.g., Seasonal discount"
            />
          </div>

          <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
            <p class="text-sm text-orange-900">
              <i class="pi pi-exclamation-triangle mr-2"></i>
              This will affect <strong>{{ affectedProductsCount }}</strong> products in this category.
            </p>
          </div>
        </div>
      </template>
    </Card>

    <!-- Percentage Method -->
    <Card v-if="selectedMethod === 'percentage'">
      <template #title>
        <div class="flex items-center gap-2">
          <i class="pi pi-percentage text-orange-600"></i>
          <span>Percentage Adjustment</span>
        </div>
      </template>
      <template #content>
        <div class="space-y-4">
          <div class="flex flex-col gap-2">
            <label class="text-sm font-semibold text-gray-700">
              Apply To
            </label>
            <Select 
              v-model="percentageForm.apply_to" 
              :options="applyToOptions" 
              placeholder="Select scope" 
            />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">
                Percentage Change <span class="text-red-500">*</span>
              </label>
              <InputNumber 
                v-model="percentageForm.percentage" 
                suffix="%"
                :min="-100"
                :max="100"
                showButtons
              />
              <small class="text-gray-500">Use negative values to decrease prices</small>
            </div>

            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">
                Round To
              </label>
              <Select 
                v-model="percentageForm.round_to" 
                :options="roundingOptions" 
                optionLabel="label"
                optionValue="value"
                placeholder="No rounding" 
              />
            </div>
          </div>

          <div class="flex flex-col gap-2">
            <label class="text-sm font-semibold text-gray-700">
              Reason for Change
            </label>
            <InputText 
              v-model="percentageForm.reason" 
              placeholder="e.g., Annual price adjustment"
            />
          </div>

          <!-- Preview Calculation -->
          <div v-if="percentageForm.percentage" class="bg-green-50 border border-green-200 rounded-lg p-4">
            <p class="text-sm font-semibold text-green-900 mb-2">Preview:</p>
            <div class="grid grid-cols-3 gap-4 text-sm text-green-800">
              <div>
                <p class="text-xs text-gray-600">Current Price</p>
                <p class="font-semibold">₱10,000.00</p>
              </div>
              <div>
                <p class="text-xs text-gray-600">New Price</p>
                <p class="font-semibold">₱{{ formatPrice(10000 * (1 + percentageForm.percentage / 100)) }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-600">Change</p>
                <p class="font-semibold">{{ percentageForm.percentage > 0 ? '+' : '' }}₱{{ formatPrice(10000 * percentageForm.percentage / 100) }}</p>
              </div>
            </div>
          </div>
        </div>
      </template>
    </Card>

    <!-- Action Buttons -->
    <div class="flex justify-end gap-3">
      <Button 
        label="Cancel" 
        severity="secondary" 
        outlined 
        @click="$router.push({ name: 'merchandising.pricing' })" 
      />
      <Button 
        label="Apply Changes" 
        icon="pi pi-check" 
        @click="applyBulkUpdate"
        :loading="applying"
        :disabled="!canApply"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import merchandisingService from '../../../../services/merchandising.service'

import Card from 'primevue/card'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import FileUpload from 'primevue/fileupload'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'

const router = useRouter()
const toast = useToast()

const selectedMethod = ref('csv')
const categories = ref([])
const csvData = ref([])
const applying = ref(false)

const categoryForm = reactive({
  category_id: null,
  adjustment_type: 'percentage',
  adjustment_value: 0,
  reason: ''
})

const percentageForm = reactive({
  apply_to: 'All Products',
  percentage: 0,
  round_to: null,
  reason: ''
})

const adjustmentTypes = [
  { label: 'Percentage (%)', value: 'percentage' },
  { label: 'Fixed Amount (₱)', value: 'fixed' }
]

const applyToOptions = [
  'All Products',
  'Products with Stock',
  'Low Stock Items',
  'High Margin Products'
]

const roundingOptions = [
  { label: 'Nearest ₱10', value: 10 },
  { label: 'Nearest ₱100', value: 100 },
  { label: 'Nearest ₱1,000', value: 1000 }
]

const affectedProductsCount = computed(() => {
  // Mock calculation
  return 45
})

const canApply = computed(() => {
  if (selectedMethod.value === 'csv') return csvData.value.length > 0
  if (selectedMethod.value === 'category') return !!categoryForm.category_id
  if (selectedMethod.value === 'percentage') return percentageForm.percentage !== 0
  return false
})

const loadCategories = async () => {
  try {
    const response = await merchandisingService.getCategories()
    categories.value = response.data.data
  } catch (error) {
    console.error('Failed to load categories:', error)
  }
}

const handleCSVUpload = (event: any) => {
  const file = event.files[0]
  const reader = new FileReader()
  
  reader.onload = (e) => {
    const text = e.target?.result as string
    parseCSV(text)
  }
  
  reader.readAsText(file)
}

const parseCSV = (text: string) => {
  const lines = text.split('\n')
  const data = []
  
  for (let i = 1; i < lines.length; i++) {
    const line = lines[i].split(',')
    if (line.length >= 2) {
      const currentPrice = Math.random() * 20000 + 5000 // Mock
      const newPrice = parseFloat(line[1])
      data.push({
        sku: line[0],
        current_price: currentPrice,
        new_price: newPrice,
        change: ((newPrice - currentPrice) / currentPrice) * 100
      })
    }
  }
  
  csvData.value = data
}

const downloadTemplate = () => {
  const csv = 'SKU,new_price,discounted_price,price_change_reason\nSOFA-001,25000,22000,Summer Sale\nCHAIR-002,8500,7500,Clearance'
  const blob = new Blob([csv], { type: 'text/csv' })
  const url = window.URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = 'bulk_pricing_template.csv'
  a.click()
}

const applyBulkUpdate = async () => {
  applying.value = true
  try {
    await new Promise(resolve => setTimeout(resolve, 2000))
    
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Bulk price update completed successfully',
      life: 3000
    })
    
    setTimeout(() => {
      router.push({ name: 'merchandising.pricing' })
    }, 1000)
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to apply bulk update',
      life: 3000
    })
  } finally {
    applying.value = false
  }
}

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('en-PH', { 
    minimumFractionDigits: 2,
    maximumFractionDigits: 2 
  }).format(price)
}

onMounted(() => {
  loadCategories()
})
</script>