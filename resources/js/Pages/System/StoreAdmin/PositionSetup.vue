<template>
  <div class="mx-auto max-w-5xl space-y-6 p-4 text-slate-900 sm:p-8">
    <div><p class="text-sm font-semibold uppercase tracking-wider text-orange-600">First paid subscription</p><h1 class="mt-1 text-2xl font-semibold">Set up your team positions</h1><p class="mt-2 text-sm text-slate-600">Choose the roles you need now. Departments are created when you save a selected role, and you can manage permissions later.</p></div>
    <div v-if="loading" class="rounded-xl border bg-white p-8 text-sm text-slate-500">Loading positions for your plan…</div>
    <div v-else-if="error" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{{ error }}</div>
    <template v-else>
      <div v-for="(items, module) in grouped" :key="module" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="mb-3 text-base font-semibold capitalize">{{ String(module).replace(/_/g, ' ') }}</h2>
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
          <label v-for="position in items" :key="position.key" class="flex cursor-pointer gap-3 rounded-lg border border-slate-200 p-4 hover:border-orange-300">
            <input v-model="selected" type="checkbox" :value="position.key" class="mt-1 accent-orange-500" />
            <span class="min-w-0"><span class="block text-sm font-semibold">{{ position.name }}</span><span class="text-xs text-slate-500">{{ position.department }}</span><details class="mt-2 text-xs text-slate-600"><summary class="cursor-pointer text-orange-600">Review permissions</summary><ul class="mt-2 max-h-32 list-inside list-disc overflow-y-auto"><li v-for="permission in position.permissions" :key="permission">{{ permission }}</li></ul></details></span>
          </label>
        </div>
      </div>
      <div class="flex items-center justify-end gap-3"><span class="text-sm text-slate-500">{{ selected.length }} selected</span><Button label="Save & Continue" severity="warn" :loading="saving" @click="save" /></div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import Button from 'primevue/button'
import axiosClient from '@/axios'

type Suggestion = { key: string; module: string; name: string; department: string; permissions: string[]; recommended: boolean }
const suggestions = ref<Suggestion[]>([])
const selected = ref<string[]>([])
const loading = ref(true)
const saving = ref(false)
const error = ref('')
const grouped = computed(() => suggestions.value.reduce<Record<string, Suggestion[]>>((groups, item) => {
  ;(groups[item.module] ||= []).push(item)
  return groups
}, {}))

onMounted(async () => {
  try {
    const response = await axiosClient.get('/api/store/position-setup')
    if (!response.data?.required) {
      router.visit('/store/index')
      return
    }
    suggestions.value = response.data?.suggestions || []
    selected.value = suggestions.value.filter((item) => item.recommended).map((item) => item.key)
  } catch (cause: any) {
    error.value = cause?.response?.data?.message || 'Unable to load positions.'
  } finally {
    loading.value = false
  }
})

const save = async () => {
  saving.value = true
  error.value = ''
  try {
    await axiosClient.post('/api/store/position-setup', { positions: selected.value })
    router.visit('/store/index')
  } catch (cause: any) {
    error.value = cause?.response?.data?.message || 'Unable to save positions.'
  } finally {
    saving.value = false
  }
}
</script>
