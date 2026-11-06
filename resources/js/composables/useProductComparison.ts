import { computed, ref } from 'vue'

export type ComparisonProduct = {
  id: number
  product_name: string
  image?: string | null
  price?: number | string | null
  category?: string | null
}

const STORAGE_KEY = 'ecommerce_product_comparison'
const MAX_PRODUCTS = 5
const comparedProducts = ref<ComparisonProduct[]>([])
let initialized = false

function initialize() {
  if (initialized || typeof window === 'undefined') return
  initialized = true
  try {
    const stored = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]')
    comparedProducts.value = Array.isArray(stored)
      ? stored.filter((item) => Number(item?.id) > 0).slice(0, MAX_PRODUCTS)
      : []
  } catch {
    comparedProducts.value = []
  }
}

function persist() {
  if (typeof window === 'undefined') return
  localStorage.setItem(STORAGE_KEY, JSON.stringify(comparedProducts.value))
  window.dispatchEvent(new CustomEvent('ecommerce-comparison-updated', { detail: comparedProducts.value }))
}

export function useProductComparison() {
  initialize()

  const count = computed(() => comparedProducts.value.length)
  const isFull = computed(() => count.value >= MAX_PRODUCTS)

  function contains(productId: number | string) {
    return comparedProducts.value.some((item) => item.id === Number(productId))
  }

  function add(product: ComparisonProduct) {
    const id = Number(product.id)
    if (!id || contains(id)) return { added: false, reason: 'exists' as const }
    if (isFull.value) return { added: false, reason: 'full' as const }
    comparedProducts.value = [...comparedProducts.value, { ...product, id }]
    persist()
    return { added: true, reason: null }
  }

  function remove(productId: number | string) {
    const id = Number(productId)
    const existed = contains(id)
    comparedProducts.value = comparedProducts.value.filter((item) => item.id !== id)
    if (existed) persist()
  }

  function clear() {
    comparedProducts.value = []
    persist()
  }

  return { products: comparedProducts, count, isFull, maxProducts: MAX_PRODUCTS, contains, add, remove, clear }
}
