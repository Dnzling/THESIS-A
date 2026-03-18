import { ref } from 'vue'
import procurementService from '../../services/procurement.service'

export interface POLineItem {
  id?: string
  product_id: number
  variation_id?: number
  product_name?: string
  product_image?: string
  quantity_ordered: number
  unit_cost: number
  discount_percent: number
  line_total?: number
  stock_level?: number
  reorder_point?: number
  last_price_date?: string
}

export interface POForm {
  po_number?: string
  supplier_id: number
  branch_id: number
  store_id?: number
  order_date: string
  expected_delivery_date: string
  payment_terms: string
  shipping_cost: number
  discount_amount: number
  notes: string
  items: POLineItem[]
  subtotal?: number
  total_amount?: number
}

export const usePoAutomation = () => {
  const suppliers = ref<any[]>([])
  const products = ref<any[]>([])
  const branches = ref<any[]>([])
  const selectedSupplier = ref<any>(null)
  const storeSettings = ref<any>(null)

  // Supplier auto-population
  const autoFillSupplierDetails = async (supplierId: number) => {
    try {
      const response = await procurementService.getSupplier(supplierId)
      selectedSupplier.value = response.data

      return response.data
    } catch (error) {
      console.error('Failed to auto-fill supplier details', error)
      return null
    }
  }

  // Generate PO number with format: PO-{STORE}-{BRANCH}-{YYYY}-{SEQUENCE}
  const generatePoNumber = (storeCode: string, branchCode: string, sequence: number): string => {
    const year = new Date().getFullYear()
    return `PO-${storeCode}-${branchCode}-${year}-${String(sequence).padStart(5, '0')}`
  }

  // Auto-calculate expected delivery date based on supplier's average delivery days
  const autoSetDeliveryDate = (supplierDeliveryDays?: number): string => {
    const today = new Date()
    const deliveryDays = supplierDeliveryDays ?? 7
    const deliveryDate = new Date(today.getTime() + deliveryDays * 24 * 60 * 60 * 1000)
    return deliveryDate.toISOString().split('T')[0] || ''
  }

  // Calculate line item total with discount
  const calculateLineTotal = (item: POLineItem): number => {
    let total = item.quantity_ordered * item.unit_cost

    // Apply discount
    if (item.discount_percent > 0) {
      total -= total * (item.discount_percent / 100)
    }

    return Math.round(total * 100) / 100
  }

  // Calculate PO totals
  const calculateTotals = (items: POLineItem[], shippingCost: number = 0, discountAmount: number = 0) => {
    let subtotal = 0

    items.forEach((item) => {
      const itemSubtotal = item.quantity_ordered * item.unit_cost

      // Deduct item discount from subtotal
      if (item.discount_percent > 0) {
        subtotal += itemSubtotal - itemSubtotal * (item.discount_percent / 100)
      } else {
        subtotal += itemSubtotal
      }
    })

    const totalAmount = subtotal + shippingCost - discountAmount

    return {
      subtotal: Math.round(subtotal * 100) / 100,
      total_amount: Math.round(totalAmount * 100) / 100
    }
  }

  // Validate line item quantity against budget
  const validateQuantityAgainstBudget = (
    quantity: number,
    unitPrice: number,
    branchBudgetRemaining: number
  ): { valid: boolean; message?: string; percentage?: number } => {
    const lineTotal = quantity * unitPrice

    if (lineTotal > branchBudgetRemaining) {
      const percentage = Math.round((lineTotal / branchBudgetRemaining) * 100)
      return {
        valid: false,
        message: `This item (${(lineTotal).toFixed(2)}) exceeds remaining budget (${(branchBudgetRemaining).toFixed(2)}) by ${percentage - 100}%`,
        percentage
      }
    }

    return { valid: true }
  }

  // Check for supplier issues
  const checkSupplierStatus = (supplier: any): { status: string; severity: string; message: string } => {
    if (!supplier) {
      return {
        status: 'unknown',
        severity: 'info',
        message: 'Supplier details are not available yet.'
      }
    }
    if (supplier.status === 'blacklisted') {
      return {
        status: 'blacklisted',
        severity: 'danger',
        message: 'This supplier is blacklisted. Consider selecting an alternative supplier.'
      }
    }

    if (supplier.rating && supplier.rating < 3) {
      return {
        status: 'low_rating',
        severity: 'warning',
        message: `This supplier has a low rating (${supplier.rating}/5). Consider reviewing their performance history.`
      }
    }

    if (supplier.recent_delay_percentage && supplier.recent_delay_percentage > 20) {
      return {
        status: 'delivery_issues',
        severity: 'warning',
        message: `This supplier has had ${supplier.recent_delay_percentage}% late deliveries recently. Consider checking alternative suppliers.`
      }
    }

    return {
      status: 'ok',
      severity: 'success',
      message: 'Supplier status is good'
    }
  }

  // Get frequently purchased products (top 10)
  const getFrequentlyPurchasedProducts = async (): Promise<POLineItem[]> => {
    try {
      const response = await procurementService.getProductHistory({ limit: 10 })
      return response.data?.map((item: any) => ({
        id: `frequent-${item.product_id}`,
        product_id: item.product_id,
        product_name: item.product_name,
        product_image: item.product_image,
        quantity_ordered: item.average_quantity || 1,
        unit_cost: item.last_price || 0,
        discount_percent: 0,
        stock_level: item.current_stock || 0,
        reorder_point: item.reorder_point || 0
      })) || []
    } catch (error) {
      console.error('Failed to load frequently purchased products', error)
      return []
    }
  }

  // Get product reorder suggestions
  const getReorderSuggestion = (product: any): number => {
    if (!product.reorder_point || !product.current_stock) {
      return 10 // default suggestion
    }

    const shortage = product.reorder_point - product.current_stock
    return Math.max(shortage > 0 ? shortage * 1.5 : 10, 1)
  }

  // Validate date reasonability
  const validateDeliveryDate = (
    orderDate: string,
    deliveryDate: string,
    maxDaysInFuture: number = 90
  ): { valid: boolean; message?: string } => {
    const order = new Date(orderDate)
    const delivery = new Date(deliveryDate)
    const today = new Date()

    if (delivery <= order) {
      return {
        valid: false,
        message: 'Expected delivery date must be after order date.'
      }
    }

    if (delivery < today) {
      return {
        valid: false,
        message: 'Expected delivery date cannot be in the past.'
      }
    }

    const daysFromNow = Math.ceil((delivery.getTime() - today.getTime()) / (1000 * 60 * 60 * 24))
    if (daysFromNow > maxDaysInFuture) {
      return {
        valid: false,
        message: `Expected delivery date cannot be more than ${maxDaysInFuture} days in the future.`
      }
    }

    return { valid: true }
  }

  // Format currency
  const formatCurrency = (value: number, currency: string = 'PHP'): string => {
    return new Intl.NumberFormat('en-PH', {
      style: 'currency',
      currency
    }).format(value)
  }

  // Format date
  const formatDate = (date: string): string => {
    return new Intl.DateTimeFormat('en-PH', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    }).format(new Date(date))
  }

  return {
    suppliers,
    products,
    branches,
    selectedSupplier,
    storeSettings,
    autoFillSupplierDetails,
    generatePoNumber,
    autoSetDeliveryDate,
    calculateLineTotal,
    calculateTotals,
    validateQuantityAgainstBudget,
    checkSupplierStatus,
    getFrequentlyPurchasedProducts,
    getReorderSuggestion,
    validateDeliveryDate,
    formatCurrency,
    formatDate
  }
}
