import axiosClient from '../axios'

const ecommerceService = {
  getProducts(params?: any) {
    return axiosClient.get('/api/ecommerce/products', { params })
  },

  getActiveStockProducts(params?: any) {
    return axiosClient.get('/api/ecommerce/products/active-stock', { params })
  },

  getProduct(id: number | string) {
    return axiosClient.get(`/api/ecommerce/products/${id}`)
  },

  getStores(params?: any) {
    return axiosClient.get('/api/ecommerce/stores', { params })
  },

  getStore(storeId: number | string) {
    return axiosClient.get(`/api/ecommerce/stores/${storeId}`)
  },

  getStoreProducts(storeId: number | string, params?: any) {
    return axiosClient.get(`/api/ecommerce/stores/${storeId}/products`, { params })
  },

  getStoreReviews(storeId: number | string, params?: any) {
    return axiosClient.get(`/api/ecommerce/stores/${storeId}/reviews`, { params })
  },

  getDssRecommendations(payload: {
    budget_min: number
    budget_max: number
    category_id?: number | null
    length_cm?: number | null
    width_cm?: number | null
    height_cm?: number | null
    per_page?: number
    page?: number
  }) {
    return axiosClient.post('/api/ecommerce/dss/recommendations', payload)
  },

  followStore(storeId: number | string) {
    return axiosClient.post(`/api/ecommerce/stores/${storeId}/follow`)
  },

  unfollowStore(storeId: number | string) {
    return axiosClient.delete(`/api/ecommerce/stores/${storeId}/follow`)
  },

  getCart() {
    return axiosClient.get('/api/ecommerce/cart')
  },

  addToCart(payload: { product_id: number; variation_id?: number | null; quantity: number }) {
    return axiosClient.post('/api/ecommerce/cart/items', payload)
  },

  updateCartItem(itemId: number, payload: { quantity: number }) {
    return axiosClient.put(`/api/ecommerce/cart/items/${itemId}`, payload)
  },

  removeCartItem(itemId: number) {
    return axiosClient.delete(`/api/ecommerce/cart/items/${itemId}`)
  },

  clearCart() {
    return axiosClient.post('/api/ecommerce/cart/clear')
  },

  getAddressTemplates() {
    return axiosClient.get('/api/ecommerce/address-templates')
  },

  createAddressTemplate(payload: {
    full_name: string
    contact_number: string
    province: string
    city: string
    barangay: string
    address_line: string
    is_default?: boolean
  }) {
    return axiosClient.post('/api/ecommerce/address-templates', payload)
  },

  updateAddressTemplate(id: number, payload: {
    full_name: string
    contact_number: string
    province: string
    city: string
    barangay: string
    address_line: string
    is_default?: boolean
  }) {
    return axiosClient.put(`/api/ecommerce/address-templates/${id}`, payload)
  },

  validateVoucher(payload: {
    code: string
    amount?: number
  }) {
    return axiosClient.post('/api/ecommerce/vouchers/validate', payload)
  },

  getProvinces() {
    return axiosClient.get('/api/address/provinces')
  },

  getCities(provinceId: string) {
    return axiosClient.get(`/api/address/cities/${provinceId}`)
  },

  getBarangays(cityId: string) {
    return axiosClient.get(`/api/address/barangays/${cityId}`)
  },

  checkout(payload: {
    shipping_name: string
    shipping_phone?: string
    shipping_email?: string
    shipping_address: string
    customer_latitude?: number
    customer_longitude?: number
    payment_method: 'cod' | 'bank_transfer' | 'card' | 'e_wallet'
    shipping_fee?: number
    discount_amount?: number
    voucher_code?: string
    notes?: string
    item_ids?: number[]
  }) {
    return axiosClient.post('/api/ecommerce/checkout', payload)
  },

  getOrders(params?: any) {
    return axiosClient.get('/api/ecommerce/orders', { params })
  },

  getOrder(id: number | string) {
    return axiosClient.get(`/api/ecommerce/orders/${id}`)
  },

  requestOrderCancellation(orderId: number | string, payload: {
    reason: string
    details?: string
  }) {
    return axiosClient.post(`/api/ecommerce/orders/${orderId}/cancel-requests`, payload)
  },

  requestOrderReturn(itemId: number | string, payload: {
    reason: string
    details?: string
    requested_quantity?: number
  }) {
    return axiosClient.post(`/api/ecommerce/order-items/${itemId}/return-requests`, payload)
  },

  submitItemReview(itemId: number | string, payload: {
    rating: number
    review_text?: string
  }) {
    return axiosClient.post(`/api/ecommerce/order-items/${itemId}/reviews`, payload)
  },

  getChatThreads(params?: any) {
    return axiosClient.get('/api/ecommerce/chat/threads', { params })
  },

  getStoreChatMessages(storeId: number | string, params?: any) {
    return axiosClient.get(`/api/ecommerce/chat/stores/${storeId}/messages`, { params })
  },

  sendStoreChatMessage(storeId: number | string, payload: { message: string; order_id?: number }) {
    return axiosClient.post(`/api/ecommerce/chat/stores/${storeId}/messages`, payload)
  },
}

export default ecommerceService
