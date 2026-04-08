tags<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <Button 
        v-if="authStore.hasPermission('merchandising.attributes.create')"
        label="Add Attribute" 
        icon="pi pi-plus" 
        @click="openCreateDialog"
      />
    </div>

    <!-- Search & Filters -->
    <Card>
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <IconField>
            <InputIcon class="pi pi-search" />
            <InputText v-model="searchQuery" placeholder="Search attributes..." class="w-full" @input="onSearch" />
          </IconField>

          <Select 
            v-model="filters.attribute_type" 
            :options="attributeTypes" 
            placeholder="All Types" 
            showClear 
            @change="loadAttributes"
          />

          <Select 
            v-model="filters.is_filterable" 
            :options="filterableOptions" 
            optionLabel="label" 
            optionValue="value"
            placeholder="All Attributes" 
            showClear 
            @change="loadAttributes"
          />
        </div>
      </template>
    </Card>

    <!-- Loading State -->
    <div v-if="loading" class="space-y-3">
      <Skeleton v-for="i in 5" :key="i" height="100px" class="rounded-lg" />
    </div>

    <!-- Attributes List -->
    <Card v-else-if="attributes.length > 0">
      <template #content>
        <DataTable 
          :value="attributes" 
          :paginator="true" 
          :rows="15"
          :rowsPerPageOptions="[15, 25, 50]"
          dataKey="id"
          stripedRows
          class="p-datatable-sm"
        >
          <template #empty>
            <div class="text-center py-12">
              <i class="pi pi-sliders-h text-6xl text-gray-300"></i>
              <p class="text-gray-600 mt-4">No attributes found</p>
            </div>
          </template>

          <Column field="attribute_name" header="Attribute Name" sortable>
            <template #body="{ data }">
              <div>
                <p class="font-semibold text-gray-900">{{ data.attribute_name }}</p>
                <p v-if="data.description" class="text-xs text-gray-500 mt-1">{{ data.description }}</p>
              </div>
            </template>
          </Column>

          <Column field="attribute_type" header="Type" sortable>
            <template #body="{ data }">
              <Tag :value="data.attribute_type" :severity="getAttributeTypeSeverity(data.attribute_type)" />
            </template>
          </Column>

          <Column field="unit" header="Unit">
            <template #body="{ data }">
              <span v-if="data.unit" class="text-sm text-gray-600">{{ data.unit }}</span>
              <span v-else class="text-sm text-gray-400 italic">N/A</span>
            </template>
          </Column>

          <Column field="options" header="Options">
            <template #body="{ data }">
              <div v-if="data.options && data.options.length > 0" class="flex flex-wrap gap-1">
                <Tag 
                  v-for="(option, index) in data.options.slice(0, 3)" 
                  :key="index"
                  :value="option" 
                  severity="info"
                  size="small"
                />
                <Tag 
                  v-if="data.options.length > 3"
                  :value="`+${data.options.length - 3}`" 
                  severity="secondary"
                  size="small"
                />
              </div>
              <span v-else class="text-sm text-gray-400 italic">No options</span>
            </template>
          </Column>

          <Column field="is_filterable" header="Filterable">
            <template #body="{ data }">
              <Tag 
                :value="data.is_filterable ? 'Yes' : 'No'" 
                :severity="data.is_filterable ? 'success' : 'secondary'"
                size="small"
              />
            </template>
          </Column>

          <Column field="is_required" header="Required">
            <template #body="{ data }">
              <Tag 
                :value="data.is_required ? 'Yes' : 'No'" 
                :severity="data.is_required ? 'warning' : 'secondary'"
                size="small"
              />
            </template>
          </Column>

          <Column field="display_order" header="Order" sortable>
            <template #body="{ data }">
              <span class="text-sm">{{ data.display_order }}</span>
            </template>
          </Column>

          <Column field="products_count" header="Usage" sortable>
            <template #body="{ data }">
              <Badge :value="data.products_count || 0" severity="info" />
            </template>
          </Column>

          <Column header="Actions" :frozen="true" alignFrozen="right">
            <template #body="{ data }">
              <div class="flex gap-1">
                <Button 
                  icon="pi pi-pencil" 
                  severity="warning"
                  text 
                  rounded 
                  size="small"
                  v-tooltip.top="'Edit'"
                  @click="editAttribute(data)"
                />
                <Button 
                  icon="pi pi-trash" 
                  severity="danger"
                  text 
                  rounded 
                  size="small"
                  v-tooltip.top="'Delete'"
                  @click="confirmDelete(data)"
                  :disabled="data.products_count > 0"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- Empty State -->
    <Card v-else>
      <template #content>
        <div class="text-center py-12">
          <i class="pi pi-sliders-h text-6xl text-gray-300"></i>
          <p class="text-gray-600 mt-4 text-lg">No attributes found</p>
          <p class="text-gray-500 text-sm mt-2">Create attributes to add detailed specifications to your products</p>
          <Button 
            label="Create Your First Attribute" 
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
      :header="editMode ? 'Edit Attribute' : 'Create Attribute'" 
      :modal="true" 
      class="w-full max-w-2xl"
    >
      <div class="space-y-4 mt-4">
        <!-- Attribute Name -->
        <div class="flex flex-col gap-2">
          <label for="attribute_name" class="text-sm font-semibold text-gray-700">
            Attribute Name <span class="text-red-500">*</span>
          </label>
          <InputText 
            id="attribute_name"
            v-model="formData.attribute_name" 
            placeholder="e.g., Material, Color, Size" 
            :class="{ 'p-invalid': errors.attribute_name }"
          />
          <small v-if="errors.attribute_name" class="text-red-500">{{ errors.attribute_name }}</small>
        </div>

        <!-- Attribute Type -->
        <div class="flex flex-col gap-2">
          <label for="attribute_type" class="text-sm font-semibold text-gray-700">
            Attribute Type <span class="text-red-500">*</span>
          </label>
          <Select 
            id="attribute_type"
            v-model="formData.attribute_type" 
            :options="attributeTypes" 
            placeholder="Select type" 
            :class="{ 'p-invalid': errors.attribute_type }"
            @change="onAttributeTypeChange"
          />
          <small v-if="errors.attribute_type" class="text-red-500">{{ errors.attribute_type }}</small>
          <small class="text-gray-500">
            • Text: Free text input<br>
            • Number: Numeric values<br>
            • Select: Single selection from predefined options<br>
            • Multi-select: Multiple selections from options<br>
            • Color: Color picker with hex values
          </small>
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

        <!-- Unit (for Number type) -->
        <div v-if="formData.attribute_type === 'Number'" class="flex flex-col gap-2">
          <label for="unit" class="text-sm font-semibold text-gray-700">
            Unit
          </label>
          <InputText 
            id="unit"
            v-model="formData.unit" 
            placeholder="e.g., cm, kg, inches" 
          />
          <small class="text-gray-500">Optional measurement unit</small>
        </div>

        <!-- Options (for Select/Multi-select/Color) -->
        <div v-if="['Select', 'Multi-select', 'Color'].includes(formData.attribute_type)" class="flex flex-col gap-2">
          <label class="text-sm font-semibold text-gray-700">
            Options <span class="text-red-500">*</span>
          </label>
          
          <!-- Options Input -->
          <div class="space-y-2">
            <div v-for="(option, index) in formData.options" :key="index" class="flex gap-2">
              <InputText 
                v-model="formData.options[index]" 
                placeholder="Option value" 
                class="flex-1"
              />
              <Button 
                icon="pi pi-trash" 
                severity="danger"
                outlined
                @click="removeOption(index)"
                :disabled="formData.options.length === 1"
              />
            </div>
          </div>

          <Button 
            label="Add Option" 
            icon="pi pi-plus" 
            severity="secondary"
            outlined
            size="small"
            @click="addOption"
          />
          <small class="text-gray-500">Add predefined values for this attribute</small>
        </div>

        <!-- Min/Max Values (for Number type) -->
        <div v-if="formData.attribute_type === 'Number'" class="grid grid-cols-2 gap-4">
          <div class="flex flex-col gap-2">
            <label for="min_value" class="text-sm font-semibold text-gray-700">
              Minimum Value
            </label>
            <InputNumber 
              id="min_value"
              v-model="formData.min_value" 
              placeholder="0"
            />
          </div>

          <div class="flex flex-col gap-2">
            <label for="max_value" class="text-sm font-semibold text-gray-700">
              Maximum Value
            </label>
            <InputNumber 
              id="max_value"
              v-model="formData.max_value" 
              placeholder="100"
            />
          </div>
        </div>

        <!-- Display Order -->
        <div class="flex flex-col gap-2">
          <label for="display_order" class="text-sm font-semibold text-gray-700">
            Display Order
          </label>
          <InputNumber 
            id="display_order"
            v-model="formData.display_order" 
            :min="0"
            showButtons
          />
          <small class="text-gray-500">Lower numbers appear first</small>
        </div>

        <!-- Checkboxes -->
        <div class="space-y-3 pt-3 border-t border-gray-200">
          <div class="flex items-center gap-2">
            <Checkbox v-model="formData.is_filterable" inputId="is_filterable" :binary="true" />
            <label for="is_filterable" class="text-sm font-semibold text-gray-700 cursor-pointer">
              Show in Product Filters
            </label>
          </div>

          <div class="flex items-center gap-2">
            <Checkbox v-model="formData.is_required" inputId="is_required" :binary="true" />
            <label for="is_required" class="text-sm font-semibold text-gray-700 cursor-pointer">
              Required Field
            </label>
          </div>

          <div class="flex items-center gap-2">
            <Checkbox v-model="formData.is_variant_option" inputId="is_variant_option" :binary="true" />
            <label for="is_variant_option" class="text-sm font-semibold text-gray-700 cursor-pointer">
              Use for Product Variations
            </label>
          </div>
        </div>
      </div>

      <template #footer>
        <Button label="Cancel" severity="secondary" outlined @click="dialogVisible = false" />
        <Button :label="editMode ? 'Update' : 'Create'" icon="pi pi-check" @click="saveAttribute" :loading="saving" />
      </template>
    </Dialog>

    <!-- Delete Confirmation Dialog -->
    <Dialog v-model:visible="deleteDialogVisible" header="Confirm Delete" :modal="true" class="w-96">
      <div class="flex items-center gap-3">
        <i class="pi pi-exclamation-triangle text-4xl text-red-600"></i>
        <div>
          <p class="font-semibold">Are you sure you want to delete this attribute?</p>
          <p v-if="currentAttribute?.products_count > 0" class="text-sm text-red-600 mt-1">
            ⚠️ This attribute is used by {{ currentAttribute.products_count }} products!
          </p>
          <p class="text-sm text-gray-600 mt-1">This action cannot be undone.</p>
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" text @click="deleteDialogVisible = false" />
        <Button label="Delete" severity="danger" @click="deleteAttribute" :loading="deleting" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '../../../../stores/auth'
