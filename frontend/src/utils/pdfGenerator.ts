/**
 * PDF Generation Utility for Procurement Module
 * Generates professional PO PDFs with company letterhead
 */

export interface PDFConfig {
  pageSize?: string
  orientation?: 'portrait' | 'landscape'
  margin?: number
}

export interface CompanyInfo {
  name: string
  address: string
  phone: string
  email: string
  website?: string
  logo?: string
  taxId?: string
}

export interface POPrintData {
  po_number: string
  order_date: string
  expected_delivery_date: string
  po_id: number
  supplier: {
    supplier_name: string
    contact_person?: string
    email?: string
    phone?: string
    address?: string
  }
  branch?: {
    branch_name: string
    branch_code: string
    address?: string
  }
  items: Array<{
    product_name: string
    product_id: number
    quantity_ordered: number
    unit_cost: number
    tax_rate: number
    discount_percent: number
    line_total: number
  }>
  subtotal: number
  tax_total: number
  shipping_cost?: number
  discount_amount?: number
  total_amount: number
  payment_terms: string
  notes?: string
  approver_name?: string
  status: string
}

export interface GRPrintData extends POPrintData {
  grn_number: string
  receipt_date: string
  received_items: Array<{
    product_name: string
    quantity_ordered: number
    quantity_received: number
    unit_cost: number
    remarks?: string
    status: 'complete' | 'short' | 'damaged' | 'wrong'
  }>
}

/**
 * Generate HTML content for PO PDF
 */
