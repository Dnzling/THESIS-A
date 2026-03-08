// frontend/services/inventory.service.ts
import axiosClient from '../axios'

export interface InventoryDashboardStats {
  total_items: number
  low_stock_items: number
  out_of_stock_items: number
  total_inventory_value: number
  pending_adjustments: number
  pending_transfers: number
}

export interface BranchInventoryItem {
  id?: number
  store_id?: number
  branch_id: number
  name?: string
  product_id: number
  variation_id?: number | null
  quantity_on_hand: number
  quantity_reserved: number
  quantity_available: number
  quantity_damaged: number
  quantity_incoming: number
  warehouse_section?: string
  aisle?: string
  rack?: string
  shelf?: string
  bin_code?: string
  reorder_point: number
  reorder_quantity: number
  maximum_stock: number
  safety_stock: number
  stock_status: 'in_stock' | 'low_stock' | 'out_of_stock' | 'overstock'
  unit_cost?: number | null
  average_cost?: number | null
  total_value: string | number
  
  // Relationships
  product?: {
    id: number
    sku: string
    product_name: string
    base_price: string
  }
  variation?: any
  branch?: {
    id: number
    name: string
    branch_code: string
  }
}

export interface StockAdjustment {
  id?: number
  reference_no?: string
  branch_id: number
  adjustment_date: string
  reason: string
  remarks?: string
  status?: 'draft' | 'submitted' | 'approved' | 'rejected'
  items: Array<{
    inventory_item_id: number
    adjustment_type: 'add' | 'deduct'
    quantity: number
    remarks?: string
  }>
}

export interface StockTransfer {
  id?: number
  transfer_no?: string
  from_branch_id: number
  to_branch_id: number
  transfer_date: string
  expected_receive_date?: string
  remarks?: string
  status?: 'draft' | 'submitted' | 'approved' | 'shipped' | 'received' | 'cancelled'
  items: Array<{
    inventory_item_id: number
    quantity: number
    remarks?: string
  }>
}

export interface StockAlert {
  id?: number
  branch_id?: number
  name?: string
  product_id?: number
  product_name?: string
  sku?: string
  current_stock: number
  reorder_point: number
  status: string
  severity: 'low' | 'critical'
  acknowledged_at?: string | null
  resolved_at?: string | null
}

class InventoryService {
  // Use explicit API prefix so requests hit Laravel api routes and CORS paths.
  private baseUrl = '/api/inventory'

