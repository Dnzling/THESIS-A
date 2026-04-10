<template>
  <div class="p-6 min-h-screen">
    <div class="mb-6 flex justify-between items-center">
      <div>
        <h1 class="text-xl font-bold text-gray-800">Create Supplier Contract</h1>
      </div>
    </div>

    <form @submit.prevent="submitForm" class="max-w-4xl mx-auto">

      <!-- Section 1: Supplier & Contract Basic Info -->
      <Card class="mb-6">
        <template #title>
          <div class="flex items-center gap-3">
            <span>Supplier & Contract Information</span>
          </div>
        </template>

        <template #content>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Store Name</label>
              <InputText :modelValue="selectedStoreName" class="w-full" disabled />
            </div>

            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Contract Title</label>
              <InputText v-model="form.contract_title" placeholder="e.g., Annual Furniture Supply Agreement 2026" class="w-full" />
              <small class="text-gray-500">Auto-generated based on supplier and dates. You can edit if needed.</small>
              <small v-if="errors.contract_title" class="text-red-600">{{ errors.contract_title }}</small>
            </div>

            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Contract Type <span class="text-red-500">*</span></label>
              <Select v-model="form.contract_type" :options="contractTypes" optionLabel="label" optionValue="value"
                placeholder="Select Contract Type" class="w-full" />
              <small v-if="errors.contract_type" class="text-red-600">{{ errors.contract_type }}</small>
              <small class="text-gray-600 mt-2" v-if="contractTypeDescription">{{ contractTypeDescription }}</small>
            </div>

            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Contract Duration <span class="text-red-500">*</span></label>
              <DatePicker
                v-model="contractRange"
                selectionMode="range"
                date-format="yy-mm-dd" fluid
                placeholder="Select start and end date"
                :min-date="new Date()"
                class="w-full"
              />
              <small v-if="errors.start_date || errors.end_date" class="text-red-600">{{ errors.start_date || errors.end_date }}</small>
              <small v-else-if="form.start_date && form.end_date" class="text-green-600">
                Duration: {{ contractDurationDays }} days
              </small>
            </div>
          </div>
        </template>
      </Card>

      <!-- Section 3: Financial Terms -->
      <Card class="mb-6">
        <template #title>
          <div class="flex items-center gap-3">
            <span>Financial Terms</span>
          </div>
        </template>

        <template #content>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Discount Percentage <span class="text-red-500">*</span></label>
              <InputNumber v-model="form.discount_percentage" :min="0" :max="100" placeholder="0" 
                suffix="%" class="w-full" />
              <small v-if="errors.discount_percentage" class="text-red-600">{{ errors.discount_percentage }}</small>
            </div>

            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Tax Rate (%)</label>
              <InputNumber v-model="form.tax_rate" :min="0" :max="100" placeholder="0" suffix="%" class="w-full" />
              <small v-if="errors.tax_rate" class="text-red-600">{{ errors.tax_rate }}</small>
            </div>
          </div>

          <Divider class="my-6" />

          <div class="p-4 bg-blue-50 border border-blue-200 rounded">
            <p class="text-sm text-blue-900">
              <i class="pi pi-info-circle mr-2"></i>
              <strong>Summary:</strong> {{ form.discount_percentage }}% discount, tax {{ form.tax_rate }}%
            </p>
          </div>
        </template>
      </Card>

      <!-- Section 4: Terms & Conditions -->
      <Card class="mb-6">
        <template #title>
          <div class="flex items-center gap-3">
            <i class="pi pi-file-edit text-2xl text-purple-600"></i>
            <span>Terms & Conditions</span>
          </div>
        </template>

        <template #content>
          <div class="flex flex-col gap-2">
            <label class="text-sm font-semibold text-gray-700">Terms & Conditions</label>
            <Textarea v-model="form.terms_conditions" rows="6" 
              placeholder="Enter contract terms and conditions here..." class="w-full" />
            <small class="text-gray-600">{{ form.terms_conditions?.length || 0 }} / 5000 characters</small>
          </div>
        </template>
      </Card>

      <!-- Section 5: Document Attachment -->
      <Card class="mb-6">
        <template #title>
          <div class="flex items-center gap-3">
            <i class="pi pi-file text-2xl text-red-600"></i>
            <span>Contract Document (Optional)</span>
          </div>
        </template>

        <template #content>
          <FileUpload v-if="!contractFile" name="contract_file" 
            @select="onFileSelect" 
            :multiple="false" 
            accept=".pdf,.doc,.docx"
            :showUploadButton="false"
            :showCancelButton="false"
            chooseLabel="Select PDF or Document"
            class="w-full" />
          
          <div v-else class="p-4 bg-green-50 border border-green-200 rounded flex items-center justify-between">
            <div class="flex items-center gap-3">
              <i class="pi pi-file-pdf text-2xl text-red-600"></i>
              <div>
                <p class="font-semibold text-green-900">{{ contractFile.name }}</p>
                <p class="text-sm text-gray-600">{{ formatFileSize(contractFile.size) }}</p>
              </div>
            </div>
            <Button icon="pi pi-times" text rounded severity="danger" @click="contractFile = null" />
          </div>
        </template>
      </Card>

      <!-- Action Buttons -->
      <div class="flex gap-3 justify-end">
        <Button 
          :label="route.query.supplier_id ? 'Skip for Now' : 'Cancel'" 
          severity="secondary" 
          @click="skipOrCancel()" />
        <Button label="Save as Draft" severity="warning" icon="pi pi-save" @click="saveDraft" :loading="submitting" />
        <Button label="Review & Submit" severity="success" icon="pi pi-eye" @click="openReviewDialog" :loading="submitting" />
      </div>
    </form>

    <Dialog v-model:visible="showReviewDialog" modal header="Review Contract" :style="{ width: '70rem' }">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="space-y-2 text-sm">
          <div><b>Store:</b> {{ selectedStoreName }}</div>
          <div><b>Supplier:</b> {{ selectedSupplierName }}</div>
          <div><b>Title:</b> {{ form.contract_title }}</div>
          <div><b>Type:</b> {{ form.contract_type }}</div>
          <div><b>Date Range:</b> {{ form.start_date ? form.start_date.toISOString().split('T')[0] : '-' }} to {{ form.end_date ? form.end_date.toISOString().split('T')[0] : '-' }}</div>
          <div><b>Discount:</b> {{ form.discount_percentage }}%</div>
          <div><b>Tax Rate:</b> {{ form.tax_rate }}%</div>
        </div>
        <iframe class="w-full h-[420px] border rounded" :srcdoc="reviewHtml"></iframe>
      </div>
      <template #footer>
        <div class="flex justify-end gap-2">
          <Button label="Close" severity="secondary" @click="showReviewDialog = false" />
          <Button label="Submit Contract" severity="success" icon="pi pi-check" @click="submitForm" :loading="submitting" />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import procurementService from '../../../../services/procurement.service'

