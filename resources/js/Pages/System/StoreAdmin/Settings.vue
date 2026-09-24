<template>
  <div>
    <Toast />
    <div class="space-y-3">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-semibold text-slate-900">Store Settings</h1>
      </div>
    </div>
    <div class="grid gap-4 xl:grid-cols-[1.25fr_0.75fr]">
      <Card class="border border-slate-200 shadow-sm">
        <template #title></template>
        <template #content>
          <div class="space-y-4">
            <div class="flex flex-col gap-3 border-b border-slate-100 pb-4 sm:flex-row sm:items-center sm:justify-between">
              <div>
                <div class="text-2xl font-semibold text-slate-950">{{ store.name || 'Store profile' }}</div>
                <div class="mt-1 text-sm capitalize text-slate-500">{{ store.type || 'Store type not set' }}</div>
              </div>
              <div class="flex flex-wrap items-center gap-2">
                <Badge size="large" :value="storeStatusLabel" :severity="storeStatusSeverity" />
                <Button
                  v-if="shouldShowVerifyButton"
                  :label="verifyButtonLabel"
                  :icon="verifyButtonIcon"
                  size="small"
                  :severity="isVerificationRejected ? 'danger' : 'warn'"
                  outlined
              
                  :rounded="isVerificationRejected"
                  @click="handleStoreVerifyClick"
                />
                <Button v-if="showStoreStatusInfo" icon="pi pi-info-circle" text rounded severity="secondary" @click="storeStatusDialogVisible = true" />
              </div>
            </div>

            <div class="grid gap-3 text-sm text-slate-700 sm:grid-cols-2">
              <div class="rounded-xl bg-slate-50 p-3">
                <div class="text-xs uppercase tracking-wide text-slate-400">Phone</div>
                <div class="mt-1 font-semibold text-slate-900">{{ store.phone || 'Not set' }}</div>
              </div>
              <div class="rounded-xl bg-slate-50 p-3">
                <div class="text-xs uppercase tracking-wide text-slate-400">Location</div>
                <div class="mt-1 font-semibold text-slate-900">{{ [store.city, store.province].filter(Boolean).join(', ') || 'Not set' }}</div>
              </div>
              <div class="rounded-xl bg-slate-50 p-3 sm:col-span-2">
                <div class="text-xs uppercase tracking-wide text-slate-400">Shop Address</div>
                <div class="mt-1 font-semibold capitalize text-slate-900">
                  {{ [store.address, store.barangay, store.city, store.province || 'Cavite'].filter(Boolean).join(', ') || 'No address recorded' }}
                </div>
              </div>
              <div class="rounded-xl bg-slate-50 p-3">
                <div class="text-xs uppercase tracking-wide text-slate-400">Store Code</div>
                <div class="mt-1 font-semibold text-slate-900">{{ store.store_code || 'Not set' }}</div>
              </div>
              <div class="rounded-xl bg-slate-50 p-3">
                <div class="text-xs uppercase tracking-wide text-slate-400">Billing Email</div>
                <div class="mt-1 font-semibold text-slate-900">{{ store.email || 'Not set' }}</div>
              </div>
            </div>
          </div>
          <Button class="mt-5 w-full" label="Edit store profile" size="small" icon="pi pi-pencil" outlined @click="openProfileEditor" />
        </template>
      </Card>

      <!-- Online Payment methods moved to Upgrade flow; card removed -->

      <Card class="border border-slate-200 shadow-sm">
        <template #title>Logo</template>
        <template #content>
          <div class="space-y-4">
            <div class="mx-auto w-full max-w-xs">
              <div class="relative aspect-square overflow-hidden rounded-3xl border border-slate-200 bg-slate-50 shadow-sm">
                <img v-if="store.logo_url" :src="store.logo_url" :alt="`${store.name || 'Store'} logo`" class="h-full w-full object-contain p-3" />
                <div v-else class="flex h-full w-full items-center justify-center bg-orange-50 text-orange-500">
                  <i class="pi pi-image text-4xl"></i>
                </div>
                <Button
                  label="Upload logo"
                  icon="pi pi-upload"
                  size="small"
                  severity="warn"
                  class="!absolute bottom-3 left-1/2 -translate-x-1/2 shadow-md"
                  @click="openLogoDialog"
                />
              </div>
            </div>

            <div class="rounded-md bg-slate-50 px-2.5 py-1.5 text-center">
              <div class="text-[10px] uppercase tracking-wide text-slate-400">Saved logo size</div>
              <div class="text-xs font-semibold text-slate-900">{{ logoDimensionLabel }}</div>
              <p class="mt-0.5 text-[10px] leading-3 text-slate-500">
                Shown on shop pages. Upload at least 512 x 512 px, ideally 1024 x 1024 px.
              </p>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <Dialog v-model:visible="planDialogVisible" modal header="Available Plans" :style="{ width: '36rem' }">
      <div class="space-y-3">
        <button
          v-for="plan in availablePlans"
          :key="plan.key"
          type="button"
          class="w-full rounded-lg border p-3 text-left transition"
          :class="selectedPlan.key === plan.key ? 'border-blue-500 bg-blue-50' : 'border-slate-200 hover:border-slate-300'"
          @click="selectPlan(plan.key)"
        >
          <div class="flex items-center justify-between">
            <div class="font-semibold text-slate-900">{{ plan.label }}</div>
            <div class="text-sm font-semibold text-slate-700">PHP {{ plan.amountPhp.toLocaleString() }}</div>
          </div>
          <p class="mt-1 text-xs text-slate-600">{{ plan.description }}</p>
        </button>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" text @click="planDialogVisible = false" />
        <Button label="Continue" @click="openPaymentMethodDialogForSelectedPlan" />
      </template>
    </Dialog>

    <Dialog v-model:visible="gcashDialogVisible" modal header="Payment Details" :style="{ width: '28rem' }">
      <div class="space-y-4">
        <div>
          <label class="text-sm font-medium text-slate-700 block mb-1">Payment Method</label>
          <Select v-model="selectedWalletType" :options="walletTypeOptions" optionLabel="label" optionValue="value" class="w-full" />
          <p class="mt-1 text-xs text-slate-500">This selection affects the wallet you will be redirected to.</p>
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700 block mb-1">Account Name</label>
          <InputText v-model="gcashForm.name" class="w-full" placeholder="Juan Dela Cruz" />
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700 block mb-1">Mobile Number</label>
          <InputMask v-model="gcashForm.phone" class="w-full" mask="0999 999 9999" placeholder="09__ ___ ____" />
          <p class="mt-1 text-xs text-slate-500">Use your active Philippine mobile number linked to the selected wallet.</p>
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" text @click="gcashDialogVisible = false" />
        <Button :label="`Continue to ${selectedWalletLabel}`" :loading="upgrading" @click="submitUpgradeCheckout" />
      </template>
    </Dialog>

    <Dialog v-model:visible="paymentMethodDialogVisible" modal header="Choose Payment Method" :style="{ width: '28rem' }">
      <div class="space-y-3">
        <p class="text-sm text-slate-600">Select which payment method you'd like to use for this upgrade.</p>
        <div class="grid gap-3">
          <label class="flex items-center gap-3">
            <input type="radio" v-model="selectedPaymentMethod" value="card" />
            <span>Credit / Debit Card</span>
          </label>
          <label class="flex items-center gap-3">
            <input type="radio" v-model="selectedPaymentMethod" value="gcash" />
            <span>GCash / E-Wallet</span>
          </label>
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" text @click="paymentMethodDialogVisible = false" />
        <Button :disabled="!selectedPaymentMethod" @click="continueToPaymentCredentials" />
      </template>
    </Dialog>

    <Dialog v-model:visible="storeStatusDialogVisible" modal header="Store Status Details" :style="{ width: '34rem' }">
      <div class="space-y-3 text-sm">
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
          <div class="font-semibold text-slate-900">{{ store.name || 'Store' }}</div>
          <div class="text-xs text-slate-500">{{ store.email || '-' }}</div>
        </div>
        <div class="flex items-center justify-between">
          <span class="text-slate-600">Current Status</span>
          <Tag :value="storeStatusLabel" :severity="storeStatusSeverity" />
        </div>
        <div v-if="store.status === 'suspended'" class="flex items-center justify-between">
          <span class="text-slate-600">Suspension Remaining</span>
          <span class="font-semibold text-amber-600">{{ formatRemainingDays(store.status_details?.suspension_days_remaining) }}</span>
        </div>
        <div v-if="store.status_details?.actioned_at" class="flex items-center justify-between">
          <span class="text-slate-600">Action Date</span>
          <span class="font-semibold text-slate-800">{{ formatDateTime(store.status_details.actioned_at) }}</span>
        </div>
        <div>
          <div class="text-slate-600 mb-1">Reason</div>
          <div class="rounded-lg border border-slate-200 p-3 text-slate-800 whitespace-pre-wrap">
            {{ store.status_details?.action_reason || 'No reason was recorded.' }}
          </div>
        </div>
      </div>
      <template #footer>
        <Button label="Close" text @click="storeStatusDialogVisible = false" />
      </template>
    </Dialog>

  

    <Dialog v-model:visible="verificationDocumentsDialogVisible" modal :header="verificationDocumentsDialogTitle" :style="{ width: '48rem' }">
      <div class="space-y-3">
        <div v-if="isVerificationRejected" class="rounded-xl border border-red-100 bg-red-50 p-3 text-sm text-red-800">
          <div class="font-semibold">Rejected Reason</div>
          <div class="mt-1 whitespace-pre-wrap">{{ verification.rejection_reason || 'No reason was provided.' }}</div>
        </div>
        <div class="text-xs text-slate-500">
          {{ isVerificationRejected ? 'Review the previous attachments before resubmitting.' : 'Review submitted attachments while your verification is under review.' }}
        </div>

        <DataTable :value="verificationDocuments" responsiveLayout="scroll" class="text-sm" stripedRows>
          <Column field="label" header="Document"></Column>
          <Column header="Status">
            <template #body="slotProps">
              <Tag
                :value="slotProps.data.submitted ? (slotProps.data.is_valid ? 'Submitted' : 'Needs Review') : 'Missing'"
                :severity="slotProps.data.submitted ? (slotProps.data.is_valid ? 'success' : 'warn') : 'secondary'"
              />
            </template>
          </Column>
          <Column field="size_kb" header="Size (KB)">
            <template #body="slotProps">
              <span>{{ slotProps.data.size_kb ?? '—' }}</span>
            </template>
          </Column>
          <Column header="Action">
            <template #body="slotProps">
              <Button
                v-if="slotProps.data.path || slotProps.data.download_url || slotProps.data.inspect_url"
                label="View"
                icon="pi pi-external-link"
                size="small"
                outlined
                @click="openDocumentDialog(slotProps.data)"
              />
              <span v-else class="text-slate-400">—</span>
            </template>
          </Column>
        </DataTable>

        <div v-if="verificationDocuments.length === 0" class="text-sm text-slate-500">
          No verification attachments found.
        </div>
      </div>
      <template #footer>
        <Button label="Close" severity="secondary" outlined @click="verificationDocumentsDialogVisible = false" />
        <Button v-if="isVerificationRejected" label="Resubmit" severity="warn" icon="pi pi-refresh" @click="resubmitVerification" />
      </template>
    </Dialog>

    <Dialog v-model:visible="documentPreviewDialogVisible" modal :header="documentPreviewTitle" :style="{ width: 'min(92vw, 56rem)' }">
      <div class="min-h-[22rem] overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
        <img
          v-if="documentPreviewKind === 'image'"
          :src="documentPreviewUrl"
          :alt="documentPreviewTitle"
          class="max-h-[70vh] w-full object-contain"
        />
        <iframe
          v-else
          :src="documentPreviewUrl"
          class="h-[70vh] w-full border-0"
          title="Verification document preview"
        ></iframe>
      </div>
      <template #footer>
        <Button label="Close" severity="secondary" outlined @click="documentPreviewDialogVisible = false" />
      </template>
    </Dialog>


    <Dialog v-model:visible="profileEditorVisible" modal header="Edit Store Profile" :style="{ width: '36rem' }">
      <div class="space-y-4">
        <div class="grid gap-4 sm:grid-cols-2">
          <div class="sm:col-span-2"><label class="mb-1 block text-sm font-medium cap">Store Name</label><InputText :modelValue="store.name" class="capitalize w-full bg-slate-100" disabled /></div>
          <div><label class="mb-1 block text-sm font-medium">Contact Person</label><InputText v-model="profileForm.contact_person" class="w-full" disabled/></div>
          <div><label class="mb-1 block text-sm font-medium">Phone</label><InputMask v-model="profileForm.phone" class="w-full" mask="0999 999 9999" placeholder="09__ ___ ____" /></div>
          <div class="sm:col-span-2"><label class="mb-1 block text-sm font-medium">Address</label><Textarea v-model="profileForm.address" rows="3" class="w-full" /></div>
          <div><label class="mb-1 block text-sm font-medium">City</label><Select v-model="profileForm.city_id" :options="profileCityOptions" optionLabel="label" optionValue="value" class="w-full" filter :loading="profileCitiesLoading" :disabled="profileCitiesLoading" placeholder="Select city" @change="onProfileCityChange" /></div>
          <div><label class="mb-1 block text-sm font-medium">Province</label><InputText v-model="profileForm.province" class="w-full" readonly /></div>
          <div><label class="mb-1 block text-sm font-medium">Barangay</label><Select v-model="profileForm.barangay" :options="profileBarangayOptions" optionLabel="label" optionValue="value" class="w-full" filter :loading="profileBarangaysLoading" :disabled="!profileForm.city_id || profileBarangaysLoading" placeholder="Select barangay" /></div>
          <div><label class="mb-1 block text-sm font-medium">Store Type</label><Select v-model="profileForm.type" :options="businessTypeOptions" optionLabel="label" optionValue="value" class="w-full" /></div>
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" text @click="profileEditorVisible = false" />
        <Button label="Save" :loading="savingProfile" @click="saveStoreProfile" />
      </template>
    </Dialog>

    <Dialog v-model:visible="profileSuccessVisible" modal header="Profile Updated" :style="{ width: '28rem' }">
      <div class="py-3 text-center"><i class="pi pi-check-circle text-5xl text-green-500"></i><p class="mt-3 text-slate-700">Your store profile was updated successfully.</p></div>
      <template #footer><Button label="Close" @click="profileSuccessVisible = false" /></template>
    </Dialog>

    <Dialog v-model:visible="logoDialogVisible" modal header="Shop Logo" :style="{ width: '34rem' }">
      <div class="space-y-4">
        <div class="rounded-xl border border-orange-100 bg-orange-50 p-4 text-sm text-slate-700">
          Upload any JPG, PNG, or WebP logo. We will center-crop it into a square before saving so it looks clean on the shop page.
        </div>

        <div class="grid gap-4 sm:grid-cols-[10rem_1fr]">
          <div class="aspect-square overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
            <img v-if="logoPreviewUrl" :src="logoPreviewUrl" alt="Logo preview" class="h-full w-full object-cover" />
            <div v-else class="flex h-full w-full items-center justify-center text-slate-400">
              <i class="pi pi-image text-3xl"></i>
            </div>
          </div>
          <div class="space-y-3">
            <label class="block">
              <span class="mb-1 block text-sm font-medium text-slate-700">Upload logo</span>
              <input type="file" accept="image/png,image/jpeg,image/webp" class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-orange-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-orange-700 hover:file:bg-orange-200" @change="handleLogoFileChange" />
            </label>
            <div class="rounded-lg border border-slate-200 p-3 text-xs text-slate-600">
              <div><b>Original:</b> {{ logoOriginalDimensionLabel }}</div>
              <div><b>Saved:</b> 512 x 512 px square</div>
              <div class="mt-2 text-slate-500">Best upload: at least 512 x 512 px, ideally 1024 x 1024 px for sharper displays.</div>
            </div>
          </div>
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" text @click="closeLogoDialog" />
        <Button label="Save Logo" icon="pi pi-check" severity="warn" :disabled="!logoSquareBlob" :loading="savingLogo" @click="saveLogo" />
      </template>
    </Dialog>

    <Card>
      <template #title>Attendance Geolocation</template>
      <template #content>
        <p class="text-sm text-slate-600 mb-4">
          Set the main branch location for attendance login/clock-in. Employees must be within the radius to log in.
        </p>
        <div class="grid gap-4 lg:grid-cols-[1fr_0.8fr]">
          <div class="space-y-3 text-sm text-slate-700">
            <div>
              <div class="text-xs uppercase text-slate-400">Address</div>
              <div class="font-semibold">{{ attendance.address || 'Not set' }}</div>
            </div>
            <div class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2">
              <div>
                <div class="text-xs uppercase text-slate-400">Geofence</div>
                <div class="text-sm font-semibold">{{ attendance.geofence_enabled ? 'Enabled' : 'Disabled' }}</div>
              </div>
              <Tag :value="attendance.geofence_enabled ? 'Enabled' : 'Disabled'" :severity="attendance.geofence_enabled ? 'success' : 'secondary'" />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <div class="text-xs uppercase text-slate-400">Barangay</div>
                <div class="font-semibold">{{ attendance.barangay || 'Not set' }}</div>
              </div>
              <div>
                <div class="text-xs uppercase text-slate-400">City</div>
                <div class="font-semibold">{{ attendance.city || 'Not set' }}</div>
              </div>
              <div>
                <div class="text-xs uppercase text-slate-400">Province</div>
                <div class="font-semibold">{{ attendance.province || 'Not set' }}</div>
              </div>
              <div>
                <div class="text-xs uppercase text-slate-400">Radius (meters)</div>
                <div class="font-semibold">{{ attendance.geofence_radius_m }} meters</div>
              </div>
            </div>
           
          </div>
          <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
            <div class="text-xs uppercase text-slate-400">Attendance location</div>
            <div class="mt-1 font-semibold text-slate-900">Update your attendance area</div>
            <p class="mt-2 text-xs leading-5 text-slate-500">
              Set the address, geofence, and radius used for employee attendance.
            </p>
            <Button class="mt-4 w-full" label="Edit Attendance Location" icon="pi pi-map-marker" outlined @click="openAttendanceEditor" />
          </div>
        </div>
      </template>
    </Card>

    <Dialog v-model:visible="attendanceEditorVisible" modal header="Edit Attendance Geolocation" :style="{ width: '52rem' }" @show="handleAttendanceDialogShow">
      <div class="grid gap-5 lg:grid-cols-[1.1fr_0.9fr]">
        <div>
          <div class="mb-3 flex flex-wrap items-center gap-2">
            <InputText v-model="searchQuery" class="flex-1 min-w-[220px]" placeholder="Search address..." />
            <Button label="Search" icon="pi pi-search" severity="secondary" @click="searchAddress" />
          </div>
          <div ref="mapEl" class="h-72 w-full rounded-xl border border-slate-200"></div>
          <p v-if="mapError" class="mt-2 text-xs text-red-600">{{ mapError }}</p>
          <p class="mt-2 text-xs text-slate-500">Drag the pin or click the map to adjust the exact attendance point.</p>
        </div>

        <div class="space-y-4">
          <div class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2">
            <div>
              <div class="text-xs uppercase text-slate-400">Geofence</div>
              <div class="text-sm font-semibold">{{ attendanceDraft.geofence_enabled ? 'Enabled' : 'Disabled' }}</div>
            </div>
            <InputSwitch v-model="attendanceDraft.geofence_enabled" />
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Address</label>
            <Textarea v-model="attendanceDraft.address" rows="4" class="w-full" placeholder="Attendance address" />
          </div>

          <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">City</label>
              <Select v-model="attendanceDraft.city_id" :options="attendanceCityOptions" optionLabel="label" optionValue="value" class="w-full" filter :loading="attendanceCitiesLoading" :disabled="attendanceCitiesLoading" placeholder="Select city" @change="onAttendanceCityChange" />
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Province</label>
              <InputText v-model="attendanceDraft.province" class="w-full" readonly />
            </div>
            <div class="sm:col-span-2">
              <label class="mb-1 block text-sm font-medium text-slate-700">Barangay</label>
              <Select v-model="attendanceDraft.barangay" :options="attendanceBarangayOptions" optionLabel="label" optionValue="value" class="w-full" filter :loading="attendanceBarangaysLoading" :disabled="!attendanceDraft.city_id || attendanceBarangaysLoading" placeholder="Select barangay" />
            </div>
          </div>

          <div>
            <div class="mb-2 flex items-center justify-between">
              <label class="block text-sm font-medium text-slate-700">Allowed Radius</label>
              <span class="text-sm font-semibold text-slate-900">{{ attendanceDraft.geofence_radius_m }} meters</span>
            </div>
            <Slider v-model="attendanceDraft.geofence_radius_m" :min="0" :max="100" :step="1" class="w-full" />
          </div>

          <div class="grid grid-cols-2 gap-3 text-sm">
            <div>
              <div class="text-xs uppercase text-slate-400">Barangay</div>
              <div class="font-semibold">{{ attendanceDraft.barangay || 'Not set' }}</div>
            </div>
            <div>
              <div class="text-xs uppercase text-slate-400">City</div>
              <div class="font-semibold">{{ attendanceDraft.city || 'Not set' }}</div>
            </div>
            <div>
              <div class="text-xs uppercase text-slate-400">Province</div>
              <div class="font-semibold">{{ attendanceDraft.province || 'Not set' }}</div>
            </div>
            <div>
              <div class="text-xs uppercase text-slate-400">Coordinates</div>
              <div class="font-semibold">{{ attendanceDraft.latitude ?? '—' }}, {{ attendanceDraft.longitude ?? '—' }}</div>
            </div>
          </div>
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" text @click="attendanceEditorVisible = false" />
        <Button label="Save Attendance Location" icon="pi pi-check" :loading="savingAttendance" @click="saveAttendance" />
      </template>
    </Dialog>

    <Dialog v-model:visible="attendanceSuccessVisible" modal header="Attendance Updated" :style="{ width: '28rem' }">
      <div class="py-3 text-center"><i class="pi pi-check-circle text-5xl text-green-500"></i><p class="mt-3 text-slate-700">Attendance geolocation settings were updated successfully.</p></div>
      <template #footer><Button label="Close" @click="attendanceSuccessVisible = false" /></template>
    </Dialog>


    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, reactive, ref, watch } from 'vue'
