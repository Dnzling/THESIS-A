<template>
    <div class="space-y-5">
        <Toast />
        <ConfirmDialog />
        <header>
            <p
                class="text-xs font-semibold uppercase tracking-wider text-orange-600"
            >
                Store Settings
            </p>
            <h1 class="text-2xl font-semibold text-slate-900">
                Branch Information
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                Manage how this branch is identified and located.
            </p>
        </header>

        <div v-if="loading" class="grid items-start gap-4 xl:grid-cols-3">
            <Card class="self-start border border-slate-200 shadow-sm xl:col-span-2">
                <template #title><Skeleton width="11rem" height="1.25rem" /></template>
                <template #content>
                    <div class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                        <div v-for="item in 4" :key="`branch-field-${item}`" class="space-y-2">
                            <Skeleton width="6rem" height="0.75rem" />
                            <Skeleton :width="item === 4 ? '9rem' : '12rem'" height="1rem" />
                        </div>
                        <div class="space-y-2 sm:col-span-2">
                            <Skeleton width="4rem" height="0.75rem" />
                            <Skeleton width="90%" height="1rem" />
                        </div>
                    </div>
                    <div class="mt-4 space-y-3 border-t border-slate-200 pt-4">
                        <Skeleton width="10rem" height="1rem" />
                        <Skeleton width="14rem" height="0.75rem" />
                    </div>
                    <div class="mt-4 space-y-3 border-t border-slate-200 pt-4">
                        <Skeleton width="7rem" height="1rem" />
                        <div class="flex flex-wrap gap-3">
                            <Skeleton v-for="item in 2" :key="`geo-${item}`" width="12rem" height="0.875rem" />
                        </div>
                    </div>
                </template>
            </Card>
            <Card class="self-start border border-slate-200 shadow-sm">
                <template #title><Skeleton width="6rem" height="1.25rem" /></template>
                <template #content>
                    <Skeleton class="mx-auto aspect-square w-full max-w-64" borderRadius="12px" />
                    <Skeleton class="mx-auto mt-3" width="85%" height="0.75rem" />
                </template>
            </Card>
            <Card class="border border-slate-200 shadow-sm xl:col-span-3">
                <template #title><Skeleton width="12rem" height="1.25rem" /></template>
                <template #content>
                    <div class="flex flex-wrap gap-x-5 gap-y-3">
                        <Skeleton v-for="day in 7" :key="`hours-${day}`" width="7.5rem" height="1rem" />
                    </div>
                </template>
            </Card>
            <Card class="border border-slate-200 shadow-sm xl:col-span-3">
                <template #content>
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <Skeleton width="9rem" height="1.5rem" borderRadius="999px" />
                            <Skeleton width="16rem" height="1rem" />
                        </div>
                        <div class="ml-auto flex gap-2">
                            <Skeleton width="7rem" height="2.25rem" />
                            <Skeleton width="5rem" height="2.25rem" />
                        </div>
                    </div>
                </template>
            </Card>
            <Card class="border border-slate-200 shadow-sm xl:col-span-3">
                <template #title><Skeleton width="13rem" height="1.25rem" /></template>
                <template #content>
                    <Skeleton width="75%" height="0.75rem" class="mb-4" />
                    <div class="divide-y divide-slate-100 rounded-xl border border-slate-200">
                        <div v-for="method in 2" :key="`window-${method}`" class="flex items-center justify-between gap-3 px-4 py-3">
                            <div class="space-y-2">
                                <Skeleton width="5rem" height="1rem" />
                                <Skeleton width="18rem" height="0.75rem" />
                            </div>
                            <Skeleton width="6rem" height="2rem" />
                        </div>
                    </div>
                </template>
            </Card>
        </div>
        <Message v-else-if="error" severity="error" :closable="false">{{
            error
        }}</Message>

        <div v-else class="grid items-start gap-4 xl:grid-cols-3">
            <Card
                class="self-start border border-slate-200 shadow-sm xl:col-span-2"
                ><template #title
                    ><div class="flex items-center justify-between gap-3">
                        <span class="text-base">Store Information</span
                        ><Button
                            label="Edit"
                            icon="pi pi-pencil"
                            size="small"
                            severity="secondary"
                            text
                            @click="openStoreInfoDialog"
                        /></div></template
                ><template #content
                    ><div class="grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                        <div>
                            <span class="block text-xs text-slate-500"
                                >Store / Branch Name</span
                            ><span class="font-medium text-slate-900">{{
                                form.name || 'Not set'
                            }}</span>
                        </div>
                        <div>
                            <span class="block text-xs text-slate-500"
                                >Branch Code</span
                            ><span class="font-medium text-slate-900">{{
                                form.branch_code || 'Not set'
                            }}</span>
                        </div>
                        <div>
                            <span class="block text-xs text-slate-500"
                                >Contact Number</span
                            ><span class="font-medium text-slate-900">{{
                                form.contact_number || 'Not set'
                            }}</span>
                        </div>
                        <div>
                            <span class="block text-xs text-slate-500"
                                >Email Address</span
                            ><span class="font-medium text-slate-900">{{
                                form.email || 'Not set'
                            }}</span>
                        </div>
                        <div class="sm:col-span-2">
                            <span class="block text-xs text-slate-500"
                                >Address</span
                            ><span class="font-medium text-slate-900">{{
                                [
                                    form.address,
                                    form.barangay,
                                    form.city,
                                    form.province
                                ]
                                    .filter(Boolean)
                                    .join(', ') || 'Not set'
                            }}</span>
                        </div>
                    </div>
                    <div class="mt-3 border-t border-slate-200 pt-3">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-slate-800">
                                    Branch documents
                                </p>
                                <p class="text-xs text-slate-500">
                                    {{ branchDocuments.length }} submitted<span
                                        v-if="form.verification?.submitted_at"
                                    >
                                        |
                                        {{
                                            formatDocumentDate(
                                                form.verification.submitted_at
                                            )
                                        }}</span
                                    >
                                </p>
                            </div>
                            <Button
                                label="View"
                                icon="pi pi-eye"
                                size="small"
                                severity="secondary"
                                text
                                @click="documentsDialogVisible = true"
                            />
                        </div>
                    </div>
                    <div class="mt-3 border-t border-slate-200 pt-3">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-slate-800">
                                    Geolocation
                                </p>
                                <div
                                    class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-600"
                                >
                                    <span>{{
                                        geocodingLocationAddress
                                            ? 'Looking up address...'
                                            : geolocationAddress || 'Address not available'
                                    }}</span
                                    ><Tag
                                        :value="
                                            form.geofence_enabled
                                                ? 'Geofence on - ' +
                                                  (Number(
                                                      form.geofence_radius_m
                                                  ) || 0) +
                                                  ' m'
                                                : 'Geofence off'
                                        "
                                        :severity="
                                            form.geofence_enabled
                                                ? 'success'
                                                : 'secondary'
                                        "
                                    />
                                </div>
                            </div>
                            <Button
                                label="Edit"
                                icon="pi pi-pencil"
                                size="small"
                                severity="secondary"
                                text
                                @click="openGeolocationDialog"
                            />
                        </div></div></template
            ></Card>
            <Card class="self-start border border-slate-200 shadow-sm"
                ><template #title
                    ><span class="text-base">Store Logo</span></template
                ><template #content
                    ><button
                        type="button"
                        class="group relative mx-auto flex aspect-square w-full max-w-64 items-center justify-center overflow-hidden rounded-xl border border-dashed border-slate-300 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-orange-500"
                        aria-label="Upload store logo"
                        :disabled="saving"
                        @click="logoInput?.click()"
                    >
                        <img
                            v-if="logoPreview || form.logo_url"
                            :src="logoPreview || form.logo_url"
                            alt="Store logo"
                            class="h-full w-full object-contain p-3"
                        />
                        <div v-else class="text-center text-slate-400">
                            <i class="pi pi-image text-4xl"></i>
                            <p class="mt-2 text-xs">No logo uploaded</p>
                        </div>
                        <span
                            class="absolute inset-0 flex items-center justify-center gap-2 bg-slate-950/55 text-sm font-medium text-white opacity-0 transition group-hover:opacity-100 group-focus:opacity-100"
                            ><i class="pi pi-pencil"></i
                            >{{ saving ? 'Uploading…' : 'Edit logo' }}</span
                        ><span
                            v-if="saving"
                            class="absolute inset-0 flex items-center justify-center bg-white/70"
                            ><i
                                class="pi pi-spin pi-spinner text-2xl text-orange-600"
                            ></i
                        ></span></button
                    ><input
                        ref="logoInput"
                        type="file"
                        accept="image/png,image/jpeg,image/webp"
                        class="hidden"
                        @change="handleLogoSelected"
                    />
                    <p class="mt-2 text-center text-xs text-slate-500">
                        Hover the image to change it · PNG, JPG, or WebP up to 4
                        MB.
                    </p></template
                ></Card
            >
            <Card class="border border-slate-200 shadow-sm xl:col-span-3"
                ><template #title
                    ><div class="flex items-center justify-between gap-3">
                        <span class="text-base">Store Operating Hours</span
                        ><Button
                            label="Edit hours"
                            icon="pi pi-clock"
                            size="small"
                            severity="secondary"
                            outlined
                            @click="openOperatingHoursDialog"
                        /></div></template
                ><template #content
                    ><div
                        class="flex flex-wrap gap-x-5 gap-y-2 text-sm text-slate-700"
                    >
                        <span
                            v-for="day in operatingDayOptions"
                            :key="day.value"
                            ><b>{{ day.label.slice(0, 3) }}</b>
                            {{
                                formatOperatingHours(
                                    form.opening_hours?.schedule?.[day.value]
                                )
                            }}</span
                        >
                    </div></template
                ></Card
            >

            <Card class="border border-slate-200 shadow-sm xl:col-span-3"
                ><template #content
                    ><div
                        class="flex flex-wrap items-center justify-between gap-3"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <Tag
                                :value="
                                    form.suspension_today
                                        ? 'Suspended today'
                                        : 'No suspension today'
                                "
                                :severity="
                                    form.suspension_today ? 'danger' : 'success'
                                "
                            />
                            <div class="min-w-0 text-sm">
                                <p
                                    v-if="form.suspension_today"
                                    class="truncate font-medium text-slate-900"
                                >
                                    {{
                                        form.suspension_today.reason ||
                                        'Branch service suspension'
                                    }}
                                    |
                                    {{
                                        formatDocumentDate(
                                            form.suspension_today.start_date
                                        )
                                    }}<span
                                        v-if="form.suspension_today.end_date"
                                    >
                                        to
                                        {{
                                            formatDocumentDate(
                                                form.suspension_today.end_date
                                            )
                                        }}</span
                                    >
                                </p>
                                <p v-else class="text-slate-600">
                                    Pickup and delivery are available for this
                                    branch today.
                                </p>
                            </div>
                        </div>
                        <div class="ml-auto flex shrink-0 items-center gap-2">
                            <Button
                                label="View history"
                                icon="pi pi-history"
                                size="small"
                                severity="secondary"
                                outlined
                                @click="openSuspensionHistory"
                            /><Button
                                label="Create"
                                icon="pi pi-plus"
                                size="small"
                                severity="warn"
                                @click="openSuspensionDialog"
                            />
                        </div></div></template
            ></Card>
            <Card class="border border-slate-200 shadow-sm xl:col-span-3"><template #title><span class="text-base">Pickup and Delivery Windows</span></template><template #content><p class="mb-4 text-xs text-slate-500">Set the days and broad hours customers can choose. Your team confirms the exact time after ordering.</p><div class="divide-y divide-slate-100 rounded-xl border border-slate-200"><div v-for="method in ['pickup', 'delivery']" :key="method" class="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"><div class="min-w-0"><p class="text-sm font-semibold capitalize text-slate-900">{{ method }}</p><p class="mt-0.5 text-xs text-slate-600">{{ formatWindowSummary(fulfillmentWindows[method]) }}</p></div><Button :label="method === 'pickup' ? 'Pickup' : 'Delivery'" icon="pi pi-pencil" size="small" severity="secondary" outlined @click="openFulfillmentEditor(method)" /></div></div></template></Card>
            <Dialog v-model:visible="fulfillmentEditorVisible" modal :header="fulfillmentEditorMethod === 'pickup' ? 'Pickup settings' : 'Delivery settings'" :style="{ width: 'min(94vw, 44rem)' }"><p class="mb-4 text-xs text-slate-500">Set broad service windows. Customers select a date; your team confirms the exact time after ordering.</p><div class="mb-4 flex gap-2"><Button label="Pickup" size="small" :severity="fulfillmentEditorMethod === 'pickup' ? 'warn' : 'secondary'" :outlined="fulfillmentEditorMethod !== 'pickup'" @click="fulfillmentEditorMethod = 'pickup'" /><Button label="Delivery" size="small" :severity="fulfillmentEditorMethod === 'delivery' ? 'warn' : 'secondary'" :outlined="fulfillmentEditorMethod !== 'delivery'" @click="fulfillmentEditorMethod = 'delivery'" /></div><section class="space-y-3 rounded-xl border border-slate-200 p-4"><h3 class="text-sm font-semibold capitalize text-slate-900">{{ fulfillmentEditorMethod }}</h3><div class="flex flex-wrap gap-3"><label v-for="day in operatingDayOptions" :key="day.value" class="flex items-center gap-2 text-sm"><input v-model="fulfillmentDraft[fulfillmentEditorMethod].days" type="checkbox" :value="day.value" class="rounded border-slate-300 text-orange-500 focus:ring-orange-500" />{{ day.label }}</label></div><div class="grid grid-cols-2 gap-3"><div><label class="mb-1 block text-xs font-medium text-slate-700">From</label><DatePicker v-model="fulfillmentTimeModels[fulfillmentEditorMethod].start.value" timeOnly hourFormat="12" size="small" fluid /></div><div><label class="mb-1 block text-xs font-medium text-slate-700">Until</label><DatePicker v-model="fulfillmentTimeModels[fulfillmentEditorMethod].end.value" timeOnly hourFormat="12" size="small" fluid /></div></div><div><label class="mb-1 block text-xs font-medium text-slate-700">Days needed before booking</label><InputNumber v-model="fulfillmentDraft[fulfillmentEditorMethod].lead_days" :min="0" :max="14" size="small" fluid /></div></section><template #footer><Button label="Cancel" severity="secondary" outlined size="small" @click="cancelFulfillmentEditor" /><Button label="Save windows" icon="pi pi-check" severity="warn" size="small" :loading="savingFulfillmentWindows" :disabled="savingFulfillmentWindows" @click="confirmSaveFulfillmentWindows" /></template></Dialog>
            <Dialog
                v-model:visible="storeInfoDialogVisible"
                modal
                header="Edit Store Information"
                :style="{ width: 'min(94vw, 48rem)' }"
                ><div class="grid gap-4 sm:grid-cols-2">
                    <Field label="Store Name" required
                        ><InputText v-model="form.name" class="w-full" /></Field
                    ><Field label="Branch Code"
                        ><InputText
                            v-model="form.branch_code"
                            class="w-full"
                            disabled /></Field
                    ><Field label="Contact Number" required
                        ><InputText
                            v-model="form.contact_number"
                            class="w-full" /></Field
                    ><Field label="Email Address"
                        ><InputText
                            v-model="form.email"
                            type="email"
                            class="w-full" /></Field
                    ><Field label="Address" required class="sm:col-span-2"
                        ><InputText
                            v-model="form.address"
                            class="w-full" /></Field
                    ><Field label="City" required
                        ><Select
                            v-model="cityId"
                            :options="cityOptions"
                            optionLabel="label"
                            optionValue="value"
                            filter
                            class="w-full"
                            placeholder="Select city"
                            :loading="loadingCities"
                            :disabled="!provinceId"
                            @change="onCityChange" /></Field
                    ><Field label="Barangay"
                        ><Select
                            v-model="form.barangay"
                            :options="barangayOptions"
                            optionLabel="label"
                            optionValue="value"
                            filter
                            class="w-full"
                            placeholder="Select barangay"
                            :loading="loadingBarangays"
                            :disabled="!cityId" /></Field
                    ><Field label="Province" required
                        ><InputText
                            v-model="form.province"
                            class="w-full"
                            readonly
                    /></Field>
                </div>
                <template #footer
                    ><Button
                        label="Cancel"
                        severity="secondary"
                        outlined
                        @click="cancelStoreInfoDialog" /><Button
                        label="Save Information"
                        icon="pi pi-check"
                        severity="warn"
                        :loading="saving"
                        :disabled="saving"
                        @click="confirmSaveStoreInfo" /></template
            ></Dialog>
            <Dialog
                v-model:visible="operatingHoursDialogVisible"
                modal
                header="Edit Store Operating Hours"
                :style="{ width: 'min(94vw, 44rem)' }"
                ><div class="space-y-2">
                    <div
                        v-for="day in operatingDayOptions"
                        :key="day.value"
                        class="grid items-center gap-3 rounded-lg border border-slate-200 px-3 py-2 sm:grid-cols-[1fr_1fr_1fr]"
                    >
                        <label class="flex items-center gap-2 text-sm"
                            ><input
                                v-model="
                                    operatingHoursDraft.schedule[day.value]
                                        .enabled
                                "
                                type="checkbox"
                                class="rounded border-slate-300 text-orange-500 focus:ring-orange-500"
                                @change="applyDefaultHours(day.value)"
                            />{{ day.label }}</label
                        ><label class="text-xs text-slate-500"
                            >From<DatePicker
                                v-model="
                                    operatingTimeModels[day.value].opens_at
                                        .value
                                "
                                timeOnly
                                hourFormat="12"
                                size="small"
                                fluid
                                :disabled="
                                    !operatingHoursDraft.schedule[day.value]
                                        .enabled
                                " /></label
                        ><label class="text-xs text-slate-500"
                            >Until<DatePicker
                                v-model="
                                    operatingTimeModels[day.value].closes_at
                                        .value
                                "
                                timeOnly
                                hourFormat="12"
                                size="small"
                                fluid
                                :disabled="
                                    !operatingHoursDraft.schedule[day.value]
                                        .enabled
                                "
                        /></label>
                    </div>
                </div>
                <p class="mt-2 text-xs text-slate-500">
                    Turning on a day defaults the hours to 8:00 AM to 6:00 PM.
                </p>
                <template #footer
                    ><Button
                        label="Cancel"
                        severity="secondary"
                        outlined
                        @click="operatingHoursDialogVisible = false" /><Button
                        label="Save Hours"
                        icon="pi pi-check"
                        severity="warn"
                        :loading="saving"
                        :disabled="saving"
                        @click="confirmSaveOperatingHours" /></template
            ></Dialog>
            <Dialog
                v-model:visible="geolocationDialogVisible"
                modal
                header="Edit Geolocation"
                :style="{ width: 'min(94vw, 54rem)' }"
                @show="handleGeolocationDialogShow"
                @hide="destroyMap"
                ><div class="grid gap-5 sm:grid-cols-2">
                    <Field label="Geofence Radius"
                        ><div class="flex items-center gap-3">
                            <Slider
                                v-model="form.geofence_radius_m"
                                :min="50"
                                :max="500"
                                :step="10"
                                class="flex-1"
                            /><span
                                class="min-w-16 text-right text-sm font-medium text-slate-700"
                                >{{
                                    Number(form.geofence_radius_m) || 0
                                }}
                                m</span
                            >
                        </div></Field
                    >
                    <div class="flex items-end pb-1">
                        <div class="flex items-center gap-3">
                            <ToggleSwitch
                                v-model="form.geofence_enabled"
                            /><span class="text-sm text-slate-700"
                                >Enable geofence</span
                            >
                        </div>
                    </div>
                </div>
                <div class="mt-4 space-y-2">
                    <div class="flex gap-2">
                        <InputText
                            v-model="searchQuery"
                            class="flex-1"
                            placeholder="Search an address or place"
                            @keyup.enter.prevent="searchLocation"
                        /><Button
                            type="button"
                            label="Search"
                            icon="pi pi-search"
                            :loading="searching"
                            @click="searchLocation"
                        />
                    </div>
                    <div
                        ref="mapContainer"
                        class="h-80 w-full overflow-hidden rounded-xl border border-slate-200"
                    ></div>
                    <small class="text-xs text-slate-500"
                        >Click the map or drag the marker to set the branch
                        location.</small
                    ><Message
                        v-if="locationError"
                        severity="error"
                        :closable="false"
                        >{{ locationError }}</Message
                    ><Message
                        v-else-if="locationStatus === 'valid'"
                        severity="success"
                        :closable="false"
                        >Location is inside Cavite province.</Message
                    ><Message
                        v-if="mapError"
                        severity="error"
                        :closable="false"
                        >{{ mapError }}</Message
                    >
                </div>
                <template #footer
                    ><Button
                        label="Cancel"
                        severity="secondary"
                        outlined
                        @click="cancelGeolocationDialog" /><Button
                        label="Save Location"
                        icon="pi pi-check"
                        severity="warn"
                        :loading="saving"
                        :disabled="saving"
                        @click="confirmSaveGeolocation" /></template
            ></Dialog>
            <Dialog
                v-model:visible="documentsDialogVisible"
                modal
                header="Branch Documents"
                :style="{ width: 'min(94vw, 54rem)' }"
                ><DataTable :value="branchDocuments" size="small" rowHover
                    ><template #empty
                        ><div class="py-8 text-center text-sm text-slate-500">
                            No documents have been submitted by this branch.
                        </div></template
                    ><Column field="label" header="Document" /><Column
                        header="Reg. / Reference"
                        ><template #body="{ data }">{{
                            data.reference_number || 'Not provided'
                        }}</template></Column
                    ><Column header="Expiration"
                        ><template #body="{ data }">{{
                            formatDocumentDate(data.expires_at)
                        }}</template></Column
                    ><Column header="File"
                        ><template #body="{ data }"
                            ><a
                                v-if="data.preview_url"
                                :href="data.preview_url"
                                target="_blank"
                                rel="noopener"
                                class="text-orange-700 hover:underline"
                                >Preview</a
                            ><a
                                v-if="data.download_url"
                                :href="data.download_url"
                                class="ml-3 text-slate-600 hover:underline"
                                >Download</a
                            ><span
                                v-if="!data.preview_url"
                                class="text-slate-400"
                                >Owner access required</span
                            ></template
                        ></Column
                    ></DataTable
                ></Dialog
            >
            <Dialog
                v-model:visible="suspensionDialogVisible"
                modal
                header="Create Service Suspension"
                :style="{ width: 'min(94vw, 38rem)' }"
                ><div class="grid gap-4 sm:grid-cols-2">
                    <Field label="Start date" required
                        ><DatePicker
                            v-model="newSuspensionStartModel"
                            dateFormat="dd M yy"
                            showIcon
                            fluid /></Field
                    ><Field label="End date"
                        ><DatePicker
                            v-model="newSuspensionEndModel"
                            dateFormat="dd M yy"
                            showIcon
                            fluid
                            placeholder="No end date" /></Field
                    ><Field label="Reason (optional)" class="sm:col-span-2"
                        ><InputText
                            v-model="newSuspension.reason"
                            class="w-full"
                            placeholder="Typhoon, emergency, maintenance, etc."
                    /></Field>
                </div>
                <p class="mt-3 text-xs text-slate-500">
                    The branch will not be chosen for ecommerce pickup or
                    delivery during these dates.
                </p>
                <template #footer
                    ><Button
                        label="Cancel"
                        severity="secondary"
                        outlined
                        :disabled="savingSuspension"
                        @click="suspensionDialogVisible = false" /><Button
                        label="Create suspension"
                        icon="pi pi-check"
                        severity="warn"
                        :loading="savingSuspension"
                        :disabled="savingSuspension"
                        @click="confirmCreateSuspension" /></template
            ></Dialog>
            <Dialog
                v-model:visible="suspensionHistoryDialogVisible"
                modal
                header="Branch Suspension History"
                :style="{ width: 'min(94vw, 58rem)' }"
                ><DataTable
                    :value="suspensionHistory"
                    size="small"
                    rowHover
                    :loading="loadingSuspensionHistory"
                    ><template #empty
                        ><div class="py-8 text-center text-sm text-slate-500">
                            No suspensions have been recorded for this branch.
                        </div></template
                    ><Column header="Status"
                        ><template #body="{ data }"
                            ><Tag
                                :value="suspensionStatus(data)"
                                :severity="
                                    suspensionSeverity(data)
                                " /></template></Column
                    ><Column field="reason" header="Reason"
                        ><template #body="{ data }">{{
                            data.reason || 'No reason provided'
                        }}</template></Column
                    ><Column header="From"
                        ><template #body="{ data }">{{
                            formatDocumentDate(data.start_date)
                        }}</template></Column
                    ><Column header="Until"
                        ><template #body="{ data }">{{
                            data.end_date
                                ? formatDocumentDate(data.end_date)
                                : 'Until ended'
                        }}</template></Column
                    ><Column header="Created"
                        ><template #body="{ data }">{{
                            formatDocumentDate(data.created_at)
                        }}</template></Column
                    ></DataTable
                ></Dialog
            >
        </div>
    </div>
