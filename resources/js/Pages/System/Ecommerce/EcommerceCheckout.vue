<template>
  <div class="min-h-screen bg-slate-50 px-3 pb-8 sm:px-5">
    <div class="mx-auto max-w-7xl space-y-5">
    <header class="flex items-center justify-between border-b border-slate-200 bg-white py-3 sm:px-4">
      <button type="button" class="flex items-center gap-2" aria-label="FurniSync home" @click="router.push({ name: 'ecommerce.home' })">
        <span class="portal-brand text-sm font-bold text-orange-500 uppercase">FurniSync</span>
      </button>
    </header>
      <div class="lg:hidden">
        <Button :label="`Show summary · ₱ ${formatNumber(totalAmount, 2)}`" icon="pi pi-chevron-down" iconPos="right" size="small" text severity="secondary" fluid @click="mobileSummaryVisible = true" />
      </div>
    <div class="flex flex-col gap-3 border-b border-slate-200 pb-5 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="mt-1 text-2xl font-bold text-gray-900 sm:text-3xl">Review and place your order</h1>
        <p class="mt-1 text-sm text-slate-500">Confirm your fulfillment option, items, and payment method.</p>
      </div>
     
    </div>

    <Card v-if="completedPaymongoOrderId" class="border border-emerald-200 bg-emerald-50/60 shadow-none">
      <template #content>
        <div class="flex flex-col items-center px-4 py-8 text-center">
          <div class="flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-2xl text-emerald-600">
            <i class="pi pi-check" />
          </div>
          <p class="mt-4 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Payment confirmed</p>
          <h2 class="mt-1 text-2xl font-bold text-slate-900">Your order has been paid successfully</h2>
          <p class="mt-2 max-w-lg text-sm text-slate-600">
            Order #{{ formatNumber(completedPaymongoOrderId) }} is confirmed. You can now view its details and delivery progress.
          </p>
          <div class="mt-6 flex w-full flex-col justify-center gap-2 sm:w-auto sm:flex-row">
            <Button label="View Order" icon="pi pi-receipt" @click="goToCompletedOrder" />
            <Button label="Continue Shopping" icon="pi pi-shopping-bag" severity="secondary" outlined @click="goShopping" />
          </div>
        </div>
      </template>
    </Card>

    <div v-else-if="loading" class="grid grid-cols-1 gap-4 lg:grid-cols-3">
      <div class="space-y-4 lg:col-span-2">
        <Card class="border border-slate-200 shadow-none">
          <template #content>
            <Skeleton height="5rem" />
          </template>
        </Card>
        <Card class="border border-slate-200 shadow-none">
          <template #content>
            <div class="space-y-3">
              <Skeleton v-for="idx in 3" :key="`checkout-left-${idx}`" height="5rem" />
            </div>
          </template>
        </Card>
      </div>
      <Card class="border border-slate-200 shadow-none">
        <template #content>
          <div class="space-y-3">
            <Skeleton v-for="idx in 6" :key="`checkout-right-${idx}`" height="1.1rem" />
            <Skeleton height="2.5rem" />
          </div>
        </template>
      </Card>
    </div>

    <div v-else class="grid grid-cols-1 gap-4 lg:grid-cols-3">
      <div class="space-y-4 lg:col-span-2">
      
        <div class="flex items-center justify-between gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-6">
          <button v-for="(step, index) in checkoutSteps" :key="step" type="button" :disabled="index + 1 > checkoutStep"
            class="flex min-w-0 flex-1 items-center gap-2 text-left text-xs font-semibold sm:text-sm"
            :class="checkoutStep === index + 1 ? 'text-orange-600' : index + 1 < checkoutStep ? 'text-slate-900' : 'text-slate-400'"
            @click="checkoutStep = index + 1">
            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border"
              :class="checkoutStep === index + 1 ? 'border-orange-500 bg-orange-500 text-white' : index + 1 < checkoutStep ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200'">
              <i v-if="index + 1 < checkoutStep" class="pi pi-check text-[10px]" /><span v-else>{{ index + 1 }}</span>
            </span>
            <span class="truncate">{{ step }}</span>
          </button>
        </div>
        <Card
          v-if="checkoutStep === 1"
          class="border border-slate-200 shadow-none"
        >
          <template #content>
            <div class="flex items-start justify-between gap-3">
              <div class="flex min-w-0 items-start gap-3">
                <div class="min-w-0">
                  <div class="flex flex-wrap items-center gap-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Your address</p>
                    <Badge v-if="selectedAddress && selectedAddress.id === defaultAddressId" value="Default"  />
                  </div>
                  <p class="mt-1 text-sm font-semibold text-slate-900">{{ selectedAddress?.full_name || 'Select a shipping address' }}</p>
                  <p class="text-sm text-slate-600">{{ selectedAddress?.contact_number || '-' }}</p>
                  <p class="mt-1 text-sm leading-6 text-slate-600">{{ selectedAddressSummary }}</p>
                </div>
              </div>
          <Button :label="selectedAddress ? 'Change' : 'Add address'" icon="pi pi-pencil" size="small" text severity="warn" @click.stop="openAddressDialog" />
            </div>
          </template>
        </Card>

        <Card v-if="checkoutStep === 2" class="border border-slate-200 shadow-none">
          <template #content>
            <h2 class="text-lg font-semibold text-slate-900">How would you like to receive your order?</h2>
            <p class="mt-1 text-xs text-slate-500">Choose how this order gets to you.</p>
            <div class="mt-5 grid gap-3 sm:grid-cols-2">
              <button type="button" class="rounded-xl border p-4 text-left transition" :class="serviceMethod === 'pickup' ? 'border-orange-500 bg-orange-50' : 'border-slate-200 hover:border-orange-300'" @click="serviceMethod = 'pickup'">
                <span class="block text-sm font-semibold text-slate-900">Store pickup <span class="float-right text-emerald-700">Free</span></span>
                <span class="mt-2 block text-xs text-slate-600">Collect from the store after confirmation.</span>
              </button>
              <button type="button" class="rounded-xl border p-4 text-left transition disabled:cursor-not-allowed disabled:opacity-50" :class="serviceMethod === 'delivery' ? 'border-orange-500 bg-orange-50' : 'border-slate-200 hover:border-orange-300'" :disabled="pickupOnly" @click="serviceMethod = 'delivery'">
                <span class="block text-sm font-semibold text-slate-900">Truck delivery <span class="float-right">{{ shippingFeeLoading ? 'Calculating...' : shippingFeeError ? 'Unavailable' : estimatedShippingFee <= 0 ? 'Free' : `₱ ${formatNumber(estimatedShippingFee, 2)}` }}</span></span>
                <span class="mt-2 block text-xs text-slate-600">{{ pickupOnly ? 'Delivery is unavailable for this order.' : 'Delivered to your selected address.' }}</span>
              </button>
            </div>
            <p v-if="serviceMethod === 'delivery' && shippingFeeError" class="mt-3 text-xs text-red-600">{{ shippingFeeError }}</p>
            <div v-if="serviceMethod === 'pickup'" class="mt-4 rounded-xl bg-slate-50 p-4 text-sm text-slate-700">
              <p class="font-semibold">{{ pickupBranch?.name || 'Pickup branch confirmed after ordering' }}</p>
              <p class="mt-1 text-xs">{{ pickupBranchAddress }}</p>
            </div>
            <div class="mt-5 border-t border-slate-200 pt-4">
              <label class="mb-2 block text-xs font-semibold text-slate-700">Preferred {{ serviceMethod }} date *</label>
              <DatePicker v-model="fulfillmentDate" :disabledDates="disabledFulfillmentDates" :minDate="new Date()" :maxDate="maxFulfillmentDate" dateFormat="M d, yy" showIcon  size="small" placeholder="Select an available date" :disabled="availabilityLoading || !fulfillmentDates.length" fluid onlyDate />
              <p v-if="availabilityError" class="mt-2 text-xs text-red-600">{{ availabilityError }}</p>
              <p v-else-if="!availabilityLoading && !fulfillmentDates.length" class="mt-2 text-xs text-amber-700">No dates are available in the next 30 days. Please contact the store.</p>
              <p v-if="fulfillmentWindow" class="mt-2 text-xs text-slate-600">Service window: {{ formatServiceTime(fulfillmentWindow.start) }} to {{ formatServiceTime(fulfillmentWindow.end) }}. The store will confirm the exact time.</p>
            </div>
          </template>
        </Card>
        <Card v-if="checkoutStep === 3" class="border border-slate-200 shadow-none">
          <template #content>
            <h2 class="text-lg font-semibold text-slate-900">How would you like to pay?</h2>
            <div class="mt-5 grid gap-3">
              <button v-for="method in availablePaymentMethods" :key="method.value" type="button" class="flex items-center gap-4 rounded-xl border p-4 text-left transition disabled:opacity-50"
                :class="selectedPaymentMethod === method.value ? 'border-orange-500 bg-orange-50' : 'border-slate-200 hover:border-orange-300'"
                @click="selectPaymentMethod(method.value)">
                <i :class="[method.icon, 'text-lg text-slate-800']" />
                <span><span class="block text-sm font-semibold text-slate-900">{{ paymentMethodLabel(method.value) }}</span><span class="block text-xs text-slate-500">{{ paymentMethodDescription(method.value) }}</span></span>
                <i v-if="selectedPaymentMethod === method.value" class="pi pi-check-circle ml-auto text-orange-600" />
              </button>
              <p v-if="paymentMethodsError" class="text-xs text-red-600">{{ paymentMethodsError }}</p>
            </div>
          </template>
        </Card>
        <Card v-if="checkoutStep === 3" class="border border-slate-200 shadow-none">
          <template #content>
            <h2 class="text-base font-semibold text-slate-900">Billing address</h2>
            <p class="mt-1 text-xs text-slate-500">Used for your payment receipt, not for delivery.</p>
            <label class="mt-4 flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 p-3 text-sm text-slate-700">
              <input v-model="useDifferentBillingAddress" type="checkbox" class="h-4 w-4 accent-orange-500" />
              Use a different billing address
            </label>
            <p v-if="!useDifferentBillingAddress" class="mt-3 text-xs text-slate-500">Same as saved address: {{ selectedAddressSummary }}</p>
            <div v-if="useDifferentBillingAddress" class="mt-4 grid gap-3 sm:grid-cols-2">
              <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-medium text-slate-600">Billing name *</label>
                <InputText v-model.trim="billingForm.name" fluid size="small" placeholder="Full name or business name" />
              </div>
              <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-medium text-slate-600">Street, building, and unit *</label>
                <InputText v-model.trim="billingForm.street" fluid size="small" placeholder="House number and street" />
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">Barangay</label>
                <InputText v-model.trim="billingForm.barangay" fluid size="small" placeholder="Barangay" />
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">City *</label>
                <InputText v-model.trim="billingForm.city" fluid size="small" placeholder="City or municipality" />
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">Province *</label>
                <InputText v-model.trim="billingForm.province" fluid size="small" placeholder="Province" />
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">Postal code</label>
                <InputText v-model.trim="billingForm.postalCode" fluid size="small" placeholder="Postal code" />
              </div>
              <p v-if="billingAddressIncomplete" class="text-xs text-amber-700 sm:col-span-2">Complete the required billing fields before placing your order.</p>
            </div>
          </template>
        </Card>

        <div v-if="checkoutStep < 3" class="flex justify-between gap-3">
          <Button v-if="checkoutStep > 1" label="Back" icon="pi pi-arrow-left" text severity="secondary" @click="checkoutStep--" />
          <span v-else />
          <Button label="Continue" icon="pi pi-arrow-right" iconPos="right" severity="warn" :disabled="(checkoutStep === 1 && !selectedAddress) || (checkoutStep === 2 && (!fulfillmentDates.includes(fulfillmentDateString) || availabilityLoading || (serviceMethod === 'delivery' && !!shippingFeeError)))" @click="checkoutStep++" />
        </div>
        <div v-else class="space-y-3">
          <div class="grid grid-cols-1 gap-2 lg:hidden">
            <Button label="Back" icon="pi pi-arrow-left" text severity="secondary" fluid @click="checkoutStep--" />
            <Button :label="retryOrderId ? 'Continue Payment' : 'Place Order'" severity="warn" fluid :disabled="!retryOrderId && (!selectedAddress || !fulfillmentDates.includes(fulfillmentDateString) || !!paymentMethodsError || paymentMethodsLoading)" :loading="placing || paymongoCreating" @click="placeOrder" />
          </div>
          <div class="hidden lg:block">
            <Button label="Back" icon="pi pi-arrow-left" text severity="secondary" @click="checkoutStep--" />
          </div>
        </div>
      </div>

      <aside class="hidden lg:sticky lg:top-4 lg:block">
      <Card class="h-fit border border-slate-200 shadow-none">
        <template #content>
          <div class="space-y-3 text-sm">
            <div>
              <h2 class="text-base font-semibold text-slate-900">Order summary</h2>
              <p class="text-xs text-slate-500">Final charges before placing your order</p>
            </div>
            <div v-if="checkoutStep === 3">
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Payment Method</p>
              <div class="mt-2 flex w-full items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 text-left">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white text-amber-600 shadow-sm">
                  <i :class="selectedPaymentMethodOption.icon" />
                </span>
                <span class="min-w-0 flex-1">
                  <span class="block text-sm font-semibold text-slate-900">{{ selectedPaymentMethodOption.label }}</span>
                  <span class="block text-xs text-slate-500">{{ selectedPaymentMethodOption.description }}</span>
                </span>
              </div>
            </div>

            <!-- <div class="rounded-lg border border-slate-200 p-3">
              <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Voucher</label>
              <div class="flex flex-col gap-2 sm:flex-row">
                <InputText v-model="voucherCode" fluid placeholder="Enter voucher code" />
                <Button label="Apply" size="small" severity="warn" class="w-full sm:w-auto" :loading="applyingVoucher" @click="applyVoucher" />
              </div>
              <p v-if="appliedVoucher" class="mt-1 text-xs text-emerald-600">
                Applied: {{ appliedVoucher.code }} ({{ voucherLabel }})
              </p>
            </div> -->

            <Divider />
            <div class="flex justify-between"><span>Items</span><span>{{ formatNumber(itemsCount) }}</span></div>
            <div class="flex justify-between"><span>Subtotal</span><span>₱ {{ formatNumber(subtotal, 2) }}</span></div>
            <div class="flex justify-between text-slate-600"><span>VATable Sales</span><span>₱ {{ formatNumber(vatableSales, 2) }}</span></div>
            <div class="flex justify-between text-slate-600"><span> (12%)</span><span>₱ {{ formatNumber(vatAmount, 2) }}</span></div>
            <div class="flex justify-between"><span>{{ serviceMethod === 'pickup' ? 'Pickup' : 'Delivery' }}</span><span>{{ shippingFeeTotal <= 0 ? 'Free' : `₱ ${formatNumber(shippingFeeTotal, 2)}` }}</span></div>
            <div v-if="fulfillmentDateString" class="flex justify-between text-xs text-slate-600"><span>Preferred date</span><span>{{ fulfillmentDate?.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) }}</span></div>
            <div class="border-t border-slate-200 pt-3">
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ serviceMethod === 'pickup' ? 'Pickup details' : 'Delivery details' }}</p>
              <p class="mt-1 text-xs font-semibold text-slate-900">{{ serviceMethod === 'pickup' ? (pickupBranch?.name || 'Store branch') : (selectedAddress?.full_name || 'Choose an address') }}</p>
              <p class="mt-1 text-xs leading-5 text-slate-600">{{ serviceMethod === 'pickup' ? pickupBranchAddress : selectedAddressSummary }}</p>
              <p v-if="fulfillmentWindow" class="mt-1 text-xs text-slate-500">{{ formatServiceTime(fulfillmentWindow.start) }} to {{ formatServiceTime(fulfillmentWindow.end) }}</p>
            </div>
            <div class="border-t border-slate-200 pt-3">
              <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Products</p>
              <div v-for="item in checkoutItems" :key="`summary-${item.id}`" class="flex gap-2 py-2 text-xs">
                <img :src="normalizeImageUrl(item.image) || '/F.svg'" :alt="item.product_name" class="h-11 w-11 rounded-lg border border-slate-200 object-cover" @error="onImageError" />
                <span class="min-w-0 flex-1"><span class="block truncate font-semibold text-slate-900">{{ item.product_name }}</span><span class="text-slate-500">Qty {{ item.quantity }}</span></span>
                <span class="font-semibold text-slate-900">₱ {{ formatNumber(item.line_subtotal, 2) }}</span>
              </div>
            </div>
            <!-- <div v-if="bulkTripAllowed" class="flex items-center justify-between text-xs text-slate-600">
              <span>Bulk trip discount</span>
              <InputSwitch v-model="bulkTripEnabled" />
            </div> -->
            <div v-if="shippingDistanceKm !== null" class="flex justify-between text-xs text-slate-500">
              <span>Distance to Store</span>
              <span>{{ formatNumber(shippingDistanceKm, 2) }} km</span>
            </div>
            <!-- <div v-if="shippingFeeNotice" class="rounded-md border border-amber-200 bg-amber-50 px-2 py-1 text-xs text-amber-700">
              {{ shippingFeeNotice }}
            </div> -->
            <div v-if="shippingFeeBreakdown" class="rounded-md border border-slate-200 bg-slate-50 px-2 py-2 text-xs text-slate-700">
              <div class="flex items-center justify-between">
                <span class="font-semibold">Shipping breakdown</span>
                <Button
                  :label="showBreakdown ? 'Hide' : 'View'"
                  size="small"
                  text
                  severity="secondary"
                  @click="showBreakdown = !showBreakdown"
                />
              </div>
              <div v-if="showBreakdown" class="mt-2 space-y-1">
                <div class="flex justify-between"><span>Base fee</span><span>₱ {{ formatNumber(shippingFeeBreakdown.base_fee, 2) }}</span></div>
                <div class="flex justify-between"><span>Distance fee</span><span>₱ {{ formatNumber(shippingFeeBreakdown.distance_fee, 2) }}</span></div>
                <div class="flex justify-between"><span>Weight fee</span><span>₱ {{ formatNumber(shippingFeeBreakdown.weight_fee, 2) }}</span></div>
                <div class="flex justify-between"><span>Distance</span><span>{{ formatNumber(shippingFeeBreakdown.distance_km, 2) }} km</span></div>
                <div class="flex justify-between"><span>Weight</span><span>{{ formatNumber(shippingFeeBreakdown.weight_kg, 2) }} kg</span></div>
                <div v-if="shippingFeeBreakdown.bulk_trip" class="flex justify-between text-emerald-600">
                  <span>Bulk trip discount</span>
                  <span>- ₱ {{ formatNumber(shippingFeeBreakdown.bulk_discount_amount, 2) }}</span>
                </div>
              </div>
            </div>
            <!-- <div class="flex justify-between" v-if="discountAmount > 0">
              <span>Voucher Discount</span>
              <span class="text-emerald-600">- ₱ {{ discountAmount.toFixed(2) }}</span>
            </div> -->
            <Divider />
            <div class="flex items-end justify-between">
              <span class="font-semibold text-slate-900">Total</span>
              <span class="text-xl font-bold text-slate-900">₱ {{ formatNumber(totalAmount, 2) }}</span>
            </div>
          <Button v-if="checkoutStep === 3" :label="retryOrderId ? 'Continue Payment' : 'Place Order'" class="mt-2" fluid :disabled="!retryOrderId && (!selectedAddress || !fulfillmentDates.includes(fulfillmentDateString) || !!paymentMethodsError || paymentMethodsLoading)" :loading="placing || paymongoCreating" @click="placeOrder" />
            <p class="text-center text-xs text-slate-500">Your payment information is securely processed.</p>
          </div>
        </template>
      </Card>
      </aside>
    </div>

    <Drawer v-model:visible="mobileSummaryVisible" position="top" header="Order summary" class="!h-auto max-h-[85vh] lg:hidden">
      <div class="max-h-[65vh] space-y-4 overflow-y-auto text-sm">
        <div v-for="item in checkoutItems" :key="`mobile-summary-${item.id}`" class="flex gap-3 border-b border-slate-100 pb-3">
          <img :src="normalizeImageUrl(item.image) || '/F.svg'" :alt="item.product_name" class="h-14 w-14 rounded-lg object-cover" @error="onImageError" />
          <div class="min-w-0 flex-1"><p class="truncate font-semibold">{{ item.product_name }}</p><p class="text-xs text-slate-500">Qty {{ item.quantity }}</p></div>
          <span class="font-semibold">₱ {{ formatNumber(item.line_subtotal, 2) }}</span>
        </div>
        <div class="flex justify-between"><span>Subtotal</span><span>₱ {{ formatNumber(subtotal, 2) }}</span></div>
        <div class="flex justify-between"><span>{{ serviceMethod === 'pickup' ? 'Pickup' : 'Delivery' }}</span><span>{{ shippingFeeTotal <= 0 ? 'Free' : `₱ ${formatNumber(shippingFeeTotal, 2)}` }}</span></div>
        <div v-if="fulfillmentDateString" class="flex justify-between"><span>Preferred date</span><span>{{ fulfillmentDate?.toLocaleDateString('en-PH', { dateStyle: 'medium' }) }}</span></div>
        <div class="flex justify-between border-t border-slate-200 pt-3 text-base font-bold"><span>Total</span><span>₱ {{ formatNumber(totalAmount, 2) }}</span></div>
      </div>
    </Drawer>

    <Dialog
      v-model:visible="addressDialogVisible"
      modal
      :header="isEditingAddress ? 'Edit Shipping Address' : (showAddAddressForm ? 'Add Shipping Address' : 'Choose Shipping Address')"
      class="w-full max-w-3xl"
      :draggable="false"
      @hide="closeAddressDialog"
    >
      <div v-if="!showAddAddressForm" class="space-y-4">
        <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 p-3">
          <div>
            <p class="text-sm font-semibold text-slate-900">Saved addresses</p>
            <p class="text-xs text-slate-500">The first saved address is used as your default.</p>
          </div>
          <Button label="Add Address" icon="pi pi-plus" size="small" severity="warn" @click="showAddAddressForm = true" />
        </div>

        <div v-if="addressTemplates.length" class="grid max-h-[52vh] gap-3 overflow-y-auto pr-1 sm:grid-cols-2">
          <div
            v-for="(address, index) in addressTemplates"
            :key="address.id"
            class="relative rounded-xl border p-4 text-left transition"
            :class="selectedAddressId === address.id
              ? 'border-amber-500 bg-amber-50/60 ring-1 ring-amber-500'
              : 'border-slate-200 bg-white hover:border-amber-300'"
            role="button"
            tabindex="0"
            @click="selectedAddressId = address.id"
            @keydown.enter="selectedAddressId = address.id"
          >
            <div class="flex items-start gap-3">
              <RadioButton v-model="selectedAddressId" :inputId="`address-${address.id}`" :value="address.id" />
              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                  <p class="font-semibold text-slate-900">{{ address.full_name }}</p>
                  <Badge v-if="index === 0" value="Default" severity="success" />
                </div>
                <p class="mt-1 text-sm text-slate-600">{{ address.contact_number }}</p>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                  {{ address.address_line }}, {{ address.barangay }}, {{ address.city }}, {{ address.province }}
                </p>
              </div>
            </div>
            <div class="mt-3 flex justify-end border-t border-slate-200 pt-2">
              <Button label="Edit" icon="pi pi-pencil" size="small" text severity="warn" @click.stop="startEditAddress(address)" />
            </div>
          </div>
        </div>

        <div v-else class="rounded-xl border border-dashed border-slate-300 px-4 py-10 text-center">
          <i class="pi pi-map-marker text-3xl text-slate-300" />
          <p class="mt-3 text-sm font-semibold text-slate-700">No saved address yet</p>
          <p class="mt-1 text-xs text-slate-500">Add an address to calculate delivery and continue checkout.</p>
        </div>
      </div>

      <div v-else class="space-y-5">
        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-600">Full name</label>
            <InputText v-model="newAddress.full_name" fluid placeholder="Recipient's full name" :invalid="addressTouched.name && !validRecipientName(newAddress.full_name)" @blur="addressTouched.name = true" />
            <p v-if="addressTouched.name && !validRecipientName(newAddress.full_name)" class="mt-1 text-xs text-red-600">Enter a valid full name with at least two letters.</p>
          </div>
          <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-600">Contact number</label>
            <InputMask mask="+63 999-999-9999" v-model="newAddress.contact_number" fluid placeholder="+63 999-999-9999" :invalid="addressTouched.phone && !validPhilippineMobile(newAddress.contact_number)" @blur="addressTouched.phone = true" />
            <p v-if="addressTouched.phone && !validPhilippineMobile(newAddress.contact_number)" class="mt-1 text-xs text-red-600">Enter a complete Philippine mobile number.</p>
          </div>
          <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-600">Province</label>
            <Select v-model="newAddressSelection.provinceId" :options="provinceOptions" optionLabel="label" optionValue="value"
              fluid placeholder="Select Province" @change="onProvinceChange" />
          </div>
          <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-600">City</label>
            <Select v-model="newAddressSelection.cityId" :options="cityOptions" optionLabel="label" optionValue="value"
              fluid placeholder="Select City" :disabled="!newAddressSelection.provinceId" @change="onCityChange" />
          </div>
          <div class="sm:col-span-2">
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-600">Barangay</label>
            <Select v-model="newAddressSelection.barangayCode" :options="barangayOptions" optionLabel="label" optionValue="value"
              fluid placeholder="Select Barangay" :disabled="!newAddressSelection.cityId" />
          </div>
          <div class="sm:col-span-2">
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-600">Street, building, and unit</label>
            <Textarea v-model="newAddress.address_line" rows="3" fluid placeholder="House number, street, subdivision, building, or unit" />
          </div>
        </div>

        <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
          <div>
            <p class="text-sm font-medium text-slate-800">Pin delivery location</p>
            <p class="text-xs text-slate-500">Add map coordinates for a more accurate delivery fee.</p>
          </div>
          <Button label="Open Map" icon="pi pi-map-marker" severity="secondary" outlined size="small" @click="openCoordsMapDialog" />
        </div>
      </div>

      <template #footer>
        <div class="flex w-full flex-col-reverse gap-2 sm:flex-row sm:justify-end">
          <Button
            v-if="showAddAddressForm"
            label="Back"
            icon="pi pi-arrow-left"
            severity="secondary"
            text
            @click="cancelAddressForm"
          />
          <Button
            v-if="showAddAddressForm"
            :label="isEditingAddress ? 'Update Address' : 'Save Address'"
            icon="pi pi-check"
            severity="warn"
            @click="saveNewAddress"
          />
          <Button
            v-else
            label="Use Selected Address"
            icon="pi pi-check"
            severity="warn"
            :disabled="!selectedAddressId"
            @click="addressDialogVisible = false"
          />
        </div>
      </template>
    </Dialog>

    <Dialog v-model:visible="coordsMap.visible" modal header="Location" class="w-full max-w-4xl" :draggable="false" @show="initCoordsMap" @hide="destroyCoordsMap">
   
        <div class="space-y-4">
          <div class="space-y-2">
            <label class="text-xs font-semibold text-slate-600">Search Location</label>
            <div class="flex gap-2">
              <InputText
                v-model="coordsMap.searchQuery"
                placeholder="Type address or place (e.g., Dasmariñas City, Cavite)"
                class="flex-1"
                @keyup.enter="searchCoordsLocation"
              />
              <Button label="Search" icon="pi pi-search" severity="warn" @click="searchCoordsLocation" :loading="coordsMap.searching" />
            </div>
            <small class="text-xs text-slate-500">Press Enter to search. Click or drag the pin to refine.</small>
          </div>
        </div>

        <div class="h-80 overflow-hidden rounded-xl border border-slate-200">
          <div id="checkout-coords-map" class="h-full w-full"></div>
        </div>

      <template #footer>
        <Button label="Save" fluid severity="warn" @click="saveCoordsFromMap" />
      </template>
    </Dialog>

    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import RadioButton from 'primevue/radiobutton'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import Dialog from 'primevue/dialog'