import axiosClient from '@/axios'
import { router } from '@inertiajs/vue3'
import Button from 'primevue/button'
import Badge from 'primevue/badge'
import Card from 'primevue/card'
import Dialog from 'primevue/dialog'
import MultiSelect from 'primevue/multiselect'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import InputMask from 'primevue/inputmask'
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'
import InputSwitch from 'primevue/inputswitch'
import Slider from 'primevue/slider'
import Tag from 'primevue/tag'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Toast from 'primevue/toast'
import { useToast } from 'primevue/usetoast'
import { usePermissions } from '@/composables/usePermissions'
import paymongoService from '@/services/paymongo.service'
import ecommerceService from '@/services/ecommerce.service'
import { forwardGeocodeMapbox, requireMapboxToken } from '@/utils/mapbox'
import type { GeoJSONSource, Map as MapboxMap, Marker as MapboxMarker } from 'mapbox-gl'
import 'mapbox-gl/dist/mapbox-gl.css'

type UpgradePlan = {
  key: string
  label: string
  amountPhp: number
  months: number
  tier: string
  description: string
  isFeatured?: boolean
}

const props = defineProps<{
  store: any
  payments: any
  branches: any[]
  attendance: any
  subscription: any
  available_plans: any[]
  verification: any
  onboarding: any
}>()

