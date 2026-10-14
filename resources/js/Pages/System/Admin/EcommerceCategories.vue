<template>
  <div class="space-y-4 text-sm">
    <header class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold text-slate-950">Ecommerce Categories</h1>
        <p class="mt-1 text-xs text-slate-500">Manage the category shortcuts displayed on the shopping Home page.</p>
      </div>
      <Button label="Add Category" icon="pi pi-plus" severity="warn" size="small" @click="openAdd" />
    </header>
    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
      <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3">
        <div>
          <h2 class="font-semibold text-slate-900">Currently displayed on Home</h2>
          <p class="text-xs text-slate-500">These appear as quick shopping choices.</p>
        </div>
        <Tag :value="`${displayed.length} displayed`" severity="warn" />
      </div>
      <div v-if="loading" class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
        <Skeleton v-for="i in 6" :key="i" height="210px" borderRadius="1rem" />
      </div>
      <div v-else-if="displayed.length" class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
        <article v-for="category in displayed" :key="category.id"
          class="overflow-hidden rounded-xl border border-slate-200">
          <div class="aspect-square bg-slate-50"><img v-if="category.ecommerce_icon_url"
              :src="category.ecommerce_icon_url" :alt="category.category_name" class="h-full w-full object-cover" />
            <div v-else class="flex h-full items-center justify-center text-xs text-slate-400">No icon</div>
          </div>
          <div class="p-3">
            <h3 class="truncate font-semibold">{{ category.category_name }}</h3>
            <p class="mt-1 text-xs text-slate-500">{{ category.products_count }} products</p>
            <div class="mt-3 grid grid-cols-2 gap-1"><Button label="Edit Icon" outlined severity="secondary" size="small"
                @click="openEdit(category)" /><Button label="Remove" text severity="danger" size="small"
                @click="remove(category)" /></div>
          </div>
        </article>
      </div>
      <div v-else class="rounded-xl border border-dashed border-slate-300 py-12 text-center">
        <p class="font-medium">No categories are displayed yet.</p>
        <p class="mt-1 text-xs text-slate-500">Add a category and icon to show it on Home.</p><Button label="Add Category"
          severity="warn" size="small" class="mt-4" @click="openAdd" />
      </div>
    </section>
  
    <Dialog v-model:visible="visible" modal :header="mode === 'add' ? 'Add Ecommerce Category' : 'Update Category Icon'"
      :style="{ width: 'min(95vw, 80rem)' }" @hide="reset">
      <div v-if="mode === 'add' && !selected" class="space-y-3">
        <div>
          <h3 class="font-semibold">Select a recorded category</h3>
          <p class="text-xs text-slate-500">Sorted by the number of products, highest first.</p>
        </div>
        <IconField class="w-full">
          <InputIcon class="pi pi-search" />
          <InputText v-model="search" placeholder="Search categories or stores" size="small" class="w-full" />
        </IconField>
        <div class="max-h-[52vh] overflow-y-auto rounded-xl border border-slate-200">
          <button v-for="category in choices" :key="category.id" type="button"
            :disabled="category.is_ecommerce_quick_select"
            class="flex w-full items-center justify-between gap-4 border-b border-slate-100 px-4 py-3 text-left last:border-0 enabled:hover:bg-orange-50 disabled:bg-slate-50"
            @click="selected = category">
            <div class="min-w-0">
              <p class="truncate font-medium">{{ category.category_name }}</p>
              <p class="truncate text-xs text-slate-500">{{ category.store_name }}</p>
            </div>
            <div class="flex shrink-0 items-center gap-3"><span class="text-xs text-slate-500">{{ category.products_count
                }} products</span>
              <Tag v-if="category.is_ecommerce_quick_select" value="On Home" severity="success" /><i v-else
                class="pi pi-chevron-right text-xs text-orange-500" />
            </div>
          </button>
          <div v-if="!choices.length" class="py-10 text-center text-xs text-slate-500">No matching categories found.</div>
        </div>
      </div>
      <div v-else-if="selected" class="grid gap-5 md:grid-cols-[220px_1fr]">
        <div class="aspect-square overflow-hidden rounded-xl border border-dashed border-slate-300 bg-slate-50"><img
            v-if="selected.previewUrl || selected.ecommerce_icon_url"
            :src="selected.previewUrl || selected.ecommerce_icon_url" class="h-full w-full object-cover" />
          <div v-else class="flex h-full items-center justify-center p-5 text-center text-xs text-slate-400">Upload an
            image to preview the icon.</div>
        </div>
        <div>
          <Tag :value="`${selected.products_count} products`" severity="secondary" />
          <h3 class="mt-3 text-lg font-semibold">{{ selected.category_name }}</h3>
          <p class="text-xs text-slate-500">{{ selected.store_name }}</p>
          <div class="mt-5 rounded-xl bg-orange-50 p-3 text-xs text-orange-900">Upload a square JPG, PNG, or WebP. Adjust
            the crop before saving. Output: 512 x 512 px.</div><label
            class="mt-4 inline-flex cursor-pointer items-center gap-2 rounded-lg border border-orange-300 px-3 py-2 text-xs font-semibold text-orange-600 hover:bg-orange-50"><i
              class="pi pi-upload" />{{ selected.ecommerce_icon_url ? 'Replace Icon' : 'Upload Icon' }}<input type="file"
              accept="image/jpeg,image/png,image/webp" class="sr-only" @change="pickIcon" /></label>
        </div>
      </div>
      <template #footer><Button v-if="mode === 'add' && selected" label="Back" text severity="secondary" size="small"
          @click="selected = null" /><Button label="Cancel" outlined severity="secondary" size="small"
          @click="visible = false" /><Button v-if="selected" :label="mode === 'add' ? 'Add to Home' : 'Save Icon'"
          severity="warn" size="small" :loading="saving === selected.id" @click="save" /></template>
    </Dialog>
    <ConfirmDialog />
    <MarketingImageCropDialog v-model:visible="crop.visible" :source="crop.source" :file-name="crop.fileName"
      :aspect-ratio="1" :output-width="512" :output-height="512" @cropped="acceptCrop" />
  </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import axiosClient from '@/axios'