import Drawer from 'primevue/drawer'
import InputSwitch from 'primevue/inputswitch'
import Badge from 'primevue/badge'
import ecommerceService from '@/services/ecommerce.service'
import paymongoService from '@/services/paymongo.service'
import { useAuthStore } from '@/stores/auth'
import InputMask from 'primevue/inputmask'
import { showAlert } from '@/utils/swal'
import mapboxgl from 'mapbox-gl'
import 'mapbox-gl/dist/mapbox-gl.css'
import { forwardGeocodeMapbox, requireMapboxToken } from '@/utils/mapbox'
defineOptions({
  layout: null,
})


type AddressTemplate = {
  id: number
  full_name: string
  contact_number: string
  province: string
  city: string
  barangay: string
  address_line: string
  latitude?: number | null
  longitude?: number | null
  is_default?: boolean
}

type AppliedVoucher = {
  code: string
  discount_type: 'fixed' | 'percent'
  discount_value: number
}

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const placing = ref(false)
const paymongoCreating = ref(false)
const applyingVoucher = ref(false)
const loading = ref(false)
const checkingPaymongoResult = ref(false)
const completedPaymongoOrderId = ref<number | null>(null)
const checkoutItems = ref<any[]>([])
const checkoutSteps = ['Address', 'Service', 'Payment']
const checkoutStep = ref(1)
const mobileSummaryVisible = ref(false)
const serviceMethod = ref<'pickup' | 'delivery'>('delivery')
const fulfillmentDates = ref<string[]>([])
const fulfillmentWindow = ref<{ start: string; end: string } | null>(null)
const fulfillmentDate = ref<Date | null>(null)
const availabilityLoading = ref(false)
const availabilityError = ref('')
let availabilityRequest = 0
const fulfillmentDateString = computed(() => fulfillmentDate.value
  ? `${fulfillmentDate.value.getFullYear()}-${String(fulfillmentDate.value.getMonth() + 1).padStart(2, '0')}-${String(fulfillmentDate.value.getDate()).padStart(2, '0')}`
  : '')
