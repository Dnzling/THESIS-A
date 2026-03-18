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
  rating?: number
  supplier_type?: 'manufacturer' | 'wholesaler' | 'distributor' | 'importer' | 'local_artisan'
  payment_terms?: string
  credit_limit?: number
}

export interface SupplierContact {
  id?: number
  supplier_id: number
  contact_name: string
  contact_title?: string
  email?: string
  phone?: string
  mobile?: string
  contact_type: 'Sales' | 'Technical' | 'Support' | 'Billing' | 'Logistics'
  preferred_contact_method?: string
  is_primary?: boolean
  is_emergency_contact?: boolean
}

export interface SupplierPrice {
  id?: number
  supplier_id: number
  product_id: number
  unit_price: number
  currency: string
  minimum_order_quantity?: number
  lead_time_days?: number
  pack_size?: number
  effective_date?: string
  expiry_date?: string
  is_active?: boolean
}

export interface Invoice {
  id?: number
  invoice_number: string
  supplier_id: number
  purchase_order_id: number
  goods_receipt_id?: number
  invoice_date: string
  due_date: string
  invoice_amount: number
  tax_amount?: number
  shipping_cost?: number
  discount_amount?: number
  net_amount?: number
  currency?: string
  status?: 'draft' | 'pending_approval' | 'approved' | 'paid'
  match_status?: 'pending' | 'matched' | 'exception'
  payment_status?: 'pending' | 'paid'
}

export interface PurchaseRequisition {
  id?: number
  pr_number?: string
  branch_id: number
  requisition_type: 'regular' | 'urgent' | 'new_product' | 'seasonal' | 'emergency'
  required_date: string
  reason: string
  status?: 'draft'
    | 'pending'
    | 'warehouse_approved'
    | 'branch_manager_approved'
    | 'pending_central_review'
    | 'procurement_processing'
    | 'rfq_sent'
    | 'quotes_received'
    | 'supplier_selected'
    | 'po_created'
    | 'rejected'
    | 'cancelled'
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
  status?: 'draft'
    | 'pending_finance_approval'
    | 'approved'
    | 'sent_to_supplier'
    | 'supplier_accepted'
    | 'in_transit'
    | 'delivered'
    | 'rejected_finance'
    | 'declined_supplier'
    | 'cancelled'
    | 'revision_requested'
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

