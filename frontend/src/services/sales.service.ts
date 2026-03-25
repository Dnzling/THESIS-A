import axiosClient from '@/axios'

class SalesService {
  async getDashboard(params?: any) {
    const res = await axiosClient.get('/api/sales/dashboard', { params })
    return res.data
  }

  async getLeads(params?: any) {
    const res = await axiosClient.get('/api/sales/crm/leads', { params })
    return res.data
  }

  async createLead(payload: any) {
    const res = await axiosClient.post('/api/sales/crm/leads', payload)
    return res.data
  }

  async updateLead(id: number | string, payload: any) {
    const res = await axiosClient.put(`/api/sales/crm/leads/${id}`, payload)
    return res.data
  }

  async updateLeadStage(id: number | string, payload: any) {
    const res = await axiosClient.post(`/api/sales/crm/leads/${id}/stage`, payload)
    return res.data
  }

  async getLeadActivities(id: number | string, params?: any) {
    const res = await axiosClient.get(`/api/sales/crm/leads/${id}/activities`, { params })
    return res.data
  }

  async addLeadActivity(id: number | string, payload: any) {
    const res = await axiosClient.post(`/api/sales/crm/leads/${id}/activities`, payload)
    return res.data
  }

  async getPosProducts(params?: any) {
    const res = await axiosClient.get('/api/sales/pos/products', { params })
    return res.data
  }

  async checkout(payload: any) {
    const res = await axiosClient.post('/api/sales/pos/checkout', payload)
    return res.data
  }

  async getPaymentAnalytics(params?: any) {
    const res = await axiosClient.get('/api/sales/analytics/payments', { params })
    return res.data
  }

  async getPosOrders(params?: any) {
    const res = await axiosClient.get('/api/sales/pos/orders', { params })
    return res.data
  }

  async getPosOrder(id: number | string) {
    const res = await axiosClient.get(`/api/sales/pos/orders/${id}`)
    return res.data
  }

  async syncPosOrderPayment(id: number | string) {
    const res = await axiosClient.post(`/api/sales/pos/orders/${id}/sync-payment`)
    return res.data
  }

  async getOrderDeliveries(params?: any) {
    const res = await axiosClient.get('/api/sales/order-deliveries', { params })
    return res.data
  }

  async getOrderDelivery(id: number | string) {
    const res = await axiosClient.get(`/api/sales/order-deliveries/${id}`)
    return res.data
  }

  async getOrderDeliveryDrivers(params?: any) {
    const res = await axiosClient.get('/api/sales/order-deliveries/drivers', { params })
    return res.data
  }

  async updateOrderDeliveryStatus(id: number | string, payload: any) {
    const res = await axiosClient.put(`/api/sales/order-deliveries/${id}/status`, payload)
    return res.data
  }

  async assignOrderDeliveryDriver(id: number | string, payload: any) {
    const res = await axiosClient.post(`/api/sales/order-deliveries/${id}/assign-driver`, payload)
    return res.data
  }

  async uploadOrderDeliveryProof(id: number | string, payload: FormData) {
    const res = await axiosClient.post(`/api/sales/order-deliveries/${id}/proof`, payload, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    return res.data
  }

  async getOrderDeliveryLogs(id: number | string, params?: any) {
    const res = await axiosClient.get(`/api/sales/order-deliveries/${id}/logs`, { params })
    return res.data
  }

  async addOrderDeliveryLog(id: number | string, payload: any) {
    const res = await axiosClient.post(`/api/sales/order-deliveries/${id}/logs`, payload)
    return res.data
  }

  async getEcommerceOrders(params?: any) {
    const res = await axiosClient.get('/api/sales/ecommerce-orders', { params })
    return res.data
  }

  async getEcommerceOrder(id: number | string) {
    const res = await axiosClient.get(`/api/sales/ecommerce-orders/${id}`)
    return res.data
  }

  async updateEcommerceOrderStatus(id: number | string, payload: any) {
    const res = await axiosClient.put(`/api/sales/ecommerce-orders/${id}/status`, payload)
    return res.data
  }

  async assignEcommerceOrderDelivery(id: number | string, payload: any) {
    const res = await axiosClient.post(`/api/sales/ecommerce-orders/${id}/assign-delivery`, payload)
    return res.data
  }

  async updateEcommerceOrderDeliveryAssignment(id: number | string, payload: any) {
    const res = await axiosClient.put(`/api/sales/ecommerce-orders/${id}/delivery-assignment`, payload)
    return res.data
  }

  async getEcommerceOrderBranchCandidates(id: number | string) {
    const res = await axiosClient.get(`/api/sales/ecommerce-orders/${id}/branch-candidates`)
    return res.data
  }

  async passEcommerceOrderToBranch(id: number | string, payload: { to_branch_id: number; notes?: string }) {
    const res = await axiosClient.post(`/api/sales/ecommerce-orders/${id}/pass-branch`, payload)
    return res.data
  }

  async getEcommerceOrderChatMessages(id: number | string, params?: any) {
    const res = await axiosClient.get(`/api/sales/ecommerce-orders/${id}/chat/messages`, { params })
    return res.data
  }

  async sendEcommerceOrderChatMessage(id: number | string, payload: { message: string }) {
    const res = await axiosClient.post(`/api/sales/ecommerce-orders/${id}/chat/messages`, payload)
    return res.data
  }

  async getChatThreads(params?: any) {
    const res = await axiosClient.get('/api/sales/chats/threads', { params })
    return res.data
  }

  async getChatMessages(threadId: number | string, params?: any) {
    const res = await axiosClient.get(`/api/sales/chats/threads/${threadId}/messages`, { params })
    return res.data
  }

  async sendChatMessage(threadId: number | string, payload: any) {
    const res = await axiosClient.post(`/api/sales/chats/threads/${threadId}/messages`, payload)
    return res.data
  }
}

export default new SalesService()
