import{g as E,C as R,o as c,k as v,d as e,a as d,b as r,r as h,w as p,E as i,a1 as k,G as P,a0 as b,c as $,ad as T,ac as g,f as N,v as _,a2 as L,aj as F}from"./app-DAedBCYh.js";import{f as D}from"./finance.service-Bzc668xI.js";const U={class:"mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8"},B={class:"rounded-3xl border border-gray-100 bg-linear-to-r from-slate-50 via-white to-blue-50 p-5 shadow-sm"},A={class:"flex flex-wrap items-center justify-between gap-3"},M={class:"flex items-center gap-3"},V={key:0,class:"p-6 text-sm text-gray-500"},q={key:1,class:"p-6 text-sm text-gray-500"},G={key:2,class:"grid grid-cols-1 gap-4 p-6 md:grid-cols-3"},O={class:"mt-1 text-sm font-semibold text-gray-900"},Q={class:"mt-1 text-base font-semibold text-green-700"},K={class:"mt-1 text-sm text-gray-700"},W={class:"mt-1 text-sm text-gray-700"},J={class:"mt-1 text-sm text-gray-700"},X={class:"mt-1 text-sm text-gray-700"},Y={class:"mt-1 text-sm text-gray-700"},Z={class:"mt-1 text-sm text-gray-700"},ee={class:"md:col-span-3"},te={class:"mt-1 text-sm text-gray-700"},se={key:0,class:"md:col-span-3"},ae={class:"mt-1 whitespace-pre-wrap text-sm text-gray-700"},ie={class:"grid grid-cols-1 gap-4 p-6 pt-2 md:grid-cols-3"},ne={class:"mt-1 text-sm font-semibold text-gray-900"},oe={class:"mt-1 text-sm text-gray-700"},re={class:"mt-1 text-sm text-gray-700"},le={class:"mt-1 text-sm text-gray-700"},de={class:"mt-1 text-sm text-gray-700"},pe={class:"p-6 pt-0"},ce={class:"text-sm font-medium text-gray-900"},ue={class:"text-xs text-gray-500"},xe={class:"font-semibold text-green-700"},me={class:"mt-4 grid grid-cols-1 gap-2 text-sm md:ml-auto md:max-w-sm"},ve={class:"flex items-center justify-between"},ge={class:"flex items-center justify-between"},fe={class:"flex items-center justify-between"},ye={class:"flex items-center justify-between border-t border-gray-200 pt-2 text-base font-semibold"},be={class:"p-6 text-sm text-gray-500"},ke=E({__name:"FinanceExpenseDetail",setup(_e){const S=L(),C=F(),f=_(!1),a=_(null),n=_(null),o=s=>{const t=typeof s=="string"?parseFloat(s):s||0;return new Intl.NumberFormat("en-PH",{minimumFractionDigits:2,maximumFractionDigits:2}).format(t)},x=s=>s?new Date(s).toLocaleDateString("en-PH",{month:"short",day:"numeric",year:"numeric"}):"-",y=s=>s?s.split("_").map(t=>t.charAt(0).toUpperCase()+t.slice(1)).join(" "):"-",w=s=>s==="approved"||s==="matched"||s==="paid"?"success":s==="pending"||s==="pending_approval"?"warn":s==="rejected"||s==="exception"||s==="cancelled"?"danger":"secondary",I=()=>C.push({name:"finance.expenses"}),j=async()=>{f.value=!0;try{const s=Number(S.params.id),t=await D.getExpenseDetail(s);if(a.value=t.data||null,a.value?.reference_type==="invoice"&&a.value?.reference_id){const l=await D.getInvoice(Number(a.value.reference_id));n.value=l.data||null}}finally{f.value=!1}},z=()=>{if(!a.value)return;const s=a.value,t=n.value,l=(t?.items||[]).map(m=>`
			<tr>
				<td style="padding:10px 12px;border-bottom:1px solid #edf0f4;">${m.product?.product_name||"Unknown Product"}</td>
				<td style="padding:10px 12px;border-bottom:1px solid #edf0f4;text-align:right;">${m.quantity_invoiced||m.quantity||0}</td>
				<td style="padding:10px 12px;border-bottom:1px solid #edf0f4;text-align:right;">PHP ${o(m.unit_price||0)}</td>
				<td style="padding:10px 12px;border-bottom:1px solid #edf0f4;text-align:right;font-weight:600;">PHP ${o(m.line_amount||0)}</td>
			</tr>
		`).join(""),H=`
		<html>
			<head>
				<title>Expense ${s.reference_number||s.id}</title>
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
							<p class="subtitle">Reference: ${s.reference_number||"-"} | Type: ${s.reference_type||"-"}</p>
						</div>
						<div class="pill">${y(s.status||"-")}</div>
					</div>

					<div class="meta-grid">
						<div class="meta-card"><div class="meta-label">Category</div><div class="meta-value">${s.category||"-"}</div></div>
						<div class="meta-card"><div class="meta-label">Expense Date</div><div class="meta-value">${x(s.expense_date)}</div></div>
						<div class="meta-card"><div class="meta-label">Amount</div><div class="meta-value">PHP ${o(s.amount||0)}</div></div>
					</div>

					<div class="meta-card" style="margin-bottom: 12px;">
						<div class="meta-label">Description</div>
						<div class="meta-value">${s.description||"-"}</div>
					</div>

					${t?`
						<h3 class="section-title">Linked Invoice Detail</h3>
						<div class="meta-grid">
							<div class="meta-card"><div class="meta-label">Invoice #</div><div class="meta-value">${t.invoice_number||"-"}</div></div>
							<div class="meta-card"><div class="meta-label">Supplier</div><div class="meta-value">${t.supplier?.supplier_name||"-"}</div></div>
							<div class="meta-card"><div class="meta-label">PO #</div><div class="meta-value">${t.purchase_order?.po_number||"-"}</div></div>
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
							<tbody>${l}</tbody>
						</table>

						<div class="totals">
							<div class="row"><span>Subtotal</span><strong>PHP ${o(t.subtotal||t.invoice_amount||0)}</strong></div>
							<div class="row"><span>Shipping Cost</span><strong>PHP ${o(t.shipping_cost||0)}</strong></div>
							<div class="row"><span>Tax</span><strong>PHP ${o(t.tax_amount||0)}</strong></div>
							<div class="row grand"><span>Invoice Total</span><strong>PHP ${o(t.net_amount||t.invoice_amount||0)}</strong></div>
						</div>
					`:""}

					<div class="footer">Generated on ${new Date().toLocaleDateString("en-PH")}</div>
				</div>
			</body>
		</html>
	`,u=window.open("","_blank","width=900,height=720");u&&(u.document.open(),u.document.write(H),u.document.close(),u.focus(),setTimeout(()=>{u.print()},250))};return R(j),(s,t)=>(c(),v("div",U,[e("div",B,[e("div",A,[e("div",M,[d(r(h),{icon:"pi pi-arrow-left",rounded:"",text:"",onClick:I}),t[0]||(t[0]=e("div",null,[e("h1",{class:"text-xl font-semibold tracking-tight text-gray-900"},"Expense Detail"),e("p",{class:"mt-0.5 text-sm text-gray-500"},"Full expense record with linked invoice/receipt details.")],-1))]),d(r(h),{icon:"pi pi-print",label:"Print",disabled:!a.value,onClick:z},null,8,["disabled"])])]),d(r(b),{class:"overflow-hidden rounded-2xl border border-gray-100 shadow-sm"},{content:p(()=>[f.value?(c(),v("div",V,"Loading expense details...")):a.value?(c(),v("div",G,[e("div",null,[t[1]||(t[1]=e("p",{class:"text-xs uppercase tracking-wide text-gray-500"},"Category",-1)),e("p",O,i(a.value.category||"-"),1)]),e("div",null,[t[2]||(t[2]=e("p",{class:"text-xs uppercase tracking-wide text-gray-500"},"Status",-1)),d(r(k),{value:y(a.value.status),severity:w(a.value.status)},null,8,["value","severity"])]),e("div",null,[t[3]||(t[3]=e("p",{class:"text-xs uppercase tracking-wide text-gray-500"},"Amount",-1)),e("p",Q,"₱ "+i(o(a.value.amount)),1)]),e("div",null,[t[4]||(t[4]=e("p",{class:"text-xs uppercase tracking-wide text-gray-500"},"Expense Date",-1)),e("p",K,i(x(a.value.expense_date)),1)]),e("div",null,[t[5]||(t[5]=e("p",{class:"text-xs uppercase tracking-wide text-gray-500"},"Payment Method",-1)),e("p",W,i(a.value.payment_method||"-"),1)]),e("div",null,[t[6]||(t[6]=e("p",{class:"text-xs uppercase tracking-wide text-gray-500"},"Payment Date",-1)),e("p",J,i(x(a.value.payment_date)),1)]),e("div",null,[t[7]||(t[7]=e("p",{class:"text-xs uppercase tracking-wide text-gray-500"},"Reference Type",-1)),e("p",X,i(a.value.reference_type||"-"),1)]),e("div",null,[t[8]||(t[8]=e("p",{class:"text-xs uppercase tracking-wide text-gray-500"},"Reference #",-1)),e("p",Y,i(a.value.reference_number||"-"),1)]),e("div",null,[t[9]||(t[9]=e("p",{class:"text-xs uppercase tracking-wide text-gray-500"},"Currency",-1)),e("p",Z,i(a.value.currency||"PHP"),1)]),e("div",ee,[t[10]||(t[10]=e("p",{class:"text-xs uppercase tracking-wide text-gray-500"},"Description",-1)),e("p",te,i(a.value.description||"-"),1)]),a.value.notes?(c(),v("div",se,[t[11]||(t[11]=e("p",{class:"text-xs uppercase tracking-wide text-gray-500"},"Notes",-1)),e("p",ae,i(a.value.notes),1)])):P("",!0)])):(c(),v("div",q,"Expense not found."))]),_:1}),n.value?(c(),$(r(b),{key:0,class:"overflow-hidden rounded-2xl border border-gray-100 shadow-sm"},{header:p(()=>[...t[12]||(t[12]=[e("div",{class:"px-6 pt-6"},[e("h2",{class:"text-lg font-semibold text-gray-900"},"Linked Invoice/Receipt Detail")],-1)])]),content:p(()=>[e("div",ie,[e("div",null,[t[13]||(t[13]=e("p",{class:"text-xs uppercase tracking-wide text-gray-500"},"Invoice #",-1)),e("p",ne,i(n.value.invoice_number||"-"),1)]),e("div",null,[t[14]||(t[14]=e("p",{class:"text-xs uppercase tracking-wide text-gray-500"},"Supplier",-1)),e("p",oe,i(n.value.supplier?.supplier_name||"-"),1)]),e("div",null,[t[15]||(t[15]=e("p",{class:"text-xs uppercase tracking-wide text-gray-500"},"PO #",-1)),e("p",re,i(n.value.purchase_order?.po_number||"-"),1)]),e("div",null,[t[16]||(t[16]=e("p",{class:"text-xs uppercase tracking-wide text-gray-500"},"Invoice Date",-1)),e("p",le,i(x(n.value.invoice_date)),1)]),e("div",null,[t[17]||(t[17]=e("p",{class:"text-xs uppercase tracking-wide text-gray-500"},"Due Date",-1)),e("p",de,i(x(n.value.due_date)),1)]),e("div",null,[t[18]||(t[18]=e("p",{class:"text-xs uppercase tracking-wide text-gray-500"},"Invoice Status",-1)),d(r(k),{value:y(n.value.status),severity:w(n.value.status)},null,8,["value","severity"])])]),e("div",pe,[d(r(T),{value:n.value.items||[],stripedRows:"",responsiveLayout:"scroll",class:"p-datatable-sm"},{default:p(()=>[d(r(g),{field:"product.product_name",header:"Item",style:{"min-width":"220px"}},{body:p(({data:l})=>[e("div",null,[e("p",ce,i(l.product?.product_name||"Unknown Product"),1),e("p",ue,"SKU: "+i(l.product?.sku||"-"),1)])]),_:1}),d(r(g),{field:"quantity_invoiced",header:"Qty",style:{width:"90px"}}),d(r(g),{field:"unit_price",header:"Unit Price",style:{width:"140px"}},{body:p(({data:l})=>[N("₱ "+i(o(l.unit_price)),1)]),_:1}),d(r(g),{field:"line_amount",header:"Line Total",style:{width:"160px"}},{body:p(({data:l})=>[e("span",xe,"₱ "+i(o(l.line_amount||0)),1)]),_:1})]),_:1},8,["value"]),e("div",me,[e("div",ve,[t[19]||(t[19]=e("span",{class:"text-gray-500"},"Subtotal",-1)),e("span",null,"₱ "+i(o(n.value.subtotal||n.value.invoice_amount||0)),1)]),e("div",ge,[t[20]||(t[20]=e("span",{class:"text-gray-500"},"Shipping Cost",-1)),e("span",null,"₱ "+i(o(n.value.shipping_cost||0)),1)]),e("div",fe,[t[21]||(t[21]=e("span",{class:"text-gray-500"},"Tax",-1)),e("span",null,"₱ "+i(o(n.value.tax_amount||0)),1)]),e("div",ye,[t[22]||(t[22]=e("span",null,"Total",-1)),e("span",null,"₱ "+i(o(n.value.net_amount||n.value.invoice_amount||0)),1)])])])]),_:1})):a.value&&a.value.reference_type?(c(),$(r(b),{key:1,class:"overflow-hidden rounded-2xl border border-gray-100 shadow-sm"},{content:p(()=>[e("div",be," No expanded invoice/receipt detail is available for reference type: "+i(a.value.reference_type)+". ",1)]),_:1})):P("",!0)]))}});export{ke as default};