const maxFulfillmentDate = computed(() => {
  const last = fulfillmentDates.value.at(-1)
  if (!last) return new Date()
  const [year, month, day] = last.split('-').map(Number)
  return new Date(year, month - 1, day)
})
const disabledFulfillmentDates = computed(() => {
  const allowed = new Set(fulfillmentDates.value)
  const days: Date[] = []
  const start = new Date()
  for (let offset = 0; offset <= 45; offset++) {
    const date = new Date(start.getFullYear(), start.getMonth(), start.getDate() + offset)
    const key = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
    if (!allowed.has(key)) days.push(date)
  }
  return days
})

async function loadFulfillmentAvailability() {
  const requestId = ++availabilityRequest
  const storeId = Number(checkoutItems.value[0]?.store_id || 0)
  fulfillmentDate.value = null
  fulfillmentDates.value = []
  fulfillmentWindow.value = null
  if (!storeId) return
  availabilityLoading.value = true
  availabilityError.value = ''
  try {
    const response = await ecommerceService.getCheckoutAvailability(storeId, serviceMethod.value, checkoutItemIds.value)
    if (requestId !== availabilityRequest) return
    fulfillmentDates.value = response.data?.data?.dates || []
    fulfillmentWindow.value = response.data?.data?.window || null
    if (serviceMethod.value === 'pickup') pickupBranch.value = response.data?.data?.pickup_branch || null
  } catch (error: any) {
    if (requestId !== availabilityRequest) return
    availabilityError.value = error?.response?.data?.message || 'Available dates could not be loaded. Please try again.'
  } finally {
    if (requestId === availabilityRequest) availabilityLoading.value = false
  }
}

