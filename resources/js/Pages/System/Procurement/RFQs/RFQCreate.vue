<template>
  <div class="max-w-7xl mx-auto space-y-6 py-6 px-4 sm:px-6 lg:px-8">
    <!-- iOS-style Header -->
    <div class="flex items-center gap-4">
      <button 
        @click="router.push({ name: 'procurement.rfqs' })"
        class="w-10 h-10 rounded-full bg-white hover:bg-gray-100 flex items-center justify-center transition-colors shadow-sm border border-gray-200"
      >
        <i class="pi pi-chevron-left text-gray-600 text-lg"></i>
      </button>
      <div>
        <h1 class="text-2xl font-semibold text-gray-900 tracking-tight">
          {{ isEditMode ? 'Edit Request for Quotation' : 'Create Request for Quotation' }}
        </h1>
        <p class="text-sm text-gray-500 mt-1">
          {{ isEditMode ? 'Update and resend RFQ details' : 'Create and send RFQ in a single view' }}
        </p>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Left Column - Main Forms -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Basic Information Card -->
        <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
          <template #header>
            <div class="px-6 pt-6">
              <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                  <i class="pi pi-info-circle text-blue-600 text-sm"></i>
                </div>
                <div>
                  <h3 class="text-lg font-semibold text-gray-900">Basic Information</h3>
                  <p class="text-sm text-gray-500 mt-0.5">Enter RFQ header details</p>
                </div>
              </div>
            </div>
          </template>
          <template #content>
            <div class="p-6 pt-2 space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                  <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <span class="text-red-500 mr-1">*</span> Title
                  </label>
                  <InputText 
                    v-model="form.title" 
                    placeholder="e.g., Office Furniture Purchase"
                    :invalid="errors.title !== undefined"
                    class="w-full bg-gray-50 border-gray-200 rounded-xl"
                  />
                  <small class="text-red-500" v-if="errors.title">{{ errors.title }}</small>
                </div>

                <div class="space-y-2">
                  <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <span class="text-red-500 mr-1">*</span> RFQ Type
                  </label>
                  <Select 
                    v-model="form.rfq_type" 
                    :options="rfqTypes" 
                    optionLabel="label" 
                    optionValue="value"
                    placeholder="Select type" 
                    :invalid="errors.rfq_type !== undefined"
                    class="w-full bg-gray-50 border-gray-200 rounded-xl"
                  />
                  <small class="text-red-500" v-if="errors.rfq_type">{{ errors.rfq_type }}</small>
                </div>

                <div class="space-y-2">
                  <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <span class="text-red-500 mr-1">*</span> Issue Date
                  </label>
                  <DatePicker 
                    v-model="form.issue_date"  fluid
                    dateFormat="yy-mm-dd" 
                    :invalid="errors.issue_date !== undefined"
                    class="w-full bg-gray-50 border-gray-200 rounded-xl"
                    showIcon
                    iconDisplay="input"
                  />
                  <small class="text-red-500" v-if="errors.issue_date">{{ errors.issue_date }}</small>
                </div>

                <div class="space-y-2">
                  <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <span class="text-red-500 mr-1">*</span> Currency
                  </label>
                  <Select 
                    v-model="form.currency" 
                    :options="currencies" 
                    optionLabel="label" 
                    optionValue="value"
                    placeholder="Select currency" 
                    filter 
                    :invalid="errors.currency !== undefined"
                    class="w-full bg-gray-50 border-gray-200 rounded-xl"
                  />
                  <small class="text-red-500" v-if="errors.currency">{{ errors.currency }}</small>
                </div>

                <!-- <div class="space-y-2">
                  <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Shipping Terms</label>
                  <Select 
                    v-model="form.shipping_terms" 
                    :options="shippingTerms" 
                    optionLabel="label" 
                    optionValue="value"
                    placeholder="Select terms"
                    class="w-full bg-gray-50 border-gray-200 rounded-xl"
                  />
                </div> -->
              </div>

              <div class="space-y-2">
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Description</label>
                <Textarea 
                  v-model="form.description" 
                  placeholder="Brief description of the RFQ" 
                  rows="3"
                  class="w-full bg-gray-50 border-gray-200 rounded-xl resize-none"
                />
              </div>

              <div class="space-y-2">
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Submission Instructions</label>
                <Textarea 
                  v-model="form.instructions" 
                  placeholder="Any special instructions for suppliers" 
                  rows="3"
                  class="w-full bg-gray-50 border-gray-200 rounded-xl resize-none"
                />
              </div>

              <div class="space-y-2">
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Qualification Requirements</label>
                <Textarea 
                  v-model="form.qualification_requirements" 
                  placeholder="Supplier qualification criteria" 
                  rows="3"
                  class="w-full bg-gray-50 border-gray-200 rounded-xl resize-none"
                />
              </div>
            </div>
          </template>
        </Card>

        <!-- Supplier Selection Card -->
        <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
          <template #header>
            <div class="px-6 pt-6">
              <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                  <i class="pi pi-users text-green-600 text-sm"></i>
                </div>
                <div>
                  <h3 class="text-lg font-semibold text-gray-900">Supplier Selection</h3>
                  <p class="text-sm text-gray-500 mt-0.5">Select suppliers to send RFQ to</p>
                </div>
              </div>
            </div>
          </template>
          <template #content>
            <div class="p-6 pt-2 space-y-4">
              <div class="space-y-2">
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                  <span v-if="!splitRfqMode" class="text-red-500 mr-1">*</span> Select Suppliers
                </label>
                <MultiSelect 
                  v-model="selectedSupplierIds" 
                  :options="suppliers" 
                  optionLabel="name" 
                  optionValue="id"
                  placeholder="Select suppliers" 
                  filter 
                  display="chip" 
                  :disabled="splitRfqMode"
                  class="w-full bg-gray-50 border-gray-200 rounded-xl"
                />
                <small class="text-gray-500" v-if="!splitRfqMode">Select at least one supplier to send the RFQ</small>
                <small class="text-blue-600" v-else>
                  Split mode active: PR has {{ splitRfqSupplierGroups }} supplier groups. System will create one RFQ per supplier automatically.
                </small>
              </div>

              <div class="space-y-2">
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                  <span class="text-red-500 mr-1">*</span> Send Via
                </label>
                <div class="flex gap-6 p-3 bg-gray-50 rounded-xl">
                  <div class="flex items-center gap-2">
                    <RadioButton v-model="form.invitation_method" value="email" inputId="email" />
                    <label for="email" class="text-sm text-gray-700">Email</label>
                  </div>
                  <div class="flex items-center gap-2">
                    <RadioButton v-model="form.invitation_method" value="portal" inputId="portal" />
                    <label for="portal" class="text-sm text-gray-700">Supplier Portal</label>
                  </div>
                  <div class="flex items-center gap-2">
                    <RadioButton v-model="form.invitation_method" value="both" inputId="both" />
                    <label for="both" class="text-sm text-gray-700">Both</label>
                  </div>
                </div>
              </div>

              <!-- Selected Suppliers List -->
              <div v-if="selectedSupplierIds.length > 0" class="mt-4 pt-4 border-t border-gray-100">
                <h4 class="text-sm font-medium text-gray-700 mb-3 flex items-center gap-2">
                  <i class="pi pi-check-circle text-green-500"></i>
                  Recipients ({{ selectedSupplierIds.length }})
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                  <div v-for="supplierId in selectedSupplierIds" :key="supplierId"
                    class="flex items-start gap-3 p-3 bg-blue-50 rounded-xl border border-blue-100">
                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
                      <i class="pi pi-building text-blue-600 text-xs"></i>
                    </div>
                    <div>
                      <p class="font-medium text-gray-900">{{ suppliers.find(s => s.id === supplierId)?.name }}</p>
                      <p class="text-xs text-gray-600 mt-0.5">{{ suppliers.find(s => s.id === supplierId)?.email }}</p>
                    </div>
                  </div>
                </div>
              </div>

              <div v-if="splitRfqMode" class="mt-4 pt-4 border-t border-gray-100">
                <h4 class="text-sm font-medium text-gray-700 mb-3 flex items-center gap-2">
                  <i class="pi pi-sitemap text-blue-500"></i>
                  Split Preview ({{ splitRfqSupplierGroups }} groups)
                </h4>
                <div class="space-y-2">
                  <div v-for="group in splitRfqSummary" :key="group.supplier_id" class="p-3 bg-blue-50 rounded-xl border border-blue-100">
                    <p class="font-medium text-gray-900">{{ group.supplier_name }}</p>
                    <p class="text-xs text-gray-600 mb-1">{{ group.item_count }} items • Qty {{ group.total_qty }}</p>
                    <ul class="text-xs text-gray-700 space-y-0.5">
                      <li v-for="line in group.lines" :key="line.key">{{ line.product_name }} • Qty {{ line.qty }}</li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
          </template>
        </Card>
      </div>

      <!-- Right Column - Products -->
      <div class="space-y-6">
        <!-- Requested Products Card -->
        <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden sticky top-6">
          <template #header>
            <div class="px-6 pt-6">
              <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center">
                  <i class="pi pi-box text-orange-600 text-sm"></i>
                </div>
                <div>
                  <h3 class="text-lg font-semibold text-gray-900">Requested Products</h3>
                  <p class="text-sm text-gray-500 mt-0.5">Line items from the requisition</p>
                </div>
              </div>
            </div>
          </template>
          <template #content>
            <div class="p-6 pt-2 space-y-4">
              <div v-for="(item, index) in form.items" :key="index" class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                <div class="flex items-start justify-between mb-3">
                  <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Item #{{ index + 1 }}</span>
                  <Button 
                    icon="pi pi-trash" 
                    text 
                    rounded 
                    severity="danger" 
                    @click="removeItem(index)"
                    class="w-8 h-8 text-gray-400 hover:text-red-600"
                  />
                </div>
                
                <div class="space-y-3">
                  <div v-if="!item.product_id">
                    <Select 
                      :options="products" 
                      optionLabel="product_name" 
                      optionValue="id" 
                      placeholder="Select product"
                      filter
                      @change="(e) => selectProduct(index, e)"
                      class="w-full bg-white border-gray-200 rounded-xl"
                    />
                  </div>
                  <div v-else class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                      <i class="pi pi-box text-blue-600 text-xs"></i>
                    </div>
                    <div>
                      <p class="font-medium text-gray-900">{{ item.product_name }}</p>
                      <p class="text-xs text-gray-500 mt-0.5">Product ID: {{ item.product_id }}</p>
                    </div>
                  </div>

                  <div class="flex items-center gap-3">
                    <label class="text-xs text-gray-500">Quantity:</label>
                    <InputNumber 
                      v-model="item.quantity" 
                      :min="1" 
                      class="w-24"
                      buttonLayout="horizontal"
                      showButtons fluid
                    >
                      <template #incrementbuttonicon>
                        <span class="pi pi-plus" />
                      </template>
                      <template #decrementbuttonicon>
                        <span class="pi pi-minus" />
                      </template>
                    </InputNumber>
                  </div>
                </div>
              </div>

              <Button 
                label="Add Line Item" 
                icon="pi pi-plus" 
                @click="addItem" 
                severity="secondary" 
                text 
                class="w-full border border-dashed border-gray-300 rounded-xl py-3"
              />
              <div class="text-red-500 text-xs" v-if="errors.items">{{ errors.items }}</div>
            </div>
          </template>
        </Card>
      </div>
    </div>

    <!-- iOS-style Sticky Footer -->
    <div class="sticky bottom-4 -mx-6 px-6 py-4 bg-white/95 backdrop-blur-lg rounded-2xl border border-gray-100 shadow-lg">
      <div class="flex justify-end gap-3">
        <Button 
          label="Cancel" 
          severity="secondary" 
          text 
          @click="router.push({ name: 'procurement.rfqs' })"
          class="rounded-xl px-5 py-2.5"
        />
        <Button 
          :label="isEditMode ? 'Update Draft' : 'Save as Draft'" 
          icon="pi pi-save" 
          severity="warning" 
          @click="saveDraft" 
          :loading="saving"
          class="rounded-xl px-5 py-2.5 bg-orange-50 text-orange-700 border-orange-200 hover:bg-orange-100"
        />
        <Button 
          :label="isEditMode ? 'Review & Update' : 'Review & Create'" 
          icon="pi pi-send" 
          iconPos="right" 
          @click="showReview = true" 
          :loading="saving"
          :disabled="!splitRfqMode && selectedSupplierIds.length === 0"
          class="rounded-xl px-5 py-2.5 bg-blue-500 hover:bg-blue-600 text-white border-none"
        />
      </div>
    </div>

    <!-- iOS-style Review & Submit Modal -->
    <Dialog 
      v-model:visible="showReview" 
      modal 
      header="Review & Submit RFQ" 
      :style="{ width: '50rem' }"
      class="rounded-2xl"
      :dismissableMask="true"
    >
      <div class="space-y-6 py-2">
        <!-- RFQ Summary -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="bg-blue-50 rounded-xl p-4 border border-blue-100">
            <div class="flex items-center gap-2 mb-3">
              <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center">
                <i class="pi pi-file text-blue-600 text-xs"></i>
              </div>
              <h4 class="font-semibold text-blue-900">RFQ Details</h4>
            </div>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-gray-600">Title:</span>
                <span class="font-medium text-gray-900">{{ form.title || 'N/A' }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Type:</span>
                <span class="font-medium text-gray-900">{{ getRfqTypeLabel(form.rfq_type) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Issue Date:</span>
                <span class="font-medium text-gray-900">{{ formatDate(form.issue_date) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Currency:</span>
                <span class="font-medium text-gray-900">{{ form.currency }}</span>
              </div>
            </div>
          </div>

          <div class="bg-green-50 rounded-xl p-4 border border-green-100">
            <div class="flex items-center gap-2 mb-3">
              <div class="w-6 h-6 rounded-full bg-green-100 flex items-center justify-center">
                <i class="pi pi-users text-green-600 text-xs"></i>
              </div>
              <h4 class="font-semibold text-green-900">Suppliers</h4>
            </div>
            <div class="text-sm">
              <div class="flex justify-between mb-2">
                <span class="text-gray-600">Recipients:</span>
                <span class="font-medium text-gray-900">{{ selectedSupplierIds.length }}</span>
              </div>
              <div class="max-h-32 overflow-y-auto space-y-2">
                <div v-for="supplierId in selectedSupplierIds" :key="supplierId" 
                     class="flex items-center gap-2 p-2 bg-white rounded-lg border border-green-100">
                  <i class="pi pi-building text-green-500 text-xs"></i>
                  <div>
                    <div class="font-medium text-gray-900 text-xs">{{ suppliers.find(s => s.id === supplierId)?.name }}</div>
                    <div class="text-xs text-gray-500">{{ suppliers.find(s => s.id === supplierId)?.email }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Products Summary -->
        <div class="bg-orange-50 rounded-xl p-4 border border-orange-100">
          <div class="flex items-center gap-2 mb-3">
            <div class="w-6 h-6 rounded-full bg-orange-100 flex items-center justify-center">
              <i class="pi pi-box text-orange-600 text-xs"></i>
            </div>
            <h4 class="font-semibold text-orange-900">Requested Products</h4>
          </div>
          <div class="space-y-2">
            <div v-for="(item, index) in form.items.filter(i => i.product_id)" :key="index"
                 class="flex items-center justify-between p-3 bg-white rounded-xl border border-orange-100">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center">
                  <i class="pi pi-box text-orange-600 text-xs"></i>
                </div>
                <div>
                  <span class="font-medium text-gray-900">{{ item.product_name }}</span>
                  <span class="text-xs text-gray-500 block mt-0.5">Qty: {{ item.quantity }}</span>
                </div>
              </div>
            </div>
            <div v-if="form.items.filter(i => i.product_id).length === 0" class="text-center py-4">
              <i class="pi pi-exclamation-circle text-orange-400 text-xl mb-2"></i>
              <p class="text-sm text-red-600">No items selected.</p>
            </div>
          </div>
        </div>

        <!-- Terms & Conditions Section -->
        <div class="bg-purple-50 rounded-xl p-4 border border-purple-100">
          <div class="flex items-center gap-2 mb-3">
            <div class="w-6 h-6 rounded-full bg-purple-100 flex items-center justify-center">
              <i class="pi pi-file-pdf text-purple-600 text-xs"></i>
            </div>
            <h4 class="font-semibold text-purple-900">Terms & Conditions</h4>
          </div>
          
          <div class="space-y-3">
            <p class="text-sm text-gray-600">
              By submitting this RFQ, you agree to the following terms and conditions that govern the procurement process.
            </p>
            

            <div class="flex items-start gap-3 mt-4 p-3 bg-white rounded-xl">
              <Checkbox 
                v-model="confirmTerms" 
                :binary="true" 
                inputId="terms"
                class="mt-0.5"
              />
              <label for="terms" class="text-sm text-gray-700">
                I confirm that I have read and agree to the 
                <button 
                  @click="showTermsModal = true"
                  class="text-purple-700 font-medium hover:underline"
                >
                  Terms and Conditions
                </button>
                . I understand that this creates a binding agreement between our organization and the selected suppliers.
              </label>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end gap-3 pt-2">
          <Button 
            label="Close" 
            severity="secondary" 
            text 
            @click="showReview = false"
            class="rounded-xl px-5 py-2.5"
          />
          <Button 
            :label="isEditMode ? 'Update & Send RFQ' : 'Create & Send RFQ'" 
            icon="pi pi-send" 
            @click="submitForm"
            :disabled="!confirmTerms || (!splitRfqMode && selectedSupplierIds.length === 0)"
            class="rounded-xl px-5 py-2.5 bg-blue-500 hover:bg-blue-600 text-white border-none"
          />
        </div>
      </template>
    </Dialog>

    <!-- iOS-style Terms & Conditions Modal -->
    <Dialog 
      v-model:visible="showTermsModal" 
      modal 
      header="Terms and Conditions" 
      :style="{ width: '40rem' }"
      class="rounded-2xl"
    >
      <div class="space-y-6 py-2 max-h-[60vh] overflow-y-auto px-1">
        <!-- Legal Notice -->
        <div class="bg-red-50 rounded-xl p-4 border border-red-100">
          <div class="flex items-start gap-3">
            <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center shrink-0">
              <i class="pi pi-exclamation-triangle text-red-600 text-sm"></i>
            </div>
            <div>
              <h3 class="font-semibold text-red-800 mb-1">Legal Notice</h3>
              <p class="text-xs text-red-700">This is a legally binding document. Please read carefully before proceeding.</p>
            </div>
          </div>
        </div>

        <!-- Terms Content -->
        <div class="space-y-4">
          <div class="bg-white rounded-xl p-4 border border-gray-100">
            <h4 class="font-semibold text-gray-900 mb-2 flex items-center gap-2">
              <i class="pi pi-check-circle text-green-500"></i>
              1. RFQ Validity
            </h4>
            <p class="text-sm text-gray-600">
              This Request for Quotation (RFQ) is valid for a period of thirty (30) calendar days from the date of issue. 
              Any quotations submitted after the deadline will not be considered. The issuing organization reserves the right 
              to accept or reject any quotation at its sole discretion.
            </p>
          </div>

          <div class="bg-white rounded-xl p-4 border border-gray-100">
            <h4 class="font-semibold text-gray-900 mb-2 flex items-center gap-2">
              <i class="pi pi-check-circle text-green-500"></i>
              2. Supplier Obligations
            </h4>
            <p class="text-sm text-gray-600 mb-2">By submitting a quotation, the supplier agrees to:</p>
            <ul class="list-disc pl-5 text-sm text-gray-600 space-y-1">
              <li>Provide accurate and complete information about products, pricing, and delivery capabilities</li>
              <li>Honor the quoted prices for the duration of the RFQ validity period</li>
              <li>Comply with all specified requirements, including delivery timelines and quality standards</li>
              <li>Maintain confidentiality of all information received through this RFQ process</li>
            </ul>
          </div>

          <div class="bg-white rounded-xl p-4 border border-gray-100">
            <h4 class="font-semibold text-gray-900 mb-2 flex items-center gap-2">
              <i class="pi pi-check-circle text-green-500"></i>
              3. Liability and Indemnification
            </h4>
            <p class="text-sm text-gray-600 mb-2">
              The supplier agrees to indemnify and hold harmless the issuing organization from any claims, damages, 
              or liabilities arising from:
            </p>
            <ul class="list-disc pl-5 text-sm text-gray-600 space-y-1">
              <li>Non-performance or delayed delivery of goods or services</li>
              <li>Defective products or failure to meet specified quality standards</li>
              <li>Breach of confidentiality or intellectual property rights</li>
              <li>Any misrepresentation in the submitted quotation</li>
            </ul>
          </div>

          <div class="bg-white rounded-xl p-4 border border-gray-100">
            <h4 class="font-semibold text-gray-900 mb-2 flex items-center gap-2">
              <i class="pi pi-check-circle text-green-500"></i>
              4. Pricing and Payment Terms
            </h4>
            <p class="text-sm text-gray-600">
              All quoted prices must include applicable taxes, duties, and delivery charges unless otherwise specified. 
              Payment terms will be negotiated upon award of the contract. The issuing organization reserves the right 
              to negotiate pricing and terms with selected suppliers.
            </p>
          </div>

          <div class="bg-white rounded-xl p-4 border border-gray-100">
            <h4 class="font-semibold text-gray-900 mb-2 flex items-center gap-2">
              <i class="pi pi-check-circle text-green-500"></i>
              5. Dispute Resolution
            </h4>
            <p class="text-sm text-gray-600">
              Any disputes arising from this RFQ or resulting contract shall be resolved through good faith negotiations. 
              If unresolved, disputes shall be submitted to arbitration in accordance with the rules of the Philippine 
              Dispute Resolution Center, Inc. (PDRCI). The decision of the arbitrator shall be final and binding.
            </p>
          </div>

          <div class="bg-white rounded-xl p-4 border border-gray-100">
            <h4 class="font-semibold text-gray-900 mb-2 flex items-center gap-2">
              <i class="pi pi-check-circle text-green-500"></i>
              6. Force Majeure
            </h4>
            <p class="text-sm text-gray-600">
              Neither party shall be liable for any failure or delay in performance due to causes beyond their reasonable 
              control, including but not limited to acts of God, natural disasters, war, terrorism, strikes, or government 
              actions. The affected party shall provide prompt written notice and take reasonable steps to minimize the impact.
            </p>
          </div>

          <div class="bg-white rounded-xl p-4 border border-gray-100">
            <h4 class="font-semibold text-gray-900 mb-2 flex items-center gap-2">
              <i class="pi pi-check-circle text-green-500"></i>
              7. Data Privacy
            </h4>
            <p class="text-sm text-gray-600">
              All personal and business information collected through this RFQ process will be handled in accordance with 
              the Data Privacy Act of 2012 (Republic Act No. 10173). By participating, suppliers consent to the collection, 
              use, and storage of their information for procurement purposes.
            </p>
          </div>

          <div class="bg-white rounded-xl p-4 border border-gray-100">
            <h4 class="font-semibold text-gray-900 mb-2 flex items-center gap-2">
              <i class="pi pi-check-circle text-green-500"></i>
              8. Governing Law
            </h4>
            <p class="text-sm text-gray-600">
              This RFQ and any resulting contract shall be governed by and construed in accordance with the laws of the 
              Republic of the Philippines. The parties submit to the exclusive jurisdiction of the courts of Makati City.
            </p>
          </div>
        </div>

        <!-- Acceptance Statement -->
        <div class="bg-blue-50 rounded-xl p-4 border border-blue-100">
          <p class="text-sm text-blue-800 font-medium flex items-start gap-2">
            <i class="pi pi-info-circle text-blue-600 mt-0.5"></i>
            <span>By checking the agreement box in the review screen, you acknowledge that you have read, understood, 
            and agree to be bound by these Terms and Conditions.</span>
          </p>
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end gap-3">
          <Button 
            label="Close" 
            severity="secondary" 
            text 
            @click="showTermsModal = false"
            class="rounded-xl px-5 py-2.5"
          />
          <Button 
            label="I Agree" 
            icon="pi pi-check" 
            @click="agreeToTerms"
            class="rounded-xl px-5 py-2.5 bg-green-500 hover:bg-green-600 text-white border-none"
          />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import MultiSelect from 'primevue/multiselect'
import RadioButton from 'primevue/radiobutton'
import InputNumber from 'primevue/inputnumber'
import Dialog from 'primevue/dialog'
import Checkbox from 'primevue/checkbox'
import procurementService from '../../../../services/procurement.service'

interface RFQItem {
  product_id: number | null
  variation_id: number | null
  selected_supplier_id?: number | null
  selected_supplier_name?: string | null
  product_name?: string
  quantity: number
  unit: string
  target_price: number | null
  specifications: string
  requirements?: string
  notes: string
}

interface RFQForm {
  purchase_requisition_id: number | null
  title: string
  description: string
  issue_date: Date
  rfq_type: 'purchase' | 'service' | 'both'
  currency: string
  shipping_terms: string
  instructions: string
  qualification_requirements: string
  items: RFQItem[]
  invitation_method: string
}

interface FormErrors {
  [key: string]: string | undefined
}

interface Supplier {
  id: number
  name: string
  email: string
}

interface Product {
  id: number
  product_name: string
  sku: string
  brand?: string
  base_price: string
  category_id?: number
  category?: string
  variation_id?: number | null
  available_qty?: number
  on_order_qty?: number
  received_qty?: number
  pending_receive_qty?: number
}

const router = useRouter()
const route = useRoute()
const toast = useToast()
const saving = ref(false)
const loading = ref(false)
const confirmTerms = ref(false)
const showReview = ref(false)
const showTermsModal = ref(false)
const selectedSupplierIds = ref<number[]>([])
const editingRfqId = ref<number | null>(null)
const isEditMode = computed(() => editingRfqId.value !== null)
const splitRfqMode = ref(false)
const splitRfqSupplierGroups = ref<number>(0)
const products = ref<Product[]>([])
const suppliers = ref<Supplier[]>([])
const errors = reactive<FormErrors>({})
const splitRfqSummary = computed(() => {
  const groups = new Map<number, {
    supplier_id: number
    supplier_name: string
    item_count: number
    total_qty: number
    lines: Array<{ key: string; product_name: string; qty: number }>
  }>()

  for (const item of form.items) {
    const supplierId = Number(item?.selected_supplier_id || 0)
    if (!supplierId) continue

    const supplierName = item?.selected_supplier_name
      || suppliers.value.find((s) => Number(s.id) === supplierId)?.name
      || `Supplier #${supplierId}`

    if (!groups.has(supplierId)) {
      groups.set(supplierId, {
        supplier_id: supplierId,
        supplier_name: supplierName,
        item_count: 0,
        total_qty: 0,
        lines: [],
      })
    }

    const group = groups.get(supplierId)!
    const qty = Number(item?.quantity || 0)
    group.item_count += 1
    group.total_qty += qty
    group.lines.push({
      key: `${supplierId}-${item?.product_id}-${group.lines.length}`,
      product_name: item?.product_name || `Product #${item?.product_id}`,
      qty,
    })
  }

  return Array.from(groups.values())
})

const rfqTypes = [
  { label: 'Purchase', value: 'purchase' },
  { label: 'Service', value: 'service' },
  { label: 'Both', value: 'both' },
]

const currencies = [
  { label: 'PHP (Philippine Peso)', value: 'PHP' },
  { label: 'USD (US Dollar)', value: 'USD' },
  { label: 'EUR (Euro)', value: 'EUR' },
]

const shippingTerms = [
  { label: 'FOB (Free on Board)', value: 'FOB' },
  { label: 'CIF (Cost, Insurance, Freight)', value: 'CIF' },
  { label: 'EXW (Ex Works)', value: 'EXW' },
  { label: 'DDP (Delivered Duty Paid)', value: 'DDP' },
]

const form = reactive<RFQForm>({
  purchase_requisition_id: null,
  title: '',
  description: '',
  issue_date: new Date(),
  rfq_type: 'purchase',
  currency: 'PHP',
  shipping_terms: '',
  instructions: '',
  qualification_requirements: '',
  items: [{ product_id: null, variation_id: null, quantity: 1, unit: 'pcs', target_price: null, specifications: '', notes: '' }],
  invitation_method: 'both',
})

// Helper functions
const formatDate = (date: Date | null): string => {
  if (!date) return 'N/A'
  return date.toLocaleDateString('en-PH', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric' 
  })
}

const agreeToTerms = () => {
  confirmTerms.value = true
  showTermsModal.value = false
  toast.add({
    severity: 'success',
    summary: 'Terms Accepted',
    detail: 'You have agreed to the Terms and Conditions',
    life: 2000
  })
}

const toDateOrNull = (value: any): Date | null => {
  if (!value) return null
  const date = new Date(value)
  return isNaN(date.getTime()) ? null : date
}

const loadRfqForEdit = async (id: number) => {
  try {
    const response = await procurementService.getRFQ(id)
    const rfq = response?.data?.data || response?.data || response

    if (!rfq) return

    form.purchase_requisition_id = rfq.purchase_requisition_id ?? null
    form.title = rfq.title || ''
    form.description = rfq.description || ''
    form.issue_date = toDateOrNull(rfq.issue_date) || new Date()
    form.rfq_type = rfq.rfq_type || 'purchase'
    form.currency = rfq.currency || 'PHP'
    form.shipping_terms = rfq.shipping_terms
    form.instructions = rfq.instructions || ''
    form.qualification_requirements = rfq.qualification_requirements || ''

    if (Array.isArray(rfq.items) && rfq.items.length > 0) {
      form.items = rfq.items.map((item: any) => ({
        product_id: item.product_id || null,
        variation_id: item.variation_id || null,
        product_name: item.product?.product_name || item.product_name || '',
        quantity: item.quantity || 1,
        unit: 'pcs',
        target_price: item.target_price ?? null,
        specifications: item.specifications || '',
        notes: item.notes || '',
      }))
    }

    if (Array.isArray(rfq.suppliers)) {
      selectedSupplierIds.value = rfq.suppliers
        .map((s: any) => s.supplier_id || s.supplier?.id)
        .filter((id: number | null) => !!id)
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'Failed to load RFQ for editing',
      life: 3000,
    })
  }
}

const getRfqTypeLabel = (value: string) => {
  const type = rfqTypes.find(t => t.value === value)
  return type?.label || value
}

const validateForm = (): boolean => {
  Object.keys(errors).forEach(key => delete errors[key])

  if (!form.title || form.title.trim() === '') {
    errors.title = 'Title is required'
  }

  if (!form.rfq_type) {
    errors.rfq_type = 'RFQ Type is required'
  }

  if (!form.issue_date) {
    errors.issue_date = 'Issue Date is required'
  }

  if (!form.currency) {
    errors.currency = 'Currency is required'
  }

  const validItems = form.items.filter(i => i.product_id)
  if (validItems.length === 0) {
    errors.items = 'At least one line item is required'
  }

  return Object.keys(errors).length === 0
}

const addItem = () => {
  form.items.push({
    product_id: null,
    variation_id: null,
    product_name: '',
    quantity: 1,
    unit: 'pcs',
    target_price: null,
    specifications: '',
    notes: '',
  })
}

const removeItem = (index: number) => {
  form.items.splice(index, 1)
}

const selectProduct = (index: number, event: any) => {
  const selectedProductId = event.value
  const selectedProduct = products.value.find(p => p.id === selectedProductId)

  if (selectedProduct && form.items[index]) {
    form.items[index].product_id = selectedProduct.id
    form.items[index].variation_id = null
    form.items[index].product_name = selectedProduct.product_name
  }
}

const prefillFromRequisition = (requisition: any) => {
  if (!requisition) return

  form.purchase_requisition_id = requisition.id
  if (!form.title) {
    form.title = `RFQ for ${requisition.pr_number || 'Purchase Requisition'}`
  }

  if (!form.description && requisition.reason) {
    form.description = requisition.reason
  }

  if (Array.isArray(requisition.items) && requisition.items.length > 0) {
    form.items = requisition.items.map((item: any) => ({
      product_id: item.product_id || null,
      variation_id: item.variation_id || null,
      selected_supplier_id: item.selected_supplier_id || null,
      selected_supplier_name: item.selected_supplier_id
        ? (Array.isArray(item.product?.suppliers)
          ? item.product.suppliers.find((s: any) => Number(s.id) === Number(item.selected_supplier_id))?.supplier_name
          : null)
        : null,
      product_name: item.product?.product_name || item.product_name || '',
      quantity: item.quantity_requested || 1,
      unit: 'pcs',
      target_price: item.estimated_unit_cost ?? null,
      specifications: item.specifications || '',
      notes: ''
    }))

    const resolvedSupplierIds = requisition.items
      .map((item: any) => {
        if (item?.selected_supplier_id) return Number(item.selected_supplier_id)
        const productSuppliers = Array.isArray(item?.product?.suppliers) ? item.product.suppliers : []
        if (productSuppliers.length === 1) return Number(productSuppliers[0].id)
        return null
      })
      .filter((id: any) => Number(id) > 0)

    const uniqueSupplierIds = Array.from(new Set(resolvedSupplierIds)).map((id: any) => Number(id)).filter((id: number) => id > 0)
    splitRfqSupplierGroups.value = uniqueSupplierIds.length
    splitRfqMode.value = uniqueSupplierIds.length > 1

    if (!splitRfqMode.value && uniqueSupplierIds.length === 1) {
      selectedSupplierIds.value = [uniqueSupplierIds[0]]
    } else if (splitRfqMode.value) {
      selectedSupplierIds.value = []
    }
  }
}

const saveDraft = async () => {
  if (!validateForm()) {
    toast.add({
      severity: 'error',
      summary: 'Validation Error',
      detail: 'Please complete required fields',
      life: 3000
    })
    return
  }

  saving.value = true
  try {
    const validItems = form.items.filter(i => i.product_id)
    if (validItems.length === 0) {
      toast.add({
        severity: 'error',
        summary: 'Validation Error',
        detail: 'Please add at least one line item before saving',
        life: 3000
      })
      saving.value = false
      return
    }

    if (!splitRfqMode.value && selectedSupplierIds.value.length === 0) {
      toast.add({
        severity: 'error',
        summary: 'Validation Error',
        detail: 'Please select at least one supplier to send RFQ to',
        life: 3000
      })
      saving.value = false
      return
    }

    if (splitRfqMode.value && form.purchase_requisition_id && !isEditMode.value) {
      const splitPayload = {
        purchase_requisition_id: form.purchase_requisition_id,
        title: form.title,
        description: form.description,
        issue_date: form.issue_date instanceof Date
          ? form.issue_date.toISOString().split('T')[0]
          : String(form.issue_date || ''),
        rfq_type: form.rfq_type,
        currency: form.currency,
        shipping_terms: form.shipping_terms,
        instructions: form.instructions,
        qualification_requirements: form.qualification_requirements,
      }

      const splitResponse = await procurementService.createRFQsFromRequisitionSplit(splitPayload)
      if (splitResponse?.success) {
        toast.add({
          severity: 'success',
          summary: 'Success',
          detail: `Created ${splitResponse?.data?.created_count || splitRfqSupplierGroups.value || 'multiple'} draft RFQs grouped by supplier`,
          life: 3000
        })
        setTimeout(() => {
          router.push({ name: 'procurement.rfqs' })
        }, 1500)
        return
      }
    }

    const payload = {
      purchase_requisition_id: form.purchase_requisition_id,
      title: form.title,
      description: form.description,
      issue_date: form.issue_date instanceof Date
        ? form.issue_date.toISOString().split('T')[0]
        : form.issue_date,
      items: validItems.map(item => ({
        product_id: item.product_id,
        variation_id: item.variation_id,
        quantity: item.quantity,
        specifications: item.specifications,
        requirements: item.requirements || ''
      })),
      supplier_ids: selectedSupplierIds.value
    }

    const response = isEditMode.value && editingRfqId.value
      ? await procurementService.updateRFQ(editingRfqId.value, payload as any)
      : await procurementService.createRFQ(payload as any)

    if (response?.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: isEditMode.value ? 'RFQ updated successfully' : 'RFQ saved as draft successfully',
        life: 3000
      })
      setTimeout(() => {
        router.push({ name: 'procurement.rfqs' })
      }, 1500)
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response?.message || 'Failed to save draft',
        life: 3000
      })
    }
  } catch (error: any) {
    console.error('Failed to save draft:', error)

    if (error.response?.data?.errors) {
      const backendErrors = error.response.data.errors
      Object.entries(backendErrors).forEach(([key, value]: [string, any]) => {
        errors[key] = Array.isArray(value) ? value[0] : value
      })
    }

    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || error.message || 'Failed to save draft',
      life: 3000
    })
  } finally {
    saving.value = false
  }
}

