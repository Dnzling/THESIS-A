<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Roles & Permissions</h2>
        <p class="text-sm text-gray-500 mt-1">Manage store roles and module access</p>
      </div>
    </div>

    <!-- Enabled Modules -->
    <Card>
      <template #title>Enabled Modules</template>
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
          <div class="flex flex-col gap-2">
            <label class="text-xs font-semibold text-gray-600">Modules</label>
            <MultiSelect
              v-model="enabledModules"
              :options="availableModules"
              placeholder="Select modules"
              display="chip"
              class="w-full"
              :loading="loadingModules"
            />
          </div>
          <div class="flex justify-end">
            <Button
              label="Save Modules"
              icon="pi pi-check"
              :loading="savingModules"
              @click="saveModules"
            />
          </div>
        </div>
      </template>
    </Card>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      <!-- Roles -->
      <Card class="lg:col-span-4">
        <template #title>Roles</template>
        <template #content>
          <DataTable
            :value="roles"
            class="p-datatable-sm"
            selectionMode="single"
            dataKey="id"
            v-model:selection="selectedRole"
            :loading="loadingRoles"
          >
            <Column field="display_name" header="Role">
              <template #body="{ data }">
                <div class="text-sm">
                  <div class="font-semibold text-gray-900">{{ data.display_name || data.name }}</div>
                </div>
              </template>
            </Column>
            <Column field="users_count" header="Users" style="width: 90px" />
          </DataTable>
        </template>
      </Card>

      <!-- Permissions -->
      <Card class="lg:col-span-8">
        <template #title>
          <div class="flex items-center justify-between">
            <span>Permissions</span>
            <Button
              label="Save Permissions"
              icon="pi pi-check"
              size="small"
              :disabled="!selectedRole"
              :loading="savingPermissions"
              @click="savePermissions"
            />
          </div>
        </template>
        <template #content>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
            <InputText v-model="permissionSearch" placeholder="Search permissions..." />
            <Select
              v-model="selectedModule"
              :options="moduleOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Filter by module"
              showClear
            />
            <div class="text-xs text-gray-500 flex items-center">
              {{ filteredPermissions.length }} permissions
            </div>
          </div>

          <div class="max-h-[520px] overflow-y-auto border border-gray-100 rounded-lg p-3 space-y-3">
            <div
              v-for="module in groupedPermissions"
              :key="module.name"
              class="border border-gray-200 rounded-lg overflow-hidden"
            >
              <div class="bg-gray-50 px-4 py-2 flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <Button
                    :icon="expandedModules.has(module.name) ? 'pi pi-chevron-down' : 'pi pi-chevron-right'"
                    text
                    rounded
                    size="small"
                    @click="toggleModuleCollapse(module.name)"
                  />
                  <div class="font-semibold text-gray-900">{{ formatLabel(module.name) }}</div>
                  <span class="text-xs text-gray-500">{{ module.selected }}/{{ module.total }}</span>
                </div>
                <Checkbox
                  :modelValue="isModuleChecked(module)"
                  :indeterminate="isModuleIndeterminate(module)"
                  :binary="true"
                  @update:modelValue="(checked) => toggleModuleGroup(module, checked)"
                />
              </div>

              <div v-show="expandedModules.has(module.name)" class="p-3 space-y-2">
                <div
                  v-for="submodule in module.submodules"
                  :key="`${module.name}-${submodule.name}`"
                  class="border border-gray-100 rounded-md"
                >
                  <div class="flex items-center justify-between px-3 py-2 bg-white">
                    <div class="text-sm font-medium text-gray-800">
                      {{ formatLabel(submodule.name) }}
                    </div>
                    <Checkbox
                      :modelValue="isSubmoduleChecked(submodule)"
                      :indeterminate="isSubmoduleIndeterminate(submodule)"
                      :binary="true"
                      @update:modelValue="(checked) => toggleSubmoduleGroup(submodule, checked)"
                    />
                  </div>
                  <div class="divide-y">
                    <div
                      v-for="permission in submodule.permissions"
                      :key="permission.id"
                      class="flex items-center justify-between px-3 py-2"
                    >
                      <div class="text-sm">
                        <div class="font-medium text-gray-900">{{ permission.display_name }}</div>
                        <div class="text-xs text-gray-500">{{ permission.name }}</div>
                      </div>
                      <Checkbox
                        :modelValue="selectedRolePermissions.includes(permission.id)"
                        :binary="true"
                        @update:modelValue="(checked) => togglePermission(permission.id, checked)"
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </template>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'

const roles = ref<any[]>([])
const permissions = ref<any[]>([])
const selectedRole = ref<any | null>(null)
const selectedRolePermissions = ref<number[]>([])
const toast = useToast()

const loadingRoles = ref(false)
const loadingPermissions = ref(false)
const savingPermissions = ref(false)

const loadingModules = ref(false)
const savingModules = ref(false)
const enabledModules = ref<string[]>([])
const availableModules = ref<string[]>([])

const permissionSearch = ref('')
const selectedModule = ref<string | null>(null)
const expandedModules = ref<Set<string>>(new Set())

const moduleOptions = computed(() =>
  availableModules.value.map((m) => ({ label: m, value: m }))
)

const formatLabel = (value: string) => {
  return String(value || '')
    .replace(/[_-]/g, ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase())
}