watch([serviceMethod, () => checkoutItems.value[0]?.store_id], loadFulfillmentAvailability)
const customizationRequests = reactive<Record<number, string>>({})
const customizationEnabled = reactive<Record<number, boolean>>({})
const selectedItemIds = ref<number[]>([])
const addressDialogVisible = ref(false)
const showAddAddressForm = ref(false)
const isEditingAddress = ref(false)
const editingAddressId = ref<number | null>(null)
const customerLatitude = ref<number | null>(null)
const customerLongitude = ref<number | null>(null)
const pickupOnly = ref(false)
const pickupBranch = ref<any | null>(null)
const pickupBranchAddress = computed(() => {
  if (!pickupBranch.value) return 'Pickup address is not available yet.'
  const branch = pickupBranch.value
  return [branch.address, branch.barangay, branch.city, branch.province].filter(Boolean).join(', ') || 'Contact the store for pickup directions.'
})
const useDifferentBillingAddress = ref(false)
const billingForm = reactive({ name: '', street: '', barangay: '', city: '', province: '', postalCode: '' })
const billingAddressIncomplete = computed(() => useDifferentBillingAddress.value &&
  ![billingForm.name, billingForm.street, billingForm.city, billingForm.province].every((value) => value.trim().length > 0))

const PAYMONGO_PENDING_ORDER_STORAGE_KEY = 'paymongo_pending_order_id'
const retryOrderId = ref<number | null>(null)
const DEFAULT_DASM_LAT = 14.3294
const DEFAULT_DASM_LNG = 120.9367

const coordsMap = reactive({
  visible: false,
  searching: false,
  searchQuery: 'Dasmariñas City, Cavite',
  latitude: null as number | null,
  longitude: null as number | null,
})

let coordsMapInstance: mapboxgl.Map | null = null
let coordsMarker: mapboxgl.Marker | null = null
let coordsMapReady = false

const initCoordsMap = () => {
  const container = document.getElementById('checkout-coords-map')
  if (!container) return

  const lat = Number(coordsMap.latitude ?? newAddress.latitude ?? DEFAULT_DASM_LAT) || DEFAULT_DASM_LAT
  const lng = Number(coordsMap.longitude ?? newAddress.longitude ?? DEFAULT_DASM_LNG) || DEFAULT_DASM_LNG
  coordsMap.latitude = Number(lat.toFixed(6))
  coordsMap.longitude = Number(lng.toFixed(6))

  if (!coordsMapReady) {
    try {
      coordsMapInstance = new mapboxgl.Map({
        container,
        style: 'mapbox://styles/mapbox/streets-v12',
        accessToken: requireMapboxToken(),
        center: [lng, lat],
        zoom: 14,
      })
    } catch (error) {
      console.warn('Mapbox location picker unavailable', error)
      return
    }
    coordsMapInstance.on('click', (e) => {
      coordsMap.latitude = Number(e.lngLat.lat.toFixed(6))
      coordsMap.longitude = Number(e.lngLat.lng.toFixed(6))
      redrawCoordsMarker()
    })

    coordsMapReady = true
  }

  redrawCoordsMarker()
  setTimeout(() => coordsMapInstance?.resize(), 150)
}

