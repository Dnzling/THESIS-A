<template>
	<div class="mx-auto max-w-7xl space-y-5 px-4 py-5 sm:px-6 lg:px-8">
		<ConfirmDialog />
		<header class="flex flex-wrap items-center justify-between gap-3">
			<div class="flex min-w-0 items-center gap-3">
				<Button icon="pi pi-chevron-left" rounded outlined aria-label="Go back" v-tooltip.bottom="'Go back'" class="!h-10 !w-10 shrink-0 !border-slate-200 !bg-white !text-slate-600 shadow-sm" @click="goBack" />
				<div class="min-w-0">
					<p class="text-xs font-medium uppercase tracking-wide text-orange-700">Finance Expense</p>
					<h1 class="truncate font-mono text-lg font-semibold tracking-tight text-slate-900">{{ expenseDisplayReference || (expense ? 'Expense #' + expense.id : 'Expense Details') }}</h1>
					<p class="mt-0.5 text-sm text-slate-500">{{ expense?.branch?.name || 'Store-wide' }}<span v-if="expense?.category"> · {{ formatLabel(expense.category) }}</span></p>
				</div>
			</div>
			<div class="flex items-center gap-2">
				<Badge v-if="expense" :value="formatStatus(expense.status)" :severity="statusSeverity(expense.status)" />
				<Button icon="pi pi-print" label="Print" severity="secondary" outlined size="small" :disabled="!expense" @click="printDocument" />
			</div>
		</header>

		<Card class="overflow-hidden rounded-2xl border border-slate-200 shadow-sm">
			<template #content>
				<div v-if="loading" class="space-y-3 p-6"><div class="h-6 w-48 animate-pulse rounded bg-slate-100" /><div class="h-20 animate-pulse rounded-xl bg-slate-50" /><div class="h-28 animate-pulse rounded-xl bg-slate-50" /></div>
				<div v-else-if="!expense" class="p-8 text-center text-sm text-slate-500">Expense not found.</div>
				<div v-else class="expense-meta-grid grid grid-cols-1 gap-3 p-5 sm:p-6 md:grid-cols-3">
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
						<p class="mt-1 text-sm text-gray-700">{{ expense.payment_method ? formatLabel(expense.payment_method) : '-' }}</p>
					</div>
					<div>
						<p class="text-xs uppercase tracking-wide text-gray-500">Payment Date</p>
						<p class="mt-1 text-sm text-gray-700">{{ formatDate(expense.payment_date) }}</p>
					</div>
					<div>
						<p class="text-xs uppercase tracking-wide text-gray-500">Reference Type</p>
						<p class="mt-1 text-sm text-gray-700">{{ formatLabel(expense.reference_type) }}</p>
					</div>
					<div>
						<p class="text-xs uppercase tracking-wide text-gray-500">Payment Reference</p>
						<p class="mt-1 break-all font-mono text-sm text-gray-700">{{ expense.payment_reference || '-' }}</p>
					</div>
					<div>
						<p class="text-xs uppercase tracking-wide text-gray-500">Currency</p>
						<p class="mt-1 text-sm text-gray-700">{{ expense.currency || 'PHP' }}</p>
					</div>
					<div>
						<p class="text-xs uppercase tracking-wide text-gray-500">Requested By</p>
						<p class="mt-1 text-sm text-gray-700">{{ person(expense.requested_by) }}</p>
					</div>
					<div v-if="expense.approved_by">
						<p class="text-xs uppercase tracking-wide text-gray-500">Approved By</p>
						<p class="mt-1 text-sm text-gray-700">{{ person(expense.approved_by) }}</p>
					</div>
					<div v-if="expense.paid_by">
						<p class="text-xs uppercase tracking-wide text-gray-500">Recorded Paid By</p>
						<p class="mt-1 text-sm text-gray-700">{{ person(expense.paid_by) }}</p>
					</div>
					<div class="md:col-span-3">
						<p class="text-xs uppercase tracking-wide text-gray-500">Description</p>
						<p class="mt-1 text-sm text-gray-700">{{ expense.description || '-' }}</p>
					</div>
					<div class="md:col-span-3" v-if="expense.notes">
						<p class="text-xs uppercase tracking-wide text-gray-500">Notes</p>
						<p class="mt-1 whitespace-pre-wrap text-sm text-gray-700">{{ expense.notes }}</p>
					</div>
					<div v-if="expense.branch_expense_request" class="md:col-span-3 rounded-xl border border-blue-100 bg-blue-50/50 p-4">
						<p class="text-xs font-medium uppercase tracking-wide text-blue-800">Originating branch request</p>
						<p class="mt-1 text-sm font-medium text-gray-900">{{ expense.branch_expense_request.title }} <span v-if="expense.branch_expense_request.reference_number" class="font-mono text-xs text-slate-500">· {{ expense.branch_expense_request.reference_number }}</span></p>
						<div class="mt-3 grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
							<div><p class="text-xs text-slate-500">Category</p><p class="mt-1 font-medium text-slate-800">{{ formatLabel(expense.branch_expense_request.category) }}</p></div>
							<div><p class="text-xs text-slate-500">Approved estimate</p><p class="mt-1 font-medium text-slate-800">PHP {{ formatMoney(expense.branch_expense_request.estimated_amount) }}</p></div>
							<div><p class="text-xs text-slate-500">Actual recorded</p><p class="mt-1 font-medium text-slate-800">PHP {{ formatMoney(expense.branch_expense_request.actual_amount ?? expense.amount) }}</p></div>
							<div><p class="text-xs text-slate-500">Difference</p><p class="mt-1 font-medium text-slate-800">{{ Number(expense.branch_expense_request.actual_amount ?? expense.amount) - Number(expense.branch_expense_request.estimated_amount) > 0 ? '+' : '' }}PHP {{ formatMoney(Number(expense.branch_expense_request.actual_amount ?? expense.amount) - Number(expense.branch_expense_request.estimated_amount)) }}</p></div>
						</div>
						<a v-if="expense.branch_expense_request.receipt_url && !expense.branch_expense_request.receipt_mime?.startsWith('image/')" :href="expense.branch_expense_request.receipt_url" target="_blank" rel="noopener" class="mt-2 inline-flex text-sm text-orange-700 hover:underline">Open receipt</a>
						<button v-if="expense.branch_expense_request.receipt_url && expense.branch_expense_request.receipt_mime?.startsWith('image/')" type="button" class="group relative mt-3 block h-28 w-40 overflow-hidden rounded-lg border border-slate-200 bg-slate-50" aria-label="View branch expense receipt" @click="openReceiptPreview">
							<img v-if="branchReceiptImage" :src="branchReceiptImage" alt="Branch expense receipt" class="h-full w-full object-cover transition group-hover:scale-[1.03]" />
							<span v-else class="absolute inset-0 flex items-center justify-center text-slate-400"><i class="pi pi-image text-xl" /></span>
							<span class="absolute inset-0 flex items-center justify-center bg-slate-950/0 text-white opacity-0 transition group-hover:bg-slate-950/35 group-hover:opacity-100"><i class="pi pi-eye text-xl" /></span>
						</button>
					</div>
				</div>
			</template>
		</Card>

		<Card v-if="expense?.status === 'pending_approval' && canApprove" class="overflow-hidden rounded-2xl border border-orange-200 shadow-sm">
			<template #title><span class="text-base">Expense Approval</span></template>
			<template #content>
				<div class="flex flex-wrap items-center justify-between gap-3">
					<p class="text-sm text-slate-600">Review the expense details and supporting receipt before deciding.</p>
					<div class="flex justify-end gap-2">
						<Button label="Reject" severity="secondary" outlined :disabled="acting" @click="showReject = true" />
						<Button label="Approve Expense" icon="pi pi-check" severity="warn" :loading="acting" @click="confirmApprove" />
					</div>
				</div>
			</template>
		</Card>

		<Dialog v-model:visible="showReject" modal header="Reject Expense" :style="{ width: 'min(92vw, 30rem)' }">
			<label class="mb-1 block text-sm font-medium text-slate-700">Reason for rejection <span class="text-red-600">*</span></label>
			<Textarea v-model="rejectNotes" rows="4" autoResize fluid placeholder="Explain why this expense is being rejected." />
			<template #footer>
				<Button label="Keep Reviewing" severity="secondary" outlined :disabled="acting" @click="showReject = false" />
				<Button label="Reject Expense" severity="warn" :loading="acting" :disabled="!rejectNotes.trim()" @click="rejectExpense" />
			</template>
		</Dialog>

		<Dialog v-model:visible="receiptPreviewVisible" modal header="Branch Request Receipt" :style="{ width: 'min(92vw, 56rem)' }" :breakpoints="{ '640px': '96vw' }">
			<img v-if="branchReceiptImage" :src="branchReceiptImage" alt="Branch expense receipt" class="mx-auto max-h-[75vh] max-w-full rounded-lg object-contain" />
		</Dialog>

		<Card v-if="benefitDetail" class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm">
			<template #title>Employee benefit request</template>
			<template #content>
				<div class="grid gap-4 p-6 text-sm md:grid-cols-3">
					<div><p class="text-gray-500">Employee</p><p class="font-medium">{{ benefitDetail.employee?.user?.fname }} {{ benefitDetail.employee?.user?.lname }}</p></div>
					<div><p class="text-gray-500">Benefit</p><p class="font-medium">{{ benefitDetail.deduction_type?.name }}</p></div>
					<div><p class="text-gray-500">Provider</p><p class="font-medium">{{ benefitDetail.provider }}</p></div>
					<div><p class="text-gray-500">Service date</p><p class="font-medium">{{ formatDate(benefitDetail.service_date) }}</p></div>
					<div><p class="text-gray-500">Benefit used</p><p class="font-medium">₱ {{ formatMoney(benefitDetail.used_amount) }}</p></div>
					<div><p class="text-gray-500">Company payment</p><p class="font-medium">₱ {{ formatMoney(benefitDetail.company_payment_amount) }}</p></div>
					<div class="md:col-span-3"><a v-if="benefitDetail.receipt_path" :href="`/storage/${benefitDetail.receipt_path}`" target="_blank" rel="noopener" class="text-blue-600 underline">View final receipt</a></div>
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

		<Card class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm" v-else-if="expense && expense.reference_type && !['employee_benefit_request', 'branch_expense_request'].includes(expense.reference_type)">
			<template #content>
				<div class="p-6 text-sm text-gray-500">
					No expanded invoice/receipt detail is available for reference type: {{ expense.reference_type }}.
				</div>
			</template>
		</Card>
	</div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Button from 'primevue/button'