const savingAttendance = ref(false)
const savingProfile = ref(false)
const savingLogo = ref(false)
const profileEditorVisible = ref(false)
const profileSuccessVisible = ref(false)
const logoDialogVisible = ref(false)
const attendanceEditorVisible = ref(false)
const attendanceSuccessVisible = ref(false)
const upgrading = ref(false)
const planDialogVisible = ref(false)
const gcashDialogVisible = ref(false)
const paymentMethodDialogVisible = ref(false)
const selectedPaymentMethod = ref<'card' | 'gcash' | ''>('')
const savingPayments = ref(false)
const paymongoMethodOptions = [
  { label: 'Credit/Debit Card', value: 'card' },
  { label: 'GCash', value: 'gcash' },
  { label: 'GrabPay', value: 'grab_pay' },
  { label: 'PayMaya', value: 'paymaya' },
]
const paymongoPaymentMethods = ref<string[]>(['gcash'])
const selectedWalletType = ref<'card' | 'gcash' | 'grab_pay' | 'paymaya'>('gcash')
const walletTypeOptions = computed(() => {
  const allowed = Array.isArray(paymongoPaymentMethods.value) ? paymongoPaymentMethods.value : ['gcash']
  const map: Record<string, string> = { card: 'Card', gcash: 'GCash', grab_pay: 'GrabPay', paymaya: 'PayMaya' }
  return allowed
    .filter((v) => ['card', 'gcash', 'grab_pay', 'paymaya'].includes(String(v)))
    .map((v) => ({ label: map[String(v)] || String(v), value: v as any }))
})
const selectedWalletLabel = computed(() => {
  const opt = walletTypeOptions.value.find((o) => o.value === selectedWalletType.value)
  return opt?.label || 'Wallet'
})
const storeStatusDialogVisible = ref(false)
const loading = ref(false)
const mapEl = ref<HTMLElement | null>(null)
const mapReady = ref(false)
const mapRef = ref<MapboxMap | null>(null)
const markerRef = ref<MapboxMarker | null>(null)
let mapboxgl: typeof import('mapbox-gl').default | null = null
const searchQuery = ref('')
const mapError = ref('')
const toast = useToast()

