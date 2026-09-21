import axiosClient from '../axios'

const unwrap = (response: any) => response.data?.data ?? response.data

export const WarehouseService = {
  dashboard: async () => unwrap(await axiosClient.get('/api/warehouse/dashboard')),
  warehouses: async (params: any = {}) => unwrap(await axiosClient.get('/api/warehouse/warehouses', { params })),
  warehouse: async (id: number | string) => unwrap(await axiosClient.get(`/api/warehouse/warehouses/${id}`)),
  stock: async (params: any = {}) => unwrap(await axiosClient.get('/api/warehouse/stock', { params })),
  stockItem: async (id: number | string) => unwrap(await axiosClient.get(`/api/warehouse/stock/${id}`)),
  stockItemOptions: async () => unwrap(await axiosClient.get('/api/warehouse/stock-options')),
  updateStockItem: async (id: number | string, payload: any) => unwrap(await axiosClient.put(`/api/warehouse/stock/${id}`, payload)),
  transferRequests: async (params: any = {}) => unwrap(await axiosClient.get('/api/warehouse/transfer-requests', { params })),
  transferRequest: async (id: number | string) => unwrap(await axiosClient.get(`/api/warehouse/transfer-requests/${id}`)),
  approveTransferRequest: async (id: number | string) => unwrap(await axiosClient.post(`/api/warehouse/transfer-requests/${id}/approve`)),
  rejectTransferRequest: async (id: number | string, rejection_reason: string) => unwrap(await axiosClient.post(`/api/warehouse/transfer-requests/${id}/reject`, { rejection_reason })),
  receiving: async (params: any = {}) => unwrap(await axiosClient.get('/api/warehouse/receiving', { params })),
  transferHistory: async (params: any = {}) => unwrap(await axiosClient.get('/api/warehouse/transfer-history', { params })),
  purchaseRequisitionOptions: async () => unwrap(await axiosClient.get('/api/warehouse/purchase-requisitions/options')),
  purchaseRequisitions: async (params: any = {}) => unwrap(await axiosClient.get('/api/warehouse/purchase-requisitions', { params })),
  purchaseRequisition: async (id: number | string) => unwrap(await axiosClient.get(`/api/warehouse/purchase-requisitions/${id}`)),
  createPurchaseRequisition: async (payload: any) => unwrap(await axiosClient.post('/api/warehouse/purchase-requisitions', payload)),
}

export default WarehouseService