import Badge from 'primevue/badge'
import Card from 'primevue/card'
import ConfirmDialog from 'primevue/confirmdialog'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import financeService from '../../../services/finance.service'
import axios from '@/axios'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const confirm = useConfirm()
const toast = useToast()
const auth = useAuthStore()

const loading = ref(false)
const acting = ref(false)
const showReject = ref(false)
const rejectNotes = ref('')
const expense = ref<any>(null)
const invoiceDetail = ref<any>(null)
const benefitDetail = ref<any>(null)
const branchReceiptImage = ref('')
const receiptPreviewVisible = ref(false)
const expenseDisplayReference = computed(() => expense.value?.branch_expense_request?.reference_number || expense.value?.reference_number || '')
const canApprove = computed(() => auth.hasPermission('finance.expenses.approve'))

const formatMoney = (value: number | string) => {
	const amount = typeof value === 'string' ? parseFloat(value) : value || 0
	return new Intl.NumberFormat('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount)
}

const formatDate = (value: string) => {
	if (!value) return '-'
	return new Date(value).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
}

const formatLabel = (value: string | null) => value ? value.split('_').map((word) => word.charAt(0).toUpperCase() + word.slice(1)).join(' ') : '-'

const person = (user: any) => user ? [user.fname, user.lname].filter(Boolean).join(' ') || user.name || '-' : '-'

const formatStatus = (status: string) => {
	if (!status) return '-'
	return status.split('_').map((word) => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')
}

const statusSeverity = (status: string) => {
	if (status === 'approved' || status === 'finance_approved' || status === 'matched' || status === 'paid') return 'success'
	if (status === 'pending' || status === 'pending_approval') return 'warn'
	if (status === 'submitted_to_finance') return 'info'
	if (status === 'rejected' || status === 'finance_rejected' || status === 'exception') return 'danger'
	if (status === 'cancelled') return 'secondary'
	return 'secondary'
}

const goBack = () => window.history.length > 1 ? router.back() : router.push({ name: 'finance.expenses' })

const confirmApprove = () => {
	if (!expense.value) return
	confirm.require({
		header: 'Approve this expense?',
		message: `Approve expense ${expenseDisplayReference.value}?`,
		icon: 'pi pi-check-circle',
		acceptLabel: 'Approve Expense',
		rejectLabel: 'Keep Reviewing',
		acceptProps: { severity: 'warn' },
		rejectProps: { severity: 'secondary', outlined: true },
		accept: approveExpense,
	})
}

const approveExpense = async () => {
	if (!expense.value || acting.value) return
	acting.value = true
	try {
		await financeService.approveExpense(Number(expense.value.id))
		toast.add({ severity: 'success', summary: 'Expense approved', life: 4000 })
		await loadDetail()
	} catch (error: any) {
		toast.add({ severity: 'error', summary: 'Unable to approve expense', detail: error?.response?.data?.message || 'Please try again.', life: 5000 })
	} finally {
		acting.value = false
	}
}

const rejectExpense = async () => {
	if (!expense.value || !rejectNotes.value.trim() || acting.value) return
	acting.value = true
	try {
		await financeService.rejectExpense(Number(expense.value.id), { notes: rejectNotes.value.trim() })
		showReject.value = false
		rejectNotes.value = ''
		toast.add({ severity: 'success', summary: 'Expense rejected', life: 4000 })
		await loadDetail()
	} catch (error: any) {
		toast.add({ severity: 'error', summary: 'Unable to reject expense', detail: error?.response?.data?.message || 'Please try again.', life: 5000 })
	} finally {
		acting.value = false
	}
}

const loadBranchReceiptImage = async () => {
	const url = expense.value?.branch_expense_request?.receipt_url
	if (!url || !expense.value?.branch_expense_request?.receipt_mime?.startsWith('image/')) return
	try {
		const parsed = new URL(url, window.location.origin)
		const response = await axios.get(parsed.pathname + parsed.search, { responseType: 'blob' })
		branchReceiptImage.value = URL.createObjectURL(response.data)
	} catch {
		branchReceiptImage.value = ''
	}
}

const openReceiptPreview = async () => {
	if (!branchReceiptImage.value) await loadBranchReceiptImage()
	if (branchReceiptImage.value) receiptPreviewVisible.value = true
}

const loadDetail = async () => {
	loading.value = true
	try {
		const id = Number(route.params.id)
		const expenseRes = await financeService.getExpenseDetail(id)
		expense.value = expenseRes.data || null
		await loadBranchReceiptImage()

		if (expense.value?.reference_type === 'invoice' && expense.value?.reference_id) {
			const invoiceRes = await financeService.getInvoice(Number(expense.value.reference_id))
			invoiceDetail.value = invoiceRes.data || null
		}
		if (expense.value?.reference_type === 'employee_benefit_request' && expense.value?.reference_id) {
			const benefitRes = await financeService.getBenefitRequest(Number(expense.value.reference_id))
			benefitDetail.value = benefitRes.data || null
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
				<title>Expense ${e.branch_expense_request?.reference_number || e.reference_number || e.id}</title>
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
							<p class="subtitle">Expense reference: ${e.branch_expense_request?.reference_number || e.reference_number || '-'}${e.payment_reference ? ` | Payment reference: ${e.payment_reference}` : ''}</p>
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

onBeforeUnmount(() => {
	if (branchReceiptImage.value) URL.revokeObjectURL(branchReceiptImage.value)
})
onMounted(loadDetail)
</script>

<style scoped>
.expense-meta-grid > div {
	border: 1px solid #e2e8f0;
	border-radius: 0.75rem;
	padding: 0.75rem;
}

.expense-meta-grid > div:first-child {
	border-color: #fed7aa;
	background: #fff7ed;
}
</style>