const router = useRouter()
const route = useRoute()
const toast = useToast()
const loading = ref(false)
const submitting = ref(false)
const suppliers = ref<any[]>([])
const contractFile = ref<File | null>(null)
const selectedSupplierName = ref<string>('Not Selected')
const selectedStoreName = ref<string>('Not Selected')
const showReviewDialog = ref(false)
const titleManuallyEdited = ref(false)

const form = reactive({
  store_id: null,
  supplier_id: null,
  contract_title: '',
  contract_type: 'supply',
  start_date: null,
  end_date: null,
  discount_percentage: 0,
  payment_terms_days: 30,
  tax_rate: 0,
  terms_conditions: '',
  contract_file_path: null,
})

const errors = reactive<Record<string, string>>({})

const contractTypes = [
  { label: 'Supply', value: 'supply', description: 'For product/material supply agreements' },
  { label: 'Service', value: 'service', description: 'For service-based agreements' },
]

const contractTypeDescription = computed(() => {
  const type = contractTypes.find(t => t.value === form.contract_type)
  return type?.description || ''
})

const formProgress = computed(() => {
  let filled = 0
  let total = 8
  
  if (form.supplier_id) filled++
  if (form.contract_title) filled++
  if (form.contract_type) filled++
  if (form.start_date) filled++
  if (form.end_date) filled++
  if (form.discount_percentage) filled++
  if (form.tax_rate !== null) filled++
  if (form.terms_conditions) filled++
  
  return Math.round((filled / total) * 100)
})

const contractDurationDays = computed(() => {
  if (!form.start_date || !form.end_date) return 0
  const start = new Date(form.start_date)
  const end = new Date(form.end_date)
  return Math.ceil((end.getTime() - start.getTime()) / (1000 * 3600 * 24))
})

