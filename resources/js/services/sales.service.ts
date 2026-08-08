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

  async getPosOrderReceiptUrl(id: number | string) {
    return `/api/sales/pos/orders/${id}/receipt`
  }

  async sendPosOrderToLogistics(id: number | string) {
    const res = await axiosClient.post(`/api/sales/pos/orders/${id}/send-to-logistics`)
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

  async getEcommerceOrderReceiptUrl(id: number | string) {
    return `/api/sales/ecommerce-orders/${id}/receipt`
  }

  async updateEcommerceOrderStatus(id: number | string, payload: any) {
    const res = await axiosClient.put(`/api/sales/ecommerce-orders/${id}/status`, payload)
    return res.data
  }

  async reviewEcommerceOrderCancellation(orderId: number | string, requestId: number | string, payload: { status: 'approved' | 'rejected'; review_notes?: string }) {
    const res = await axiosClient.put(`/api/sales/ecommerce-orders/${orderId}/cancellation-requests/${requestId}/review`, payload)
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

  async updateChatMessage(threadId: number | string, messageId: number | string, payload: { message: string }) {
    const res = await axiosClient.put(`/api/sales/chats/threads/${threadId}/messages/${messageId}`, payload)
    return res.data
  }

  async unsendChatMessage(threadId: number | string, messageId: number | string) {
    const res = await axiosClient.delete(`/api/sales/chats/threads/${threadId}/messages/${messageId}`)
    return res.data
  }

  async getReviews(params?: any) {
    const res = await axiosClient.get('/api/sales/reviews', { params })
    return res.data
  }

  async getVouchers(params?: any) {
    const res = await axiosClient.get('/api/sales/vouchers', { params })
    return res.data
  }

  async createVoucher(payload: any) {
    const res = await axiosClient.post('/api/sales/vouchers', payload)
    return res.data
  }

  async getVoucher(id: number | string) {
    const res = await axiosClient.get(`/api/sales/vouchers/${id}`)
    return res.data
  }

  async updateVoucher(id: number | string, payload: any) {
    const res = await axiosClient.put(`/api/sales/vouchers/${id}`, payload)
    return res.data
  }

  async getReview(id: number | string) {
    const res = await axiosClient.get(`/api/sales/reviews/${id}`)
    return res.data
  }

  async replyReview(id: number | string, payload: { reply: string }) {
    const res = await axiosClient.put(`/api/sales/reviews/${id}/reply`, payload)
    return res.data
  }

  async getRefunds(params?: any) {
    const res = await axiosClient.get('/api/sales/refunds', { params })
    return res.data
  }

  async getRefund(id: number | string) {
    const res = await axiosClient.get(`/api/sales/refunds/${id}`)
    return res.data
  }

  async createRefund(payload: any) {
    const res = await axiosClient.post('/api/sales/refunds', payload)
    return res.data
  }

  async updateRefundStatus(id: number | string, payload: { status: 'approved' | 'rejected'; notes?: string }) {
    const res = await axiosClient.put(`/api/sales/refunds/${id}/status`, payload)
    return res.data
  }

  async getReturns(params?: any) {
    const res = await axiosClient.get('/api/sales/returns', { params })
    return res.data
  }

  async getReturn(id: number | string) {
    const res = await axiosClient.get(`/api/sales/returns/${id}`)
    return res.data
  }

  async updateReturnStatus(id: number | string, payload: { status: 'approved' | 'rejected' | 'received' | 'refunded'; return_type?: 'refund' | 'replacement'; review_notes?: string }) {
    const res = await axiosClient.put(`/api/sales/returns/${id}/status`, payload)
    return res.data
  }

  async scheduleReturnPickup(id: number | string, payload: { scheduled_at: string; pickup_name?: string; pickup_phone?: string; pickup_address?: string; notes?: string }) {
    const res = await axiosClient.post(`/api/sales/returns/${id}/pickup`, payload)
    return res.data
  }

  async receiveReturn(id: number | string, payload: { received_quantity: number; condition: 'good' | 'bad'; notes?: string }) {
    const res = await axiosClient.post(`/api/sales/returns/${id}/receive`, payload)
    return res.data
  }

  async createReturnRefund(id: number | string, payload: { amount: number; reason?: string; notes?: string; mark_as_approved?: boolean }) {
    const res = await axiosClient.post(`/api/sales/returns/${id}/refund`, payload)
    return res.data
  }

  async getReportsSummary(params?: any) {
    const res = await axiosClient.get('/api/sales/reports/summary', { params })
    return res.data
  }
}

export default new SalesService()