</template>

<script setup lang="ts">
import {
    computed,
    defineComponent,
    h,
    nextTick,
    onBeforeUnmount,
    onMounted,
    reactive,
    ref,
    watch
} from 'vue'
import Button from 'primevue/button'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Message from 'primevue/message'
import Select from 'primevue/select'
import Skeleton from 'primevue/skeleton'
import Slider from 'primevue/slider'
import Toast from 'primevue/toast'
import ToggleSwitch from 'primevue/toggleswitch'
import Dialog from 'primevue/dialog'
import DatePicker from 'primevue/datepicker'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import ConfirmDialog from 'primevue/confirmdialog'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { storeModuleService } from '@/services/store-module.service'
import ecommerceService from '@/services/ecommerce.service'
import axiosClient from '@/axios'
import { forwardGeocodeMapbox, requireMapboxToken, reverseGeocodeMapbox } from '@/utils/mapbox'
import type {
    GeoJSONSource,
    Map as MapboxMap,
    Marker as MapboxMarker
} from 'mapbox-gl'
import 'mapbox-gl/dist/mapbox-gl.css'

const Field = defineComponent({
    props: { label: { type: String, required: true }, required: Boolean },
    setup:
        (props, { slots, attrs }) =>
        () =>
            h('label', { class: ['block', attrs.class] }, [
                h(
                    'span',
                    {
                        class: 'mb-1.5 block text-sm font-medium text-slate-700'
                    },
                    `${props.label}${props.required ? ' *' : ''}`
                ),
                slots.default?.()
            ])
})
const toast = useToast()
const confirm = useConfirm()
const loading = ref(false)
const saving = ref(false)
const locating = ref(false)
const error = ref('')
const logoFile = ref<File | null>(null)
const logoPreview = ref('')
const logoInput = ref<HTMLInputElement | null>(null)
const form = reactive<any>({
    name: '',
    branch_code: '',
    contact_number: '',
    email: '',
    address: '',
    barangay: '',
    city: '',
    province: 'Cavite',
    latitude: null,
    longitude: null,
    geofence_enabled: false,
    geofence_radius_m: 100,
    logo_url: '',
    opening_hours: { days: [], schedule: {} },
    suspension_today: null,
    verification: { documents: [] }
})
const storeInfoDialogVisible = ref(false)
const operatingHoursDialogVisible = ref(false)
const geolocationDialogVisible = ref(false)
const documentsDialogVisible = ref(false)
const suspensionDialogVisible = ref(false)
const suspensionHistoryDialogVisible = ref(false)
const savingSuspension = ref(false)
const loadingSuspensionHistory = ref(false)
const suspensionHistory = ref<any[]>([])
const newSuspension = reactive({
    reason: '',
    start_date: '',
    end_date: null as string | null
})
const operatingDayOptions = [
    'monday',
    'tuesday',
    'wednesday',
    'thursday',
    'friday',
    'saturday',
    'sunday'
].map(value => ({ value, label: value[0].toUpperCase() + value.slice(1) }))
const defaultFulfillmentWindows = {
    pickup: { days: operatingDayOptions.slice(0, 6).map(day => day.value), start: '09:00', end: '18:00', lead_days: 1 },
    delivery: { days: operatingDayOptions.slice(0, 6).map(day => day.value), start: '08:00', end: '20:00', lead_days: 2 }
}
const fulfillmentWindows = reactive({
    pickup: { ...defaultFulfillmentWindows.pickup, days: [...defaultFulfillmentWindows.pickup.days] },
    delivery: { ...defaultFulfillmentWindows.delivery, days: [...defaultFulfillmentWindows.delivery.days] }
})
const fulfillmentDraft = reactive({
    pickup: { ...fulfillmentWindows.pickup, days: [...fulfillmentWindows.pickup.days] },
    delivery: { ...fulfillmentWindows.delivery, days: [...fulfillmentWindows.delivery.days] }
})
const fulfillmentEditorVisible = ref(false)
const fulfillmentEditorMethod = ref<'pickup' | 'delivery'>('pickup')
const savingFulfillmentWindows = ref(false)
const operatingHoursDraft = reactive<{
    schedule: Record<
        string,
        { enabled: boolean; opens_at: string; closes_at: string }
    >
}>({ schedule: {} })
const branchDocuments = computed(() => form.verification?.documents || [])
const formSnapshot = ref<any>(null)
const dateValue = (value: string | null) =>
    value ? new Date(`${value}T00:00:00`) : null
