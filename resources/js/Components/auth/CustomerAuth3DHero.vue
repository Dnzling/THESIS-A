<template>
  <section class="relative flex h-full w-full flex-col gap-8 overflow-hidden bg-gradient-to-br from-orange-500 via-orange-600 to-orange-700 p-8 text-white shadow-2xl lg:p-10">
    <div class="absolute inset-0 opacity-25">
      <div class="pointer-events-none absolute -left-24 -top-24 h-72 w-72 rounded-full"></div>
      <div class="pointer-events-none absolute -bottom-20 -right-16 h-80 w-80 rounded-full"></div>
    </div>

    <div class="relative z-10 space-y-4">
      <h2 class="text-4xl font-semibold leading-tight sm:text-4xl">
        {{ title }}
      </h2>
      <p class="max-w-md text-sm leading-relaxed text-white/90 sm:text-base">
        {{ subtitle }}
      </p>
    </div>

    <div class="relative z-10 flex flex-1 items-center justify-center">
      <div class="h-full w-full rounded-2xl p-2">
        <div ref="host" class="h-full w-full"></div>
      </div>
    </div>

    <div class="relative z-10 text-xs text-white/80">
      {{ footer }}
    </div>
  </section>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue'
import * as THREE from 'three'
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls'
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader'

const props = withDefaults(defineProps<{
  title?: string
  subtitle?: string
  footer?: string
  modelUrl?: string
}>(), {
  title: 'Shop your next favorite piece in 3D',
  subtitle: 'Rotate, zoom, and explore every fabric detail before you add it to cart.',
  footer: 'Interactive 3D sofa preview powered by Furnisync',
  modelUrl: '/storage/platform/sofa.glb',
})

const host = ref<HTMLElement | null>(null)
let renderer: THREE.WebGLRenderer | null = null
let scene: THREE.Scene | null = null
let camera: THREE.PerspectiveCamera | null = null
let controls: OrbitControls | null = null
let animationId: number | null = null
let currentModel: THREE.Object3D | null = null

const cleanup = () => {
  if (animationId) {
    cancelAnimationFrame(animationId)
    animationId = null
  }
  controls?.dispose()
  controls = null
  if (renderer && host.value && renderer.domElement.parentNode === host.value) {
    host.value.removeChild(renderer.domElement)
  }
  renderer?.dispose()
  renderer = null
  scene = null
  camera = null
  currentModel = null
}

const animate = () => {
  if (!renderer || !scene || !camera) return
  animationId = requestAnimationFrame(animate)
  controls?.update()
  renderer.render(scene, camera)
}

const fitCameraToObject = (object: THREE.Object3D) => {
  if (!camera || !controls) return
  const box = new THREE.Box3().setFromObject(object)
  const size = box.getSize(new THREE.Vector3())
  const center = box.getCenter(new THREE.Vector3())

  const maxDim = Math.max(size.x, size.y, size.z)
  const fov = camera.fov * (Math.PI / 180)
  let cameraZ = Math.abs(maxDim / (2 * Math.tan(fov / 2)))
  cameraZ *= 1.6

  camera.position.set(center.x, center.y + size.y * 0.15, center.z + cameraZ)
  camera.near = maxDim / 100
  camera.far = maxDim * 10
  camera.updateProjectionMatrix()

  controls.target.copy(center)
  controls.minDistance = maxDim * 0.7
  controls.maxDistance = maxDim * 3
  controls.update()
}

const loadFallback = () => {
  if (!scene) return
  const geometry = new THREE.TorusKnotGeometry(1, 0.35, 120, 24)
  const material = new THREE.MeshStandardMaterial({
    color: 0xfef3c7,
    metalness: 0.35,
    roughness: 0.28,
  })
  currentModel = new THREE.Mesh(geometry, material)
  scene.add(currentModel)
  fitCameraToObject(currentModel)
}

const loadModel = async () => {
  if (!scene) return
  const loader = new GLTFLoader()

  try {
    const gltf = await loader.loadAsync(props.modelUrl)
    currentModel = gltf.scene
    currentModel.traverse((child) => {
      if ((child as THREE.Mesh).isMesh) {
        const mesh = child as THREE.Mesh
        if (Array.isArray(mesh.material)) return
        const material = mesh.material as THREE.MeshStandardMaterial
        material.metalness = material.metalness ?? 0.2
        material.roughness = material.roughness ?? 0.65
      }
    })
    scene.add(currentModel)
    fitCameraToObject(currentModel)
  } catch (error) {
    loadFallback()
  }
}

const initScene = () => {
  if (!host.value) return
  cleanup()

  const width = host.value.clientWidth || 500
  const height = host.value.clientHeight || 500

  scene = new THREE.Scene()
  camera = new THREE.PerspectiveCamera(55, width / height, 0.1, 1000)
  camera.position.set(0, 1, 4)

  renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true })
  renderer.setSize(width, height)
  renderer.setPixelRatio(window.devicePixelRatio)
  renderer.outputColorSpace = THREE.SRGBColorSpace
  renderer.toneMapping = THREE.ACESFilmicToneMapping
  renderer.toneMappingExposure = 1.1
  host.value.appendChild(renderer.domElement)

  scene.add(new THREE.AmbientLight(0xffffff, 0.9))
  const key = new THREE.DirectionalLight(0xffffff, 1.2)
  key.position.set(3, 4, 5)
  scene.add(key)
  const fill = new THREE.DirectionalLight(0xffffff, 0.7)
  fill.position.set(-3, 2, 4)
  scene.add(fill)
  const rim = new THREE.DirectionalLight(0xffffff, 0.5)
  rim.position.set(-4, 3, -3)
  scene.add(rim)

  controls = new OrbitControls(camera, renderer.domElement)
  controls.enableDamping = true
  controls.dampingFactor = 0.08
  controls.enablePan = false
  controls.autoRotate = true
  controls.autoRotateSpeed = 0.6

  loadModel()
  animate()
}

const handleResize = () => {
  initScene()
}

onMounted(() => {
  initScene()
  window.addEventListener('resize', handleResize)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', handleResize)
  cleanup()
})
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap');

section {
  font-family: 'Space Grotesk', sans-serif;
}
</style>