import MarketingImageCropDialog from '@/Components/MarketingImageCropDialog.vue'
import Button from 'primevue/button'; import ConfirmDialog from 'primevue/confirmdialog'; import Dialog from 'primevue/dialog'; import IconField from 'primevue/iconfield'; import InputIcon from 'primevue/inputicon'; import InputText from 'primevue/inputtext'; import Skeleton from 'primevue/skeleton'; import Tag from 'primevue/tag'
import { useConfirm } from 'primevue/useconfirm'; import { useToast } from 'primevue/usetoast'

const confirm = useConfirm(), toast = useToast()
const categories = ref<any[]>([]), loading = ref(true), saving = ref<number | null>(null), visible = ref(false), mode = ref<'add' | 'edit'>('add'), search = ref(''), selected = ref<any | null>(null)
const urls = new Set<string>(), crop = reactive({ visible: false, source: '', fileName: '' })
const displayed = computed(() => groupCategories(categories.value).filter(c => c.is_ecommerce_quick_select))
const choices = computed(() => { const q = search.value.trim().toLowerCase(); return groupCategories(categories.value).filter(c => !q || c.category_name.toLowerCase().includes(q) || c.store_name.toLowerCase().includes(q)).sort((a, b) => b.products_count - a.products_count || a.category_name.localeCompare(b.category_name)) })