const redrawCoordsMarker = () => {
  if (!coordsMapInstance) return
  const lat = Number(coordsMap.latitude ?? DEFAULT_DASM_LAT) || DEFAULT_DASM_LAT
  const lng = Number(coordsMap.longitude ?? DEFAULT_DASM_LNG) || DEFAULT_DASM_LNG

  if (coordsMarker) coordsMarker.remove()
  coordsMarker = new mapboxgl.Marker({ draggable: true }).setLngLat([lng, lat]).addTo(coordsMapInstance)
  coordsMarker.on('dragend', () => {
    const pos = coordsMarker!.getLngLat()
    coordsMap.latitude = Number(pos.lat.toFixed(6))
    coordsMap.longitude = Number(pos.lng.toFixed(6))
  })

  coordsMapInstance.flyTo({ center: [lng, lat], zoom: 14 })
}

const openCoordsMapDialog = () => {
  coordsMap.latitude = newAddress.latitude ? Number(newAddress.latitude) : DEFAULT_DASM_LAT
  coordsMap.longitude = newAddress.longitude ? Number(newAddress.longitude) : DEFAULT_DASM_LNG
  coordsMap.visible = true
}

async function searchCoordsLocation() {
  if (!coordsMap.searchQuery.trim()) return
  coordsMap.searching = true
  try {
    const result = await forwardGeocodeMapbox(coordsMap.searchQuery.trim())
    if (result) {
      coordsMap.latitude = Number(result.latitude.toFixed(6))
      coordsMap.longitude = Number(result.longitude.toFixed(6))
      redrawCoordsMarker()
    }
  } catch (e) {
    console.warn('Search failed', e)
  } finally {
    coordsMap.searching = false
  }
}

const saveCoordsFromMap = () => {
  newAddress.latitude = coordsMap.latitude
  newAddress.longitude = coordsMap.longitude
  customerLatitude.value = coordsMap.latitude
  customerLongitude.value = coordsMap.longitude
  estimateShippingFee()
  coordsMap.visible = false
}

const destroyCoordsMap = () => {
  if (coordsMapInstance) {
    coordsMapInstance.remove()
    coordsMapInstance = null
  }
  coordsMarker = null
  coordsMapReady = false
}

onBeforeUnmount(() => {
  destroyCoordsMap()
})

const estimatedShippingFee = ref(0)
const shippingFeeTotal = computed(() => serviceMethod.value === 'pickup' ? 0 : estimatedShippingFee.value)
const shippingFeeLoading = ref(false)
const shippingFeeNotice = ref<string | null>(null)
const shippingFeeError = ref<string | null>(null)
const shippingDistanceKm = ref<number | null>(null)
const shippingFeeBreakdown = ref<any | null>(null)
const showBreakdown = ref(false)
const bulkTripEnabled = ref(false)
const bulkTripAllowed = ref(false)
const addressTemplates = ref<AddressTemplate[]>([])
const selectedAddressId = ref<number | null>(null)
const defaultAddressId = computed(() => addressTemplates.value[0]?.id || null)
const newAddress = reactive<AddressTemplate>({
  id: 0,
  full_name: '',
  contact_number: '',
  province: '',
  city: '',
  barangay: '',
  address_line: '',
  latitude: null,
  longitude: null,
})
const addressTouched = reactive({ name: false, phone: false })
const validRecipientName = (name: string) => /^[\p{L}][\p{L}\s.'-]*[\p{L}]$/u.test(String(name || '').trim()) && String(name || '').trim().length <= 120
const validPhilippineMobile = (phone: string) => /^(?:639\d{9}|09\d{9})$/.test(String(phone || '').replace(/\D/g, ''))
const newAddressSelection = reactive({
  provinceId: '',
  cityId: '',
  barangayCode: '',
})

const resetAddressForm = () => {
  addressTouched.name = false
  addressTouched.phone = false
  newAddress.id = 0
  newAddress.full_name = ''
  newAddress.contact_number = ''
  newAddress.province = ''
  newAddress.city = ''
  newAddress.barangay = ''
  newAddress.address_line = ''
  newAddress.latitude = null
  newAddress.longitude = null
  newAddressSelection.provinceId = ''
  newAddressSelection.cityId = ''
  newAddressSelection.barangayCode = ''
  cities.value = []
  barangays.value = []
  isEditingAddress.value = false
  editingAddressId.value = null
}

const openAddressDialog = () => {
  addressDialogVisible.value = true
}

const cancelAddressForm = () => {
  showAddAddressForm.value = false
  resetAddressForm()
}

const closeAddressDialog = () => {
  cancelAddressForm()
}

const provinces = ref<any[]>([])
const cities = ref<any[]>([])
const barangays = ref<any[]>([])
const citiesCache = ref<Record<string, any[]>>({})

const selectedPaymentMethod = ref<'gcash' | 'card' | 'paymaya'>('gcash')
const allPaymentMethods = [
  { label: 'GCash', value: 'gcash' as const, icon: 'pi pi-wallet', description: 'Pay securely with your GCash account.' },
  { label: 'Credit or Debit Card', value: 'card' as const, icon: 'pi pi-credit-card', description: 'Enter your card details on the secure payment page.' },
  { label: 'Maya', value: 'paymaya' as const, icon: 'pi pi-wallet', description: 'Pay securely with your Maya account.' },
]
const allowedPaymentMethods = ref<string[]>([])
const paymentMethodsLoading = ref(false)
const paymentMethodsError = ref('')
const availablePaymentMethods = computed(() => allPaymentMethods.filter((method) => allowedPaymentMethods.value.includes(method.value)))

async function loadAvailablePaymentMethods() {
  const storeId = Number(checkoutItems.value[0]?.store_id || 0)
  if (!storeId) return

  paymentMethodsLoading.value = true
  paymentMethodsError.value = ''
  try {
    const response = await paymongoService.getAvailableCheckoutMethods(storeId)
    allowedPaymentMethods.value = Array.isArray(response?.data?.methods) ? response.data.methods : []
    if (!availablePaymentMethods.value.length) {
      paymentMethodsError.value = 'No online payment methods are available for this store right now.'
    } else if (!allowedPaymentMethods.value.includes(selectedPaymentMethod.value)) {
      selectedPaymentMethod.value = availablePaymentMethods.value[0].value
    }
  } catch (error: any) {
    allowedPaymentMethods.value = []
    paymentMethodsError.value = error?.response?.data?.message || 'Unable to load payment methods. Please try again later.'
  } finally {
    paymentMethodsLoading.value = false
  }
}

const voucherCode = ref('')
const appliedVoucher = ref<AppliedVoucher | null>(null)
const validatedDiscountAmount = ref(0)

const selectedAddress = computed(() => addressTemplates.value.find((a) => a.id === selectedAddressId.value) || null)
const selectedAddressSummary = computed(() =>
  selectedAddress.value
    ? `${selectedAddress.value.province}, ${selectedAddress.value.city}, ${selectedAddress.value.barangay}, ${selectedAddress.value.address_line}`
    : 'No shipping address selected.',
)
const voucherLabel = computed(() => {
  if (!appliedVoucher.value) return ''
  return appliedVoucher.value.discount_type === 'percent'
    ? `${appliedVoucher.value.discount_value}%`
    : `₱ ${formatNumber(appliedVoucher.value.discount_value, 2)}`
})
const provinceOptions = computed(() => provinces.value.map((p: any) => ({ label: p.name, value: p.province_id })))
const cityOptions = computed(() => cities.value.map((c: any) => ({ label: c.name, value: c.city_id })))
const barangayOptions = computed(() => barangays.value.map((b: any) => ({ label: b.name, value: b.code })))

const itemsCount = computed(() => checkoutItems.value.reduce((sum, item) => sum + Number(item.quantity || 0), 0))
const subtotal = computed(() => checkoutItems.value.reduce((sum, item) => sum + Number(item.line_subtotal || 0), 0))
const shippingFeePerItem = computed(() => {
  if (!checkoutItems.value.length) return 0
  return shippingFeeTotal.value / checkoutItems.value.length
})
const discountAmount = computed(() => validatedDiscountAmount.value)
const VAT_RATE = 12
const vatInclusiveProductTotal = computed(() => Math.max(0, subtotal.value - discountAmount.value))
const vatAmount = computed(() => vatInclusiveProductTotal.value > 0
  ? vatInclusiveProductTotal.value - (vatInclusiveProductTotal.value / (1 + (VAT_RATE / 100)))
  : 0)
const vatableSales = computed(() => vatInclusiveProductTotal.value - vatAmount.value)
const totalAmount = computed(() => Math.max(0, subtotal.value + shippingFeeTotal.value - discountAmount.value))
const checkoutItemIds = computed(() =>
  checkoutItems.value
    .map((item: any) => Number(item?.id))
    .filter((id: number) => Number.isInteger(id) && id > 0),
)

function paymentMethodLabel(method: 'gcash' | 'card' | 'paymaya') {
  return allPaymentMethods.find((m) => m.value === method)?.label || 'Online payment'
}

function paymentMethodDescription(method: 'gcash' | 'card' | 'paymaya') {
  return allPaymentMethods.find((m) => m.value === method)?.description || ''
}

const selectedPaymentMethodOption = computed(() =>
  ({
    ...allPaymentMethods.find((method) => method.value === selectedPaymentMethod.value) || allPaymentMethods[0],
    label: paymentMethodLabel(selectedPaymentMethod.value),
    description: paymentMethodDescription(selectedPaymentMethod.value),
  }),
)

function selectPaymentMethod(method: 'gcash' | 'card' | 'paymaya') {
  selectedPaymentMethod.value = method
}

function formatNumber(value: unknown, decimals = 0): string {
  const number = Number(value || 0)
  return Number.isFinite(number)
    ? number.toLocaleString('en-PH', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
      })
    : (0).toLocaleString('en-PH', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
      })
}

