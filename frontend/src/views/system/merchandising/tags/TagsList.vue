<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Tags & Collections</h2>
        <p class="text-sm text-gray-500 mt-1">Manage product tags for filtering and organization</p>
      </div>
      <Button 
        v-if="authStore.hasPermission('merchandising.tags.create')"
        label="Add Tag" 
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
            <InputText v-model="searchQuery" placeholder="Search tags..." class="w-full" @input="onSearch" />
          </IconField>

          <Select 
            v-model="filters.tag_type" 
            :options="tagTypes" 
            placeholder="All Types" 
            showClear 
            @change="loadTags"
          />

          <Select 
            v-model="filters.is_active" 
            :options="statusOptions" 
            optionLabel="label" 
            optionValue="value"
            placeholder="All Status" 
            showClear 
            @change="loadTags"
          />
        </div>
      </template>
    </Card>

    <!-- Loading State -->
    <div v-if="loading" class="space-y-3">
      <Skeleton v-for="i in 5" :key="i" height="80px" class="rounded-lg" />
    </div>

    <!-- Tags Grid View -->
    <div v-else-if="tags.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
      <Card 
        v-for="tag in tags" 
        :key="tag.id"
        class="hover:shadow-lg transition-shadow"
      >
        <template #content>
          <div class="space-y-3">
            <!-- Tag Header -->
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <h3 class="text-lg font-bold text-gray-900">{{ tag.tag_name }}</h3>
                <Tag :value="tag.tag_type" :severity="getTagTypeSeverity(tag.tag_type)" size="small" class="mt-2" />
              </div>
              <div class="flex gap-1">
                <Button 
                  icon="pi pi-pencil" 
                  severity="warning"
                  text 
                  rounded 
                  size="small"
                  v-tooltip.top="'Edit'"
                  @click="openEditDialog(tag)"
                />
                <Button 
                  icon="pi pi-trash" 
                  severity="danger"
                  text 
                  rounded 
                  size="small"
                  v-tooltip.top="'Delete'"
                  @click="confirmDelete(tag)"
                />
              </div>
            </div>

            <!-- Tag Stats -->
            <div class="flex items-center justify-between pt-3 border-t border-gray-200">
              <div class="flex items-center gap-2">
                <i class="pi pi-box text-gray-500"></i>
                <span class="text-sm text-gray-600">{{ tag.products_count || 0 }} products</span>
              </div>
              <Tag 
                :value="tag.is_active ? 'Active' : 'Inactive'" 
                :severity="tag.is_active ? 'success' : 'secondary'"
                size="small"
              />
            </div>

            <!-- Color Preview (if applicable) -->
            <div v-if="tag.color_hex" class="flex items-center gap-2">
              <span class="text-xs text-gray-600">Color:</span>
              <div 
                :style="{ backgroundColor: tag.color_hex }"
                class="w-8 h-8 rounded border-2 border-gray-300"
              ></div>
              <span class="text-xs font-mono text-gray-600">{{ tag.color_hex }}</span>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Empty State -->
    <Card v-else>
      <template #content>
        <div class="text-center py-12">
          <i class="pi pi-tags text-6xl text-gray-300"></i>
          <p class="text-gray-600 mt-4 text-lg">No tags found</p>
          <p class="text-gray-500 text-sm mt-2">Create tags to organize and filter your products</p>
          <Button 
            label="Create Your First Tag" 
            icon="pi pi-plus" 
            class="mt-4" 
            @click="openCreateDialog"
          />
        </div>
      </template>
    </Card>

    <!-- Pagination -->
    <div v-if="totalRecords > 15" class="flex justify-center">
      <Paginator 
        :rows="15" 
        :totalRecords="totalRecords"
        @page="onPage"
      />
    </div>

    <!-- Create/Edit Tag Dialog -->
    <Dialog 
      v-model:visible="formDialogVisible" 
      :header="editMode ? 'Edit Tag' : 'Create Tag'" 
      :modal="true" 
      class="w-full max-w-lg"
    >
      <TagForm 
        ref="tagFormRef"
        :tagId="currentTagId"
        @save="handleSave"
        @cancel="formDialogVisible = false"
      />
      
      <template #footer>
        <Button label="Cancel" severity="secondary" outlined @click="formDialogVisible = false" />
        <Button :label="editMode ? 'Update' : 'Create'" icon="pi pi-check" @click="submitForm" :loading="saving" />
      </template>
    </Dialog>

    <!-- Delete Confirmation Dialog -->
    <Dialog v-model:visible="deleteDialogVisible" header="Confirm Delete" :modal="true" class="w-96">
      <div class="flex items-center gap-3">
        <i class="pi pi-exclamation-triangle text-4xl text-red-600"></i>
        <div>
          <p class="font-semibold">Are you sure you want to delete this tag?</p>
          <p v-if="currentTag?.products_count > 0" class="text-sm text-orange-600 mt-1">
            ⚠️ This tag is used by {{ currentTag.products_count }} products. They will be untagged.
          </p>
          <p class="text-sm text-gray-600 mt-1">This action cannot be undone.</p>
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" text @click="deleteDialogVisible = false" />
        <Button label="Delete" severity="danger" @click="deleteTag" :loading="deleting" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '../../../../stores/auth'
