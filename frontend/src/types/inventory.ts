// frontend/src/types/inventory.ts

export interface BranchInventory {
  id: number;
  store_id: number;
  branch_id: number;
  product_id: number;
  variation_id?: number;
  quantity_on_hand: number;
  quantity_reserved: number;
  quantity_available: number;
  quantity_damaged: number;
  quantity_incoming: number;
  warehouse_section?: string;
  aisle?: string;
  rack?: string;
  shelf?: string;
  bin_code?: string;
  reorder_point: number;
  reorder_quantity: number;
  maximum_stock?: number;
  safety_stock: number;
  stock_status: 'in_stock' | 'low_stock' | 'out_of_stock' | 'discontinued' | 'on_order';
  unit_cost?: number;
  average_cost?: number;
  total_value?: number;
  last_stock_count_date?: string;
  last_counted_quantity?: number;
  last_counted_by?: number;
  created_at: string;
  updated_at: string;
  deleted_at?: string;
  
  // Relationships
  product?: any;
  variation?: any;
  branch?: any;
  store?: any;
  last_counted_by_user?: any;
}

export interface StockAdjustment {
  id: number;
  adjustment_number: string;
  store_id: number;
  branch_id: number;
  type: 'physical_count' | 'cycle_count' | 'spot_check' | 'damage' | 'loss' | 'found' | 'correction' | 'writeoff';
  status: 'draft' | 'pending_approval' | 'approved' | 'rejected' | 'applied';
  reason: string;
  adjustment_date: string;
  created_by: number;
  approved_by?: number;
  approved_at?: string;
  approval_notes?: string;
  created_at: string;
  updated_at: string;
  deleted_at?: string;
  
  // Relationships
  branch?: any;
  items?: StockAdjustmentItem[];
  created_by_user?: any;
  approved_by_user?: any;
}

export interface StockAdjustmentItem {
  id: number;
  adjustment_id: number;
  product_id: number;
  variation_id?: number;
  system_quantity: number;
  actual_quantity: number;
  difference: number;
  unit_cost?: number;
  value_difference?: number;
  notes?: string;
  created_at: string;
  updated_at: string;
  
  // Relationships
  product?: any;
  variation?: any;
  adjustment?: StockAdjustment;
}

export interface StockTransfer {
  id: number;
  transfer_number: string;
  store_id: number;
  from_branch_id: number;
  to_branch_id: number;
  status: 'draft' | 'requested' | 'sender_approved' | 'receiver_acknowledged' | 'pending_finance' | 
          'finance_approved' | 'approved' | 'in_transit' | 'received' | 'partially_received' | 'cancelled' | 'rejected';
  approval_policy_used?: string;
  cost_method?: string;
  distance_km?: number;
  transfer_cost: number;
  goods_value: number;
  cost_calculation_notes?: string;
  requested_date?: string;
  sender_approved_date?: string;
  receiver_acknowledged_date?: string;
  finance_approved_date?: string;
  shipped_date?: string;
  received_date?: string;
  expected_delivery_date?: string;
  requested_by: number;
  sender_approved_by?: number;
  receiver_acknowledged_by?: number;
  finance_approved_by?: number;
  shipped_by?: number;
  received_by?: number;
  vehicle_type?: string;
  driver_name?: string;
  driver_contact?: string;
  tracking_number?: string;
  reason?: string;
  notes?: string;
  rejection_reason?: string;
  created_at: string;
  updated_at: string;
  deleted_at?: string;
  
  // Relationships
  from_branch?: any;
  to_branch?: any;
  store?: any;
  items?: StockTransferItem[];
  requested_by_user?: any;
  sender_approved_by_user?: any;
  receiver_acknowledged_by_user?: any;
  finance_approved_by_user?: any;
  shipped_by_user?: any;
  received_by_user?: any;
}

export interface StockTransferItem {
  id: number;
  transfer_id: number;
  product_id: number;
  variation_id?: number;
  requested_quantity: number;
  approved_quantity?: number;
  shipped_quantity?: number;
  received_quantity?: number;
  damaged_quantity: number;
  unit_value?: number;
  notes?: string;
  created_at: string;
  updated_at: string;
  
  // Relationships
  product?: any;
  variation?: any;
  transfer?: StockTransfer;
}

export interface StockAlert {
  id: number;
  branch_id: number;
  product_id: number;
  variation_id?: number;
  alert_type: 'low_stock' | 'out_of_stock' | 'overstock' | 'reorder_needed' | 'expired_soon';
  current_quantity: number;
  reorder_point?: number;
  recommended_order_quantity?: number;
  status: 'active' | 'acknowledged' | 'resolved';
  acknowledged_by?: number;
  acknowledged_at?: string;
  created_at: string;
  updated_at: string;
  
  // Relationships
  branch?: any;
  product?: any;
  variation?: any;
  acknowledged_by_user?: any;
}

export interface InventoryTransaction {
  id: number;
  transaction_number: string;
  store_id: number;
  branch_id: number;
  product_id: number;
  variation_id?: number;
  transaction_type: 'purchase' | 'sale' | 'return_to_supplier' | 'customer_return' | 'transfer_out' | 
                    'transfer_in' | 'adjustment' | 'damage' | 'expired' | 'lost' | 'assembly' | 'sample' | 'writeoff';
  quantity_before: number;
  quantity_change: number;
  quantity_after: number;
  related_branch_id?: number;
  reference_type?: string;
  reference_id?: number;
  notes?: string;
  unit_cost?: number;
  total_value?: number;
  created_by: number;
  transaction_date: string;
  created_at: string;
  
  // Relationships
  branch?: any;
  product?: any;
  variation?: any;
  related_branch?: any;
  created_by_user?: any;
}

export interface InventorySummary {
  total_items: number;
  in_stock: number;
  low_stock: number;
  out_of_stock: number;
  total_value: number;
  total_quantity: number;
}

export interface InventoryFilters {
  branch_id?: number;
  stock_status?: string;
  search?: string;
  warehouse_section?: string;
  low_stock?: boolean;
  out_of_stock?: boolean;
  sort_by?: string;
  sort_order?: 'asc' | 'desc';
  per_page?: number;
  page?: number;
}

export interface AdjustmentFilters {
  branch_id?: number;
  status?: string;
  type?: string;
  start_date?: string;
  end_date?: string;
  sort_by?: string;
  sort_order?: 'asc' | 'desc';
  per_page?: number;
  page?: number;
}

export interface TransferFilters {
  from_branch_id?: number;
  to_branch_id?: number;
  status?: string;
  start_date?: string;
  end_date?: string;
  sort_by?: string;
  sort_order?: 'asc' | 'desc';
  per_page?: number;
  page?: number;
}

export interface AlertFilters {
  branch_id?: number;
  status?: string;
  alert_type?: string;
  sort_by?: string;
  sort_order?: 'asc' | 'desc';
  per_page?: number;
  page?: number;
}

export interface TransactionFilters {
  branch_id?: number;
  product_id?: number;
  transaction_type?: string;
  start_date?: string;
  end_date?: string;
  reference_type?: string;
  sort_by?: string;
  sort_order?: 'asc' | 'desc';
  per_page?: number;
  page?: number;
}

// Response types
export interface ApiResponse<T> {
  success: boolean;
  data: T;
  message?: string;
}

export interface PaginatedResponse<T> {
  success: boolean;
  data: {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
  };
}