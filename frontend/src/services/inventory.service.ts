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

  // GET /inventory/{id}
  async getInventoryItems(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/items`, { params })
    return response.data
  }

  async getInventoryItem(id: number) {
    const response = await axiosClient.get(`${this.baseUrl}/items/${id}`)
    return response.data
  }

  // POST /inventory
  async createInventoryItem(data: Partial<BranchInventoryItem>) {
    const response = await axiosClient.post(`${this.baseUrl}`, data)
    return response.data
  }

  // PUT /inventory/{id}
  async updateInventoryItem(id: number, data: Partial<BranchInventoryItem>) {
    const response = await axiosClient.put(`${this.baseUrl}/${id}`, data)
    return response.data
  }

  // DELETE /inventory/{id}
  async deleteInventoryItem(id: number) {
    const response = await axiosClient.delete(`${this.baseUrl}/${id}`)
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

  async approveAdjustment(id: number, notes?: string) {
    const response = await axiosClient.post(`${this.baseUrl}/adjustments/${id}/approve`, { notes })
    return response.data
  }

  async rejectAdjustment(id: number, notes?: string) {
    const response = await axiosClient.post(`${this.baseUrl}/adjustments/${id}/reject`, { notes })
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

  async approveTransfer(id: number, notes?: string) {
    const response = await axiosClient.post(`${this.baseUrl}/transfers/${id}/approve`, { notes })
    return response.data
  }

  async shipTransfer(id: number, notes?: string) {
    const response = await axiosClient.post(`${this.baseUrl}/transfers/${id}/ship`, { notes })
    return response.data
  }

  async receiveTransfer(id: number, notes?: string) {
    const response = await axiosClient.post(`${this.baseUrl}/transfers/${id}/receive`, { notes })
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

  // ==================== PRODUCTS ====================
  async getProducts(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/products`, { params })
    return response.data
  }

  async getProduct(id: number) {
    const response = await axiosClient.get(`${this.baseUrl}/products/${id}`)
    return response.data
  }

  async createProduct(data: any) {
    const response = await axiosClient.post(`${this.baseUrl}/products`, data)
    return response.data
  }

  async updateProduct(id: number, data: any) {
    const response = await axiosClient.put(`${this.baseUrl}/products/${id}`, data)
    return response.data
  }

  async deleteProduct(id: number) {
    const response = await axiosClient.delete(`${this.baseUrl}/products/${id}`)
    return response.data
  }

  async getProductTypes() {
    const response = await axiosClient.get(`${this.baseUrl}/products/types`)
    return response.data
  }

  async getProductStats() {
    const response = await axiosClient.get(`${this.baseUrl}/products/stats/overview`)
    return response.data
  }

  // ==================== CATEGORIES ====================
  async getCategories(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/categories`, { params })
    return response.data
  }

  async getCategory(id: number) {
    const response = await axiosClient.get(`${this.baseUrl}/categories/${id}`)
    return response.data
  }

  async createCategory(data: any) {
    const response = await axiosClient.post(`${this.baseUrl}/categories`, data)
    return response.data
  }

  async updateCategory(id: number, data: any) {
    const response = await axiosClient.put(`${this.baseUrl}/categories/${id}`, data)
    return response.data
  }

  async deleteCategory(id: number) {
    const response = await axiosClient.delete(`${this.baseUrl}/categories/${id}`)
    return response.data
  }

  async getCategoryTree() {
    const response = await axiosClient.get(`${this.baseUrl}/categories/tree`)
    return response.data
  }

  async getCategoryStats() {
    const response = await axiosClient.get(`${this.baseUrl}/categories/stats/overview`)
    return response.data
  }

  // ==================== UNITS ====================
  async getUnits(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/units`, { params })
    return response.data
  }

  async getUnit(id: number) {
    const response = await axiosClient.get(`${this.baseUrl}/units/${id}`)
    return response.data
  }

  async createUnit(data: any) {
    const response = await axiosClient.post(`${this.baseUrl}/units`, data)
    return response.data
  }

  async updateUnit(id: number, data: any) {
    const response = await axiosClient.put(`${this.baseUrl}/units/${id}`, data)
    return response.data
  }

  async deleteUnit(id: number) {
    const response = await axiosClient.delete(`${this.baseUrl}/units/${id}`)
    return response.data
  }

  async getUnitTypes() {
    const response = await axiosClient.get(`${this.baseUrl}/units/types`)
    return response.data
  }

  // ==================== STOCK ISSUES ====================
  async getStockIssues(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/issues`, { params })
    return response.data
  }

  async getStockIssue(id: number) {
    const response = await axiosClient.get(`${this.baseUrl}/issues/${id}`)
    return response.data
  }

  async createStockIssue(data: any) {
    const response = await axiosClient.post(`${this.baseUrl}/issues`, data)
    return response.data
  }

  async updateStockIssue(id: number, data: any) {
    const response = await axiosClient.put(`${this.baseUrl}/issues/${id}`, data)
    return response.data
  }

  async deleteStockIssue(id: number) {
    const response = await axiosClient.delete(`${this.baseUrl}/issues/${id}`)
    return response.data
  }

  async approveStockIssue(id: number, notes?: string) {
    const response = await axiosClient.post(`${this.baseUrl}/issues/${id}/approve`, { notes })
    return response.data
  }

  async rejectStockIssue(id: number, notes?: string) {
    const response = await axiosClient.post(`${this.baseUrl}/issues/${id}/reject`, { notes })
    return response.data
  }

  async getIssueReasons() {
    const response = await axiosClient.get(`${this.baseUrl}/issues/reasons`)
    return response.data
  }

  async getStockIssueStats() {
    const response = await axiosClient.get(`${this.baseUrl}/issues/stats/overview`)
    return response.data
  }

  // ==================== STOCK RETURNS ====================
  async getStockReturns(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/returns`, { params })
    return response.data
  }

  async getStockReturn(id: number) {
    const response = await axiosClient.get(`${this.baseUrl}/returns/${id}`)
    return response.data
  }

  async createStockReturn(data: any) {
    const response = await axiosClient.post(`${this.baseUrl}/returns`, data)
    return response.data
  }

  async updateStockReturn(id: number, data: any) {
    const response = await axiosClient.put(`${this.baseUrl}/returns/${id}`, data)
    return response.data
  }

  async deleteStockReturn(id: number) {
    const response = await axiosClient.delete(`${this.baseUrl}/returns/${id}`)
    return response.data
  }

  async approveStockReturn(id: number, notes?: string) {
    const response = await axiosClient.post(`${this.baseUrl}/returns/${id}/approve`, { notes })
    return response.data
  }

  async rejectStockReturn(id: number, notes?: string) {
    const response = await axiosClient.post(`${this.baseUrl}/returns/${id}/reject`, { notes })
    return response.data
  }

  async receiveStockReturn(id: number, notes?: string) {
    const response = await axiosClient.post(`${this.baseUrl}/returns/${id}/receive`, { notes })
    return response.data
  }

  async getReturnReasons() {
    const response = await axiosClient.get(`${this.baseUrl}/returns/reasons`)
    return response.data
  }

  async getReturnTypes() {
    const response = await axiosClient.get(`${this.baseUrl}/returns/types`)
    return response.data
  }

  // ==================== STOCK COUNTS ====================
  async getStockCounts(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/counts`, { params })
    return response.data
  }

  async getStockCount(id: number) {
    const response = await axiosClient.get(`${this.baseUrl}/counts/${id}`)
    return response.data
  }

  async createStockCount(data: any) {
    const response = await axiosClient.post(`${this.baseUrl}/counts`, data)
    return response.data
  }

  async updateStockCount(id: number, data: any) {
    const response = await axiosClient.put(`${this.baseUrl}/counts/${id}`, data)
    return response.data
  }

  async deleteStockCount(id: number) {
    const response = await axiosClient.delete(`${this.baseUrl}/counts/${id}`)
    return response.data
  }

  async startStockCount(id: number) {
    const response = await axiosClient.post(`${this.baseUrl}/counts/${id}/start`)
    return response.data
  }

  async updateStockCounts(id: number, data: any) {
    const response = await axiosClient.post(`${this.baseUrl}/counts/${id}/update-counts`, data)
    return response.data
  }

  async approveStockCount(id: number, notes?: string) {
    const response = await axiosClient.post(`${this.baseUrl}/counts/${id}/approve`, { notes })
    return response.data
  }

  async rejectStockCount(id: number, notes?: string) {
    const response = await axiosClient.post(`${this.baseUrl}/counts/${id}/reject`, { notes })
    return response.data
  }

  async getStockCountSheets(id: number) {
    const response = await axiosClient.get(`${this.baseUrl}/counts/${id}/sheets`)
    return response.data
  }

  async getCountTypes() {
    const response = await axiosClient.get(`${this.baseUrl}/counts/types`)
    return response.data
  }

  async getCountStatuses() {
    const response = await axiosClient.get(`${this.baseUrl}/counts/statuses`)
    return response.data
  }

  async getStockCountSuggestions(params: any) {
    const response = await axiosClient.get(`${this.baseUrl}/counts/suggestions`, { params })
    return response.data
  }

  async autoScheduleStockCounts(data: any) {
    const response = await axiosClient.post(`${this.baseUrl}/counts/auto-schedule`, data)
    return response.data
  }

  async getStockCountStats() {
    const response = await axiosClient.get(`${this.baseUrl}/counts/stats/overview`)
    return response.data
  }

  // ==================== WAREHOUSES ====================
  async getWarehouses(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/warehouses`, { params })
    return response.data
  }

  async getWarehouse(id: number) {
    const response = await axiosClient.get(`${this.baseUrl}/warehouses/${id}`)
    return response.data
  }

  async createWarehouse(data: any) {
    const response = await axiosClient.post(`${this.baseUrl}/warehouses`, data)
    return response.data
  }

  async updateWarehouse(id: number, data: any) {
    const response = await axiosClient.put(`${this.baseUrl}/warehouses/${id}`, data)
    return response.data
  }

  async deleteWarehouse(id: number) {
    const response = await axiosClient.delete(`${this.baseUrl}/warehouses/${id}`)
    return response.data
  }

  async getWarehouseTypes() {
    const response = await axiosClient.get(`${this.baseUrl}/warehouses/types`)
    return response.data
  }

  async getWarehouseStats() {
    const response = await axiosClient.get(`${this.baseUrl}/warehouses/stats`)
    return response.data
  }

  async getWarehouseCapacityUtilization(warehouseId?: number) {
    const params = warehouseId ? { warehouse_id: warehouseId } : {}
    const response = await axiosClient.get(`${this.baseUrl}/warehouses/capacity-utilization`, { params })
    return response.data
  }

  // ==================== LOCATIONS ====================
  async getLocations(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/locations`, { params })
    return response.data
  }

  async getLocation(id: number) {
    const response = await axiosClient.get(`${this.baseUrl}/locations/${id}`)
    return response.data
  }

  async createLocation(data: any) {
    const response = await axiosClient.post(`${this.baseUrl}/locations`, data)
    return response.data
  }

  async updateLocation(id: number, data: any) {
    const response = await axiosClient.put(`${this.baseUrl}/locations/${id}`, data)
    return response.data
  }

  async deleteLocation(id: number) {
    const response = await axiosClient.delete(`${this.baseUrl}/locations/${id}`)
    return response.data
  }

  async getLocationTypes() {
    const response = await axiosClient.get(`${this.baseUrl}/locations/types`)
    return response.data
  }

  async getAvailableLocations(warehouseId: number) {
    const response = await axiosClient.get(`${this.baseUrl}/locations/available`, { params: { warehouse_id: warehouseId } })
    return response.data
  }

  async updateLocationStock(id: number, data: any) {
    const response = await axiosClient.post(`${this.baseUrl}/locations/${id}/update-stock`, data)
    return response.data
  }

  async getLocationsNeedingCheck() {
    const response = await axiosClient.get(`${this.baseUrl}/locations/needing-check`)
    return response.data
  }

  // ==================== REORDER RULES ====================
  async getReorderRules(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/reorder-rules`, { params })
    return response.data
  }

  async getReorderRule(id: number) {
    const response = await axiosClient.get(`${this.baseUrl}/reorder-rules/${id}`)
    return response.data
  }

  async createReorderRule(data: any) {
    const response = await axiosClient.post(`${this.baseUrl}/reorder-rules`, data)
    return response.data
  }

  async updateReorderRule(id: number, data: any) {
    const response = await axiosClient.put(`${this.baseUrl}/reorder-rules/${id}`, data)
    return response.data
  }

  async deleteReorderRule(id: number) {
    const response = await axiosClient.delete(`${this.baseUrl}/reorder-rules/${id}`)
    return response.data
  }

  async getReorderRuleTypes() {
    const response = await axiosClient.get(`${this.baseUrl}/reorder-rules/rule-types`)
    return response.data
  }

  async getReorderTriggerTypes() {
    const response = await axiosClient.get(`${this.baseUrl}/reorder-rules/trigger-types`)
    return response.data
  }

  async getReorderPriorities() {
    const response = await axiosClient.get(`${this.baseUrl}/reorder-rules/priorities`)
    return response.data
  }

  async checkReorderStatus(productId: number) {
    const response = await axiosClient.post(`${this.baseUrl}/reorder-rules/check-status`, { product_id: productId })
    return response.data
  }

  async generateReorderSuggestions() {
    const response = await axiosClient.post(`${this.baseUrl}/reorder-rules/generate-suggestions`)
    return response.data
  }

  async getRulesNeedingReview() {
    const response = await axiosClient.get(`${this.baseUrl}/reorder-rules/needing-review`)
    return response.data
  }

  async bulkUpdateRulePriority(data: any) {
    const response = await axiosClient.post(`${this.baseUrl}/reorder-rules/bulk-update-priority`, data)
    return response.data
  }

  // ==================== REORDER SUGGESTIONS ====================
  async getReorderSuggestions(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/reorder-suggestions`, { params })
    return response.data
  }

  async getReorderSuggestion(id: number) {
    const response = await axiosClient.get(`${this.baseUrl}/reorder-suggestions/${id}`)
    return response.data
  }

  async createReorderSuggestion(data: any) {
    const response = await axiosClient.post(`${this.baseUrl}/reorder-suggestions`, data)
    return response.data
  }

  async updateReorderSuggestion(id: number, data: any) {
    const response = await axiosClient.put(`${this.baseUrl}/reorder-suggestions/${id}`, data)
    return response.data
  }

  async deleteReorderSuggestion(id: number) {
    const response = await axiosClient.delete(`${this.baseUrl}/reorder-suggestions/${id}`)
    return response.data
  }

  async approveReorderSuggestion(id: number, notes?: string) {
    const response = await axiosClient.post(`${this.baseUrl}/reorder-suggestions/${id}/approve`, { notes })
    return response.data
  }

  async rejectReorderSuggestion(id: number, notes?: string) {
    const response = await axiosClient.post(`${this.baseUrl}/reorder-suggestions/${id}/reject`, { notes })
    return response.data
  }

  async implementReorderSuggestion(id: number, notes?: string) {
    const response = await axiosClient.post(`${this.baseUrl}/reorder-suggestions/${id}/implement`, { notes })
    return response.data
  }

  async cancelReorderSuggestion(id: number, notes?: string) {
    const response = await axiosClient.post(`${this.baseUrl}/reorder-suggestions/${id}/cancel`, { notes })
    return response.data
  }

  async generateSuggestions() {
    const response = await axiosClient.post(`${this.baseUrl}/reorder-suggestions/generate`)
    return response.data
  }

  async bulkApproveReorderSuggestions(ids: number[], notes?: string) {
    const response = await axiosClient.post(`${this.baseUrl}/reorder-suggestions/bulk-approve`, { ids, notes })
    return response.data
  }

  async bulkRejectReorderSuggestions(ids: number[], notes?: string) {
    const response = await axiosClient.post(`${this.baseUrl}/reorder-suggestions/bulk-reject`, { ids, notes })
    return response.data
  }

  async getReorderSuggestionStats() {
    const response = await axiosClient.get(`${this.baseUrl}/reorder-suggestions/stats/overview`)
    return response.data
  }

  async getReorderSuggestionTypes() {
    const response = await axiosClient.get(`${this.baseUrl}/reorder-suggestions/types`)
    return response.data
  }

  // ==================== SERIAL NUMBERS ====================
  async getSerialNumbers(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/serial-numbers`, { params })
    return response.data
  }

  async getSerialNumber(id: string) {
    const response = await axiosClient.get(`${this.baseUrl}/serial-numbers/${id}`)
    return response.data
  }

  async createSerialNumber(data: any) {
    const response = await axiosClient.post(`${this.baseUrl}/serial-numbers`, data)
    return response.data
  }

  async updateSerialNumber(id: string, data: any) {
    const response = await axiosClient.put(`${this.baseUrl}/serial-numbers/${id}`, data)
    return response.data
  }

  async deleteSerialNumber(id: string) {
    const response = await axiosClient.delete(`${this.baseUrl}/serial-numbers/${id}`)
    return response.data
  }

  async sellSerialNumber(id: string, data: any) {
    const response = await axiosClient.post(`${this.baseUrl}/serial-numbers/${id}/sell`, data)
    return response.data
  }

  async reserveSerialNumber(id: string, data: any) {
    const response = await axiosClient.post(`${this.baseUrl}/serial-numbers/${id}/reserve`, data)
    return response.data
  }

  async unreserveSerialNumber(id: string, data: any) {
    const response = await axiosClient.post(`${this.baseUrl}/serial-numbers/${id}/unreserve`, data)
    return response.data
  }

  async markSerialNumberAsDamaged(id: string, data: any) {
    const response = await axiosClient.post(`${this.baseUrl}/serial-numbers/${id}/mark-damaged`, data)
    return response.data
  }

  async returnSerialNumber(id: string, data: any) {
    const response = await axiosClient.post(`${this.baseUrl}/serial-numbers/${id}/return`, data)
    return response.data
  }

  async moveSerialNumberToLocation(id: string, data: any) {
    const response = await axiosClient.post(`${this.baseUrl}/serial-numbers/${id}/move-location`, data)
    return response.data
  }

  async getSerialNumberStats(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/serial-numbers/stats/overview`, { params })
    return response.data
  }

  async getExpiringWarranties(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/serial-numbers/expiring-warranties`, { params })
    return response.data
  }

  async getExpiredWarranties(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/serial-numbers/expired-warranties`, { params })
    return response.data
  }

  async generateNextSerialNumber(data: any) {
    const response = await axiosClient.post(`${this.baseUrl}/serial-numbers/generate-next`, data)
    return response.data
  }

  async checkSerialNumberExists(data: any) {
    const response = await axiosClient.post(`${this.baseUrl}/serial-numbers/check-exists`, data)
    return response.data
  }

  async getSerialNumbersByProduct(data: any) {
    const response = await axiosClient.get(`${this.baseUrl}/serial-numbers/by-product`, { params: data })
    return response.data
  }

  async transferSerialNumbers(data: any) {
    const response = await axiosClient.post(`${this.baseUrl}/serial-numbers/transfer`, data)
    return response.data
  }

  async bulkImportSerialNumbers(data: any) {
    const response = await axiosClient.post(`${this.baseUrl}/serial-numbers/bulk-import`, data)
    return response.data
  }

  async getSerialNumberTypes() {
    const response = await axiosClient.get(`${this.baseUrl}/serial-numbers/types`)
    return response.data
  }

  // ==================== BATCHES ====================
  async getBatches(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/batches`, { params })
    return response.data
  }

  async getBatch(id: number) {
    const response = await axiosClient.get(`${this.baseUrl}/batches/${id}`)
    return response.data
  }

  async createBatch(data: any) {
    const response = await axiosClient.post(`${this.baseUrl}/batches`, data)
    return response.data
  }

  async updateBatch(id: number, data: any) {
    const response = await axiosClient.put(`${this.baseUrl}/batches/${id}`, data)
    return response.data
  }

  async deleteBatch(id: number) {
    const response = await axiosClient.delete(`${this.baseUrl}/batches/${id}`)
    return response.data
  }

  async reserveBatchStock(id: number, data: any) {
    const response = await axiosClient.post(`${this.baseUrl}/batches/${id}/reserve-stock`, data)
    return response.data
  }

  async unreserveBatchStock(id: number, data: any) {
    const response = await axiosClient.post(`${this.baseUrl}/batches/${id}/unreserve-stock`, data)
    return response.data
  }

  async sellBatchStock(id: number, data: any) {
    const response = await axiosClient.post(`${this.baseUrl}/batches/${id}/sell-stock`, data)
    return response.data
  }

  async returnBatchStock(id: number, data: any) {
    const response = await axiosClient.post(`${this.baseUrl}/batches/${id}/return-stock`, data)
    return response.data
  }

  async markBatchAsDamaged(id: number, data: any) {
    const response = await axiosClient.post(`${this.baseUrl}/batches/${id}/mark-damaged`, data)
    return response.data
  }

  async moveBatchToLocation(id: number, data: any) {
    const response = await axiosClient.post(`${this.baseUrl}/batches/${id}/move-location`, data)
    return response.data
  }

  async approveBatchQuality(id: number) {
    const response = await axiosClient.post(`${this.baseUrl}/batches/${id}/approve-quality`)
    return response.data
  }

  async rejectBatchQuality(id: number) {
    const response = await axiosClient.post(`${this.baseUrl}/batches/${id}/reject-quality`)
    return response.data
  }

  async quarantineBatch(id: number) {
    const response = await axiosClient.post(`${this.baseUrl}/batches/${id}/quarantine`)
    return response.data
  }

  async getBatchStats(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/batches/stats/overview`, { params })
    return response.data
  }

  async getExpiringBatches(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/batches/expiring`, { params })
    return response.data
  }

  async getBestBeforeBatches(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/batches/best-before`, { params })
    return response.data
  }

  async generateNextBatchNumber(data: any) {
    const response = await axiosClient.post(`${this.baseUrl}/batches/generate-next`, data)
    return response.data
  }

  async checkBatchNumberExists(data: any) {
    const response = await axiosClient.post(`${this.baseUrl}/batches/check-exists`, data)
    return response.data
  }

  async getBatchesByProduct(productId: number) {
    const response = await axiosClient.get(`${this.baseUrl}/batches/by-product`, { params: { product_id: productId } })
    return response.data
  }

  async transferBatches(data: any) {
    const response = await axiosClient.post(`${this.baseUrl}/batches/transfer`, data)
    return response.data
  }

  async bulkImportBatches(data: any) {
    const response = await axiosClient.post(`${this.baseUrl}/batches/bulk-import`, data)
    return response.data
  }

  async updateBatchStatuses() {
    const response = await axiosClient.post(`${this.baseUrl}/batches/update-statuses`)
    return response.data
  }

  async getBatchTypes() {
    const response = await axiosClient.get(`${this.baseUrl}/batches/types`)
    return response.data
  }
}

export default new InventoryService()
