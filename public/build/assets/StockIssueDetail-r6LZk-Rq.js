import{g as Z,i as ee,C as te,N as y,o as u,k as c,d as e,E as a,a as o,c as w,G as p,w as r,f as i,n as F,F as U,O as se,v as _,K as ae,aj as le,a2 as ne,z as oe}from"./app-DAedBCYh.js";import{i as S}from"./inventory.service-CRUanJWq.js";const re={class:"bg-gray-50 min-h-screen p-6"},ie={class:"max-w-6xl mx-auto"},de={class:"mb-6"},ue={class:"flex items-center justify-between"},ce={class:"text-gray-600 mt-1"},pe={class:"flex gap-3"},me={key:0,class:"flex justify-center py-12"},ve={key:1,id:"printable-content"},_e={class:"space-y-6 screen-view"},ye={class:"grid grid-cols-1 lg:grid-cols-3 gap-6"},ge={class:"lg:col-span-2"},be={class:"grid grid-cols-1 md:grid-cols-2 gap-6"},fe={class:"text-gray-900 font-mono"},xe={class:"text-gray-900"},ke={class:"text-gray-900"},he={class:"text-gray-900"},we={class:"text-gray-900"},Se={class:"text-gray-900"},Ne={class:"md:col-span-2"},Ce={class:"text-gray-900"},Ae={key:0,class:"md:col-span-2"},De={class:"text-gray-900"},Ie={key:1,class:"md:col-span-2"},Te={class:"text-gray-900"},Be={class:"space-y-4"},qe={class:"text-2xl font-bold text-blue-600"},Re={class:"text-2xl font-bold text-red-600"},Le={class:"space-y-3"},Ee={class:"text-gray-900"},Fe={key:0},Ue={class:"text-gray-900"},ze={key:0},Me={key:1},Pe={class:"font-medium text-red-600"},Ve={class:"print-view"},je={class:"print-header"},Oe={class:"flex justify-between items-center mb-6"},Ke={class:"text-gray-600"},He={class:"text-right"},Qe={class:"text-sm"},$e={class:"text-sm"},Ye={class:"print-company-info mb-6 p-4 border rounded"},Ge={class:"grid grid-cols-2 gap-4"},We={class:"text-right"},Je={class:"font-bold"},Xe={class:"print-details mb-6"},Ze={class:"grid grid-cols-2 gap-4"},et={key:0},tt={class:"print-items mb-6"},st={class:"print-table"},at={class:"text-right"},lt={class:"text-right"},nt={class:"text-right"},ot={class:"text-right font-bold"},rt={class:"text-right font-bold"},it={class:"print-signatures mt-8 grid grid-cols-3 gap-4"},dt={class:"signature-line mt-8 border-t border-black pt-1"},ut={class:"text-sm"},ct={class:"signature-line mt-8 border-t border-black pt-1"},pt={class:"text-sm"},mt={key:2,class:"text-center py-12"},vt={class:"flex items-center gap-3"},_t={class:"text-sm text-gray-600 mt-1"},yt=Z({__name:"StockIssueDetail",setup(gt){const m=_(!0),N=_(!1),k=_(!1),C=_(!1),A=_(!1),s=_(null),D=_([]),I=_([]),g=ee(),B=le(),z=ne(),q=ae(()=>s.value?.items?s.value.items.reduce((l,t)=>l+t.quantity,0):0),R=async()=>{m.value=!0;try{const l=await S.getStockIssue(z.params.id);l.success?(s.value=l.data,console.log("Stock issue loaded:",s.value)):g.add({severity:"error",summary:"Error",detail:"Failed to load stock issue details",life:3e3})}catch(l){g.add({severity:"error",summary:"Error",detail:l.response?.data?.message||"Failed to load stock issue details",life:3e3})}finally{m.value=!1}},M=async()=>{if(s.value?.items?.length){C.value=!0;try{const l=await S.getProductStockMovements(s.value.items[0].inventory_item?.product_id,{reference:s.value.issue_number});l.success&&(D.value=l.data||[])}catch(l){console.error("Failed to load stock movements",l)}finally{C.value=!1}}},P=async()=>{if(s.value?.items?.some(t=>t.inventory_item?.product?.track_serial_numbers)){A.value=!0;try{const t=await S.getSerialNumbers({reference:s.value.issue_number});t.success&&(I.value=t.data||[])}catch(t){console.error("Failed to load serial numbers",t)}finally{A.value=!1}}},V=()=>{document.getElementById("printable-content");const l=window.open("","_blank");if(!l){g.add({severity:"error",summary:"Error",detail:"Please allow pop-ups to print",life:3e3});return}const t=document.querySelector(".print-view")?.innerHTML||"";l.document.write(`
    <!DOCTYPE html>
    <html>
    <head>
      <title>Stock Issue - ${s.value?.issue_number}</title>
      <style>
        body {
          font-family: Arial, sans-serif;
          margin: 0;
          padding: 20px;
          color: #333;
        }
        .print-container {
          max-width: 1200px;
          margin: 0 auto;
        }
        .print-header {
          margin-bottom: 20px;
        }
        .print-company-info {
          border: 1px solid #ddd;
          padding: 15px;
          margin-bottom: 20px;
          border-radius: 4px;
        }
        .print-table {
          width: 100%;
          border-collapse: collapse;
          margin-bottom: 20px;
        }
        .print-table th,
        .print-table td {
          border: 1px solid #ddd;
          padding: 8px;
          text-align: left;
        }
        .print-table th {
          background-color: #f5f5f5;
          font-weight: bold;
        }
        .print-table tfoot {
          font-weight: bold;
          background-color: #f9f9f9;
        }
        .print-signatures {
          margin-top: 40px;
        }
        .signature-line {
          margin-top: 40px;
        }
        .print-footer {
          margin-top: 40px;
          padding-top: 20px;
          border-top: 1px solid #ddd;
          text-align: center;
          font-size: 12px;
          color: #666;
        }
        .grid {
          display: grid;
          gap: 1rem;
        }
        .grid-cols-2 {
          grid-template-columns: repeat(2, 1fr);
        }
        .grid-cols-3 {
          grid-template-columns: repeat(3, 1fr);
        }
        .flex {
          display: flex;
        }
        .justify-between {
          justify-content: space-between;
        }
        .items-center {
          align-items: center;
        }
        .text-right {
          text-align: right;
        }
        .text-center {
          text-align: center;
        }
        .font-bold {
          font-weight: bold;
        }
        .mb-2 {
          margin-bottom: 0.5rem;
        }
        .mb-4 {
          margin-bottom: 1rem;
        }
        .mb-6 {
          margin-bottom: 1.5rem;
        }
        .mt-2 {
          margin-top: 0.5rem;
        }
        .mt-4 {
          margin-top: 1rem;
        }
        .mt-8 {
          margin-top: 2rem;
        }
        .p-4 {
          padding: 1rem;
        }
        .border {
          border: 1px solid #ddd;
        }
        .rounded {
          border-radius: 4px;
        }
        .text-green-600 { color: #059669; }
        .text-yellow-600 { color: #d97706; }
        .text-blue-600 { color: #2563eb; }
        .text-red-600 { color: #dc2626; }
        .text-gray-600 { color: #4b5563; }
        .text-sm { font-size: 0.875rem; }
        @media print {
          body { margin: 0; }
          .no-print { display: none; }
        }
      </style>
    </head>
    <body>
      <div class="print-container">
        ${t}
      </div>
    </body>
    </html>
  `),l.document.close(),l.onload=()=>{l.print()}},L=l=>{if(!l)return"N/A";const t=[];return l.warehouse_section&&t.push(l.warehouse_section),l.aisle&&t.push(l.aisle),l.rack&&t.push(l.rack),l.shelf&&t.push(l.shelf),t.join("-")||l.bin_code||"N/A"},j=l=>{switch(l){case"approved":case"completed":return"success";case"pending":return"warning";case"draft":return"info";case"cancelled":return"danger";default:return"secondary"}},O=l=>{switch(l){case"expired":case"damaged":return"danger";case"lost":return"warning";case"internal_use":return"info";case"sample":return"success";default:return"secondary"}},K=l=>{switch(l){case"quality_issue":case"damaged":return"danger";case"expired":return"warning";case"internal_use":return"info";default:return"secondary"}},H=l=>{switch(l){case"in":case"receive":return"success";case"out":case"issue":return"danger";case"adjustment":return"warning";case"transfer":return"info";default:return"secondary"}},Q=l=>{switch(l){case"available":return"success";case"sold":case"issued":return"info";case"damaged":return"danger";case"returned":return"warning";default:return"secondary"}},E=l=>l?.replace(/_/g," ")||"N/A",v=l=>l?new Date(l).toLocaleDateString("en-US",{year:"numeric",month:"short",day:"numeric",hour:"2-digit",minute:"2-digit"}):"N/A",b=l=>l==null?"0.00":parseFloat(l.toString()).toFixed(2),$=()=>{B.push({name:"inventory.stock-issues.edit",params:{id:s.value.id}})},Y=()=>{k.value=!0},G=async()=>{if(s.value){N.value=!0;try{const l=await S.cancelStockIssue(s.value.id);l.success?(g.add({severity:"success",summary:"Success",detail:"Stock issue cancelled successfully",life:3e3}),k.value=!1,await R()):g.add({severity:"error",summary:"Error",detail:l.message||"Failed to cancel stock issue",life:3e3})}catch(l){g.add({severity:"error",summary:"Error",detail:l.response?.data?.message||"Failed to cancel stock issue",life:3e3})}finally{N.value=!1}}},W=()=>{B.push({name:"inventory.stock-issues"})};return te(async()=>{await R(),s.value&&(M(),P())}),(l,t)=>{const f=y("Button"),J=y("ProgressSpinner"),h=y("Tag"),x=y("Card"),d=y("Column"),T=y("DataTable"),X=y("Dialog");return u(),c(U,null,[e("div",re,[e("div",ie,[e("div",de,[e("div",ue,[e("div",null,[t[2]||(t[2]=e("h1",{class:"text-3xl font-bold text-gray-800"},"Stock Issue Details",-1)),e("p",ce,"Reference: "+a(s.value?.issue_number),1)]),e("div",pe,[o(f,{label:"Print",icon:"pi pi-print",severity:"info",onClick:V,disabled:m.value},null,8,["disabled"]),s.value?.status==="draft"?(u(),w(f,{key:0,label:"Edit",icon:"pi pi-pencil",severity:"secondary",onClick:$,disabled:m.value},null,8,["disabled"])):p("",!0),s.value?.status==="draft"?(u(),w(f,{key:1,label:"Cancel Issue",icon:"pi pi-times",severity:"danger",onClick:Y,disabled:m.value},null,8,["disabled"])):p("",!0),o(f,{label:"Back to List",icon:"pi pi-arrow-left",severity:"secondary",onClick:W,disabled:m.value},null,8,["disabled"])])])]),m.value?(u(),c("div",me,[o(J)])):s.value?(u(),c("div",ve,[e("div",_e,[e("div",ye,[e("div",ge,[o(x,null,{title:r(()=>[...t[3]||(t[3]=[i("Issue Information",-1)])]),content:r(()=>[e("div",be,[e("div",null,[t[4]||(t[4]=e("label",{class:"block text-sm font-medium text-gray-700 mb-1"},"Issue Number",-1)),e("p",fe,a(s.value.issue_number),1)]),e("div",null,[t[5]||(t[5]=e("label",{class:"block text-sm font-medium text-gray-700 mb-1"},"Type",-1)),o(h,{value:s.value.issue_type,severity:O(s.value.issue_type),class:"capitalize"},null,8,["value","severity"])]),e("div",null,[t[6]||(t[6]=e("label",{class:"block text-sm font-medium text-gray-700 mb-1"},"Status",-1)),o(h,{value:s.value.status,severity:j(s.value.status),class:"capitalize"},null,8,["value","severity"])]),e("div",null,[t[7]||(t[7]=e("label",{class:"block text-sm font-medium text-gray-700 mb-1"},"Issue Date",-1)),e("p",xe,a(v(s.value.issue_date)),1)]),e("div",null,[t[8]||(t[8]=e("label",{class:"block text-sm font-medium text-gray-700 mb-1"},"Branch",-1)),e("p",ke,a(s.value.branch?.name||"N/A"),1)]),e("div",null,[t[9]||(t[9]=e("label",{class:"block text-sm font-medium text-gray-700 mb-1"},"Requested By",-1)),e("p",he,a(s.value.requester?.full_name||"N/A"),1)]),e("div",null,[t[10]||(t[10]=e("label",{class:"block text-sm font-medium text-gray-700 mb-1"},"Created By",-1)),e("p",we,a(s.value.creator?.full_name||"N/A"),1)]),e("div",null,[t[11]||(t[11]=e("label",{class:"block text-sm font-medium text-gray-700 mb-1"},"Created At",-1)),e("p",Se,a(v(s.value.created_at)),1)]),e("div",Ne,[t[12]||(t[12]=e("label",{class:"block text-sm font-medium text-gray-700 mb-1"},"Description",-1)),e("p",Ce,a(s.value.description||"No description provided"),1)]),s.value.remarks?(u(),c("div",Ae,[t[13]||(t[13]=e("label",{class:"block text-sm font-medium text-gray-700 mb-1"},"Remarks",-1)),e("p",De,a(s.value.remarks),1)])):p("",!0),s.value.approval_notes?(u(),c("div",Ie,[t[14]||(t[14]=e("label",{class:"block text-sm font-medium text-gray-700 mb-1"},"Approval Notes",-1)),e("p",Te,a(s.value.approval_notes),1)])):p("",!0)])]),_:1})]),e("div",null,[o(x,null,{title:r(()=>[...t[15]||(t[15]=[i("Cost Summary",-1)])]),content:r(()=>[e("div",Be,[e("div",null,[e("div",qe,a(q.value),1),t[16]||(t[16]=e("div",{class:"text-sm text-gray-600"},"Total Items Issued",-1))]),e("div",null,[e("div",Re," ₱"+a(b(s.value.total_value)),1),t[17]||(t[17]=e("div",{class:"text-sm text-gray-600"},"Total Value",-1))])])]),_:1}),s.value.approver?(u(),w(x,{key:0,class:"mt-4"},{title:r(()=>[...t[18]||(t[18]=[i("Approval Information",-1)])]),content:r(()=>[e("div",Le,[e("div",null,[t[19]||(t[19]=e("label",{class:"block text-sm font-medium text-gray-700 mb-1"},"Approved By",-1)),e("p",Ee,a(s.value.approver?.full_name||"N/A"),1)]),s.value.approved_at?(u(),c("div",Fe,[t[20]||(t[20]=e("label",{class:"block text-sm font-medium text-gray-700 mb-1"},"Approved At",-1)),e("p",Ue,a(v(s.value.approved_at)),1)])):p("",!0)])]),_:1})):p("",!0)])]),o(x,null,{title:r(()=>[...t[21]||(t[21]=[i("Issued Items",-1)])]),content:r(()=>[o(T,{value:s.value.items||[],loading:m.value,tableStyle:"min-width: 50rem",class:"p-datatable-sm"},{default:r(()=>[o(d,{field:"inventory_item.product.sku",header:"SKU",style:{width:"120px"}},{body:r(n=>[i(a(n.data.inventory_item?.product?.sku||"N/A"),1)]),_:1}),o(d,{field:"inventory_item.product.product_name",header:"Product Name",style:{"min-width":"200px"}},{body:r(n=>[i(a(n.data.inventory_item?.product?.product_name||"N/A"),1)]),_:1}),o(d,{header:"Location",style:{width:"150px"}},{body:r(n=>[n.data.inventory_item?(u(),c("span",ze,a(L(n.data.inventory_item)),1)):(u(),c("span",Me,"N/A"))]),_:1}),o(d,{field:"quantity",header:"Quantity",style:{width:"100px"}},{body:r(n=>[e("span",Pe," -"+a(n.data.quantity),1)]),_:1}),o(d,{field:"unit_cost",header:"Unit Cost",style:{width:"120px"}},{body:r(n=>[i(" ₱"+a(b(n.data.unit_cost)),1)]),_:1}),o(d,{field:"total_value",header:"Total Value",style:{width:"120px"}},{body:r(n=>[i(" ₱"+a(b(n.data.total_value)),1)]),_:1}),o(d,{field:"reason",header:"Reason",style:{"min-width":"150px"}},{body:r(n=>[o(h,{value:E(n.data.reason),severity:K(n.data.reason),class:"capitalize"},null,8,["value","severity"])]),_:1}),o(d,{field:"remarks",header:"Remarks",style:{"min-width":"150px"}},{body:r(n=>[i(a(n.data.remarks||"N/A"),1)]),_:1})]),_:1},8,["value","loading"])]),_:1}),D.value.length>0?(u(),w(x,{key:0},{title:r(()=>[...t[22]||(t[22]=[i("Stock Movement History",-1)])]),content:r(()=>[o(T,{value:D.value,loading:C.value,paginator:"",rows:5,rowsPerPageOptions:[5,10],tableStyle:"min-width: 50rem",class:"p-datatable-sm"},{default:r(()=>[o(d,{field:"type",header:"Movement Type",style:{width:"150px"}},{body:r(n=>[o(h,{value:n.data.type,severity:H(n.data.type),class:"capitalize"},null,8,["value","severity"])]),_:1}),o(d,{field:"quantity",header:"Quantity",style:{width:"100px"}},{body:r(n=>[e("span",{class:F(n.data.quantity>0?"text-green-600":"text-red-600")},a(n.data.quantity>0?"+":"")+a(n.data.quantity),3)]),_:1}),o(d,{field:"reference",header:"Reference",style:{width:"150px"}},{body:r(n=>[i(a(n.data.reference_number||"N/A"),1)]),_:1}),o(d,{field:"notes",header:"Notes"}),o(d,{field:"created_at",header:"Date",style:{width:"150px"}},{body:r(n=>[i(a(v(n.data.created_at)),1)]),_:1})]),_:1},8,["value","loading"])]),_:1})):p("",!0),s.value.items?.some(n=>n.inventory_item?.product?.track_serial_numbers)&&I.value.length>0?(u(),w(x,{key:1},{title:r(()=>[...t[23]||(t[23]=[i("Serial Numbers Issued",-1)])]),content:r(()=>[o(T,{value:I.value,loading:A.value,paginator:"",rows:10,rowsPerPageOptions:[5,10,25],tableStyle:"min-width: 50rem",class:"p-datatable-sm"},{default:r(()=>[o(d,{field:"serial_number",header:"Serial Number",style:{width:"200px"}}),o(d,{field:"batch_number",header:"Batch",style:{width:"150px"}}),o(d,{field:"status",header:"Status",style:{width:"120px"}},{body:r(n=>[o(h,{value:n.data.status,severity:Q(n.data.status),class:"capitalize"},null,8,["value","severity"])]),_:1}),o(d,{field:"location",header:"Location"}),o(d,{field:"created_at",header:"Created",style:{width:"150px"}},{body:r(n=>[i(a(v(n.data.created_at)),1)]),_:1})]),_:1},8,["value","loading"])]),_:1})):p("",!0)]),e("div",Ve,[e("div",je,[e("div",Oe,[e("div",null,[t[24]||(t[24]=e("h1",{class:"text-2xl font-bold"},"STOCK ISSUE VOUCHER",-1)),e("p",Ke,a(s.value.issue_number),1)]),e("div",He,[e("p",Qe,"Date Printed: "+a(new Date().toLocaleDateString()),1),e("p",$e,"Time Printed: "+a(new Date().toLocaleTimeString()),1)])])]),e("div",Ye,[e("div",Ge,[e("div",null,[t[25]||(t[25]=e("h3",{class:"font-bold"},"Branch Information",-1)),e("p",null,a(s.value.branch?.name||"N/A"),1),e("p",null,a(s.value.branch?.address||"N/A"),1),e("p",null,a(s.value.branch?.city)+", "+a(s.value.branch?.province),1),e("p",null,"Contact: "+a(s.value.branch?.contact_number||"N/A"),1)]),e("div",We,[e("h3",Je,[t[26]||(t[26]=i("Status: ",-1)),e("span",{class:F({"text-green-600":s.value.status==="approved","text-yellow-600":s.value.status==="pending","text-blue-600":s.value.status==="draft","text-red-600":s.value.status==="cancelled"})},a(s.value.status?.toUpperCase()),3)]),e("p",null,"Issue Type: "+a(s.value.issue_type?.toUpperCase()),1),e("p",null,"Issue Date: "+a(v(s.value.issue_date)),1)])])]),e("div",Xe,[e("div",Ze,[e("div",null,[e("p",null,[t[27]||(t[27]=e("strong",null,"Requested By:",-1)),i(" "+a(s.value.requester?.full_name||"N/A"),1)]),e("p",null,[t[28]||(t[28]=e("strong",null,"Created By:",-1)),i(" "+a(s.value.creator?.full_name||"N/A"),1)]),e("p",null,[t[29]||(t[29]=e("strong",null,"Created At:",-1)),i(" "+a(v(s.value.created_at)),1)])]),e("div",null,[e("p",null,[t[30]||(t[30]=e("strong",null,"Description:",-1)),i(" "+a(s.value.description||"N/A"),1)]),e("p",null,[t[31]||(t[31]=e("strong",null,"Remarks:",-1)),i(" "+a(s.value.remarks||"N/A"),1)]),s.value.approval_notes?(u(),c("p",et,[t[32]||(t[32]=e("strong",null,"Approval Notes:",-1)),i(" "+a(s.value.approval_notes),1)])):p("",!0)])])]),e("div",tt,[t[37]||(t[37]=e("h3",{class:"font-bold mb-2"},"Issued Items",-1)),e("table",st,[t[36]||(t[36]=e("thead",null,[e("tr",null,[e("th",null,"SKU"),e("th",null,"Product Name"),e("th",null,"Location"),e("th",null,"Qty"),e("th",null,"Unit Cost"),e("th",null,"Total Value"),e("th",null,"Reason")])],-1)),e("tbody",null,[(u(!0),c(U,null,se(s.value.items,n=>(u(),c("tr",{key:n.id},[e("td",null,a(n.inventory_item?.product?.sku||"N/A"),1),e("td",null,a(n.inventory_item?.product?.product_name||"N/A"),1),e("td",null,a(L(n.inventory_item)),1),e("td",at,a(n.quantity),1),e("td",lt,"₱"+a(b(n.unit_cost)),1),e("td",nt,"₱"+a(b(n.total_value)),1),e("td",null,a(E(n.reason)),1)]))),128))]),e("tfoot",null,[e("tr",null,[t[33]||(t[33]=e("td",{colspan:"3",class:"text-right font-bold"},"Totals:",-1)),e("td",ot,a(q.value),1),t[34]||(t[34]=e("td",null,null,-1)),e("td",rt,"₱"+a(b(s.value.total_value)),1),t[35]||(t[35]=e("td",null,null,-1))])])])]),e("div",it,[e("div",null,[t[38]||(t[38]=e("p",{class:"font-bold"},"Requested By:",-1)),e("div",dt,[e("p",null,a(s.value.requester?.full_name||"_________________"),1),e("p",ut,"Date: "+a(new Date().toLocaleDateString()),1)])]),e("div",null,[t[39]||(t[39]=e("p",{class:"font-bold"},"Approved By:",-1)),e("div",ct,[e("p",null,a(s.value.approver?.full_name||"_________________"),1),e("p",pt,"Date: "+a(s.value.approved_at?v(s.value.approved_at):"_________________"),1)])]),t[40]||(t[40]=e("div",null,[e("p",{class:"font-bold"},"Received By:"),e("div",{class:"signature-line mt-8 border-t border-black pt-1"},[e("p",null,"_________________"),e("p",{class:"text-sm"},"Date: _________________")])],-1))]),t[41]||(t[41]=e("div",{class:"print-footer mt-8 text-center text-sm text-gray-600"},[e("p",null,"This is a system-generated document. No signature required if printed electronically."),e("p",null,"Page 1 of 1")],-1))])])):(u(),c("div",mt,[...t[42]||(t[42]=[e("p",{class:"text-gray-500"},"Stock issue not found",-1)])]))])]),o(X,{visible:k.value,"onUpdate:visible":t[1]||(t[1]=n=>k.value=n),modal:"",header:"Confirm Cancellation",style:{width:"450px"}},{footer:r(()=>[o(f,{label:"No, Keep It",severity:"secondary",onClick:t[0]||(t[0]=n=>k.value=!1)}),o(f,{label:"Yes, Cancel Issue",severity:"danger",onClick:G,loading:N.value},null,8,["loading"])]),default:r(()=>[e("div",vt,[t[46]||(t[46]=e("i",{class:"pi pi-exclamation-triangle text-orange-500 text-2xl"},null,-1)),e("div",null,[t[44]||(t[44]=e("p",{class:"font-medium"},"Are you sure you want to cancel this stock issue?",-1)),e("p",_t,[t[43]||(t[43]=i(" Reference: ",-1)),e("strong",null,a(s.value?.issue_number),1)]),t[45]||(t[45]=e("p",{class:"text-sm text-gray-600 mt-1"}," This will reverse the stock reduction and mark the issue as cancelled. ",-1))])])]),_:1},8,["visible"])],64)}}}),xt=oe(yt,[["__scopeId","data-v-ef0a0527"]]);export{xt as default};