import merchandisingService from '../../../../services/merchandising.service'

const toast = useToast()
const authStore = useAuthStore()

// State
const attributes = ref([])
const loading = ref(false)
const saving = ref(false)
const deleting = ref(false)
const dialogVisible = ref(false)
const deleteDialogVisible = ref(false)
const editMode = ref(false)
const currentAttribute = ref(null)
const searchQuery = ref('')

const filters = reactive({
  attribute_type: null,
  is_filterable: null
})

const formData = reactive({
  attribute_name: '',
  attribute_type: 'Text',
  description: '',
  unit: '',
  options: [''],
  min_value: null,
  max_value: null,
  is_filterable: false,
  is_required: false,
  is_variant_option: false,
  display_order: 0
})

const errors = ref<Record<string, string>>({})

const attributeTypes = ['Text', 'Number', 'Select', 'Multi-select', 'Color']

const filterableOptions = [
  { label: 'Filterable Only', value: true },
  { label: 'Non-filterable', value: false }
]

// Methods
const loadAttributes = async () => {
  loading.value = true
  try {
    const params: any = { ...filters }
    if (searchQuery.value) params.search = searchQuery.value

    const response = await merchandisingService.getAttributes(params)
    attributes.value = response.data.data
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load attributes',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const onSearch = () => {
  loadAttributes()
}

const openCreateDialog = () => {
  resetForm()
  editMode.value = false
  dialogVisible.value = true
}

const editAttribute = (attribute: any) => {
  currentAttribute.value = attribute
  Object.assign(formData, {
    attribute_name: attribute.attribute_name,
    attribute_type: attribute.attribute_type,
    description: attribute.description || '',
    unit: attribute.unit || '',
    options: attribute.options && attribute.options.length > 0 ? [...attribute.options] : [''],
    min_value: attribute.min_value,
    max_value: attribute.max_value,
    is_filterable: attribute.is_filterable || false,
    is_required: attribute.is_required || false,
    is_variant_option: attribute.is_variant_option || false,
    display_order: attribute.display_order || 0
  })
  editMode.value = true
  dialogVisible.value = true
}

const onAttributeTypeChange = () => {
  // Reset options when changing to non-select types
  if (!['Select', 'Multi-select', 'Color'].includes(formData.attribute_type)) {
    formData.options = ['']
  }
  // Reset unit and min/max when not Number
  if (formData.attribute_type !== 'Number') {
    formData.unit = ''
    formData.min_value = null
    formData.max_value = null
  }
}

const addOption = () => {
  formData.options.push('')
}

const removeOption = (index: number) => {
  if (formData.options.length > 1) {
    formData.options.splice(index, 1)
  }
}

const saveAttribute = async () => {
  if (!validate()) return

  saving.value = true
  try {
    // Clean up options - remove empty values
    const cleanedData = {
      ...formData,
      options: ['Select', 'Multi-select', 'Color'].includes(formData.attribute_type)
        ? formData.options.filter(opt => opt.trim() !== '')
        : null
    }

    if (editMode.value) {
      await merchandisingService.updateAttribute(currentAttribute.value.id, cleanedData)
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Attribute updated successfully',
        life: 3000
      })
    } else {
      await merchandisingService.createAttribute(cleanedData)
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Attribute created successfully',
        life: 3000
      })
    }
    dialogVisible.value = false
    loadAttributes()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to save attribute',
      life: 3000
    })
  } finally {
    saving.value = false
  }
}

