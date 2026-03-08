<template>
    <div class="max-w-7xl mx-auto space-y-6 pb-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">3D Models Gallery</h2>
                <p class="text-sm text-gray-500 mt-1">All 3D models from Store 1</p>
            </div>
            <div class="flex gap-2">
                <Button label="Refresh" icon="pi pi-refresh" severity="secondary" outlined @click="loadModels"
                    :loading="loading" />
                <Button label="Upload New" icon="pi pi-cloud-upload"
                    @click="router.push({ name: 'merchandising.assets.upload' })" />
            </div>
        </div>
    
        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <Card>
                <template #content>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Total 3D Models</p>
                            <h3 class="text-3xl font-bold text-indigo-600 mt-1">{{ models.length }}</h3>
                        </div>
                        <div class="bg-indigo-100 p-4 rounded-full">
                            <i class="pi pi-cube text-indigo-600 text-3xl"></i>
                        </div>
                    </div>
                </template>
            </Card>
    
            <Card>
                <template #content>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">GLB Models</p>
                            <h3 class="text-3xl font-bold text-blue-600 mt-1">{{ glbCount }}</h3>
                        </div>
                        <div class="bg-blue-100 p-4 rounded-full">
                            <i class="pi pi-box text-blue-600 text-3xl"></i>
                        </div>
                    </div>
                </template>
            </Card>
    
            <Card>
                <template #content>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Total Size</p>
                            <h3 class="text-3xl font-bold text-green-600 mt-1">{{ totalSizeFormatted }}</h3>
                        </div>
                        <div class="bg-green-100 p-4 rounded-full">
                            <i class="pi pi-database text-green-600 text-3xl"></i>
                        </div>
                    </div>
                </template>
            </Card>
        </div>
    
        <!-- Loading State -->
        <div v-if="loading && models.length === 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <Skeleton v-for="i in 6" :key="i" height="400px" class="rounded-lg" />
        </div>
    
        <!-- 3D Models Grid -->
        <div v-else-if="models.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <Card v-for="model in models" :key="model.id" class="hover:shadow-2xl transition-shadow">
                <template #content>
                    <div class="space-y-4">
                        <!-- 3D Viewer Container -->
                        <div :ref="el => setModelRef(model.id, el)"
                            class="relative bg-gradient-to-br from-gray-50 to-gray-100 rounded-lg overflow-hidden"
                            style="height: 300px;">
                            <!-- Loading Indicator -->
                            <div v-if="!modelStates[model.id]?.loaded"
                                class="absolute inset-0 flex items-center justify-center bg-white/90 z-10">
                                <div class="text-center">
                                    <ProgressSpinner style="width: 50px; height: 50px" strokeWidth="4" />
                                    <p class="text-sm text-gray-600 mt-2">Loading 3D model...</p>
                                </div>
                            </div>
    
                            <!-- Error State -->
                            <div v-if="modelStates[model.id]?.error"
                                class="absolute inset-0 flex items-center justify-center bg-red-50 z-10">
                                <div class="text-center p-4">
                                    <i class="pi pi-exclamation-triangle text-4xl text-red-500 mb-2"></i>
                                    <p class="text-sm text-red-700">Failed to load</p>
                                    <Button label="Retry" size="small" class="mt-2" @click="retryLoadModel(model)" />
                                </div>
                            </div>
    
                            <!-- Auto-rotate Toggle -->
                            <div v-if="modelStates[model.id]?.loaded" class="absolute top-2 right-2 z-20">
                                <Button :icon="modelStates[model.id]?.autoRotate ? 'pi pi-pause' : 'pi pi-play'" rounded
                                    size="small" :severity="modelStates[model.id]?.autoRotate ? 'warning' : 'secondary'"
                                    @click="toggleAutoRotate(model.id)" />
                            </div>
    
                            <!-- Primary Badge -->
                            <Badge v-if="model.is_primary" value="Primary" severity="success"
                                class="absolute top-2 left-2 z-20" />
                        </div>
    
                        <!-- Model Info -->
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 truncate">{{ model.file_name }}</h3>
                            <p v-if="model.product" class="text-sm text-gray-600 mt-1 truncate">
                                {{ model.product.product_name }}
                            </p>
    
                            <div class="flex items-center gap-2 mt-3">
                                <Tag :value="model.model_format?.toUpperCase()" severity="info" size="small" />
                                <span class="text-xs text-gray-500">{{ formatFileSize(model.file_size_kb * 1024) }}</span>
                            </div>
    
                            <!-- Camera Settings -->
                            <div v-if="model.camera_settings" class="mt-3 p-3 bg-gray-50 rounded-lg">
                                <p class="text-xs font-semibold text-gray-700 mb-2">Camera Settings</p>
                                <div class="grid grid-cols-3 gap-2 text-xs text-gray-600">
                                    <div>
                                        <span class="block text-gray-500">Angle X</span>
                                        <span class="font-medium">{{ model.camera_settings.angle_x }}°</span>
                                    </div>
                                    <div>
                                        <span class="block text-gray-500">Angle Y</span>
                                        <span class="font-medium">{{ model.camera_settings.angle_y }}°</span>
                                    </div>
                                    <div>
                                        <span class="block text-gray-500">Zoom</span>
                                        <span class="font-medium">{{ model.camera_settings.zoom }}x</span>
                                    </div>
                                </div>
                            </div>
    
                            <!-- Actions -->
                            <div class="flex gap-2 mt-4">
                                <Button label="View" icon="pi pi-eye" size="small" class="flex-1"
                                    @click="viewModel(model)" />
                                <Button label="Download" icon="pi pi-download" severity="secondary" size="small"
                                    class="flex-1" @click="downloadModel(model)" />
                            </div>
                        </div>
                    </div>
                </template>
            </Card>
        </div>
    
        <!-- Empty State -->
        <Card v-else>
            <template #content>
                <div class="text-center py-12">
                    <i class="pi pi-cube text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">No 3D Models Found</h3>
                    <p class="text-gray-600 mb-4">Upload your first 3D model to get started</p>
                    <Button label="Upload 3D Model" icon="pi pi-cloud-upload"
                        @click="$router.push({ name: 'merchandising.assets.upload' })" />
                </div>
            </template>
        </Card>
    
        <!-- View Dialog -->
        <Dialog v-model:visible="viewDialogVisible" :header="currentModel?.file_name" :modal="true"
            class="w-full max-w-4xl">
            <div v-if="currentModel" class="space-y-4">
                <!-- Fullscreen 3D Viewer -->
                <div ref="dialogViewerContainer" class="bg-gradient-to-br from-gray-100 to-gray-200 rounded-lg"
                    style="height: 500px;"></div>
    
                <!-- Model Details -->
                <div class="grid grid-cols-2 gap-4 p-4 bg-gray-50 rounded-lg">
                    <div>
                        <p class="text-xs text-gray-600 mb-1">Format</p>
                        <Tag :value="currentModel.model_format?.toUpperCase()" severity="info" />
                    </div>
                    <div>
                        <p class="text-xs text-gray-600 mb-1">File Size</p>
                        <p class="text-sm font-semibold">{{ formatFileSize(currentModel.file_size_kb * 1024) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-600 mb-1">Product</p>
                        <p class="text-sm font-semibold">{{ currentModel.product?.product_name || 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-600 mb-1">Uploaded</p>
                        <p class="text-sm font-semibold">{{ formatDate(currentModel.created_at) }}</p>
                    </div>
                </div>
            </div>
    
            <template #footer>
                <Button label="Download" icon="pi pi-download" @click="downloadModel(currentModel)" />
                <Button label="Close" severity="secondary" outlined @click="closeViewDialog" />
            </template>
        </Dialog>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import axios from 'axios'
import * as THREE from 'three'
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader'
import { OBJLoader } from 'three/examples/jsm/loaders/OBJLoader'
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls'

import Card from 'primevue/card'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Badge from 'primevue/badge'
import Dialog from 'primevue/dialog'
import Skeleton from 'primevue/skeleton'
import ProgressSpinner from 'primevue/progressspinner'
import { useAuthStore } from '../../../../stores/auth'

const router = useRouter()
const toast = useToast()

const models = ref<any[]>([])
const loading = ref(false)
const viewDialogVisible = ref(false)
const currentModel = ref(null)
const dialogViewerContainer = ref<HTMLElement | null>(null)

const modelContainers = ref<Map<number, HTMLElement>>(new Map())
const modelScenes = ref<Map<number, any>>(new Map())
const modelStates = reactive<Record<number, { loaded: boolean; error: boolean; autoRotate: boolean }>>({})

// Computed
const glbCount = computed(() => {
  return models.value.filter(m => m.model_format?.toLowerCase() === 'glb').length
})

const totalSizeFormatted = computed(() => {
  const totalBytes = models.value.reduce((sum, m) => sum + (m.file_size_kb * 1024), 0)
  return formatFileSize(totalBytes)
})

// Load Models from API
const loadModels = async () => {
  loading.value = true
  try {
    // Fetch all assets filtered by 3D_Model type
    const response = await axios.get('/api/product-catalog/assets', {
      params: {
        asset_type: '3D_Model'
      }
    })

    models.value = response.data.data.data || response.data.data || []

    console.log(`Loaded ${models.value.length} 3D models:`, models.value)

    // Initialize model states
    models.value.forEach(model => {
      modelStates[model.id] = {
        loaded: false,
        error: false,
        autoRotate: false
      }
    })

    // Load 3D viewers after DOM update
    nextTick(() => {
      models.value.forEach(model => {
        const container = modelContainers.value.get(model.id)
        if (container) {
          load3DModel(model, container)
        }
      })
    })

  } catch (error: any) {
    console.error('Failed to load 3D models:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load 3D models',
      life: 5000
    })
  } finally {
    loading.value = false
  }
}

const setModelRef = (modelId: number, el: HTMLElement | null) => {
  if (el) {
    modelContainers.value.set(modelId, el)
  }
}

const load3DModel = (model: any, container: HTMLElement) => {
  if (!container || !model.url) {
    console.error('Missing container or model URL')
    modelStates[model.id].error = true
    return
  }

  try {
    const width = container.clientWidth
    const height = container.clientHeight

    // Scene
    const scene = new THREE.Scene()
    scene.background = new THREE.Color(0xf5f5f5)

    // Camera
    const camera = new THREE.PerspectiveCamera(50, width / height, 0.1, 1000)
    camera.position.set(
      model.camera_settings?.angle_x || 2,
      model.camera_settings?.angle_y || 2,
      model.camera_settings?.zoom || 5
    )

    // Renderer
    const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true })
    renderer.setSize(width, height)
    renderer.setPixelRatio(window.devicePixelRatio)
    container.appendChild(renderer.domElement)

    // Lights
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.6)
    scene.add(ambientLight)

    const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8)
    directionalLight.position.set(5, 10, 7.5)
    scene.add(directionalLight)

    // Controls
    const controls = new OrbitControls(camera, renderer.domElement)
    controls.enableDamping = true
    controls.dampingFactor = 0.05
    controls.minDistance = 1
    controls.maxDistance = 20

    // ✅ Get auth token
    const authStore = useAuthStore()
    const token = authStore.token || localStorage.getItem('auth_token')

    // Load model
    const modelFormat = model.model_format?.toLowerCase()

    console.log(`Loading model ${model.id}:`, model.url)

    if (modelFormat === 'obj') {
      // ✅ OBJ Loader with auth using fetch
      fetch(model.url, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': '*/*'
        }
      })
        .then(response => {
          if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`)
          }
          return response.text()
        })
        .then(objText => {
          const loader = new OBJLoader()
          const object = loader.parse(objText)

          // Center and scale
          const box = new THREE.Box3().setFromObject(object)
          const center = box.getCenter(new THREE.Vector3())
          const size = box.getSize(new THREE.Vector3())
          const maxDim = Math.max(size.x, size.y, size.z)
          const scale = 3 / maxDim

          object.scale.multiplyScalar(scale)
          object.position.sub(center.multiplyScalar(scale))

          scene.add(object)
          modelStates[model.id].loaded = true

          console.log(`Model ${model.id} loaded successfully`)

          // Animation loop
          const animate = () => {
            if (!modelScenes.value.has(model.id)) return

            requestAnimationFrame(animate)

            if (modelStates[model.id]?.autoRotate) {
              object.rotation.y += 0.005
            }

            controls.update()
            renderer.render(scene, camera)
          }
          animate()
        })
        .catch((error: any) => {
          console.error(`Failed to load OBJ model ${model.id}:`, error)
          modelStates[model.id].error = true
        })

    } else {
      // ✅ GLTF/GLB Loader with auth using fetch
      fetch(model.url, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/octet-stream, application/json, */*'
        }
      })
        .then(response => {
          if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`)
          }
          return response.arrayBuffer()
        })
        .then(buffer => {
          const loader = new GLTFLoader()
          loader.parse(buffer, '', (gltf) => {
            const object = gltf.scene

            // Center and scale
            const box = new THREE.Box3().setFromObject(object)
            const center = box.getCenter(new THREE.Vector3())
            const size = box.getSize(new THREE.Vector3())
            const maxDim = Math.max(size.x, size.y, size.z)
            const scale = 3 / maxDim

            object.scale.multiplyScalar(scale)
            object.position.sub(center.multiplyScalar(scale))

            scene.add(object)
            modelStates[model.id].loaded = true

            console.log(`Model ${model.id} loaded successfully`)

            // Animation loop
            const animate = () => {
              if (!modelScenes.value.has(model.id)) return

              requestAnimationFrame(animate)

              if (modelStates[model.id]?.autoRotate) {
                object.rotation.y += 0.005
              }

              controls.update()
              renderer.render(scene, camera)
            }
            animate()
          }, (error: any) => {
            console.error(`Failed to parse GLTF model ${model.id}:`, error)
            modelStates[model.id].error = true
          })
        })
        .catch((error: any) => {
          console.error(`Failed to load GLTF model ${model.id}:`, error)
          modelStates[model.id].error = true
        })
    }

    // Store scene for cleanup
    modelScenes.value.set(model.id, { scene, renderer, controls, camera })

  } catch (error) {
    console.error(`Error initializing model ${model.id}:`, error)
    modelStates[model.id].error = true
  }
}

const retryLoadModel = (model: any) => {
  modelStates[model.id].error = false
  modelStates[model.id].loaded = false

  const container = modelContainers.value.get(model.id)
  if (container) {
    // Clear container
    while (container.firstChild) {
      container.removeChild(container.firstChild)
    }
    // Reload
    load3DModel(model, container)
  }
}

const toggleAutoRotate = (modelId: number) => {
  if (modelStates[modelId]) {
    modelStates[modelId].autoRotate = !modelStates[modelId].autoRotate
  }
}

const viewModel = (model: any) => {
  currentModel.value = model
  viewDialogVisible.value = true

  nextTick(() => {
    if (dialogViewerContainer.value) {
      load3DModel(model, dialogViewerContainer.value)
    }
  })
}

const closeViewDialog = () => {
  // Cleanup dialog viewer
  if (dialogViewerContainer.value) {
    while (dialogViewerContainer.value.firstChild) {
      dialogViewerContainer.value.removeChild(dialogViewerContainer.value.firstChild)
    }
  }

  viewDialogVisible.value = false
  currentModel.value = null
}

const downloadModel = (model: any) => {
  window.open(model.url, '_blank')
  toast.add({
    severity: 'success',
    summary: 'Download Started',
    detail: `Downloading ${model.file_name}`,
    life: 2000
  })
}

const formatFileSize = (bytes: number) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
}

const formatDate = (date: string) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

// Cleanup on unmount
onBeforeUnmount(() => {
  modelScenes.value.forEach((sceneData) => {
    sceneData.renderer?.dispose()
    sceneData.controls?.dispose()
  })
  modelScenes.value.clear()
  modelContainers.value.clear()
})

onMounted(() => {
  loadModels()
})
</script>

<style scoped>
:deep(canvas) {
    display: block;
    max-width: 100%;
}
</style>