export const generatePOHtml = (data: POPrintData, company: CompanyInfo): string => {
  const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('en-PH', {
      style: 'currency',
      currency: 'PHP'
    }).format(value)
  }

  const formatDate = (date: string) => {
    return new Intl.DateTimeFormat('en-PH', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    }).format(new Date(date))
  }

  const discrepancies = data.items
    .filter((item) => item.discount_percent > 0 || item.tax_rate !== data.items[0]?.tax_rate)
    .length > 0

  return `
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="UTF-8">
      <title>Purchase Order ${data.po_number}</title>
      <style>
        * {
          margin: 0;
          padding: 0;
          box-sizing: border-box;
        }
        
        body {
          font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
          color: #333;
          line-height: 1.4;
          font-size: 11px;
        }
        
        @page {
          size: A4;
          margin: 0.5in;
        }
        
        .container {
          max-width: 900px;
          margin: 0;
          background: white;
        }
        
        /* Header */
        .header {
          display: flex;
          justify-content: space-between;
          align-items: flex-start;
          margin-bottom: 20px;
          padding-bottom: 15px;
          border-bottom: 3px solid #1e5aa8;
        }
        
        .company-info h1 {
          font-size: 22px;
          color: #1e5aa8;
          margin-bottom: 5px;
        }
        
        .company-info p {
          font-size: 10px;
          color: #666;
          margin: 2px 0;
        }
        
        .po-header {
          text-align: right;
        }
        
        .po-header .po-number {
          font-size: 18px;
          font-weight: bold;
          color: #1e5aa8;
          margin-bottom: 5px;
        }
        
        .po-header .status-badge {
          display: inline-block;
          background: #e8f4f8;
          color: #1e5aa8;
          padding: 4px 8px;
          border-radius: 3px;
          font-size: 10px;
          font-weight: bold;
          margin-bottom: 10px;
        }
        
        .meta-grid {
          display: grid;
          grid-template-columns: 1fr 1fr 1fr;
          gap: 10px;
          font-size: 10px;
          margin-bottom: 20px;
        }
        
        .meta-item {
          background: #f5f5f5;
          padding: 8px;
          border-left: 3px solid #1e5aa8;
        }
        
        .meta-item strong {
          display: block;
          color: #1e5aa8;
          margin-bottom: 3px;
        }
        
        /* Addresses */
        .addresses {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 15px;
          margin-bottom: 20px;
          font-size: 10px;
        }
        
        .address-card {
          background: #f9f9f9;
          padding: 10px;
          border: 1px solid #e0e0e0;
          border-radius: 4px;
        }
        
        .address-card h3 {
          color: #1e5aa8;
          font-size: 11px;
          margin-bottom: 5px;
          border-bottom: 1px solid #ddd;
          padding-bottom: 3px;
        }
        
        .address-card p {
          margin: 2px 0;
          color: #555;
        }
        
        /* Items Table */
        .items-section {
          margin-bottom: 20px;
        }
        
        .items-section h3 {
          color: #1e5aa8;
          font-size: 12px;
          margin-bottom: 8px;
          border-bottom: 2px solid #1e5aa8;
          padding-bottom: 5px;
        }
        
        table {
          width: 100%;
          border-collapse: collapse;
          margin-bottom: 15px;
        }
        
        table thead {
          background: #1e5aa8;
          color: white;
        }
        
        table th {
          padding: 8px;
          text-align: left;
          font-weight: 600;
          font-size: 10px;
          border: 1px solid #1e5aa8;
        }
        
        table td {
          padding: 8px;
          border: 1px solid #e0e0e0;
          font-size: 10px;
        }
        
        table tbody tr:nth-child(even) {
          background: #f9f9f9;
        }
        
        table tbody tr:hover {
          background: #f0f8ff;
        }
        
        .text-right {
          text-align: right;
        }
        
        .text-center {
          text-align: center;
        }
        
        /* Totals */
        .totals {
          margin-bottom: 20px;
          display: flex;
          justify-content: flex-end;
        }
        
        .totals-box {
          width: 300px;
          background: #f9f9f9;
          border: 2px solid #1e5aa8;
          border-radius: 4px;
          padding: 10px;
        }
        
        .total-row {
          display: flex;
          justify-content: space-between;
          margin-bottom: 6px;
          font-size: 10px;
        }
        
        .total-row strong {
          font-weight: 600;
        }
        
        .total-row.highlight {
          background: #1e5aa8;
          color: white;
          margin: 0 -10px 0 -10px;
          padding: 6px 10px;
          font-size: 12px;
          font-weight: bold;
          border-radius: 0 0 2px 2px;
        }
        
        /* Notes and Footer */
        .notes-section {
          margin-bottom: 20px;
          background: #f5f5f5;
          padding: 10px;
          border-left: 3px solid #1e5aa8;
          border-radius: 3px;
        }
        
        .notes-section h4 {
          color: #1e5aa8;
          font-size: 11px;
          margin-bottom: 5px;
        }
        
        .notes-section p {
          font-size: 10px;
          color: #666;
          white-space: pre-wrap;
        }
        
        .terms-section {
          margin-bottom: 20px;
          font-size: 10px;
        }
        
        .terms-section h4 {
          color: #1e5aa8;
          font-size: 11px;
          margin-bottom: 5px;
        }
        
        .terms-section p {
          color: #666;
          margin: 3px 0;
        }
        
        /* Signature Section */
        .signature-section {
          display: grid;
          grid-template-columns: 1fr 1fr 1fr;
          gap: 20px;
          margin-top: 40px;
          font-size: 10px;
        }
        
        .signature-block {
          text-align: center;
        }
        
        .signature-line {
          border-top: 1px solid #333;
          margin-bottom: 3px;
          height: 50px;
        }
        
        .signature-name {
          font-weight: bold;
          margin-bottom: 2px;
        }
        
        .signature-title {
          font-size: 9px;
          color: #666;
        }
        
        /* Footer */
        .footer {
          margin-top: 30px;
          padding-top: 10px;
          border-top: 1px solid #ddd;
          text-align: center;
          font-size: 9px;
          color: #999;
        }
        
        .discount-highlight {
          background: #fff3cd;
          color: #856404;
        }
      </style>
    </head>
    <body>
      <div class="container">
        <!-- Header -->
        <div class="header">
          <div class="company-info">
            <h1>${company.name}</h1>
            <p>${company.address}</p>
            <p>📞 ${company.phone} | 📧 ${company.email}</p>
            ${company.taxId ? `<p>Tax ID: ${company.taxId}</p>` : ''}
          </div>
          <div class="po-header">
            <div class="po-number">${data.po_number}</div>
            <div class="status-badge">${data.status.toUpperCase()}</div>
            <div style="font-size: 10px; color: #666;">
              <p><strong>Order Date:</strong> ${formatDate(data.order_date)}</p>
              <p><strong>Expected Delivery:</strong> ${formatDate(data.expected_delivery_date)}</p>
              <p><strong>Currency:</strong> PHP</p>
            </div>
          </div>
        </div>
        
        <!-- Meta Information -->
        <div class="meta-grid">
          <div class="meta-item">
            <strong>Supplier</strong>
            ${data.supplier.supplier_name}
          </div>
          <div class="meta-item">
            <strong>Branch</strong>
            ${data.branch?.branch_name || 'N/A'}
          </div>
          <div class="meta-item">
            <strong>Payment Terms</strong>
            ${data.payment_terms || 'N/A'}
          </div>
        </div>
        
        <!-- Addresses -->
        <div class="addresses">
          <div class="address-card">
            <h3>SHIP TO</h3>
            <p><strong>${data.branch?.branch_name || 'Main Branch'}</strong></p>
            <p>${data.branch?.address || '-'}</p>
          </div>
          <div class="address-card">
            <h3>SUPPLIER CONTACT</h3>
            <p><strong>${data.supplier.supplier_name}</strong></p>
            <p>${data.supplier.contact_person || '-'}</p>
            <p>${data.supplier.email || '-'}</p>
            <p>${data.supplier.phone || '-'}</p>
          </div>
        </div>
        
        <!-- Items Table -->
        <div class="items-section">
          <h3>ORDER DETAILS</h3>
          <table>
            <thead>
              <tr>
                <th style="width: 5%">No.</th>
                <th style="width: 30%">Product Description</th>
                <th class="text-center" style="width: 12%">Quantity</th>
                <th class="text-right" style="width: 15%">Unit Price</th>
                ${discrepancies ? '<th class="text-right" style="width: 10%">Tax</th>' : ''}
                ${discrepancies ? '<th class="text-right" style="width: 10%">Discount</th>' : ''}
                <th class="text-right" style="width: ${discrepancies ? '18%' : '28%'}">Line Total</th>
              </tr>
            </thead>
            <tbody>
              ${data.items
                .map(
                  (item, index) => `
                <tr>
                  <td class="text-center">${index + 1}</td>
                  <td>${item.product_name}</td>
                  <td class="text-center">${item.quantity_ordered}</td>
                  <td class="text-right">₱ ${item.unit_cost.toFixed(2)}</td>
                  ${discrepancies ? `<td class="text-right">${item.tax_rate.toFixed(0)}%</td>` : ''}
                  ${discrepancies ? `<td class="text-right ${item.discount_percent > 0 ? 'discount-highlight' : ''}">${item.discount_percent.toFixed(0)}%</td>` : ''}
                  <td class="text-right">₱ ${item.line_total.toFixed(2)}</td>
                </tr>
              `
                )
                .join('')}
            </tbody>
          </table>
        </div>
        
        <!-- Totals -->
        <div class="totals">
          <div class="totals-box">
            <div class="total-row">
              <strong>Subtotal:</strong>
              <span>₱ ${data.subtotal.toFixed(2)}</span>
            </div>
            <div class="total-row">
              <strong>Tax (VAT):</strong>
              <span>₱ ${data.tax_total.toFixed(2)}</span>
            </div>
            ${data.shipping_cost ? `
            <div class="total-row">
              <strong>Shipping Cost:</strong>
              <span>₱ ${(data.shipping_cost || 0).toFixed(2)}</span>
            </div>
            ` : ''}
            ${data.discount_amount ? `
            <div class="total-row discount-highlight">
              <strong>Discount:</strong>
              <span>-₱ ${Math.abs(data.discount_amount || 0).toFixed(2)}</span>
            </div>
            ` : ''}
            <div class="total-row highlight">
              TOTAL AMOUNT
              <span style="font-size: 14px;">₱ ${data.total_amount.toFixed(2)}</span>
            </div>
          </div>
        </div>
        
        <!-- Special Instructions -->
        ${
          data.notes
            ? `
        <div class="notes-section">
          <h4>📝 SPECIAL INSTRUCTIONS / NOTES</h4>
          <p>${data.notes}</p>
        </div>
        `
            : ''
        }
        
        <!-- Terms & Conditions -->
        <div class="terms-section">
          <h4>TERMS & CONDITIONS</h4>
          <p>• Payment Terms: ${data.payment_terms || 'Net 30'}</p>
          <p>• This Purchase Order is valid for 7 days from the order date.</p>
          <p>• All goods must be accompanied by valid tax invoices.</p>
          <p>• Quality inspection will be performed upon receipt.</p>
          <p>• Returns must be authorized before shipment.</p>
        </div>
        
        <!-- Signature Section -->
        <div class="signature-section">
          <div class="signature-block">
            <div class="signature-line"></div>
            <div class="signature-name">Prepared By</div>
            <div class="signature-title">Procurement Officer</div>
          </div>
          <div class="signature-block">
            <div class="signature-line"></div>
            <div class="signature-name">${data.approver_name || '_________________'}</div>
            <div class="signature-title">Approved By<br/>Finance Manager</div>
          </div>
          <div class="signature-block">
            <div class="signature-line"></div>
            <div class="signature-name">_________________</div>
            <div class="signature-title">Supplier Rep<br/>Date:__________</div>
          </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
          <p>© ${new Date().getFullYear()} ${company.name}. All rights reserved. | Generated: ${new Date().toLocaleString()}</p>
          <p>This is a computer-generated document. Signature/stamp signifies approval of this Purchase Order.</p>
        </div>
      </div>
    </body>
    </html>
  `
}