const dateString = (value: Date | null) =>
    value instanceof Date && !Number.isNaN(value.getTime())
        ? `${value.getFullYear()}-${String(value.getMonth() + 1).padStart(
              2,
              '0'
          )}-${String(value.getDate()).padStart(2, '0')}`
        : null
const newSuspensionStartModel = computed({
    get: () => dateValue(newSuspension.start_date),
    set: (value: Date | null) => {
        newSuspension.start_date = dateString(value) || ''
    }
})
const newSuspensionEndModel = computed({
    get: () => dateValue(newSuspension.end_date),
    set: (value: Date | null) => {
        newSuspension.end_date = dateString(value)
    }
})
const provinceId = ref('')
const cityId = ref('')
const provinces = ref<any[]>([])
const cities = ref<any[]>([])
const barangays = ref<any[]>([])
const loadingCities = ref(false)
const loadingBarangays = ref(false)
const searchQuery = ref('')
const searching = ref(false)
const mapError = ref('')
const mapContainer = ref<HTMLElement | null>(null)
const geolocationAddress = ref('')
const geocodingLocationAddress = ref(false)
let addressLookupRequest = 0
const locationStatus = ref<'unchecked' | 'checking' | 'valid' | 'invalid'>(
    'unchecked'
)
const locationError = ref('')
let locationRequest = 0
const normalize = (value: unknown) =>
    String(value ?? '')
        .trim()
        .toLowerCase()