function formatServiceTime(value: string): string {
  const [hours, minutes] = value.split(':').map(Number)
  return new Date(2020, 0, 1, hours, minutes).toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit', hour12: true })
}


watch(selectedAddressId, () => {
  const selected = selectedAddress.value
  if (selected?.latitude != null && selected?.longitude != null) {
    customerLatitude.value = Number(selected.latitude)
    customerLongitude.value = Number(selected.longitude)
  }
})

watch(
  [selectedAddressId, () => checkoutItems.value.length, customerLatitude, customerLongitude],
  () => {
    estimateShippingFee()
  },
)

watch(bulkTripEnabled, () => {
  estimateShippingFee()
})

async function loadAddressTemplates() {
  try {
    const response = await ecommerceService.getAddressTemplates()
    const addresses = Array.isArray(response.data?.data) ? response.data.data : []
    addressTemplates.value = addresses
      .map((address: AddressTemplate, index: number) => ({ address, index }))
      .sort((a: { address: AddressTemplate; index: number }, b: { address: AddressTemplate; index: number }) =>
        Number(Boolean(b.address.is_default)) - Number(Boolean(a.address.is_default)) || a.index - b.index,
      )
      .map(({ address }: { address: AddressTemplate }) => address)
    selectedAddressId.value = addressTemplates.value[0]?.id || null
    const selected = addressTemplates.value.find((a) => a.id === selectedAddressId.value) || null
    if (selected?.latitude != null && selected?.longitude != null) {
      customerLatitude.value = Number(selected.latitude)
      customerLongitude.value = Number(selected.longitude)
    }
  } catch (error: any) {
    showAlert({ severity: 'error', summary: 'Address', detail: error?.response?.data?.message || 'Failed to load addresses' })
  }
}


async function saveNewAddress() {
  addressTouched.name = true
  addressTouched.phone = true
  if (
    !validRecipientName(newAddress.full_name) ||
    !validPhilippineMobile(newAddress.contact_number) ||
    !newAddressSelection.provinceId ||
    !newAddressSelection.cityId ||
    !newAddressSelection.barangayCode ||
    !newAddress.address_line
  ) {
    showAlert({ severity: 'warn', summary: 'Incomplete', detail: 'Please complete all address fields.' })
    return
  }

  try {
    const provinceName = provinces.value.find((p: any) => p.province_id === newAddressSelection.provinceId)?.name || ''
    const cityName = cities.value.find((c: any) => c.city_id === newAddressSelection.cityId)?.name || ''
    const barangayName = barangays.value.find((b: any) => b.code === newAddressSelection.barangayCode)?.name || ''

    const payload = {
      full_name: newAddress.full_name,
      contact_number: newAddress.contact_number,
      province: provinceName,
      city: cityName,
      barangay: barangayName,
      address_line: newAddress.address_line,
      latitude: newAddress.latitude ?? customerLatitude.value ?? undefined,
      longitude: newAddress.longitude ?? customerLongitude.value ?? undefined,
      is_default: addressTemplates.value.length === 0,
    }

    if (isEditingAddress.value && editingAddressId.value) {
      await ecommerceService.updateAddressTemplate(editingAddressId.value, payload)
      await loadAddressTemplates()
      showAlert({ severity: 'success', summary: 'Address Updated', detail: 'Address template updated.' })
    } else {
      const response = await ecommerceService.createAddressTemplate(payload)
      addressTemplates.value.push(response.data?.data)
      selectedAddressId.value = response.data?.data?.id || selectedAddressId.value
      showAlert({ severity: 'success', summary: 'Address Saved', detail: 'New address template has been saved.' })
    }

    showAddAddressForm.value = false
    resetAddressForm()
    estimateShippingFee()
  } catch (error: any) {
    showAlert({ severity: 'error', summary: 'Address', detail: error?.response?.data?.message || 'Failed to save address' })
  }
}

async function loadCheckoutItems() {
  const storeId = route.query.store_id ? Number(route.query.store_id) : null
  const response = await ecommerceService.getCart(storeId ? { store_id: storeId } : undefined)
  const cart = response.data?.data
  const allItems = cart?.items || []

  selectedItemIds.value = String(route.query.item_ids || '')
    .split(',')
    .map((id) => Number(id.trim()))
    .filter((id) => Number.isInteger(id) && id > 0)

  checkoutItems.value = selectedItemIds.value.length
    ? allItems.filter((item: any) => selectedItemIds.value.includes(item.id))
    : allItems

  checkoutItems.value.filter((item: any) => item.is_customizable).forEach((item: any) => {
    if (customizationRequests[item.id] === undefined) customizationRequests[item.id] = item.customization_request || ''
    if (customizationEnabled[item.id] === undefined) customizationEnabled[item.id] = Boolean(item.customization_request)
  })

  if (!checkoutItems.value.length) {
    showAlert({ severity: 'warn', summary: 'Cart Empty', detail: 'Please select cart items first.' })
    router.push({ name: 'ecommerce.cart' })
  }
  await estimateShippingFee()
}

function toggleCustomization(itemId: number) {
  customizationEnabled[itemId] = !customizationEnabled[itemId]
  if (!customizationEnabled[itemId]) customizationRequests[itemId] = ''
}

async function applyVoucher() {
  if (!voucherCode.value.trim()) {
    showAlert({ severity: 'warn', summary: 'Voucher', detail: 'Enter voucher code first.' })
    return
  }

  applyingVoucher.value = true
  try {
    const response = await ecommerceService.validateVoucher({
      code: voucherCode.value.trim(),
      amount: subtotal.value + shippingFeeTotal.value,
    })

    const voucher = response.data?.data?.voucher
    const discount = Number(response.data?.data?.discount_amount || 0)

    appliedVoucher.value = {
      code: String(voucher?.code || voucherCode.value.trim().toUpperCase()),
      discount_type: voucher?.discount_type || 'fixed',
      discount_value: Number(voucher?.discount_value || 0),
    }
    validatedDiscountAmount.value = discount
    showAlert({ severity: 'success', summary: 'Voucher Applied', detail: 'Voucher has been applied.' })
  } catch (error: any) {
    appliedVoucher.value = null
    validatedDiscountAmount.value = 0
    showAlert({ severity: 'error', summary: 'Invalid Voucher', detail: error?.response?.data?.message || 'Voucher does not exist.' })
  } finally {
    applyingVoucher.value = false
  }
}

