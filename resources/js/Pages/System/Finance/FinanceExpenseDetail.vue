<template>
	<div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
		<div class="rounded-3xl border border-gray-100 bg-linear-to-r from-slate-50 via-white to-blue-50 p-5 shadow-sm">
			<div class="flex flex-wrap items-center justify-between gap-3">
				<div class="flex items-center gap-3">
					<Button icon="pi pi-arrow-left" rounded text @click="goBack" />
					<div>
						<h1 class="text-xl font-semibold tracking-tight text-gray-900">Expense Detail</h1>
						<p class="mt-0.5 text-sm text-gray-500">Full expense record with linked invoice/receipt details.</p>
					</div>
				</div>
				<Button icon="pi pi-print" label="Print" :disabled="!expense" @click="printDocument" />
			</div>
		</div>

		<Card class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm">
			<template #content>
				<div v-if="loading" class="p-6 text-sm text-gray-500">Loading expense details...</div>
				<div v-else-if="!expense" class="p-6 text-sm text-gray-500">Expense not found.</div>
				<div v-else class="grid grid-cols-1 gap-4 p-6 md:grid-cols-3">
					<div>
						<p class="text-xs uppercase tracking-wide text-gray-500">Category</p>
						<p class="mt-1 text-sm font-semibold text-gray-900">{{ expense.category || '-' }}</p>
					</div>
					<div>
						<p class="text-xs uppercase tracking-wide text-gray-500">Status</p>
						<Tag :value="formatStatus(expense.status)" :severity="statusSeverity(expense.status)" />
					</div>
					<div>
						<p class="text-xs uppercase tracking-wide text-gray-500">Amount</p>
						<p class="mt-1 text-base font-semibold text-green-700">₱ {{ formatMoney(expense.amount) }}</p>
					</div>
					<div>
						<p class="text-xs uppercase tracking-wide text-gray-500">Expense Date</p>
						<p class="mt-1 text-sm text-gray-700">{{ formatDate(expense.expense_date) }}</p>
					</div>
					<div>
						<p class="text-xs uppercase tracking-wide text-gray-500">Payment Method</p>
						<p class="mt-1 text-sm text-gray-700">{{ expense.payment_method || '-' }}</p>
					</div>
					<div>
						<p class="text-xs uppercase tracking-wide text-gray-500">Payment Date</p>
						<p class="mt-1 text-sm text-gray-700">{{ formatDate(expense.payment_date) }}</p>
					</div>
					<div>
						<p class="text-xs uppercase tracking-wide text-gray-500">Reference Type</p>
						<p class="mt-1 text-sm text-gray-700">{{ expense.reference_type || '-' }}</p>
					</div>
					<div>
						<p class="text-xs uppercase tracking-wide text-gray-500">Reference #</p>
						<p class="mt-1 text-sm text-gray-700">{{ expense.reference_number || '-' }}</p>
					</div>
					<div>
						<p class="text-xs uppercase tracking-wide text-gray-500">Currency</p>
						<p class="mt-1 text-sm text-gray-700">{{ expense.currency || 'PHP' }}</p>
					</div>
					<div class="md:col-span-3">
						<p class="text-xs uppercase tracking-wide text-gray-500">Description</p>
						<p class="mt-1 text-sm text-gray-700">{{ expense.description || '-' }}</p>
					</div>
					<div class="md:col-span-3" v-if="expense.notes">
						<p class="text-xs uppercase tracking-wide text-gray-500">Notes</p>
						<p class="mt-1 whitespace-pre-wrap text-sm text-gray-700">{{ expense.notes }}</p>
					</div>
				</div>
			</template>
		</Card>

		<Card class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm" v-if="invoiceDetail">
			<template #header>
				<div class="px-6 pt-6">
					<h2 class="text-lg font-semibold text-gray-900">Linked Invoice/Receipt Detail</h2>
				</div>
			</template>
			<template #content>
				<div class="grid grid-cols-1 gap-4 p-6 pt-2 md:grid-cols-3">
					<div>
						<p class="text-xs uppercase tracking-wide text-gray-500">Invoice #</p>
						<p class="mt-1 text-sm font-semibold text-gray-900">{{ invoiceDetail.invoice_number || '-' }}</p>
					</div>
					<div>
						<p class="text-xs uppercase tracking-wide text-gray-500">Supplier</p>
						<p class="mt-1 text-sm text-gray-700">{{ invoiceDetail.supplier?.supplier_name || '-' }}</p>
					</div>
					<div>
						<p class="text-xs uppercase tracking-wide text-gray-500">PO #</p>
						<p class="mt-1 text-sm text-gray-700">{{ invoiceDetail.purchase_order?.po_number || '-' }}</p>
					</div>
					<div>
						<p class="text-xs uppercase tracking-wide text-gray-500">Invoice Date</p>
						<p class="mt-1 text-sm text-gray-700">{{ formatDate(invoiceDetail.invoice_date) }}</p>
					</div>
					<div>
						<p class="text-xs uppercase tracking-wide text-gray-500">Due Date</p>
						<p class="mt-1 text-sm text-gray-700">{{ formatDate(invoiceDetail.due_date) }}</p>
					</div>
					<div>
						<p class="text-xs uppercase tracking-wide text-gray-500">Invoice Status</p>
						<Tag :value="formatStatus(invoiceDetail.status)" :severity="statusSeverity(invoiceDetail.status)" />
					</div>
				</div>

				<div class="p-6 pt-0">
					<DataTable :value="invoiceDetail.items || []" stripedRows responsiveLayout="scroll" class="p-datatable-sm">
						<Column field="product.product_name" header="Item" style="min-width: 220px">
							<template #body="{ data }">
								<div>
									<p class="text-sm font-medium text-gray-900">{{ data.product?.product_name || 'Unknown Product' }}</p>
									<p class="text-xs text-gray-500">SKU: {{ data.product?.sku || '-' }}</p>
								</div>
							</template>
						</Column>
						<Column field="quantity_invoiced" header="Qty" style="width: 90px" />
						<Column field="unit_price" header="Unit Price" style="width: 140px">
							<template #body="{ data }">₱ {{ formatMoney(data.unit_price) }}</template>
						</Column>
						<Column field="line_amount" header="Line Total" style="width: 160px">
							<template #body="{ data }">
								<span class="font-semibold text-green-700">₱ {{ formatMoney(data.line_amount || 0) }}</span>
							</template>
						</Column>
					</DataTable>

					<div class="mt-4 grid grid-cols-1 gap-2 text-sm md:ml-auto md:max-w-sm">
						<div class="flex items-center justify-between"><span class="text-gray-500">Subtotal</span><span>₱ {{ formatMoney(invoiceDetail.subtotal || invoiceDetail.invoice_amount || 0) }}</span></div>
						<div class="flex items-center justify-between"><span class="text-gray-500">Shipping Cost</span><span>₱ {{ formatMoney(invoiceDetail.shipping_cost || 0) }}</span></div>
						<div class="flex items-center justify-between"><span class="text-gray-500">Tax</span><span>₱ {{ formatMoney(invoiceDetail.tax_amount || 0) }}</span></div>
						<div class="flex items-center justify-between border-t border-gray-200 pt-2 text-base font-semibold"><span>Total</span><span>₱ {{ formatMoney(invoiceDetail.net_amount || invoiceDetail.invoice_amount || 0) }}</span></div>
					</div>
				</div>
			</template>
		</Card>

		<Card class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm" v-else-if="expense && expense.reference_type">
			<template #content>
				<div class="p-6 text-sm text-gray-500">
					No expanded invoice/receipt detail is available for reference type: {{ expense.reference_type }}.
				</div>
			</template>
		</Card>
	</div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Tag from 'primevue/tag'