const submitForm = async () => {
  if (!validateForm()) {
    toast.add({
      severity: 'error',
      summary: 'Validation Error',
      detail: 'Please complete required fields',
      life: 3000
    })
    return
  }

  if (!confirmTerms.value) {
    toast.add({
      severity: 'error',
      summary: 'Validation Error',
      detail: 'Please agree to the Terms and Conditions',
      life: 3000
    })
    return
  }

  if (!splitRfqMode.value && selectedSupplierIds.value.length === 0) {
    toast.add({
      severity: 'error',
      summary: 'Validation Error',
      detail: 'Please select at least one supplier',
      life: 3000
    })
    return
  }

  const validItems = form.items.filter(i => i.product_id)
  if (validItems.length === 0) {
    toast.add({
      severity: 'error',
      summary: 'Validation Error',
      detail: 'Please add at least one line item',
      life: 3000
    })
    return
  }

  saving.value = true
  try {
    if (splitRfqMode.value && form.purchase_requisition_id && !isEditMode.value) {
      const splitPayload = {
        purchase_requisition_id: form.purchase_requisition_id,
        title: form.title,
        description: form.description,
        issue_date: form.issue_date instanceof Date
          ? form.issue_date.toISOString().split('T')[0]
          : String(form.issue_date || ''),
        rfq_type: form.rfq_type,
        currency: form.currency,
        shipping_terms: form.shipping_terms,
        instructions: form.instructions,
        qualification_requirements: form.qualification_requirements,
      }

      const splitResponse = await procurementService.createRFQsFromRequisitionSplit(splitPayload)
      if (!splitResponse?.success) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: splitResponse?.message || 'Failed to create split RFQs',
          life: 3000
        })
        return
      }

      const rfqs = Array.isArray(splitResponse?.data?.rfqs) ? splitResponse.data.rfqs : []
      for (const rfq of rfqs) {
        const rfqId = Number(rfq?.id)
        if (!rfqId) continue
        await procurementService.sendRfq(rfqId, { invitation_method: form.invitation_method } as any)
      }

      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: `Created and sent ${splitResponse?.data?.created_count || rfqs.length || splitRfqSupplierGroups.value || 'multiple'} supplier-split RFQs successfully`,
        life: 3000
      })

      setTimeout(() => {
        router.push({ name: 'procurement.rfqs' })
      }, 1500)
      return
    }

    const payload = {
      purchase_requisition_id: form.purchase_requisition_id,
      title: form.title,
      description: form.description,
      issue_date: form.issue_date instanceof Date
        ? form.issue_date.toISOString().split('T')[0]
        : form.issue_date,
      rfq_type: form.rfq_type,
      currency: form.currency,
      shipping_terms: form.shipping_terms,
      instructions: form.instructions,
      qualification_requirements: form.qualification_requirements,
      items: validItems.map(item => ({
        product_id: item.product_id,
        variation_id: item.variation_id || null,
        quantity: item.quantity,
        specifications: item.specifications || '',
        requirements: item.requirements || ''
      })),
      supplier_ids: selectedSupplierIds.value
    }

    // Debug log removed: do not print RFQ payload in console

    const rfqResponse = isEditMode.value && editingRfqId.value
      ? await procurementService.updateRFQ(editingRfqId.value, payload as any)
      : await procurementService.createRFQ(payload as any)

    if (!rfqResponse?.success) {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: rfqResponse?.message || 'Failed to create RFQ',
        life: 3000
      })
      return
    }

    const rfqId = rfqResponse.data?.id || editingRfqId.value

    const sendResponse = await procurementService.sendRfq(rfqId, {
      supplier_ids: selectedSupplierIds.value,
      invitation_method: form.invitation_method,
    })

    if (sendResponse?.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: isEditMode.value ? 'RFQ updated and sent to suppliers successfully' : 'RFQ created and sent to suppliers successfully',
        life: 3000
      })

      setTimeout(() => {
        router.push({ name: 'procurement.rfqs.detail', params: { id: rfqId } })
      }, 1500)
    } else {
      toast.add({
        severity: 'warn',
        summary: 'Partial Success',
        detail: isEditMode.value ? 'RFQ updated but failed to send to some suppliers' : 'RFQ created but failed to send to some suppliers',
        life: 3000
      })

      setTimeout(() => {
        router.push({ name: 'procurement.rfqs.detail', params: { id: rfqId } })
      }, 1500)
    }
  } catch (error: any) {
    console.error('Failed to create RFQ:', error)

    if (error.response?.data?.errors) {
      const backendErrors = error.response.data.errors
      Object.entries(backendErrors).forEach(([key, value]: [string, any]) => {
        errors[key] = Array.isArray(value) ? value[0] : value
      })
    }

    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || error.message || 'Failed to create RFQ',
      life: 3000
    })
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  loading.value = true
  try {
    const [productsRes, suppliersRes] = await Promise.all([
      procurementService.getProcurementProducts({ per_page: 1000 }).catch(err => {
        console.error('Failed to load products:', err)
        return null
      }),
      procurementService.getSuppliers({ per_page: 1000 }).catch(err => {
        console.error('Failed to load suppliers:', err)
        return null
      })
    ])

    if (productsRes?.data) {
      const productList = productsRes.data.data || (Array.isArray(productsRes.data) ? productsRes.data : [])
      products.value = productList.map((product: any) => ({
        id: product.id,
        product_name: product.product_name || 'Unknown Product',
        sku: product.sku || '',
        brand: product.brand || '',
      }))
    } else {
      toast.add({
        severity: 'warn',
        summary: 'Warning',
        detail: 'Failed to load products',
        life: 3000
      })
    }

    if (suppliersRes?.data) {
      let supplierList = suppliersRes.data.data || (Array.isArray(suppliersRes.data) ? suppliersRes.data : [])

      suppliers.value = supplierList.map((supplier: any) => ({
        id: supplier.id,
        name: supplier.supplier_name || supplier.name || `${supplier.fname || ''} ${supplier.lname || ''}`.trim(),
        email: supplier.email || '',
        ...supplier
      }))
    } else {
      toast.add({
        severity: 'warn',
        summary: 'Warning',
        detail: 'Failed to load suppliers',
        life: 3000
      })
    }

    if (route.query.requisition_id) {
      const requisitionId = parseInt(route.query.requisition_id as string)
      const requisitionRes = await procurementService.getPurchaseRequisition(requisitionId).catch(() => null)
      const requisition = requisitionRes?.data || requisitionRes?.data?.data || requisitionRes
      prefillFromRequisition(requisition)
    }

    if (route.query.rfq_id) {
      const rfqId = parseInt(route.query.rfq_id as string)
      if (!isNaN(rfqId)) {
        editingRfqId.value = rfqId
        await loadRfqForEdit(rfqId)
      }
    }
  } catch (error) {
    console.error('Failed to load data:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load required data. Please refresh the page.',
      life: 3000
    })
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
/* Smooth transitions */
* {
  transition: background-color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
}

