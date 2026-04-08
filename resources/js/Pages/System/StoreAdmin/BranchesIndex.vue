<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">Branches</h1>
        <p class="text-sm text-slate-600">Manage store branches and geofence coverage.</p>
      </div>
    </div>

    <Card class="rounded-2xl border border-slate-200/70 shadow-sm">
      <template #content>
        <div class="flex flex-col gap-3">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <span class="text-xs font-semibold text-slate-500 uppercase">Search</span>
            <InputText v-model="search" placeholder="Search branch name or code" size="small" @input="loadBranches" />
          </div>

          <DataTable
            :value="filteredBranches"
            :paginator="true"
            :rows="10"
            size="small"
            stripedRows
            dataKey="id"
            class="p-datatable-sm"
          >
            <Column field="name" header="Branch" sortable>
              <template #body="{ data }">
                <div class="flex flex-col">
                  <span class="font-semibold text-slate-900">{{ data.name || data.branch_name || 'Branch' }}</span>
                  <span class="text-xs text-slate-500">{{ data.branch_code || data.code || '—' }}</span>
                </div>
              </template>
            </Column>
            <Column field="city" header="City" sortable></Column>
            <Column field="address" header="Address"></Column>
            <Column header="Geofence" style="width: 120px">
              <template #body="{ data }">
                <Tag :value="data.geofence_radius_m ? data.geofence_radius_m + ' m' : 'Not set'" severity="info" />
              </template>
            </Column>
            <Column header="Action" style="width: 110px">
              <template #body="{ data }">
                <Button label="View" text size="small" icon="pi pi-arrow-right" @click="viewBranch(data.id)" />
              </template>
            </Column>
          </DataTable>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import inventoryService from '@/services/inventory.service'

const router = useRouter()
const loading = ref(false)
const branches = ref<any[]>([])
const search = ref('')

const loadBranches = async () => {
  try {
    loading.value = true
    const res = await inventoryService.getBranches()
    const payload = res?.data ?? res ?? {}
    branches.value = payload.data ?? payload ?? []
  } finally {
    loading.value = false
  }
}

const filteredBranches = computed(() => {
  if (!search.value) return branches.value
  const term = search.value.toLowerCase()
  return branches.value.filter((b: any) =>
    [b.name, b.branch_name, b.branch_code, b.code]
      .filter(Boolean)
      .some((v: string) => v.toLowerCase().includes(term))
  )
})

const viewBranch = (id: number) => {
  router.push(`/store/branches/${id}`)
}

onMounted(loadBranches)
</script>