/**
 * Generate HTML for Goods Receipt
 */
export const generateGRHtml = (data: GRPrintData, company: CompanyInfo): string => {
  const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('en-PH', {
      style: 'currency',
      currency: 'PHP'
    }).format(value)
  }

  const formatDate = (date: string) => {
    return new Intl.DateTimeFormat('en-PH', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    }).format(new Date(date))
  }

  const hasDiscrepancies = data.received_items.some((item) => item.status !== 'complete')

  return `
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="UTF-8">
      <title>Goods Receipt ${data.grn_number}</title>
      <style>
        * {
          margin: 0;
          padding: 0;
          box-sizing: border-box;
        }
        
        body {
          font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
          color: #333;
          line-height: 1.4;
          font-size: 11px;
        }
        
        @page {
          size: A4;
          margin: 0.5in;
        }
        
        .container {
          max-width: 900px;
          margin: 0;
          background: white;
        }
        
        .header {
          display: flex;
          justify-content: space-between;
          align-items: flex-start;
          margin-bottom: 20px;
          padding-bottom: 15px;
          border-bottom: 3px solid #155e75;
        }
        
        .company-info h1 {
          font-size: 22px;
          color: #155e75;
          margin-bottom: 5px;
        }
        
        .company-info p {
          font-size: 10px;
          color: #666;
          margin: 2px 0;
        }
        
        .gr-header {
          text-align: right;
        }
        
        .gr-header .gr-number {
          font-size: 18px;
          font-weight: bold;
          color: #155e75;
          margin-bottom: 5px;
        }
        
        .gr-header .status-badge {
          display: inline-block;
          background: #e0f2fe;
          color: #155e75;
          padding: 4px 8px;
          border-radius: 3px;
          font-size: 10px;
          font-weight: bold;
          margin-bottom: 10px;
        }
        
        .meta-grid {
          display: grid;
          grid-template-columns: 1fr 1fr 1fr;
          gap: 10px;
          font-size: 10px;
          margin-bottom: 20px;
        }
        
        .meta-item {
          background: #f5f5f5;
          padding: 8px;
          border-left: 3px solid #155e75;
        }
        
        .meta-item strong {
          display: block;
          color: #155e75;
          margin-bottom: 3px;
        }
        
        .reference-info {
          background: #e0f2fe;
          padding: 10px;
          border-radius: 4px;
          margin-bottom: 15px;
          font-size: 10px;
        }
        
        .reference-info strong {
          color: #155e75;
        }
        
        /* Discrepancy Alert */
        .discrepancy-alert {
          background: #fee2e2;
          border: 2px solid #dc2626;
          color: #991b1b;
          padding: 10px;
          border-radius: 4px;
          margin-bottom: 15px;
          font-size: 10px;
        }
        
        .discrepancy-alert h3 {
          color: #dc2626;
          margin-bottom: 5px;
        }
        
        table {
          width: 100%;
          border-collapse: collapse;
          margin-bottom: 15px;
        }
        
        table thead {
          background: #155e75;
          color: white;
        }
        
        table th {
          padding: 8px;
          text-align: left;
          font-weight: 600;
          font-size: 10px;
          border: 1px solid #155e75;
        }
        
        table td {
          padding: 8px;
          border: 1px solid #e0e0e0;
          font-size: 10px;
        }
        
        table tbody tr:nth-child(even) {
          background: #f9f9f9;
        }
        
        .text-right {
          text-align: right;
        }
        
        .text-center {
          text-align: center;
        }
        
        .status-badge-cell {
          padding: 4px 6px;
          border-radius: 3px;
          font-size: 9px;
          font-weight: bold;
          text-align: center;
        }
        
        .status-complete {
          background: #d1fae5;
          color: #065f46;
        }
        
        .status-short {
          background: #fef3c7;
          color: #92400e;
        }
        
        .status-damaged {
          background: #fee2e2;
          color: #991b1b;
        }
        
        .status-wrong {
          background: #dbeafe;
          color: #1e40af;
        }
        
        .variance-highlight {
          background: #fef3c7;
          color: #92400e;
        }
        
        .signature-section {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 20px;
          margin-top: 40px;
          font-size: 10px;
        }
        
        .signature-block {
          text-align: center;
        }
        
        .signature-line {
          border-top: 1px solid #333;
          margin-bottom: 3px;
          height: 50px;
        }
        
        .signature-name {
          font-weight: bold;
          margin-bottom: 2px;
        }
        
        .signature-title {
          font-size: 9px;
          color: #666;
        }
        
        .footer {
          margin-top: 30px;
          padding-top: 10px;
          border-top: 1px solid #ddd;
          text-align: center;
          font-size: 9px;
          color: #999;
        }
      </style>
    </head>
    <body>
      <div class="container">
        <!-- Header -->
        <div class="header">
          <div class="company-info">
            <h1>${company.name}</h1>
            <p>GOODS RECEIPT NOTIFICATION</p>
            <p>${company.address}</p>
          </div>
          <div class="gr-header">
            <div class="gr-number">${data.grn_number}</div>
            <div class="status-badge">RECEIVED</div>
            <div style="font-size: 10px; color: #666;">
              <p><strong>Receipt Date:</strong> ${formatDate(data.receipt_date)}</p>
              <p><strong>PO Reference:</strong> ${data.po_number}</p>
            </div>
          </div>
        </div>
        
        <!-- Meta Information -->
        <div class="meta-grid">
          <div class="meta-item">
            <strong>Supplier</strong>
            ${data.supplier.supplier_name}
          </div>
          <div class="meta-item">
            <strong>Branch</strong>
            ${data.branch?.branch_name || 'N/A'}
          </div>
          <div class="meta-item">
            <strong>Receipt Status</strong>
            ${data.receipt_status || 'Complete'}
          </div>
        </div>
        
        <!-- Reference Information -->
        <div class="reference-info">
          <strong>📋 Reference:</strong> Original PO ${data.po_number} dated 
          ${formatDate(data.order_date)} | Expected Delivery: ${formatDate(data.expected_delivery_date)}
        </div>
        
        ${
          hasDiscrepancies
            ? `
        <div class="discrepancy-alert">
          <h3>⚠️ DISCREPANCIES DETECTED</h3>
          <p>This receipt contains variances between ordered and received quantities or condition issues. 
          Review the details table below carefully.</p>
        </div>
        `
            : ''
        }
        
        <!-- Receipt Details Table -->
        <h3 style="color: #155e75; margin-bottom: 10px;">RECEIVED ITEMS</h3>
        <table>
          <thead>
            <tr>
              <th style="width: 5%">No.</th>
              <th style="width: 35%">Product Description</th>
              <th class="text-center" style="width: 12%">Ordered</th>
              <th class="text-center" style="width: 12%">Received</th>
              <th class="text-center" style="width: 10%">Variance</th>
              <th class="text-center" style="width: 12%">Status</th>
              <th style="width: 14%">Remarks</th>
            </tr>
          </thead>
          <tbody>
            ${data.received_items
              .map(
                (item, index) => {
                  const variance = item.quantity_received - item.quantity_ordered
                  const variancePercent = ((variance / item.quantity_ordered) * 100).toFixed(0)
                  const isVariance = variance !== 0
                  const cellClass = isVariance ? 'variance-highlight' : ''

                  return `
                <tr>
                  <td class="text-center">${index + 1}</td>
                  <td>${item.product_name}</td>
                  <td class="text-center">${item.quantity_ordered}</td>
                  <td class="text-center ${cellClass}">${item.quantity_received}</td>
                  <td class="text-center ${cellClass}">${variance >= 0 ? '+' : ''}${variance} (${variancePercent}%)</td>
                  <td class="text-center">
                    <div class="status-badge-cell status-${item.status}">
                      ${item.status.toUpperCase()}
                    </div>
                  </td>
                  <td>${item.remarks || '-'}</td>
                </tr>
              `
                }
              )
              .join('')}
          </tbody>
        </table>
        
        <!-- Original PO Totals for Reference -->
        <div style="display: flex; justify-content: flex-end; margin-bottom: 20px;">
          <div style="width: 300px; background: #f9f9f9; border: 1px solid #e0e0e0; border-radius: 4px; padding: 10px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 10px;">
              <strong>Original PO Total:</strong>
              <span>₱ ${data.total_amount.toFixed(2)}</span>
            </div>
            <div style="display: flex; justify-content: space-between; border-top: 1px solid #ddd; padding-top: 6px; font-size: 10px; color: #666;">
              <em>Variance Amount:</em>
              <span><em>${hasDiscrepancies ? 'See discrepancies above' : 'None'}</em></span>
            </div>
          </div>
        </div>
        
        <!-- Signature Section -->
        <div class="signature-section">
          <div class="signature-block">
            <div class="signature-line"></div>
            <div class="signature-name">Warehouse Inbound Officer</div>
            <div class="signature-title">Date: ______________</div>
          </div>
          <div class="signature-block">
            <div class="signature-line"></div>
            <div class="signature-name">Warehouse Supervisor</div>
            <div class="signature-title">Date: ______________</div>
          </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
          <p>© ${new Date().getFullYear()} ${company.name}. All rights reserved. | Generated: ${new Date().toLocaleString()}</p>
          <p>This is a computer-generated Goods Receipt document.</p>
        </div>
      </div>
    </body>
    </html>
  `
}

