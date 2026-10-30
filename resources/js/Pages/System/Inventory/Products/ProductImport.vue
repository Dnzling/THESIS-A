<template>
  <div class="mx-auto max-w-5xl space-y-5 p-4">
    <div class="flex items-center gap-3">
      <Button icon="pi pi-arrow-left" text rounded @click="router.push({ name: 'inventory.products.index' })" />
      <div>
        <h1 class="text-lg font-bold text-gray-900">Import Products</h1>
        <p class="text-xs text-gray-500">Create multiple catalog products from a CSV file.</p>
      </div>
    </div>

    <Card class="border border-gray-200 shadow-sm">
      <template #content>
        <div class="space-y-5">
          <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
            <p class="font-semibold">Required CSV columns</p>
            <p class="mt-1 text-xs">product_name, category_id, unit_of_measurement</p>
            <p class="mt-2 text-xs text-blue-700">Optional: sku, product_type, brand, subcategory_id, cost_price, initial_stock, reorder_point, supplier_id, description.</p>
          </div>

          <label class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-gray-300 p-10 text-center transition hover:border-orange-400 hover:bg-orange-50/30">
            <i class="pi pi-file-import text-4xl text-orange-500"></i>
            <span class="mt-3 text-sm font-semibold text-gray-800">Select CSV file</span>
            <span class="mt-1 text-xs text-gray-500">The first row must contain column names.</span>
            <input type="file" accept=".csv,text/csv" class="hidden" @change="selectFile" />
          </label>

          <Message v-if="parseError" severity="error" :closable="false">{{ parseError }}</Message>

          <div v-if="rows.length" class="space-y-3">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-semibold text-gray-900">{{ fileName }}</p>
                <p class="text-xs text-gray-500">{{ rows.length }} products ready to import</p>
              </div>
              <Button label="Import Products" icon="pi pi-upload" size="small" :loading="importing" @click="importProducts" />
            </div>
            <DataTable :value="rows.slice(0, 20)" class="p-datatable-sm text-xs" scrollable>
              <Column field="product_name" header="Product" />
              <Column field="sku" header="SKU" />
              <Column field="category_id" header="Category ID" />
              <Column field="brand" header="Brand" />
              <Column field="cost_price" header="Cost" />
              <Column field="unit_of_measurement" header="Unit" />
            </DataTable>
            <p v-if="rows.length > 20" class="text-xs text-gray-500">Showing the first 20 rows.</p>
          </div>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import inventoryService from '../../../../services/inventory.service'

const router = useRouter()
const toast = useToast()
const fileName = ref('')
const rows = ref<Record<string, string>[]>([])
const parseError = ref('')
const importing = ref(false)

const parseCsvLine = (line: string) => {
  const values: string[] = []
  let value = ''
  let quoted = false
  for (let index = 0; index < line.length; index += 1) {
    const character = line[index]
    if (character === '"' && line[index + 1] === '"' && quoted) {
      value += '"'
      index += 1
    } else if (character === '"') {
      quoted = !quoted
    } else if (character === ',' && !quoted) {
      values.push(value.trim())
      value = ''
    } else {
      value += character
    }
  }
  values.push(value.trim())
  return values
}

const selectFile = async (event: Event) => {
  parseError.value = ''
  rows.value = []
  const file = (event.target as HTMLInputElement).files?.[0]
  if (!file) return
  fileName.value = file.name
  const lines = (await file.text()).replace(/^\uFEFF/, '').split(/\r?\n/).filter(line => line.trim())
  if (lines.length < 2) {
    parseError.value = 'The CSV file does not contain any product rows.'
    return
  }
  const headers = parseCsvLine(lines[0]).map(header => header.trim().toLowerCase())
  rows.value = lines.slice(1).map(line => Object.fromEntries(
    headers.map((header, index) => [header, parseCsvLine(line)[index] || ''])
  ))
  if (!headers.includes('product_name') || !headers.includes('category_id') || !headers.includes('unit_of_measurement')) {
    parseError.value = 'Missing required columns: product_name, category_id, or unit_of_measurement.'
    rows.value = []
  }
}

const importProducts = async () => {
  importing.value = true
  let imported = 0
  const failures: string[] = []
  for (const [index, row] of rows.value.entries()) {
    const payload = new FormData()
    Object.entries(row).forEach(([key, value]) => {
      if (value !== '') payload.append(key, value)
    })
    payload.set('product_type', row.product_type || 'finished_good')
    payload.set('is_active', '1')
    try {
      await inventoryService.createProduct(payload)
      imported += 1
    } catch (error: any) {
      failures.push(`Row ${index + 2}: ${error.response?.data?.message || 'Import failed'}`)
    }
  }
  importing.value = false
  toast.add({
    severity: failures.length ? 'warn' : 'success',
    summary: failures.length ? 'Import completed with errors' : 'Import complete',
    detail: `${imported} of ${rows.value.length} products imported.`,
    life: 5000
  })
  if (!failures.length) router.push({ name: 'inventory.products.index' })
  else parseError.value = failures.slice(0, 5).join(' | ')
}
</script>