  async getDashboardStats(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/dashboard/stats`, { params })
    return response.data
  }

  async getSummaryCards() {
    const response = await axiosClient.get(`${this.baseUrl}/dashboard/summary-cards`)
    return response.data
  }

  // ==================== BRANCH INVENTORY ====================
  // GET /inventory/branches
  async getBranches() {
    const response = await axiosClient.get(`${this.baseUrl}/branches`)
    return response.data
  }

  // GET /inventory/branch/{branchId}
  async getBranchInventory(branchId: number, params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/branch/${branchId}`, { params })
    return response.data
  }

  // GET /inventory/branch/{branchId}/summary
  async getBranchSummary(branchId: number) {
    const response = await axiosClient.get(`${this.baseUrl}/branch/${branchId}/summary`)
    return response.data
  }

  // GET /inventory/branch/{branchId}/low-stock
  async getLowStockItems(branchId: number) {
    const response = await axiosClient.get(`${this.baseUrl}/branch/${branchId}/low-stock`)
    return response.data
  }

  // GET /inventory/items/{id}
  async getInventoryItem(id: number) {
    const response = await axiosClient.get(`${this.baseUrl}/items/${id}`)
    return response.data
  }

  // POST /inventory/items
  async createInventoryItem(data: Partial<BranchInventoryItem>) {
    const response = await axiosClient.post(`${this.baseUrl}/items`, data)
    return response.data
  }

  // PUT /inventory/items/{id}
  async updateInventoryItem(id: number, data: Partial<BranchInventoryItem>) {
    const response = await axiosClient.put(`${this.baseUrl}/items/${id}`, data)
    return response.data
  }

  // DELETE /inventory/items/{id}
  async deleteInventoryItem(id: number) {
    const response = await axiosClient.delete(`${this.baseUrl}/items/${id}`)
    return response.data
  }

  // POST /inventory/items/{id}/update-status
  async updateItemStatus(id: number, status: string) {
    const response = await axiosClient.post(`${this.baseUrl}/items/${id}/update-status`, { status })
    return response.data
  }

  // ==================== STOCK ADJUSTMENTS ====================
  async getAdjustments(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/adjustments`, { params })
    return response.data
  }

  async getAdjustment(id: number) {
    const response = await axiosClient.get(`${this.baseUrl}/adjustments/${id}`)
    return response.data
  }

  async createAdjustment(data: StockAdjustment) {
    const response = await axiosClient.post(`${this.baseUrl}/adjustments`, data)
    return response.data
  }

  async submitAdjustment(id: number) {
    const response = await axiosClient.post(`${this.baseUrl}/adjustments/${id}/submit`)
    return response.data
  }

  async approveAdjustment(id: number) {
    const response = await axiosClient.post(`${this.baseUrl}/adjustments/${id}/approve`)
    return response.data
  }

  async rejectAdjustment(id: number, remarks?: string) {
    const response = await axiosClient.post(`${this.baseUrl}/adjustments/${id}/reject`, { remarks })
    return response.data
  }

  // ==================== STOCK TRANSFERS ====================
  async getTransfers(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/transfers`, { params })
    return response.data
  }

  async getTransfer(id: number) {
    const response = await axiosClient.get(`${this.baseUrl}/transfers/${id}`)
    return response.data
  }

  async createTransfer(data: StockTransfer) {
    const response = await axiosClient.post(`${this.baseUrl}/transfers`, data)
    return response.data
  }

  async approveTransfer(id: number) {
    const response = await axiosClient.post(`${this.baseUrl}/transfers/${id}/approve`)
    return response.data
  }

  async shipTransfer(id: number) {
    const response = await axiosClient.post(`${this.baseUrl}/transfers/${id}/ship`)
    return response.data
  }

  async receiveTransfer(id: number) {
    const response = await axiosClient.post(`${this.baseUrl}/transfers/${id}/receive`)
    return response.data
  }

  async cancelTransfer(id: number, remarks?: string) {
    const response = await axiosClient.post(`${this.baseUrl}/transfers/${id}/cancel`, { remarks })
    return response.data
  }

  // ==================== STOCK ALERTS ====================
  async getAlerts(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/alerts`, { params })
    return response.data
  }

  async getAlertSummary() {
    const response = await axiosClient.get(`${this.baseUrl}/alerts/summary`)
    return response.data
  }

  async acknowledgeAlert(id: number) {
    const response = await axiosClient.post(`${this.baseUrl}/alerts/${id}/acknowledge`)
    return response.data
  }

  async resolveAlert(id: number) {
    const response = await axiosClient.post(`${this.baseUrl}/alerts/${id}/resolve`)
    return response.data
  }

  async bulkAcknowledgeAlerts(ids: number[]) {
    const response = await axiosClient.post(`${this.baseUrl}/alerts/bulk-acknowledge`, { ids })
    return response.data
  }

  async generateAlerts() {
    const response = await axiosClient.post(`${this.baseUrl}/alerts/generate`)
    return response.data
  }

  // ==================== INVENTORY TRANSACTIONS ====================
  async getTransactions(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/transactions`, { params })
    return response.data
  }

  async getTransactionSummary(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/transactions/summary`, { params })
    return response.data
  }

  async getProductHistory(productId: number, params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/transactions/product/${productId}`, { params })
    return response.data
  }

  async getTransactionChartData(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/transactions/chart`, { params })
    return response.data
  }

  async getRecentTransactions(limit: number = 10) {
    const response = await axiosClient.get(`${this.baseUrl}/transactions/recent`, { params: { limit } })
    return response.data
  }

  async exportTransactions(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/transactions/export`, { 
      params,
      responseType: 'blob' 
    })
    return response.data
  }
}

export default new InventoryService()