import financeService from '../../../services/finance.service'

const route = useRoute()
const router = useRouter()

const loading = ref(false)
const expense = ref<any>(null)
const invoiceDetail = ref<any>(null)

const formatMoney = (value: number | string) => {
	const amount = typeof value === 'string' ? parseFloat(value) : value || 0
	return new Intl.NumberFormat('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount)
}

const formatDate = (value: string) => {
	if (!value) return '-'
	return new Date(value).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
}

const formatStatus = (status: string) => {
	if (!status) return '-'
	return status.split('_').map((word) => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')
}

const statusSeverity = (status: string) => {
	if (status === 'approved' || status === 'matched' || status === 'paid') return 'success'
	if (status === 'pending' || status === 'pending_approval') return 'warn'
	if (status === 'rejected' || status === 'exception' || status === 'cancelled') return 'danger'
	return 'secondary'
}

const goBack = () => router.push({ name: 'finance.expenses' })

const loadDetail = async () => {
	loading.value = true
	try {
		const id = Number(route.params.id)
		const expenseRes = await financeService.getExpenseDetail(id)
		expense.value = expenseRes.data || null

		if (expense.value?.reference_type === 'invoice' && expense.value?.reference_id) {
			const invoiceRes = await financeService.getInvoice(Number(expense.value.reference_id))
			invoiceDetail.value = invoiceRes.data || null
		}
	} finally {
		loading.value = false
	}
}

const printDocument = () => {
	if (!expense.value) return

	const e = expense.value
	const inv = invoiceDetail.value
	const rows = (inv?.items || [])
		.map((item: any) => `
			<tr>
				<td style="padding:10px 12px;border-bottom:1px solid #edf0f4;">${item.product?.product_name || 'Unknown Product'}</td>
				<td style="padding:10px 12px;border-bottom:1px solid #edf0f4;text-align:right;">${item.quantity_invoiced || item.quantity || 0}</td>
				<td style="padding:10px 12px;border-bottom:1px solid #edf0f4;text-align:right;">PHP ${formatMoney(item.unit_price || 0)}</td>
				<td style="padding:10px 12px;border-bottom:1px solid #edf0f4;text-align:right;font-weight:600;">PHP ${formatMoney(item.line_amount || 0)}</td>
			</tr>
		`)
		.join('')

	const html = `
		<html>
			<head>
				<title>Expense ${e.reference_number || e.id}</title>
				<style>
					body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; margin: 0; background: #f5f7fb; color: #101828; }
					.page { max-width: 920px; margin: 24px auto; background: #ffffff; border: 1px solid #e7ebf1; border-radius: 16px; padding: 24px; }
					.top { display:flex; justify-content:space-between; align-items:start; gap: 12px; margin-bottom:18px; }
					.title { font-size:24px; font-weight:700; margin:0; }
					.subtitle { margin:4px 0 0 0; color:#667085; font-size:12px; }
					.pill { background:#eff4ff; border:1px solid #dbe8ff; color:#1d4ed8; font-size:11px; font-weight:600; border-radius:999px; padding:6px 10px; }
					.meta-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:14px; }
					.meta-card { border:1px solid #edf0f4; background:#fafbfd; border-radius:10px; padding:10px; }
					.meta-label { font-size:10px; text-transform:uppercase; letter-spacing:0.08em; color:#667085; margin-bottom:4px; }
					.meta-value { font-size:12px; font-weight:600; color:#1f2937; }
					.section-title { margin:18px 0 10px 0; font-size:13px; font-weight:700; color:#111827; }
					table { width:100%; border-collapse:collapse; font-size:12px; border:1px solid #edf0f4; border-radius:12px; overflow:hidden; }
					thead th { text-align:left; padding:10px 12px; border-bottom:1px solid #e5e7eb; background:#f8fafc; color:#475467; font-size:10px; text-transform:uppercase; letter-spacing:0.08em; }
					.totals { margin-top: 14px; margin-left:auto; width: 340px; border:1px solid #edf0f4; border-radius:12px; padding:12px 14px; background:#fcfdff; }
					.row { display:flex; justify-content:space-between; font-size:12px; padding:3px 0; }
					.grand { margin-top:8px; padding-top:8px; border-top:1px solid #dfe4ea; font-size:14px; font-weight:700; }
					.footer { margin-top:20px; font-size:10px; color:#98a2b3; text-align:center; }
				</style>
			</head>
			<body>
				<div class="page">
					<div class="top">
						<div>
							<h2 class="title">Expense Document</h2>
							<p class="subtitle">Reference: ${e.reference_number || '-'} | Type: ${e.reference_type || '-'}</p>
						</div>
						<div class="pill">${formatStatus(e.status || '-')}</div>
					</div>

					<div class="meta-grid">
						<div class="meta-card"><div class="meta-label">Category</div><div class="meta-value">${e.category || '-'}</div></div>
						<div class="meta-card"><div class="meta-label">Expense Date</div><div class="meta-value">${formatDate(e.expense_date)}</div></div>
						<div class="meta-card"><div class="meta-label">Amount</div><div class="meta-value">PHP ${formatMoney(e.amount || 0)}</div></div>
					</div>

					<div class="meta-card" style="margin-bottom: 12px;">
						<div class="meta-label">Description</div>
						<div class="meta-value">${e.description || '-'}</div>
					</div>

					${inv ? `
						<h3 class="section-title">Linked Invoice Detail</h3>
						<div class="meta-grid">
							<div class="meta-card"><div class="meta-label">Invoice #</div><div class="meta-value">${inv.invoice_number || '-'}</div></div>
							<div class="meta-card"><div class="meta-label">Supplier</div><div class="meta-value">${inv.supplier?.supplier_name || '-'}</div></div>
							<div class="meta-card"><div class="meta-label">PO #</div><div class="meta-value">${inv.purchase_order?.po_number || '-'}</div></div>
						</div>

						<table>
							<thead>
								<tr>
									<th>Item</th>
									<th style="text-align:right;">Qty</th>
									<th style="text-align:right;">Unit Price</th>
									<th style="text-align:right;">Line Total</th>
								</tr>
							</thead>
							<tbody>${rows}</tbody>
						</table>

						<div class="totals">
							<div class="row"><span>Subtotal</span><strong>PHP ${formatMoney(inv.subtotal || inv.invoice_amount || 0)}</strong></div>
							<div class="row"><span>Shipping Cost</span><strong>PHP ${formatMoney(inv.shipping_cost || 0)}</strong></div>
							<div class="row"><span>Tax</span><strong>PHP ${formatMoney(inv.tax_amount || 0)}</strong></div>
							<div class="row grand"><span>Invoice Total</span><strong>PHP ${formatMoney(inv.net_amount || inv.invoice_amount || 0)}</strong></div>
						</div>
					` : ''}

					<div class="footer">Generated on ${new Date().toLocaleDateString('en-PH')}</div>
				</div>
			</body>
		</html>
	`

	const printWindow = window.open('', '_blank', 'width=900,height=720')
	if (!printWindow) return
	printWindow.document.open()
	printWindow.document.write(html)
	printWindow.document.close()
	printWindow.focus()
	setTimeout(() => {
		printWindow.print()
	}, 250)
}

onMounted(loadDetail)
</script>
