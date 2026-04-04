import{g as j,C as z,o as u,k as m,d as e,a as o,b as l,r as w,w as d,E as r,a1 as h,a0 as f,ad as B,ac as x,f as K,G as O,v as _,K as k,a2 as U,aj as G}from"./app-DAedBCYh.js";import{f as L}from"./finance.service-Bzc668xI.js";const M={class:"mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8"},A={class:"rounded-3xl border border-gray-100 bg-linear-to-r from-slate-50 via-white to-blue-50 p-5 shadow-sm"},E={class:"flex flex-wrap items-center justify-between gap-3"},V={class:"flex items-center gap-3"},q={key:0,class:"p-6 text-sm text-gray-500"},Q={key:1,class:"p-6 text-sm text-gray-500"},W={key:2,class:"grid grid-cols-1 gap-4 p-6 md:grid-cols-3"},J={class:"mt-1 font-semibold text-gray-900"},X={class:"mt-1 font-semibold text-gray-900"},Y={class:"text-sm text-gray-500"},Z={class:"text-sm text-gray-500"},ee={class:"mt-1 text-sm text-gray-700"},te={class:"mt-1 text-sm text-gray-700"},se={class:"mt-1 text-sm text-gray-700"},ae={class:"mt-1 text-sm text-gray-700"},re={class:"mt-1 text-sm text-gray-700"},ie={class:"grid grid-cols-1 gap-4 p-6 pt-2 md:grid-cols-3"},oe={class:"mt-1 text-sm font-medium text-gray-900"},le={class:"mt-1 text-sm text-gray-700"},ne={class:"mt-1 text-sm text-gray-700"},de={class:"rounded-xl border border-gray-200 bg-gray-50 p-3"},pe={class:"mt-1 text-sm font-semibold text-gray-900"},ce={class:"rounded-xl border border-gray-200 bg-gray-50 p-3"},ue={class:"mt-1 text-sm font-semibold text-gray-900"},me={class:"rounded-xl border border-blue-200 bg-blue-50 p-3"},xe={class:"mt-1 text-base font-semibold text-blue-900"},ge={class:"p-6 pt-2"},ve={class:"font-semibold text-green-700"},fe={key:0,class:"mt-4 grid grid-cols-1 gap-2 text-sm md:ml-auto md:max-w-sm"},ye={class:"flex items-center justify-between"},be={class:"flex items-center justify-between"},we={class:"flex items-center justify-between"},he={class:"flex items-center justify-between"},_e={class:"flex items-center justify-between"},ke={class:"flex items-center justify-between border-t border-gray-200 pt-2 text-base font-semibold"},Se=j({__name:"FinanceReceivablesDetail",setup(De){const y=U(),D=G(),v=_(!1),a=_(null),P=k(()=>{const s=Number(a.value?.delivery?.distance_km||0);if(s>0)return s;const t=Number(a.value?.delivery?.estimated_fee||0),n=Number(a.value?.delivery?.per_km_charge||0);return t>0&&n>0?t/n:s}),$=k(()=>{const s=Number(a.value?.amounts?.total||0),t=Number(a.value?.delivery?.estimated_fee||0);return s+t}),i=s=>{const t=typeof s=="string"?parseFloat(s):s||0;return new Intl.NumberFormat("en-PH",{minimumFractionDigits:2,maximumFractionDigits:2}).format(t)},S=s=>{const t=typeof s=="string"?parseFloat(s):s||0;return new Intl.NumberFormat("en-PH",{minimumFractionDigits:2,maximumFractionDigits:2}).format(t)},g=s=>s?new Date(s).toLocaleDateString("en-PH",{month:"short",day:"numeric",year:"numeric"}):"-",b=s=>s?s.charAt(0).toUpperCase()+s.slice(1):"-",F=s=>s==="paid"?"success":s==="partial"?"info":s==="pending"?"warn":s==="cancelled"?"danger":"secondary",N=()=>D.push({name:"finance.receivables"}),T=async()=>{v.value=!0;try{const s=String(y.params.source||"sales"),t=Number(y.params.id),n=await L.getReceivableDetail(s,t);a.value=n.data||null}finally{v.value=!1}},C=()=>{if(!a.value)return;const s=a.value,t=Number(s.delivery?.estimated_fee||0),n=Number(s.amounts?.total||0),I=n+t,R=(s.items||[]).map(c=>`
      <tr>
        <td style="padding:10px 12px;border-bottom:1px solid #edf0f4;">${c.sku||"-"}</td>
        <td style="padding:10px 12px;border-bottom:1px solid #edf0f4;">${c.name||"-"}</td>
        <td style="padding:10px 12px;border-bottom:1px solid #edf0f4;text-align:right;">${c.quantity||0}</td>
        <td style="padding:10px 12px;border-bottom:1px solid #edf0f4;text-align:right;">PHP ${i(c.unit_price||0)}</td>
        <td style="padding:10px 12px;border-bottom:1px solid #edf0f4;text-align:right;font-weight:600;">PHP ${i(c.line_total||0)}</td>
      </tr>
    `).join(""),H=`
    <html>
      <head>
        <title>Invoice ${s.reference}</title>
        <style>
          body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; margin: 0; background: #f5f7fb; color: #101828; }
          .page { max-width: 920px; margin: 24px auto; background: #ffffff; border: 1px solid #e7ebf1; border-radius: 16px; padding: 24px; }
          .top { display: flex; justify-content: space-between; align-items: start; gap: 12px; margin-bottom: 20px; }
          .title { font-size: 26px; font-weight: 700; letter-spacing: -0.3px; margin: 0; }
          .subtitle { margin: 4px 0 0 0; color: #667085; font-size: 12px; }
          .pill { background: #eff4ff; border: 1px solid #dbe8ff; color: #1d4ed8; font-size: 11px; font-weight: 600; border-radius: 999px; padding: 6px 10px; }
          .meta-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 18px; }
          .meta-card { border: 1px solid #edf0f4; background: #fafbfd; border-radius: 10px; padding: 10px; }
          .meta-label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.08em; color: #667085; margin-bottom: 4px; }
          .meta-value { font-size: 12px; font-weight: 600; color: #1f2937; }
          .section-title { margin: 18px 0 10px 0; font-size: 13px; font-weight: 700; color: #111827; }
          table { width: 100%; border-collapse: collapse; font-size: 12px; border: 1px solid #edf0f4; border-radius: 12px; overflow: hidden; }
          thead th { text-align: left; padding: 10px 12px; border-bottom: 1px solid #e5e7eb; background: #f8fafc; color: #475467; font-size: 10px; text-transform: uppercase; letter-spacing: 0.08em; }
          .totals { margin-top: 16px; margin-left: auto; width: 360px; border: 1px solid #edf0f4; border-radius: 12px; padding: 12px 14px; background: #fcfdff; }
          .row { display: flex; justify-content: space-between; font-size: 12px; padding: 3px 0; color: #374151; }
          .row strong { color: #111827; }
          .grand { margin-top: 8px; padding-top: 8px; border-top: 1px solid #dfe4ea; font-size: 14px; font-weight: 700; }
          .footer { margin-top: 20px; font-size: 10px; color: #98a2b3; text-align: center; }
        </style>
      </head>
      <body>
        <div class="page">
          <div class="top">
            <div>
              <h2 class="title">Official Receipt</h2>
              <p class="subtitle">Reference: ${s.reference} ${s.receipt?.receipt_number?`| Receipt: ${s.receipt.receipt_number}`:""}</p>
            </div>
            <div class="pill">${s.source_type==="ecommerce"?"Ecommerce":"Sales"} Invoice</div>
          </div>

          <div class="meta-grid">
            <div class="meta-card">
              <div class="meta-label">Customer</div>
              <div class="meta-value">${s.customer?.name||"-"}</div>
            </div>
            <div class="meta-card">
              <div class="meta-label">Phone</div>
              <div class="meta-value">${s.customer?.phone||"-"}</div>
            </div>
            <div class="meta-card">
              <div class="meta-label">Issue Date</div>
              <div class="meta-value">${g(s.dates?.created_at)}</div>
            </div>
            <div class="meta-card">
              <div class="meta-label">Status</div>
              <div class="meta-value">${b(s.status)}</div>
            </div>
          </div>

          <div class="meta-card" style="margin-bottom: 12px;">
            <div class="meta-label">Billing Address</div>
            <div class="meta-value">${s.billing?.address||"-"}</div>
          </div>

          <h3 class="section-title">Order Items</h3>
          <table>
          <thead>
            <tr>
              <th>SKU</th>
              <th>Item</th>
              <th style="text-align:right;">Qty</th>
              <th style="text-align:right;">Unit Price</th>
              <th style="text-align:right;">Line Total</th>
            </tr>
          </thead>
          <tbody>${R}</tbody>
        </table>

          <div class="totals">
            <div class="row"><span>Subtotal</span><strong>PHP ${i(s.amounts?.subtotal||0)}</strong></div>
            <div class="row"><span>Discount</span><strong>PHP ${i(s.amounts?.discount||0)}</strong></div>
            <div class="row"><span>Tax</span><strong>PHP ${i(s.amounts?.tax||0)}</strong></div>
            <div class="row"><span>Shipping Fee</span><strong>PHP ${i(s.amounts?.shipping_fee||0)}</strong></div>
            <div class="row"><span>Delivery Fee</span><strong>PHP ${i(t)}</strong></div>
            <div class="row"><span>Order Total</span><strong>PHP ${i(n)}</strong></div>
            <div class="row grand"><span>Grand Total</span><strong>PHP ${i(I)}</strong></div>
          </div>

          <div class="footer">
            Generated on ${g(new Date().toISOString())}
          </div>
        </div>
      </body>
    </html>
  `,p=window.open("","_blank","width=900,height=700");p&&(p.document.open(),p.document.write(H),p.document.close(),p.focus(),setTimeout(()=>{p.print()},250))};return z(T),(s,t)=>(u(),m("div",M,[e("div",A,[e("div",E,[e("div",V,[o(l(w),{icon:"pi pi-arrow-left",rounded:"",text:"",onClick:N}),t[0]||(t[0]=e("div",null,[e("h1",{class:"text-xl font-semibold tracking-tight text-gray-900"},"Receivable Detail"),e("p",{class:"mt-0.5 text-sm text-gray-500"},"Full order and invoice details for customer billing.")],-1))]),o(l(w),{icon:"pi pi-print",label:"Print Invoice",disabled:!a.value,onClick:C},null,8,["disabled"])])]),o(l(f),{class:"overflow-hidden rounded-2xl border border-gray-100 shadow-sm"},{content:d(()=>[v.value?(u(),m("div",q,"Loading receivable details...")):a.value?(u(),m("div",W,[e("div",null,[t[1]||(t[1]=e("p",{class:"text-xs uppercase tracking-wide text-gray-500"},"Invoice Reference",-1)),e("p",J,r(a.value.reference),1)]),e("div",null,[t[2]||(t[2]=e("p",{class:"text-xs uppercase tracking-wide text-gray-500"},"Source",-1)),o(l(h),{value:a.value.source_type==="ecommerce"?"Ecommerce":"Sales",severity:a.value.source_type==="ecommerce"?"info":"secondary"},null,8,["value","severity"])]),e("div",null,[t[3]||(t[3]=e("p",{class:"text-xs uppercase tracking-wide text-gray-500"},"Customer",-1)),e("p",X,r(a.value.customer?.name||"-"),1),e("p",Y,r(a.value.customer?.phone||"-"),1),e("p",Z,r(a.value.customer?.email||"-"),1)]),e("div",null,[t[4]||(t[4]=e("p",{class:"text-xs uppercase tracking-wide text-gray-500"},"Address",-1)),e("p",ee,r(a.value.billing?.address||"-"),1)]),e("div",null,[t[5]||(t[5]=e("p",{class:"text-xs uppercase tracking-wide text-gray-500"},"Payment Method",-1)),e("p",te,r(a.value.payment?.method||"-"),1)]),e("div",null,[t[6]||(t[6]=e("p",{class:"text-xs uppercase tracking-wide text-gray-500"},"Payment Status",-1)),o(l(h),{value:b(a.value.status),severity:F(a.value.status)},null,8,["value","severity"])]),e("div",null,[t[7]||(t[7]=e("p",{class:"text-xs uppercase tracking-wide text-gray-500"},"Receipt Number",-1)),e("p",se,r(a.value.receipt?.receipt_number||"-"),1)]),e("div",null,[t[8]||(t[8]=e("p",{class:"text-xs uppercase tracking-wide text-gray-500"},"Created Date",-1)),e("p",ae,r(g(a.value.dates?.created_at)),1)]),e("div",null,[t[9]||(t[9]=e("p",{class:"text-xs uppercase tracking-wide text-gray-500"},"Due Date",-1)),e("p",re,r(g(a.value.dates?.due_date)),1)])])):(u(),m("div",Q,"Receivable record not found."))]),_:1}),o(l(f),{class:"overflow-hidden rounded-2xl border border-gray-100 shadow-sm"},{header:d(()=>[...t[10]||(t[10]=[e("div",{class:"px-6 pt-6"},[e("h2",{class:"text-lg font-semibold text-gray-900"},"Delivery Details")],-1)])]),content:d(()=>[e("div",ie,[e("div",null,[t[11]||(t[11]=e("p",{class:"text-xs uppercase tracking-wide text-gray-500"},"Tracking #",-1)),e("p",oe,r(a.value?.delivery?.tracking_number||"-"),1)]),e("div",null,[t[12]||(t[12]=e("p",{class:"text-xs uppercase tracking-wide text-gray-500"},"Courier",-1)),e("p",le,r(a.value?.delivery?.courier_name||"-"),1)]),e("div",null,[t[13]||(t[13]=e("p",{class:"text-xs uppercase tracking-wide text-gray-500"},"Courier Contact",-1)),e("p",ne,r(a.value?.delivery?.courier_contact||"-"),1)]),e("div",de,[t[14]||(t[14]=e("p",{class:"text-xs uppercase tracking-wide text-gray-500"},"Distance",-1)),e("p",pe,r(S(P.value))+" km",1)]),e("div",ce,[t[15]||(t[15]=e("p",{class:"text-xs uppercase tracking-wide text-gray-500"},"Per KM Charge",-1)),e("p",ue,"₱ "+r(i(a.value?.delivery?.per_km_charge||0)),1)]),e("div",me,[t[16]||(t[16]=e("p",{class:"text-xs uppercase tracking-wide text-blue-700"},"Delivery Fee",-1)),e("p",xe,"₱ "+r(i(a.value?.delivery?.estimated_fee||0)),1)])])]),_:1}),o(l(f),{class:"overflow-hidden rounded-2xl border border-gray-100 shadow-sm"},{header:d(()=>[...t[17]||(t[17]=[e("div",{class:"px-6 pt-6"},[e("h2",{class:"text-lg font-semibold text-gray-900"},"Order Items")],-1)])]),content:d(()=>[e("div",ge,[o(l(B),{value:a.value?.items||[],stripedRows:"",responsiveLayout:"scroll",class:"p-datatable-sm"},{empty:d(()=>[...t[18]||(t[18]=[e("div",{class:"py-8 text-center text-sm text-gray-500"},"No line items found.",-1)])]),default:d(()=>[o(l(x),{field:"sku",header:"SKU",style:{"min-width":"130px"}}),o(l(x),{field:"name",header:"Item",style:{"min-width":"220px"}}),o(l(x),{field:"quantity",header:"Qty",style:{width:"90px"}}),o(l(x),{field:"unit_price",header:"Unit Price",style:{width:"140px"}},{body:d(({data:n})=>[K("₱ "+r(i(n.unit_price)),1)]),_:1}),o(l(x),{field:"line_total",header:"Line Total",style:{width:"150px"}},{body:d(({data:n})=>[e("span",ve,"₱ "+r(i(n.line_total)),1)]),_:1})]),_:1},8,["value"]),a.value?(u(),m("div",fe,[e("div",ye,[t[19]||(t[19]=e("span",{class:"text-gray-500"},"Subtotal",-1)),e("span",null,"₱ "+r(i(a.value.amounts?.subtotal)),1)]),e("div",be,[t[20]||(t[20]=e("span",{class:"text-gray-500"},"Discount",-1)),e("span",null,"₱ "+r(i(a.value.amounts?.discount)),1)]),e("div",we,[t[21]||(t[21]=e("span",{class:"text-gray-500"},"Tax",-1)),e("span",null,"₱ "+r(i(a.value.amounts?.tax)),1)]),e("div",he,[t[22]||(t[22]=e("span",{class:"text-gray-500"},"Order Total",-1)),e("span",null,"₱ "+r(i(a.value.amounts?.total)),1)]),e("div",_e,[t[23]||(t[23]=e("span",{class:"text-gray-500"},"Delivery Fee",-1)),e("span",null,"₱ "+r(i(a.value.delivery?.estimated_fee||0)),1)]),e("div",ke,[t[24]||(t[24]=e("span",null,"Grand Total",-1)),e("span",null,"₱ "+r(i($.value)),1)])])):O("",!0)])]),_:1})]))}});export{Se as default};