const cityOptions = computed(() =>
    cities.value.map((item: any) => ({
        label: item.name || item.city_name,
        value: String(item.city_id || item.code || item.id || '')
    }))
)
const barangayOptions = computed(() => {
    const options = barangays.value.map((item: any) => ({
        label: item.name || item.barangay_name,
        value: item.name || item.barangay_name
    }))
    if (
        form.barangay &&
        !options.some(
            item => normalize(item.value) === normalize(form.barangay)
        )
    )
        options.unshift({ label: form.barangay, value: form.barangay })
    return options
})
let map: MapboxMap | null = null
let marker: MapboxMarker | null = null
let mapboxgl: typeof import('mapbox-gl').default | null = null
const applyData = (value: any) =>
    Object.assign(form, value, {
        latitude: value.latitude === null ? null : Number(value.latitude),
        longitude: value.longitude === null ? null : Number(value.longitude),
        geofence_radius_m: Math.max(50, Math.min(500, Number(value.geofence_radius_m) || 50))
    })
const responseItems = (response: any) => {
    const data = response?.data?.data ?? response?.data ?? []
    return Array.isArray(data) ? data : []
}
const loadAddresses = async () => {
    provinces.value = responseItems(await ecommerceService.getProvinces())
    const province =
        provinces.value.find(
            (item: any) =>
                normalize(item.name || item.province_name) ===
                normalize(form.province)
        ) ||
        provinces.value.find(
            (item: any) =>
                normalize(item.name || item.province_name) === 'cavite'
        )
    provinceId.value = String(
        province?.province_id || province?.code || province?.id || ''
    )
    if (!provinceId.value) return
    loadingCities.value = true
    try {
        cities.value = responseItems(
            await ecommerceService.getCities(provinceId.value)
        )
    } finally {
        loadingCities.value = false
    }
    const city = cities.value.find(
        (item: any) =>
            normalize(item.name || item.city_name) === normalize(form.city)
    )
    cityId.value = String(city?.city_id || city?.code || city?.id || '')
    if (cityId.value) await loadBarangays(cityId.value)
}
const loadBarangays = async (id: string) => {
    loadingBarangays.value = true
    try {
        barangays.value = responseItems(await ecommerceService.getBarangays(id))
    } finally {
        loadingBarangays.value = false
    }
}
const onCityChange = async () => {
    const selected = cityOptions.value.find(item => item.value === cityId.value)
    form.city = selected?.label || ''
    form.barangay = ''
    barangays.value = []
    if (cityId.value) await loadBarangays(cityId.value)
}
const load = async () => {
    loading.value = true
    error.value = ''
    try {
        applyData((await storeModuleService.getSettings()).data)
        applyFulfillmentWindows(form.fulfillment_windows)
        if (form.latitude !== null && form.longitude !== null) {
            await resolveGeolocationAddress(Number(form.latitude), Number(form.longitude))
        } else {
            geolocationAddress.value = ''
        }
        await loadAddresses()
    } catch (err: any) {
        error.value =
            err?.response?.data?.message || 'Unable to load store settings.'
    } finally {
        loading.value = false
    }
}
const resolveGeolocationAddress = async (latitude: number, longitude: number) => {
    const requestId = ++addressLookupRequest
    geocodingLocationAddress.value = true
    geolocationAddress.value = 'Looking up address...'
    try {
        const address = await reverseGeocodeMapbox(latitude, longitude)
        if (requestId === addressLookupRequest) geolocationAddress.value = address || 'Address unavailable'
    } catch {
        if (requestId === addressLookupRequest) geolocationAddress.value = 'Address unavailable'
    } finally {
        if (requestId === addressLookupRequest) geocodingLocationAddress.value = false
    }
}
const timeAsDate = (value: string) => {
    const [hours, minutes] = value.split(':').map(Number)
    if (!Number.isInteger(hours) || !Number.isInteger(minutes)) return null
    const date = new Date()
    date.setHours(hours, minutes, 0, 0)
    return date
}
const timeAsString = (value: Date | null) =>
    value instanceof Date && !Number.isNaN(value.getTime())
        ? `${String(value.getHours()).padStart(2, '0')}:${String(
              value.getMinutes()
          ).padStart(2, '0')}`
        : ''
