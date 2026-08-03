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
        <h1 class="text-3xl font-semibold leading-tight lg:text-4xl portal-brand">
          FURNISYNC
        </h1>
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
import * as THREE from 'three'
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls'

const props = withDefaults(defineProps<{
  theme?: 'light' | 'dark'
  title?: string
  subtitle?: string
  brand?: string
  footer?: string
}>(), {
  theme: 'light',
  title: 'Furnisync',
  subtitle: 'Showcase your catalog in 3D and create immersive product stories in minutes.',
  brand: 'Furnisync',
  footer: 'Interactive 3D model preview',
})

const host = ref<HTMLElement | null>(null)
let renderer: THREE.WebGLRenderer | null = null
let scene: THREE.Scene | null = null
let camera: THREE.PerspectiveCamera | null = null
let mesh: THREE.Mesh | null = null
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
  mesh = null
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
  camera.position.set(0, 0.6, 4)

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

  const geometry = new THREE.TorusKnotGeometry(1, 0.35, 140, 24)
  const material = new THREE.MeshStandardMaterial({
    color: props.theme === 'dark' ? 0x38bdf8 : 0xf8fafc,
    metalness: 0.35,
    roughness: 0.25,
  })
  mesh = new THREE.Mesh(geometry, material)
  const box = new THREE.Box3().setFromObject(mesh)
  const center = box.getCenter(new THREE.Vector3())
  mesh.position.sub(center)
  scene.add(mesh)

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