/* iOS-style shadows */
:deep(.p-card) {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.02);
}

:deep(.p-card .p-card-content) {
  padding: 0;
}

:deep(.p-card .p-card-body) {
  padding: 0;
}

/* iOS-style inputs */
:deep(.p-inputtext),
:deep(.p-select),
:deep(.p-textarea),
:deep(.p-multiselect),
:deep(.p-datepicker),
:deep(.p-inputnumber) {
  border-radius: 12px;
  border-color: #e5e7eb;
  background-color: #f9fafb;
  transition: all 0.2s ease;
}

:deep(.p-inputtext:focus),
:deep(.p-select:focus),
:deep(.p-textarea:focus),
:deep(.p-multiselect:focus),
:deep(.p-datepicker:focus) {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* iOS-style buttons */
:deep(.p-button) {
  border-radius: 12px;
  font-weight: 500;
  transition: all 0.2s ease;
}

:deep(.p-button.p-button-text) {
  border-radius: 9999px;
}

:deep(.p-button.p-button-text:hover) {
  background-color: #f3f4f6;
}

/* iOS-style dialogs */
:deep(.p-dialog) {
  border-radius: 24px;
  overflow: hidden;
}

:deep(.p-dialog .p-dialog-header) {
  padding: 1.5rem 1.5rem 0.5rem;
  border-bottom: 1px solid #f3f4f6;
}

:deep(.p-dialog .p-dialog-content) {
  padding: 1rem 1.5rem;
}

:deep(.p-dialog .p-dialog-footer) {
  padding: 1rem 1.5rem 1.5rem;
  border-top: 1px solid #f3f4f6;
}

/* Custom scrollbar */
::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
  background: #a1a1a1;
}

/* Sticky footer animation */
.sticky {
  animation: slideUp 0.3s ease;
}

@keyframes slideUp {
  from {
    transform: translateY(100%);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}
</style>