<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Roles & Permissions</h2>
        <p class="text-sm text-gray-500 mt-1">Manage roles for your store</p>
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

    <Dialog v-model:visible="permissionsDialog" :style="{ width: '800px' }" header="Manage Permissions" modal>
      <div v-if="selectedRole" class="mb-4">
        <p class="text-sm text-gray-600">Role: <strong>{{ selectedRole.display_name }}</strong></p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-h-[60vh] overflow-y-auto">
        <div v-for="module in permissionsByModule" :key="module.name" class="border rounded-lg p-4">
          <div class="flex items-center justify-between mb-3">
            <div class="font-semibold text-gray-800">{{ module.display_name }}</div>
            <Checkbox :binary="true" :modelValue="module.selected === module.total"
              @update:modelValue="(val) => toggleModulePermissions(module.name, val)" />
          </div>
          <div class="space-y-2">
            <div v-for="permission in module.permissions" :key="permission.id" class="flex items-center gap-2">
              <Checkbox v-model="selectedRolePermissions" :value="permission.id" />
              <span class="text-sm text-gray-700">{{ permission.display_name || permission.name }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="flex justify-end gap-2 mt-6">
        <Button label="Cancel" text @click="permissionsDialog = false" />
        <Button label="Save" :loading="savingPermissions" @click="saveRolePermissions" />
      </div>
    </Dialog>

    <Dialog v-model:visible="roleDialog" :style="{ width: '520px' }" :header="editingRole ? 'Edit Role' : 'Create Role'" modal>
      <form class="space-y-4" @submit.prevent="saveRole">
        <div>
          <label class="text-sm font-semibold">Name</label>
          <InputText v-model="roleForm.name" class="w-full" placeholder="e.g. purchasing_manager" />
        </div>
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
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Avatar from 'primevue/avatar'
import Tag from 'primevue/tag'
import Menu from 'primevue/menu'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Checkbox from 'primevue/checkbox'

const toast = useToast()

const roles = ref<any[]>([])
const allPermissions = ref<any[]>([])
const selectedRole = ref<any>(null)
const selectedRolePermissions = ref<number[]>([])

const permissionsDialog = ref(false)
const savingPermissions = ref(false)

const roleDialog = ref(false)
const deleteRoleDialog = ref(false)
const savingRole = ref(false)
const editingRole = ref<any>(null)
const roleToDelete = ref<any>(null)

const roleForm = ref({
  name: '',
  display_name: '',
  code: '',
  description: '',
  is_active: true,
})

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

const permissionsByModule = computed(() => {
  const grouped: Record<string, any> = {}
  allPermissions.value.forEach((permission: any) => {
    if (!grouped[permission.module]) {
      grouped[permission.module] = {
        name: permission.module,
        display_name: permission.module.charAt(0).toUpperCase() + permission.module.slice(1),
        permissions: [],
        total: 0,
        selected: 0,
      }
    }
    grouped[permission.module].permissions.push(permission)
    grouped[permission.module].total++
    if (selectedRolePermissions.value.includes(permission.id)) {
      grouped[permission.module].selected++
    }
  })

  return Object.values(grouped)
})

const loadRoles = async () => {
  const response = await axiosClient.get('/api/store/roles')
  roles.value = response.data.data || response.data
}

const loadPermissions = async () => {
  const response = await axiosClient.get('/api/store/permissions')
  allPermissions.value = response.data.data || response.data
}

const openPermissionsDialog = async (role: any) => {
  selectedRole.value = role
  const response = await axiosClient.get(`/api/store/roles/${role.id}/permissions`)
  selectedRolePermissions.value = response.data.permissions.map((p: any) => p.id)
  permissionsDialog.value = true
}

const saveRolePermissions = async () => {
  if (!selectedRole.value?.store_id) {
    toast.add({ severity: 'warn', summary: 'Read-only', detail: 'System roles cannot be modified', life: 3000 })
    return
  }

  savingPermissions.value = true
  try {
    await axiosClient.post(`/api/store/roles/${selectedRole.value.id}/permissions`, {
      permissions: selectedRolePermissions.value,
    })
    toast.add({ severity: 'success', summary: 'Success', detail: 'Permissions updated', life: 3000 })
    permissionsDialog.value = false
    loadRoles()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Failed to update permissions', life: 3000 })
  } finally {
    savingPermissions.value = false
  }
}

const toggleModulePermissions = (moduleName: string, checked: boolean) => {
  const module = permissionsByModule.value.find((m: any) => m.name === moduleName)
  if (!module) return
  const permissionIds = module.permissions.map((p: any) => p.id)
  if (checked) {
    selectedRolePermissions.value = [...new Set([...selectedRolePermissions.value, ...permissionIds])]
  } else {
    selectedRolePermissions.value = selectedRolePermissions.value.filter(
      (id: number) => !permissionIds.includes(id)
    )
  }
}

const openCreateRoleDialog = () => {
  editingRole.value = null
  roleForm.value = { name: '', display_name: '', code: '', description: '', is_active: true }
  roleDialog.value = true
}

const openEditRoleDialog = (role: any) => {
  if (!role?.store_id) {
    toast.add({ severity: 'warn', summary: 'Read-only', detail: 'System roles cannot be edited', life: 3000 })
    return
  }
  editingRole.value = role
  roleForm.value = {
    name: role.name,
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
    if (editingRole.value) {
      await axiosClient.put(`/api/store/roles/${editingRole.value.id}`, roleForm.value)
      toast.add({ severity: 'success', summary: 'Updated', detail: 'Role updated', life: 3000 })
    } else {
      await axiosClient.post('/api/store/roles', roleForm.value)
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
  if (!role?.store_id) {
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
    await axiosClient.delete(`/api/store/roles/${roleToDelete.value.id}`)
    toast.add({ severity: 'success', summary: 'Deleted', detail: 'Role deleted', life: 3000 })
    deleteRoleDialog.value = false
    loadRoles()
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
  await loadRoles()
  await loadPermissions()
})
</script>
