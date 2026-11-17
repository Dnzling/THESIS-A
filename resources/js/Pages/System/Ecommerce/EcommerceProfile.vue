<template>
  <div class="space-y-4">
    <ConfirmDialog />
    <div class="rounded-3xl border border-slate-200 bg-white/80">
      <div class="grid grid-cols-1 lg:grid-cols-[280px_1fr]">
        <aside class="hidden border-b border-slate-200 p-5 lg:block lg:border-b-0 lg:border-r">
          <h2 class="text-xl font-semibold text-slate-900">My Account</h2>
          <div class="mt-5 space-y-5">
            <div>
              <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">MyAccount</p>
              <div class="space-y-1">
                <Button label="Basic Information" text fluid :severity="activeSection === 'basic' ? 'warn' : 'secondary'"
                  class="justify-start" @click="activeSection = 'basic'" />
                <Button label="Address Book" text fluid :severity="activeSection === 'address' ? 'warn' : 'secondary'"
                  class="justify-start" @click="activeSection = 'address'" />
                <Button label="Payment Methods" text fluid :severity="activeSection === 'payment' ? 'warn' : 'secondary'"
                  class="justify-start" @click="activeSection = 'payment'" />
              </div>
            </div>
  
            <div>
              <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">My Orders</p>
              <div class="space-y-1">
                <Button label="Returns" text fluid :severity="activeSection === 'returns' ? 'warn' : 'secondary'"
                  class="justify-start" @click="activeSection = 'returns'" />
                <Button label="Cancellations" text fluid
                  :severity="activeSection === 'cancellations' ? 'warn' : 'secondary'" class="justify-start"
                  @click="activeSection = 'cancellations'" />
              </div>
            </div>

            <div class="pt-2 lg:hidden">
              <Button
                label="Logout"
                text
                fluid
                severity="danger"
                class="justify-start"
                :loading="loggingOut"
                @click="logoutCustomer"
              />
            </div>
          </div>
        </aside>
  
        <section class="p-5">
          <div class="mb-4 flex items-center justify-between gap-3 lg:hidden">
            <div>
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">My Account</p>
              <h2 class="text-lg font-semibold text-slate-900">{{ sectionTitle }}</h2>
            </div>
            <Button label="Menu" icon="pi pi-bars" severity="secondary" outlined @click="mobileNavigationVisible = true" />
          </div>
          <div v-if="loading" class="space-y-3">
            <Skeleton v-for="idx in 8" :key="idx" height="1.2rem" />
          </div>
  
          <template v-else>
            <div v-if="activeSection === 'basic'" class="space-y-4">
              <h3 class="text-2xl font-semibold text-slate-900">Basic Information</h3>
  
              <div class="grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
                <div class="rounded-xl border border-slate-200 p-3">
                  <p class="text-xs text-slate-500">First Name</p>
                  <p class="mt-1 font-semibold">{{ basicInfo.firstName }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 p-3">
                  <p class="text-xs text-slate-500">Last Name</p>
                  <p class="mt-1 font-semibold">{{ basicInfo.lastName }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 p-3">
                  <p class="text-xs text-slate-500">Email</p>
                  <p class="mt-1 font-semibold">{{ basicInfo.maskedEmail }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 p-3">
                  <p class="text-xs text-slate-500">Contact Number</p>
                  <p class="mt-1 font-semibold">{{ basicInfo.maskedPhone }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 p-3">
                  <p class="text-xs text-slate-500">Birthday</p>
                  <p class="mt-1 font-semibold">{{ basicInfo.formattedBirthday }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 p-3">
                  <p class="text-xs text-slate-500">Created At</p>
                  <p class="mt-1 font-semibold">{{ basicInfo.formattedCreatedAt }}</p>
                </div>
              </div>
  
              <div class="flex flex-wrap gap-2">
                <Button label="Edit" outlined @click="editDialogVisible = true" />
                <Button label="Change Password" outlined @click="passwordDialogVisible = true" />
                <Button label="Change Email (Verify)" severity="secondary" outlined @click="emailDialogVisible = true" />
                <Button label="Change Mobile" severity="secondary" outlined
                  @click="mobileDialogVisible = true" />
              </div>
            </div>
  
            <div v-else-if="activeSection === 'address'" class="space-y-4">
              <div class="flex flex-wrap items-center justify-between gap-2">
                <h3 class="text-2xl font-semibold text-slate-900">Address Book</h3>
                <Button label="Add Address"  text @click="openAddAddressDialog" />
              </div>
              <p v-if="!addressTemplates.length" class="rounded-xl border border-slate-200 p-4 text-sm text-slate-500">
                No address preset yet. Add one to speed up checkout.
              </p>
              <div v-for="template in addressTemplates" :key="template.id" class="rounded-xl border border-slate-200 p-4">
                <div v-if="editingAddressId !== template.id" class="flex flex-wrap items-start justify-between gap-2">
                  <div>
                    <p class="text-sm font-semibold text-slate-900">{{ template.full_name }} - {{ template.contact_number
                      }}</p>
                    <p class="text-xs text-slate-600">{{ template.province }}, {{ template.city }}, {{ template.barangay
                      }}, {{ template.address_line }}</p>
                  </div>
                  <Button label="Edit" text  @click="startEditAddress(template)" />
                </div>
                <div v-else class="space-y-2">
                  <InputText v-model="addressEditForm.full_name" fluid placeholder="Full name" />
                  <InputMask mask="9999-999-9999" v-model="addressEditForm.contact_number" fluid
                    placeholder="0912-456-7890" />
  
                  <Select v-model="editAddressSelection.provinceId" :options="provinceOptions" optionLabel="label"
                    optionValue="value" filter fluid placeholder="Select Province" @change="onEditProvinceChange" />
                  <Select v-model="editAddressSelection.cityId" :options="editCityOptions" optionLabel="label"
                    optionValue="value" filter fluid placeholder="Select City"
                    :disabled="!editAddressSelection.provinceId" @change="onEditCityChange" />
                  <Select v-model="editAddressSelection.barangayCode" :options="editBarangayOptions" optionLabel="label"
                    optionValue="value" filter fluid placeholder="Select Barangay"
                    :disabled="!editAddressSelection.cityId" />
                  <Textarea v-model="addressEditForm.address_line" fluid rows="2" placeholder="Address line" />
                  <div class="flex gap-2">
                    <Button label="Save" size="small"  :loading="savingAddress"
                      @click="saveAddressEdit(template.id)" />
                    <Button label="Cancel" size="small" severity="secondary" outlined @click="editingAddressId = null" />
                  </div>
                </div>
              </div>
            </div>
  
            <div v-else-if="activeSection === 'payment'" class="space-y-4">
              <div>
                <h3 class="text-2xl font-semibold text-slate-900">Available Payment Methods</h3>
                <p class="mt-1 text-sm text-slate-500">These payment methods are available for ecommerce payments and refunds.</p>
              </div>
              <div class="grid gap-3 sm:grid-cols-2">
                <div class="flex items-center gap-3 rounded-2xl border border-slate-200 p-4">
                  <span class="flex h-11 w-11 items-center justify-center rounded-full bg-blue-50 text-blue-700"><i class="pi pi-mobile" /></span>
                  <div>
                    <p class="font-semibold text-slate-900">GCash</p>
                    <p class="text-sm text-slate-500">Mobile wallet</p>
                  </div>
                </div>
                <div class="flex items-center gap-3 rounded-2xl border border-slate-200 p-4">
                  <span class="flex h-11 w-11 items-center justify-center rounded-full bg-amber-50 text-amber-700"><i class="pi pi-credit-card" /></span>
                  <div>
                    <p class="font-semibold text-slate-900">Card</p>
                    <p class="text-sm text-slate-500">Credit or debit card</p>
                  </div>
                </div>
              </div>
              <p class="rounded-xl bg-slate-50 p-3 text-sm text-slate-600">
                Refund account details are entered inside the related Return Order when the return is approved.
              </p>
            </div>

            <div v-else-if="false" class="space-y-4">
              <div class="flex items-center justify-between">
                <h3 class="text-2xl font-semibold text-slate-900">Notifications</h3>
                <Button label="Mark all as read" size="small" text severity="secondary" :disabled="notificationsLoading || unreadNotificationCount === 0" @click="markAllNotificationsRead" />
              </div>
              <p v-if="notificationsLoading" class="text-sm text-slate-500">Loading notifications...</p>
              <p v-else-if="notifications.length === 0" class="text-sm text-slate-500">No notifications yet.</p>
              <div v-else class="space-y-2">
                <button
                  v-for="notif in notifications"
                  :key="notif.id"
                  type="button"
                  class="w-full rounded-xl border p-3 text-left transition"
                  :class="notif.is_read ? 'border-slate-200 bg-white' : 'border-amber-200 bg-amber-50'"
                  @click="openNotification(notif)"
                >
                  <div class="flex items-start justify-between gap-2">
                    <p class="text-sm font-semibold text-slate-900">{{ notif.title || 'Notification' }}</p>
                    <span class="text-xs text-slate-500">{{ formatDate(notif.created_at) }}</span>
                  </div>
                  <p class="mt-1 text-xs text-slate-600">{{ notif.message || 'Tap to view details.' }}</p>
                  <p
                    v-if="notif.entity_type === 'product_review' && notif.data?.reply"
                    class="mt-2 rounded-lg border border-sky-100 bg-sky-50 px-3 py-2 text-sm italic text-slate-700"
                  >
                    “{{ notif.data.reply }}”
                  </p>
                </button>
              </div>
            </div>

            <div v-else-if="activeSection === 'returns'" class="space-y-4">
              <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                  <h3 class="text-2xl font-semibold text-slate-900">Returns</h3>
                  <p class="mt-1 text-sm text-slate-500">Return updates here match your order details and notifications.</p>
                </div>
                <span v-if="returnOrders.length" class="rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-700">
                  {{ returnOrders.length }} order{{ returnOrders.length === 1 ? '' : 's' }}
                </span>
              </div>
              <div v-if="!returnOrders.length" class="rounded-2xl border border-dashed border-slate-300 py-10 text-center text-sm text-slate-500">
                No return records yet.
              </div>
              <div v-for="order in returnOrders" :key="order.id" class="rounded-2xl border border-slate-200 bg-white p-4 text-sm">
                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 pb-3">
                  <div>
                    <p class="font-semibold text-slate-900">Order #{{ order.order_number }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ order.store_name }}</p>
                  </div>
                  <span class="rounded-full px-3 py-1 text-xs font-semibold capitalize" :class="orderStatusClass(order.primary_status || order.status)">
                    {{ formatOrderStatus(order.primary_status || order.status) }}
                  </span>
                </div>
                <div class="mt-3 space-y-2">
                  <div v-for="item in getReturnedItems(order)" :key="item.id" class="flex items-center gap-3 rounded-xl bg-slate-50 p-3">
                    <img v-if="item.image" :src="item.image" :alt="item.product_name" loading="lazy" decoding="async" class="h-12 w-12 shrink-0 rounded-lg border border-slate-200 object-cover" />
                    <span v-else class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-slate-200 text-slate-400"><i class="pi pi-image" /></span>
                    <div class="min-w-0 flex-1">
                      <p class="truncate font-semibold text-slate-800">{{ item.product_name }}</p>
                      <p class="text-xs text-slate-500">Qty {{ item.return_request?.requested_quantity || item.quantity }}</p>
                      <p class="mt-1 text-xs font-medium capitalize text-orange-600">{{ formatOrderStatus(item.return_request?.status) }}</p>
                    </div>
                  </div>
                </div>
                <div class="mt-3 flex justify-end">
                  <Button label="View order" icon="pi pi-arrow-right" iconPos="right" size="small" severity="warn" outlined @click="goOrderDetail(order.id)" />
                </div>
              </div>
            </div>
  
            <div v-else class="space-y-4">
              <div>
                <h3 class="text-2xl font-semibold text-slate-900">Cancellations</h3>
                <p class="mt-1 text-sm text-slate-500">Your cancelled orders appear here.</p>
              </div>
              <div v-if="!cancellationOrders.length" class="rounded-2xl border border-dashed border-slate-300 py-10 text-center text-sm text-slate-500">
                No cancellation records yet.
              </div>
              <div v-for="order in cancellationOrders" :key="order.id" class="rounded-2xl border border-slate-200 bg-white p-4 text-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                  <div>
                    <p class="font-semibold text-slate-900">Order #{{ order.order_number }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ order.store_name }}</p>
                    <p v-if="order.cancellation_request?.reason" class="mt-2 text-sm text-slate-600">{{ order.cancellation_request.reason }}</p>
                  </div>
                  <span class="rounded-full px-3 py-1 text-xs font-semibold capitalize" :class="orderStatusClass(order.cancellation_request?.status || order.primary_status || order.status)">
                    {{ formatOrderStatus(order.primary_status || order.status) }}
                  </span>
                </div>
                <p v-if="order.cancellation_request?.review_notes" class="mt-3 rounded-xl bg-slate-50 p-3 text-xs text-slate-600">
                  Store note: {{ order.cancellation_request.review_notes }}
                </p>
                <div class="mt-3 flex justify-end">
                  <Button label="View order" icon="pi pi-arrow-right" iconPos="right" size="small" severity="secondary" outlined @click="goOrderDetail(order.id)" />
                </div>
              </div>
            </div>
          </template>
        </section>
      </div>
    </div>
  
    <Dialog v-model:visible="editDialogVisible" modal header="Edit Basic Information" :style="{ width: '32rem' }">
      <div class="space-y-3">
        <InputText v-model="editForm.fname" fluid placeholder="First name" />
        <InputText v-model="editForm.lname" fluid placeholder="Last name" />
        <DatePicker v-model="editForm.birthday" :maxDate="new Date()" fluid showIcon dateFormat="mm/dd/yy" />
        <Button label="Save Changes"  :loading="savingProfile" @click="saveBasicInfo" />
      </div>
    </Dialog>
  
    <Dialog v-model:visible="passwordDialogVisible" modal header="Change Password" :style="{ width: '32rem' }">
      <div class="space-y-3">
        <Password v-model="passwordForm.current_password" :feedback="false" fluid toggleMask
          placeholder="Current password" />
        <Password v-model="passwordForm.password" :feedback="true" fluid toggleMask placeholder="New password" />
        <Password v-model="passwordForm.password_confirmation" :feedback="false" fluid toggleMask
          placeholder="Confirm new password" />
        <Button label="Update Password"  :loading="changingPassword" @click="changePassword" />
      </div>
    </Dialog>
  
    <Dialog v-model:visible="emailDialogVisible" modal header="Change Email (Verification Required)"
      :style="{ width: '32rem' }">
      <div class="space-y-3">
        <InputText v-model="contactChangeForm.newEmail" fluid placeholder="New email address" />
        <div class="flex gap-2">
          <InputText v-model="contactChangeForm.otpCode" fluid placeholder="Enter OTP code" />
          <Button label="Send OTP" severity="secondary" outlined :loading="sendingOtp" @click="sendOtp" />
        </div>
        <Button label="Verify and Change Email"  :loading="savingContactChange"
          @click="verifyAndChangeEmail" />
      </div>
    </Dialog>
  
    <Dialog v-model:visible="mobileDialogVisible" modal header="Change Mobile Number"
      :style="{ width: '32rem' }">
      <div class="space-y-3">
        <InputText v-model="contactChangeForm.newMobile" fluid placeholder="New mobile number" />
        <Button label="Save Mobile Number" :loading="savingContactChange" @click="changeMobile" />
      </div>
    </Dialog>

    <Dialog
      v-model:visible="mobileNavigationVisible"
      modal
      :closable="false"
      :showHeader="false"
      :style="{ width: '100vw', height: '100dvh', maxHeight: '100dvh', margin: '0' }"
      :pt="{ mask: { class: 'p-0 lg:hidden' }, root: { class: 'mobile-account-navigation lg:hidden !m-0 !max-h-none !w-screen !rounded-none' }, content: { class: 'h-full p-0' } }"
    >
      <div class="flex h-full flex-col bg-white p-5">
        <div class="mb-6 flex items-center justify-between border-b border-slate-200 pb-4">
          <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">My Account</p>
            <h2 class="text-xl font-semibold text-slate-900">Navigate</h2>
          </div>
          <Button icon="pi pi-times" aria-label="Close navigation" severity="secondary" text rounded @click="mobileNavigationVisible = false" />
        </div>
        <div class="flex-1 space-y-6 overflow-y-auto">
          <div>
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">My Account</p>
            <div class="space-y-1">
              <Button label="Basic Information" icon="pi pi-user" text fluid :severity="activeSection === 'basic' ? 'warn' : 'secondary'" class="justify-start" @click="selectSection('basic')" />
              <Button label="Address Book" icon="pi pi-map-marker" text fluid :severity="activeSection === 'address' ? 'warn' : 'secondary'" class="justify-start" @click="selectSection('address')" />
              <Button label="Payment Methods" icon="pi pi-credit-card" text fluid :severity="activeSection === 'payment' ? 'warn' : 'secondary'" class="justify-start" @click="selectSection('payment')" />
            </div>
          </div>
          <div>
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">My Orders</p>
            <div class="space-y-1">
              <Button label="Returns" icon="pi pi-replay" text fluid :severity="activeSection === 'returns' ? 'warn' : 'secondary'" class="justify-start" @click="selectSection('returns')" />
              <Button label="Cancellations" icon="pi pi-times-circle" text fluid :severity="activeSection === 'cancellations' ? 'warn' : 'secondary'" class="justify-start" @click="selectSection('cancellations')" />
            </div>
          </div>
        </div>
        <Button label="Logout" icon="pi pi-sign-out" text fluid severity="danger" class="mt-4 justify-start border-t border-slate-200 pt-4" :loading="loggingOut" @click="logoutCustomer" />
      </div>
    </Dialog>
  
    <Dialog v-model:visible="addAddressDialogVisible" modal header="Add Address Preset" :style="{ width: '32rem' }">
      <div class="space-y-3">
        <InputText v-model="addAddressForm.full_name" fluid placeholder="Full name" />
        <InputMask mask="9999-999-9999" v-model="addAddressForm.contact_number" fluid placeholder="Contact number" />
        <Select v-model="addAddressSelection.provinceId" :options="provinceOptions" optionLabel="label"
          optionValue="value" filter fluid placeholder="Select Province" @change="onAddProvinceChange" />
        <Select v-model="addAddressSelection.cityId" :options="cityOptions" optionLabel="label" optionValue="value" filter
          fluid placeholder="Select City" :disabled="!addAddressSelection.provinceId" @change="onAddCityChange" />
        <Select v-model="addAddressSelection.barangayCode" :options="barangayOptions" optionLabel="label"
          optionValue="value" filter fluid placeholder="Select Barangay" :disabled="!addAddressSelection.cityId" />
        <Textarea v-model="addAddressForm.address_line" fluid rows="2" placeholder="Address line" />
        <Button label="Save Address"  :loading="savingNewAddress" @click="createAddressTemplate" />
      </div>
    </Dialog>

  </div>
</template>

<script setup lang="ts">
import EcommerceMobileWrapper from '@/Layouts/EcommerceMobileWrapper.vue'
import { computed, onMounted, reactive, ref, watch } from 'vue'
import axios from 'axios'
import axiosClient from '@/axios'
import ecommerceService from '@/services/ecommerce.service'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Password from 'primevue/password'
import Dialog from 'primevue/dialog'
import DatePicker from 'primevue/datepicker'
import Select from 'primevue/select'
import ConfirmDialog from 'primevue/confirmdialog'
import { useConfirm } from 'primevue/useconfirm'
import { showAlert } from '@/utils/swal'
defineOptions({
  layout: EcommerceMobileWrapper,
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
}

const loading = ref(false)
const initialProfileSection = String(new URLSearchParams(window.location.search).get('section') || '').toLowerCase()
const activeSection = ref<'basic' | 'address' | 'payment' | 'notifications' | 'returns' | 'cancellations'>(
  ['address', 'payment', 'returns', 'cancellations'].includes(initialProfileSection)
    ? initialProfileSection as any
    : 'basic',
)
const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const confirm = useConfirm()
const myOrders = ref<any[]>([])
const profileUser = ref<any>({})
const profileEmployee = ref<any>(null)
const profileCustomer = ref<any>(null)
const notifications = ref<any[]>([])
const addressTemplates = ref<AddressTemplate[]>([])
const editingAddressId = ref<number | null>(null)
const savingAddress = ref(false)

const editDialogVisible = ref(false)
const passwordDialogVisible = ref(false)
const emailDialogVisible = ref(false)
const mobileDialogVisible = ref(false)
const mobileNavigationVisible = ref(false)
const addAddressDialogVisible = ref(false)
const savingProfile = ref(false)
const changingPassword = ref(false)
const sendingOtp = ref(false)
const savingContactChange = ref(false)
const savingNewAddress = ref(false)
const loggingOut = ref(false)
const notificationsLoading = ref(false)

const editForm = reactive<{ fname: string; lname: string; birthday: Date | null }>({ fname: '', lname: '', birthday: null })
const passwordForm = reactive({ current_password: '', password: '', password_confirmation: '' })
const contactChangeForm = reactive({ newEmail: '', newMobile: '', otpCode: '' })
const addressEditForm = reactive({
  full_name: '',
  contact_number: '',
  province: '',
  city: '',
  barangay: '',
  address_line: '',
  latitude: null as number | null,
  longitude: null as number | null,
})
const addAddressForm = reactive({
  full_name: '',
  contact_number: '',
  province: '',
  city: '',
  barangay: '',
  address_line: '',
  latitude: null as number | null,
  longitude: null as number | null,
})
const addAddressSelection = reactive({ provinceId: '', cityId: '', barangayCode: '' })
const editAddressSelection = reactive({ provinceId: '', cityId: '', barangayCode: '' })
const provinces = ref<any[]>([])
const cities = ref<any[]>([])
const barangays = ref<any[]>([])
const editCities = ref<any[]>([])
const editBarangays = ref<any[]>([])
const citiesCache = ref<Record<string, any[]>>({})

const returnOrderStatuses = new Set(['return_pending', 'return_requested', 'return_approved', 'return_received', 'refund_pending', 'refunded', 'replaced', 'returned'])
const cancellationOrderStatuses = new Set(['cancel_pending', 'cancellation_pending', 'cancelled', 'canceled'])
const returnOrders = computed(() => myOrders.value.filter((order: any) => {
  const status = String(order.primary_status || order.status || '').toLowerCase()
  return returnOrderStatuses.has(status) || getReturnedItems(order).length > 0
}))
const cancellationOrders = computed(() => myOrders.value.filter((order: any) => {
  const status = String(order.primary_status || order.status || '').toLowerCase()
  return cancellationOrderStatuses.has(status) || Boolean(order.cancellation_request)
}))
const unreadNotificationCount = computed(() => notifications.value.filter((n: any) => !n?.is_read).length)
const basicInfo = computed(() => ({
  firstName: profileUser.value?.fname || '-',
  lastName: profileUser.value?.lname || '-',
  maskedEmail: maskEmail(profileUser.value?.email || ''),
  maskedPhone: maskPhone(profileCustomer.value?.contact_number || profileEmployee.value?.phone || ''),
  formattedBirthday: formatDate(profileUser.value?.birthday || profileEmployee.value?.date_of_birth),
  formattedCreatedAt: formatDate(profileUser.value?.created_at),
}))
const sectionTitle = computed(() => ({
  basic: 'Basic Information',
  address: 'Address Book',
  payment: 'Payment Methods',
  notifications: 'Notifications',
  returns: 'Returns',
  cancellations: 'Cancellations',
}[activeSection.value] || 'Basic Information'))
const provinceOptions = computed(() => provinces.value.map((p: any) => ({ label: p.name, value: p.province_id })))
const cityOptions = computed(() => cities.value.map((c: any) => ({ label: c.name, value: c.city_id })))
const barangayOptions = computed(() => barangays.value.map((b: any) => ({ label: b.name, value: b.code })))
const editCityOptions = computed(() => editCities.value.map((c: any) => ({ label: c.name, value: c.city_id })))
const editBarangayOptions = computed(() => editBarangays.value.map((b: any) => ({ label: b.name, value: b.code })))

function getSectionFromUrl(): string {
  try {
    const qs = new URLSearchParams(window.location.search)
    return String(qs.get('section') || '').toLowerCase()
  } catch {
    return ''
  }
}

function maskEmail(email: string) {
  if (!email || !email.includes('@')) return '-'
  const [local, domain] = email.split('@')
  const safe = local.length <= 2 ? `${local[0] || '*'}*` : `${local.slice(0, 2)}${'*'.repeat(Math.max(2, local.length - 2))}`
  return `${safe}@${domain}`
}

function maskPhone(phone: string) {
  if (!phone) return '-'
  const clean = phone.replace(/\D/g, '')
  if (clean.length <= 4) return `${'*'.repeat(clean.length)}`
  return `${'*'.repeat(clean.length - 4)}${clean.slice(-4)}`
}

function formatDate(value?: string | null) {
  if (!value) return '-'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return '-'
  return date.toLocaleDateString('en-PH', { month: 'long', day: 'numeric', year: 'numeric' })
}

function getReturnedItems(order: any) {
  return (Array.isArray(order?.items) ? order.items : []).filter((item: any) => Boolean(item?.return_request))
}

function formatOrderStatus(value?: string | null) {
  if (!value) return 'Pending update'
  return String(value).replaceAll('_', ' ')
}

function orderStatusClass(value?: string | null) {
  const status = String(value || '').toLowerCase()
  if (['refunded', 'replaced', 'cancelled', 'canceled', 'approved'].includes(status)) {
    return 'bg-emerald-100 text-emerald-700'
  }
  if (['rejected'].includes(status)) return 'bg-rose-100 text-rose-700'
  if (status.includes('pending')) return 'bg-amber-100 text-amber-700'
  return 'bg-sky-100 text-sky-700'
}

function goOrderDetail(orderId: number) {
  router.push({ name: 'ecommerce.order-detail', params: { id: orderId } })
}

async function loadProfile() {
  const response = await axios.get('/api/profile')
  profileUser.value = response.data?.data?.user || {}
  profileEmployee.value = response.data?.data?.employee || null
  profileCustomer.value = response.data?.data?.customer || null
  editForm.fname = profileUser.value?.fname || ''
  editForm.lname = profileUser.value?.lname || ''
  editForm.birthday = profileUser.value?.birthday ? new Date(profileUser.value.birthday) : (profileEmployee.value?.date_of_birth ? new Date(profileEmployee.value.date_of_birth) : null)
}

async function loadNotifications() {
  notificationsLoading.value = true
  try {
    const response = await axiosClient.get('/api/notifications', {
      params: { per_page: 20 },
      headers: { 'X-Suppress-Dialog': '1' },
    })
    const payload = response?.data || {}
    notifications.value = payload?.data || []
  } catch {
    notifications.value = []
  } finally {
    notificationsLoading.value = false
  }
}

function selectSection(section: 'basic' | 'address' | 'payment' | 'returns' | 'cancellations') {
  activeSection.value = section
  mobileNavigationVisible.value = false
  router.replace({ name: 'ecommerce.profile', query: { section } })
}

function openNotificationsSection() {
  activeSection.value = 'notifications'
  router.replace({ name: 'ecommerce.profile', query: { section: 'notifications' } })
  loadNotifications()
}

async function markAllNotificationsRead() {
  if (!notifications.value.length) return
  try {
    await axiosClient.put('/api/notifications/mark-all-read', {}, { headers: { 'X-Suppress-Dialog': '1' } })
    notifications.value = notifications.value.map((n: any) => ({ ...n, is_read: true, read_at: new Date().toISOString() }))
  } catch {
    // no-op
  }
}

async function openNotification(notif: any) {
  try {
    if (!notif?.is_read && notif?.id) {
      await axiosClient.put(`/api/notifications/${notif.id}/read`, {}, { headers: { 'X-Suppress-Dialog': '1' } })
      notif.is_read = true
    }
  } catch {
    // no-op
  }

  if (notif?.link) {
    router.push(notif.link)
  }
}

async function loadAddressTemplates() {
  const response = await ecommerceService.getAddressTemplates()
  addressTemplates.value = response.data?.data || []
}

async function loadOrders() {
  const response = await ecommerceService.getOrders({ per_page: 50 })
  myOrders.value = response.data?.data?.data || []
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

async function fetchEditCities(provinceId: string) {
  if (!provinceId) {
    editCities.value = []
    return
  }
  if (citiesCache.value[provinceId]) {
    editCities.value = citiesCache.value[provinceId]
    return
  }
  try {
    const response = await ecommerceService.getCities(provinceId)
    citiesCache.value[provinceId] = response.data || []
    editCities.value = citiesCache.value[provinceId]
  } catch {
    editCities.value = []
  }
}

async function fetchEditBarangays(cityId: string) {
  if (!cityId) {
    editBarangays.value = []
    return
  }
  try {
    const response = await ecommerceService.getBarangays(cityId)
    editBarangays.value = response.data || []
  } catch {
    editBarangays.value = []
  }
}

async function onAddProvinceChange() {
  addAddressSelection.cityId = ''
  addAddressSelection.barangayCode = ''
  barangays.value = []
  await fetchCities(addAddressSelection.provinceId)
}

async function onAddCityChange() {
  addAddressSelection.barangayCode = ''
  await fetchBarangays(addAddressSelection.cityId)
}

async function onEditProvinceChange() {
  editAddressSelection.cityId = ''
  editAddressSelection.barangayCode = ''
  editBarangays.value = []
  await fetchEditCities(editAddressSelection.provinceId)
}

async function onEditCityChange() {
  editAddressSelection.barangayCode = ''
  await fetchEditBarangays(editAddressSelection.cityId)
}

async function saveBasicInfo() {
  savingProfile.value = true
  try {
    await axios.put('/api/profile', {
      fname: editForm.fname,
      lname: editForm.lname,
      birthday: editForm.birthday ? editForm.birthday.toISOString().slice(0, 10) : null,
    })
    editDialogVisible.value = false
    await loadProfile()
    showAlert({ severity: 'success', summary: 'Saved', detail: 'Basic information updated.' })
  } catch (error: any) {
    showAlert({ severity: 'error', summary: 'Failed', detail: error?.response?.data?.message || 'Unable to update basic information.' })
  } finally {
    savingProfile.value = false
  }
}

async function changePassword() {
  changingPassword.value = true
  try {
    await axios.post('/api/auth/change-password', passwordForm)
    passwordDialogVisible.value = false
    passwordForm.current_password = ''
    passwordForm.password = ''
    passwordForm.password_confirmation = ''
    showAlert({ severity: 'success', summary: 'Success', detail: 'Password changed successfully.' })
  } catch (error: any) {
    showAlert({ severity: 'error', summary: 'Failed', detail: error?.response?.data?.message || 'Unable to change password.' })
  } finally {
    changingPassword.value = false
  }
}

async function sendOtp() {
  sendingOtp.value = true
  try {
    await axios.post('/api/auth/resend-otp')
    showAlert({ severity: 'info', summary: 'OTP Sent', detail: 'Verification code sent to your registered email.' })
  } catch (error: any) {
    showAlert({ severity: 'error', summary: 'OTP Failed', detail: error?.response?.data?.message || 'Unable to send OTP.' })
  } finally {
    sendingOtp.value = false
  }
}

async function verifyOtpCode() {
  await axios.post('/api/auth/verify-otp', { otp: contactChangeForm.otpCode })
}

async function verifyAndChangeEmail() {
  if (!contactChangeForm.newEmail || !contactChangeForm.otpCode) return
  savingContactChange.value = true
  try {
    await verifyOtpCode()
    await axios.put('/api/profile', { email: contactChangeForm.newEmail })
    emailDialogVisible.value = false
    contactChangeForm.newEmail = ''
    contactChangeForm.otpCode = ''
    await loadProfile()
    showAlert({ severity: 'success', summary: 'Email Updated', detail: 'Please verify your new email OTP to fully activate it.' })
  } catch (error: any) {
    showAlert({ severity: 'error', summary: 'Failed', detail: error?.response?.data?.message || 'Unable to change email.' })
  } finally {
    savingContactChange.value = false
  }
}

async function changeMobile() {
  if (!contactChangeForm.newMobile.trim()) return
  savingContactChange.value = true
  try {
    await axios.put('/api/profile', { contact_number: contactChangeForm.newMobile })
    mobileDialogVisible.value = false
    contactChangeForm.newMobile = ''
    await loadProfile()
    showAlert({ severity: 'success', summary: 'Mobile Updated', detail: 'Mobile number updated successfully.' })
  } catch (error: any) {
    showAlert({ severity: 'error', summary: 'Failed', detail: error?.response?.data?.message || 'Unable to change mobile number.' })
  } finally {
    savingContactChange.value = false
  }
}

async function startEditAddress(template: AddressTemplate) {
  editingAddressId.value = template.id
  addressEditForm.full_name = template.full_name
  addressEditForm.contact_number = template.contact_number
  addressEditForm.province = template.province
  addressEditForm.city = template.city
  addressEditForm.barangay = template.barangay
  addressEditForm.address_line = template.address_line
  addressEditForm.latitude = template.latitude ?? null
  addressEditForm.longitude = template.longitude ?? null

  const province = provinces.value.find((p: any) => String(p.name).toLowerCase() === String(template.province).toLowerCase())
  editAddressSelection.provinceId = province?.province_id || ''
  editAddressSelection.cityId = ''
  editAddressSelection.barangayCode = ''
  editCities.value = []
  editBarangays.value = []

  if (editAddressSelection.provinceId) {
    await fetchEditCities(editAddressSelection.provinceId)
    const city = editCities.value.find((c: any) => String(c.name).toLowerCase() === String(template.city).toLowerCase())
    editAddressSelection.cityId = city?.city_id || ''
  }

  if (editAddressSelection.cityId) {
    await fetchEditBarangays(editAddressSelection.cityId)
    const barangay = editBarangays.value.find((b: any) => String(b.name).toLowerCase() === String(template.barangay).toLowerCase())
    editAddressSelection.barangayCode = barangay?.code || ''
  }
}

async function saveAddressEdit(id: number) {
  savingAddress.value = true
  try {
    const provinceName = provinces.value.find((p: any) => p.province_id === editAddressSelection.provinceId)?.name
    const cityName = editCities.value.find((c: any) => c.city_id === editAddressSelection.cityId)?.name
    const barangayName = editBarangays.value.find((b: any) => b.code === editAddressSelection.barangayCode)?.name

    if (provinceName) addressEditForm.province = provinceName
    if (cityName) addressEditForm.city = cityName
    if (barangayName) addressEditForm.barangay = barangayName
    await ecommerceService.updateAddressTemplate(id, { ...addressEditForm })
    editingAddressId.value = null
    await loadAddressTemplates()
    showAlert({ severity: 'success', summary: 'Saved', detail: 'Address template updated.' })
  } catch (error: any) {
    showAlert({ severity: 'error', summary: 'Failed', detail: error?.response?.data?.message || 'Unable to update address.' })
  } finally {
    savingAddress.value = false
  }
}

function openAddAddressDialog() {
  addAddressForm.full_name = ''
  addAddressForm.contact_number = ''
  addAddressForm.address_line = ''
  addAddressForm.latitude = null
  addAddressForm.longitude = null
  addAddressSelection.provinceId = ''
  addAddressSelection.cityId = ''
  addAddressSelection.barangayCode = ''
  cities.value = []
  barangays.value = []
  addAddressDialogVisible.value = true
}

async function createAddressTemplate() {
  if (!addAddressForm.full_name || !addAddressForm.contact_number || !addAddressSelection.provinceId || !addAddressSelection.cityId || !addAddressSelection.barangayCode || !addAddressForm.address_line) {
    showAlert({ severity: 'warn', summary: 'Missing fields', detail: 'Please complete all address fields.' })
    return
  }

  savingNewAddress.value = true
  try {
    const provinceName = provinces.value.find((p: any) => p.province_id === addAddressSelection.provinceId)?.name || ''
    const cityName = cities.value.find((c: any) => c.city_id === addAddressSelection.cityId)?.name || ''
    const barangayName = barangays.value.find((b: any) => b.code === addAddressSelection.barangayCode)?.name || ''

    await ecommerceService.createAddressTemplate({
      full_name: addAddressForm.full_name,
      contact_number: addAddressForm.contact_number,
      province: provinceName,
      city: cityName,
      barangay: barangayName,
      address_line: addAddressForm.address_line,
      latitude: addAddressForm.latitude ?? undefined,
      longitude: addAddressForm.longitude ?? undefined,
      is_default: addressTemplates.value.length === 0,
    })

    addAddressDialogVisible.value = false
    await loadAddressTemplates()
    showAlert({ severity: 'success', summary: 'Saved', detail: 'Address preset added.' })
  } catch (error: any) {
    showAlert({ severity: 'error', summary: 'Failed', detail: error?.response?.data?.message || 'Unable to add address preset.' })
  } finally {
    savingNewAddress.value = false
  }
}

async function logoutCustomer() {
  confirm.require({
    message: 'Are you sure you want to log out?',
    header: 'Confirm Logout',
    icon: 'pi pi-sign-out',
    rejectProps: {
      label: 'Cancel',
      severity: 'secondary',
      outlined: true,
    },
    acceptProps: {
      label: 'Log out',
      severity: 'danger',
    },
    accept: async () => {
      loggingOut.value = true
      try {
        await authStore.logout({ redirect: false })
        showAlert({ severity: 'success', summary: 'Logged out', detail: 'See you again soon!' })
        router.push({ name: 'ecommerce.products' })
      } finally {
        loggingOut.value = false
      }
    },
  })
}

onMounted(async () => {
  loading.value = true
  try {
    await Promise.all([loadProfile(), loadAddressTemplates(), loadOrders(), fetchProvinces()])
    const querySection = String(route.query?.section || '').toLowerCase() || getSectionFromUrl()
  } finally {
    loading.value = false
  }
})

watch(
  () => String(route.query?.section || '').toLowerCase() || getSectionFromUrl(),
  async (section) => {
    if (section === 'address') {
      activeSection.value = 'address'
      return
    }
    if (section === 'payment') {
      activeSection.value = 'payment'
      return
    }
    if (section === 'returns') {
      activeSection.value = 'returns'
      return
    }
    if (section === 'cancellations') {
      activeSection.value = 'cancellations'
      return
    }
    activeSection.value = 'basic'
  },
  { immediate: true },
)
</script>