const paymentDueDate = computed(() => {
  if (!form.start_date || !form.payment_terms_days) return 'N/A'
  const dueDate = new Date(form.start_date)
  dueDate.setDate(dueDate.getDate() + form.payment_terms_days)
  return dueDate.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
})

const contractRange = computed({
  get: () => {
    if (!form.start_date && !form.end_date) return null
    return [form.start_date, form.end_date].filter(Boolean)
  },
  set: (value: any) => {
    if (!Array.isArray(value) || value.length === 0) {
      form.start_date = null
      form.end_date = null
      return
    }
    const a = value[0] || null
    const b = value[1] || null
    // PrimeVue range can return the dates in the order the user clicked; normalize so start <= end.
    if (a && b) {
      const da = new Date(a)
      const db = new Date(b)
      if (da.getTime() <= db.getTime()) {
        form.start_date = a
        form.end_date = b
      } else {
        form.start_date = b
        form.end_date = a
      }
      return
    }
    form.start_date = a
    form.end_date = b
  },
})

const reviewHtml = computed(() => {
  const start = form.start_date ? form.start_date.toISOString().split('T')[0] : '-'
  const end = form.end_date ? form.end_date.toISOString().split('T')[0] : '-'
  
  return `
  <!doctype html>
  <html lang="en">
  <head>
      <meta charset="UTF-8">
      <title>Store-Supplier Agreement - ${form.contract_number || 'DRAFT'}</title>
      <style>
          /* BARABARA font for FurniSync platform name only */
          @font-face {
              font-family: 'Barabara';
              src: url('/resources/fonts/BARABARA-final.otf') format('opentype');
              font-weight: normal;
              font-style: normal;
          }
          
          /* Professional contract styling */
          * {
              margin: 0;
              padding: 0;
              box-sizing: border-box;
          }
          
          body {
              font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif;
              font-size: 10pt;
              line-height: 1.5;
              color: #1a1a1a;
              background: white;
              margin: 0;
              padding: 1.5cm 1.8cm;
          }
          
          .contract-header {
              text-align: center;
              margin-bottom: 20px;
              padding-bottom: 12px;
              border-bottom: 2px solid #2c3e50;
          }
          
          .platform-name {
              font-family: 'Barabara', 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif;
              font-size: 32pt;
              font-weight: normal;
              letter-spacing: 2px;
              color: #1a5276;
              margin-bottom: 4px;
              line-height: 1.2;
          }
          
          .document-title {
              font-size: 16pt;
              font-weight: 700;
              text-transform: uppercase;
              letter-spacing: 2px;
              margin-top: 8px;
          }
          
          .document-subtitle {
              font-size: 9pt;
              color: #5d6d7e;
              margin-top: 4px;
          }
          
          .agreement-date {
              text-align: right;
              margin: 15px 0 20px;
              font-size: 9pt;
          }
          
          .parties-section {
              margin: 20px 0;
              padding: 10px 0;
              border-top: 1px solid #ccc;
              border-bottom: 1px solid #ccc;
          }
          
          .party-block {
              margin: 12px 0;
          }
          
          .party-label {
              font-weight: 700;
              text-decoration: underline;
              margin-bottom: 6px;
          }
          
          .facilitator-note {
              margin: 15px 0;
              padding: 10px;
              background: #f8f9fa;
              font-size: 9pt;
              text-align: center;
              border-left: 3px solid #1a5276;
          }
          
          .clause {
              margin: 14px 0;
          }
          
          .clause-number {
              font-weight: 700;
              display: inline-block;
              width: 30px;
          }
          
          .clause-title {
              font-weight: 700;
              display: inline-block;
              text-transform: uppercase;
              font-size: 9.5pt;
          }
          
          .clause-content {
              margin-left: 30px;
              margin-top: 4px;
              text-align: justify;
          }
          
          .contract-table {
              width: 100%;
              border-collapse: collapse;
              margin: 12px 0;
              font-size: 9pt;
          }
          
          .contract-table th,
          .contract-table td {
              border: 1px solid #999;
              padding: 8px 10px;
              vertical-align: top;
          }
          
          .contract-table th {
              background: #f0f3f5;
              font-weight: 700;
              text-align: left;
              width: 25%;
          }
          
          .commercial-table {
              width: 100%;
              border-collapse: collapse;
              margin: 12px 0;
          }
          
          .commercial-table th,
          .commercial-table td {
              border: 1px solid #999;
              padding: 8px 10px;
              vertical-align: top;
          }
          
          .commercial-table th {
              background: #f0f3f5;
              font-weight: 700;
              width: 33%;
          }
          
          /* Terms and Conditions styling */
          .terms-section {
              margin: 15px 0;
              padding: 12px;
              background: #fef9e6;
              border-left: 4px solid #f39c12;
              font-size: 9pt;
          }
          
          .terms-title {
              font-weight: 700;
              font-size: 10pt;
              margin-bottom: 10px;
              color: #e67e22;
          }
          
          .terms-content {
              white-space: pre-wrap;
              line-height: 1.5;
          }
          
          .signature-section {
              margin-top: 35px;
              page-break-inside: avoid;
          }
          
          .signature-grid {
              width: 100%;
              border-collapse: collapse;
              margin-top: 20px;
          }
          
          .signature-grid td {
              width: 50%;
              padding: 30px 15px 0 15px;
              vertical-align: bottom;
          }
          
          .signature-line {
              border-top: 1px solid #1a1a1a;
              margin-top: 8px;
              width: 100%;
          }
          
          .signature-label {
              font-size: 8pt;
              color: #5d6d7e;
              margin-top: 5px;
          }
          
          .footer {
              margin-top: 35px;
              padding-top: 12px;
              border-top: 1px solid #ccc;
              font-size: 7pt;
              text-align: center;
              color: #7f8c8d;
          }
      </style>
  </head>
  <body>
      <div class="contract-header">
          <div class="platform-name">FurniSync</div>
          <div class="document-title">STORE-SUPPLIER AGREEMENT</div>
          <div class="document-subtitle">Facilitated by FurniSync IMS Platform</div>
      </div>

      <div class="agreement-date">
          <strong>Date of Issue:</strong> ${new Date().toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' })}<br>
          <strong>Agreement Reference:</strong> ${form.contract_number || 'DRAFT-' + Date.now()}
      </div>

      <!-- PARTIES - Only Store and Supplier -->
      <div class="parties-section">
          <div class="party-block">
              <div class="party-label">THIS AGREEMENT (the "Agreement") is made and entered into by and between:</div>
              <div style="margin-top: 8px;">
                  <strong>(1) THE STORE:</strong> ${selectedStoreName.value || '[Store Name]'} (hereinafter referred to as the "<strong>Store</strong>");
              </div>
          </div>
          <div class="party-block">
              <div style="margin-top: 12px;">
                  <strong>(2) THE SUPPLIER:</strong> ${selectedSupplierName.value || '[Supplier Name]'} (hereinafter referred to as the "<strong>Supplier</strong>").
              </div>
          </div>
          <div class="party-block" style="margin-top: 12px; font-style: italic;">
              The Store and the Supplier are each referred to individually as a "<strong>Party</strong>" and collectively as the "<strong>Parties</strong>."
          </div>
      </div>

      <!-- FACILITATOR NOTE - FurniSync as middle man -->
      <div class="facilitator-note">
          <strong>Facilitator:</strong> This Agreement is facilitated through the FurniSync IMS Platform. FurniSync acts solely as a technology platform and intermediary. FurniSync is NOT a party to this Agreement and shall have no liability or obligations under the terms set forth herein.
      </div>

      <!-- RECITALS -->
      <div class="clause">
          <div class="clause-number"></div>
          <div class="clause-title">WHEREAS:</div>
          <div class="clause-content">
              The Store operates a retail business and wishes to purchase products from the Supplier. The Supplier wishes to supply products to the Store under the terms and conditions set forth herein. The Parties agree to use the FurniSync IMS Platform to facilitate their transactions.
          </div>
      </div>

      <!-- AGREEMENT DETAILS TABLE -->
      <div class="clause">
          <div class="clause-number">1.</div>
          <div class="clause-title">Agreement Details</div>
          <div class="clause-content">
              <table class="contract-table">
                  <tr>
                      <th>Agreement Number</th>
                      <td>${form.contract_number || 'PENDING'}</td>
                      <th>Agreement Type</th>
                      <td>${(form.contract_type || 'standard').toUpperCase()}</td>
                  </tr>
                  <tr>
                      <th>Agreement Title</th>
                      <td colspan="3">${form.contract_title || 'Supply Agreement'}</td>
                  </tr>
                  <tr>
                      <th>Effective Date</th>
                      <td>${start || 'Not Specified'}</td>
                      <th>Expiration Date</th>
                      <td>${end || 'Not Specified'}</td>
                  </tr>
                
              </table>
          </div>
      </div>

      <!-- COMMERCIAL TERMS - Discount from Supplier to Store (No Payment Terms) -->
      <div class="clause">
          <div class="clause-number">2.</div>
          <div class="clause-title">Commercial Terms</div>
          <div class="clause-content">
              <table class="commercial-table">
                  <tr>
                      <th>Discount</th>
                      <td>The Supplier agrees to grant the Store a <strong>${form.discount_percentage || 0}% discount</strong> on all products purchased under this Agreement.<br>
                      <span style="font-size:8pt;">(This discount shall be applied to the Supplier's standard retail price)</span>
                  </td>
                  </tr>
                  <tr>
                      <th>Tax</th>
                      <td>A tax rate of <strong>${form.tax_rate || 0}%</strong> shall apply to all transactions in accordance with Philippine tax laws (VAT).</td>
                  </tr>
                  <tr>
                      <th>Minimum Order Value</th>
                      <td>${form.minimum_order_value ? '₱' + parseFloat(form.minimum_order_value).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) : 'None - No minimum order required'}</td>
                  </tr>
              </table>
          </div>
      </div>

      <!-- SUPPLIER'S TERMS AND CONDITIONS -->
      <div class="clause">
          <div class="clause-number">3.</div>
          <div class="clause-title">Supplier's Terms and Conditions</div>
          <div class="clause-content">
              <div class="terms-section">
                  <div class="terms-title">📋 TERMS & CONDITIONS PROVIDED BY SUPPLIER</div>
                  <div class="terms-content">
                      ${form.terms_conditions ? 
                          form.terms_conditions.replace(/\n/g, '<br>') : 
                          '<em>No specific terms and conditions provided by the supplier. Standard terms shall apply.</em>'}
                  </div>
              </div>
          </div>
      </div>

      <!-- STANDARD TERMS -->
      <div class="clause">
          <div class="clause-number">4.</div>
          <div class="clause-title">Term</div>
          <div class="clause-content">
              This Agreement shall take effect on ${start || 'the Effective Date'} and shall remain in force until ${end || 'the Expiration Date'}, unless earlier terminated in accordance with Section 5 below.
          </div>
      </div>

      <div class="clause">
          <div class="clause-number">5.</div>
          <div class="clause-title">Termination</div>
          <div class="clause-content">
              Either Party may terminate this Agreement by giving fifteen (15) days' written notice to the other Party. Either Party may terminate this Agreement immediately upon a material breach by the other Party that remains uncured for seven (7) days after written notice.
          </div>
      </div>

      <div class="clause">
          <div class="clause-number">6.</div>
          <div class="clause-title">Supplier Obligations</div>
          <div class="clause-content">
              The Supplier agrees to: (a) deliver products in good condition and on time; (b) comply with all applicable Philippine laws and regulations; (c) provide accurate product information; (d) honor the discount stated in Section 2 of this Agreement; and (e) comply with the Terms and Conditions provided in Section 3.
          </div>
      </div>

      <div class="clause">
          <div class="clause-number">7.</div>
          <div class="clause-title">Store Obligations</div>
          <div class="clause-content">
              The Store agrees to: (a) provide accurate order information; (b) communicate any issues or concerns to the Supplier in a timely manner; and (c) comply with the Supplier's Terms and Conditions as provided in Section 3.
          </div>
      </div>

      <div class="clause">
          <div class="clause-number">8.</div>
          <div class="clause-title">Limitation of Liability</div>
          <div class="clause-content">
              To the extent permitted by Philippine law, neither Party shall be liable for indirect or consequential damages. The FurniSync Platform shall have no liability whatsoever under this Agreement as it is solely a facilitator.
          </div>
      </div>

      <div class="clause">
          <div class="clause-number">9.</div>
          <div class="clause-title">Governing Law</div>
          <div class="clause-content">
              This Agreement shall be governed by and construed in accordance with the laws of the Republic of the Philippines. Any dispute arising from this Agreement shall be resolved through good-faith negotiations. If unresolved, the dispute may be brought to the proper courts of the Philippines.
          </div>
      </div>

      <div class="clause">
          <div class="clause-number">10.</div>
          <div class="clause-title">Entire Agreement</div>
          <div class="clause-content">
              This Agreement, including the Supplier's Terms and Conditions in Section 3, constitutes the entire understanding between the Store and the Supplier with respect to the subject matter hereof and supersedes all prior agreements, whether written or oral.
          </div>
      </div>

      <!-- SIGNATURE SECTION - Store and Supplier only -->
      <div class="signature-section">
          <table class="signature-grid">
              <tr>
                  <td>
                      <div class="signature-line"></div>
                      <div class="signature-label">
                          <strong>FOR AND ON BEHALF OF THE STORE</strong><br>
                          Signature: ___________________________<br>
                          Printed Name: ${selectedStoreName.value || '[Store Representative]'}<br>
                          Title: Authorized Representative<br>
                          Date: ___________________
                      </div>
                  </td>
                  <td>
                      <div class="signature-line"></div>
                      <div class="signature-label">
                          <strong>FOR AND ON BEHALF OF THE SUPPLIER</strong><br>
                          Signature: ___________________________<br>
                          Printed Name: ${selectedSupplierName.value || '[Supplier Representative]'}<br>
                          Title: Authorized Representative<br>
                          Date: ___________________
                      </div>
                  </td>
              </tr>
          </table>
      </div>

      <!-- FOOTER -->
      <div class="footer">
          This Agreement is executed in duplicate. Each Party acknowledges receipt of a true copy.<br>
          Facilitated by FurniSync IMS Platform | Agreement ID: ${form.contract_number || 'DRAFT'} | Generated: ${new Date().toLocaleString('en-PH')}
      </div>
  </body>
  </html>
  `
})