const applyFulfillmentWindows = (value: any = {}) => {
    for (const method of ['pickup', 'delivery'] as const) {
        const source = value?.[method] || defaultFulfillmentWindows[method]
        fulfillmentWindows[method].days = Array.isArray(source.days) ? [...source.days] : [...defaultFulfillmentWindows[method].days]
        fulfillmentWindows[method].start = source.start || defaultFulfillmentWindows[method].start
        fulfillmentWindows[method].end = source.end || defaultFulfillmentWindows[method].end
        fulfillmentWindows[method].lead_days = Number(source.lead_days ?? defaultFulfillmentWindows[method].lead_days)
    }
}
const fulfillmentTimeModels = {
    pickup: {
        start: computed({ get: () => timeAsDate(fulfillmentDraft.pickup.start), set: (value: Date | null) => { fulfillmentDraft.pickup.start = timeAsString(value) } }),
        end: computed({ get: () => timeAsDate(fulfillmentDraft.pickup.end), set: (value: Date | null) => { fulfillmentDraft.pickup.end = timeAsString(value) } })
    },
    delivery: {
        start: computed({ get: () => timeAsDate(fulfillmentDraft.delivery.start), set: (value: Date | null) => { fulfillmentDraft.delivery.start = timeAsString(value) } }),
        end: computed({ get: () => timeAsDate(fulfillmentDraft.delivery.end), set: (value: Date | null) => { fulfillmentDraft.delivery.end = timeAsString(value) } })
    }
}
const formatWindowSummary = (window: { days: string[]; start: string; end: string; lead_days: number }) => {
    const days = window.days.map(day => day.slice(0, 3).replace(/^./, letter => letter.toUpperCase())).join(', ')
    return `${days || 'No days selected'} | ${timeAsDate(window.start)?.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }) || '—'}–${timeAsDate(window.end)?.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }) || '—'} | ${window.lead_days} day${window.lead_days === 1 ? '' : 's'} notice`
}
const openFulfillmentEditor = (method: 'pickup' | 'delivery') => {
    for (const key of ['pickup', 'delivery'] as const) {
        fulfillmentDraft[key].days = [...fulfillmentWindows[key].days]
        fulfillmentDraft[key].start = fulfillmentWindows[key].start
        fulfillmentDraft[key].end = fulfillmentWindows[key].end
        fulfillmentDraft[key].lead_days = fulfillmentWindows[key].lead_days
    }
    fulfillmentEditorMethod.value = method
    fulfillmentEditorVisible.value = true
}
const cancelFulfillmentEditor = () => {
    fulfillmentEditorVisible.value = false
}
const confirmSaveFulfillmentWindows = () => {
    const invalid = (['pickup', 'delivery'] as const).some(method => {
        const window = fulfillmentDraft[method]
        return !window.days.length || !window.start || !window.end || window.end <= window.start || window.lead_days < 0 || window.lead_days > 14
    })
    if (invalid) return toast.add({ severity: 'warn', summary: 'Check service windows', detail: 'Select at least one day and ensure each end time is later than its start time. Lead time must be 0–14 days.', life: 3500 })
    confirm.require({ header: 'Save fulfillment settings?', message: `Apply the updated ${fulfillmentEditorMethod.value} availability settings?`, icon: 'pi pi-question-circle', rejectProps: { label: 'Cancel', severity: 'secondary', outlined: true }, acceptProps: { label: 'Save settings', severity: 'warn' }, accept: submitFulfillmentWindows })
}
const submitFulfillmentWindows = async () => {
    savingFulfillmentWindows.value = true
    try {
        const response = await storeModuleService.updateFulfillmentWindows(fulfillmentDraft)
        applyFulfillmentWindows(response.data)
        form.fulfillment_windows = response.data
        fulfillmentEditorVisible.value = false
        toast.add({ severity: 'success', summary: 'Service windows saved', detail: response.message, life: 3000 })
    } catch (err: any) {
        toast.add({ severity: 'error', summary: 'Unable to save service windows', detail: err?.response?.data?.message || 'Please check the fields and try again.', life: 4000 })
    } finally {
        savingFulfillmentWindows.value = false
    }
}
const operatingTimeModels: Record<string, any> = Object.fromEntries(
    operatingDayOptions.map(({ value }) => [
        value,
        {
            opens_at: computed({
                get: () =>
                    timeAsDate(
                        operatingHoursDraft.schedule[value]?.opens_at || '08:00'
                    ),
                set: (time: Date | null) => {
                    operatingHoursDraft.schedule[value].opens_at =
                        timeAsString(time)
                }
            }),
            closes_at: computed({
                get: () =>
                    timeAsDate(
                        operatingHoursDraft.schedule[value]?.closes_at ||
                            '18:00'
                    ),
                set: (time: Date | null) => {
                    operatingHoursDraft.schedule[value].closes_at =
                        timeAsString(time)
                }
            })
        }
    ])
)
const formatOperatingHours = (row?: {
    opens_at?: string
    closes_at?: string
}) =>
    row?.opens_at && row?.closes_at
        ? `${timeAsDate(row.opens_at)?.toLocaleTimeString([], {
              hour: 'numeric',
              minute: '2-digit'
          })}–${timeAsDate(row.closes_at)?.toLocaleTimeString([], {
              hour: 'numeric',
              minute: '2-digit'
          })}`
        : 'Closed'
