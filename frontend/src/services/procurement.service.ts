import axiosClient from '../axios'

export interface Supplier {
  id?: number
  supplier_name: string
  company_name?: string
  contact_person?: string
  email?: string
  phone: string
  address?: string
  status?: 'active' | 'inactive' | 'blacklisted'
}

export interface PurchaseRequisition {
  id?: number
  pr_number?: string
  branch_id: number
  requisition_type: 'regular' | 'urgent' | 'new_product' | 'seasonal' | 'emergency'
  required_date: string
  reason: string
  status?: 'draft' | 'submitted' | 'approved' | 'rejected' | 'cancelled'
  items?: Array<{
    product_id: number
    variation_id?: number
    quantity_requested: number
    estimated_unit_cost?: number
    specifications?: string
  }>
}

export interface RequestForQuotation {
  id?: number
  rfq_number?: string
  title: string
  description?: string
  issue_date: string
  deadline_date: string
  status?: 'draft' | 'sent' | 'closed' | 'awarded' | 'cancelled'
}

export interface PurchaseOrder {
  id?: number
  po_number?: string
  supplier_id: number
  branch_id: number
  order_date: string
  expected_delivery_date: string
  payment_terms?: string
  notes?: string
  status?: 'draft' | 'pending_approval' | 'approved' | 'rejected' | 'ordered' | 'cancelled'
}

export interface GoodsReceipt {
  id?: number
  grn_number?: string
  purchase_order_id: number
  branch_id: number
  receipt_date: string
  receipt_status?: 'full' | 'partial' | 'damaged' | 'rejected'
}

export interface SupplierPayment {
  id?: number
  payment_number?: string
  purchase_order_id: number
  supplier_id: number
  payment_amount: number
  payment_method: 'cash' | 'check' | 'bank_transfer' | 'credit_card' | 'debit_card' | 'online_payment'
  payment_date: string
  status?: 'pending_approval' | 'approved' | 'processing' | 'completed' | 'failed' | 'cancelled'
}

class ProcurementService {
  private baseUrl = '/api/procurement'

