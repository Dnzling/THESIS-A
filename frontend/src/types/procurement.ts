// frontend/src/types/procurement.ts

export interface Supplier {
  id: number;
  supplier_code: string;
  supplier_name: string;
  company_name?: string;
  contact_person?: string;
  email?: string;
  phone: string;
  mobile?: string;
  address?: string;
  city?: string;
  province?: string;
  country?: string;
  supplier_type: 'manufacturer' | 'wholesaler' | 'distributor' | 'importer' | 'local_artisan';
  payment_terms: 'cash_on_delivery' | 'net_7' | 'net_15' | 'net_30' | 'net_60' | 'advance_payment';
  credit_limit?: number;
  status: 'active' | 'inactive' | 'blacklisted';
  rating: number;
  total_orders: number;
  total_amount_purchased: number;
  on_time_deliveries: number;
  late_deliveries: number;
  current_balance: number;
  created_at: string;
  updated_at: string;
}

export interface PurchaseRequisition {
  id: number;
  pr_number: string;
  store_id: number;
  branch_id: number;
  requisition_type: 'regular' | 'urgent' | 'new_product' | 'seasonal' | 'emergency';
  status: 'draft' | 'submitted' | 'warehouse_approved' | 'finance_approved' | 'approved' | 'rejected' | 'cancelled' | 'converted_to_po';
  estimated_amount: number;
  procurement_route: 'branch_direct' | 'centralized' | 'rfq_required';
  required_approvals: string[];
  approval_chain: any[];
  required_date: string;
  reason: string;
  priority: number;
  requested_by: number;
  created_at: string;
  updated_at: string;
  items?: PurchaseRequisitionItem[];
  branch?: any;
  requested_by_user?: any;
}

export interface PurchaseRequisitionItem {
  id: number;
  requisition_id: number;
  product_id: number;
  variation_id?: number;
  quantity_requested: number;
  estimated_unit_cost?: number;
  specifications?: string;
  product?: any;
  variation?: any;
}

export interface RequestForQuotation {
  id: number;
  rfq_number: string;
  store_id: number;
  purchase_requisition_id?: number;
  title: string;
  description?: string;
  issue_date: string;
  deadline_date: string;
  status: 'draft' | 'sent' | 'quotes_received' | 'under_evaluation' | 'awarded' | 'cancelled';
  awarded_to_supplier_id?: number;
  evaluation_notes?: string;
  created_by: number;
  created_at: string;
  updated_at?: string;
  items?: RFQItem[];
  suppliers?: Supplier[];
  quotations?: SupplierQuotation[];
}

export interface RFQItem {
  id: number;
  rfq_id: number;
  product_id: number;
  variation_id?: number;
  quantity: number;
  specifications?: string;
  requirements?: string;
  product?: any;
  variation?: any;
}

export interface SupplierQuotation {
  id: number;
  quotation_number: string;
  rfq_id: number;
  supplier_id: number;
  quotation_date: string;
  valid_until: string;
  subtotal: number;
  tax_amount: number;
  shipping_cost: number;
  total_amount: number;
  payment_terms: string;
  delivery_days: number;
  notes?: string;
  status: 'draft' | 'submitted' | 'under_evaluation' | 'accepted' | 'rejected';
  evaluation_score?: number;
  evaluation_notes?: string;
  created_at?: string;
  updated_at?: string;
  supplier?: Supplier;
  items?: SupplierQuotationItem[];
  rfq?: RequestForQuotation;
}

export interface SupplierQuotationItem {
  id: number;
  quotation_id: number;
  rfq_item_id: number;
  unit_price: number;
  quantity: number;
  discount_percent: number;
  line_total: number;
  notes?: string;
  rfq_item?: RFQItem;
}