const fallbackPlans: UpgradePlan[] = [
  {
    key: 'simple',
    label: 'Simple',
    amountPhp: 1490,
    months: 1,
    tier: 'simple',
    description: 'For single stores and single locations.',
  },
  {
    key: 'unlimited',
    label: 'Unlimited',
    amountPhp: 3500,
    months: 1,
    tier: 'unlimited',
    description: 'For multi-store operations and fast growth.',
    isFeatured: true,
  },
]

const availablePlans = ref<UpgradePlan[]>(
  Array.isArray(props.available_plans) && props.available_plans.length
    ? props.available_plans.map((plan: any) => ({
        key: String(plan?.key || ''),
        label: String(plan?.label || ''),
        amountPhp: Number(plan?.amount_php ?? plan?.amountPhp ?? 0),
        months: Number(plan?.months ?? 1),
        tier: String(plan?.tier || plan?.key || ''),
        description: String(plan?.description || ''),
        isFeatured: Boolean(plan?.is_featured ?? plan?.isFeatured),
      }))
    : [...fallbackPlans]
)

const store = reactive({
  name: '',
  contact_person: '',
  email: '',
  phone: '',
  address: '',
  city: '',
  barangay: '',
  province: '',
  type: '',
  store_code: '',
  status: '',
  status_details: null as any,
  logo_url: '',
  logo_dimensions: null as any,
})

const logoPreviewUrl = ref('')
const logoSquareBlob = ref<Blob | null>(null)
const logoOriginalDimensions = reactive({
  width: 0,
  height: 0,
})

const attendance = reactive({
  branch_id: null as number | null,
  address: '',
  barangay: '',
  city: '',
  province: '',
  latitude: null as number | null,
  longitude: null as number | null,
  geofence_radius_m: 5,
  geofence_enabled: false,
})

const attendanceDraft = reactive({
  branch_id: null as number | null,
  address: '',
  barangay: '',
  city: '',
  city_id: '',
  province: '',
  latitude: null as number | null,
  longitude: null as number | null,
  geofence_radius_m: 5,
  geofence_enabled: false,
})

const subscription = reactive({
  store_id: null as number | null,
  tier: 'free',
  plan_label: 'Free',
  ends_at: '',
  modules: [] as { key: string; name: string }[],
})

const profileForm = reactive({
  contact_person: '',
  phone: '',
  address: '',
  city: '',
  city_id: '',
  barangay: '',
  province: '',
  type: '',
})
const businessTypeOptions = [
  { label: 'Retail', value: 'retail' },
  { label: 'Enterprise', value: 'enterprise' },
  { label: 'Showroom', value: 'showroom' },
  { label: 'Wholesale', value: 'wholesale' },
]
const profileCities = ref<any[]>([])
const profileBarangays = ref<any[]>([])
const profileCitiesLoading = ref(false)
const profileBarangaysLoading = ref(false)
const profileCityOptions = computed(() => profileCities.value.map((city: any) => ({ label: city.name || city.city_name || 'City', value: String(city.city_id || city.id || city.code || '') })))
const profileBarangayOptions = computed(() => profileBarangays.value.map((item: any) => ({ label: item.name || item.barangay_name || 'Barangay', value: String(item.code || item.psgc_id || item.id || item.name || '') })))
const attendanceCities = ref<any[]>([])
const attendanceBarangays = ref<any[]>([])
const attendanceCitiesLoading = ref(false)
const attendanceBarangaysLoading = ref(false)
const attendanceCityOptions = computed(() => attendanceCities.value.map((city: any) => ({
  label: city.name || city.city_name || 'City',
  value: String(city.city_id || city.id || city.code || ''),
})))
const attendanceBarangayOptions = computed(() => {
  const options = attendanceBarangays.value.map((item: any) => ({
    label: item.name || item.barangay_name || 'Barangay',
    value: item.name || item.barangay_name || '',
  }))
  if (attendanceDraft.barangay && !options.some((option) => option.value.toLowerCase() === attendanceDraft.barangay.toLowerCase())) {
    options.unshift({ label: attendanceDraft.barangay, value: attendanceDraft.barangay })
  }
  return options
})

const verification = reactive({
  store_status: 'pending',
  submitted_at: '' as string | null,
  reviewed_at: '' as string | null,
  rejection_reason: '' as string | null,
  documents_submitted: false,
})

const verificationDocumentsDialogVisible = ref(false)
const verificationDocuments = ref<any[]>([])
const loadingVerificationDocuments = ref(false)
const documentPreviewDialogVisible = ref(false)
const documentPreviewUrl = ref('')
const documentPreviewTitle = ref('Verification Document')
const documentPreviewKind = ref<'image' | 'pdf'>('pdf')
const apiBaseUrl = String(axiosClient.defaults.baseURL || '').replace(/\/api\/?$/, '')

const branches = ref<any[]>(Array.isArray(props.branches) ? props.branches : [])

const onboarding = reactive({
  plan: 'simple',
})

const gcashForm = reactive({
  name: '',
  phone: '',
})

const selectedPlan = reactive({
  key: fallbackPlans[0].key,
  amountPhp: fallbackPlans[0].amountPhp,
  months: fallbackPlans[0].months,
  tier: fallbackPlans[0].tier,
  label: fallbackPlans[0].label,
})

usePermissions()

// Initialize state from Inertia props (classic Inertia pattern: server provides the page data).
store.name = props.store?.name || ''
store.contact_person = props.store?.contact_person || ''
store.email = props.store?.email || ''
store.phone = props.store?.phone || ''
store.address = props.store?.address || ''
store.city = props.store?.city || ''
store.barangay = props.store?.barangay || ''
store.province = props.store?.province || ''
store.type = props.store?.type || ''
store.store_code = props.store?.store_code || ''
store.status = props.store?.status || ''
store.status_details = props.store?.status_details || null
store.logo_url = props.store?.logo_url || ''
store.logo_dimensions = props.store?.logo_dimensions || null

Object.assign(profileForm, {
  contact_person: store.contact_person,
  phone: store.phone,
  address: store.address,
  city: store.city,
  barangay: store.barangay,
  province: store.province,
  type: store.type,
})

subscription.store_id = props.store?.id ?? null
subscription.tier = props.subscription?.tier || 'free'
subscription.plan_label = props.subscription?.plan_label || 'Free'
subscription.ends_at = props.subscription?.ends_at || ''
subscription.modules = Array.isArray(props.subscription?.modules) ? props.subscription.modules : []

verification.store_status = props.verification?.store_status || 'pending'
verification.submitted_at = props.verification?.submitted_at || null
verification.reviewed_at = props.verification?.reviewed_at || null
verification.rejection_reason = props.verification?.rejection_reason || null
verification.documents_submitted = Boolean(props.verification?.documents_submitted)

