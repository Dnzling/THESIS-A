<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    
            <div class="flex gap-2">
                <Button label="Tree View" icon="pi pi-sitemap" :severity="viewMode === 'tree' ? 'primary' : 'secondary'"
                    outlined @click="viewMode = 'tree'" />
                <Button label="List View" icon="pi pi-list" :severity="viewMode === 'list' ? 'primary' : 'secondary'"
                    outlined @click="viewMode = 'list'" />
                <Button v-if="authStore.hasPermission('merchandising.categories.create')" label="Add Category"
                    icon="pi pi-plus" @click="openCreateDialog" class="ml-auto" />
            </div>
        </div>
    
        <!-- Search & Filters -->
        <Card>
            <template #content>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <IconField>
                        <InputIcon class="pi pi-search" />
                        <InputText v-model="searchQuery" placeholder="Search categories..." class="w-full"
                            @input="onSearch" />
                    </IconField>
    
                    <Select v-model="filters.is_active" :options="statusOptions" optionLabel="label" optionValue="value"
                        placeholder="All Status" showClear @change="loadCategories" />
    
                    <Select v-model="filters.parent_only" :options="parentFilterOptions" optionLabel="label"
                        optionValue="value" placeholder="All Categories" showClear @change="loadCategories" />
                </div>
            </template>
        </Card>
    
        <!-- Loading State -->
        <div v-if="loading" class="space-y-3">
            <Skeleton v-for="i in 5" :key="i" height="80px" class="rounded-lg" />
        </div>
    
        <!-- Tree View -->
        <Card v-else-if="viewMode === 'tree' && !loading">
            <template #content>
                <Tree :value="categoryTree" class="w-full" :pt="{
                root: { class: 'border-none' }
              }">
                    <template #default="{ node }">
                        <div class="flex items-center justify-between w-full p-2 hover:bg-gray-50 rounded">
                            <div class="flex items-center gap-3">
                                <i v-if="node.data.icon_path" :class="node.data.icon_path"
                                    class="text-xl text-gray-600"></i>
                                <div>
                                    <p class="font-semibold text-gray-900">{{ node.label }}</p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-xs font-mono text-gray-500">{{ node.data.category_code }}</span>
                                        <Badge :value="`${node.data.products_count || 0} products`" severity="info"
                                            size="small" />
                                        <Tag v-if="node.data.is_active" value="Active" severity="success" size="small" />
                                        <Tag v-else value="Inactive" severity="secondary" size="small" />
                                    </div>
                                </div>
                            </div>
                            <div class="flex gap-1">
                                <Button icon="pi pi-plus" severity="info" text rounded size="small"
                                    v-tooltip.top="'Add Subcategory'" @click="addSubcategory(node.data)" />
                                <Button icon="pi pi-pencil" severity="warning" text rounded size="small"
                                    v-tooltip.top="'Edit'" @click="editCategory(node.data)" />
                                <Button icon="pi pi-trash" severity="danger" text rounded size="small"
                                    v-tooltip.top="'Delete'" @click="confirmDelete(node.data)" />
                            </div>
                        </div>
                    </template>
                </Tree>
    
                <div v-if="categoryTree.length === 0" class="text-center py-12">
                    <i class="pi pi-folder-open text-6xl text-gray-300"></i>
                    <p class="text-gray-600 mt-4">No categories found</p>
                    <Button label="Create Your First Category" icon="pi pi-plus" class="mt-4" @click="openCreateDialog" />
                </div>
            </template>
        </Card>
    
        <!-- List View -->
        <Card v-else-if="viewMode === 'list' && !loading">
            <template #content>
                <DataTable :value="categories" :paginator="true" :rows="15" :rowsPerPageOptions="[15, 25, 50]" dataKey="id"
                    stripedRows class="p-datatable-sm">
                    <template #empty>
                        <div class="text-center py-12">
                            <i class="pi pi-folder-open text-6xl text-gray-300"></i>
                            <p class="text-gray-600 mt-4">No categories found</p>
                        </div>
                    </template>
    
                    <Column field="category_code" header="Code" sortable>
                        <template #body="{ data }">
                            <span class="font-mono text-sm font-semibold">{{ data.category_code }}</span>
                        </template>
                    </Column>
    
                    <Column field="category_name" header="Category Name" sortable>
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <i v-if="data.icon_path" :class="data.icon_path" class="text-lg text-gray-600"></i>
                                <div>
                                    <p class="font-semibold text-gray-900">{{ data.category_name }}</p>
                                    <p v-if="data.description" class="text-xs text-gray-500 mt-1">{{
                                        truncate(data.description, 50) }}</p>
                                </div>
                            </div>
                        </template>
                    </Column>
    
                    <Column field="parent.category_name" header="Parent Category">
                        <template #body="{ data }">
                            <span v-if="data.parent" class="text-sm text-gray-700">{{ data.parent.category_name }}</span>
                            <Tag v-else value="Root Category" severity="info" size="small" />
                        </template>
                    </Column>
    
                    <Column field="products_count" header="Products" sortable>
                        <template #body="{ data }">
                            <Badge :value="data.products_count || 0" severity="info" />
                        </template>
                    </Column>
    
                    <Column field="display_order" header="Order" sortable>
                        <template #body="{ data }">
                            <span class="text-sm">{{ data.display_order }}</span>
                        </template>
                    </Column>
    
                    <Column field="is_active" header="Status">
                        <template #body="{ data }">
                            <Tag :value="data.is_active ? 'Active' : 'Inactive'"
                                :severity="data.is_active ? 'success' : 'secondary'" />
                        </template>
                    </Column>
    
                    <Column header="Actions" :frozen="true" alignFrozen="right">
                        <template #body="{ data }">
                            <div class="flex gap-1">
                                <Button icon="pi pi-plus" severity="info" text rounded v-tooltip.top="'Add Subcategory'"
                                    @click="addSubcategory(data)" />
                                <Button icon="pi pi-pencil" severity="warning" text rounded v-tooltip.top="'Edit'"
                                    @click="editCategory(data)" />
                                <Button icon="pi pi-trash" severity="danger" text rounded v-tooltip.top="'Delete'"
                                    @click="confirmDelete(data)" :disabled="data.products_count > 0" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </template>
        </Card>
    
        <!-- Create/Edit Dialog -->
        <Dialog v-model:visible="dialogVisible"
            :header="editMode ? 'Edit Category' : (isSubcategory ? 'Add Subcategory' : 'Add Category')" :modal="true"
            class="w-full max-w-2xl">
            <div class="space-y-4 mt-4">
                <!-- Category Code -->
                <div class="flex flex-col gap-2">
                    <label for="category_code" class="text-sm font-semibold text-gray-700">
                        Category Code <span class="text-red-500">*</span>
                    </label>
                    <InputText id="category_code" v-model="formData.category_code" placeholder="e.g., SOFA, CHAIR, TABLE"
                        :class="{ 'p-invalid': errors.category_code }" />
                    <small v-if="errors.category_code" class="text-red-500">{{ errors.category_code }}</small>
                </div>
    
                <!-- Category Name -->
                <div class="flex flex-col gap-2">
                    <label for="category_name" class="text-sm font-semibold text-gray-700">
                        Category Name <span class="text-red-500">*</span>
                    </label>
                    <InputText id="category_name" v-model="formData.category_name" placeholder="e.g., Sofas & Couches"
                        :class="{ 'p-invalid': errors.category_name }" />
                    <small v-if="errors.category_name" class="text-red-500">{{ errors.category_name }}</small>
                </div>
    
                <!-- Parent Category -->
                <div class="flex flex-col gap-2">
                    <label for="parent_category_id" class="text-sm font-semibold text-gray-700">
                        Parent Category
                    </label>
                    <Select id="parent_category_id" v-model="formData.parent_category_id" :options="parentCategoryOptions"
                        optionLabel="category_name" optionValue="id" placeholder="None (Root Category)" showClear />
                    <small class="text-gray-500">Leave empty for root category</small>
                </div>
    
                <!-- Description -->
                <div class="flex flex-col gap-2">
                    <label for="description" class="text-sm font-semibold text-gray-700">
                        Description
                    </label>
                    <Textarea id="description" v-model="formData.description" rows="3"
                        placeholder="Category description..." />
                </div>
    
                <!-- Icon -->
                <div class="flex flex-col gap-2">
                    <label for="icon_path" class="text-sm font-semibold text-gray-700">
                        Icon (PrimeIcons class)
                    </label>
                    <div class="flex gap-2">
                        <InputText id="icon_path" v-model="formData.icon_path" placeholder="e.g., pi pi-box, pi pi-home"
                            class="flex-1" />
                        <Button label="Browse Icons" icon="pi pi-external-link" outlined @click="openIconBrowser" />
                    </div>
                    <div v-if="formData.icon_path" class="flex items-center gap-2 text-sm text-gray-600">
                        <span>Preview:</span>
                        <i :class="formData.icon_path" class="text-2xl"></i>
                    </div>
                    <small class="text-gray-500">Visit <a href="https://primevue.org/icons" target="_blank"
                            class="text-blue-600">primeicons.org</a> for icon names</small>
                </div>
    
                <!-- Display Order -->
                <div class="flex flex-col gap-2">
                    <label for="display_order" class="text-sm font-semibold text-gray-700">
                        Display Order
                    </label>
                    <InputNumber id="display_order" v-model="formData.display_order" :min="0" showButtons />
                    <small class="text-gray-500">Lower numbers appear first</small>
                </div>
    
                <!-- Active Status -->
                <div class="flex items-center gap-2">
                    <Checkbox v-model="formData.is_active" inputId="is_active" :binary="true" />
                    <label for="is_active" class="text-sm font-semibold text-gray-700 cursor-pointer">Active</label>
                </div>
            </div>
    
            <template #footer>
                <Button label="Cancel" severity="secondary" outlined @click="dialogVisible = false" />
                <Button :label="editMode ? 'Update' : 'Create'" icon="pi pi-check" @click="saveCategory"
                    :loading="saving" />
            </template>
        </Dialog>
    
        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:visible="deleteDialogVisible" header="Confirm Delete" :modal="true" class="w-96">
            <div class="flex items-center gap-3">
                <i class="pi pi-exclamation-triangle text-4xl text-red-600"></i>
                <div>
                    <p class="font-semibold">Are you sure you want to delete this category?</p>
                    <p v-if="currentCategory?.products_count > 0" class="text-sm text-red-600 mt-1">
                        ⚠️ This category has {{ currentCategory.products_count }} products!
                    </p>
                    <p class="text-sm text-gray-600 mt-1">This action cannot be undone.</p>
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" severity="secondary" text @click="deleteDialogVisible = false" />
                <Button label="Delete" severity="danger" @click="deleteCategory" :loading="deleting" />
            </template>
        </Dialog>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '../../../../stores/auth'
