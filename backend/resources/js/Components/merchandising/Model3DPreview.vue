<template>
  <div class="relative w-full overflow-hidden rounded-lg border border-gray-200 bg-gray-50" :style="{ height }">
    <div v-if="loading" class="absolute inset-0 z-10 flex items-center justify-center bg-white/80">
      <div class="text-center">
        <i class="pi pi-spin pi-spinner text-xl text-gray-600"></i>
        <p class="mt-2 text-xs text-gray-600">Loading 3D preview...</p>
      </div>
    </div>
    <div v-if="error" class="absolute inset-0 z-10 flex items-center justify-center bg-red-50">
      <div class="px-4 text-center text-sm text-red-700">
        Failed to load 3D model preview.
      </div>
    </div>
    <div ref="host" class="h-full w-full"></div>
  </div>
</template>

<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import * as THREE from 'three'
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader'
import { OBJLoader } from 'three/examples/jsm/loaders/OBJLoader'
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls'
import { useAuthStore } from '@/stores/auth'

const props = withDefaults(defineProps<{
  modelUrl: string
  modelFormat?: string
  authToken?: string | null
  cameraX?: number
  cameraY?: number
  zoom?: number
  height?: string
}>(), {
  modelFormat: 'glb',
  cameraX: 0,
  cameraY: 15,
  zoom: 1.5,
  height: '320px',
})

const authStore = useAuthStore()
const host = ref<HTMLElement | null>(null)
const loading = ref(false)
const error = ref(false)

let scene: THREE.Scene | null = null
let camera: THREE.PerspectiveCamera | null = null
let renderer: THREE.WebGLRenderer | null = null
let controls: OrbitControls | null = null
let model: THREE.Object3D | null = null
let animationId: number | null = null

const getModelFormat = () => {
  const raw = (props.modelFormat || '').toLowerCase()
  if (raw) return raw
  return props.modelUrl?.split('.').pop()?.toLowerCase() || 'glb'
}

const setCameraFromProps = () => {
  if (!camera || !controls) return
  const distance = Math.max(1, Number(props.zoom || 1.5) * 3)
  const xRad = THREE.MathUtils.degToRad(Number(props.cameraX || 0))
  const yRad = THREE.MathUtils.degToRad(Number(props.cameraY || 15))
  camera.position.set(
    Math.sin(xRad) * distance,
    Math.sin(yRad) * distance + 1.5,
    Math.cos(xRad) * distance
  )
  controls.target.set(0, 0, 0)
  controls.update()
}

const animate = () => {
  if (!scene || !camera || !renderer || !controls) return
  animationId = requestAnimationFrame(animate)
  controls.update()
  renderer.render(scene, camera)
}

const cleanup = () => {
  if (animationId) {
    cancelAnimationFrame(animationId)
    animationId = null
  }
  if (controls) {
    controls.dispose()
    controls = null
  }
  if (renderer) {
    renderer.dispose()
    if (host.value && renderer.domElement.parentNode === host.value) {
      host.value.removeChild(renderer.domElement)
    }
    renderer = null
  }
  scene = null
  camera = null
  model = null
}

const normalizeModel = (object: THREE.Object3D) => {
  const box = new THREE.Box3().setFromObject(object)
  const center = box.getCenter(new THREE.Vector3())
  const size = box.getSize(new THREE.Vector3())
  const maxDim = Math.max(size.x, size.y, size.z) || 1
  const scale = 2.8 / maxDim
  object.scale.multiplyScalar(scale)
  object.position.sub(center.multiplyScalar(scale))
}

const loadModel = async () => {
  if (!host.value || !props.modelUrl) return

  cleanup()
  loading.value = true
  error.value = false

  try {
    const width = host.value.clientWidth || 500
    const height = host.value.clientHeight || 320

    scene = new THREE.Scene()
    scene.background = new THREE.Color(0xf8fafc)

    camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 1000)
    renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true })
    renderer.setSize(width, height)
    renderer.setPixelRatio(window.devicePixelRatio)
    host.value.appendChild(renderer.domElement)

    scene.add(new THREE.AmbientLight(0xffffff, 0.75))
    const keyLight = new THREE.DirectionalLight(0xffffff, 0.9)
    keyLight.position.set(4, 8, 6)
    scene.add(keyLight)

    controls = new OrbitControls(camera, renderer.domElement)
    controls.enableDamping = true
    controls.dampingFactor = 0.08
    controls.minDistance = 1
    controls.maxDistance = 20
    controls.maxPolarAngle = Math.PI / 2

    setCameraFromProps()

    const token = props.authToken || authStore.token || localStorage.getItem('auth_token') || localStorage.getItem('access_token')
    const response = await fetch(props.modelUrl, {
      headers: token ? { Authorization: `Bearer ${token}` } : {},
    })
    if (!response.ok) throw new Error(`HTTP ${response.status}`)

    const format = getModelFormat()
    if (format === 'obj') {
      const text = await response.text()
      const obj = new OBJLoader().parse(text)
      normalizeModel(obj)
      scene.add(obj)
      model = obj
    } else {
      const buffer = await response.arrayBuffer()
      await new Promise<void>((resolve, reject) => {
        new GLTFLoader().parse(
          buffer,
          '',
          (gltf: any) => {
            const obj = gltf.scene
            normalizeModel(obj)
            scene?.add(obj)
            model = obj
            resolve()
          },
          (e: any) => reject(e)
        )
      })
    }

    loading.value = false
    animate()
  } catch (e) {
    console.error('Model3DPreview error:', e)
    loading.value = false
    error.value = true
  }
}

const handleResize = () => {
  if (!host.value || !camera || !renderer) return
  const width = host.value.clientWidth || 500
  const height = host.value.clientHeight || 320
  camera.aspect = width / height
  camera.updateProjectionMatrix()
  renderer.setSize(width, height)
}

watch(
  () => [props.modelUrl, props.modelFormat],
  async () => {
    await nextTick()
    loadModel()
  }
)

watch(
  () => [props.cameraX, props.cameraY, props.zoom],
  () => setCameraFromProps()
)

onMounted(async () => {
  await nextTick()
  loadModel()
  window.addEventListener('resize', handleResize)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', handleResize)
  cleanup()
})
</script>
