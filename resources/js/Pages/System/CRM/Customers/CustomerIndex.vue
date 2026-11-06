<template>
    <div class="min-h-screen p-4">
        <div
            class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between"
        >
            <div>
                <h1 class="text-lg font-bold text-slate-900">Customers</h1>
                <p class="text-xs text-slate-500">
                    Manage retail and wholesale customer relationships for this
                    store.
                </p>
            </div>
            <Button
                v-if="canManage"
                label="Create Customer"
                icon="pi pi-plus"
                size="small"
                @click="router.visit('/crm/customers/create')"
            />
        </div>
        <Card class="border border-slate-200 shadow-sm"
            ><template #content>
                <div class="mb-4 grid items-end gap-3 md:grid-cols-4">
                    <IconField class="md:col-span-2"
                        ><InputIcon class="pi pi-search" /><InputText
                            v-model="filters.search"
                            fluid
                            size="small"
                            placeholder="Name, company, email, phone, or code"
                            @input="searchLater"
                    /></IconField>
                    <Select
                        v-model="filters.type"
                        :options="types"
                        optionLabel="label"
                        optionValue="value"
                        showClear
                        fluid
                        size="small"
                        placeholder="All customer types"
                        @change="load"
                    />
                    <Select
                        v-model="filters.stage"
                        :options="stages"
                        optionLabel="label"
                        optionValue="value"
                        showClear
                        fluid
                        size="small"
                        placeholder="All relationship stages"
                        @change="load"
                    />
                </div>
                <DataTable
                    :value="rows"
                    :loading="loading"
                    dataKey="id"
                    lazy
                    paginator
                    :rows="page.rows"
                    :first="(page.current - 1) * page.rows"
                    :totalRecords="page.total"
                    :rowsPerPageOptions="[10, 15, 25, 50]"
                    rowHover
                    stripedRows
                    responsiveLayout="scroll"
                    class="text-xs"
                    @page="onPage"
                >
                    <template #empty
                        ><div class="py-12 text-center">
                            <i class="pi pi-users text-4xl text-slate-300" />
                            <p class="mt-2 text-slate-500">
                                No customers found.
                            </p>
                        </div></template
                    >
                    <Column header="Customer" style="min-width: 220px"
                        ><template #body="{ data }"
                            ><button
                                class="font-semibold text-orange-600 hover:underline"
                                @click="view(data.id)"
                            >
                                {{ data.full_name }}
                            </button>
                            <p class="mt-1 text-[11px] text-slate-400">
                                {{ data.lead_code }}
                            </p></template
                        ></Column
                    >
                    <Column header="Type" style="min-width: 150px"
                        ><template #body="{ data }"
                            ><Tag
                                :value="fmt(data.customer_type)"
                                :severity="
                                    data.customer_type === 'business'
                                        ? 'info'
                                        : 'secondary'
                                "
                            />
                            <p
                                v-if="data.company_name"
                                class="mt-1 font-medium text-slate-600"
                            >
                                {{ data.company_name }}
                            </p></template
                        ></Column
                    >
                    <Column header="Contact" style="min-width: 210px"
                        ><template #body="{ data }"
                            ><p>{{ data.email || 'No email' }}</p>
                            <p class="text-slate-500">
                                {{ data.phone || 'No phone' }}
                            </p></template
                        ></Column
                    >
                    <Column field="stage" header="Stage"
                        ><template #body="{ data }"
                            ><Tag
                                :value="fmt(data.stage)"
                                :severity="
                                    stageSeverity(data.stage)
                                " /></template
                    ></Column>
                    <Column field="activities_count" header="Activities" />
                    <Column field="created_at" header="Added"
                        ><template #body="{ data }">{{
                            date(data.created_at)
                        }}</template></Column
                    >
                    <Column header="Actions" frozen alignFrozen="right"
                        ><template #body="{ data }"
                            ><Button
                                label="View"
                                icon="pi pi-eye"
                                outlined
                                rounded
                                size="small"
                                @click="view(data.id)" /></template
                    ></Column>
                </DataTable> </template
        ></Card>
    </div>
</template>
<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/stores/auth'
import crmService from '@/services/crm.service'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
const toast = useToast(),
    auth = useAuthStore(),
    loading = ref(false),
    rows = ref<any[]>([]),
    page = reactive({ current: 1, rows: 15, total: 0 }),
    filters = reactive({
        search: '',
        type: null as string | null,
        stage: null as string | null
    })
let timer: any
const canManage = computed(() => auth.hasPermission('crm.crm.manage'))
const types = [
    { label: 'Individual', value: 'individual' },
    { label: 'Business / Wholesale', value: 'business' }
]
const stages = ['new', 'contacted', 'qualified', 'proposal', 'won', 'lost'].map(
    value => ({ label: value[0].toUpperCase() + value.slice(1), value })
)
const load = async () => {
    loading.value = true
    try {
        const r = await crmService.getCustomers({
            page: page.current,
            per_page: page.rows,
            search: filters.search || undefined,
            stage: filters.stage || undefined,
            customer_type: filters.type || undefined
        })
        const p = r?.data
        rows.value = p?.data || []
        page.total = Number(p?.total || 0)
    } catch (e: any) {
        toast.add({
            severity: 'error',
            summary: 'Unable to load customers',
            detail: e.response?.data?.message || 'Please try again.',
            life: 3000
        })
    } finally {
        loading.value = false
    }
}
const searchLater = () => {
        clearTimeout(timer)
        timer = setTimeout(() => {
            page.current = 1
            load()
        }, 300)
    },
    onPage = (e: any) => {
        page.current = Number(e.page) + 1
        page.rows = Number(e.rows)
        load()
    },
    view = (id: number) => router.visit(`/crm/customers/${id}`),
    fmt = (v: string) =>
        String(v || '')
            .replace(/_/g, ' ')
            .replace(/\b\w/g, c => c.toUpperCase()),
    stageSeverity = (v: string) =>
        v === 'won'
            ? 'success'
            : v === 'lost'
            ? 'danger'
            : v === 'proposal'
            ? 'warn'
            : v === 'qualified'
            ? 'info'
            : 'secondary',
    date = (v: any) =>
        v
            ? new Intl.DateTimeFormat('en-PH', { dateStyle: 'medium' }).format(
                  new Date(v)
              )
            : '—'
onMounted(load)
</script>