import merchandisingService from '../../../../services/merchandising.service'

const toast = useToast()
const authStore = useAuthStore()

// State
const categories = ref([])
const loading = ref(false)
const saving = ref(false)
const deleting = ref(false)
const dialogVisible = ref(false)
const deleteDialogVisible = ref(false)
const editMode = ref(false)
const isSubcategory = ref(false)
const currentCategory = ref(null)
const searchQuery = ref('')
const viewMode = ref('list') // 'tree' or 'list'

const filters = reactive({
  is_active: null,
  parent_only: null
})

const formData = reactive({
  category_code: '',
  category_name: '',
  description: '',
  parent_category_id: null,
  icon_path: '',
  is_active: true,
  display_order: 0
})

const errors = ref<Record<string, string>>({})

const statusOptions = [
  { label: 'Active', value: true },
  { label: 'Inactive', value: false }
]

const parentFilterOptions = [
  { label: 'Root Categories Only', value: 'root' },
  { label: 'Subcategories Only', value: 'sub' }
]

// Computed
const categoryTree = computed(() => {
  return buildTree(categories.value.filter(c => !c.parent_category_id))
})

const parentCategoryOptions = computed(() => {
  if (editMode.value && currentCategory.value) {
    return categories.value.filter(c =>
      c.id !== currentCategory.value.id &&
      c.parent_category_id !== currentCategory.value.id
    )
  }
  return categories.value.filter(c => !c.parent_category_id)
})

