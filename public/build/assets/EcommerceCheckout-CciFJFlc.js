import{_ as Ke}from"./EcommerceMobileWrapper.vue_vue_type_script_setup_true_lang-Cc58fQpO.js";import{aw as je,ax as Te,ay as Ge,r as He,az as Xe,aA as qe,aB as Ze,aC as We,aD as Ye,aE as Je,aF as le,aG as Qe,N as E,ak as et,o as f,c as Q,w as g,k as y,aH as S,a as r,$ as tt,P as nt,Y as R,F as M,d as a,n as at,E as c,G as D,S as st,g as ot,i as rt,j as lt,C as it,O as T,b as k,l as ie,aI as he,aJ as ye,a9 as de,ag as dt,f as ut,I as ct,v as m,x as ue,K as C,a5 as I,aj as mt,a2 as pt}from"./app-DAedBCYh.js";import{p as be}from"./paymongo.service-DA37U6Ok.js";var ft=`
    .p-drawer {
        display: flex;
        flex-direction: column;
        transform: translate3d(0px, 0px, 0px);
        position: relative;
        transition: transform 0.3s;
        background: dt('drawer.background');
        color: dt('drawer.color');
        border-style: solid;
        border-color: dt('drawer.border.color');
        box-shadow: dt('drawer.shadow');
    }

    .p-drawer-content {
        overflow-y: auto;
        flex-grow: 1;
        padding: dt('drawer.content.padding');
    }

    .p-drawer-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-shrink: 0;
        padding: dt('drawer.header.padding');
    }

    .p-drawer-footer {
        padding: dt('drawer.footer.padding');
    }

    .p-drawer-title {
        font-weight: dt('drawer.title.font.weight');
        font-size: dt('drawer.title.font.size');
    }

    .p-drawer-full .p-drawer {
        transition: none;
        transform: none;
        width: 100vw !important;
        height: 100vh !important;
        max-height: 100%;
        top: 0px !important;
        left: 0px !important;
        border-width: 1px;
    }

    .p-drawer-left .p-drawer-enter-active {
        animation: p-animate-drawer-enter-left 0.5s cubic-bezier(0.32, 0.72, 0, 1);
    }
    .p-drawer-left .p-drawer-leave-active {
        animation: p-animate-drawer-leave-left 0.5s cubic-bezier(0.32, 0.72, 0, 1);
    }

    .p-drawer-right .p-drawer-enter-active {
        animation: p-animate-drawer-enter-right 0.5s cubic-bezier(0.32, 0.72, 0, 1);
    }
    .p-drawer-right .p-drawer-leave-active {
        animation: p-animate-drawer-leave-right 0.5s cubic-bezier(0.32, 0.72, 0, 1);
    }

    .p-drawer-top .p-drawer-enter-active {
        animation: p-animate-drawer-enter-top 0.5s cubic-bezier(0.32, 0.72, 0, 1);
    }
    .p-drawer-top .p-drawer-leave-active {
        animation: p-animate-drawer-leave-top 0.5s cubic-bezier(0.32, 0.72, 0, 1);
    }

    .p-drawer-bottom .p-drawer-enter-active {
        animation: p-animate-drawer-enter-bottom 0.5s cubic-bezier(0.32, 0.72, 0, 1);
    }
    .p-drawer-bottom .p-drawer-leave-active {
        animation: p-animate-drawer-leave-bottom 0.5s cubic-bezier(0.32, 0.72, 0, 1);
    }

    .p-drawer-full .p-drawer-enter-active {
        animation: p-animate-drawer-enter-full 0.5s cubic-bezier(0.32, 0.72, 0, 1);
    }
    .p-drawer-full .p-drawer-leave-active {
        animation: p-animate-drawer-leave-full 0.5s cubic-bezier(0.32, 0.72, 0, 1);
    }
    
    .p-drawer-left .p-drawer {
        width: 20rem;
        height: 100%;
        border-inline-end-width: 1px;
    }

    .p-drawer-right .p-drawer {
        width: 20rem;
        height: 100%;
        border-inline-start-width: 1px;
    }

    .p-drawer-top .p-drawer {
        height: 10rem;
        width: 100%;
        border-block-end-width: 1px;
    }

    .p-drawer-bottom .p-drawer {
        height: 10rem;
        width: 100%;
        border-block-start-width: 1px;
    }

    .p-drawer-left .p-drawer-content,
    .p-drawer-right .p-drawer-content,
    .p-drawer-top .p-drawer-content,
    .p-drawer-bottom .p-drawer-content {
        width: 100%;
        height: 100%;
    }

    .p-drawer-open {
        display: flex;
    }

    .p-drawer-mask:dir(rtl) {
        flex-direction: row-reverse;
    }

    @keyframes p-animate-drawer-enter-left {
        from {
            transform: translate3d(-100%, 0px, 0px);
        }
    }

    @keyframes p-animate-drawer-leave-left {
        to {
            transform: translate3d(-100%, 0px, 0px);
        }
    }

    @keyframes p-animate-drawer-enter-right {
        from {
            transform: translate3d(100%, 0px, 0px);
        }
    }

    @keyframes p-animate-drawer-leave-right {
        to {
            transform: translate3d(100%, 0px, 0px);
        }
    }

    @keyframes p-animate-drawer-enter-top {
        from {
            transform: translate3d(0px, -100%, 0px);
        }
    }

    @keyframes p-animate-drawer-leave-top {
        to {
            transform: translate3d(0px, -100%, 0px);
        }
    }

    @keyframes p-animate-drawer-enter-bottom {
        from {
            transform: translate3d(0px, 100%, 0px);
        }
    }

    @keyframes p-animate-drawer-leave-bottom {
        to {
            transform: translate3d(0px, 100%, 0px);
        }
    }

    @keyframes p-animate-drawer-enter-full {
        from {
            opacity: 0;
            transform: scale(0.93);
        }
    }

    @keyframes p-animate-drawer-leave-full {
        to {
            opacity: 0;
            transform: scale(0.93);
        }
    }
`,vt={mask:function(s){var l=s.position,d=s.modal;return{position:"fixed",height:"100%",width:"100%",left:0,top:0,display:"flex",justifyContent:l==="left"?"flex-start":l==="right"?"flex-end":"center",alignItems:l==="top"?"flex-start":l==="bottom"?"flex-end":"center",pointerEvents:d?"auto":"none"}},root:{pointerEvents:"auto"}},ht={mask:function(s){var l=s.instance,d=s.props,V=["left","right","top","bottom"],i=V.find(function(P){return P===d.position});return["p-drawer-mask",{"p-overlay-mask p-overlay-mask-enter-active":d.modal,"p-drawer-open":l.containerVisible,"p-drawer-full":l.fullScreen},i?"p-drawer-".concat(i):""]},root:function(s){var l=s.instance;return["p-drawer p-component",{"p-drawer-full":l.fullScreen}]},header:"p-drawer-header",title:"p-drawer-title",pcCloseButton:"p-drawer-close-button",content:"p-drawer-content",footer:"p-drawer-footer"},yt=je.extend({name:"drawer",style:ft,classes:ht,inlineStyles:vt}),bt={name:"BaseDrawer",extends:qe,props:{visible:{type:Boolean,default:!1},position:{type:String,default:"left"},header:{type:null,default:null},baseZIndex:{type:Number,default:0},autoZIndex:{type:Boolean,default:!0},dismissable:{type:Boolean,default:!0},showCloseIcon:{type:Boolean,default:!0},closeButtonProps:{type:Object,default:function(){return{severity:"secondary",text:!0,rounded:!0}}},closeIcon:{type:String,default:void 0},modal:{type:Boolean,default:!0},blockScroll:{type:Boolean,default:!1},closeOnEscape:{type:Boolean,default:!0}},style:yt,provide:function(){return{$pcDrawer:this,$parentInstance:this}}};function G(t){"@babel/helpers - typeof";return G=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(s){return typeof s}:function(s){return s&&typeof Symbol=="function"&&s.constructor===Symbol&&s!==Symbol.prototype?"symbol":typeof s},G(t)}function ce(t,s,l){return(s=wt(s))in t?Object.defineProperty(t,s,{value:l,enumerable:!0,configurable:!0,writable:!0}):t[s]=l,t}function wt(t){var s=gt(t,"string");return G(s)=="symbol"?s:s+""}function gt(t,s){if(G(t)!="object"||!t)return t;var l=t[Symbol.toPrimitive];if(l!==void 0){var d=l.call(t,s);if(G(d)!="object")return d;throw new TypeError("@@toPrimitive must return a primitive value.")}return(s==="string"?String:Number)(t)}var me={name:"Drawer",extends:bt,inheritAttrs:!1,emits:["update:visible","show","after-show","hide","after-hide","before-hide"],data:function(){return{containerVisible:this.visible}},container:null,mask:null,content:null,headerContainer:null,footerContainer:null,closeButton:null,outsideClickListener:null,documentKeydownListener:null,watch:{dismissable:function(s){s&&!this.modal?this.bindOutsideClickListener():this.unbindOutsideClickListener()}},updated:function(){this.visible&&(this.containerVisible=this.visible)},beforeUnmount:function(){this.disableDocumentSettings(),this.mask&&this.autoZIndex&&le.clear(this.mask),this.container=null,this.mask=null},methods:{hide:function(){this.$emit("update:visible",!1)},onEnter:function(){this.$emit("show"),this.focus(),this.bindDocumentKeyDownListener(),this.autoZIndex&&le.set("modal",this.mask,this.baseZIndex||this.$primevue.config.zIndex.modal)},onAfterEnter:function(){this.enableDocumentSettings(),this.$emit("after-show")},onBeforeLeave:function(){this.modal&&!this.isUnstyled&&Qe(this.mask,"p-overlay-mask-leave-active"),this.$emit("before-hide")},onLeave:function(){this.$emit("hide")},onAfterLeave:function(){this.autoZIndex&&le.clear(this.mask),this.unbindDocumentKeyDownListener(),this.containerVisible=!1,this.disableDocumentSettings(),this.$emit("after-hide")},onMaskClick:function(s){this.dismissable&&this.modal&&this.mask===s.target&&this.hide()},focus:function(){var s=function(V){return V&&V.querySelector("[autofocus]")},l=this.$slots.header&&s(this.headerContainer);l||(l=this.$slots.default&&s(this.container),l||(l=this.$slots.footer&&s(this.footerContainer),l||(l=this.closeButton))),l&&Je(l)},enableDocumentSettings:function(){this.dismissable&&!this.modal&&this.bindOutsideClickListener(),this.blockScroll&&Ye()},disableDocumentSettings:function(){this.unbindOutsideClickListener(),this.blockScroll&&We()},onKeydown:function(s){s.code==="Escape"&&this.closeOnEscape&&this.hide()},containerRef:function(s){this.container=s},maskRef:function(s){this.mask=s},contentRef:function(s){this.content=s},headerContainerRef:function(s){this.headerContainer=s},footerContainerRef:function(s){this.footerContainer=s},closeButtonRef:function(s){this.closeButton=s?s.$el:void 0},bindDocumentKeyDownListener:function(){this.documentKeydownListener||(this.documentKeydownListener=this.onKeydown,document.addEventListener("keydown",this.documentKeydownListener))},unbindDocumentKeyDownListener:function(){this.documentKeydownListener&&(document.removeEventListener("keydown",this.documentKeydownListener),this.documentKeydownListener=null)},bindOutsideClickListener:function(){var s=this;this.outsideClickListener||(this.outsideClickListener=function(l){s.isOutsideClicked(l)&&s.hide()},document.addEventListener("click",this.outsideClickListener,!0))},unbindOutsideClickListener:function(){this.outsideClickListener&&(document.removeEventListener("click",this.outsideClickListener,!0),this.outsideClickListener=null)},isOutsideClicked:function(s){return this.container&&!this.container.contains(s.target)}},computed:{fullScreen:function(){return this.position==="full"},closeAriaLabel:function(){return this.$primevue.config.locale.aria?this.$primevue.config.locale.aria.close:void 0},dataP:function(){return Ze(ce(ce(ce({"full-screen":this.position==="full"},this.position,this.position),"open",this.containerVisible),"modal",this.modal))}},directives:{focustrap:Xe},components:{Button:He,Portal:Ge,TimesIcon:Te}},kt=["data-p"],xt=["role","aria-modal","data-p"];function Ct(t,s,l,d,V,i){var P=E("Button"),F=E("Portal"),N=et("focustrap");return f(),Q(F,null,{default:g(function(){return[V.containerVisible?(f(),y("div",S({key:0,ref:i.maskRef,onMousedown:s[0]||(s[0]=function(){return i.onMaskClick&&i.onMaskClick.apply(i,arguments)}),class:t.cx("mask"),style:t.sx("mask",!0,{position:t.position,modal:t.modal}),"data-p":i.dataP},t.ptm("mask")),[r(tt,S({name:"p-drawer",onEnter:i.onEnter,onAfterEnter:i.onAfterEnter,onBeforeLeave:i.onBeforeLeave,onLeave:i.onLeave,onAfterLeave:i.onAfterLeave,appear:""},t.ptm("transition")),{default:g(function(){return[t.visible?nt((f(),y("div",S({key:0,ref:i.containerRef,class:t.cx("root"),style:t.sx("root"),role:t.modal?"dialog":"complementary","aria-modal":t.modal?!0:void 0,"data-p":i.dataP},t.ptmi("root")),[t.$slots.container?R(t.$slots,"container",{key:0,closeCallback:i.hide}):(f(),y(M,{key:1},[a("div",S({ref:i.headerContainerRef,class:t.cx("header")},t.ptm("header")),[R(t.$slots,"header",{class:at(t.cx("title"))},function(){return[t.header?(f(),y("div",S({key:0,class:t.cx("title")},t.ptm("title")),c(t.header),17)):D("",!0)]}),t.showCloseIcon?R(t.$slots,"closebutton",{key:0,closeCallback:i.hide},function(){return[r(P,S({ref:i.closeButtonRef,type:"button",class:t.cx("pcCloseButton"),"aria-label":i.closeAriaLabel,unstyled:t.unstyled,onClick:i.hide},t.closeButtonProps,{pt:t.ptm("pcCloseButton"),"data-pc-group-section":"iconcontainer"}),{icon:g(function(L){return[R(t.$slots,"closeicon",{},function(){return[(f(),Q(st(t.closeIcon?"span":"TimesIcon"),S({class:[t.closeIcon,L.class]},t.ptm("pcCloseButton").icon),null,16,["class"]))]})]}),_:3},16,["class","aria-label","unstyled","onClick","pt"])]}):D("",!0)],16),a("div",S({ref:i.contentRef,class:t.cx("content")},t.ptm("content")),[R(t.$slots,"default")],16),t.$slots.footer?(f(),y("div",S({key:0,ref:i.footerContainerRef,class:t.cx("footer")},t.ptm("footer")),[R(t.$slots,"footer")],16)):D("",!0)],64))],16,xt)),[[N]]):D("",!0)]}),_:3},16,["onEnter","onAfterEnter","onBeforeLeave","onLeave","onAfterLeave"])],16,kt)):D("",!0)]}),_:3})}me.render=Ct;const _t={class:"space-y-4"},Vt={class:"flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"},Pt={class:"flex items-start justify-between gap-3"},Lt={class:"mt-1 text-sm font-semibold text-slate-900"},St={class:"text-sm text-slate-600"},It={class:"text-sm text-slate-600"},Dt={key:2,class:"grid grid-cols-1 gap-4 lg:grid-cols-3"},$t={class:"space-y-3"},At={class:"space-y-3"},Bt={key:3,class:"grid grid-cols-1 gap-4 lg:grid-cols-3"},Et={class:"space-y-3"},Nt={class:"flex min-w-0 items-start gap-3"},Ot=["src","alt"],Rt={class:"min-w-0"},Mt={class:"truncate text-sm font-semibold text-slate-900"},Ft={class:"truncate text-xs text-slate-500"},Ut={class:"mt-1 text-xs text-slate-500"},zt={class:"text-right"},Kt={class:"text-sm font-semibold text-slate-900"},jt={class:"text-xs text-slate-500"},Tt={class:"space-y-3 text-sm"},Gt={class:"mt-1 text-sm font-semibold text-slate-900"},Ht={class:"rounded-lg border border-slate-200 p-3"},Xt={class:"flex flex-col gap-2 sm:flex-row"},qt={key:0,class:"mt-1 text-xs text-emerald-600"},Zt={class:"flex justify-between"},Wt={class:"flex justify-between"},Yt={class:"flex justify-between"},Jt={key:0,class:"flex justify-between"},Qt={class:"text-emerald-600"},en={class:"flex justify-between text-base font-bold"},tn={class:"space-y-3"},nn={class:"flex items-start gap-2"},an=["for"],sn={class:"text-sm font-semibold text-slate-900"},on={class:"text-xs text-slate-600"},rn={key:0,class:"space-y-2 rounded-lg border border-slate-200 p-3"},ln={class:"space-y-3"},dn={class:"flex items-center gap-2"},un=["for"],cn={class:"space-y-3 p-1"},we=120,vn=ot({layout:Ke,__name:"EcommerceCheckout",setup(t){const s=mt(),l=pt(),d=rt(),V=lt(),i=m(!1),P=m(!1),F=m(!1),N=m(!1),L=m([]),U=m([]),H=m(!1),X=m(!1),q=m(!1),ee=m(null),te=m(null),ne=m(null),ae=m(null),v=ue({visible:!1,processing:!1,phone:"",email:""}),ge=C(()=>{const n=new Date;return n.setDate(n.getDate()+5),n.toLocaleDateString("en-PH",{month:"short",day:"numeric",year:"numeric"})}),$=m([]),O=m(null),h=ue({id:0,full_name:"",contact_number:"",province:"",city:"",barangay:"",address_line:""}),p=ue({provinceId:"",cityId:"",barangayCode:""}),Z=m([]),A=m([]),B=m([]),W=m({}),z=m("cod"),pe=[{label:"Cash on Delivery (COD)",value:"cod"},{label:"GCash (PayMongo)",value:"gcash"},{label:"Credit Card",value:"card"}],K=m(""),_=m(null),se=m(0),w=C(()=>$.value.find(n=>n.id===O.value)||null),ke=C(()=>w.value?`${w.value.province}, ${w.value.city}, ${w.value.barangay}, ${w.value.address_line}`:"No shipping address selected."),xe=C(()=>_.value?_.value.discount_type==="percent"?`${_.value.discount_value}%`:`PHP ${Number(_.value.discount_value).toFixed(2)}`:""),Ce=C(()=>Z.value.map(n=>({label:n.name,value:n.province_id}))),_e=C(()=>A.value.map(n=>({label:n.name,value:n.city_id}))),Ve=C(()=>B.value.map(n=>({label:n.name,value:n.code}))),Pe=C(()=>L.value.reduce((n,e)=>n+Number(e.quantity||0),0)),oe=C(()=>L.value.reduce((n,e)=>n+Number(e.line_subtotal||0),0)),Y=C(()=>L.value.length*we),J=C(()=>se.value),fe=C(()=>Math.max(0,oe.value+Y.value-J.value));function Le(n){return pe.find(e=>e.value===n)?.label||"Cash on Delivery (COD)"}async function Se(){try{const n=await I.getAddressTemplates();$.value=n.data?.data||[],O.value=$.value.find(e=>e.is_default)?.id||$.value[0]?.id||null}catch(n){d.add({severity:"error",summary:"Address",detail:n?.response?.data?.message||"Failed to load addresses",life:2500})}}async function Ie(){if(!h.full_name||!h.contact_number||!p.provinceId||!p.cityId||!p.barangayCode||!h.address_line){d.add({severity:"warn",summary:"Incomplete",detail:"Please complete all address fields.",life:2200});return}try{const n=Z.value.find(x=>x.province_id===p.provinceId)?.name||"",e=A.value.find(x=>x.city_id===p.cityId)?.name||"",u=B.value.find(x=>x.code===p.barangayCode)?.name||"",b=await I.createAddressTemplate({full_name:h.full_name,contact_number:h.contact_number,province:n,city:e,barangay:u,address_line:h.address_line,is_default:$.value.length===0});$.value.push(b.data?.data),O.value=b.data?.data?.id||O.value,h.id=0,h.full_name="",h.contact_number="",h.province="",h.city="",h.barangay="",h.address_line="",p.provinceId="",p.cityId="",p.barangayCode="",A.value=[],B.value=[],q.value=!1,d.add({severity:"success",summary:"Address Saved",detail:"New address template has been saved.",life:1800})}catch(n){d.add({severity:"error",summary:"Address",detail:n?.response?.data?.message||"Failed to save address",life:2500})}}async function De(){const u=(await I.getCart()).data?.data?.items||[];U.value=String(l.query.item_ids||"").split(",").map(b=>Number(b.trim())).filter(b=>Number.isInteger(b)&&b>0),L.value=U.value.length?u.filter(b=>U.value.includes(b.id)):u,L.value.length||(d.add({severity:"warn",summary:"Cart Empty",detail:"Please select cart items first.",life:2500}),s.push({name:"ecommerce.cart"}))}async function $e(){if(!K.value.trim()){d.add({severity:"warn",summary:"Voucher",detail:"Enter voucher code first.",life:1800});return}F.value=!0;try{const n=await I.validateVoucher({code:K.value.trim(),amount:oe.value+Y.value}),e=n.data?.data?.voucher,u=Number(n.data?.data?.discount_amount||0);_.value={code:String(e?.code||K.value.trim().toUpperCase()),discount_type:e?.discount_type||"fixed",discount_value:Number(e?.discount_value||0)},se.value=u,d.add({severity:"success",summary:"Voucher Applied",detail:"Voucher has been applied.",life:1800})}catch(n){_.value=null,se.value=0,d.add({severity:"error",summary:"Invalid Voucher",detail:n?.response?.data?.message||"Voucher does not exist.",life:2200})}finally{F.value=!1}}function Ae(n){return n==="gcash"?"e_wallet":n==="card"?"card":"cod"}async function Be(){try{const n=await I.getProvinces();Z.value=n.data||[]}catch{Z.value=[]}}async function Ee(n){if(!n){A.value=[];return}if(W.value[n]){A.value=W.value[n];return}try{const e=await I.getCities(n);W.value[n]=e.data||[],A.value=W.value[n]}catch{A.value=[]}}async function Ne(n){if(!n){B.value=[];return}try{const e=await I.getBarangays(n);B.value=e.data||[]}catch{B.value=[]}}async function Oe(){p.cityId="",p.barangayCode="",B.value=[],await Ee(p.provinceId)}async function Re(){p.barangayCode="",await Ne(p.cityId)}async function Me(){if(!w.value){d.add({severity:"warn",summary:"Address Required",detail:"Please select a shipping address.",life:2500});return}i.value=!0;try{const n={shipping_name:w.value.full_name,shipping_phone:w.value.contact_number,shipping_email:V.user?.email||void 0,shipping_address:`${w.value.province}, ${w.value.city}, ${w.value.barangay}, ${w.value.address_line}`,payment_method:Ae(z.value),shipping_fee:Y.value,discount_amount:J.value,voucher_code:_.value?.code,notes:_.value?`Voucher: ${_.value.code}`:"",item_ids:U.value.length?U.value:void 0,customer_latitude:ne.value??void 0,customer_longitude:ae.value??void 0},e=await I.checkout(n),u=e.data?.data?.id,b=Number(e.data?.data?.store_id||L.value?.[0]?.store_id||0);if(z.value==="gcash"){if(!u||!b)throw new Error("Order ID or store ID is missing for PayMongo checkout.");P.value=!0;const j=(await be.createIntent({amount:Math.max(Math.round(fe.value*100),1),payment_method_allowed:["gcash"],store_id:b,payable_type:"ecommerce_order",payable_id:Number(u)}))?.data?.data?.id;if(!j)throw new Error("Failed to initialize PayMongo payment intent.");ee.value=String(j),te.value=Number(u),v.phone=ve(w.value.contact_number||""),v.email=V.user?.email||n.shipping_email||"",v.visible=!0,d.add({severity:"success",summary:"Order Placed",detail:"Enter GCash details to continue payment.",life:2500});return}d.add({severity:"success",summary:"Order Placed",detail:"Your order was created successfully.",life:2500}),s.push({name:"ecommerce.orders",query:{placed:u}})}catch(n){d.add({severity:"error",summary:"Checkout Failed",detail:n?.response?.data?.message||"Please try again.",life:3e3})}finally{i.value=!1,P.value=!1}}function ve(n){const e=String(n||"").replace(/\D/g,"");return e?e.startsWith("63")&&e.length>=12?`0${e.slice(2,12)}`:e.length>11?e.slice(e.length-11):e:""}async function Fe(){if(!ee.value||!te.value){d.add({severity:"warn",summary:"Missing Payment Context",detail:"Please place the order again.",life:2500});return}const n=ve(v.phone);if(!/^09\d{9}$/.test(n)){d.add({severity:"warn",summary:"Invalid Number",detail:"Use an 11-digit GCash number (09XXXXXXXXX).",life:2500});return}if(!v.email.trim()){d.add({severity:"warn",summary:"Email Required",detail:"Please provide receipt email.",life:2500});return}v.processing=!0,P.value=!0;try{const u=(await be.startGcash(ee.value,{name:w.value?.full_name||"Customer",email:v.email.trim(),phone:n,return_url:`${window.location.origin}/shop/orders/${te.value}`}))?.data?.redirect_url;if(!u)throw new Error("PayMongo did not return a checkout URL.");window.location.href=u}catch(e){d.add({severity:"error",summary:"Checkout Failed",detail:e?.response?.data?.message||"Unable to start GCash checkout.",life:3200})}finally{v.processing=!1,P.value=!1}}function Ue(){s.push({name:"ecommerce.cart"})}return it(async()=>{navigator.geolocation&&navigator.geolocation.getCurrentPosition(n=>{ne.value=Number(n.coords.latitude),ae.value=Number(n.coords.longitude)},()=>{ne.value=null,ae.value=null},{enableHighAccuracy:!1,maximumAge:12e4,timeout:5e3}),N.value=!0;try{await Be(),await Se(),await De()}finally{N.value=!1}}),(n,e)=>{const u=E("Button"),b=E("Skeleton"),x=E("Card"),j=E("Divider"),ze=E("Message");return f(),y("div",_t,[a("div",Vt,[e[20]||(e[20]=a("div",null,[a("h1",{class:"text-2xl font-bold text-gray-900"},"Checkout")],-1)),r(u,{label:"Back to Cart",icon:"pi pi-arrow-left",severity:"secondary",class:"w-full sm:w-auto",onClick:Ue})]),N.value?(f(),Q(x,{key:0,class:"border border-slate-200 shadow-none"},{content:g(()=>[r(b,{height:"4rem"})]),_:1})):(f(),Q(x,{key:1,class:"cursor-pointer border border-slate-200 shadow-none",onClick:e[0]||(e[0]=o=>H.value=!0)},{content:g(()=>[a("div",Pt,[a("div",null,[e[21]||(e[21]=a("p",{class:"text-xs font-semibold uppercase tracking-wide text-slate-500"},"Shipping Address",-1)),a("p",Lt,c(w.value?.full_name||"Select address template"),1),a("p",St,c(w.value?.contact_number||"-"),1),a("p",It,c(ke.value),1)]),r(u,{icon:"pi pi-chevron-right",text:"",severity:"secondary"})])]),_:1})),N.value?(f(),y("div",Dt,[r(x,{class:"border border-slate-200 shadow-none lg:col-span-2"},{content:g(()=>[a("div",$t,[(f(),y(M,null,T(3,o=>r(b,{key:`checkout-left-${o}`,height:"5rem"})),64))])]),_:1}),r(x,{class:"border border-slate-200 shadow-none"},{content:g(()=>[a("div",At,[(f(),y(M,null,T(6,o=>r(b,{key:`checkout-right-${o}`,height:"1.1rem"})),64)),r(b,{height:"2.5rem"})])]),_:1})])):(f(),y("div",Bt,[r(x,{class:"border border-slate-200 shadow-none lg:col-span-2"},{content:g(()=>[a("div",Et,[(f(!0),y(M,null,T(L.value,o=>(f(),y("div",{key:o.id,class:"flex items-start justify-between gap-3 rounded-lg border border-slate-200 p-3"},[a("div",Nt,[a("img",{src:o.image||"/F.svg",alt:o.product_name,class:"h-14 w-14 rounded-lg border border-slate-200 object-cover"},null,8,Ot),a("div",Rt,[e[22]||(e[22]=a("p",{class:"text-xs font-medium text-slate-500"},"Furni Shop",-1)),a("p",Mt,c(o.product_name),1),a("p",Ft,"Variant: "+c(o.sku||"Standard"),1),a("p",Ut,"Delivery: PHP "+c(we.toFixed(2))+" - "+c(ge.value),1)])]),a("div",zt,[a("p",Kt,"PHP "+c(Number(o.unit_price).toFixed(2)),1),a("p",jt,"Qty "+c(o.quantity),1)])]))),128))])]),_:1}),r(x,{class:"border border-slate-200 shadow-none"},{content:g(()=>[a("div",Tt,[a("div",null,[e[23]||(e[23]=a("p",{class:"text-xs font-semibold uppercase tracking-wide text-slate-500"},"Payment Method",-1)),a("p",Gt,c(Le(z.value)),1),r(u,{label:"View all payment methods",size:"small",link:"",severity:"info",onClick:e[1]||(e[1]=o=>X.value=!0)})]),a("div",Ht,[e[24]||(e[24]=a("label",{class:"mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500"},"Voucher",-1)),a("div",Xt,[r(k(ie),{modelValue:K.value,"onUpdate:modelValue":e[2]||(e[2]=o=>K.value=o),fluid:"",placeholder:"Enter voucher code"},null,8,["modelValue"]),r(u,{label:"Apply",size:"small",severity:"info",class:"w-full sm:w-auto",loading:F.value,onClick:$e},null,8,["loading"])]),_.value?(f(),y("p",qt," Applied: "+c(_.value.code)+" ("+c(xe.value)+") ",1)):D("",!0)]),r(j),a("div",Zt,[e[25]||(e[25]=a("span",null,"Items",-1)),a("span",null,c(Pe.value),1)]),a("div",Wt,[e[26]||(e[26]=a("span",null,"Subtotal",-1)),a("span",null,"PHP "+c(oe.value.toFixed(2)),1)]),a("div",Yt,[e[27]||(e[27]=a("span",null,"Shipping Fee",-1)),a("span",null,"PHP "+c(Y.value.toFixed(2)),1)]),J.value>0?(f(),y("div",Jt,[e[28]||(e[28]=a("span",null,"Voucher Discount",-1)),a("span",Qt,"- PHP "+c(J.value.toFixed(2)),1)])):D("",!0),r(j),a("div",en,[e[29]||(e[29]=a("span",null,"Total",-1)),a("span",null,"PHP "+c(fe.value.toFixed(2)),1)]),r(u,{label:"Place Order",severity:"info",class:"mt-2 w-full",loading:i.value||P.value,onClick:Me},null,8,["loading"])])]),_:1})])),r(k(me),{visible:H.value,"onUpdate:visible":e[12]||(e[12]=o=>H.value=o),header:"Shipping Address Templates",position:"right",class:"w-full sm:w-[30rem] lg:!w-[30rem]"},{default:g(()=>[a("div",tn,[r(u,{label:"Add New Address",text:"",severity:"secondary",onClick:e[3]||(e[3]=o=>q.value=!q.value)}),(f(!0),y(M,null,T($.value,o=>(f(),y("div",{key:o.id,class:"rounded-lg border border-slate-200 p-3"},[a("div",nn,[r(k(he),{modelValue:O.value,"onUpdate:modelValue":e[4]||(e[4]=re=>O.value=re),inputId:`address-${o.id}`,value:o.id},null,8,["modelValue","inputId","value"]),a("label",{for:`address-${o.id}`,class:"cursor-pointer"},[a("p",sn,c(o.full_name)+" - "+c(o.contact_number),1),a("p",on,c(o.province)+", "+c(o.city)+", "+c(o.barangay)+", "+c(o.address_line),1)],8,an)])]))),128)),q.value?(f(),y("div",rn,[r(k(ie),{modelValue:h.full_name,"onUpdate:modelValue":e[5]||(e[5]=o=>h.full_name=o),fluid:"",placeholder:"Full name"},null,8,["modelValue"]),r(k(ye),{mask:"9999-999-9999",modelValue:h.contact_number,"onUpdate:modelValue":e[6]||(e[6]=o=>h.contact_number=o),fluid:"",placeholder:"0912-456-7890"},null,8,["modelValue"]),r(k(de),{modelValue:p.provinceId,"onUpdate:modelValue":e[7]||(e[7]=o=>p.provinceId=o),options:Ce.value,optionLabel:"label",optionValue:"value",fluid:"",placeholder:"Select Province",onChange:Oe},null,8,["modelValue","options"]),r(k(de),{modelValue:p.cityId,"onUpdate:modelValue":e[8]||(e[8]=o=>p.cityId=o),options:_e.value,optionLabel:"label",optionValue:"value",fluid:"",placeholder:"Select City",disabled:!p.provinceId,onChange:Re},null,8,["modelValue","options","disabled"]),r(k(de),{modelValue:p.barangayCode,"onUpdate:modelValue":e[9]||(e[9]=o=>p.barangayCode=o),options:Ve.value,optionLabel:"label",optionValue:"value",fluid:"",placeholder:"Select Barangay",disabled:!p.cityId},null,8,["modelValue","options","disabled"]),r(k(dt),{modelValue:h.address_line,"onUpdate:modelValue":e[10]||(e[10]=o=>h.address_line=o),rows:"2",fluid:"",placeholder:"Address line"},null,8,["modelValue"]),r(u,{label:"Save Address Template",severity:"info",onClick:Ie})])):D("",!0),r(u,{label:"Use Selected Address",severity:"info",fluid:"",onClick:e[11]||(e[11]=o=>H.value=!1)})])]),_:1},8,["visible"]),r(k(me),{visible:X.value,"onUpdate:visible":e[15]||(e[15]=o=>X.value=o),header:"All Payment Methods",position:"right",class:"w-full sm:w-[26rem]"},{default:g(()=>[a("div",ln,[(f(),y(M,null,T(pe,o=>a("div",{key:o.value,class:"rounded-lg border border-slate-200 p-3"},[a("div",dn,[r(k(he),{modelValue:z.value,"onUpdate:modelValue":e[13]||(e[13]=re=>z.value=re),inputId:`payment-${o.value}`,value:o.value},null,8,["modelValue","inputId","value"]),a("label",{for:`payment-${o.value}`,class:"cursor-pointer text-sm text-slate-800"},c(o.label),9,un)])])),64)),r(u,{label:"Use Payment Method",severity:"info",fluid:"",onClick:e[14]||(e[14]=o=>X.value=!1)})])]),_:1},8,["visible"]),r(k(ct),{visible:v.visible,"onUpdate:visible":e[19]||(e[19]=o=>v.visible=o),modal:"",draggable:!1,closable:!v.processing,class:"w-full max-w-md",pt:{root:{class:"overflow-hidden"}}},{header:g(()=>[...e[30]||(e[30]=[a("div",{class:"flex w-full items-center gap-2 rounded-t-lg bg-blue-600 px-3 py-2 text-white"},[a("i",{class:"pi pi-wallet text-sm"}),a("span",{class:"text-sm font-semibold"},"GCash Checkout Details")],-1)])]),footer:g(()=>[r(u,{label:"Cancel",severity:"secondary",outlined:"",disabled:v.processing,onClick:e[18]||(e[18]=o=>v.visible=!1)},null,8,["disabled"]),r(u,{label:"Continue to PayMongo",severity:"info",loading:v.processing,disabled:v.processing,onClick:Fe},null,8,["loading","disabled"])]),default:g(()=>[a("div",cn,[r(ze,{severity:"info",closable:!1},{default:g(()=>[...e[31]||(e[31]=[ut(" Enter your GCash number and receipt email before redirecting to PayMongo. ",-1)])]),_:1}),a("div",null,[e[32]||(e[32]=a("label",{class:"mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500"},"GCash Number",-1)),r(k(ye),{modelValue:v.phone,"onUpdate:modelValue":e[16]||(e[16]=o=>v.phone=o),mask:"09999999999",fluid:"",placeholder:"09999999999",autoClear:!1},null,8,["modelValue"])]),a("div",null,[e[33]||(e[33]=a("label",{class:"mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500"},"Email",-1)),r(k(ie),{modelValue:v.email,"onUpdate:modelValue":e[17]||(e[17]=o=>v.email=o),type:"email",fluid:"",placeholder:"name@example.com"},null,8,["modelValue"])])])]),_:1},8,["visible","closable"])])}}});export{vn as default};