attendance.branch_id = props.attendance?.branch_id ?? null
attendance.address = props.attendance?.address || ''
attendance.barangay = props.attendance?.barangay || ''
attendance.city = props.attendance?.city || ''
attendance.province = props.attendance?.province || ''
attendance.latitude = props.attendance?.latitude ?? null
attendance.longitude = props.attendance?.longitude ?? null
attendance.geofence_radius_m = props.attendance?.geofence_radius_m ?? 5
attendance.geofence_enabled = props.attendance?.geofence_enabled ?? false

const allowedMethods = props.payments?.paymongo?.payment_method_allowed
paymongoPaymentMethods.value = Array.isArray(allowedMethods) && allowedMethods.length ? allowedMethods : ['gcash']

const isActiveSubscription = computed(() => Boolean(subscription.ends_at))
const canShowVerificationCard = computed(() => {
  const tier = String(subscription.tier || '').toLowerCase()
  return tier === 'unlimited' || tier === 'simple'
})
const currentPlanLabel = computed(() => {
  const planLabel = String(subscription.plan_label || '').trim()
  if (planLabel) return planLabel
  return String(subscription.tier || (isActiveSubscription.value ? 'paid' : 'free')).toUpperCase()
})

const storeStatusLabel = computed(() => {
  const raw = String(store.status || 'unknown').toLowerCase()
  if (raw === 'pending') return 'Unverified'
  if (raw === 'unverified') return 'Unverified'
  if (raw === 'deactivated') return 'Deactivated'
  return raw.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
})
const isStoreUnverified = computed(() => String(store.status || '').toLowerCase() === 'unverified')
const isStorePendingVerification = computed(() => String(store.status || '').toLowerCase() === 'pending')
const isVerificationRejected = computed(() => verification.store_status === 'rejected')
const shouldShowVerifyButton = computed(() => isStoreUnverified.value && !isStorePendingVerification.value)
const verifyButtonLabel = computed(() => isVerificationRejected.value ? '' : 'Verify')
const verifyButtonIcon = computed(() => isVerificationRejected.value ? 'pi pi-exclamation-circle' : '')
const verificationDocumentsDialogTitle = computed(() => isVerificationRejected.value ? 'Verification Rejected' : 'Submitted Verification Documents')

const storeStatusSeverity = computed(() => {
  const status = String(store.status || '').toLowerCase()
  if (status === 'active' || status === 'approved' || status === 'verified') return 'success'
  if (status === 'pending' || status === 'unverified') return 'warning'
  if (status === 'suspended' || status === 'banned' || status === 'deactivated') return 'danger'
  return 'secondary'
})

const showStoreStatusInfo = computed(() => {
  const status = String(store.status || '').toLowerCase()
  return status === 'suspended' || status === 'banned'
})

const formatRemainingDays = (value: number) => {
  if (!Number.isFinite(value)) return 'Not available'
  if (value <= 0) return 'Suspension period ended'
  return `${Math.round(value)} day(s) remaining`
}


const logoDimensionLabel = computed(() => {
  const dimensions = store.logo_dimensions || {}
  const width = Number(dimensions.width || 0)
  const height = Number(dimensions.height || 0)
  if (!width || !height) return 'No logo yet'
  return `${width} x ${height} px`
})

const logoOriginalDimensionLabel = computed(() => {
  if (logoOriginalDimensions.width && logoOriginalDimensions.height) {
    return `${logoOriginalDimensions.width} x ${logoOriginalDimensions.height} px`
  }

  const dimensions = store.logo_dimensions || {}
  const width = Number(dimensions.original_width || dimensions.width || 0)
  const height = Number(dimensions.original_height || dimensions.height || 0)
  if (!width || !height) return 'Select an image to detect pixels'
  return `${width} x ${height} px`
})

const approvalMatrixRows = [
  {
    source_module: 'Procurement',
    action: 'Purchase Order approval flow',
    approver_module: 'Finance',
    approver_action: 'Approve PO (final approval)',
    notes: 'PO can move to approved/sent only after finance approval step.',
  },
  {
    source_module: 'HR Payroll',
    action: 'Payroll processing and release',
    approver_module: 'Finance',
    approver_action: 'Finance approve / mark paid',
    notes: 'Payroll must be finance-approved before payment/release.',
  },
  {
    source_module: 'Merchandising',
    action: 'Price change request',
    approver_module: 'Finance',
    approver_action: 'Approve/reject pending price',
    notes: 'Live price stays unchanged until finance decision.',
  },
  {
    source_module: 'Procurement',
    action: 'Supplier payment above threshold',
    approver_module: 'Finance',
    approver_action: 'Expense approval workflow',
    notes: 'High-value payments trigger finance expense approval.',
  },
  {
    source_module: 'Inventory',
    action: 'High-impact stock adjustment',
    approver_module: 'Finance',
    approver_action: 'Adjustment approval',
    notes: 'Certain adjustments require finance-level approval.',
  },
]

const approvalSourceCount = computed(() => new Set(approvalMatrixRows.map(row => row.source_module)).size)
const approvalApproverCount = computed(() => new Set(approvalMatrixRows.map(row => row.approver_module)).size)

const verificationStatusLabel = computed(() => {
  if (verification.store_status === 'approved') return 'Approved'
  if (verification.store_status === 'reviewing') return 'Under Review'
  if (verification.store_status === 'rejected') return 'Rejected'
  return 'Unverified'
})

const verificationSeverity = computed(() => {
  if (verification.store_status === 'approved') return 'success'
  if (verification.store_status === 'reviewing') return 'info'
  if (verification.store_status === 'rejected') return 'danger'
  return 'warn'
})

const shouldShowVerificationAttachments = computed(() =>
  ['reviewing', 'approved', 'rejected'].includes(verification.store_status)
)

const verificationActionLabel = computed(() =>
  shouldShowVerificationAttachments.value ? 'View Submitted Attachments' : 'Open Verification Page'
)

const formatDateTime = (value?: string | null) => {
  if (!value) return '—'
  const parsed = new Date(value)
  if (Number.isNaN(parsed.getTime())) return value
  return new Intl.DateTimeFormat('en-PH', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  }).format(parsed)
}

const savePaymentSettings = async () => {
  if (paymongoPaymentMethods.value.length === 0) {
    toast.add({ severity: 'warn', summary: 'Select a method', detail: 'Choose at least one payment method.', life: 3000 })
    return
  }
  savingPayments.value = true
  try {
    router.put(
      '/store/settings/payments',
      { paymongo_payment_methods: paymongoPaymentMethods.value },
      { preserveScroll: true }
    )
    toast.add({ severity: 'success', summary: 'Saved', detail: 'Payment methods updated.', life: 2500 })
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Save failed',
      detail: error?.response?.data?.message || 'Unable to update payment settings.',
      life: 3500,
    })
  } finally {
    savingPayments.value = false
  }
}

const saveAttendance = async () => {
  if (!attendanceDraft.city || !attendanceDraft.barangay) {
    toast.add({ severity: 'warn', summary: 'Address required', detail: 'Select the city and barangay for the attendance location.', life: 3000 })
    return
  }
  if (attendanceDraft.latitude === null || attendanceDraft.longitude === null) {
    toast.add({ severity: 'warn', summary: 'Location required', detail: 'Search, click, or drag the pin to set a valid attendance point.', life: 3000 })
    return
  }
  savingAttendance.value = true
  router.put(
    '/store/settings/attendance',
    {
      branch_id: attendanceDraft.branch_id,
      address: attendanceDraft.address || null,
      barangay: attendanceDraft.barangay || null,
      city: attendanceDraft.city || null,
      province: attendanceDraft.province || null,
      latitude: attendanceDraft.latitude,
      longitude: attendanceDraft.longitude,
      geofence_radius_m: attendanceDraft.geofence_radius_m || 5,
      geofence_enabled: attendanceDraft.geofence_enabled,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        attendanceEditorVisible.value = false
        attendanceSuccessVisible.value = true
      },
      onError: (errors) => {
        toast.add({
          severity: 'error',
          summary: 'Unable to save',
          detail: errors?.attendance || 'Please check the attendance location details.',
          life: 3500,
        })
      },
      onFinish: () => { savingAttendance.value = false },
    }
  )
}