const generateContractTitle = () => {
  const supplier = suppliers.value.find((s) => s.id === form.supplier_id)
  const supplierName = supplier?.supplier_name || selectedSupplierName.value || 'Supplier'
  const year = form.start_date ? new Date(form.start_date).getFullYear() : new Date().getFullYear()
  const type = contractTypes.find((t) => t.value === form.contract_type)?.label || 'Contract'
  return `${supplierName} - ${type} ${year}`
}

const formatCurrency = (value: number): string => {
  return (value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i]
}

const onFileSelect = (event: any) => {
  contractFile.value = event.files[0]
}


const validateForm = (): boolean => {
  Object.keys(errors).forEach(key => delete errors[key])

  if (!form.supplier_id) errors.supplier_id = 'Please select a supplier'
  if (!form.contract_title) {
    form.contract_title = generateContractTitle()
  }
  if (!form.contract_type) errors.contract_type = 'Please select contract type'
  if (!form.start_date) errors.start_date = 'Please select start date'
  if (!form.end_date) errors.end_date = 'Please select end date'
  if (form.start_date && form.end_date && form.end_date <= form.start_date) {
    errors.end_date = 'End date must be after start date'
  }
  if (form.discount_percentage < 0 || form.discount_percentage > 100) {
    errors.discount_percentage = 'Discount must be between 0 and 100'
  }
  if (form.tax_rate < 0 || form.tax_rate > 100) {
    errors.tax_rate = 'Tax rate must be between 0 and 100'
  }

  return Object.keys(errors).length === 0
}

