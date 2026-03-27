<template>
  <div class="space-y-6">
    <Card class="border border-blue-100 bg-blue-50/40">
      <template #content>
        <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
          <div class="space-y-1">
            <h3 class="text-base font-semibold text-gray-800">Enabled Store Modules</h3>
            <p class="text-sm text-gray-600">Permissions are filtered to these modules only.</p>
          </div>
          <div class="flex w-full flex-col gap-2 md:w-auto md:min-w-120">
            <MultiSelect
              v-model="selectedModules"
              :options="availableModuleOptions"
              optionLabel="label"
              optionValue="value"
              display="chip"
              filter
              placeholder="Select enabled modules"
              class="w-full"
            />
            <div class="flex justify-end">
              <Button label="Save Modules" icon="pi pi-check" size="small" :loading="savingModules" @click="saveModules" />
            </div>
          </div>
        </div>
      </template>
    </Card>

    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Roles & Permissions</h2>
        <p class="text-sm text-gray-500 mt-1">Manage roles and assign permissions for enabled modules only</p>
      </div>
      <Button label="Create Role" icon="pi pi-plus" @click="openCreateRoleDialog" />
    </div>

    <div class="space-y-3">
      <div class="grid grid-cols-12 gap-4 px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider bg-gray-50 rounded-lg">
        <div class="col-span-4">Role</div>
        <div class="col-span-3">Description</div>
        <div class="col-span-1 text-center">Permissions</div>
        <div class="col-span-1 text-center">Users</div>
        <div class="col-span-3 text-right">Actions</div>
      </div>

      <div v-for="role in roles" :key="role.id"
        class="grid grid-cols-12 gap-4 items-center p-4 bg-white border border-gray-200 rounded-lg hover:shadow-sm transition-shadow">
        <div class="col-span-4 flex items-center gap-3">
          <Avatar :label="role.name[0].toUpperCase()" class="bg-blue-100 text-blue-600" shape="circle" size="normal" />
          <div>
            <h3 class="font-semibold text-gray-800">{{ role.display_name }}</h3>
            <p class="text-xs text-gray-500">{{ role.name }}</p>
          </div>
          <Tag v-if="!role.store_id" value="System" severity="secondary" class="ml-2" />
        </div>

        <div class="col-span-3">
          <p class="text-sm text-gray-600 truncate">{{ role.description || '—' }}</p>
        </div>

        <div class="col-span-1 text-center">
          <Tag :value="role.permissions_count || 0" severity="info" />
        </div>

        <div class="col-span-1 text-center">
          <Tag :value="role.users_count || 0" severity="success" />
        </div>

        <div class="col-span-3 flex justify-end gap-2">
          <Button label="Permissions" icon="pi pi-cog" outlined size="small" @click="openPermissionsDialog(role)" />
          <Button icon="pi pi-ellipsis-v" text rounded size="small" @click="toggleRoleMenu($event, role)"
            aria-haspopup="true" :aria-controls="`role_menu_${role.id}`" />
        </div>
      </div>

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

    <Menu ref="roleMenu" :model="roleMenuItems" :popup="true" />

    <Dialog v-model:visible="permissionsDialog" :style="{ width: '980px' }" header="Manage Permissions" :modal="true" maximizable>
      <div v-if="selectedRole" class="space-y-4">
        <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
          <h3 class="font-semibold text-blue-900">{{ selectedRole.display_name }}</h3>
          <p class="text-sm text-blue-700">{{ selectedRole.description || 'Manage role access by module and action.' }}</p>
        </div>

      <div class="flex flex-wrap items-center gap-3 mb-4">
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

      <div v-if="loadingPermissionDialog" class="py-12 text-center text-gray-500">
        Loading permissions...
      </div>

      <div v-else-if="groupedPermissionTree.length === 0" class="py-12 text-center text-gray-500">
        No permissions available. Enable store modules first, then reopen this dialog.
      </div>

      <div v-else class="max-h-[60vh] overflow-y-auto space-y-4">
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
            <div
              v-for="submodule in module.submodules"
              :key="`${module.name}-${submodule.name}`"
              class="rounded-lg border border-gray-200"
            >
              <div class="bg-gray-50 px-3 py-2 flex items-center justify-between">
                <div class="font-medium text-gray-800">{{ submodule.display_name }}</div>
                <div class="flex items-center gap-2">
                  <small class="text-gray-600">All</small>
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
      </div>

      <template #footer>
        <Button label="Cancel" text @click="permissionsDialog = false" />
        <Button label="Save Permissions" icon="pi pi-check" :loading="savingPermissions" @click="saveRolePermissions" />
      </template>
    </Dialog>

    <Dialog v-model:visible="roleDialog" :style="{ width: '520px' }" :header="editingRole ? 'Edit Role' : 'Create Role'" modal>
      <form class="space-y-4" @submit.prevent="saveRole">
        <div>
          <label class="text-sm font-semibold">Display Name</label>
          <InputText v-model="roleForm.display_name" class="w-full" placeholder="Purchasing Manager" />
        </div>
        <div>
          <label class="text-sm font-semibold">Code</label>
          <InputText v-model="roleForm.code" class="w-full" placeholder="Optional short code" />
        </div>
        <div>
          <label class="text-sm font-semibold">Description</label>
          <Textarea v-model="roleForm.description" rows="3" class="w-full" />
        </div>
        <div class="flex items-center gap-2">
          <Checkbox v-model="roleForm.is_active" :binary="true" inputId="role_active" />
          <label for="role_active" class="text-sm text-gray-700">Active</label>
        </div>
        <div class="flex justify-end gap-2">
          <Button label="Cancel" class="p-button-secondary" @click="roleDialog = false" />
          <Button type="submit" :label="editingRole ? 'Update' : 'Create'" :loading="savingRole" />
        </div>
      </form>
    </Dialog>

    <Dialog v-model:visible="deleteRoleDialog" header="Delete Role" modal :style="{ width: '420px' }">
      <p class="text-sm text-gray-700">Are you sure you want to delete <strong>{{ roleToDelete?.display_name }}</strong>?</p>
      <div class="flex justify-end gap-2 mt-4">
        <Button label="Cancel" text @click="deleteRoleDialog = false" />
        <Button label="Delete" severity="danger" :loading="savingRole" @click="deleteRole" />
      </div>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import axiosClient from '../../../axios'