const loadProfileCities = async () => {
  profileCitiesLoading.value = true
  try {
    const provinces = await ecommerceService.getProvinces()
    const items = Array.isArray(provinces.data?.data) ? provinces.data.data : (provinces.data || [])
    const cavite = items.find((item: any) => String(item.name).trim().toLowerCase() === 'cavite')
    profileForm.province = cavite?.name || 'Cavite'
    const response = await ecommerceService.getCities(String(cavite?.province_id || cavite?.id || ''))
    profileCities.value = Array.isArray(response.data?.data) ? response.data.data : (response.data || [])
    const match = profileCities.value.find((item: any) => String(item.name || item.city_name).trim().toLowerCase() === String(profileForm.city).trim().toLowerCase())
    profileForm.city_id = String(match?.city_id || match?.id || match?.code || '')
    if (profileForm.city_id) await onProfileCityChange()
  } finally {
    profileCitiesLoading.value = false
  }
}

const onProfileCityChange = async () => {
  const selectedCity = profileCities.value.find((item: any) => String(item.city_id || item.id || item.code || '') === String(profileForm.city_id))
  profileForm.city = selectedCity?.name || selectedCity?.city_name || profileForm.city
  profileForm.barangay = ''
  profileBarangaysLoading.value = true
  try {
    const response = await ecommerceService.getBarangays(String(profileForm.city_id))
    profileBarangays.value = Array.isArray(response.data?.data) ? response.data.data : (response.data || [])
    const match = profileBarangays.value.find((item: any) => String(item.name || item.barangay_name).trim().toLowerCase() === String(store.barangay || '').trim().toLowerCase())
    profileForm.barangay = String(match?.code || match?.id || match?.name || '')
  } finally {
    profileBarangaysLoading.value = false
  }
}

const openProfileEditor = async () => {
  Object.assign(profileForm, {
    contact_person: store.contact_person,
    phone: store.phone,
    address: store.address,
    city: store.city,
    barangay: store.barangay,
    province: store.province,
    type: store.type,
  })
  profileEditorVisible.value = true
  await loadProfileCities()
}

const openLogoDialog = () => {
  logoPreviewUrl.value = store.logo_url || ''
  logoSquareBlob.value = null
  logoOriginalDimensions.width = Number(store.logo_dimensions?.original_width || store.logo_dimensions?.width || 0)
  logoOriginalDimensions.height = Number(store.logo_dimensions?.original_height || store.logo_dimensions?.height || 0)
  logoDialogVisible.value = true
}

const closeLogoDialog = () => {
  logoDialogVisible.value = false
  if (logoPreviewUrl.value && logoPreviewUrl.value.startsWith('blob:')) {
    URL.revokeObjectURL(logoPreviewUrl.value)
  }
  logoPreviewUrl.value = store.logo_url || ''
  logoSquareBlob.value = null
}

const createSquareLogoBlob = (file: File): Promise<Blob> => {
  return new Promise((resolve, reject) => {
    const image = new Image()
    const objectUrl = URL.createObjectURL(file)

    image.onload = () => {
      logoOriginalDimensions.width = image.naturalWidth
      logoOriginalDimensions.height = image.naturalHeight

      const sourceSize = Math.min(image.naturalWidth, image.naturalHeight)
      const sourceX = Math.floor((image.naturalWidth - sourceSize) / 2)
      const sourceY = Math.floor((image.naturalHeight - sourceSize) / 2)
      const canvas = document.createElement('canvas')
      canvas.width = 512
      canvas.height = 512
      const context = canvas.getContext('2d')

      if (!context) {
        URL.revokeObjectURL(objectUrl)
        reject(new Error('Unable to prepare the logo crop.'))
        return
      }

      context.drawImage(image, sourceX, sourceY, sourceSize, sourceSize, 0, 0, 512, 512)
      canvas.toBlob((blob) => {
        URL.revokeObjectURL(objectUrl)
        if (!blob) {
          reject(new Error('Unable to convert the logo to a square image.'))
          return
        }
        resolve(blob)
      }, 'image/png', 0.92)
    }

    image.onerror = () => {
      URL.revokeObjectURL(objectUrl)
      reject(new Error('Unable to read the selected image.'))
    }

    image.src = objectUrl
  })
}

const handleLogoFileChange = async (event: Event) => {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return

  if (!file.type.startsWith('image/')) {
    toast.add({ severity: 'warn', summary: 'Invalid file', detail: 'Please choose a JPG, PNG, or WebP image.', life: 3000 })
    input.value = ''
    return
  }

  try {
    const blob = await createSquareLogoBlob(file)
    if (logoPreviewUrl.value && logoPreviewUrl.value.startsWith('blob:')) {
      URL.revokeObjectURL(logoPreviewUrl.value)
    }
    logoSquareBlob.value = blob
    logoPreviewUrl.value = URL.createObjectURL(blob)
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Logo failed',
      detail: error?.message || 'Unable to prepare the logo.',
      life: 3500,
    })
  } finally {
    input.value = ''
  }
}

const saveLogo = () => {
  if (!logoSquareBlob.value) {
    toast.add({ severity: 'warn', summary: 'No logo selected', detail: 'Choose a logo image first.', life: 2800 })
    return
  }

  savingLogo.value = true
  const formData = new FormData()
  formData.append('logo', logoSquareBlob.value, 'store-logo.png')
  formData.append('original_width', String(logoOriginalDimensions.width || 512))
  formData.append('original_height', String(logoOriginalDimensions.height || 512))

  router.post('/store/settings/logo', formData, {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      logoDialogVisible.value = false
      toast.add({ severity: 'success', summary: 'Logo updated', detail: 'Your shop logo was saved.', life: 3000 })
      router.reload({ only: ['store'], preserveScroll: true })
    },
    onError: (errors) => {
      toast.add({
        severity: 'error',
        summary: 'Upload failed',
        detail: errors?.logo || 'Unable to save the store logo.',
        life: 3500,
      })
    },
    onFinish: () => {
      savingLogo.value = false
    },
  })
}

const loadAttendanceCities = async () => {
  attendanceCitiesLoading.value = true
  try {
    const provincesResponse = await ecommerceService.getProvinces()
    const provinces = Array.isArray(provincesResponse.data?.data) ? provincesResponse.data.data : (provincesResponse.data || [])
    const province = provinces.find((item: any) => String(item.name || item.province_name).trim().toLowerCase() === String(attendanceDraft.province || 'Cavite').trim().toLowerCase())
      || provinces.find((item: any) => String(item.name || item.province_name).trim().toLowerCase() === 'cavite')
    if (province) attendanceDraft.province = province.name || province.province_name || 'Cavite'
    const provinceId = String(province?.province_id || province?.id || province?.code || '')
    const citiesResponse = await ecommerceService.getCities(provinceId)
    attendanceCities.value = Array.isArray(citiesResponse.data?.data) ? citiesResponse.data.data : (citiesResponse.data || [])
    const city = attendanceCities.value.find((item: any) => String(item.name || item.city_name).trim().toLowerCase() === String(attendanceDraft.city).trim().toLowerCase())
    attendanceDraft.city_id = String(city?.city_id || city?.id || city?.code || '')
    if (attendanceDraft.city_id) await loadAttendanceBarangays(false)
  } finally {
    attendanceCitiesLoading.value = false
  }
}

const loadAttendanceBarangays = async (clearSelection = true) => {
  if (!attendanceDraft.city_id) {
    attendanceBarangays.value = []
    return
  }
  const existingBarangay = attendanceDraft.barangay
  if (clearSelection) attendanceDraft.barangay = ''
  attendanceBarangaysLoading.value = true
  try {
    const response = await ecommerceService.getBarangays(String(attendanceDraft.city_id))
    attendanceBarangays.value = Array.isArray(response.data?.data) ? response.data.data : (response.data || [])
    if (!clearSelection) {
      const barangay = attendanceBarangays.value.find((item: any) => String(item.name || item.barangay_name).trim().toLowerCase() === String(existingBarangay).trim().toLowerCase())
      attendanceDraft.barangay = barangay?.name || barangay?.barangay_name || existingBarangay
    }
  } finally {
    attendanceBarangaysLoading.value = false
  }
}