const saveDraft = async () => {
  if (!form.supplier_id) {
    toast.add({ severity: 'warn', summary: 'Incomplete', detail: 'Please select supplier first', life: 3000 })
    return
  }
  if (!form.contract_title) form.contract_title = generateContractTitle()

  submitting.value = true
  try {
    const formData = new FormData()
    Object.entries(form).forEach(([key, value]) => {
      if (value !== null && value !== '') {
        if (value instanceof Date) {
          formData.append(key, value.toISOString().split('T')[0])
        } else {
          formData.append(key, String(value))
        }
      }
    })
    formData.append('status', 'pending')
    if (contractFile.value) {
      formData.append('contract_file', contractFile.value)
    }

    await procurementService.createSupplierContract(formData)
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Contract saved as draft',
      life: 3000,
    })
    
    if (route.query.from_supplier_portal) {
      const storeId = route.query.store_id
      setTimeout(() => {
        if (storeId) {
          router.push({ name: 'supplier.stores.show', params: { storeId } })
        } else {
          router.push({ name: 'supplier.stores' })
        }
      }, 1500)
    } else {
      // If coming from supplier creation flow, redirect to suppliers list
      const redirectName = route.query.supplier_id ? 'procurement.suppliers' : 'procurement.supplier-contracts.index'
      setTimeout(() => router.push({ name: redirectName }), 1500)
    }
  } catch (error: any) {
    const serverErrors = error?.response?.data?.errors || {}
    Object.keys(serverErrors).forEach((k) => {
      errors[k] = Array.isArray(serverErrors[k]) ? serverErrors[k][0] : String(serverErrors[k])
    })
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to save contract',
      life: 3000,
    })
  } finally {
    submitting.value = false
  }
}