async function estimateShippingFee() {
  if (!checkoutItems.value.length) return

  const address = selectedAddress.value
    ? `${selectedAddress.value.province}, ${selectedAddress.value.city}, ${selectedAddress.value.barangay}, ${selectedAddress.value.address_line}`
    : ''
  shippingFeeLoading.value = true
  shippingFeeError.value = null
  try {
    const latitude = customerLatitude.value ?? (selectedAddress.value?.latitude ?? undefined)
    const longitude = customerLongitude.value ?? (selectedAddress.value?.longitude ?? undefined)
    const response = await ecommerceService.estimateShippingFee({
      shipping_address: address || undefined,
      customer_latitude: latitude,
      customer_longitude: longitude,
      item_ids: checkoutItemIds.value.length ? checkoutItemIds.value : undefined,
      bulk_trip: bulkTripEnabled.value,
    })
    const fee = Number(response?.data?.data?.shipping_fee || 0)
    pickupOnly.value = Boolean(response?.data?.data?.pickup_only)
    if (pickupOnly.value) serviceMethod.value = 'pickup'
    if (pickupOnly.value) pickupBranch.value = response?.data?.data?.pickup_branch || null
    const fallbackUsed = Boolean(response?.data?.data?.fallback_used)
    const fallbackReason = String(response?.data?.data?.fallback_reason || '')
    const distance = Number(response?.data?.data?.distance_km ?? NaN)
    const allowed = Boolean(response?.data?.data?.bulk_trip_allowed)
    const breakdown = response?.data?.data?.breakdown || null
    estimatedShippingFee.value = Number.isFinite(fee) ? fee : 0
    shippingDistanceKm.value = Number.isFinite(distance) ? distance : null
    bulkTripAllowed.value = allowed
    if (!allowed && bulkTripEnabled.value) {
      bulkTripEnabled.value = false
    }
    shippingFeeBreakdown.value = breakdown
    shippingFeeNotice.value = fallbackUsed
      ? (fallbackReason || 'Using default delivery fee settings for this address.')
      : null
  } catch (error: any) {
    if (serviceMethod.value !== 'pickup') pickupBranch.value = null
    estimatedShippingFee.value = 0
    shippingDistanceKm.value = null
    shippingFeeNotice.value = null
    shippingFeeBreakdown.value = null
    shippingFeeError.value = error?.response?.data?.message || 'Delivery fee is unavailable. Please check your address or choose pickup.'
  } finally {
    shippingFeeLoading.value = false
  }
}

function toBackendPaymentMethod(method: 'gcash' | 'card' | 'paymaya') {
  return method === 'card' ? 'card' : 'e_wallet'
}

async function fetchProvinces() {
  try {
    const response = await ecommerceService.getProvinces()
    provinces.value = response.data || []
  } catch {
    provinces.value = []
  }
}

async function fetchCities(provinceId: string) {
  if (!provinceId) {
    cities.value = []
    return
  }

  if (citiesCache.value[provinceId]) {
    cities.value = citiesCache.value[provinceId]
    return
  }

  try {
    const response = await ecommerceService.getCities(provinceId)
    citiesCache.value[provinceId] = response.data || []
    cities.value = citiesCache.value[provinceId]
  } catch {
    cities.value = []
  }
}

async function fetchBarangays(cityId: string) {
  if (!cityId) {
    barangays.value = []
    return
  }

  try {
    const response = await ecommerceService.getBarangays(cityId)
    barangays.value = response.data || []
  } catch {
    barangays.value = []
  }
}

async function onProvinceChange() {
  newAddressSelection.cityId = ''
  newAddressSelection.barangayCode = ''
  barangays.value = []
  await fetchCities(newAddressSelection.provinceId)
}

async function onCityChange() {
  newAddressSelection.barangayCode = ''
  await fetchBarangays(newAddressSelection.cityId)
}

const buildAddressForGeocoding = (): string => {
  const parts = [
    newAddress.address_line,
    newAddress.barangay,
    newAddress.city,
    newAddress.province,
    'Philippines',
  ]

  return parts
    .map((part) => String(part || '').trim())
    .filter(Boolean)
    .join(', ')
}

const geocodeAddressText = async (addressText: string): Promise<{ latitude: number; longitude: number } | null> => {
  const query = String(addressText || '').trim()
  if (!query) return null

  try {
    const response = await fetch(
      `https://nominatim.openstreetmap.org/search?format=jsonv2&q=${encodeURIComponent(query)}&limit=1`,
      {
        headers: { Accept: 'application/json' },
      },
    )

    if (!response.ok) return null
    const results = await response.json()
    if (!Array.isArray(results) || results.length === 0) return null

    const first = results[0]
    const latitude = Number(first?.lat)
    const longitude = Number(first?.lon)
    if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) return null

    return { latitude, longitude }
  } catch {
    return null
  }
}

async function startEditAddress(address: AddressTemplate) {
  isEditingAddress.value = true
  editingAddressId.value = address.id
  showAddAddressForm.value = true

  newAddress.full_name = address.full_name
  newAddress.contact_number = address.contact_number
  newAddress.province = address.province
  newAddress.city = address.city
  newAddress.barangay = address.barangay
  newAddress.address_line = address.address_line
  newAddress.latitude = address.latitude ?? null
  newAddress.longitude = address.longitude ?? null

  const province = provinces.value.find((p: any) => String(p.name).toLowerCase() === String(address.province).toLowerCase())
  newAddressSelection.provinceId = province?.province_id || ''
  newAddressSelection.cityId = ''
  newAddressSelection.barangayCode = ''
  cities.value = []
  barangays.value = []

  if (newAddressSelection.provinceId) {
    await fetchCities(newAddressSelection.provinceId)
    const city = cities.value.find((c: any) => String(c.name).toLowerCase() === String(address.city).toLowerCase())
    newAddressSelection.cityId = city?.city_id || ''
  }

  if (newAddressSelection.cityId) {
    await fetchBarangays(newAddressSelection.cityId)
    const barangay = barangays.value.find((b: any) => String(b.name).toLowerCase() === String(address.barangay).toLowerCase())
    newAddressSelection.barangayCode = barangay?.code || ''
  }
}

// Coordinates are picked via the interactive Mapbox dialog.