// Methods
const buildTree = (items: any[], parentId: number | null = null): any[] => {
  return items.map(item => {
    const children = categories.value.filter(c => c.parent_category_id === item.id)
    return {
      key: item.id,
      label: item.category_name,
      data: item,
      icon: item.icon_path,
      children: children.length > 0 ? buildTree(children, item.id) : undefined
    }
  })
}

const loadCategories = async () => {
  loading.value = true
  try {
    const params: any = {}
    if (filters.is_active !== null) params.is_active = filters.is_active
    if (filters.parent_only === 'root') params.parent_only = true
    if (filters.parent_only === 'sub') params.has_parent = true
    if (searchQuery.value) params.search = searchQuery.value

    const response = await merchandisingService.getCategories(params)
    categories.value = response.data.data
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load categories',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const onSearch = () => {
  loadCategories()
}

const openCreateDialog = () => {
  resetForm()
  editMode.value = false
  isSubcategory.value = false
  dialogVisible.value = true
}

const addSubcategory = (parent: any) => {
  resetForm()
  formData.parent_category_id = parent.id
  isSubcategory.value = true
  editMode.value = false
  dialogVisible.value = true
}

const editCategory = (category: any) => {
  currentCategory.value = category
  Object.assign(formData, {
    category_code: category.category_code,
    category_name: category.category_name,
    description: category.description || '',
    parent_category_id: category.parent_category_id,
    icon_path: category.icon_path || '',
    is_active: category.is_active,
    display_order: category.display_order || 0
  })
  editMode.value = true
  isSubcategory.value = false
  dialogVisible.value = true
}

const saveCategory = async () => {
  if (!validate()) return

  saving.value = true
  try {
    if (editMode.value) {
      await merchandisingService.updateCategory(currentCategory.value.id, formData)
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Category updated successfully',
        life: 3000
      })
    } else {
      await merchandisingService.createCategory(formData)
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Category created successfully',
        life: 3000
      })
    }
    dialogVisible.value = false
    loadCategories()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to save category',
      life: 3000
    })
  } finally {
    saving.value = false
  }
}

