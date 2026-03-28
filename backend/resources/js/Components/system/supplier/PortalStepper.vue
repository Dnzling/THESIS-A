<template>
  <div class="">
    <div class="flex flex-wrap items-center justify-center gap-4 p-5">
      <div
        v-for="(step, index) in steps"
        :key="step.label"
        class="flex items-center gap-3"
      >
        <div
          class="flex h-10 w-10 items-center justify-center rounded-full text-sm font-semibold"
          :class="index <= current ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-400'"
        >
          {{ index + 1 }}
        </div>
        <div>
          <p class="text-sm font-semibold text-slate-900">{{ step.label }}</p>
          <p v-if="step.description" class="text-xs text-slate-500">{{ step.description }}</p>
        </div>
        <div
          v-if="index < steps.length - 1"
          class="hidden h-px w-10 bg-slate-200 md:block"
        ></div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Step {
  label: string
  description?: string
}

defineProps<{
  steps: Step[]
  current: number
}>()
</script>
