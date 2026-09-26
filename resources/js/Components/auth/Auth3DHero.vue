<template>
  <section
    class="relative flex h-full w-full flex-col gap-8 overflow-hidden p-8 lg:p-10"
    :class="theme === 'dark' ? 'bg-slate-950 text-slate-100' : 'bg-gradient-to-br from-orange-500 via-orange-600 to-orange-700 text-white'"
  >
    <div class="absolute inset-0 opacity-25">
      <div class="pointer-events-none absolute -left-20 -top-20 h-72 w-72 rounded-full bg-white/30 blur-3xl"></div>
      <div class="pointer-events-none absolute -bottom-20 -right-20 h-80 w-80 rounded-full bg-white/20 blur-3xl"></div>
    </div>

    <div class="relative z-10 space-y-6">

      <div>
        <Link href="/" class="inline-flex text-3xl font-semibold leading-tight lg:text-4xl portal-brand">
          FURNISYNC
        </Link>
        <p class="mt-4 max-w-md text-sm leading-relaxed opacity-90 lg:text-base">
          {{ subtitle }}
        </p>
      </div>
    </div>

    <div class="relative flex flex-1 items-center justify-center">
      <div class="h-full w-full rounded-2xl">
        <div ref="host" class="h-full w-full"></div>
      </div>
    </div>

    <div class="relative z-10 text-xs opacity-70">
      {{ footer }}
    </div>
  </section>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import * as THREE from 'three'
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls'

const props = withDefaults(defineProps<{
  theme?: 'light' | 'dark'
  title?: string
  subtitle?: string
  brand?: string
  footer?: string
  visual?: 'abstract' | 'furniture'
}>(), {
  theme: 'light',
  title: 'Furnisync',
  subtitle: 'Showcase your catalog in 3D and create immersive product stories in minutes.',
  brand: 'Furnisync',
  footer: 'Interactive 3D model preview',
  visual: 'abstract',
})

const host = ref<HTMLElement | null>(null)
let renderer: THREE.WebGLRenderer | null = null
let scene: THREE.Scene | null = null
let camera: THREE.PerspectiveCamera | null = null
let model: THREE.Object3D | null = null
let controls: OrbitControls | null = null
let animationId: number | null = null

const cleanup = () => {
  if (animationId) {
    cancelAnimationFrame(animationId)
    animationId = null
  }
  if (renderer && host.value && renderer.domElement.parentNode === host.value) {
    host.value.removeChild(renderer.domElement)
  }
  controls?.dispose()
  controls = null
  renderer?.dispose()
  renderer = null
  scene = null
  camera = null
  model?.traverse((child) => {
    if (!(child instanceof THREE.Mesh)) return
    child.geometry.dispose()
    const materials = Array.isArray(child.material) ? child.material : [child.material]
    materials.forEach((material) => material.dispose())
  })
  model = null
}

const createFurnitureModel = () => {
  const chair = new THREE.Group()
  const walnut = new THREE.MeshStandardMaterial({ color: 0x694028, roughness: 0.55, metalness: 0.08 })
  const edge = new THREE.MeshStandardMaterial({ color: 0x472b20, roughness: 0.58 })
  const fabric = new THREE.MeshStandardMaterial({ color: 0xf4eee4, roughness: 0.95 })
  const accent = new THREE.MeshStandardMaterial({ color: 0xd5b69d, roughness: 0.9 })
  const addBox = (size: [number, number, number], position: [number, number, number], material: THREE.Material) => {
    const part = new THREE.Mesh(new THREE.BoxGeometry(...size), material)
    part.position.set(...position)
    chair.add(part)
    return part
  }

  // A compact upholstered accent chair keeps the hero relevant without depending on uploaded assets.
  addBox([2.05, 0.17, 1.65], [0, -0.35, 0], walnut)
  addBox([1.75, 0.28, 1.46], [0, -0.18, 0.02], fabric)
  addBox([2.05, 1.65, 0.18], [0, 0.53, -0.74], walnut)
  addBox([1.73, 1.23, 0.16], [0, 0.51, -0.61], fabric)
  for (const x of [-0.93, 0.93]) {
    addBox([0.16, 0.94, 0.16], [x, -0.91, -0.62], edge)
    addBox([0.16, 0.94, 0.16], [x, -0.91, 0.62], edge)
    addBox([0.14, 0.12, 1.44], [x, 0.19, 0], walnut)
    addBox([0.19, 0.26, 0.2], [x, 0.04, 0.58], walnut)
  }
  addBox([1.69, 0.1, 0.12], [0, -1.06, 0.59], accent)
  chair.rotation.y = -0.38
  return chair
}

const animate = () => {
  if (!renderer || !scene || !camera) return
  animationId = requestAnimationFrame(animate)
  controls?.update()
  renderer.render(scene, camera)
}

const initScene = () => {
  if (!host.value) return
  cleanup()
  const width = host.value.clientWidth || 500
  const height = host.value.clientHeight || 500

  scene = new THREE.Scene()
  camera = new THREE.PerspectiveCamera(60, width / height, 0.1, 1000)
  camera.position.set(0, 0.6, props.visual === 'furniture' ? 4.7 : 4)

  renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true })
  renderer.setSize(width, height)
  renderer.setPixelRatio(window.devicePixelRatio)
  host.value.appendChild(renderer.domElement)

  scene.add(new THREE.AmbientLight(0xffffff, 0.85))
  const key = new THREE.DirectionalLight(0xffffff, 1.1)
  key.position.set(3, 4, 5)
  scene.add(key)
  const rim = new THREE.DirectionalLight(0xffffff, 0.6)
  rim.position.set(-4, 2, -3)
  scene.add(rim)

  model = props.visual === 'furniture'
    ? createFurnitureModel()
    : new THREE.Mesh(
      new THREE.TorusKnotGeometry(1, 0.35, 140, 24),
      new THREE.MeshStandardMaterial({
        color: props.theme === 'dark' ? 0x38bdf8 : 0xf8fafc,
        metalness: 0.35,
        roughness: 0.25,
      }),
    )
  const box = new THREE.Box3().setFromObject(model)
  const center = box.getCenter(new THREE.Vector3())
  model.position.sub(center)
  scene.add(model)

  controls = new OrbitControls(camera, renderer.domElement)
  controls.enableDamping = true
  controls.dampingFactor = 0.08
  controls.minDistance = 2
  controls.maxDistance = 7
  controls.target.set(0, 0, 0)
  controls.update()

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

.portal-brand {
  font-family: 'Barabara', sans-serif;

}
</style>