  // ==================== DASHBOARD & ANALYTICS ====================
  async getDashboardStats(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/suppliers/stats`, { params })
    return response.data
  }

  async getPendingApprovals(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/suppliers/summary-cards`, { params })
    return response.data
  }

  async getReorderSuggestions(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/analytics/reorder-suggestions`, { params })
    return response.data
  }

  async getSpendAnalytics(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/analytics/spend`, { params })
    return response.data
  }

  async getSupplierPerformanceAnalytics(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/analytics/supplier-performance`, { params })
    return response.data
  }

  async getReceivingAccuracy(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/analytics/receiving-accuracy`, { params })
    return response.data
  }

  async getBudgetTracking(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/analytics/budget`, { params })
    return response.data
  }

  async getLeadTimeAnalysis(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/analytics/lead-time`, { params })
    return response.data
  }

  async getLeadTimeMonitoring(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/analytics/lead-time`, { params })
    return response.data
  }

  // ==================== BRANCHES ====================
  async getBranches(params?: any) {
    const response = await axiosClient.get('/api/branches', { params })
    return response.data
  }

  // ==================== PRODUCTS (Procurement View) ====================
  async getBranchInventory(branchId: number, params?: any) {
    const response = await axiosClient.get(`/api/inventory/branch/${branchId}`, { params })
    return response.data
  }

  async getProcurementProducts(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/products`, { params })
    return response.data
  }

  async getProductSuppliers(id: number) {
    const response = await axiosClient.get(`${this.baseUrl}/products/${id}/suppliers`)
    return response.data
  }

  async getProductHistory(idOrParams: number | Record<string, any>, params?: any) {
    if (typeof idOrParams === 'number') {
      const response = await axiosClient.get(`${this.baseUrl}/products/${idOrParams}/history`, { params })
      return response.data
    }
    const response = await axiosClient.get(`${this.baseUrl}/product-inventory/history`, { params: idOrParams })
    return response.data
  }

  // ==================== PROCUREMENT INVENTORY ====================
  async getProcurementInventory(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/inventory`, { params })
    return response.data
  }

  async getProcurementInventoryItem(id: number) {
    const response = await axiosClient.get(`${this.baseUrl}/inventory/${id}`)
    return response.data
  }

  async getProcurementInventorySummary() {
    const response = await axiosClient.get(`${this.baseUrl}/inventory/summary`)
    return response.data
  }

  async getProcurementLowStockItems(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/inventory/low-stock`, { params })
    return response.data
  }

  async initializeProcurementInventory(productIds: number[], initialQty?: number) {
    const response = await axiosClient.post(`${this.baseUrl}/inventory/init`, {
      product_ids: productIds,
      initial_qty: initialQty
    })
    return response.data
  }

  async updateProcurementInventory(id: number, data: any) {
    const response = await axiosClient.put(`${this.baseUrl}/inventory/${id}`, data)
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

  async getSupplierPerformance(id: number) {
    const response = await axiosClient.get(`${this.baseUrl}/suppliers/${id}/performance`)
    return response.data
  }

  async updateSupplierRating(id: number) {
    const response = await axiosClient.post(`${this.baseUrl}/suppliers/${id}/update-rating`)
    return response.data
  }

  async blacklistSupplier(id: number, data: { reason: string; notes?: string }) {
    const response = await axiosClient.post(`${this.baseUrl}/suppliers/${id}/blacklist`, data)
    return response.data
  }

  async activateSupplier(id: number) {
    const response = await axiosClient.post(`${this.baseUrl}/suppliers/${id}/activate`)
    return response.data
  }

  async attachSupplierProducts(id: number, data: any) {
    const response = await axiosClient.post(`${this.baseUrl}/suppliers/${id}/products`, data)
    return response.data
  }

  // ==================== SUPPLIER CONTACTS ====================
  async getSupplierContacts(id: number) {
    const response = await axiosClient.get(`${this.baseUrl}/suppliers/${id}/contacts`)
    return response.data
  }

  async addSupplierContact(id: number, data: SupplierContact) {
    const response = await axiosClient.post(`${this.baseUrl}/suppliers/${id}/contacts`, data)
    return response.data
  }

  async updateSupplierContact(supplierId: number, contactId: number, data: Partial<SupplierContact>) {
    const response = await axiosClient.put(`${this.baseUrl}/suppliers/${supplierId}/contacts/${contactId}`, data)
    return response.data
  }

  async deleteSupplierContact(supplierId: number, contactId: number) {
    const response = await axiosClient.delete(`${this.baseUrl}/suppliers/${supplierId}/contacts/${contactId}`)
    return response.data
  }

  // ==================== SUPPLIER PRICING ====================
  async getSupplierPricingHistory(id: number, params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/suppliers/${id}/pricing-history`, { params })
    return response.data
  }

  async updateSupplierPrice(id: number, data: SupplierPrice) {
    const response = await axiosClient.post(`${this.baseUrl}/suppliers/${id}/update-price`, data)
    return response.data
  }

  // ==================== INVOICES & 3-WAY MATCHING ====================
  async getInvoices(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/invoices`, { params })
    return response.data
  }

  async getInvoice(id: number) {
    const response = await axiosClient.get(`${this.baseUrl}/invoices/${id}`)
    return response.data
  }

  async createInvoice(data: Invoice) {
    const response = await axiosClient.post(`${this.baseUrl}/invoices`, data)
    return response.data
  }

  async updateInvoice(id: number, data: Partial<Invoice>) {
    const response = await axiosClient.put(`${this.baseUrl}/invoices/${id}`, data)
    return response.data
  }

  async performInvoiceMatch(id: number) {
    const response = await axiosClient.post(`${this.baseUrl}/invoices/${id}/match`)
    return response.data
  }

  async approveInvoice(id: number) {
    const response = await axiosClient.post(`${this.baseUrl}/invoices/${id}/approve`)
    return response.data
  }

  async scheduleInvoicePayment(id: number, data: { payment_date: string }) {
    const response = await axiosClient.post(`${this.baseUrl}/invoices/${id}/schedule-payment`, data)
    return response.data
  }

  async markInvoiceAsPaid(id: number, data: { payment_method: string; payment_amount: number }) {
    const response = await axiosClient.post(`${this.baseUrl}/invoices/${id}/mark-paid`, data)
    return response.data
  }

  async getPendingInvoiceMatch(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/invoices/pending/match`, { params })
    return response.data
  }

  async getInvoiceExceptions(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/invoices/exceptions`, { params })
    return response.data
  }

  // ==================== BARCODE SCANNING ====================
  async lookupBarcode(code: string) {
    const response = await axiosClient.get(`${this.baseUrl}/barcode/lookup`, { params: { code } })
    return response.data
  }

  async validateBarcode(code: string) {
    const response = await axiosClient.post(`${this.baseUrl}/barcode/validate`, { code })
    return response.data
  }

  async getBarcodeSuggestions(q: string) {
    const response = await axiosClient.get(`${this.baseUrl}/barcode/suggestions`, { params: { q } })
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

  async approvePurchaseRequisition(id: number, data: { role: string; notes?: string }) {
    const response = await axiosClient.post(`${this.baseUrl}/requisitions/${id}/approve`, data)
    return response.data
  }

  async rejectPurchaseRequisition(id: number, data?: { role: string; reason?: string }) {
    const response = await axiosClient.post(`${this.baseUrl}/requisitions/${id}/reject`, data || {})
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

  async sendRFQ(id: number, data?: { supplier_ids: number[], invitation_method?: string }) {
    const response = await axiosClient.post(`${this.baseUrl}/rfqs/${id}/send`, data || {})
    return response.data
  }

  async sendRfq(id: number, data?: { supplier_ids: number[], invitation_method?: string }) {
    return this.sendRFQ(id, data)
  }

  async closeRFQ(id: number) {
    const response = await axiosClient.post(`${this.baseUrl}/rfqs/${id}/close`)
    return response.data
  }

  async compareQuotations(rfqId: number, params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/rfqs/${rfqId}/quotations/compare`, { params })
    return response.data
  }

  async awardQuotation(rfqId: number, data: { winner_quotation_id: number, rejected_quotation_ids?: number[] }) {
    const response = await axiosClient.post(`${this.baseUrl}/rfqs/${rfqId}/award`, data)
    return response.data
  }

  async awardRFQ(id: number, data?: { supplier_id: number, evaluation_notes?: string }) {
    const response = await axiosClient.post(`${this.baseUrl}/rfqs/${id}/award`, data || {})
    return response.data
  }

  async cancelRFQ(id: number, data?: { reason?: string }) {
    const response = await axiosClient.post(`${this.baseUrl}/rfqs/${id}/cancel`, data || {})
    return response.data
  }

  async reviewPortalFeedback(rfqId: number, feedbackId: number, data: { status: 'approved' | 'rejected'; rejection_reason?: string }) {
    const response = await axiosClient.post(`${this.baseUrl}/rfqs/${rfqId}/portal-feedbacks/${feedbackId}/review`, data)
    return response.data
  }

  async negotiatePortalFeedback(rfqId: number, feedbackId: number, data: { counter_price: number; notes?: string }) {
    const response = await axiosClient.post(`${this.baseUrl}/rfqs/${rfqId}/portal-feedbacks/${feedbackId}/negotiate`, data)
    return response.data
  }

  async bulkApprovePortalFeedbacks(rfqId: number, data: { feedback_ids: number[] }) {
    const response = await axiosClient.post(`${this.baseUrl}/rfqs/${rfqId}/portal-feedbacks/bulk-approve`, data)
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

  async approvePurchaseOrder(id: number, data?: { notes?: string }) {
    const response = await axiosClient.post(`${this.baseUrl}/purchase-orders/${id}/approve`, data || {})
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

  async requestRevision(id: number, data: { comments: string }) {
    const response = await axiosClient.post(`${this.baseUrl}/purchase-orders/${id}/request-revision`, data)
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

  // ==================== SUPPLIER CONTRACTS ====================
  async getSupplierContracts(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/supplier-contracts`, { params })
    return response.data
  }

  async getSupplierContract(id: string | number) {
    const response = await axiosClient.get(`${this.baseUrl}/supplier-contracts/${id}`)
    return response.data
  }

  async createSupplierContract(data: FormData | any) {
    const response = await axiosClient.post(`${this.baseUrl}/supplier-contracts`, data, {
      headers: data instanceof FormData ? { 'Content-Type': 'multipart/form-data' } : {}
    })
    return response.data
  }

  async updateSupplierContract(id: string | number, data: FormData | any) {
    const response = await axiosClient.put(`${this.baseUrl}/supplier-contracts/${id}`, data, {
      headers: data instanceof FormData ? { 'Content-Type': 'multipart/form-data' } : {}
    })
    return response.data
  }

  async deleteSupplierContract(id: string | number) {
    const response = await axiosClient.delete(`${this.baseUrl}/supplier-contracts/${id}`)
    return response.data
  }

  async activateSupplierContract(id: string | number) {
    const response = await axiosClient.post(`${this.baseUrl}/supplier-contracts/${id}/activate`)
    return response.data
  }

  async terminateSupplierContract(id: string | number) {
    const response = await axiosClient.post(`${this.baseUrl}/supplier-contracts/${id}/terminate`)
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

  // ==================== PRINT & EMAIL ====================
  async generatePOPdf(id: number) {
    const response = await axiosClient.get(`${this.baseUrl}/purchase-orders/${id}/print`, {
      responseType: 'blob'
    })
    return response
  }

  async emailPurchaseOrder(id: number, data: { recipient_email?: string; subject?: string; message?: string }) {
    const response = await axiosClient.post(`${this.baseUrl}/purchase-orders/${id}/email`, data)
    return response.data
  }

  async generateGRPdf(id: number) {
    const response = await axiosClient.get(`${this.baseUrl}/goods-receipts/${id}/print`, {
      responseType: 'blob'
    })
    return response
  }

  async generatePOLabel(id: number) {
    const response = await axiosClient.get(`${this.baseUrl}/purchase-orders/${id}/label`, {
      responseType: 'blob'
    })
    return response
  }

  // ==================== STOCK ORDER REQUESTS ====================
  async getStockOrderRequests(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/stock-order-requests`, { params })
    return response.data
  }

  async getStockOrderRequest(id: number) {
    const response = await axiosClient.get(`${this.baseUrl}/stock-order-requests/${id}`)
    return response.data
  }

  async createStockOrderRequest(data: {
    branch_inventory_id: number
    requested_quantity: number
  }) {
    const response = await axiosClient.post(`${this.baseUrl}/stock-order-requests`, data)
    return response.data
  }

  async updateStockOrderRequest(id: number, data: {
    branch_inventory_id?: number
    requested_quantity?: number
    notes?: string
  }) {
    const response = await axiosClient.put(`${this.baseUrl}/stock-order-requests/${id}`, data)
    return response.data
  }

  async bulkCreateStockOrderRequestsFromLowStock(params?: {
    store_id?: number
    branch_id?: number
  }) {
    const response = await axiosClient.post(`${this.baseUrl}/stock-order-requests/bulk/create-from-low-stock`, {}, { params })
    return response.data
  }

  async approveStockOrderRequest(id: number) {
    const response = await axiosClient.post(`${this.baseUrl}/stock-order-requests/${id}/approve`)
    return response.data
  }

  async rejectStockOrderRequest(id: number, reason?: string) {
    const response = await axiosClient.post(`${this.baseUrl}/stock-order-requests/${id}/reject`, { reason })
    return response.data
  }

  async getPendingStockOrderRequestsForConversion(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/stock-order-requests/pending/for-conversion`, { params })
    return response.data
  }

  async getStockOrderRequestSummary(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/stock-order-requests/summary`, { params })
    return response.data
  }

  // ==================== AUTOMATION & SUGGESTIONS ====================
  async getSupplierDeliveryHistory(supplierId: number) {
    const response = await axiosClient.get(`${this.baseUrl}/suppliers/${supplierId}/delivery-history`)
    return response.data
  }

  async getBranchBudget(branchId: number) {
    const response = await axiosClient.get(`${this.baseUrl}/branches/${branchId}/budget`)
    return response.data
  }

  async getAlternativeSuppliers(productId: number, params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/products/${productId}/alternative-suppliers`, { params })
    return response.data
  }

  async getApprovedPurchaseOrders(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/purchase-orders/approved`, { params })
    return response.data
  }

  // ==================== COMMON API CALLS ====================
  async getEmployees(params?: any) {
    const response = await axiosClient.get('/api/employees', { params })
    return response.data
  }
}

export default new ProcurementService()