const onAttendanceCityChange = async () => {
  const city = attendanceCities.value.find((item: any) => String(item.city_id || item.id || item.code || '') === String(attendanceDraft.city_id))
  attendanceDraft.city = city?.name || city?.city_name || ''
  await loadAttendanceBarangays(true)
}

const openAttendanceEditor = async () => {
  if (mapRef.value) {
    mapRef.value.remove()
    mapRef.value = null
    markerRef.value = null
  }
  Object.assign(attendanceDraft, {
    branch_id: attendance.branch_id,
    address: attendance.address,
    barangay: attendance.barangay,
    city: attendance.city,
    city_id: '',
    province: attendance.province,
    latitude: attendance.latitude,
    longitude: attendance.longitude,
    geofence_radius_m: attendance.geofence_radius_m,
    geofence_enabled: attendance.geofence_enabled,
  })
  searchQuery.value = attendance.address || ''
  mapError.value = ''
  mapReady.value = false
  attendanceEditorVisible.value = true
  await loadAttendanceCities()
}

const handleAttendanceDialogShow = async () => {
  await nextTick()
  await initMap()
}

const goToStoreVerification = () => router.visit('/system/store/verification')
const goToBilling = () => router.visit('/store/billing')

const saveStoreProfile = async () => {
  savingProfile.value = true
  router.post(
    '/store/settings/profile/prepare',
    {
      ...profileForm,
      city: profileForm.city,
      barangay: profileBarangayOptions.value.find((item) => item.value === profileForm.barangay)?.label || profileForm.barangay,
    },
    {
      preserveScroll: true,
      onError: (errors) => {
        toast.add({
          severity: 'error',
          summary: 'Unable to continue',
          detail: errors?.email || errors?.profile || 'Please check the store profile details.',
          life: 3500,
        })
      },
      onFinish: () => { savingProfile.value = false },
    }
  )
}

const syncLocation = (lat: number, lng: number) => {
  attendanceDraft.latitude = lat
  attendanceDraft.longitude = lng
}

const getAttendanceGeofence = () => {
  const radius = Number(attendanceDraft.geofence_radius_m)
  const latitude = Number(attendanceDraft.latitude)
  const longitude = Number(attendanceDraft.longitude)
  if (!radius || attendanceDraft.latitude === null || attendanceDraft.longitude === null) {
    return { type: 'FeatureCollection' as const, features: [] }
  }

  const earthRadius = 6371008.8
  const angularDistance = radius / earthRadius
  const latitudeRadians = latitude * Math.PI / 180
  const longitudeRadians = longitude * Math.PI / 180
  const ring: number[][] = []
  for (let step = 0; step <= 64; step += 1) {
    const bearing = step / 64 * Math.PI * 2
    const pointLatitude = Math.asin(
      Math.sin(latitudeRadians) * Math.cos(angularDistance)
        + Math.cos(latitudeRadians) * Math.sin(angularDistance) * Math.cos(bearing),
    )
    const pointLongitude = longitudeRadians + Math.atan2(
      Math.sin(bearing) * Math.sin(angularDistance) * Math.cos(latitudeRadians),
      Math.cos(angularDistance) - Math.sin(latitudeRadians) * Math.sin(pointLatitude),
    )
    ring.push([pointLongitude * 180 / Math.PI, pointLatitude * 180 / Math.PI])
  }
  return { type: 'Feature' as const, properties: {}, geometry: { type: 'Polygon' as const, coordinates: [ring] } }
}

const redrawAttendanceMap = () => {
  if (!mapRef.value || !mapboxgl || attendanceDraft.latitude === null || attendanceDraft.longitude === null) return
  const center: [number, number] = [Number(attendanceDraft.longitude), Number(attendanceDraft.latitude)]
  markerRef.value?.remove()
  markerRef.value = new mapboxgl.Marker({ draggable: true }).setLngLat(center).addTo(mapRef.value)
  markerRef.value.on('dragend', () => {
    const point = markerRef.value!.getLngLat()
    syncLocation(Number(point.lat.toFixed(6)), Number(point.lng.toFixed(6)))
  })
  const source = mapRef.value.getSource('attendance-geofence') as GeoJSONSource | undefined
  source?.setData(getAttendanceGeofence())
}

const initMap = async () => {
  if (!mapEl.value || mapReady.value) return
  try {
    mapError.value = ''
    const module = await import('mapbox-gl')
    mapboxgl = module.default
    mapboxgl.accessToken = requireMapboxToken()
    const center: [number, number] = [attendanceDraft.longitude ?? 120.9842, attendanceDraft.latitude ?? 14.5995]
    const map = new mapboxgl.Map({
      container: mapEl.value,
      style: 'mapbox://styles/mapbox/streets-v12',
      center,
      zoom: attendanceDraft.latitude !== null ? 16 : 12,
    })
    mapRef.value = map
    mapReady.value = true
    map.on('load', () => {
      map.addSource('attendance-geofence', { type: 'geojson', data: getAttendanceGeofence() })
      map.addLayer({ id: 'attendance-geofence-fill', type: 'fill', source: 'attendance-geofence', paint: { 'fill-color': '#22c55e', 'fill-opacity': 0.15 } })
      map.addLayer({ id: 'attendance-geofence-outline', type: 'line', source: 'attendance-geofence', paint: { 'line-color': '#16a34a', 'line-width': 2 } })
      if (attendanceDraft.latitude === null || attendanceDraft.longitude === null) syncLocation(center[1], center[0])
      redrawAttendanceMap()
      setTimeout(() => map.resize(), 150)
    })
    map.on('click', (event) => {
      syncLocation(Number(event.lngLat.lat.toFixed(6)), Number(event.lngLat.lng.toFixed(6)))
    })
    map.on('error', (event) => {
      mapError.value = event.error?.message || 'Mapbox could not load the map. Check the token and network connection.'
    })
  } catch (error: any) {
    mapRef.value?.remove()
    mapRef.value = null
    mapReady.value = false
    mapError.value = error?.message || 'Unable to load the Mapbox map.'
  }
}

const searchAddress = async () => {
  try {
    const result = await forwardGeocodeMapbox(searchQuery.value.trim())
    if (!result) {
      toast.add({ severity: 'warn', summary: 'Location not found', detail: 'Try a more specific address.', life: 3000 })
      return
    }
    attendanceDraft.address = result.address || attendanceDraft.address
    syncLocation(Number(result.latitude.toFixed(6)), Number(result.longitude.toFixed(6)))
    mapRef.value?.jumpTo({ center: [result.longitude, result.latitude], zoom: 16 })
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Mapbox search failed', detail: error?.message || 'Unable to search for that location.', life: 3500 })
  }
}

const goToUpgrade = () => {
  const storeId = Number(subscription.store_id || store.id || 0)
  router.visit(`/subscription-plans${storeId ? `?store_id=${storeId}` : ''}`)
}

const selectPlan = (key: string) => {
  const plan = availablePlans.value.find(item => item.key === key)
  if (!plan) return
  selectedPlan.key = plan.key
  selectedPlan.amountPhp = plan.amountPhp
  selectedPlan.months = plan.months
  selectedPlan.tier = plan.tier
  selectedPlan.label = plan.label
}

const openGcashDialogForSelectedPlan = () => {
  planDialogVisible.value = false
  gcashForm.name = ''
  gcashForm.phone = ''
  const first = walletTypeOptions.value[0]?.value
  selectedWalletType.value = (first as any) || 'gcash'
  gcashDialogVisible.value = true
}

const openPaymentMethodDialogForSelectedPlan = () => {
  planDialogVisible.value = false
  selectedPaymentMethod.value = ''
  paymentMethodDialogVisible.value = true
}

const continueToPaymentCredentials = () => {
  paymentMethodDialogVisible.value = false
  // map selected payment method to wallet/credential dialog
  if (selectedPaymentMethod.value === 'gcash') {
    const first = walletTypeOptions.value[0]?.value
    selectedWalletType.value = (first as any) || 'gcash'
    gcashForm.name = ''
    gcashForm.phone = ''
    gcashDialogVisible.value = true
    return
  }

  // card or others: reuse credential dialog (collect account name/email) and treat as card flow
  selectedWalletType.value = 'card'
  gcashForm.name = ''
  gcashForm.phone = ''
  gcashDialogVisible.value = true
}

const goToVerification = () => {
  router.visit('/system/store/verification')
}

