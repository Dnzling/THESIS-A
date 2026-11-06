import { ref } from 'vue'

export type RecentlyViewedProduct = { id: number; product_name: string; image?: string | null; price?: number | null; category?: string | null; viewed_at: string }
const key = 'ecommerce_recently_viewed_products'
const items = ref<RecentlyViewedProduct[]>([])
let initialized = false

function load() {
  if (initialized || typeof window === 'undefined') return
  initialized = true
  try { items.value = (JSON.parse(localStorage.getItem(key) || '[]') || []).filter((item: any) => Number(item?.id) > 0).slice(0, 12) } catch { items.value = [] }
}
function save() { if (typeof window !== 'undefined') localStorage.setItem(key, JSON.stringify(items.value)) }

export function useRecentlyViewedProducts() {
  load()
  function add(product: Partial<RecentlyViewedProduct> & { id: number; product_name: string }) {
    const next = { id: Number(product.id), product_name: product.product_name, image: product.image || null, price: Number(product.price || 0), category: product.category || null, viewed_at: new Date().toISOString() }
    items.value = [next, ...items.value.filter(item => item.id !== next.id)].slice(0, 12); save()
  }
  function clear() { items.value = []; save() }
  return { recentlyViewed: items, addRecentlyViewed: add, clearRecentlyViewed: clear }
}