import merchandisingService from '../../../../services/merchandising.service'
import TagForm from './TagForm.vue'

import Card from 'primevue/card'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Dialog from 'primevue/dialog'
import Skeleton from 'primevue/skeleton'
import Tag from 'primevue/tag'
import Paginator from 'primevue/paginator'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'

const toast = useToast()
const authStore = useAuthStore()

// State
const tags = ref([])
const loading = ref(false)
const saving = ref(false)
const deleting = ref(false)
const formDialogVisible = ref(false)
const deleteDialogVisible = ref(false)
const editMode = ref(false)
const currentTag = ref(null)
const currentTagId = ref(null)
const searchQuery = ref('')
const totalRecords = ref(0)
const tagFormRef = ref(null)

const filters = reactive({
  tag_type: null,
  is_active: null,
  page: 1,
  per_page: 15
})

const tagTypes = ['Style', 'Room', 'Promotion', 'Feature']

const statusOptions = [
  { label: 'Active', value: true },
  { label: 'Inactive', value: false }
]

// Methods
const loadTags = async () => {
  loading.value = true
  try {
    const params: any = { ...filters }
    if (searchQuery.value) params.search = searchQuery.value

    const response = await merchandisingService.getTags(params)
    tags.value = response.data.data
    totalRecords.value = response.data.total || tags.value.length
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load tags',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const onSearch = () => {
  filters.page = 1
  loadTags()
}

const onPage = (event: any) => {
  filters.page = event.page + 1
  loadTags()
}

const openCreateDialog = () => {
  currentTagId.value = null
  editMode.value = false
  formDialogVisible.value = true
}

const openEditDialog = (tag: any) => {
  currentTagId.value = tag.id
  editMode.value = true
  formDialogVisible.value = true
}

const submitForm = async () => {
  if (!tagFormRef.value) return
  
  saving.value = true
  const success = await tagFormRef.value.save()
  saving.value = false
  
  if (success) {
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: editMode.value ? 'Tag updated successfully' : 'Tag created successfully',
      life: 3000
    })
    formDialogVisible.value = false
    loadTags()
  }
}

const handleSave = () => {
  // This is called from the child component if needed
  formDialogVisible.value = false
  loadTags()
}

const confirmDelete = (tag: any) => {
  currentTag.value = tag
  deleteDialogVisible.value = true
}

const deleteTag = async () => {
  deleting.value = true
  try {
    await merchandisingService.deleteTag(currentTag.value.id)
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Tag deleted successfully',
      life: 3000
    })
    deleteDialogVisible.value = false
    loadTags()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to delete tag',
      life: 3000
    })
  } finally {
    deleting.value = false
  }
}

const getTagTypeSeverity = (type: string) => {
  const severities: Record<string, string> = {
    'Style': 'info',
    'Room': 'success',
    'Promotion': 'danger',
    'Feature': 'warning'
  }
  return severities[type] || 'secondary'
}

onMounted(() => {
  loadTags()
})
</script>

<style scoped>
:deep(.p-card-body) {
  padding: 1rem;
}
</style>