const confirmDelete = (category: any) => {
  currentCategory.value = category
  deleteDialogVisible.value = true
}

const deleteCategory = async () => {
  deleting.value = true
  try {
    await merchandisingService.deleteCategory(currentCategory.value.id)
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Category deleted successfully',
      life: 3000
    })
    deleteDialogVisible.value = false
    loadCategories()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to delete category',
      life: 3000
    })
  } finally {
    deleting.value = false
  }
}

const validate = () => {
  errors.value = {}

  if (!formData.category_code) {
    errors.value.category_code = 'Category code is required'
  }

  if (!formData.category_name) {
    errors.value.category_name = 'Category name is required'
  }

  return Object.keys(errors.value).length === 0
}

const resetForm = () => {
  formData.category_code = ''
  formData.category_name = ''
  formData.description = ''
  formData.parent_category_id = null
  formData.icon_path = ''
  formData.is_active = true
  formData.display_order = 0
  errors.value = {}
}

const openIconBrowser = () => {
  window.open('https://primevue.org/icons', '_blank')
}

const truncate = (text: string, length: number) => {
  return text && text.length > length ? text.substring(0, length) + '...' : text
}

onMounted(() => {
  loadCategories()
})
</script>

<style scoped>
:deep(.p-tree) {
    border: none;
    padding: 0;
}

:deep(.p-tree-node-content) {
    padding: 0.5rem;
}

:deep(.p-tree-node-content:hover) {
    background: transparent;
}
</style>