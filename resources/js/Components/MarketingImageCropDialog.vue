<template>
  <Dialog :visible="visible" modal :closable="false" :style="{ width: 'min(94vw, 620px)' }" @update:visible="close">
    <template #header>
      <div>
        <h3 class="text-base font-semibold text-slate-900">Crop image</h3>
        <p class="mt-1 text-xs text-slate-500">Output: {{ outputWidth }} x {{ outputHeight }} px</p>
      </div>
    </template>

    <div class="space-y-5">
      <div class="flex justify-center rounded-2xl bg-slate-100 p-4">
        <div class="relative overflow-hidden rounded-xl border-2 border-orange-500 bg-white shadow-inner" :style="viewportStyle">
          <img
            ref="previewImage"
            :src="source"
            alt="Image crop preview"
            class="pointer-events-none absolute max-w-none select-none"
            :style="imageStyle"
            @load="handleImageLoad"
          />
          <div class="pointer-events-none absolute inset-0 border border-white/60"></div>
        </div>
      </div>

      <div class="grid gap-4 sm:grid-cols-3">
        <label class="text-xs font-medium text-slate-600">Zoom
          <input v-model.number="zoom" type="range" min="1" max="3" step="0.05" class="mt-2 w-full accent-orange-500" />
        </label>
        <label class="text-xs font-medium text-slate-600">Horizontal
          <input v-model.number="offsetX" type="range" :min="-viewportWidth / 2" :max="viewportWidth / 2" step="1" class="mt-2 w-full accent-orange-500" />
        </label>
        <label class="text-xs font-medium text-slate-600">Vertical
          <input v-model.number="offsetY" type="range" :min="-viewportHeight / 2" :max="viewportHeight / 2" step="1" class="mt-2 w-full accent-orange-500" />
        </label>
      </div>
    </div>

    <template #footer>
      <Button label="Cancel" severity="secondary" text size="small" @click="close" />
      <Button label="Use cropped image" severity="warn" size="small" :loading="processing" @click="crop" />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'

const props = defineProps<{
  visible: boolean
  source: string
  fileName: string
  aspectRatio: number
  outputWidth: number
  outputHeight: number
}>()

const emit = defineEmits<{
  (event: 'update:visible', value: boolean): void
  (event: 'cropped', file: File): void
}>()

const previewImage = ref<HTMLImageElement | null>(null)
const naturalWidth = ref(1)
const naturalHeight = ref(1)
const zoom = ref(1)
const offsetX = ref(0)
const offsetY = ref(0)
const processing = ref(false)
const viewportWidth = 420
const viewportHeight = computed(() => Math.round(viewportWidth / props.aspectRatio))
const baseScale = computed(() => Math.max(viewportWidth / naturalWidth.value, viewportHeight.value / naturalHeight.value))
const renderedWidth = computed(() => naturalWidth.value * baseScale.value * zoom.value)
const renderedHeight = computed(() => naturalHeight.value * baseScale.value * zoom.value)

const viewportStyle = computed(() => ({ width: `${viewportWidth}px`, height: `${viewportHeight.value}px`, maxWidth: '100%' }))
const imageStyle = computed(() => ({
  width: `${renderedWidth.value}px`,
  height: `${renderedHeight.value}px`,
  left: `${(viewportWidth - renderedWidth.value) / 2 + offsetX.value}px`,
  top: `${(viewportHeight.value - renderedHeight.value) / 2 + offsetY.value}px`,
}))

watch(() => props.source, () => {
  zoom.value = 1
  offsetX.value = 0
  offsetY.value = 0
})

const handleImageLoad = () => {
  naturalWidth.value = previewImage.value?.naturalWidth || 1
  naturalHeight.value = previewImage.value?.naturalHeight || 1
}

const close = () => emit('update:visible', false)

const crop = async () => {
  const image = previewImage.value
  if (!image) return
  processing.value = true
  try {
    const canvas = document.createElement('canvas')
    canvas.width = props.outputWidth
    canvas.height = props.outputHeight
    const context = canvas.getContext('2d')
    if (!context) throw new Error('Canvas is unavailable.')

    const factor = props.outputWidth / viewportWidth
    const left = (viewportWidth - renderedWidth.value) / 2 + offsetX.value
    const top = (viewportHeight.value - renderedHeight.value) / 2 + offsetY.value
    context.drawImage(image, left * factor, top * factor, renderedWidth.value * factor, renderedHeight.value * factor)

    const blob = await new Promise<Blob | null>((resolve) => canvas.toBlob(resolve, 'image/webp', 0.9))
    if (!blob) throw new Error('Unable to crop image.')
    const baseName = props.fileName.replace(/\.[^.]+$/, '') || 'homepage-image'
    emit('cropped', new File([blob], `${baseName}.webp`, { type: 'image/webp' }))
    close()
  } finally {
    processing.value = false
  }
}
</script>