const submitForm = async () => {
  if (!validateForm()) {
    toast.add({ severity: 'error', summary: 'Validation Error', detail: 'Please fix all errors', life: 3000 })
    return
  }

  submitting.value = true
  try {
    const formData = new FormData()
    Object.entries(form).forEach(([key, value]) => {
      if (value !== null && value !== '') {
        if (value instanceof Date) {
          formData.append(key, value.toISOString().split('T')[0])
        } else {
          formData.append(key, String(value))
        }
      }
    })
    formData.append('status', 'pending')
    if (contractFile.value) {
      formData.append('contract_file', contractFile.value)
    }

    await procurementService.createSupplierContract(formData)
    showReviewDialog.value = false
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Contract created successfully',
      life: 3000,
    })
    
    if (route.query.from_supplier_portal) {
      const storeId = route.query.store_id
      setTimeout(() => {
        if (storeId) {
          router.push({ name: 'supplier.stores.show', params: { storeId } })
        } else {
          router.push({ name: 'supplier.stores' })
        }
      }, 1500)
    } else {
      // If coming from supplier creation flow, redirect to suppliers list
      const redirectName = route.query.supplier_id ? 'procurement.suppliers' : 'procurement.supplier-contracts.index'
      setTimeout(() => router.push({ name: redirectName }), 1500)
    }
  } catch (error: any) {
    const serverErrors = error?.response?.data?.errors || {}
    Object.keys(serverErrors).forEach((k) => {
      errors[k] = Array.isArray(serverErrors[k]) ? serverErrors[k][0] : String(serverErrors[k])
    })
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to create contract',
      life: 3000,
    })
  } finally {
    submitting.value = false
  }
}