const confirmDelete = (attribute: any) => {
  currentAttribute.value = attribute
  deleteDialogVisible.value = true
}

const deleteAttribute = async () => {
  deleting.value = true
  try {
    await merchandisingService.deleteAttribute(currentAttribute.value.id)
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Attribute deleted successfully',
      life: 3000
    })
    deleteDialogVisible.value = false
    loadAttributes()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to delete attribute',
      life: 3000
    })
  } finally {
    deleting.value = false
  }
}

const validate = () => {
  errors.value = {}
  
  if (!formData.attribute_name) {
    errors.value.attribute_name = 'Attribute name is required'
  }
  
  if (!formData.attribute_type) {
    errors.value.attribute_type = 'Attribute type is required'
  }

  // Validate options for select types
  if (['Select', 'Multi-select', 'Color'].includes(formData.attribute_type)) {
    const validOptions = formData.options.filter(opt => opt.trim() !== '')
    if (validOptions.length === 0) {
      toast.add({
        severity: 'warn',
        summary: 'Validation Error',
        detail: 'Please add at least one option',
        life: 3000
      })
      return false
    }
  }
  
  return Object.keys(errors.value).length === 0
}

const resetForm = () => {
  formData.attribute_name = ''
  formData.attribute_type = 'Text'
  formData.description = ''
  formData.unit = ''
  formData.options = ['']
  formData.min_value = null
  formData.max_value = null
  formData.is_filterable = false
  formData.is_required = false
  formData.is_variant_option = false
  formData.display_order = 0
  errors.value = {}
}

const getAttributeTypeSeverity = (type: string) => {
  const severities: Record<string, string> = {
    'Text': 'info',
    'Number': 'success',
    'Select': 'warning',
    'Multi-select': 'danger',
    'Color': 'secondary'
  }
  return severities[type] || 'info'
}

onMounted(() => {
  loadAttributes()
})
</script>