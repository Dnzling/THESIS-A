<template>
  <div class="max-w-7xl mx-auto space-y-6 pb-6 p-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div class="flex items-center gap-3">
        <Button icon="pi pi-arrow-left" text rounded @click="router.push('/crm/returns')" />
        <div>
          <h2 class="text-2xl font-bold text-gray-900">Return Detail</h2>
          <p class="text-sm text-gray-500 mt-1">Review evidence, item info, and status.</p>
        </div>
      </div>
  
    </div>
  
    <div v-if="loading" class="space-y-5">
      <Skeleton height="8rem" class="rounded-2xl" />
      <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <Skeleton v-for="i in 3" :key="i" height="6rem" class="rounded-2xl" />
      </div>
      <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <Skeleton height="24rem" class="rounded-2xl lg:col-span-2" />
        <Skeleton height="18rem" class="rounded-2xl" />
      </div>
    </div>
  
    <div v-else-if="returnRequest" class="space-y-6">
      <Card class="overflow-hidden rounded-2xl border border-slate-200 shadow-sm">
        <template #header>
          <div
            class="flex flex-col gap-4 border-b border-slate-100 bg-slate-50/80 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
              <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-orange-100 text-orange-700"><i
                  class="pi pi-replay text-lg"></i></span>
              <div>
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Return request</p>
                <h2 class="text-xl font-semibold text-slate-900">{{ returnRequest.return_number || '—' }}</h2>
              </div>
            </div>
            <Tag :value="prettyStatus(returnRequest.status)" :severity="statusSeverity(returnRequest.status)" />
          </div>
        </template>
        <template #content>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-slate-200 p-4">
              <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Order</p>
              <p class="mt-1 font-semibold text-slate-900">{{ returnRequest.order?.order_number || `Order
                #${returnRequest.order_id}` }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 p-4">
              <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Requested on</p>
              <p class="mt-1 font-semibold text-slate-900">{{ formatDateTime(returnRequest.created_at) }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 p-4">
              <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Quantity</p>
              <p class="mt-1 font-semibold text-slate-900">{{ Number(returnRequest.requested_quantity ||
                1).toLocaleString() }} item(s)</p>
            </div>
            <div class="rounded-xl border border-slate-200 p-4">
              <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Resolution</p>
              <p class="mt-1 font-semibold text-slate-900">{{ returnRequest.return_type ?
                prettyStatus(returnRequest.return_type) : 'Pending review' }}</p>
            </div>
          </div>
  
          <div class="mt-6 border-t border-slate-100 pt-5">
            <div class="mb-3 flex items-center gap-2">
              <i class="pi pi-user text-slate-500"></i>
              <h3 class="text-sm font-semibold text-slate-800">Customer Information</h3>
            </div>
            <div class="grid grid-cols-1 gap-x-8 gap-y-3 sm:grid-cols-2">
              <div class="min-w-0">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Customer Name</p>
                <p class="mt-1 font-semibold text-slate-900">{{ returnRequest.user?.full_name ||
                  [returnRequest.user?.fname, returnRequest.user?.lname].filter(Boolean).join(' ') ||
                  returnRequest.order?.shipping_name || '—' }}</p>
              </div>
              <div class="min-w-0">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Email</p>
                <p class="mt-1 break-all font-medium text-slate-800">{{ returnRequest.user?.email ||
                  returnRequest.order?.shipping_email || '—' }}</p>
              </div>
              <div class="min-w-0">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Delivery Contact</p>
                <p class="mt-1 font-medium text-slate-800">{{ returnRequest.order?.shipping_name ||
                  returnRequest.user?.full_name || '—' }}</p>
              </div>
              <div class="min-w-0">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Delivery Email</p>
                <p class="mt-1 break-all font-medium text-slate-800">{{ returnRequest.order?.shipping_email ||
                  returnRequest.user?.email || '—' }}</p>
              </div>
              <div class="min-w-0">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Delivery Phone</p>
                <p class="mt-1 font-medium text-slate-800">{{ returnRequest.order?.shipping_phone || '—' }}</p>
              </div>
              <div class="min-w-0">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Order Status</p>
                <p class="mt-1 font-medium text-slate-800">{{ prettyStatus(returnRequest.order?.status) }}</p>
              </div>
              <div class="min-w-0 sm:col-span-2">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Delivery Address</p>
                <p class="mt-1 whitespace-pre-line font-medium text-slate-800">{{ returnRequest.order?.shipping_address ||
                  '—' }}</p>
              </div>
            </div>
          </div>
        </template>
      </Card>
  
      <div class="grid gap-6">
        <div class="space-y-6">
          <Card class="rounded-2xl border border-slate-200 shadow-sm">
            <template #title>
              <div class="flex items-center gap-2"><i class="pi pi-box text-slate-500"></i><span>Returned Item</span>
              </div>
            </template>
            <template #content>
              <div class="overflow-x-auto rounded-xl border border-slate-200">
                <div
                  class="grid min-w-[850px] grid-cols-[minmax(190px,2fr)_minmax(120px,1fr)_minmax(75px,.65fr)_minmax(125px,1fr)_minmax(125px,1fr)_minmax(70px,.6fr)_minmax(135px,1fr)] gap-3 bg-slate-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                  <span>Product</span><span>SKU</span><span>Unit</span><span class="text-right">Base Price</span><span
                    class="text-right">Order Unit Price</span><span class="text-right">Qty</span><span
                    class="text-right">Return Total</span>
                </div>
                <div
                  class="grid min-w-[850px] grid-cols-[minmax(190px,2fr)_minmax(120px,1fr)_minmax(75px,.65fr)_minmax(125px,1fr)_minmax(125px,1fr)_minmax(70px,.6fr)_minmax(135px,1fr)] items-center gap-3 px-4 py-4 text-sm">
                  <div>
                    <p class="font-semibold text-slate-900">{{ returnRequest.order_item?.product?.product_name ||
                      returnRequest.order_item?.product_name || 'Product unavailable' }}</p>
                    <p v-if="itemVariantName" class="mt-0.5 text-xs text-slate-500">{{ itemVariantName }}</p>
                  </div>
                  <span class="break-all text-slate-600">{{ itemSku }}</span>
                  <span class="text-slate-600">{{ itemUnit }}</span>
                  <span class="text-right text-slate-700">{{ formatMoney(itemBasePrice) }}</span>
                  <span class="text-right text-slate-700">{{ formatMoney(itemUnitPrice) }}</span>
                  <span class="text-right font-medium text-slate-900">{{ Number(returnRequest.requested_quantity ||
                    1).toLocaleString() }}</span>
                  <span class="text-right font-semibold text-slate-900">{{ formatMoney(returnTotal) }}</span>
                </div>
              </div>
              <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="rounded-xl bg-slate-50 p-4">
                  <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Return reason</p>
                  <p class="mt-2 whitespace-pre-line text-sm font-medium text-slate-900">{{ returnRequest.reason || '—' }}
                  </p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                  <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Customer details</p>
                  <p class="mt-2 font-medium text-slate-900">{{ returnRequest.details || 'No additional details provided.'
                    }}</p>
                </div>
              </div>
            </template>
          </Card>
  
          <Card class="rounded-2xl border border-slate-200 shadow-sm">
            <template #title>
              <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2"><i
                    class="pi pi-paperclip text-slate-500"></i><span>Attachments</span></div><span
                  class="text-xs font-normal text-slate-500">{{ attachmentsCount }} file(s)</span>
              </div>
            </template>
            <template #content>
              <div v-if="attachments.length" class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                <button v-for="(attachment, index) in attachments" :key="attachment.url" type="button"
                  class="group relative aspect-square overflow-hidden rounded-xl border border-slate-200 bg-slate-50 text-left"
                  @click="openImageZoom(attachment)">
                  <img :src="attachment.url" :alt="attachment.name"
                    class="h-full w-full object-cover transition group-hover:scale-105" />
                  <span
                    class="absolute inset-x-0 bottom-0 flex items-center justify-between bg-slate-950/65 px-2 py-1.5 text-xs text-white"><span>{{
                      attachment.name }}</span><i class="pi pi-search-plus"></i></span>
                </button>
              </div>
              <div v-else
                class="rounded-xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500"><i
                  class="pi pi-image mb-2 block text-xl text-slate-400"></i>No attachments were provided with this
                request.</div>
            </template>
          </Card>
        </div>
  
        <aside class="space-y-6">
          <Card v-if="returnRequest.return_type" class="rounded-2xl border border-slate-200 shadow-sm">
            <template #title>
              <div class="flex items-center gap-2"><i class="pi pi-check-square text-slate-500"></i><span>Inspection &
                  Resolution</span></div>
            </template>
            <template #content>
              <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-3">
                  <dt class="text-slate-500">Resolution</dt>
                  <dd class="font-semibold text-slate-900">{{ prettyStatus(returnRequest.return_type) }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                  <dt class="text-slate-500">Condition</dt>
                  <dd class="font-semibold text-slate-900">{{ returnRequest.product_condition ?
                    prettyStatus(returnRequest.product_condition) : 'Pending inspection' }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                  <dt class="text-slate-500">Disposition</dt>
                  <dd class="font-semibold text-slate-900">{{ returnRequest.inventory_disposition ?
                    prettyStatus(returnRequest.inventory_disposition) : 'Pending inspection' }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                  <dt class="text-slate-500">Inspected</dt>
                  <dd class="font-semibold text-slate-900">{{ returnRequest.inspected_at ?
                    formatDateTime(returnRequest.inspected_at) : 'Pending inspection' }}</dd>
                </div>
              </dl>
              <p v-if="returnRequest.inspection_notes" class="mt-4 border-t border-slate-100 pt-3 text-sm text-slate-600">
                {{ returnRequest.inspection_notes }}</p>
            </template>
          </Card>
        </aside>
      </div>
  
      <Card v-if="investigationTicket || (returnRequest.status === 'pending_verification' && canCreateInvestigation)"
        class="rounded-2xl border border-slate-200 shadow-sm">
        <template #title>
          <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2"><i class="pi pi-search text-slate-500"></i><span>Return
                Investigation</span></div>
            <Button
              v-if="!investigationTicket && returnRequest.status === 'pending_verification' && canCreateInvestigation"
              icon="pi pi-plus" label="Create Investigation Ticket" size="small" @click="openInvestigationDialog" />
          </div>
        </template>
        <template #content>
          <div v-if="investigationTicket" class="space-y-4">
            <div class="flex flex-wrap items-center gap-3">
              <Tag :value="prettyStatus(investigationTicket.status)" severity="info" />
              <span class="text-sm text-slate-600">Expected investigation: <strong class="font-semibold text-slate-900">{{
                  formatDateOnly(investigationTicket.expected_investigation_date) }}</strong></span>
            </div>
            <div>
              <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Assigned investigation team</p>
              <div class="flex flex-wrap gap-2">
                <span v-for="employee in investigationTicket.assignees || []" :key="employee.id"
                  class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-sm text-slate-700">
                  {{ investigationEmployeeName(employee) }}<span v-if="employee.employee_number"
                    class="ml-1 text-xs text-slate-500">· {{ employee.employee_number }}</span>
                </span>
                <span v-if="!(investigationTicket.assignees || []).length" class="text-sm text-slate-500">No assignees
                  recorded.</span>
              </div>
            </div>
            <div v-if="investigationTicket.notes" class="rounded-xl bg-slate-50 p-4">
              <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Investigation notes</p>
              <p class="whitespace-pre-line text-sm text-slate-700">{{ investigationTicket.notes }}</p>
            </div>
          </div>
          <p v-else class="text-sm text-slate-600">Assign a CRM investigation team and target date for this pending
            return.</p>
        </template>
      </Card>
  
      <Card class="rounded-2xl border border-slate-200 shadow-sm">
        <template #title>
          <div class="flex items-center gap-2">
            <i class="pi pi-info-circle text-slate-500"></i>
            <span>Review Notes</span>
          </div>
        </template>
        <template #content>
          <div class="text-sm text-gray-700 whitespace-pre-line">
            {{ returnRequest.review_notes || 'No review notes yet.' }}
          </div>
        </template>
      </Card>
  
      <Card class="rounded-2xl border border-slate-200 shadow-sm">
        <template #title>
          <div class="flex items-center gap-2">
            <i class="pi pi-history text-slate-500"></i>
            <span>Activity</span>
          </div>
        </template>
        <template #content>
          <div v-if="activityTimeline.length" class="divide-y divide-slate-100">
            <div v-for="(item, index) in activityTimeline" :key="`${item.title}-${index}`"
              class="flex items-start gap-3 py-3 first:pt-0 last:pb-0">
              <span
                class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-600"><i
                  :class="item.icon"></i></span>
              <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-slate-900">{{ item.title }}</p>
                <p class="mt-0.5 text-xs text-slate-500">{{ item.subtitle }}</p>
              </div>
            </div>
          </div>
          <p v-else class="text-sm text-slate-500">No activity recorded yet.</p>
        </template>
      </Card>
  
      <div class="flex flex-wrap justify-end gap-2">
        <Button v-if="canReject" icon="pi pi-times" label="Reject" size="small" :loading="statusUpdating"
          @click="openNotesThenConfirm('rejected')" />
        <Button v-if="canApprove" icon="pi pi-check" :label="approvalButtonLabel" size="small" :loading="statusUpdating"
          @click="openApproval" />
  
        <Button v-if="canMarkReceived" icon="pi pi-box" label="Inventory Inspection" outlined size="small"
          :loading="statusUpdating" @click="receiveDialogVisible = true" />
        <Button v-if="canSchedulePickup" icon="pi pi-calendar-plus" label="Schedule Pickup" outlined size="small"
          :loading="pickupScheduling" @click="pickupDialogVisible = true" />
        <Button v-if="returnRequest?.pickup?.id" icon="pi pi-truck" label="Open Pickup" outlined size="small"
          @click="openPickup" />
      </div>
    </div>
  
  
    <div v-else class="py-10 text-center text-sm text-gray-600">
      <p>{{ loadError || 'Return request not found.' }}</p>
      <Button v-if="loadError" label="Try again" icon="pi pi-refresh" outlined size="small" class="mt-3"
        @click="loadReturn" />
    </div>
  
    <Dialog v-model:visible="pickupDialogVisible" header="Schedule Pickup" modal class="w-full max-w-xl">
      <div class="space-y-3">
        <p class="text-sm text-gray-600">Create a Logistics pickup job for this approved return.</p>
        <div>
          <label class="mb-1 block text-sm text-gray-600">Scheduled At</label>
          <DatePicker v-model="pickupForm.scheduled_at" :minDate="getTodayDate()" showIcon showTime hourFormat="12"
            class="w-full" />
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="mb-1 block text-sm text-gray-600">Pickup Name</label>
            <InputText v-model="pickupForm.pickup_name" class="w-full" />
          </div>
          <div>
            <label class="mb-1 block text-sm text-gray-600">Pickup Phone</label>
            <InputText v-model="pickupForm.pickup_phone" class="w-full" />
          </div>
        </div>
        <div>
          <label class="mb-1 block text-sm text-gray-600">Pickup Address</label>
          <Textarea v-model="pickupForm.pickup_address" rows="3" class="w-full" autoResize />
        </div>
        <div>
          <label class="mb-1 block text-sm text-gray-600">Notes</label>
          <Textarea v-model="pickupForm.notes" rows="2" class="w-full" autoResize />
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" outlined size="small" @click="pickupDialogVisible = false" />
        <Button icon="pi pi-check" label="Create Pickup" size="small" :loading="pickupScheduling"
          :disabled="!pickupForm.scheduled_at" @click="schedulePickup" />
      </template>
    </Dialog>
  
    <Dialog v-model:visible="investigationDialogVisible" header="Create Return Investigation Ticket" modal
      class="w-full max-w-2xl">
      <div class="space-y-4">
        <p class="text-sm text-slate-600">Assign active employees who have <code
            class="rounded bg-slate-100 px-1 py-0.5 text-xs">crm.view</code> permission to investigate this pending
          return.</p>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Investigation Team <span
              class="text-red-500">*</span></label>
          <MultiSelect v-model="investigationForm.assigned_employee_ids" :options="investigationEmployeeOptions"
            optionLabel="name" optionValue="value" display="chip" filter :loading="loadingInvestigationEmployees"
            placeholder="Select one or more eligible employees" class="w-full">
            <template #option="{ option }">
              <div class="min-w-0">
                <p class="truncate text-sm font-medium text-slate-800">{{ option.name }}</p>
                <p class="truncate text-xs text-slate-500">{{ [option.employee_number, option.department,
                  option.email].filter(Boolean).join(' · ') }}</p>
              </div>
            </template>
          </MultiSelect>
          <p v-if="!loadingInvestigationEmployees && !investigationEmployeeOptions.length"
            class="mt-1 text-xs text-amber-700">No active employees with crm.view permission are available in this store.
          </p>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Expected Investigation Date <span
              class="text-red-500">*</span></label>
          <DatePicker v-model="investigationForm.expected_investigation_date" :minDate="new Date()" dateFormat="M d, yy"
            showIcon class="w-full" placeholder="Select expected date" />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Notes</label>
          <Textarea v-model="investigationForm.notes" rows="4" class="w-full" autoResize
            placeholder="Add investigation instructions or context for the team" />
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" outlined size="small" :disabled="creatingInvestigationTicket"
          @click="investigationDialogVisible = false" />
        <Button icon="pi pi-check" label="Create Ticket & Notify Team" size="small" :loading="creatingInvestigationTicket"
          :disabled="!investigationForm.assigned_employee_ids.length || !investigationForm.expected_investigation_date"
          @click="createInvestigationTicket" />
      </template>
    </Dialog>
  
    <Dialog v-model:visible="receiveDialogVisible" header="Receive & Inspect Return (Inventory)" modal
      class="w-full max-w-xl">
      <div class="space-y-3">
        <p class="text-sm text-gray-600">Good items return to sellable stock; bad items are discarded. Refunds are sent
          automatically to Finance, while replacements are issued from available stock.</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="mb-1 block text-sm text-gray-600">Received Quantity</label>
            <InputNumber v-model="receiveForm.received_quantity" :min="1"
              :max="Number(returnRequest?.requested_quantity ?? 1)" class="w-full" />
          </div>
          <div>
            <label class="mb-1 block text-sm text-gray-600">Product Quality</label>
            <Select v-model="receiveForm.condition" :options="receiveConditionOptions" optionLabel="label"
              optionValue="value" class="w-full" />
          </div>
        </div>
        <div>
          <label class="mb-1 block text-sm text-gray-600">Notes</label>
          <Textarea v-model="receiveForm.notes" rows="2" class="w-full" autoResize />
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" outlined size="small" @click="receiveDialogVisible = false" />
        <Button icon="pi pi-check" label="Complete Inspection" size="small" :loading="receiving"
          @click="confirmReceive" />
      </template>
    </Dialog>
  
    <Dialog v-model:visible="refundDialogVisible" header="Create Refund (Finance)" modal class="w-full max-w-xl">
      <div class="space-y-3">
        <p class="text-sm text-gray-600">This will create a finance refund record. Optionally mark as approved to set
          return as refunded.</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="mb-1 block text-sm text-gray-600">Amount</label>
            <InputNumber v-model="refundForm.amount" :min="0" mode="currency" currency="PHP" locale="en-PH"
              class="w-full" />
          </div>
          <div class="flex items-end">
            <div class="flex items-center gap-2">
              <InputSwitch v-model="refundForm.mark_as_approved" />
              <span class="text-sm text-gray-700">Mark as approved</span>
            </div>
          </div>
        </div>
        <div>
          <label class="mb-1 block text-sm text-gray-600">Reason</label>
          <Textarea v-model="refundForm.reason" rows="2" class="w-full" autoResize />
        </div>
        <div>
          <label class="mb-1 block text-sm text-gray-600">Notes</label>
          <Textarea v-model="refundForm.notes" rows="2" class="w-full" autoResize />
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" outlined size="small" @click="refundDialogVisible = false" />
        <Button icon="pi pi-check" label="Create Refund" size="small" :loading="refunding" @click="confirmRefund" />
      </template>
    </Dialog>
  
    <Dialog v-model:visible="notesDialogVisible" header="Review Notes" modal class="w-full max-w-xl">
      <div class="space-y-3">
        <p class="text-sm text-gray-600">
          Add a short note for the customer/internal team. Notes are required when rejecting.
        </p>
        <div v-if="pendingStatus === 'approved'" class="space-y-2">
          <label class="block text-sm text-gray-600">Return Type</label>
          <Select v-model="pendingReturnType" :options="returnTypeOptions" optionLabel="label" optionValue="value"
            placeholder="Select refund or replacement" class="w-full" />
        </div>
        <div v-if="pendingStatus === 'rejected'" class="space-y-2">
          <label class="block text-sm text-gray-600">Reject Reason</label>
          <Select v-model="pendingRejectReason" :options="rejectReasonOptions" optionLabel="label" optionValue="value"
            placeholder="Select a reason" fluid showClear />
        </div>
        <Textarea v-model="pendingReviewNotes" rows="5" class="w-full" placeholder="Type notes..." autoResize />
      </div>
      <template #footer>
        <Button label="Cancel" outlined size="small" @click="closeNotesDialog" />
        <Button label="Continue" size="small" :disabled="notesContinueDisabled" @click="confirmUpdate(pendingStatus)" />
      </template>
    </Dialog>
  
    <Dialog v-model:visible="imageZoomVisible" header="Preview" modal class="w-full max-w-6xl">
      <div class="flex justify-center bg-black/5 rounded-lg overflow-hidden">
        <img v-if="zoomImageUrl" :src="zoomImageUrl" alt="Preview" class="max-h-[75vh] w-auto object-contain" />
      </div>
      <template #footer>
        <Button label="Close" outlined size="small" @click="imageZoomVisible = false" />
      </template>
    </Dialog>
  
    <ConfirmDialog />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import Skeleton from 'primevue/skeleton'
import { useAuthStore } from '@/stores/auth'
import crmService from '@/services/crm.service'

const router = useRouter()
const route = useRoute()
const toast = useToast()
const confirm = useConfirm()
const authStore = useAuthStore()

const id = computed(() => {
  const pathMatch = String((route as any).path || window.location.pathname).match(/^\/crm\/returns\/(\d+)/)
  const routeId = Number((route as any).params?.id)
  return Number(pathMatch?.[1] || routeId || 0)
})
const loading = ref(false)
const returnRequest = ref<any>(null)
const loadError = ref('')
const statusUpdating = ref(false)
const investigationTicket = computed(() => returnRequest.value?.investigation_ticket || null)
const canCreateInvestigation = computed(() => authStore.hasPermission('crm.returns.manage'))

const itemVariant = computed(() => returnRequest.value?.order_item?.branch_inventory?.variation || null)
const itemVariantName = computed(() => {
  const variant = itemVariant.value
  if (!variant) return ''
  return variant.variation_name || [variant.color, variant.size, variant.material, variant.texture, variant.finish].filter(Boolean).join(' / ') || ''
})
const itemSku = computed(() => returnRequest.value?.order_item?.sku || itemVariant.value?.variation_sku || returnRequest.value?.order_item?.product?.sku || '—')
const itemUnit = computed(() => itemVariant.value?.unit_of_measurement || returnRequest.value?.order_item?.product?.unit_of_measurement || '—')
const itemBasePrice = computed(() => Number(itemVariant.value?.base_price ?? returnRequest.value?.order_item?.product?.base_price ?? 0))
const itemUnitPrice = computed(() => Number(returnRequest.value?.order_item?.unit_price ?? 0))
const returnTotal = computed(() => itemUnitPrice.value * Number(returnRequest.value?.requested_quantity || 1))
const formatMoney = (amount: number) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount || 0)

const imageZoomVisible = ref(false)
const zoomImageUrl = ref<string | null>(null)
const openImageZoom = (item: { url: string }) => {
  zoomImageUrl.value = item.url
  imageZoomVisible.value = true
}

const attachments = computed<{ url: string; name: string }[]>(() => {
  const urls: string[] = Array.isArray(returnRequest.value?.evidence_urls) ? returnRequest.value.evidence_urls : []
  return urls.map((url, idx) => ({ url, name: `Attachment ${idx + 1}` }))
})

const attachmentsCount = computed(() => attachments.value.length)

const investigationDialogVisible = ref(false)
const loadingInvestigationEmployees = ref(false)
const creatingInvestigationTicket = ref(false)
const investigationEmployeeOptions = ref<any[]>([])
const investigationForm = reactive({
  assigned_employee_ids: [] as number[],
  expected_investigation_date: null as Date | null,
  notes: '',
})

const investigationEmployeeName = (employee: any) => employee?.user?.full_name
  || [employee?.user?.fname, employee?.user?.lname].filter(Boolean).join(' ')
  || 'Unnamed employee'

const formatDateOnly = (value: any) => {
  if (!value) return 'Not set'
  const date = /^\d{4}-\d{2}-\d{2}$/.test(String(value)) ? new Date(`${value}T00:00:00`) : new Date(value)
  return Number.isNaN(date.getTime()) ? String(value) : new Intl.DateTimeFormat('en-PH', {
    year: 'numeric', month: 'short', day: '2-digit',
  }).format(date)
}

const loadInvestigationEmployees = async () => {
  loadingInvestigationEmployees.value = true
  try {
    const res = await crmService.getReturnInvestigationAssignees()
    investigationEmployeeOptions.value = Array.isArray(res?.data) ? res.data : []
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to load eligible investigators.', life: 3000 })
  } finally {
    loadingInvestigationEmployees.value = false
  }
}

const openInvestigationDialog = async () => {
  investigationForm.assigned_employee_ids = []
  investigationForm.expected_investigation_date = null
  investigationForm.notes = ''
  investigationDialogVisible.value = true
  await loadInvestigationEmployees()
}

const dateForApi = (date: Date) => {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const createInvestigationTicket = async () => {
  if (!investigationForm.assigned_employee_ids.length || !investigationForm.expected_investigation_date) return
  creatingInvestigationTicket.value = true
  try {
    const res = await crmService.createReturnInvestigationTicket(id.value, {
      assigned_employee_ids: investigationForm.assigned_employee_ids.map(Number),
      expected_investigation_date: dateForApi(investigationForm.expected_investigation_date),
      notes: investigationForm.notes.trim() || undefined,
    })
    investigationDialogVisible.value = false
    toast.add({ severity: 'success', summary: 'Ticket created', detail: res?.message || 'Investigation ticket created and team notified.', life: 3000 })
    await loadReturn()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Could not create ticket', detail: error?.response?.data?.message || 'Failed to create the investigation ticket.', life: 3500 })
  } finally {
    creatingInvestigationTicket.value = false
  }
}

const loadReturn = async () => {
  if (!Number.isInteger(id.value) || id.value <= 0) {
    returnRequest.value = null
    loadError.value = 'The return ID could not be read from this page URL.'
    return
  }

  loading.value = true
  loadError.value = ''
  try {
    const res = await crmService.getReturn(id.value)
    const record = res?.data?.data ?? res?.data ?? null
    returnRequest.value = record?.id ? record : null
    if (!returnRequest.value) loadError.value = 'No return request was found for this ID.'
  } catch (error: any) {
    returnRequest.value = null
    loadError.value = error?.response?.data?.message || 'Unable to load this return request.'
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: loadError.value,
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

const canApprove = computed(() => {
  const status = String(returnRequest.value?.status || '')
  if (status === 'pending_verification') return true
  if (status !== 'approved' || returnRequest.value?.inspected_at) return false
  return String(returnRequest.value?.pickup?.status || '') !== 'picked_up'
})
const approvalButtonLabel = computed(() => {
  if (String(returnRequest.value?.status || '') !== 'approved') return 'Approve'
  return returnRequest.value?.return_type ? 'Change Return Type' : 'Set Return Type'
})
const canReject = computed(() => ['pending_verification', 'approved'].includes(String(returnRequest.value?.status || '')))
const canMarkReceived = computed(() => {
  if (String(returnRequest.value?.status || '') !== 'approved') return false
  const pickupStatus = String(returnRequest.value?.pickup?.status || '')
  return pickupStatus === 'picked_up'
})
const canSchedulePickup = computed(() => String(returnRequest.value?.status || '') === 'approved' && !returnRequest.value?.pickup?.id)

const openOrder = () => {
  const orderId = returnRequest.value?.order_id
  if (!orderId) return
  router.push({ name: 'sales.ecommerce-orders.detail', params: { id: orderId } })
}

const openPickup = () => {
  const pickupId = returnRequest.value?.pickup?.id
  if (!pickupId) return
  router.push({ name: 'logistics.return-pickups.detail', params: { id: pickupId } })
}

const pickupDialogVisible = ref(false)
const pickupScheduling = ref(false)
const pickupForm = reactive({
  scheduled_at: null as any,
  pickup_name: '',
  pickup_phone: '',
  pickup_address: '',
  notes: '',
})

const getTodayDate = () => new Date()

const toIsoDateTime = (date: Date) => {
  const d = new Date(date)
  const yyyy = d.getFullYear()
  const mm = String(d.getMonth() + 1).padStart(2, '0')
  const dd = String(d.getDate()).padStart(2, '0')
  const hh = String(d.getHours()).padStart(2, '0')
  const mi = String(d.getMinutes()).padStart(2, '0')
  const ss = String(d.getSeconds()).padStart(2, '0')
  return `${yyyy}-${mm}-${dd} ${hh}:${mi}:${ss}`
}

const schedulePickup = async () => {
  if (!pickupForm.scheduled_at) return
  pickupScheduling.value = true
  try {
    const payload = {
      scheduled_at: toIsoDateTime(pickupForm.scheduled_at),
      pickup_name: pickupForm.pickup_name || undefined,
      pickup_phone: pickupForm.pickup_phone || undefined,
      pickup_address: pickupForm.pickup_address || undefined,
      notes: pickupForm.notes || undefined,
    }
    const res = await crmService.scheduleReturnPickup(id.value, payload)
    returnRequest.value = res?.data || returnRequest.value
    toast.add({ severity: 'success', summary: 'Scheduled', detail: res?.message || 'Pickup scheduled.', life: 2500 })
    pickupDialogVisible.value = false
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Failed', detail: error?.response?.data?.message || 'Failed to schedule pickup.', life: 3000 })
  } finally {
    pickupScheduling.value = false
  }
}

watch(
  () => pickupDialogVisible.value,
  (visible) => {
    if (!visible) return
    const order = returnRequest.value?.order || {}
    pickupForm.scheduled_at = null
    pickupForm.pickup_name = order.shipping_name || returnRequest.value?.user?.name || ''
    pickupForm.pickup_phone = order.shipping_phone || ''
    pickupForm.pickup_address = order.shipping_address || ''
    pickupForm.notes = ''
  },
)

const notesDialogVisible = ref(false)
const pendingStatus = ref<'approved' | 'rejected' | 'received' | 'refunded'>('approved')
const pendingReviewNotes = ref('')
const pendingReturnType = ref<'refund' | 'replacement' | null>(null)
const returnTypeOptions = [
  { label: 'Refund (send to Finance after inspection)', value: 'refund' },
  { label: 'Replacement (Inventory issues replacement stock)', value: 'replacement' },
]
const pendingRejectReason = ref<string | null>(null)
const rejectReasonOptions = [
  { label: 'Not eligible / outside return policy', value: 'Not eligible / outside return policy' },
  { label: 'Insufficient evidence', value: 'Insufficient evidence' },
  { label: 'Item already used / damaged by customer', value: 'Item already used / damaged by customer' },
  { label: 'Return window expired', value: 'Return window expired' },
  { label: 'Other', value: 'Other' },
]

const receiveDialogVisible = ref(false)
const receiving = ref(false)
const receiveConditionOptions = [
  { label: 'Good condition (Resell)', value: 'good' },
  { label: 'Bad condition (Discard)', value: 'bad' },
]
const receiveForm = reactive({
  received_quantity: 1,
  condition: 'good' as 'good' | 'bad',
  notes: '',
})

const confirmReceive = () => {
  const maxQty = Number(returnRequest.value?.requested_quantity ?? 1)
  const qty = Number(receiveForm.received_quantity || 1)
  if (qty < 1 || qty > maxQty) {
    toast.add({ severity: 'warn', summary: 'Invalid', detail: `Received quantity must be between 1 and ${maxQty}.`, life: 2500 })
    return
  }
  confirm.require({
    header: 'Post inventory receive?',
    message: returnRequest.value?.return_type === 'refund'
      ? 'Inventory will record the item disposition and automatically send the product-price refund to Finance.'
      : 'Inventory will record the item disposition and deduct replacement stock.',
    icon: 'pi pi-exclamation-triangle',
    rejectProps: { label: 'Cancel', outlined: true, size: 'small' },
    acceptProps: { label: 'Confirm', size: 'small' },
    accept: async () => {
      await postReceive()
    },
  })
}

const postReceive = async () => {
  receiving.value = true
  try {
    const res = await crmService.receiveReturn(id.value, {
      received_quantity: Number(receiveForm.received_quantity || 1),
      condition: receiveForm.condition,
      notes: receiveForm.notes || undefined,
    })
    returnRequest.value = res?.data || returnRequest.value
    toast.add({ severity: 'success', summary: 'Received', detail: res?.message || 'Inventory updated.', life: 2500 })
    receiveDialogVisible.value = false
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Failed', detail: error?.response?.data?.message || 'Failed to receive return.', life: 3000 })
  } finally {
    receiving.value = false
  }
}

const refundDialogVisible = ref(false)
const refunding = ref(false)
const refundForm = reactive({
  amount: 0,
  reason: '',
  notes: '',
  mark_as_approved: true,
})

const confirmRefund = () => {
  confirm.require({
    header: 'Create refund record?',
    message: refundForm.mark_as_approved ? 'This will create an approved refund and mark the return as refunded.' : 'This will create a pending refund record.',
    icon: 'pi pi-exclamation-triangle',
    rejectProps: { label: 'Cancel', outlined: true, size: 'small' },
    acceptProps: { label: 'Confirm', size: 'small' },
    accept: async () => {
      await postRefund()
    },
  })
}

const postRefund = async () => {
  refunding.value = true
  try {
    const res = await crmService.createReturnRefund(id.value, {
      amount: Number(refundForm.amount || 0),
      reason: refundForm.reason || undefined,
      notes: refundForm.notes || undefined,
      mark_as_approved: !!refundForm.mark_as_approved,
    })
    returnRequest.value = res?.data?.return || returnRequest.value
    toast.add({ severity: 'success', summary: 'Refund', detail: res?.message || 'Refund record created.', life: 2500 })
    refundDialogVisible.value = false
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Failed', detail: error?.response?.data?.message || 'Failed to create refund.', life: 3000 })
  } finally {
    refunding.value = false
  }
}

watch(
  () => receiveDialogVisible.value,
  (visible) => {
    if (!visible) return
    receiveForm.received_quantity = Number(returnRequest.value?.requested_quantity ?? 1)
    receiveForm.condition = 'good'
    receiveForm.notes = ''
  },
)

watch(
  () => refundDialogVisible.value,
  (visible) => {
    if (!visible) return
    const unitPrice = Number(returnRequest.value?.order_item?.unit_price || 0)
    const qty = Number(returnRequest.value?.requested_quantity ?? 1)
    refundForm.amount = unitPrice * qty
    refundForm.reason = `Refund for return #${id.value}`
    refundForm.notes = ''
    refundForm.mark_as_approved = true
  },
)

const notesContinueDisabled = computed(() => {
  if (pendingStatus.value === 'approved') return !pendingReturnType.value
  if (pendingStatus.value !== 'rejected') return false
  const hasReason = !!String(pendingRejectReason.value || '').trim().length
  const hasNotes = !!String(pendingReviewNotes.value || '').trim().length
  return !hasReason && !hasNotes
})

const openNotesThenConfirm = (nextStatus: 'rejected') => {
  pendingStatus.value = nextStatus
  pendingReviewNotes.value = returnRequest.value?.review_notes || ''
  pendingRejectReason.value = null
  notesDialogVisible.value = true
}

const openApproval = () => {
  pendingStatus.value = 'approved'
  pendingReturnType.value = returnRequest.value?.return_type || null
  pendingReviewNotes.value = returnRequest.value?.review_notes || ''
  notesDialogVisible.value = true
}

const closeNotesDialog = () => {
  notesDialogVisible.value = false
}

const confirmUpdate = (nextStatus: 'approved' | 'rejected' | 'received' | 'refunded') => {
  if (nextStatus === 'approved' && !pendingReturnType.value) {
    toast.add({ severity: 'warn', summary: 'Required', detail: 'Select Refund or Replacement.', life: 2500 })
    return
  }
  if (nextStatus === 'rejected') {
    const hasReason = !!String(pendingRejectReason.value || '').trim().length
    const hasNotes = !!String(pendingReviewNotes.value || '').trim().length
    if (!hasReason && !hasNotes) {
      toast.add({ severity: 'warn', summary: 'Required', detail: 'Please select a reject reason or add notes.', life: 2500 })
      return
    }
  }

  const labels: Record<string, string> = {
    approved: 'Approve this return?',
    rejected: 'Reject this return?',
    received: 'Mark as received?',
    refunded: 'Mark as refunded?',
  }
  const messages: Record<string, string> = {
    approved: 'This will set the return request to Approved.',
    rejected: 'This will set the return request to Rejected.',
    received: 'This will set the return request to Received.',
    refunded: 'This will set the return request to Refunded.',
  }

  confirm.require({
    header: labels[nextStatus] || 'Confirm',
    message: messages[nextStatus] || 'Continue?',
    icon: 'pi pi-exclamation-triangle',
    rejectProps: { label: 'Cancel', outlined: true, size: 'small' },
    acceptProps: {
      label: 'Confirm',
      size: 'small',
    },
    accept: async () => {
      notesDialogVisible.value = false
      await updateStatus(nextStatus)
    },
  })
}

const updateStatus = async (nextStatus: 'approved' | 'rejected' | 'received' | 'refunded') => {
  if (!id.value) return
  statusUpdating.value = true
  try {
    const rejectReason = String(pendingRejectReason.value || '').trim()
    const rejectNotes = String(pendingReviewNotes.value || '').trim()
    const combinedRejectNotes = rejectReason && rejectNotes ? `${rejectReason}\n\n${rejectNotes}` : (rejectReason || rejectNotes)
    const res = await crmService.updateReturnStatus(id.value, {
      status: nextStatus,
      return_type: nextStatus === 'approved' ? pendingReturnType.value || undefined : undefined,
      review_notes: nextStatus === 'rejected' ? combinedRejectNotes : (pendingReviewNotes.value || undefined),
    })
    returnRequest.value = res?.data || returnRequest.value
    toast.add({
      severity: 'success',
      summary: 'Updated',
      detail: res?.message || 'Return status updated.',
      life: 2500,
    })
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'Failed to update status',
      life: 3000,
    })
  } finally {
    statusUpdating.value = false
  }
}

const activityTimeline = computed(() => {
  const items: any[] = []
  const createdAt = returnRequest.value?.created_at
  if (createdAt) {
    items.push({
      title: 'Return requested',
      subtitle: formatDateTime(createdAt),
      icon: 'pi pi-file-plus',
    })
  }
  const reviewedAt = returnRequest.value?.reviewed_at
  const reviewer = returnRequest.value?.reviewer?.full_name || returnRequest.value?.reviewer?.email
  if (reviewedAt) {
    items.push({
      title: `Reviewed${reviewer ? ` by ${reviewer}` : ''}`,
      subtitle: formatDateTime(reviewedAt),
      icon: 'pi pi-user-edit',
    })
  }
  const updatedAt = returnRequest.value?.updated_at
  if (updatedAt) {
    items.push({
      title: `Last updated (${prettyStatus(returnRequest.value?.status)})`,
      subtitle: formatDateTime(updatedAt),
      icon: 'pi pi-refresh',
    })
  }
  return items
})

const prettyStatus = (value: any) => {
  const normalized = String(value || '').toLowerCase()
  if (normalized === 'pending_verification') return 'Return Pending'
  const v = String(value || '').replace(/_/g, ' ')
  return v ? v.charAt(0).toUpperCase() + v.slice(1) : 'Not recorded'
}

const statusSeverity = (status: any) => {
  const normalized = String(status || '').toLowerCase()
  if (['approved', 'received', 'refunded', 'replaced'].includes(normalized)) return 'success'
  if (normalized === 'rejected') return 'danger'
  return 'warning'
}

const formatDateTime = (value: any) => {
  if (!value) return 'Not recorded'
  const date = new Date(value)
  return new Intl.DateTimeFormat('en-PH', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date)
}

onMounted(loadReturn)
</script>