const formatDocumentDate = (value?: string | null) =>
    value
        ? new Date(value).toLocaleDateString('en-PH', {
              year: 'numeric',
              month: 'short',
              day: 'numeric'
          })
        : 'Not provided'
const openStoreInfoDialog = () => {
    formSnapshot.value = JSON.parse(JSON.stringify(form))
    storeInfoDialogVisible.value = true
}
const cancelStoreInfoDialog = () => {
    if (formSnapshot.value) applyData(formSnapshot.value)
    storeInfoDialogVisible.value = false
    void loadAddresses()
}
const openOperatingHoursDialog = () => {
    const openingHours = form.opening_hours || {}
    const savedSchedule = openingHours.schedule || {}
    operatingHoursDraft.schedule = Object.fromEntries(
        operatingDayOptions.map(({ value }) => {
            const saved = savedSchedule[value]
            const enabled =
                (openingHours.days || []).includes(value) || Boolean(saved)
            return [
                value,
                {
                    enabled,
                    opens_at:
                        saved?.opens_at || openingHours.opens_at || '08:00',
                    closes_at:
                        saved?.closes_at || openingHours.closes_at || '18:00'
                }
            ]
        })
    )
    operatingHoursDialogVisible.value = true
}
const applyDefaultHours = (day: string) => {
    if (operatingHoursDraft.schedule[day]?.enabled)
        Object.assign(operatingHoursDraft.schedule[day], {
            opens_at: '08:00',
            closes_at: '18:00'
        })
}
const openingHoursPayload = () => {
    const active = Object.fromEntries(
        Object.entries(operatingHoursDraft.schedule)
            .filter(([, hours]) => hours.enabled)
            .map(([day, hours]) => [
                day,
                { day, opens_at: hours.opens_at, closes_at: hours.closes_at }
            ])
    )
    return { days: Object.keys(active), schedule: active }
}
const openGeolocationDialog = () => {
    formSnapshot.value = JSON.parse(JSON.stringify(form))
    locationStatus.value = 'unchecked'
    locationError.value = ''
    geolocationDialogVisible.value = true
}
const cancelGeolocationDialog = async () => {
    if (formSnapshot.value) applyData(formSnapshot.value)
    if (form.latitude !== null && form.longitude !== null) {
        await resolveGeolocationAddress(Number(form.latitude), Number(form.longitude))
    } else {
        geolocationAddress.value = ''
    }
    geolocationDialogVisible.value = false
    locationStatus.value = 'unchecked'
    redrawMap(true)
}
const handleGeolocationDialogShow = async () => {
    await nextTick()
    if (!map) await initMap()
    else {
        map.resize()
        redrawMap(true)
    }
}
const handleLogoSelected = async (event: Event) => {
    const input = event.target as HTMLInputElement
    const file = input.files?.[0]
    input.value = ''
    if (!file) return
    if (
        !['image/png', 'image/jpeg', 'image/webp'].includes(file.type) ||
        file.size > 4 * 1024 * 1024
    )
        return toast.add({
            severity: 'warn',
            summary: 'Unsupported logo',
            detail: 'Choose a PNG, JPG, or WebP image up to 4 MB.',
            life: 3500
        })
    logoFile.value = file
    if (logoPreview.value) URL.revokeObjectURL(logoPreview.value)
    logoPreview.value = URL.createObjectURL(file)
    await submitSettings('logo')
}
const setLocation = async (latitude: number, longitude: number) => {
    const requestId = ++locationRequest
    locationStatus.value = 'checking'
    locationError.value = ''
    try {
        const response = await axiosClient.post(
            '/api/stores/validate-location',
            { latitude, longitude },
            { headers: { 'X-Suppress-Dialog': '1' } }
        )
        if (requestId !== locationRequest) return false
        if (!response.data?.valid) {
            locationStatus.value = 'invalid'
            locationError.value =
                response.data?.message ||
                'Choose a location inside Cavite province.'
            redrawMap(true)
            return false
        }
        form.latitude = latitude
        form.longitude = longitude
        await resolveGeolocationAddress(latitude, longitude)
        locationStatus.value = 'valid'
        redrawMap(true)
        return true
    } catch (err: any) {
        if (requestId !== locationRequest) return false
        locationStatus.value = 'invalid'
        locationError.value =
            err?.response?.data?.message ||
            'Could not validate the location. Try again.'
        redrawMap(true)
        return false
    }
}
const useCurrentLocation = () => {
    if (!navigator.geolocation)
        return toast.add({
            severity: 'warn',
            summary: 'Location unavailable',
            detail: 'This browser does not support geolocation.',
            life: 3000
        })
    locating.value = true
    navigator.geolocation.getCurrentPosition(
        position => {
            locating.value = false
            void setLocation(
                position.coords.latitude,
                position.coords.longitude
            )
        },
        () => {
            locating.value = false
            toast.add({
                severity: 'error',
                summary: 'Location unavailable',
                detail: 'Allow location access and try again.',
                life: 3500
            })
        },
        { enableHighAccuracy: true, timeout: 15000 }
    )
}
const geofenceData = () => {
    const lat = Number(form.latitude),
        lng = Number(form.longitude),
        radius = Number(form.geofence_radius_m)
    if (
        form.latitude === null ||
        form.latitude === '' ||
        form.longitude === null ||
        form.longitude === '' ||
        !Number.isFinite(lat) ||
        !Number.isFinite(lng) ||
        radius <= 0
    )
        return { type: 'FeatureCollection' as const, features: [] }
    const ring: number[][] = [],
        angular = radius / 6371008.8,
        latRad = (lat * Math.PI) / 180,
        lngRad = (lng * Math.PI) / 180
    for (let step = 0; step <= 64; step++) {
        const bearing = (step / 64) * Math.PI * 2
        const pointLat = Math.asin(
            Math.sin(latRad) * Math.cos(angular) +
                Math.cos(latRad) * Math.sin(angular) * Math.cos(bearing)
        )
        const pointLng =
            lngRad +
            Math.atan2(
                Math.sin(bearing) * Math.sin(angular) * Math.cos(latRad),
                Math.cos(angular) - Math.sin(latRad) * Math.sin(pointLat)
            )
        ring.push([(pointLng * 180) / Math.PI, (pointLat * 180) / Math.PI])
    }
    return {
        type: 'Feature' as const,
        properties: {},
        geometry: { type: 'Polygon' as const, coordinates: [ring] }
    }
}
const redrawMap = (recenter = false) => {
    if (!map || !mapboxgl) return
    const lat = Number(form.latitude) || 14.2794,
        lng = Number(form.longitude) || 120.8786
    if (!marker) {
        marker = new mapboxgl.Marker({ draggable: true })
            .setLngLat([lng, lat])
            .addTo(map)
        marker.on('dragend', () => {
            const point = marker!.getLngLat()
            void setLocation(point.lat, point.lng)
        })
    } else {
        marker.setLngLat([lng, lat])
    }
    ;(map.getSource('store-geofence') as GeoJSONSource | undefined)?.setData(
        geofenceData()
    )
    if (recenter) map.easeTo({ center: [lng, lat], zoom: 14 })
    setTimeout(() => map?.resize(), 100)
}
const initMap = async () => {
    if (!mapContainer.value || map) return
    try {
        mapError.value = ''
        mapboxgl = (await import('mapbox-gl')).default
        mapboxgl.accessToken = requireMapboxToken()
        map = new mapboxgl.Map({
            container: mapContainer.value,
            style: 'mapbox://styles/mapbox/streets-v12',
            center: [
                Number(form.longitude) || 120.8786,
                Number(form.latitude) || 14.2794
            ],
            zoom: 14
        })
        map.on('load', () => {
            map!.addSource('store-geofence', {
                type: 'geojson',
                data: geofenceData()
            })
            map!.addLayer({
                id: 'store-geofence-fill',
                type: 'fill',
                source: 'store-geofence',
                paint: { 'fill-color': '#f97316', 'fill-opacity': 0.16 }
            })
            map!.addLayer({
                id: 'store-geofence-outline',
                type: 'line',
                source: 'store-geofence',
                paint: { 'line-color': '#f97316', 'line-width': 2 }
            })
            redrawMap(true)
        })
        map.on('click', event => {
            void setLocation(event.lngLat.lat, event.lngLat.lng)
        })
        map.on('error', event => {
            mapError.value =
                event.error?.message || 'Mapbox could not load the map.'
        })
    } catch (err: any) {
        mapError.value = err?.message || 'Unable to load the Mapbox map.'
    }
}
const searchLocation = async () => {
    if (!searchQuery.value.trim()) return
    searching.value = true
    try {
        const result = await forwardGeocodeMapbox(searchQuery.value.trim())
        if (!result)
            return toast.add({
                severity: 'warn',
                summary: 'Location not found',
                detail: 'Try a more specific address.',
                life: 3000
            })
        if (
            (await setLocation(result.latitude, result.longitude)) &&
            result.address
        )
            form.address = result.address
    } catch (err: any) {
        toast.add({
            severity: 'error',
            summary: 'Mapbox search failed',
            detail: err?.message || 'Unable to search this location.',
            life: 4000
        })
    } finally {
        searching.value = false
    }
}
const confirmSaveStoreInfo = () => {
    if (
        !form.name.trim() ||
        !form.contact_number.trim() ||
        !form.address.trim() ||
        !form.city.trim()
    )
        return toast.add({
            severity: 'warn',
            summary: 'Complete required information',
            detail: 'Name, contact number, address, and city are required.',
            life: 3500
        })
    confirm.require({
        header: 'Save store information?',
        message: 'Apply these branch information changes?',
        icon: 'pi pi-question-circle',
        rejectProps: { label: 'Cancel', severity: 'secondary', outlined: true },
        acceptProps: { label: 'Save information', severity: 'warn' },
        accept: () => submitSettings('information')
    })
}
const confirmSaveOperatingHours = () => {
    const active = Object.values(operatingHoursDraft.schedule).filter(
        hours => hours.enabled
    )
    if (
        !active.length ||
        active.some(
            hours =>
                !hours.opens_at ||
                !hours.closes_at ||
                hours.closes_at <= hours.opens_at
        )
    )
        return toast.add({
            severity: 'warn',
            summary: 'Check operating hours',
            detail: 'Enable at least one day and make each closing time later than its opening time.',
            life: 3500
        })
    confirm.require({
        header: 'Save operating hours?',
        message: 'Apply these hours to this branch?',
        icon: 'pi pi-question-circle',
        rejectProps: { label: 'Cancel', severity: 'secondary', outlined: true },
        acceptProps: { label: 'Save hours', severity: 'warn' },
        accept: () => submitSettings('hours', openingHoursPayload())
    })
}
const confirmSaveGeolocation = async () => {
    if (
        form.latitude === null ||
        form.longitude === null ||
        form.latitude === '' ||
        form.longitude === ''
    )
        return toast.add({
            severity: 'warn',
            summary: 'Choose a map location',
            detail: 'Set a pin before saving geolocation.',
            life: 3000
        })
    if (!(await setLocation(Number(form.latitude), Number(form.longitude))))
        return
    if (form.geofence_enabled && (!form.latitude || !form.longitude))
        return toast.add({
            severity: 'warn',
            summary: 'Location required',
            detail: 'Set a valid location before enabling the geofence.',
            life: 3000
        })
    confirm.require({
        header: 'Save geolocation?',
        message: 'Apply this pin and geofence setting to the branch?',
        icon: 'pi pi-map-marker',
        rejectProps: { label: 'Cancel', severity: 'secondary', outlined: true },
        acceptProps: { label: 'Save location', severity: 'warn' },
        accept: () => submitSettings('geolocation')
    })
}
const openSuspensionDialog = () => {
    newSuspension.reason = ''
    newSuspension.start_date = dateString(new Date())!
    newSuspension.end_date = null
    suspensionDialogVisible.value = true
}
const loadSuspensionHistory = async () => {
    loadingSuspensionHistory.value = true
    try {
        suspensionHistory.value =
            (await storeModuleService.getSuspensionHistory()).data || []
    } catch (err: any) {
        toast.add({
            severity: 'error',
            summary: 'Unable to load history',
            detail: err?.response?.data?.message || 'Try again.',
            life: 3500
        })
    } finally {
        loadingSuspensionHistory.value = false
    }
}
const openSuspensionHistory = () => {
    suspensionHistoryDialogVisible.value = true
    void loadSuspensionHistory()
}
const suspensionStatus = (row: any) => {
    const today = dateString(new Date())!
    return row.start_date > today
        ? 'Scheduled'
        : !row.end_date || row.end_date >= today
        ? 'Active today'
        : 'Ended'
}
const suspensionSeverity = (row: any) =>
    ((
        {
            Scheduled: 'info',
            'Active today': 'danger',
            Ended: 'secondary'
        } as Record<string, string>
    )[suspensionStatus(row)] || 'secondary')
