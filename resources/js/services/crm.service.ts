import axiosClient from '@/axios'

class CrmService {
  async getPaymentAnalytics(params?: any) {
    const res = await axiosClient.get('/api/crm/dashboard/payment-analytics', { params })
    return res.data
  }

  async getLeads(params?: any) {
    const res = await axiosClient.get('/api/crm/leads', { params })
    return res.data
  }

  async createLead(payload: any) {
    const res = await axiosClient.post('/api/crm/leads', payload)
    return res.data
  }

  async updateLead(id: number | string, payload: any) {
    const res = await axiosClient.put(`/api/crm/leads/${id}`, payload)
    return res.data
  }

  async updateLeadStage(id: number | string, payload: any) {
    const res = await axiosClient.post(`/api/crm/leads/${id}/stage`, payload)
    return res.data
  }

  async getLeadActivities(id: number | string, params?: any) {
    const res = await axiosClient.get(`/api/crm/leads/${id}/activities`, { params })
    return res.data
  }

  async addLeadActivity(id: number | string, payload: any) {
    const res = await axiosClient.post(`/api/crm/leads/${id}/activities`, payload)
    return res.data
  }

  async getChatThreads(params?: any) {
    const res = await axiosClient.get('/api/crm/chats/threads', { params })
    return res.data
  }

  async getChatMessages(threadId: number | string, params?: any) {
    const res = await axiosClient.get(`/api/crm/chats/threads/${threadId}/messages`, { params })
    return res.data
  }

  async sendChatMessage(threadId: number | string, payload: any) {
    const res = await axiosClient.post(`/api/crm/chats/threads/${threadId}/messages`, payload)
    return res.data
  }

  async updateChatMessage(threadId: number | string, messageId: number | string, payload: { message: string }) {
    const res = await axiosClient.put(`/api/crm/chats/threads/${threadId}/messages/${messageId}`, payload)
    return res.data
  }

  async unsendChatMessage(threadId: number | string, messageId: number | string) {
    const res = await axiosClient.delete(`/api/crm/chats/threads/${threadId}/messages/${messageId}`)
    return res.data
  }

  async getReviews(params?: any) {
    const res = await axiosClient.get('/api/crm/reviews', { params })
    return res.data
  }

  async getReview(id: number | string) {
    const res = await axiosClient.get(`/api/crm/reviews/${id}`)
    return res.data
  }

  async replyReview(id: number | string, payload: { reply: string }) {
    const res = await axiosClient.put(`/api/crm/reviews/${id}/reply`, payload)
    return res.data
  }

  async getVouchers(params?: any) {
    const res = await axiosClient.get('/api/crm/vouchers', { params })
    return res.data
  }

  async createVoucher(payload: any) {
    const res = await axiosClient.post('/api/crm/vouchers', payload)
    return res.data
  }

  async getVoucher(id: number | string) {
    const res = await axiosClient.get(`/api/crm/vouchers/${id}`)
    return res.data
  }

  async updateVoucher(id: number | string, payload: any) {
    const res = await axiosClient.put(`/api/crm/vouchers/${id}`, payload)
    return res.data
  }

  async getReturns(params?: any) {
    const res = await axiosClient.get('/api/crm/returns', { params })
    return res.data
  }

  async getReturn(id: number | string) {
    const res = await axiosClient.get(`/api/crm/returns/${id}`)
    return res.data
  }

  async getReturnInvestigationAssignees() {
    const res = await axiosClient.get('/api/crm/returns/investigation-assignees')
    return res.data
  }

  async createReturnInvestigationTicket(id: number | string, payload: {
    assigned_employee_ids: number[]
    expected_investigation_date: string
    notes?: string
  }) {
    const res = await axiosClient.post(`/api/crm/returns/${id}/investigation-ticket`, payload)
    return res.data
  }

  async updateReturnStatus(id: number | string, payload: { status: 'approved' | 'rejected' | 'received' | 'refunded'; return_type?: 'refund' | 'replacement'; review_notes?: string }) {
    const res = await axiosClient.put(`/api/crm/returns/${id}/status`, payload)
    return res.data
  }

  async scheduleReturnPickup(id: number | string, payload: { scheduled_at: string; pickup_name?: string; pickup_phone?: string; pickup_address?: string; notes?: string }) {
    const res = await axiosClient.post(`/api/crm/returns/${id}/pickup`, payload)
    return res.data
  }

  async receiveReturn(id: number | string, payload: { received_quantity: number; condition: 'good' | 'bad'; notes?: string }) {
    const res = await axiosClient.post(`/api/crm/returns/${id}/receive`, payload)
    return res.data
  }

  async createReturnRefund(id: number | string, payload: { amount: number; reason?: string; notes?: string; mark_as_approved?: boolean }) {
    const res = await axiosClient.post(`/api/crm/returns/${id}/refund`, payload)
    return res.data
  }
}

export default new CrmService()
