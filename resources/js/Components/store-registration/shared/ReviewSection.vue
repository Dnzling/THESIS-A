<template>
  <div class="border border-gray-200 rounded-lg p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-800">{{ title }}</h3>
      <button
        v-if="canEdit"
        @click="$emit('edit')"
        type="button"
        class="text-blue-600 hover:text-blue-800 text-sm font-medium"
      >
        <i class="pi pi-pencil mr-1"></i>
        Edit
      </button>
    </div>
    <slot />
  </div>
</template>

<script setup lang="ts">
interface Props {
  title: string
  canEdit?: boolean
}

withDefaults(defineProps<Props>(), {
  canEdit: false
})

defineEmits<{
  edit: []
}>()
</script>