const confirmCreateSuspension = () => {
    if (
        !newSuspension.start_date ||
        (newSuspension.end_date &&
            newSuspension.end_date < newSuspension.start_date)
    )
        return toast.add({
            severity: 'warn',
            summary: 'Check suspension dates',
            detail: 'Choose a start date and make the end date on or after it.',
            life: 3500
        })
    confirm.require({
        header: 'Create branch suspension?',
        message:
            'This branch will not be selected for ecommerce pickup or delivery during these dates.',
        icon: 'pi pi-exclamation-triangle',
        rejectProps: { label: 'Cancel', severity: 'secondary', outlined: true },
        acceptProps: { label: 'Create suspension', severity: 'warn' },
        accept: createSuspension
    })
}
const createSuspension = async () => {
    savingSuspension.value = true
    try {
        const response = await storeModuleService.createSuspension({
            ...newSuspension
        })
        suspensionDialogVisible.value = false
        await load()
        await loadSuspensionHistory()
        toast.add({
            severity: 'success',
            summary: 'Suspension created',
            detail: response.message,
            life: 3000
        })
    } catch (err: any) {
        toast.add({
            severity: 'error',
            summary: 'Unable to create suspension',
            detail:
                err?.response?.data?.message ||
                'Check the dates and try again.',
            life: 4000
        })
    } finally {
        savingSuspension.value = false
    }
}
const submitSettings = async (
    kind: 'information' | 'hours' | 'geolocation' | 'logo',
    openingHours?: any
) => {
    saving.value = true
    try {
        const payload = new FormData()
        ;[
            'name',
            'branch_code',
            'contact_number',
            'email',
            'address',
            'barangay',
            'city',
            'province',
            'latitude',
            'longitude',
            'geofence_radius_m'
        ].forEach(key => payload.append(key, form[key] ?? ''))
        payload.append('geofence_enabled', form.geofence_enabled ? '1' : '0')
        if (openingHours) {
            if (!openingHours.days.length)
                payload.append('opening_hours[clear]', '1')
            openingHours.days.forEach((day: string, index: number) =>
                payload.append(`opening_hours[days][${index}]`, day)
            )
            Object.entries(openingHours.schedule).forEach(
                ([day, hours]: [string, any]) =>
                    Object.entries(hours).forEach(([field, value]) =>
                        payload.append(
                            `opening_hours[schedule][${day}][${field}]`,
                            String(value)
                        )
                    )
            )
        }
        if (logoFile.value) payload.append('logo', logoFile.value)
        const response = await storeModuleService.updateSettings(payload)
        applyData(response.data)
        if (logoFile.value) {
            logoFile.value = null
            if (logoPreview.value) URL.revokeObjectURL(logoPreview.value)
            logoPreview.value = ''
        }
        storeInfoDialogVisible.value = false
        operatingHoursDialogVisible.value = false
        geolocationDialogVisible.value = false
        if (kind === 'information') await loadAddresses()
        if (kind === 'logo') await load()
        toast.add({
            severity: 'success',
            summary: 'Branch settings saved',
            detail: response.message,
            life: 3000
        })
    } catch (err: any) {
        toast.add({
            severity: 'error',
            summary: 'Unable to save',
            detail:
                err?.response?.data?.message ||
                'Check the information and try again.',
            life: 4000
        })
    } finally {
        saving.value = false
    }
}
const destroyMap = () => {
    map?.remove()
    map = null
    marker = null
    mapboxgl = null
}
watch(() => [form.latitude, form.longitude, form.geofence_radius_m], redrawMap)
onMounted(load)
onBeforeUnmount(() => {
    destroyMap()
    if (logoPreview.value) URL.revokeObjectURL(logoPreview.value)
})
</script>