export interface PurchaseOrder {
  id: number;
  po_number: string;
  store_id: number;
  branch_id: number;
  supplier_id: number;
  purchase_requisition_id?: number;
  rfq_id?: number;
  supplier_quotation_id?: number;
  status: 'draft' | 'pending_approval' | 'partially_approved' | 'fully_approved' | 'finance_approved' | 'ordered' | 'partially_received' | 'received' | 'cancelled' | 'rejected';
  subtotal: number;
  tax_amount: number;
  shipping_cost: number;
  discount_amount: number;
  total_amount: number;
  approval_tier_level?: number;
  required_approvers: string[];
  approvals_received: any[];
  rfq_required: boolean;
  payment_status: 'pending' | 'partial' | 'paid' | 'overdue';
  payment_terms: string;
  payment_due_date?: string;
  order_date: string;
  expected_delivery_date: string;
  actual_delivery_date?: string;
  created_by: number;
  notes?: string;
  terms_conditions?: string;
  created_at: string;
  updated_at: string;
  items?: PurchaseOrderItem[];
  supplier?: Supplier;
  branch?: any;
  created_by_user?: any;
}

export interface PurchaseOrderItem {
  id: number;
  purchase_order_id: number;
  product_id: number;
  variation_id?: number;
  quantity_ordered: number;
  quantity_received: number;
  quantity_damaged: number;
  quantity_pending: number;
  unit_cost: number;
  tax_rate: number;
  discount_percent: number;
  line_total: number;
  notes?: string;
  product?: any;
  variation?: any;
}

export interface GoodsReceipt {
  id: number;
  grn_number: string;
  purchase_order_id: number;
  branch_id: number;
  receipt_date: string;
  receipt_time: string;
  receipt_status: 'full' | 'partial' | 'damaged' | 'rejected';
  received_by: number;
  verified_by?: number;
  verified_at?: string;
  delivery_note_number?: string;
  vehicle_number?: string;
  driver_name?: string;
  driver_contact?: string;
  discrepancy_notes?: string;
  quality_notes?: string;
  created_at: string;
  updated_at?: string;
  items?: GoodsReceiptItem[];
  purchase_order?: PurchaseOrder;
  received_by_user?: any;
  verified_by_user?: any;
}

export interface GoodsReceiptItem {
  id: number;
  goods_receipt_id: number;
  purchase_order_item_id: number;
  product_id: number;
  variation_id?: number;
  quantity_expected: number;
  quantity_received: number;
  quantity_damaged: number;
  condition: 'good' | 'damaged' | 'defective';
  notes?: string;
  product?: any;
  variation?: any;
  purchase_order_item?: PurchaseOrderItem;
}

export interface SupplierPayment {
  id: number;
  payment_number: string;
  store_id: number;
  purchase_order_id: number;
  supplier_id: number;
  payment_amount: number;
  payment_method: 'cash' | 'check' | 'bank_transfer' | 'credit_card' | 'debit_card' | 'online_payment';
  status: 'pending_approval' | 'approved' | 'processing' | 'completed' | 'failed' | 'cancelled';
  payment_date: string;
  reference_number?: string;
  bank_name?: string;
  account_number?: string;
  approved_by?: number;
  approved_at?: string;
  processed_by?: number;
  processed_at?: string;
  notes?: string;
  created_at: string;
  updated_at?: string;
  supplier?: Supplier;
  purchase_order?: PurchaseOrder;
  approved_by_user?: any;
  processed_by_user?: any;
}

export interface SupplierPerformance {
  supplier_id: number;
  total_orders: number;
  total_amount: number;
  average_order_value: number;
  on_time_delivery_rate: number;
  quality_rating: number;
  recent_orders: PurchaseOrder[];
  monthly_orders: { month: string; count: number; amount: number }[];
}

export interface ProcurementDashboardStats {
  active_pos: {
    count: number;
    total_value: number;
  };
  pending_approvals: {
    pr_count: number;
    po_count: number;
  };
  pending_payments: {
    count: number;
    total_amount: number;
  };
  active_suppliers: {
    count: number;
    average_rating: number;
  };
  purchase_trend: { month: string; count: number; amount: number }[];
  top_suppliers: { supplier: Supplier; total_amount: number }[];
  recent_activities: any[];
}