const openReviewDialog = () => {
  if (!validateForm()) {
    toast.add({ severity: 'error', summary: 'Validation Error', detail: 'Please fix all errors before review', life: 3000 })
    return
  }
  showReviewDialog.value = true
}

const skipOrCancel = () => {
  if (route.query.from_supplier_portal) {
    const storeId = route.query.store_id
    if (storeId) {
      router.push({ name: 'supplier.stores.show', params: { storeId } })
      return
    }
    router.push({ name: 'supplier.stores' })
    return
  }

  if (route.query.supplier_id) {
    // Coming from supplier creation - go to suppliers list
    router.push({ name: 'procurement.suppliers' })
  } else {
    // Regular cancel - go back to contracts list
    router.push({ name: 'procurement.supplier-contracts.index' })
  }
}

onMounted(async () => {
  try {
    selectedStoreName.value = (route.query.store_name as string) || 'Not Selected'
    selectedSupplierName.value = (route.query.supplier_name as string) || 'Not Selected'

    // Auto-select supplier if passed from supplier creation flow
    if (route.query.supplier_id) {
      form.supplier_id = parseInt(route.query.supplier_id as string)
      form.store_id = route.query.store_id ? parseInt(route.query.store_id as string) : null

      if (!selectedSupplierName.value || selectedSupplierName.value === 'Not Selected') {
        const response = await procurementService.getSuppliers({ per_page: 100 })
        suppliers.value = response.data?.data || []
        const supplier = suppliers.value.find(s => s.id === form.supplier_id)
        if (supplier) {
          selectedSupplierName.value = supplier.supplier_name
        }
      }
      onSupplierChange()

      toast.add({
        severity: 'info',
        summary: 'Create Contract',
        detail: 'Supplier selected. Fill in the contract details below.',
        life: 3000
      })
    } else {
      toast.add({ severity: 'warn', summary: 'Missing Supplier', detail: 'Supplier ID is required to create contract.', life: 3000 })
    }

    if (!form.contract_title) {
      form.contract_title = generateContractTitle()
    }
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load supplier', life: 3000 })
  }
})

watch(() => form.contract_title, (val, oldVal) => {
  if (oldVal !== undefined && val !== generateContractTitle()) {
    titleManuallyEdited.value = true
  }
})

watch([() => form.supplier_id, () => form.start_date, () => form.contract_type], () => {
  if (!titleManuallyEdited.value) {
    form.contract_title = generateContractTitle()
  }
})
</script>