import { useAuthStore } from '../../../stores/auth'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Avatar from 'primevue/avatar'
import Tag from 'primevue/tag'
import Menu from 'primevue/menu'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Checkbox from 'primevue/checkbox'
import Select from 'primevue/select'
import MultiSelect from 'primevue/multiselect'
import Card from 'primevue/card'

const toast = useToast()
const authStore = useAuthStore()

const resolveUserStoreId = (): number | null => {
  const user = authStore.currentUser as any
  const rawStoreId = user?.store_id ?? user?.user_store_id
  const parsed = Number(rawStoreId)
  return Number.isFinite(parsed) && parsed > 0 ? parsed : null
}

const roles = ref<any[]>([])
const allPermissions = ref<any[]>([])
const selectedRole = ref<any>(null)
const selectedRolePermissions = ref<number[]>([])

const permissionsDialog = ref(false)
const savingPermissions = ref(false)
const loadingPermissionDialog = ref(false)
const permissionAssignSearch = ref('')
const selectedAssignModule = ref<string | null>(null)

const roleDialog = ref(false)
const deleteRoleDialog = ref(false)
const savingRole = ref(false)
const savingModules = ref(false)
const editingRole = ref<any>(null)
const roleToDelete = ref<any>(null)
const selectedModules = ref<string[]>([])
const availableModules = ref<string[]>([])

const roleForm = ref({
  display_name: '',
  code: '',
  description: '',
  is_active: true,
})

const formatRoleName = (displayName: string): string => {
  return displayName
    .trim()
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '_')
    .replace(/^_+|_+$/g, '')
}

const roleMenu = ref()
const roleMenuItems = ref([
  {
    label: 'Edit',
    icon: 'pi pi-pencil',
    command: () => openEditRoleDialog(selectedRole.value),
  },
  {
    label: 'Manage Permissions',
    icon: 'pi pi-cog',
    command: () => openPermissionsDialog(selectedRole.value),
  },
  {
    separator: true,
  },
  {
    label: 'Delete',
    icon: 'pi pi-trash',
    class: 'text-red-500',
    command: () => confirmDeleteRole(selectedRole.value),
  },
])