const buildFileUrl = (path: string | null | undefined) => {
  if (!path) return ''
  if (/^https?:\/\//i.test(path)) return path
  return `${apiBaseUrl}/storage/${String(path).replace(/^\/+/, '')}`
}

const openDocumentDialog = (document: any) => {
  const url = buildFileUrl(document?.path) || String(document?.inspect_url || document?.download_url || '')
  if (!url) return

  documentPreviewUrl.value = url
  documentPreviewTitle.value = String(document?.label || 'Verification Document')
  documentPreviewKind.value = String(document?.mime_type || '').startsWith('image/') ? 'image' : 'pdf'
  documentPreviewDialogVisible.value = true
}

const loadVerificationDocuments = async () => {
  if (!subscription.store_id) {
    verificationDocuments.value = []
    return
  }

  loadingVerificationDocuments.value = true
  try {
    const response = await axiosClient.get(`/api/stores/${subscription.store_id}/verification/documents`)
    const payload = response?.data?.data || {}
    verificationDocuments.value = Array.isArray(payload.documents) ? payload.documents : []
  } catch (error: any) {
    verificationDocuments.value = []
    toast.add({
      severity: 'error',
      summary: 'Unable to load documents',
      detail: error?.response?.data?.message || 'Failed to load verification attachments.',
      life: 3000,
    })
  } finally {
    loadingVerificationDocuments.value = false
  }
}

const handleVerificationAction = async () => {
  if (!shouldShowVerificationAttachments.value) {
    goToVerification()
    return
  }

  await loadVerificationDocuments()
  verificationDocumentsDialogVisible.value = true
}

const handleStoreVerifyClick = async () => {
  if (!isVerificationRejected.value) {
    goToStoreVerification()
    return
  }

  await loadVerificationDocuments()
  verificationDocumentsDialogVisible.value = true
}

const resubmitVerification = () => {
  verificationDocumentsDialogVisible.value = false
  goToStoreVerification()
}

const toPlainPhone = (value: string): string => value.replace(/\D/g, '')

const submitUpgradeCheckout = async () => {
  const normalizedPhone = toPlainPhone(gcashForm.phone)
  if (!gcashForm.name.trim()) {
    toast.add({ severity: 'warn', summary: 'Missing name', detail: 'Enter the GCash account name.', life: 2800 })
    return
  }
  if (!/^09\d{9}$/.test(normalizedPhone)) {
    toast.add({ severity: 'warn', summary: 'Invalid number', detail: 'Enter a valid PH GCash number (09XXXXXXXXX).', life: 3200 })
    return
  }

  gcashDialogVisible.value = false
  await startUpgradeCheckout(gcashForm.name.trim(), normalizedPhone)
}

const startUpgradeCheckout = async (gcashName: string, gcashPhone: string) => {
  upgrading.value = true
  try {
    const effectiveStoreId = subscription.store_id ?? null
    const metadata = {
      store_id: effectiveStoreId,
      months: selectedPlan.months,
      subscription_tier: selectedPlan.tier,
      plan_key: selectedPlan.key,
      plan_label: selectedPlan.label,
    }

    const fallbackEmail = (store.email || 'owner@example.com').trim()

    // Card payments should use Online Payment hosted checkout so we never collect card data on our site.
    if (selectedWalletType.value === 'card') {
      const successUrl = `${window.location.origin}/store/settings?paymongo_checkout=1`
      const cancelUrl = `${window.location.origin}/store/settings?paymongo_checkout_cancel=1`
      const sessionRes = await paymongoService.createCheckoutSession({
        amount: selectedPlan.amountPhp * 100,
        currency: 'PHP',
        description: `Store plan upgrade (${selectedPlan.label})`,
        payment_method_allowed: paymongoPaymentMethods.value.length ? paymongoPaymentMethods.value : ['gcash'],
        metadata: { ...metadata, payer_email: fallbackEmail, payer_name: gcashName, payer_phone: gcashPhone },
        store_id: effectiveStoreId,
        payable_type: 'subscription_upgrade',
        payable_id: effectiveStoreId,
        success_url: successUrl,
        cancel_url: cancelUrl,
      })
      const checkoutUrl = sessionRes?.data?.checkout_url
      if (!checkoutUrl) throw new Error(sessionRes?.message || 'Unable to start card checkout.')
      window.location.href = checkoutUrl
      return
    }

    const intentResponse = await paymongoService.createIntent({
      amount: selectedPlan.amountPhp * 100,
      currency: 'PHP',
      description: `Store plan upgrade (${selectedPlan.label})`,
      statement_descriptor: 'Store Upgrade',
      payment_method_allowed: paymongoPaymentMethods.value.length ? paymongoPaymentMethods.value : ['gcash'],
      metadata,
      store_id: effectiveStoreId,
      payable_type: 'subscription_upgrade',
      payable_id: effectiveStoreId,
    })

    const paymentIntentId = intentResponse?.data?.data?.id
    if (!paymentIntentId) {
      throw new Error(intentResponse?.message || 'Unable to create payment intent.')
    }

    const returnUrl = `${window.location.origin}/store/settings?paymongo_intent=${encodeURIComponent(paymentIntentId)}`
    const walletResponse = await paymongoService.startWallet(paymentIntentId, selectedWalletType.value as any, {
      name: gcashName,
      email: fallbackEmail,
      phone: gcashPhone,
      return_url: returnUrl,
    })

    const redirectUrl = walletResponse?.data?.redirect_url
    if (!redirectUrl) {
      throw new Error(walletResponse?.message || 'Unable to start wallet checkout.')
    }

    window.location.href = redirectUrl
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Upgrade failed',
      detail: error?.message || 'Unable to start upgrade payment.',
      life: 4000,
    })
  } finally {
    upgrading.value = false
  }
}

const handleUpgradeReturn = async () => {
  const params = new URLSearchParams(window.location.search)
  const paymentIntentId = params.get('paymongo_intent') || params.get('payment_intent_id')
  if (!paymentIntentId) return

  try {
    const statusResponse = await paymongoService.getIntent(paymentIntentId)
    const status = String(statusResponse?.data?.data?.attributes?.status || '').toLowerCase()

    if (status === 'succeeded') {
      router.reload({ preserveScroll: true })
    } else {
      toast.add({
        severity: 'warn',
        summary: 'Payment pending',
        detail: 'Payment is still processing. Please refresh in a few moments.',
        life: 3500,
      })
    }
  } catch (_error) {
    toast.add({
      severity: 'error',
      summary: 'Status check failed',
      detail: 'Unable to verify payment status right now.',
      life: 3500,
    })
  } finally {
    params.delete('paymongo_intent')
    params.delete('payment_intent_id')
    const query = params.toString()
    const cleanUrl = `${window.location.pathname}${query ? `?${query}` : ''}`
    window.history.replaceState({}, '', cleanUrl)
  }
}

const handleUpgradePrompt = () => {
  const params = new URLSearchParams(window.location.search)
  const shouldOpen = params.get('open_upgrade') === '1'
  const targetPlan = params.get('plan')
  if (!shouldOpen) return

  if (targetPlan) {
    selectPlan(targetPlan)
  }
  planDialogVisible.value = true

  params.delete('open_upgrade')
  params.delete('plan')
  const query = params.toString()
  const cleanUrl = `${window.location.pathname}${query ? `?${query}` : ''}`
  window.history.replaceState({}, '', cleanUrl)
}

onMounted(async () => {
  const params = new URLSearchParams(window.location.search)
  if (params.get('profile_updated') === '1') {
    profileSuccessVisible.value = true
    params.delete('profile_updated')
    const query = params.toString()
    window.history.replaceState({}, '', `${window.location.pathname}${query ? `?${query}` : ''}`)
  }
  if (params.get('attendance_updated') === '1') {
    attendanceSuccessVisible.value = true
    params.delete('attendance_updated')
    const query = params.toString()
    window.history.replaceState({}, '', `${window.location.pathname}${query ? `?${query}` : ''}`)
  }
  await handleUpgradeReturn()
  handleUpgradePrompt()
})

watch(
  () => [attendanceDraft.latitude, attendanceDraft.longitude, attendanceDraft.geofence_radius_m],
  () => redrawAttendanceMap(),
)
</script>
