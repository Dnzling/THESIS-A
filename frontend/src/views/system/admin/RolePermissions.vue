<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Roles & Permissions</h2>
        <p class="text-sm text-gray-500 mt-1">Manage user roles and access control</p>
      </div>
      <div class="flex items-center gap-2">
        <Button label="Export Roles" icon="pi pi-download" severity="secondary" @click="exportRolesCsv" />
        <FileUpload
          mode="basic"
          name="file"
          accept=".csv"
          chooseLabel="Import Roles"
          :customUpload="true"
          @uploader="importRolesCsv"
        />
        <Button label="Create Role" icon="pi pi-plus" @click="openCreateRoleDialog" />
      </div>
    </div>
  
    <!-- Tabs -->
    <Tabs value="0">
      <TabList>
        <Tab value="0">
          <i class="pi pi-users mr-2"></i>
          Roles
        </Tab>
        <Tab value="1">
          <i class="pi pi-lock mr-2"></i>
          Permissions
        </Tab>
        <Tab value="2">
          <i class="pi pi-sitemap mr-2"></i>
          Navigation Items
        </Tab>
      </TabList>
  
      <TabPanels>
        <!-- Roles Tab -->
        <TabPanel value="0">
          <div class="space-y-3 mt-6">
            <!-- List Header -->
            <div
              class="grid grid-cols-12 gap-4 px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider bg-gray-50 rounded-lg">
              <div class="col-span-4">Role</div>
              <div class="col-span-3">Description</div>
              <div class="col-span-1 text-center">Permissions</div>
              <div class="col-span-1 text-center">Users</div>
              <div class="col-span-3 text-right">Actions</div>
            </div>
  
            <!-- List Items -->
            <div v-for="role in roles" :key="role.id"
              class="grid grid-cols-12 gap-4 items-center p-4 bg-white border border-gray-200 rounded-lg hover:shadow-sm transition-shadow">
              <!-- Role Info -->
              <div class="col-span-4 flex items-center gap-3">
                <Avatar :label="role.name[0].toUpperCase()" class="bg-blue-100 text-blue-600" shape="circle"
                  size="normal" />
                <div>
                  <h3 class="font-semibold text-gray-800">{{ role.display_name }}</h3>
                  <p class="text-xs text-gray-500">{{ role.name }}</p>
                </div>
              </div>
  
              <!-- Description -->
              <div class="col-span-3">
                <p class="text-sm text-gray-600 truncate">{{ role.description || '?' }}</p>
              </div>
  
              <!-- Permissions Count -->
              <div class="col-span-1 text-center">
                <Tag :value="role.permissions_count || 0" severity="info" />
              </div>
  
              <!-- Users Count -->
              <div class="col-span-1 text-center">
                <Tag :value="role.users_count || 0" severity="success" />
              </div>
  
              <!-- Actions -->
              <div class="col-span-3 flex justify-end gap-2">
                <Button label="Permissions" icon="pi pi-cog" outlined size="small" @click="openPermissionsDialog(role)" />
                <Button icon="pi pi-ellipsis-v" text rounded size="small" @click="toggleRoleMenu($event, role)"
                  aria-haspopup="true" :aria-controls="`role_menu_${role.id}`" />
              </div>
            </div>
  
            <!-- Empty State -->
            <div v-if="roles.length === 0" class="text-center py-12">
              <div class="flex justify-center mb-4">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center">
                  <i class="pi pi-users text-2xl text-gray-400"></i>
                </div>
              </div>
              <h3 class="text-lg font-semibold text-gray-800 mb-2">No Roles Found</h3>
              <p class="text-gray-500">Get started by creating your first role</p>
            </div>
          </div>
        </TabPanel>
  
        <!-- Permissions Tab -->
        <TabPanel value="1">
          <Card class="mt-6">
            <template #title>
              <div class="flex items-center justify-between">
                <span>All Permissions</span>
                <div class="flex items-center gap-2">
                  <Button label="Export CSV" icon="pi pi-download" size="small" severity="secondary" @click="exportPermissionsCsv" />
                  <FileUpload
                    mode="basic"
                    name="file"
                    accept=".csv"
                    chooseLabel="Import CSV"
                    :customUpload="true"
                    @uploader="importPermissionsCsv"
                  />
                  <Button label="Add Permission" icon="pi pi-plus" size="small" @click="openCreatePermissionDialog" />
                </div>
              </div>
            </template>
            <template #content>
              <!-- Filter by Module -->
              <div class="mb-4 flex flex-wrap items-center gap-3">
                <InputText
                  v-model="permissionSearch"
                  placeholder="Search permissions..."
                  class="w-full md:w-1/3"
                />
                <Select v-model="selectedModule" :options="modules" optionLabel="label" optionValue="value"
                  placeholder="Filter by Module" class="w-full md:w-1/3" showClear />
                <Button
                  v-if="permissionSearch || selectedModule"
                  label="Clear"
                  icon="pi pi-times"
                  text
                  @click="clearPermissionFilters"
                />
              </div>
  
              <DataTable :value="filteredPermissions" :loading="loadingPermissions" paginator :rows="20" stripedRows
                showGridlines>
                <Column field="name" header="Permission Name" sortable style="min-width: 250px">
                  <template #body="{ data }">
                    <div class="flex items-center gap-2">
                      <i class="pi pi-lock text-gray-400"></i>
                      <code class="text-sm bg-gray-100 px-2 py-1 rounded">{{ data.name }}</code>
                    </div>
                  </template>
                </Column>
  
                <Column field="display_name" header="Display Name" sortable style="min-width: 200px" />
  
                <Column field="module" header="Module" sortable style="min-width: 150px">
                  <template #body="{ data }">
                    <Tag :value="data.module" :severity="getModuleSeverity(data.module)" />
                  </template>
                </Column>
  
                <Column field="description" header="Description" style="min-width: 250px">
                  <template #body="{ data }">
                    <span class="text-sm text-gray-600">{{ data.description || '-' }}</span>
                  </template>
                </Column>
  
                <Column field="is_active" header="Status" sortable style="min-width: 100px">
                  <template #body="{ data }">
                    <Tag :value="data.is_active ? 'Active' : 'Inactive'"
                      :severity="data.is_active ? 'success' : 'danger'" />
                  </template>
                </Column>
  
                <Column header="Actions" style="min-width: 120px">
                  <template #body="{ data }">
                    <div class="flex gap-2">
                      <Button icon="pi pi-pencil" text rounded severity="warning" @click="editPermission(data)"
                        v-tooltip.top="'Edit'" />
                      <Button icon="pi pi-trash" text rounded severity="danger" @click="confirmDeletePermission(data)"
                        v-tooltip.top="'Delete'" />
                    </div>
                  </template>
                </Column>
              </DataTable>
            </template>
          </Card>
        </TabPanel>
  
        <!-- Navigation Items Tab -->
        <TabPanel value="2">
          <Card class="mt-6">
            <template #title>
              <div class="flex items-center justify-between">
                <span>Navigation Items</span>
                <Button label="Add Navigation" icon="pi pi-plus" size="small" @click="openCreateNavigationDialog" />
              </div>
            </template>
            <template #content>
              <div class="mb-4 flex flex-wrap items-center gap-3">
                <div class="flex-1 min-w-[220px]">
                  <InputText v-model="navigationSearch" placeholder="Search navigation items..." class="w-full" />
                </div>
                <Button v-if="navigationSearch" label="Clear" icon="pi pi-times" text @click="navigationSearch = ''" />
              </div>

              <DataTable :value="filteredNavigationItems" :loading="loadingNavigation" paginator :rows="20" stripedRows>
                <Column field="display_name" header="Navigation Item" sortable style="min-width: 200px">
                  <template #body="{ data }">
                    <div class="flex items-center gap-2">
                      <i :class="data.icon || 'pi pi-circle'" class="text-blue-600"></i>
                      <span class="font-medium">{{ data.display_name }}</span>
                    </div>
                  </template>
                </Column>
  
                <Column field="route_path" header="Route" sortable style="min-width: 200px">
                  <template #body="{ data }">
                    <code class="text-sm bg-gray-100 px-2 py-1 rounded">{{ data.route_path }}</code>
                  </template>
                </Column>
  
                <Column field="module" header="Module" sortable style="min-width: 150px">
                  <template #body="{ data }">
                    <Tag :value="data.module" severity="info" />
                  </template>
                </Column>
  
                <Column field="display_order" header="Order" sortable style="min-width: 80px">
                  <template #body="{ data }">
                    <Tag :value="data.display_order" />
                  </template>
                </Column>
  
                <Column field="is_active" header="Status" sortable style="min-width: 100px">
                  <template #body="{ data }">
                    <Tag :value="data.is_active ? 'Active' : 'Inactive'"
                      :severity="data.is_active ? 'success' : 'danger'" />
                  </template>
                </Column>
  
                <Column header="Actions" style="min-width: 120px">
                  <template #body="{ data }">
                    <div class="flex gap-2">
                      <Button icon="pi pi-pencil" text rounded severity="warning" @click="editNavigation(data)"
                        v-tooltip.top="'Edit'" />
                      <Button icon="pi pi-trash" text rounded severity="danger" @click="confirmDeleteNavigation(data)"
                        v-tooltip.top="'Delete'" />
                    </div>
                  </template>
                </Column>
              </DataTable>
            </template>
          </Card>
        </TabPanel>
      </TabPanels>
    </Tabs>
  
    <!-- Role Menu -->
    <Menu ref="roleMenu" :model="roleMenuItems" :popup="true" />
  
    <!-- Manage Permissions Dialog -->
    <Dialog v-model:visible="permissionsDialog" :style="{ width: '980px' }" header="Manage Permissions" :modal="true"
      maximizable>
      <div v-if="selectedRole" class="space-y-4">
        <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
          <h3 class="font-semibold text-blue-900">{{ selectedRole.display_name }}</h3>
          <p class="text-sm text-blue-700">{{ selectedRole.description }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <InputText v-model="permissionAssignSearch" placeholder="Search module, sub module, action..." class="w-full md:w-80" />
          <Select
            v-model="selectedAssignModule"
            :options="assignableModules"
            optionLabel="label"
            optionValue="value"
            placeholder="Filter Module"
            class="w-full md:w-64"
            showClear
          />
          <Button
            v-if="permissionAssignSearch || selectedAssignModule"
            label="Clear"
            icon="pi pi-times"
            text
            @click="clearAssignFilters"
          />
        </div>

        <div v-for="module in groupedPermissionTree" :key="module.name" class="border border-gray-200 rounded-lg overflow-hidden">
          <div class="bg-gray-100 px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
              <h4 class="font-semibold text-gray-800">{{ module.display_name }}</h4>
              <Tag :value="`${module.selected}/${module.total}`" severity="info" />
            </div>
            <div class="flex items-center gap-2">
              <small class="text-gray-600">All in module</small>
              <Checkbox
                :modelValue="isModuleChecked(module)"
                :indeterminate="isModuleIndeterminate(module)"
                :binary="true"
                @update:modelValue="(checked) => toggleModuleGroup(module, checked)"
              />
            </div>
          </div>

          <div class="p-4 space-y-3">
            <div v-for="submodule in module.submodules" :key="`${module.name}-${submodule.name}`" class="rounded-lg border border-gray-200">
              <div class="bg-gray-50 px-3 py-2 flex items-center justify-between">
                <div class="font-medium text-gray-800">{{ submodule.display_name }}</div>
                <div class="flex items-center gap-2">
                  <small class="text-gray-600">All in sub module</small>
                  <Checkbox
                    :modelValue="isSubmoduleChecked(submodule)"
                    :indeterminate="isSubmoduleIndeterminate(submodule)"
                    :binary="true"
                    @update:modelValue="(checked) => toggleSubmoduleGroup(submodule, checked)"
                  />
                </div>
              </div>

              <div class="p-3 grid grid-cols-1 md:grid-cols-2 gap-2">
                <div
                  v-for="permission in submodule.permissions"
                  :key="permission.id"
                  class="flex items-center justify-between gap-3 rounded border border-gray-100 p-2"
                >
                  <span class="text-sm text-gray-700">{{ permission.display_name }}</span>
                  <Checkbox
                    :modelValue="hasPermission(permission.id)"
                    :binary="true"
                    @update:modelValue="(checked) => togglePermission(permission.id, checked)"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
  
      <template #footer>
        <Button label="Cancel" text @click="permissionsDialog = false" />
        <Button label="Save Permissions" icon="pi pi-check" @click="saveRolePermissions" :loading="savingPermissions" />
      </template>
    </Dialog>
  
    <!-- Create/Edit Permission Dialog -->
    <Dialog v-model:visible="permissionDialog" :style="{ width: '500px' }"
      :header="editingPermission ? 'Edit Permission' : 'Create Permission'" :modal="true">
      <div class="space-y-4">
        <div class="flex flex-col gap-2">
          <label class="text-sm font-semibold text-gray-700">Permission Name *</label>
          <InputText v-model="permissionForm.name" placeholder="e.g., merchandising.products.view"
            :class="{ 'p-invalid': permissionErrors.name }" />
          <small class="text-gray-500">Use dot notation: module.resource.action</small>
          <small v-if="permissionErrors.name" class="text-red-500">{{ permissionErrors.name }}</small>
        </div>
  
        <div class="flex flex-col gap-2">
          <label class="text-sm font-semibold text-gray-700">Display Name *</label>
          <InputText v-model="permissionForm.display_name" placeholder="e.g., View Products"
            :class="{ 'p-invalid': permissionErrors.display_name }" />
          <small v-if="permissionErrors.display_name" class="text-red-500">{{ permissionErrors.display_name }}</small>
        </div>
  
        <div class="flex flex-col gap-2">
          <label class="text-sm font-semibold text-gray-700">Module *</label>
          <Select v-model="permissionForm.module" :options="modules" optionLabel="label" optionValue="value"
            placeholder="Select Module" :class="{ 'p-invalid': permissionErrors.module }" />
          <small v-if="permissionErrors.module" class="text-red-500">{{ permissionErrors.module }}</small>
        </div>
  
        <div class="flex flex-col gap-2">
          <label class="text-sm font-semibold text-gray-700">Description</label>
          <Textarea v-model="permissionForm.description" rows="3" placeholder="Describe what this permission allows..." />
        </div>
  
        <div class="flex items-center gap-2">
          <Checkbox v-model="permissionForm.is_active" inputId="is_active" :binary="true" />
          <label for="is_active" class="text-sm text-gray-700">Active</label>
        </div>
      </div>
  
      <template #footer>
        <Button label="Cancel" text @click="permissionDialog = false" />
        <Button :label="editingPermission ? 'Update' : 'Create'" icon="pi pi-check" @click="savePermission"
          :loading="savingPermission" />
      </template>
    </Dialog>
  
    <!-- Create/Edit Navigation Dialog -->
    <Dialog v-model:visible="navigationDialog" :style="{ width: '600px' }"
      :header="editingNavigation ? 'Edit Navigation' : 'Create Navigation'" :modal="true">
      <div class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div class="flex flex-col gap-2">
            <label class="text-sm font-semibold text-gray-700">Name *</label>
            <InputText v-model="navigationForm.name" placeholder="e.g., merchandising.products" />
          </div>
  
          <div class="flex flex-col gap-2">
            <label class="text-sm font-semibold text-gray-700">Display Name *</label>
            <InputText v-model="navigationForm.display_name" placeholder="e.g., All Products" />
          </div>
        </div>
  
        <div class="grid grid-cols-2 gap-4">
          <div class="flex flex-col gap-2">
            <label class="text-sm font-semibold text-gray-700">Module *</label>
            <Select v-model="navigationForm.module" :options="modules" optionLabel="label" optionValue="value"
              placeholder="Select Module" />
          </div>
  
          <div class="flex flex-col gap-2">
            <label class="text-sm font-semibold text-gray-700">Icon</label>
            <InputText v-model="navigationForm.icon" placeholder="e.g., pi pi-box" />
          </div>
        </div>
  
        <div class="grid grid-cols-2 gap-4">
          <div class="flex flex-col gap-2">
            <label class="text-sm font-semibold text-gray-700">Route Name *</label>
            <InputText v-model="navigationForm.route_name" placeholder="e.g., merchandising.products" />
          </div>
  
          <div class="flex flex-col gap-2">
            <label class="text-sm font-semibold text-gray-700">Route Path *</label>
            <InputText v-model="navigationForm.route_path" placeholder="e.g., /merchandising/products" />
          </div>
        </div>
  
        <div class="grid grid-cols-2 gap-4">
          <div class="flex flex-col gap-2">
            <label class="text-sm font-semibold text-gray-700">Display Order</label>
            <InputNumber v-model="navigationForm.display_order" :min="0" />
          </div>
  
          <div class="flex flex-col gap-2">
            <label class="text-sm font-semibold text-gray-700">Parent</label>
            <Select v-model="navigationForm.parent_id" :options="parentNavigationOptions" optionLabel="display_name"
              optionValue="id" placeholder="None (Top Level)" showClear />
          </div>
        </div>
  
        <div class="flex flex-col gap-2">
          <label class="text-sm font-semibold text-gray-700">Required Permissions</label>
          <MultiSelect v-model="navigationForm.permissions" :options="allPermissions" optionLabel="display_name"
            optionValue="id" placeholder="Select permissions" display="chip" :filter="true" class="w-full" />
          <small class="text-gray-500">User must have at least one of these permissions to see this nav item</small>
        </div>
  
        <div class="flex items-center gap-2">
          <Checkbox v-model="navigationForm.is_active" inputId="nav_active" :binary="true" />
          <label for="nav_active" class="text-sm text-gray-700">Active</label>
        </div>
      </div>
  
      <template #footer>
        <Button label="Cancel" text @click="navigationDialog = false" />
        <Button :label="editingNavigation ? 'Update' : 'Create'" icon="pi pi-check" @click="saveNavigation"
          :loading="savingNavigation" />
      </template>
    </Dialog>
  
    <!-- Delete Confirmation Dialogs -->
    <Dialog v-model:visible="deletePermissionDialog" :style="{ width: '450px' }" header="Confirm Delete" :modal="true">
      <div class="flex items-center gap-4">
        <i class="pi pi-exclamation-triangle text-4xl text-orange-500"></i>
        <span>Are you sure you want to delete permission <b>{{ permissionToDelete?.name }}</b>?</span>
      </div>
      <template #footer>
        <Button label="Cancel" text @click="deletePermissionDialog = false" />
        <Button label="Delete" severity="danger" @click="deletePermission" />
      </template>
    </Dialog>
  
    <Dialog v-model:visible="deleteNavigationDialog" :style="{ width: '450px' }" header="Confirm Delete" :modal="true">
      <div class="flex items-center gap-4">
        <i class="pi pi-exclamation-triangle text-4xl text-orange-500"></i>
        <span>Are you sure you want to delete navigation <b>{{ navigationToDelete?.display_name }}</b>?</span>
      </div>
      <template #footer>
        <Button label="Cancel" text @click="deleteNavigationDialog = false" />
        <Button label="Delete" severity="danger" @click="deleteNavigation" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import axios from 'axios'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Avatar from 'primevue/avatar'
import Tag from 'primevue/tag'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import MultiSelect from 'primevue/multiselect'
import Checkbox from 'primevue/checkbox'
import Menu from 'primevue/menu'
import FileUpload from 'primevue/fileupload'
import Tabs from 'primevue/tabs'
import TabList from 'primevue/tablist'
import Tab from 'primevue/tab'
import TabPanels from 'primevue/tabpanels'
import TabPanel from 'primevue/tabpanel'

const toast = useToast()

// State
const roles = ref([])
const allPermissions = ref([])
const navigationItems = ref([])
const loadingPermissions = ref(false)
const loadingNavigation = ref(false)

const selectedModule = ref(null)
const permissionSearch = ref('')
const navigationSearch = ref('')
const selectedRole = ref(null)
const selectedRolePermissions = ref<number[]>([])
const permissionAssignSearch = ref('')
const selectedAssignModule = ref<string | null>(null)

// Dialogs
const permissionsDialog = ref(false)
const permissionDialog = ref(false)
const navigationDialog = ref(false)
const deletePermissionDialog = ref(false)
const deleteNavigationDialog = ref(false)

const savingPermissions = ref(false)
const savingPermission = ref(false)
const savingNavigation = ref(false)

// Forms
const editingPermission = ref(null)
const permissionToDelete = ref(null)
const permissionForm = ref({
  name: '',
  display_name: '',
  module: null,
  description: '',
  is_active: true
})
const permissionErrors = ref({})

const editingNavigation = ref(null)
const navigationToDelete = ref(null)
const navigationForm = ref({
  name: '',
  display_name: '',
  module: null,
  route_name: '',
  route_path: '',
  icon: '',
  parent_id: null,
  display_order: 0,
  permissions: [],
  is_active: true
})

// Role Menu
const roleMenu = ref()
const roleMenuItems = ref([
  {
    label: 'Edit',
    icon: 'pi pi-pencil',
    command: () => console.log('Edit role')
  },
  {
    label: 'Manage Permissions',
    icon: 'pi pi-cog',
    command: () => openPermissionsDialog(selectedRole.value)
  },
  {
    separator: true
  },
  {
    label: 'Delete',
    icon: 'pi pi-trash',
    class: 'text-red-500',
    command: () => console.log('Delete role')
  }
])

// Modules
const modules = ref([
  { label: 'Admin', value: 'admin' },
  { label: 'HR', value: 'hr' },
  { label: 'Merchandising', value: 'merchandising' },
  { label: 'Inventory', value: 'inventory' },
  { label: 'Sales', value: 'sales' },
  { label: 'Accounting', value: 'accounting' },
   { label: 'Finance', value: 'finance' },
  { label: 'Procurement', value: 'procurement' },
  { label: 'Supplier', value: 'supplier' },
  { label: 'Store Manager', value: 'store_manager' }
])

// Computed
const filteredPermissions = computed(() => {
  let rows = [...allPermissions.value]

  if (selectedModule.value) {
    rows = rows.filter((p: any) => p.module === selectedModule.value)
  }

  const query = permissionSearch.value.trim().toLowerCase()
  if (query) {
    rows = rows.filter((p: any) => {
      const haystack = [
        p.name,
        p.display_name,
        p.module,
        p.description,
      ]
        .filter(Boolean)
        .join(' ')
        .toLowerCase()
      return haystack.includes(query)
    })
  }

  return rows
})

const permissionsByModule = computed(() => {
  const grouped = allPermissions.value.reduce((acc, permission) => {
    if (!acc[permission.module]) {
      acc[permission.module] = {
        name: permission.module,
        display_name: permission.module.charAt(0).toUpperCase() + permission.module.slice(1),
        permissions: [],
        total: 0,
        selected: 0
      }
    }
    acc[permission.module].permissions.push(permission)
    acc[permission.module].total++
    if (selectedRolePermissions.value.includes(permission.id)) {
      acc[permission.module].selected++
    }
    return acc
  }, {})

  return Object.values(grouped)
})

const groupedPermissionTree = computed(() => {
  const groups: Record<string, any> = {}
  const query = permissionAssignSearch.value.trim().toLowerCase()

  for (const permission of allPermissions.value as any[]) {
    const moduleName = String(permission.module || permission.name?.split('.')?.[0] || 'general')
    if (selectedAssignModule.value && selectedAssignModule.value !== moduleName) continue

    const parts = String(permission.name || '').split('.').filter(Boolean)
    const moduleStartIndex = parts[0] === moduleName ? 1 : 0
    const submoduleName = parts[moduleStartIndex] || 'general'

    const haystack = [
      moduleName,
      submoduleName,
      permission.display_name,
      permission.name,
      permission.description,
    ].filter(Boolean).join(' ').toLowerCase()

    if (query && !haystack.includes(query)) continue

    if (!groups[moduleName]) {
      groups[moduleName] = {
        name: moduleName,
        display_name: moduleName.charAt(0).toUpperCase() + moduleName.slice(1).replace(/_/g, ' '),
        submodules: {},
        total: 0,
        selected: 0,
      }
    }

    if (!groups[moduleName].submodules[submoduleName]) {
      groups[moduleName].submodules[submoduleName] = {
        name: submoduleName,
        display_name: submoduleName.charAt(0).toUpperCase() + submoduleName.slice(1).replace(/[-_]/g, ' '),
        permissions: [],
      }
    }

    groups[moduleName].submodules[submoduleName].permissions.push(permission)
    groups[moduleName].total++
    if (selectedRolePermissions.value.includes(permission.id)) {
      groups[moduleName].selected++
    }
  }

  return Object.values(groups)
    .map((module: any) => ({
      ...module,
      submodules: Object.values(module.submodules).map((submodule: any) => ({
        ...submodule,
        permissions: [...submodule.permissions].sort((a: any, b: any) =>
          String(a.display_name || '').localeCompare(String(b.display_name || ''))
        ),
      })),
    }))
    .sort((a: any, b: any) => a.display_name.localeCompare(b.display_name))
})

const assignableModules = computed(() =>
  [...new Set((allPermissions.value as any[]).map((p: any) => p.module).filter(Boolean))]
    .sort()
    .map((module: string) => ({
      label: module.charAt(0).toUpperCase() + module.slice(1).replace(/_/g, ' '),
      value: module,
    }))
)

const parentNavigationOptions = computed(() => {
  return navigationItems.value.filter(nav => !nav.parent_id)
})

const filteredNavigationItems = computed(() => {
  const query = navigationSearch.value.trim().toLowerCase()
  if (!query) return navigationItems.value

  return navigationItems.value.filter((item: any) => {
    const haystack = [
      item.display_name,
      item.name,
      item.route_path,
      item.module,
      item.section,
    ]
      .filter(Boolean)
      .join(' ')
      .toLowerCase()
    return haystack.includes(query)
  })
})

// Methods
const loadRoles = async () => {
  try {
    const response = await axios.get('/api/admin/roles')
    roles.value = response.data.data || response.data
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load roles', life: 3000 })
  }
}

const loadPermissions = async () => {
  loadingPermissions.value = true
  try {
    const response = await axios.get('/api/admin/permissions')
    allPermissions.value = response.data.data || response.data
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load permissions', life: 3000 })
  } finally {
    loadingPermissions.value = false
  }
}

const loadNavigationItems = async () => {
  loadingNavigation.value = true
  try {
    const response = await axios.get('/api/admin/navigation-items')
    navigationItems.value = response.data.data || response.data
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load navigation', life: 3000 })
  } finally {
    loadingNavigation.value = false
  }
}

const exportRolesCsv = async () => {
  try {
    const response = await axios.get('/api/admin/roles/export', { responseType: 'blob' })
    downloadBlob(response.data, 'roles.csv')
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to export roles', life: 3000 })
  }
}

const importRolesCsv = async (event: any) => {
  try {
    const file = event.files?.[0]
    if (!file) return
    const formData = new FormData()
    formData.append('file', file)
    await axios.post('/api/admin/roles/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    toast.add({ severity: 'success', summary: 'Success', detail: 'Roles imported successfully', life: 3000 })
    loadRoles()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Failed to import roles', life: 3000 })
  }
}

const exportPermissionsCsv = async () => {
  try {
    const response = await axios.get('/api/admin/permissions/export', { responseType: 'blob' })
    downloadBlob(response.data, 'permissions.csv')
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to export permissions', life: 3000 })
  }
}

const importPermissionsCsv = async (event: any) => {
  try {
    const file = event.files?.[0]
    if (!file) return
    const formData = new FormData()
    formData.append('file', file)
    await axios.post('/api/admin/permissions/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    toast.add({ severity: 'success', summary: 'Success', detail: 'Permissions imported successfully', life: 3000 })
    loadPermissions()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Failed to import permissions', life: 3000 })
  }
}

const downloadBlob = (blob: Blob, filename: string) => {
  const url = window.URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.setAttribute('download', filename)
  document.body.appendChild(link)
  link.click()
  link.remove()
  window.URL.revokeObjectURL(url)
}

const openPermissionsDialog = async (role: any) => {
  selectedRole.value = role

  try {
    const response = await axios.get(`/api/admin/roles/${role.id}/permissions`)
    selectedRolePermissions.value = response.data.permissions.map((p: any) => p.id)
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load role permissions', life: 3000 })
  }

  permissionsDialog.value = true
}

const saveRolePermissions = async () => {
  savingPermissions.value = true
  try {
    await axios.post(`/api/admin/roles/${selectedRole.value.id}/permissions`, {
      permissions: selectedRolePermissions.value
    })

    toast.add({ severity: 'success', summary: 'Success', detail: 'Permissions updated successfully', life: 3000 })
    permissionsDialog.value = false
    loadRoles()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Failed to update permissions', life: 3000 })
  } finally {
    savingPermissions.value = false
  }
}

const toggleModule = (moduleName: string) => {
  const index = expandedModules.value.indexOf(moduleName)
  if (index > -1) {
    expandedModules.value.splice(index, 1)
  } else {
    expandedModules.value.push(moduleName)
  }
}

const toggleModulePermissions = (moduleName: string, checked: boolean) => {
  const module = permissionsByModule.value.find((m: any) => m.name === moduleName)
  if (!module) return

  const permissionIds = module.permissions.map((p: any) => p.id)

  if (checked) {
    // Add all module permissions
    selectedRolePermissions.value = [...new Set([...selectedRolePermissions.value, ...permissionIds])]
  } else {
    // Remove all module permissions
    selectedRolePermissions.value = selectedRolePermissions.value.filter(
      (id: number) => !permissionIds.includes(id)
    )
  }
}

const openCreatePermissionDialog = () => {
  editingPermission.value = null
  permissionForm.value = {
    name: '',
    display_name: '',
    module: null,
    description: '',
    is_active: true
  }
  permissionErrors.value = {}
  permissionDialog.value = true
}

const editPermission = (permission: any) => {
  editingPermission.value = permission
  permissionForm.value = { ...permission }
  permissionErrors.value = {}
  permissionDialog.value = true
}

const savePermission = async () => {
  permissionErrors.value = {}
  savingPermission.value = true

  try {
    if (editingPermission.value) {
      await axios.put(`/api/admin/permissions/${editingPermission.value.id}`, permissionForm.value)
      toast.add({ severity: 'success', summary: 'Success', detail: 'Permission updated successfully', life: 3000 })
    } else {
      await axios.post('/api/admin/permissions', permissionForm.value)
      toast.add({ severity: 'success', summary: 'Success', detail: 'Permission created successfully', life: 3000 })
    }

    permissionDialog.value = false
    loadPermissions()
  } catch (error: any) {
    if (error.response?.status === 422) {
      permissionErrors.value = error.response.data.errors || {}
    }
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Failed to save permission', life: 3000 })
  } finally {
    savingPermission.value = false
  }
}

const confirmDeletePermission = (permission: any) => {
  permissionToDelete.value = permission
  deletePermissionDialog.value = true
}

const deletePermission = async () => {
  try {
    await axios.delete(`/api/admin/permissions/${permissionToDelete.value.id}`)
    toast.add({ severity: 'success', summary: 'Success', detail: 'Permission deleted successfully', life: 3000 })
    deletePermissionDialog.value = false
    loadPermissions()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Failed to delete permission', life: 3000 })
  }
}

const openCreateNavigationDialog = () => {
  editingNavigation.value = null
  navigationForm.value = {
    name: '',
    display_name: '',
    module: null,
    route_name: '',
    route_path: '',
    icon: '',
    parent_id: null,
    display_order: 0,
    permissions: [],
    is_active: true
  }
  navigationDialog.value = true
}

const editNavigation = (navigation: any) => {
  editingNavigation.value = navigation
  navigationForm.value = {
    ...navigation,
    permissions: navigation.permissions?.map((p: any) => p.id) || []
  }
  navigationDialog.value = true
}

const saveNavigation = async () => {
  savingNavigation.value = true

  try {
    if (editingNavigation.value) {
      await axios.put(`/api/admin/navigation-items/${editingNavigation.value.id}`, navigationForm.value)
      toast.add({ severity: 'success', summary: 'Success', detail: 'Navigation updated successfully', life: 3000 })
    } else {
      await axios.post('/api/admin/navigation-items', navigationForm.value)
      toast.add({ severity: 'success', summary: 'Success', detail: 'Navigation created successfully', life: 3000 })
    }

    navigationDialog.value = false
    loadNavigationItems()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Failed to save navigation', life: 3000 })
  } finally {
    savingNavigation.value = false
  }
}

const confirmDeleteNavigation = (navigation: any) => {
  navigationToDelete.value = navigation
  deleteNavigationDialog.value = true
}

const deleteNavigation = async () => {
  try {
    await axios.delete(`/api/admin/navigation-items/${navigationToDelete.value.id}`)
    toast.add({ severity: 'success', summary: 'Success', detail: 'Navigation deleted successfully', life: 3000 })
    deleteNavigationDialog.value = false
    loadNavigationItems()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Failed to delete navigation', life: 3000 })
  }
}

const toggleRoleMenu = (event: Event, role: any) => {
  selectedRole.value = role
  roleMenu.value.toggle(event)
}

const hasPermission = (permissionId: number) => selectedRolePermissions.value.includes(permissionId)

const togglePermission = (permissionId: number, checked: boolean) => {
  if (checked) {
    if (!selectedRolePermissions.value.includes(permissionId)) {
      selectedRolePermissions.value.push(permissionId)
    }
    return
  }
  selectedRolePermissions.value = selectedRolePermissions.value.filter((id) => id !== permissionId)
}

const isModuleChecked = (module: any) =>
  module.total > 0 && module.selected === module.total

const isModuleIndeterminate = (module: any) =>
  module.selected > 0 && module.selected < module.total

const isSubmoduleChecked = (submodule: any) =>
  submodule.permissions.length > 0 &&
  submodule.permissions.every((permission: any) => selectedRolePermissions.value.includes(permission.id))

const isSubmoduleIndeterminate = (submodule: any) => {
  const selectedCount = submodule.permissions.filter((permission: any) =>
    selectedRolePermissions.value.includes(permission.id)
  ).length
  return selectedCount > 0 && selectedCount < submodule.permissions.length
}

const toggleModuleGroup = (module: any, checked: boolean) => {
  const modulePermissionIds = module.submodules.flatMap((sub: any) => sub.permissions.map((p: any) => p.id))
  if (checked) {
    selectedRolePermissions.value = [...new Set([...selectedRolePermissions.value, ...modulePermissionIds])]
    return
  }
  selectedRolePermissions.value = selectedRolePermissions.value.filter((id) => !modulePermissionIds.includes(id))
}

const toggleSubmoduleGroup = (submodule: any, checked: boolean) => {
  const submodulePermissionIds = submodule.permissions.map((permission: any) => permission.id)
  if (checked) {
    selectedRolePermissions.value = [...new Set([...selectedRolePermissions.value, ...submodulePermissionIds])]
    return
  }
  selectedRolePermissions.value = selectedRolePermissions.value.filter((id) => !submodulePermissionIds.includes(id))
}

const clearAssignFilters = () => {
  permissionAssignSearch.value = ''
  selectedAssignModule.value = null
}

const clearPermissionFilters = () => {
  permissionSearch.value = ''
  selectedModule.value = null
}

const openCreateRoleDialog = () => {
  // TODO: Implement create role dialog
  toast.add({ severity: 'info', summary: 'Info', detail: 'Create role feature coming soon', life: 3000 })
}

const getModuleSeverity = (module: string) => {
  const severityMap: Record<string, string> = {
    admin: 'danger',
    hr: 'info',
    merchandising: 'success',
    inventory: 'warning',
    sales: 'primary',
    accounting: 'secondary'
  }
  return severityMap[module] || 'info'
}

onMounted(() => {
  loadRoles()
  loadPermissions()
  loadNavigationItems()
})
</script>