const filteredPermissions = computed(() => {
  let list = permissions.value
  if (enabledModules.value.length > 0) {
    const enabledSet = new Set(enabledModules.value)
    list = list.filter((p: any) => enabledSet.has(p.module))
  }
  if (selectedModule.value) {
    list = list.filter((p: any) => p.module === selectedModule.value)
  }
  if (permissionSearch.value) {
    const search = permissionSearch.value.toLowerCase()
    list = list.filter((p: any) =>
      [
        p.display_name,
        p.name,
        p.module
      ].filter(Boolean).join(' ').toLowerCase().includes(search)
    )
  }
  return list
})

const allowedPermissionIds = computed(() => {
  if (enabledModules.value.length === 0) return new Set<number>()
  const enabledSet = new Set(enabledModules.value)
  return new Set(
    permissions.value
      .filter((p: any) => enabledSet.has(p.module))
      .map((p: any) => p.id)
  )
})

const groupedPermissions = computed(() => {
  const groups: Record<string, any> = {}
  const list = filteredPermissions.value

  list.forEach((permission: any) => {
    const moduleName = permission.module || String(permission.name || '').split('.')[0] || 'general'
    const parts = String(permission.name || '').split('.').filter(Boolean)
    const submoduleName = parts[1] || 'general'

    if (!groups[moduleName]) {
      groups[moduleName] = {
        name: moduleName,
        total: 0,
        selected: 0,
        submodules: {}
      }
    }

    if (!groups[moduleName].submodules[submoduleName]) {
      groups[moduleName].submodules[submoduleName] = {
        name: submoduleName,
        permissions: []
      }
    }

    groups[moduleName].submodules[submoduleName].permissions.push(permission)
    groups[moduleName].total += 1
    if (selectedRolePermissions.value.includes(permission.id)) {
      groups[moduleName].selected += 1
    }
  })

  return Object.values(groups)
    .map((module: any) => ({
      ...module,
      submodules: Object.values(module.submodules).map((submodule: any) => ({
        ...submodule,
        permissions: submodule.permissions.sort((a: any, b: any) =>
          String(a.display_name || '').localeCompare(String(b.display_name || ''))
        )
      }))
    }))
    .sort((a: any, b: any) => a.name.localeCompare(b.name))
})

const loadRoles = async () => {
  loadingRoles.value = true
  try {
  const response = await axios.get('/api/store/roles/store-specific')
    roles.value = response.data?.data || []
    if (!selectedRole.value && roles.value.length > 0) {
      selectedRole.value = roles.value[0]
    }
  } finally {
    loadingRoles.value = false
  }
}

const loadPermissions = async () => {
  loadingPermissions.value = true
  try {
    const response = await axios.get('/api/store/permissions')
    permissions.value = response.data?.data || []
  } finally {
    loadingPermissions.value = false
  }
}

const loadModules = async () => {
  loadingModules.value = true
  try {
    const response = await axios.get('/api/store/modules')
    enabledModules.value = response.data?.data?.enabled_modules || []
    availableModules.value = response.data?.data?.available_modules || []
  } finally {
    loadingModules.value = false
  }
}

const loadRolePermissions = async (roleId: number) => {
  if (!roleId) return
  const response = await axios.get(`/api/store/roles/${roleId}/permissions`)
  selectedRolePermissions.value = (response.data?.permissions || []).map((p: any) => p.id)
}

const togglePermission = (permissionId: number, checked: boolean) => {
  if (checked) {
    if (!selectedRolePermissions.value.includes(permissionId)) {
      selectedRolePermissions.value.push(permissionId)
    }
  } else {
    selectedRolePermissions.value = selectedRolePermissions.value.filter((id) => id !== permissionId)
  }
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

const toggleModuleCollapse = (moduleName: string) => {
  const set = new Set(expandedModules.value)
  if (set.has(moduleName)) {
    set.delete(moduleName)
  } else {
    set.add(moduleName)
  }
  expandedModules.value = set
}

const savePermissions = async () => {
  if (!selectedRole.value) return
  savingPermissions.value = true
  try {
    const allowedIds = allowedPermissionIds.value
    const filtered = selectedRolePermissions.value.filter((id) => allowedIds.has(id))

    if (filtered.length !== selectedRolePermissions.value.length) {
      selectedRolePermissions.value = filtered
      toast.add({
        severity: 'warn',
        summary: 'Permissions Trimmed',
        detail: 'Some permissions were removed because their modules are not enabled.',
        life: 3500
      })
    }

    await axios.post(`/api/store/roles/${selectedRole.value.id}/permissions`, {
      permissions: selectedRolePermissions.value
    })
  } finally {
    savingPermissions.value = false
  }
}

const saveModules = async () => {
  savingModules.value = true
  try {
    await axios.put('/api/store/modules', { modules: enabledModules.value })
  } finally {
    savingModules.value = false
  }
}

watch(
  () => selectedRole.value?.id,
  (roleId) => {
    if (roleId) loadRolePermissions(roleId)
  }
)

watch(
  () => [enabledModules.value, permissions.value],
  () => {
    if (enabledModules.value.length === 0) return
    const allowedIds = allowedPermissionIds.value
    const filtered = selectedRolePermissions.value.filter((id) => allowedIds.has(id))
    if (filtered.length !== selectedRolePermissions.value.length) {
      selectedRolePermissions.value = filtered
    }
  },
  { deep: true }
)

onMounted(async () => {
  await Promise.all([loadModules(), loadPermissions(), loadRoles()])
})
</script>