async function load() { loading.value = true; try { const { data } = await axiosClient.get('/api/admin/ecommerce-categories'); categories.value = (data?.data || []).map((c: any) => ({ ...c, iconFile: null, previewUrl: '' })) } catch (e: any) { fail(e, 'Categories could not be loaded.') } finally { loading.value = false } }
function openAdd() { mode.value = 'add'; selected.value = null; search.value = ''; visible.value = true }
function openEdit(c: any) { mode.value = 'edit'; selected.value = c; visible.value = true }
function reset() { selected.value = null; search.value = '' }
function pickIcon(event: Event) { const input = event.target as HTMLInputElement, file = input.files?.[0]; input.value = ''; if (!file) return; const source = URL.createObjectURL(file); urls.add(source); Object.assign(crop, { visible: true, source, fileName: file.name }) }
function acceptCrop(file: File) { if (!selected.value) return; const url = URL.createObjectURL(file); urls.add(url); selected.value.iconFile = file; selected.value.previewUrl = url }
async function update(c: any, enabled: boolean) { saving.value = c.id; try { const form = new FormData(); form.append('is_ecommerce_quick_select', enabled ? '1' : '0'); if (c.iconFile) form.append('icon', c.iconFile); const { data } = await axiosClient.post(`/api/admin/ecommerce-categories/${c.id}`, form); const changes = { ...(data?.data || {}), iconFile: null, previewUrl: '' }; Object.assign(c, changes); const source = categories.value.find(item => item.id === c.id); if (source && source !== c) Object.assign(source, changes); return true } catch (e: any) { fail(e, 'Category could not be updated.'); return false } finally { saving.value = null } }
async function save() { const c = selected.value; if (!c) return; if (mode.value === 'add' && !c.iconFile && !c.ecommerce_icon_url) { toast.add({ severity: 'warn', summary: 'Icon required', detail: 'Upload and crop an icon first.', life: 3500 }); return } if (await update(c, true)) { toast.add({ severity: 'success', summary: mode.value === 'add' ? 'Category added' : 'Icon updated', detail: `${c.category_name} is ready for Home.`, life: 3000 }); visible.value = false } }
function remove(c: any) { confirm.require({ header: 'Remove from Home?', message: `${c.category_name} and its related category names will be hidden from quick categories. Products will not be deleted.`, icon: 'pi pi-exclamation-triangle', rejectLabel: 'Cancel', acceptLabel: 'Remove', acceptClass: 'p-button-danger', accept: async () => { const enabled = (c._members || [c]).filter((item: any) => item.is_ecommerce_quick_select); const results = await Promise.all(enabled.map((item: any) => update(item, false))); if (results.every(Boolean)) toast.add({ severity: 'success', summary: 'Removed from Home', detail: `${c.category_name} was removed.`, life: 3000 }) } }) }
function groupCategories(items: any[]) { const groups: any[] = []; for (const item of items) { const group = groups.find(g => related(g.category_name, item.category_name)); if (!group) { groups.push({ ...item, _members: [item] }); continue } group._members.push(item); group.products_count += Number(item.products_count || 0); group.is_ecommerce_quick_select ||= item.is_ecommerce_quick_select; if (!group.ecommerce_icon_url && item.ecommerce_icon_url) group.ecommerce_icon_url = item.ecommerce_icon_url; const stores = new Set(group._members.map((member: any) => member.store_name)); group.store_name = stores.size === 1 ? item.store_name : `${stores.size} stores` } return groups }
function related(first: string, second: string) { const a = normalize(first), b = normalize(second); if (a === b || distance(a, b) <= 2) return true; const aw = [...new Set(a.split(' '))], bw = [...new Set(b.split(' '))], shared = aw.filter(word => bw.includes(word)).length; return Math.min(aw.length, bw.length) >= 2 && shared / Math.max(aw.length, bw.length) >= .67 }
function normalize(value: string) { return value.toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim().split(/\s+/).filter(Boolean).map(word => word.length > 3 && word.endsWith('ies') ? `${word.slice(0, -3)}y` : word.length > 3 && word.endsWith('s') && !word.endsWith('ss') ? word.slice(0, -1) : word).sort().join(' ') }
function distance(a: string, b: string) { const row = Array.from({ length: b.length + 1 }, (_, i) => i); for (let i = 1; i <= a.length; i++) { let previous = row[0]; row[0] = i; for (let j = 1; j <= b.length; j++) { const saved = row[j]; row[j] = Math.min(row[j] + 1, row[j - 1] + 1, previous + (a[i - 1] === b[j - 1] ? 0 : 1)); previous = saved } } return row[b.length] }
function fail(e: any, fallback: string) { const errors = e.response?.data?.errors; toast.add({ severity: 'error', summary: 'Unable to save', detail: errors ? Object.values(errors).flat().join(' ') : e.response?.data?.message || fallback, life: 4500 }) }
onMounted(load); onBeforeUnmount(() => urls.forEach(URL.revokeObjectURL))
</script>