async function placeOrder() {
  if (placing.value || paymongoCreating.value) return
  if (retryOrderId.value) {
    placing.value = true
    try {
      const orderResponse = await ecommerceService.getOrder(retryOrderId.value)
      const order = orderResponse?.data?.data
      if (!order || ['paid'].includes(String(order.payment_status)) || ['cancelled', 'canceled'].includes(String(order.status))) {
        retryOrderId.value = null
        window.sessionStorage.removeItem(PAYMONGO_PENDING_ORDER_STORAGE_KEY)
        throw new Error('This order can no longer be paid. Please start a new checkout.')
      }
      const orderId = Number(order.id)
      const successUrl = new URL(router.resolve({ name: 'ecommerce.checkout', query: { paymongo_order_id: orderId, paymongo_success: '1' } }).href, window.location.origin).toString()
      const cancelUrl = new URL(router.resolve({ name: 'ecommerce.checkout', query: { paymongo_order_id: orderId, paymongo_cancel: '1' } }).href, window.location.origin).toString()
      paymongoCreating.value = true
      const sessionResponse = await paymongoService.createCheckoutSession({
        amount: Math.max(Math.round(Number(order.total_amount) * 100), 1),
        currency: 'PHP',
        description: `Order #${orderId}`,
        store_id: Number(order.store_id),
        payable_type: 'ecommerce_order',
        payable_id: orderId,
        payment_method_allowed: [selectedPaymentMethod.value],
        metadata: { order_id: orderId },
        success_url: successUrl,
        cancel_url: cancelUrl,
      })
      const checkoutUrl = String(sessionResponse?.data?.checkout_url || '')
      if (!checkoutUrl.startsWith('https://checkout.paymongo.com/')) throw new Error('PayMongo did not return a valid checkout page.')
      window.location.assign(checkoutUrl)
    } catch (error: any) {
      const paymentInProgress = Number(error?.response?.status) === 409
      showAlert({ severity: paymentInProgress ? 'warn' : 'error', summary: paymentInProgress ? 'Payment in Progress' : 'Payment Unavailable', detail: error?.response?.data?.message || error?.message || 'Please try again.' })
    } finally {
      placing.value = false
      paymongoCreating.value = false
    }
    return
  }
  if (paymentMethodsLoading.value || paymentMethodsError.value || !allowedPaymentMethods.value.includes(selectedPaymentMethod.value)) {
    checkoutStep.value = 3
    return
  }
  if (serviceMethod.value === 'delivery' && shippingFeeError.value) {
    checkoutStep.value = 2
    return
  }
  if (!fulfillmentDates.value.includes(fulfillmentDateString.value)) {
    showAlert({ severity: 'warn', summary: 'Date Required', detail: 'Please select an available pickup or delivery date.' })
    checkoutStep.value = 2
    return
  }
  if (!selectedAddress.value) {
    showAlert({ severity: 'warn', summary: 'Address Required', detail: 'Please select a shipping address.' })
    return
  }

  if (billingAddressIncomplete.value) {
    showAlert({ severity: 'warn', summary: 'Billing Address', detail: 'Complete the required billing address fields.' })
    return
  }

  placing.value = true
  try {
    if (
      customerLatitude.value == null &&
      customerLongitude.value == null &&
      selectedAddress.value
    ) {
      const selectedAddressText = `${selectedAddress.value.address_line}, ${selectedAddress.value.barangay}, ${selectedAddress.value.city}, ${selectedAddress.value.province}, Philippines`
      const geocoded = await geocodeAddressText(selectedAddressText)
      if (geocoded) {
        customerLatitude.value = geocoded.latitude
        customerLongitude.value = geocoded.longitude
      }
    }

    const payload = {
      shipping_name: selectedAddress.value?.full_name || authStore.user?.name || 'Customer',
      shipping_phone: selectedAddress.value?.contact_number || undefined,
      shipping_email: authStore.user?.email || undefined,
      shipping_address: serviceMethod.value === 'pickup' ? 'Store pickup' : `${selectedAddress.value.province}, ${selectedAddress.value.city}, ${selectedAddress.value.barangay}, ${selectedAddress.value.address_line}`,
      billing_name: useDifferentBillingAddress.value
        ? billingForm.name.trim() : selectedAddress.value?.full_name,
      billing_address: useDifferentBillingAddress.value
        ? [billingForm.street, billingForm.barangay, billingForm.city, billingForm.province, billingForm.postalCode].map((part) => part.trim()).filter(Boolean).join(', ')
        : `${selectedAddress.value.province}, ${selectedAddress.value.city}, ${selectedAddress.value.barangay}, ${selectedAddress.value.address_line}`,
      payment_method: toBackendPaymentMethod(selectedPaymentMethod.value),
      shipping_fee: shippingFeeTotal.value,
      fulfillment_method: serviceMethod.value,
      requested_fulfillment_date: fulfillmentDateString.value,
      discount_amount: discountAmount.value,
      voucher_code: appliedVoucher.value?.code,
      notes: appliedVoucher.value ? `Voucher: ${appliedVoucher.value.code}` : '',
      item_ids: checkoutItemIds.value.length ? checkoutItemIds.value : undefined,
      customization_requests: Object.fromEntries(
        checkoutItems.value
          .filter((item: any) => item.is_customizable)
          .map((item: any) => [
            Number(item.id),
            customizationEnabled[item.id] ? String(customizationRequests[item.id] || '').trim() : '',
          ]),
      ),
      customer_latitude: serviceMethod.value === 'pickup' ? undefined : (customerLatitude.value ?? selectedAddress.value?.latitude ?? undefined),
      customer_longitude: serviceMethod.value === 'pickup' ? undefined : (customerLongitude.value ?? selectedAddress.value?.longitude ?? undefined),
      bulk_trip: bulkTripEnabled.value,
    }

    const response = await ecommerceService.checkout(payload)
    const orderId = response.data?.data?.id
    const orderStoreId = Number(response.data?.data?.store_id || checkoutItems.value?.[0]?.store_id || 0)
    if (orderId) {
      retryOrderId.value = Number(orderId)
      window.sessionStorage.setItem(PAYMONGO_PENDING_ORDER_STORAGE_KEY, String(orderId))
    }

    if (selectedPaymentMethod.value) {
      if (!orderId || !orderStoreId) {
        throw new Error('Order ID or store ID is missing for Online Payment checkout.')
      }

      paymongoCreating.value = true

      const successUrl = new URL(router.resolve({ name: 'ecommerce.checkout', query: { paymongo_order_id: orderId, paymongo_success: '1' } }).href, window.location.origin).toString()
      const cancelUrl = new URL(router.resolve({ name: 'ecommerce.checkout', query: { paymongo_order_id: orderId, paymongo_cancel: '1' } }).href, window.location.origin).toString()
      const sessionResponse = await paymongoService.createCheckoutSession({
        amount: Math.max(Math.round(totalAmount.value * 100), 1),
        currency: 'PHP',
        description: `Order #${orderId}`,
        store_id: orderStoreId,
        payable_type: 'ecommerce_order',
        payable_id: Number(orderId),
        payment_method_allowed: [selectedPaymentMethod.value],
        metadata: { order_id: orderId },
        success_url: successUrl,
        cancel_url: cancelUrl,
      })
      const checkoutUrl = String(sessionResponse?.data?.checkout_url || '')
      if (!checkoutUrl.startsWith('https://checkout.paymongo.com/')) {
        throw new Error('PayMongo did not return a valid checkout page.')
      }
      try {
        window.sessionStorage.setItem(PAYMONGO_PENDING_ORDER_STORAGE_KEY, String(orderId))
      } catch {}
      window.location.assign(checkoutUrl)
      return
    }

    showAlert({ severity: 'success', summary: 'Order Placed', detail: 'Your order was created successfully.' })
    router.push({ name: 'ecommerce.orders', query: { placed: orderId } })
  } catch (error: any) {
    showAlert({ severity: 'error', summary: 'Checkout Failed', detail: error?.response?.data?.message || error?.message || 'Please try again.' })
  } finally {
    placing.value = false
    paymongoCreating.value = false
  }
}

function normalizeImageUrl(raw: string) {
  if (!raw) return ''
  if (raw.startsWith('http://') || raw.startsWith('https://') || raw.startsWith('data:')) return raw
  if (raw.startsWith('/storage/')) return raw
  if (raw.startsWith('storage/')) return `/${raw}`
  return `/storage/${raw.replace(/^\//, '')}`
}

function onImageError(event: Event) {
  const target = event.target as HTMLImageElement | null
  if (target) target.src = '/F.svg'
}

async function checkPaymongoResult(orderId: number): Promise<string> {
  checkingPaymongoResult.value = true
  try {
    const latest = await paymongoService.getLatestIntentByPayable('ecommerce_order', orderId, { sync: true })
    const status = String(latest?.data?.status || '').toLowerCase().trim()

    if (!status) {
      showAlert({ severity: 'warn', summary: 'Payment Pending', detail: 'No Online Payment status yet. Please wait a moment and refresh.' })
      return 'pending'
    }

    if (status === 'succeeded') {
      completedPaymongoOrderId.value = orderId
      try {
        window.sessionStorage.removeItem(PAYMONGO_PENDING_ORDER_STORAGE_KEY)
      } catch {}
      return status
    }

    if (status === 'failed' || status === 'cancelled' || status === 'canceled' || status === 'expired') {
      showAlert({ severity: 'error', summary: 'Payment Failed', detail: 'Your payment was not completed. You can try again.' })
      return status
    }

    // silent for intermediate statuses; user can refresh or close dialogs
    return status
  } catch (error: any) {
    showAlert({ severity: 'warn', summary: 'Payment Pending', detail: 'Unable to confirm payment yet. Please refresh in a moment.' })
    return 'unknown'
  } finally {
    checkingPaymongoResult.value = false
  }
}

function goCart() {
  router.push({ name: 'ecommerce.cart' })
}

function goToCompletedOrder() {
  if (!completedPaymongoOrderId.value) return
  router.push({ name: 'ecommerce.order-detail', params: { id: completedPaymongoOrderId.value } })
}

function goShopping() {
  router.push({ name: 'ecommerce.products' })
}

onMounted(async () => {
  let paymongoOrderId = Number(route.query?.paymongo_order_id || 0)
  let paymongoResultStatus = ''
  if (!paymongoOrderId) {
    try {
      const stored = Number(window.sessionStorage.getItem(PAYMONGO_PENDING_ORDER_STORAGE_KEY) || 0)
      if (stored > 0) paymongoOrderId = stored
    } catch {}
  }
  if (paymongoOrderId > 0) {
    paymongoResultStatus = await checkPaymongoResult(paymongoOrderId)
    if (paymongoResultStatus === 'succeeded') {
      loading.value = false
      router.replace({ name: 'ecommerce.order-detail', params: { id: paymongoOrderId } })
      return
    }
    retryOrderId.value = paymongoOrderId

    // Remove the query so refresh doesn't keep firing the result alert.
    const nextQuery = { ...route.query }
    delete (nextQuery as any).paymongo_order_id
    delete (nextQuery as any).paymongo_success
    delete (nextQuery as any).paymongo_cancel
    if (Object.keys(nextQuery).length !== Object.keys(route.query).length) {
      router.replace({ query: nextQuery })
    }
  }

  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      (position) => {
        customerLatitude.value = Number(position.coords.latitude)
        customerLongitude.value = Number(position.coords.longitude)
      },
      () => {
        customerLatitude.value = null
        customerLongitude.value = null
      },
      { enableHighAccuracy: false, maximumAge: 120000, timeout: 5000 }
    )
  }

  loading.value = true
  try {
    await fetchProvinces()
    await loadAddressTemplates()
    await loadCheckoutItems()
    await loadAvailablePaymentMethods()
    await estimateShippingFee()
  } finally {
    loading.value = false
  }
})
</script>


<style scoped>
@font-face {
  font-family: 'Barabara';
  src: url('/fonts/BARABARA-final.otf') format('opentype');
  font-weight: 400;
  font-style: normal;
  font-display: swap;
}

.portal-brand {
  font-family: 'Barabara', sans-serif;
}

</style>