/**
 * Export HTML as PDF for print or download
 */
export const exportToPDF = async (html: string, filename: string) => {
  // Using a simpler approach - print to PDF via browser
  const printWindow = window.open('', '', 'width=800,height=600')
  if (printWindow) {
    printWindow.document.write(html)
    printWindow.document.close()

    // Wait for content to fully load before printing
    printWindow.onload = () => {
      printWindow.print()
      // Don't close the window automatically - let user decide
      // printWindow.close()
    }
  }
}

/**
 * Download HTML as PDF (requires a PDF library server-side)
 */
export const downloadPDF = async (html: string, filename: string) => {
  // This would require AJAX call to backend that converts HTML to PDF
  try {
    const response = await fetch('/api/generate-pdf', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ html, filename })
    })

    if (!response.ok) throw new Error('PDF generation failed')

    const blob = await response.blob()
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = filename
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Failed to download PDF:', error)
    throw error
  }
}

/**
 * Print label/barcode for warehouse receiving
 */
export const generateLabelHtml = (data: {
  po_number: string
  supplier_name: string
  item_count: number
  total_amount: number
  expected_items: Array<{ product_name: string; quantity: number }>
}): string => {
  return `
    <!DOCTYPE html>
    <html>
    <head>
      <title>Warehouse Label - ${data.po_number}</title>
      <style>
        body {
          font-family: Arial, sans-serif;
          margin: 0;
          padding: 10mm;
          background: white;
        }
        
        .label {
          border: 2px solid #000;
          padding: 15mm;
          width: 100mm;
          margin: 0;
          page-break-after: always;
          box-sizing: border-box;
        }
        
        .barcode-area {
          text-align: center;
          margin-bottom: 10mm;
        }
        
        .po-number {
          font-size: 24pt;
          font-weight: bold;
          letter-spacing: 2px;
          margin-bottom: 5mm;
        }
        
        .supplier-name {
          font-size: 14pt;
          font-weight: bold;
          margin-bottom: 5mm;
        }
        
        .info-row {
          font-size: 11pt;
          margin: 3mm 0;
          display: flex;
          justify-content: space-between;
        }
        
        .items-table {
          margin: 10mm 0;
          font-size: 10pt;
          border-collapse: collapse;
          width: 100%;
        }
        
        .items-table th {
          border: 1px solid #000;
          padding: 3mm;
          text-align: left;
          background: #f0f0f0;
        }
        
        .items-table td {
          border: 1px solid #000;
          padding: 3mm;
        }
      </style>
    </head>
    <body>
      <div class="label">
        <div class="barcode-area">
          <div class="po-number">${data.po_number}</div>
          <svg width="100%" height="80px" style="margin: 5mm 0;"></svg>
        </div>
        
        <div class="supplier-name">${data.supplier_name}</div>
        
        <div class="info-row">
          <span><strong>Items:</strong> ${data.item_count}</span>
          <span><strong>Total:</strong> ₱${data.total_amount.toFixed(2)}</span>
        </div>
        
        ${
          data.expected_items.length > 0
            ? `
          <div style="margin-top: 8mm; font-size: 10pt;">
            <strong>Expected Items:</strong>
          </div>
          <table class="items-table">
            <thead>
              <tr>
                <th>Product</th>
                <th style="width: 30%; text-align: right;">Qty</th>
              </tr>
            </thead>
            <tbody>
              ${data.expected_items
                .slice(0, 5)
                .map(
                  (item) => `
                <tr>
                  <td>${item.product_name}</td>
                  <td style="text-align: right;">${item.quantity}</td>
                </tr>
              `
                )
                .join('')}
            </tbody>
          </table>
          ${data.expected_items.length > 5 ? `<p style="font-size: 9pt; margin-top: 5mm;">+ ${data.expected_items.length - 5} more items</p>` : ''}
        `
            : ''
        }
      </div>
    </body>
    </html>
  `
}
