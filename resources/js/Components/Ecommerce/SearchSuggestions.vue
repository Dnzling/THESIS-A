<template>
  <div aria-live="polite">
    <p v-if="loading" class="px-3 py-4 text-xs text-slate-500">Finding products...</p>
    <p v-else-if="error" class="px-3 py-4 text-xs text-rose-600">{{ error }}</p>
    <p v-else-if="!items.length" class="px-3 py-4 text-xs text-slate-500">No matching products found.</p>
    <div v-else class="space-y-1">
      <button v-for="item in items" :key="item.id" type="button"
        class="flex w-full items-center gap-3 rounded-xl p-2 text-left hover:bg-orange-50"
        @click="$emit('select', item.id)">
        <img :src="item.image || '/F.svg'" :alt="item.product_name" class="h-12 w-12 shrink-0 rounded-lg bg-slate-100 object-cover" />
        <span class="min-w-0 flex-1">
          <span class="block truncate text-sm font-medium text-slate-900">{{ item.product_name }}</span>
          <span class="block truncate text-xs text-slate-500">{{ item.category || 'Furniture' }}</span>
        </span>
        <span class="shrink-0 text-xs font-semibold text-orange-700">{{ formatPrice(item.price) }}</span>
      </button>
    </div>
    <button v-if="items.length" type="button" class="mt-2 w-full border-t border-slate-100 px-3 py-3 text-left text-xs font-semibold text-orange-600 hover:bg-orange-50"
      @click="$emit('search')">View all results</button>
  </div>
</template>

<script setup lang="ts">
defineProps<{ items: any[]; loading: boolean; error: string }>()
defineEmits<{ select: [id: number]; search: [] }>()

function formatPrice(value: number | string) {
  return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value || 0))
}
</script>