  // ==================== DASHBOARD ====================
  async getDashboardStats(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/suppliers/stats`, { params })
    return response.data
  }

  async getPendingApprovals(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/suppliers/summary-cards`, { params })
    return response.data
  }

  // ==================== SUPPLIERS ====================
  async getSuppliers(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/suppliers`, { params })
    return response.data
  }

  async getSupplier(id: number) {
    const response = await axiosClient.get(`${this.baseUrl}/suppliers/${id}`)
    return response.data
  }

  async createSupplier(data: Supplier) {
    const response = await axiosClient.post(`${this.baseUrl}/suppliers`, data)
    return response.data
  }

  async updateSupplier(id: number, data: Partial<Supplier>) {
    const response = await axiosClient.put(`${this.baseUrl}/suppliers/${id}`, data)
    return response.data
  }

  async deleteSupplier(id: number) {
    const response = await axiosClient.delete(`${this.baseUrl}/suppliers/${id}`)
    return response.data
  }

  // ==================== PURCHASE REQUISITIONS ====================
  async getPurchaseRequisitions(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/requisitions`, { params })
    return response.data
  }

  async getPurchaseRequisition(id: number) {
    const response = await axiosClient.get(`${this.baseUrl}/requisitions/${id}`)
    return response.data
  }

  async createPurchaseRequisition(data: PurchaseRequisition) {
    const response = await axiosClient.post(`${this.baseUrl}/requisitions`, data)
    return response.data
  }

  async updatePurchaseRequisition(id: number, data: Partial<PurchaseRequisition>) {
    const response = await axiosClient.put(`${this.baseUrl}/requisitions/${id}`, data)
    return response.data
  }

  async deletePurchaseRequisition(id: number) {
    const response = await axiosClient.delete(`${this.baseUrl}/requisitions/${id}`)
    return response.data
  }

  async submitPurchaseRequisition(id: number) {
    const response = await axiosClient.post(`${this.baseUrl}/requisitions/${id}/submit`)
    return response.data
  }

  async approvePurchaseRequisition(id: number) {
    const response = await axiosClient.post(`${this.baseUrl}/requisitions/${id}/approve`)
    return response.data
  }

  async rejectPurchaseRequisition(id: number, reason?: string) {
    const response = await axiosClient.post(`${this.baseUrl}/requisitions/${id}/reject`, { reason })
    return response.data
  }

  async convertPurchaseRequisition(id: number) {
    const response = await axiosClient.post(`${this.baseUrl}/requisitions/${id}/convert`)
    return response.data
  }

  // ==================== RFQS ====================
  async getRFQs(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/rfqs`, { params })
    return response.data
  }

  async getRFQ(id: number) {
    const response = await axiosClient.get(`${this.baseUrl}/rfqs/${id}`)
    return response.data
  }

  async createRFQ(data: RequestForQuotation) {
    const response = await axiosClient.post(`${this.baseUrl}/rfqs`, data)
    return response.data
  }

  async updateRFQ(id: number, data: Partial<RequestForQuotation>) {
    const response = await axiosClient.put(`${this.baseUrl}/rfqs/${id}`, data)
    return response.data
  }

  async deleteRFQ(id: number) {
    const response = await axiosClient.delete(`${this.baseUrl}/rfqs/${id}`)
    return response.data
  }

  async sendRFQ(id: number) {
    const response = await axiosClient.post(`${this.baseUrl}/rfqs/${id}/send`)
    return response.data
  }

  async closeRFQ(id: number) {
    const response = await axiosClient.post(`${this.baseUrl}/rfqs/${id}/close`)
    return response.data
  }

  async awardRFQ(id: number) {
    const response = await axiosClient.post(`${this.baseUrl}/rfqs/${id}/award`)
    return response.data
  }

  // ==================== PURCHASE ORDERS ====================
  async getPurchaseOrders(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/purchase-orders`, { params })
    return response.data
  }

  async getPurchaseOrder(id: number) {
    const response = await axiosClient.get(`${this.baseUrl}/purchase-orders/${id}`)
    return response.data
  }

  async createPurchaseOrder(data: PurchaseOrder) {
    const response = await axiosClient.post(`${this.baseUrl}/purchase-orders`, data)
    return response.data
  }

  async updatePurchaseOrder(id: number, data: Partial<PurchaseOrder>) {
    const response = await axiosClient.put(`${this.baseUrl}/purchase-orders/${id}`, data)
    return response.data
  }

  async deletePurchaseOrder(id: number) {
    const response = await axiosClient.delete(`${this.baseUrl}/purchase-orders/${id}`)
    return response.data
  }

  async approvePurchaseOrder(id: number) {
    const response = await axiosClient.post(`${this.baseUrl}/purchase-orders/${id}/approve`)
    return response.data
  }

  async rejectPurchaseOrder(id: number, reason?: string) {
    const response = await axiosClient.post(`${this.baseUrl}/purchase-orders/${id}/reject`, { reason })
    return response.data
  }

  async sendPurchaseOrder(id: number) {
    const response = await axiosClient.post(`${this.baseUrl}/purchase-orders/${id}/send`)
    return response.data
  }

  async cancelPurchaseOrder(id: number, reason?: string) {
    const response = await axiosClient.post(`${this.baseUrl}/purchase-orders/${id}/cancel`, { reason })
    return response.data
  }

  // ==================== GOODS RECEIPTS ====================
  async getGoodsReceipts(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/goods-receipts`, { params })
    return response.data
  }

  async getGoodsReceipt(id: number) {
    const response = await axiosClient.get(`${this.baseUrl}/goods-receipts/${id}`)
    return response.data
  }

  async createGoodsReceipt(data: GoodsReceipt) {
    const response = await axiosClient.post(`${this.baseUrl}/goods-receipts`, data)
    return response.data
  }

  async updateGoodsReceipt(id: number, data: Partial<GoodsReceipt>) {
    const response = await axiosClient.put(`${this.baseUrl}/goods-receipts/${id}`, data)
    return response.data
  }

  async deleteGoodsReceipt(id: number) {
    const response = await axiosClient.delete(`${this.baseUrl}/goods-receipts/${id}`)
    return response.data
  }

  async verifyGoodsReceipt(id: number) {
    const response = await axiosClient.post(`${this.baseUrl}/goods-receipts/${id}/verify`)
    return response.data
  }

  // ==================== PAYMENTS ====================
  async getSupplierPayments(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/payments`, { params })
    return response.data
  }

  async getSupplierPayment(id: number) {
    const response = await axiosClient.get(`${this.baseUrl}/payments/${id}`)
    return response.data
  }

  async createSupplierPayment(data: SupplierPayment) {
    const response = await axiosClient.post(`${this.baseUrl}/payments`, data)
    return response.data
  }

  async updateSupplierPayment(id: number, data: Partial<SupplierPayment>) {
    const response = await axiosClient.put(`${this.baseUrl}/payments/${id}`, data)
    return response.data
  }

  async deleteSupplierPayment(id: number) {
    const response = await axiosClient.delete(`${this.baseUrl}/payments/${id}`)
    return response.data
  }

  async approveSupplierPayment(id: number) {
    const response = await axiosClient.post(`${this.baseUrl}/payments/${id}/approve`)
    return response.data
  }

  async rejectSupplierPayment(id: number, reason?: string) {
    const response = await axiosClient.post(`${this.baseUrl}/payments/${id}/cancel`, { reason })
    return response.data
  }

  async processSupplierPayment(id: number) {
    const response = await axiosClient.post(`${this.baseUrl}/payments/${id}/process`)
    return response.data
  }

  // ==================== REPORTS ====================
  async getSpendAnalysisReport(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/reports/spend-analysis`, { params })
    return response.data
  }

  async getSupplierPerformanceReport(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/reports/supplier-performance`, { params })
    return response.data
  }

  async getProcurementCycleTimeReport(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/reports/cycle-time`, { params })
    return response.data
  }
}

export default new ProcurementService()
