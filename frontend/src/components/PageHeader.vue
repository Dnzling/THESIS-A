<template>
  <div class="bg-white border border-slate-200 rounded-lg px-6 py-4 shadow-sm">
    <div class="flex items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <div v-if="icon" class="h-10 w-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-600">
          <i :class="icon"></i>
        </div>
        <div>
          <h1 class="text-xl font-semibold text-slate-800">{{ title }}</h1>
          <p v-if="subtitle" class="text-sm text-slate-500">{{ subtitle }}</p>
        </div>
      </div>
      <div v-if="$slots.actions" class="shrink-0">
        <slot name="actions" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  title: string
  subtitle?: string
  icon?: string
}>()
</script>