const groupedPermissionTree = computed(() => {
  const groups: Record<string, any> = {}
  const query = permissionAssignSearch.value.trim().toLowerCase()

  for (const permission of allPermissions.value as any[]) {
    const moduleName = String(permission.module || permission.name?.split('.')?.[0] || 'general')
    if (selectedAssignModule.value && selectedAssignModule.value !== moduleName) continue

    const parts = String(permission.name || '').split('.').filter(Boolean)
    const moduleIndex = parts[0] === moduleName ? 1 : 0
    const submoduleName = parts[moduleIndex] || 'general'

    const haystack = [
      moduleName,
      submoduleName,
      permission.display_name,
      permission.name,
      permission.description,
    ]
      .filter(Boolean)
      .join(' ')
      .toLowerCase()

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

const availableModuleOptions = computed(() =>
  availableModules.value.map((module) => ({
    label: module.charAt(0).toUpperCase() + module.slice(1).replace(/_/g, ' '),
    value: module,
  }))
)

const loadRoles = async () => {
  const response = await axiosClient.get('/api/store/roles/scoped', {
    params: {
      user_store_id: resolveUserStoreId(),
    },
  })
  roles.value = response.data.data || response.data
}

const loadPermissions = async () => {
  try {
    const response = await axiosClient.get('/api/store/permissions', {
      params: {
        user_store_id: resolveUserStoreId(),
      },
    })
    allPermissions.value = response.data.data || response.data
  } catch (error: any) {
    allPermissions.value = []
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Failed to load permissions', life: 3000 })
  }
}

const loadModules = async () => {
  const response = await axiosClient.get('/api/store/modules', {
    params: {
      user_store_id: resolveUserStoreId(),
    },
  })
  const data = response.data?.data || {}
  selectedModules.value = data.enabled_modules || []
  availableModules.value = data.available_modules || []
}

const saveModules = async () => {
  savingModules.value = true
  try {
    await axiosClient.put('/api/store/modules', {
      modules: selectedModules.value,
      user_store_id: resolveUserStoreId(),
    })
    toast.add({ severity: 'success', summary: 'Saved', detail: 'Enabled modules updated', life: 3000 })
    await loadPermissions()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Failed to update modules', life: 3000 })
  } finally {
    savingModules.value = false
  }
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

const openPermissionsDialog = async (role: any) => {
  selectedRole.value = role
  selectedRolePermissions.value = []
  permissionAssignSearch.value = ''
  selectedAssignModule.value = null
  permissionsDialog.value = true
  loadingPermissionDialog.value = true

  try {
    const [permissionsResponse, rolePermissionsResponse] = await Promise.all([
      axiosClient.get('/api/store/permissions', {
        params: {
          user_store_id: resolveUserStoreId(),
        },
      }),
      axiosClient.get(`/api/store/roles/${role.id}/permissions`, {
        params: {
          user_store_id: resolveUserStoreId(),
        },
      }),
    ])

    allPermissions.value = permissionsResponse.data.data || permissionsResponse.data || []
    selectedRolePermissions.value = (rolePermissionsResponse.data.permissions || []).map((p: any) => p.id)
  } catch (error: any) {
    allPermissions.value = []
    selectedRolePermissions.value = []
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Failed to load role permissions', life: 3000 })
  } finally {
    loadingPermissionDialog.value = false
  }
}

const saveRolePermissions = async () => {
  if (selectedRole.value?.store_id == null) {
    toast.add({ severity: 'warn', summary: 'Read-only', detail: 'System roles cannot be modified', life: 3000 })
    return
  }

  savingPermissions.value = true
  try {
    await axiosClient.post(`/api/store/roles/${selectedRole.value.id}/permissions`, {
      permissions: selectedRolePermissions.value,
      user_store_id: resolveUserStoreId(),
    })
    toast.add({ severity: 'success', summary: 'Success', detail: 'Permissions updated', life: 3000 })
    permissionsDialog.value = false
    await loadRoles()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Failed to update permissions', life: 3000 })
  } finally {
    savingPermissions.value = false
  }
}

const openCreateRoleDialog = () => {
  editingRole.value = null
  roleForm.value = { display_name: '', code: '', description: '', is_active: true }
  roleDialog.value = true
}

const openEditRoleDialog = (role: any) => {
  if (role?.store_id == null) {
    toast.add({ severity: 'warn', summary: 'Read-only', detail: 'System roles cannot be edited', life: 3000 })
    return
  }
  editingRole.value = role
  roleForm.value = {
    display_name: role.display_name,
    code: role.code || '',
    description: role.description || '',
    is_active: role.is_active ?? true,
  }
  roleDialog.value = true
}

const saveRole = async () => {
  savingRole.value = true
  try {
    const generatedName = formatRoleName(roleForm.value.display_name)
    if (!generatedName) {
      toast.add({ severity: 'warn', summary: 'Invalid Display Name', detail: 'Please enter a valid display name.', life: 3000 })
      return
    }

    const payload = {
      ...roleForm.value,
      name: generatedName,
      user_store_id: resolveUserStoreId(),
    }

    if (editingRole.value) {
      await axiosClient.put(`/api/store/roles/${editingRole.value.id}`, payload, {
        params: {
          user_store_id: resolveUserStoreId(),
        },
      })
      toast.add({ severity: 'success', summary: 'Updated', detail: 'Role updated', life: 3000 })
    } else {
      await axiosClient.post('/api/store/roles', payload)
      toast.add({ severity: 'success', summary: 'Created', detail: 'Role created', life: 3000 })
    }
    roleDialog.value = false
    loadRoles()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Failed to save role', life: 3000 })
  } finally {
    savingRole.value = false
  }
}

const confirmDeleteRole = (role: any) => {
  if (role?.store_id == null) {
    toast.add({ severity: 'warn', summary: 'Read-only', detail: 'System roles cannot be deleted', life: 3000 })
    return
  }
  roleToDelete.value = role
  deleteRoleDialog.value = true
}

const deleteRole = async () => {
  if (!roleToDelete.value) return
  savingRole.value = true
  try {
    await axiosClient.delete(`/api/store/roles/${roleToDelete.value.id}`, {
      params: {
        user_store_id: resolveUserStoreId(),
      },
    })
    toast.add({ severity: 'success', summary: 'Deleted', detail: 'Role deleted', life: 3000 })
    deleteRoleDialog.value = false
    await loadRoles()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Failed to delete role', life: 3000 })
  } finally {
    savingRole.value = false
  }
}

const toggleRoleMenu = (event: Event, role: any) => {
  selectedRole.value = role
  roleMenu.value.toggle(event)
}

onMounted(async () => {
  await loadModules()
  await loadRoles()
  await loadPermissions()
})
</script>
