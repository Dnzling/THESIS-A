"use strict";
var __assign = (this && this.__assign) || function () {
    __assign = Object.assign || function(t) {
        for (var s, i = 1, n = arguments.length; i < n; i++) {
            s = arguments[i];
            for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p))
                t[p] = s[p];
        }
        return t;
    };
    return __assign.apply(this, arguments);
};
var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
var __generator = (this && this.__generator) || function (thisArg, body) {
    var _ = { label: 0, sent: function() { if (t[0] & 1) throw t[1]; return t[1]; }, trys: [], ops: [] }, f, y, t, g = Object.create((typeof Iterator === "function" ? Iterator : Object).prototype);
    return g.next = verb(0), g["throw"] = verb(1), g["return"] = verb(2), typeof Symbol === "function" && (g[Symbol.iterator] = function() { return this; }), g;
    function verb(n) { return function (v) { return step([n, v]); }; }
    function step(op) {
        if (f) throw new TypeError("Generator is already executing.");
        while (g && (g = 0, op[0] && (_ = 0)), _) try {
            if (f = 1, y && (t = op[0] & 2 ? y["return"] : op[0] ? y["throw"] || ((t = y["return"]) && t.call(y), 0) : y.next) && !(t = t.call(y, op[1])).done) return t;
            if (y = 0, t) op = [op[0] & 2, t.value];
            switch (op[0]) {
                case 0: case 1: t = op; break;
                case 4: _.label++; return { value: op[1], done: false };
                case 5: _.label++; y = op[1]; op = [0]; continue;
                case 7: op = _.ops.pop(); _.trys.pop(); continue;
                default:
                    if (!(t = _.trys, t = t.length > 0 && t[t.length - 1]) && (op[0] === 6 || op[0] === 2)) { _ = 0; continue; }
                    if (op[0] === 3 && (!t || (op[1] > t[0] && op[1] < t[3]))) { _.label = op[1]; break; }
                    if (op[0] === 6 && _.label < t[1]) { _.label = t[1]; t = op; break; }
                    if (t && _.label < t[2]) { _.label = t[2]; _.ops.push(op); break; }
                    if (t[2]) _.ops.pop();
                    _.trys.pop(); continue;
            }
            op = body.call(thisArg, _);
        } catch (e) { op = [6, e]; y = 0; } finally { f = t = 0; }
        if (op[0] & 5) throw op[1]; return { value: op[0] ? op[1] : void 0, done: true };
    }
};
var __spreadArray = (this && this.__spreadArray) || function (to, from, pack) {
    if (pack || arguments.length === 2) for (var i = 0, l = from.length, ar; i < l; i++) {
        if (ar || !(i in from)) {
            if (!ar) ar = Array.prototype.slice.call(from, 0, i);
            ar[i] = from[i];
        }
    }
    return to.concat(ar || Array.prototype.slice.call(from));
};
var _a, _b;
Object.defineProperty(exports, "__esModule", { value: true });
var vue_1 = require("vue");
var ReviewSection_vue_1 = require("../shared/ReviewSection.vue");
var InfoItem_vue_1 = require("../shared/InfoItem.vue");
var FilePreview_vue_1 = require("../shared/FilePreview.vue");
var usetoast_1 = require("primevue/usetoast");
var auth_1 = require("../../../stores/auth");
var axios_1 = require("axios");
var toast = (0, usetoast_1.useToast)();
var authStore = (0, auth_1.useAuthStore)();
var isSubmitting = (0, vue_1.ref)(false);
var storeId = (0, vue_1.ref)();
var props = defineProps();
var emit = defineEmits();
// City map for formatting
var cityMap = {
    'bacoor': 'Bacoor',
    'carmona': 'Carmona',
    'cavite-city': 'Cavite City',
    'dasmarinas': 'Dasmarinañas',
    'general-mariano-alvarez': 'General Mariano Alvarez',
    'general-trias': 'General Trias',
    'imus': 'Imus',
    'indang': 'Indang',
    'kawit': 'Kawit',
    'magallanes': 'Magallanes',
    'mendez': 'Mendez',
    'naic': 'Naic',
    'novaleta': 'Noveleta',
    'rosario': 'Rosario',
    'silang': 'Silang',
    'tagaytay': 'Tagaytay City',
    'tanza': 'Tanza',
    'ternate': 'Ternate',
    'trece-martires': 'Trece Martires City'
};
// Local form data
var localForm = (0, vue_1.ref)(__assign(__assign({}, props.formData), { termsAccepted: props.formData.termsAccepted || false, privacyAccepted: props.formData.privacyAccepted || false }));
// Validation
var isStepValid = (0, vue_1.computed)(function () {
    return localForm.value.termsAccepted && localForm.value.privacyAccepted;
});
// Format functions
var formatBusinessType = function (type) {
    var types = {
        retail: 'Retail Store',
        restaurant: 'Restaurant',
        service: 'Service Provider',
        wholesale: 'Wholesale',
        online: 'Online Store',
        other: 'Other'
    };
    return types[type] || type || 'Not provided';
};
var formattedAddress = (0, vue_1.computed)(function () {
    var addr = localForm.value.businessAddress;
    if (!addr)
        return 'Not provided';
    var cityName = cityMap[addr.city] || addr.city;
    var parts = [];
    if (addr.address)
        parts.push(addr.address);
    if (cityName)
        parts.push(cityName);
    parts.push('Cavite');
    return parts.join(', ') || 'Not provided';
});
var formattedCoordinates = (0, vue_1.computed)(function () {
    var addr = localForm.value.businessAddress;
    if (!addr)
        return 'Not set';
    if (addr.latitude && addr.longitude) {
        return "".concat(addr.latitude, ", ").concat(addr.longitude);
    }
    return 'Not set';
});
var formatIdType = function (type) {
    var types = {
        umid: 'UMID',
        driver: "Driver's License",
        passport: 'Passport',
        national: 'National ID'
    };
    return types[type] || type || 'Not provided';
};
// Map frontend ID type to backend format
var mapIdType = function (frontendType) {
    var mapping = {
        'umid': 'umid',
        'driver': 'driver_license',
        'passport': 'passport',
        'national': 'national_id'
    };
    return mapping[frontendType] || 'other';
};
// Submit registration
var submitRegistration = function () { return __awaiter(void 0, void 0, void 0, function () {
    var formData, payload, storeResponse, verifyResponse, updateUser, error_1, rollbackError_1, errors, firstError;
    var _a, _b, _c, _d, _e, _f, _g, _h;
    return __generator(this, function (_j) {
        switch (_j.label) {
            case 0:
                if (!isStepValid.value)
                    return [2 /*return*/];
                isSubmitting.value = true;
                _j.label = 1;
            case 1:
                _j.trys.push([1, 5, 10, 11]);
                formData = new FormData();
                // Step 1: Store Information
                formData.append('business_registration_number', localForm.value.businessNumber);
                // Step 2: Owner Identification
                formData.append('gov_id_type', mapIdType(localForm.value.primaryIdType));
                formData.append('gov_id_number', localForm.value.primaryIdNumber || '');
                // Step 3: Documents - Append files if they exist
                if (localForm.value.registrationPermit instanceof File) {
                    formData.append('business_registration_file', localForm.value.registrationPermit);
                }
                if (localForm.value.idFront instanceof File) {
                    formData.append('gov_id_front_file', localForm.value.idFront);
                }
                if (localForm.value.idBack instanceof File) {
                    formData.append('gov_id_back_file', localForm.value.idBack);
                }
                if (localForm.value.selfiePhoto instanceof File) {
                    formData.append('selfie_with_id_file', localForm.value.selfiePhoto);
                }
                if (localForm.value.taxCertificate instanceof File) {
                    formData.append('tax_certificate_file', localForm.value.taxCertificate);
                }
                if (localForm.value.mayorPermit instanceof File) {
                    formData.append('business_permit_file', localForm.value.mayorPermit);
                }
                payload = {
                    store_name: localForm.value.storeName,
                    business_type: localForm.value.businessType,
                    business_registration_number: localForm.value.businessNumber,
                    contact_number: localForm.value.contactNumber,
                    email: localForm.value.email,
                    address: ((_a = localForm.value.businessAddress) === null || _a === void 0 ? void 0 : _a.address) || '',
                    city: ((_b = localForm.value.businessAddress) === null || _b === void 0 ? void 0 : _b.city) || '',
                    province: 'Cavite',
                    latitude: ((_c = localForm.value.businessAddress) === null || _c === void 0 ? void 0 : _c.latitude) || '',
                    longitude: ((_d = localForm.value.businessAddress) === null || _d === void 0 ? void 0 : _d.longitude) || '',
                    contact_person: authStore.user.fname + ' ' + authStore.user.lname
                };
                return [4 /*yield*/, axios_1.default.post('api/stores/register', payload, {
                        headers: {
                            'Authorization': "Bearer ".concat(authStore.token),
                            'Content-Type': 'application/json'
                        }
                    })];
            case 2:
                storeResponse = _j.sent();
                storeId.value = storeResponse.data.store.store_id;
                return [4 /*yield*/, axios_1.default.post("api/stores/".concat(storeId.value, "/verification/submit"), formData, {
                        headers: {
                            'Content-Type': 'multipart/form-data'
                        }
                    })
                    // Update store_id for user
                ];
            case 3:
                verifyResponse = _j.sent();
                return [4 /*yield*/, axios_1.default.put("api/users/".concat(authStore.user.id), {
                        store_id: storeId.value
                    }, {
                        headers: {
                            'Authorization': "Bearer ".concat(authStore.token)
                        }
                    })];
            case 4:
                updateUser = _j.sent();
                // 4. Handle success
                toast.add({
                    severity: 'success',
                    summary: 'Registration Submitted!',
                    detail: verifyResponse.data.message || 'Your store registration is under review.',
                    life: 5000
                });
                // 5. Emit success to parent
                emit('submit');
                return [3 /*break*/, 11];
            case 5:
                error_1 = _j.sent();
                console.error('Submission error:', error_1);
                _j.label = 6;
            case 6:
                _j.trys.push([6, 8, , 9]);
                return [4 /*yield*/, axios_1.default.delete("/api/stores/".concat(storeId.value), {
                        headers: {
                            'Authorization': "Bearer ".concat(authStore.token)
                        }
                    })];
            case 7:
                _j.sent();
                return [3 /*break*/, 9];
            case 8:
                rollbackError_1 = _j.sent();
                console.error('Failed to rollback store creation:', rollbackError_1);
                return [3 /*break*/, 9];
            case 9:
                if (((_e = error_1.response) === null || _e === void 0 ? void 0 : _e.status) === 422) {
                    errors = error_1.response.data.errors;
                    firstError = Object.values(errors)[0];
                    toast.add({
                        severity: 'error',
                        summary: 'Validation Error',
                        detail: Array.isArray(firstError) ? firstError[0] : 'Please check your input',
                        life: 5000
                    });
                }
                else if (((_f = error_1.response) === null || _f === void 0 ? void 0 : _f.status) === 403) {
                    toast.add({
                        severity: 'error',
                        summary: 'Permission Denied',
                        detail: 'You do not have permission to submit verification for this store.',
                        life: 5000
                    });
                }
                else {
                    toast.add({
                        severity: 'error',
                        summary: 'Submission Failed',
                        detail: ((_h = (_g = error_1.response) === null || _g === void 0 ? void 0 : _g.data) === null || _h === void 0 ? void 0 : _h.message) || 'Please try again later',
                        life: 3000
                    });
                }
                return [3 /*break*/, 11];
            case 10:
                isSubmitting.value = false;
                return [7 /*endfinally*/];
            case 11: return [2 /*return*/];
        }
    });
}); };
var __VLS_ctx = __assign(__assign(__assign(__assign(__assign({}, {}), {}), {}), {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.h2, __VLS_intrinsics.h2)(__assign({ class: "text-2xl font-bold text-gray-800 mb-6" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-8" }));
/** @type {__VLS_StyleScopedClasses['space-y-8']} */ ;
var __VLS_0 = ReviewSection_vue_1.default || ReviewSection_vue_1.default;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ 'onEdit': {} }, { title: "Store Information", canEdit: (true) })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ 'onEdit': {} }, { title: "Store Information", canEdit: (true) })], __VLS_functionalComponentArgsRest(__VLS_1), false));
var __VLS_5;
var __VLS_6 = ({ edit: {} },
    { onEdit: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.$emit('edit-step', 1);
            // @ts-ignore
            [$emit,];
        } });
var __VLS_7 = __VLS_3.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-2 gap-4" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
/** @type {__VLS_StyleScopedClasses['md:grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
var __VLS_8 = InfoItem_vue_1.default;
// @ts-ignore
var __VLS_9 = __VLS_asFunctionalComponent1(__VLS_8, new __VLS_8({
    label: "Store Name",
    value: (__VLS_ctx.formData.storeName),
}));
var __VLS_10 = __VLS_9.apply(void 0, __spreadArray([{
        label: "Store Name",
        value: (__VLS_ctx.formData.storeName),
    }], __VLS_functionalComponentArgsRest(__VLS_9), false));
var __VLS_13 = InfoItem_vue_1.default;
// @ts-ignore
var __VLS_14 = __VLS_asFunctionalComponent1(__VLS_13, new __VLS_13({
    label: "Business Type",
    value: (__VLS_ctx.formatBusinessType(__VLS_ctx.formData.businessType)),
}));
var __VLS_15 = __VLS_14.apply(void 0, __spreadArray([{
        label: "Business Type",
        value: (__VLS_ctx.formatBusinessType(__VLS_ctx.formData.businessType)),
    }], __VLS_functionalComponentArgsRest(__VLS_14), false));
var __VLS_18 = InfoItem_vue_1.default;
// @ts-ignore
var __VLS_19 = __VLS_asFunctionalComponent1(__VLS_18, new __VLS_18({
    label: "Business Number",
    value: (__VLS_ctx.formData.businessNumber),
}));
var __VLS_20 = __VLS_19.apply(void 0, __spreadArray([{
        label: "Business Number",
        value: (__VLS_ctx.formData.businessNumber),
    }], __VLS_functionalComponentArgsRest(__VLS_19), false));
var __VLS_23 = InfoItem_vue_1.default;
// @ts-ignore
var __VLS_24 = __VLS_asFunctionalComponent1(__VLS_23, new __VLS_23({
    label: "Contact Number",
    value: (__VLS_ctx.formData.contactNumber),
}));
var __VLS_25 = __VLS_24.apply(void 0, __spreadArray([{
        label: "Contact Number",
        value: (__VLS_ctx.formData.contactNumber),
    }], __VLS_functionalComponentArgsRest(__VLS_24), false));
var __VLS_28 = InfoItem_vue_1.default;
// @ts-ignore
var __VLS_29 = __VLS_asFunctionalComponent1(__VLS_28, new __VLS_28(__assign({ label: "Email", value: (__VLS_ctx.formData.email) }, { class: "md:col-span-2" })));
var __VLS_30 = __VLS_29.apply(void 0, __spreadArray([__assign({ label: "Email", value: (__VLS_ctx.formData.email) }, { class: "md:col-span-2" })], __VLS_functionalComponentArgsRest(__VLS_29), false));
/** @type {__VLS_StyleScopedClasses['md:col-span-2']} */ ;
var __VLS_33 = InfoItem_vue_1.default;
// @ts-ignore
var __VLS_34 = __VLS_asFunctionalComponent1(__VLS_33, new __VLS_33(__assign({ label: "Business Address", value: (__VLS_ctx.formattedAddress) }, { class: "md:col-span-2" })));
var __VLS_35 = __VLS_34.apply(void 0, __spreadArray([__assign({ label: "Business Address", value: (__VLS_ctx.formattedAddress) }, { class: "md:col-span-2" })], __VLS_functionalComponentArgsRest(__VLS_34), false));
/** @type {__VLS_StyleScopedClasses['md:col-span-2']} */ ;
if (((_a = __VLS_ctx.formData.businessAddress) === null || _a === void 0 ? void 0 : _a.latitude) || ((_b = __VLS_ctx.formData.businessAddress) === null || _b === void 0 ? void 0 : _b.longitude)) {
    var __VLS_38 = InfoItem_vue_1.default;
    // @ts-ignore
    var __VLS_39 = __VLS_asFunctionalComponent1(__VLS_38, new __VLS_38(__assign({ label: "Coordinates", value: (__VLS_ctx.formattedCoordinates) }, { class: "md:col-span-2" })));
    var __VLS_40 = __VLS_39.apply(void 0, __spreadArray([__assign({ label: "Coordinates", value: (__VLS_ctx.formattedCoordinates) }, { class: "md:col-span-2" })], __VLS_functionalComponentArgsRest(__VLS_39), false));
    /** @type {__VLS_StyleScopedClasses['md:col-span-2']} */ ;
}
// @ts-ignore
[formData, formData, formData, formData, formData, formData, formData, formatBusinessType, formattedAddress, formattedCoordinates,];
var __VLS_3;
var __VLS_4;
var __VLS_43 = ReviewSection_vue_1.default || ReviewSection_vue_1.default;
// @ts-ignore
var __VLS_44 = __VLS_asFunctionalComponent1(__VLS_43, new __VLS_43(__assign({ 'onEdit': {} }, { title: "Owner Identification", canEdit: (true) })));
var __VLS_45 = __VLS_44.apply(void 0, __spreadArray([__assign({ 'onEdit': {} }, { title: "Owner Identification", canEdit: (true) })], __VLS_functionalComponentArgsRest(__VLS_44), false));
var __VLS_48;
var __VLS_49 = ({ edit: {} },
    { onEdit: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.$emit('edit-step', 2);
            // @ts-ignore
            [$emit,];
        } });
var __VLS_50 = __VLS_46.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
/** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
var __VLS_51 = InfoItem_vue_1.default;
// @ts-ignore
var __VLS_52 = __VLS_asFunctionalComponent1(__VLS_51, new __VLS_51({
    label: "Primary ID Type",
    value: (__VLS_ctx.formatIdType(__VLS_ctx.formData.primaryIdType)),
}));
var __VLS_53 = __VLS_52.apply(void 0, __spreadArray([{
        label: "Primary ID Type",
        value: (__VLS_ctx.formatIdType(__VLS_ctx.formData.primaryIdType)),
    }], __VLS_functionalComponentArgsRest(__VLS_52), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-3 gap-4" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
/** @type {__VLS_StyleScopedClasses['md:grid-cols-3']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
var __VLS_56 = FilePreview_vue_1.default;
// @ts-ignore
var __VLS_57 = __VLS_asFunctionalComponent1(__VLS_56, new __VLS_56({
    file: (__VLS_ctx.formData.idFront),
    label: "ID Front",
}));
var __VLS_58 = __VLS_57.apply(void 0, __spreadArray([{
        file: (__VLS_ctx.formData.idFront),
        label: "ID Front",
    }], __VLS_functionalComponentArgsRest(__VLS_57), false));
var __VLS_61 = FilePreview_vue_1.default;
// @ts-ignore
var __VLS_62 = __VLS_asFunctionalComponent1(__VLS_61, new __VLS_61({
    file: (__VLS_ctx.formData.idBack),
    label: "ID Back",
}));
var __VLS_63 = __VLS_62.apply(void 0, __spreadArray([{
        file: (__VLS_ctx.formData.idBack),
        label: "ID Back",
    }], __VLS_functionalComponentArgsRest(__VLS_62), false));
var __VLS_66 = FilePreview_vue_1.default;
// @ts-ignore
var __VLS_67 = __VLS_asFunctionalComponent1(__VLS_66, new __VLS_66({
    file: (__VLS_ctx.formData.selfiePhoto),
    label: "Selfie with ID",
}));
var __VLS_68 = __VLS_67.apply(void 0, __spreadArray([{
        file: (__VLS_ctx.formData.selfiePhoto),
        label: "Selfie with ID",
    }], __VLS_functionalComponentArgsRest(__VLS_67), false));
// @ts-ignore
[formData, formData, formData, formData, formatIdType,];
var __VLS_46;
var __VLS_47;
var __VLS_71 = ReviewSection_vue_1.default || ReviewSection_vue_1.default;
// @ts-ignore
var __VLS_72 = __VLS_asFunctionalComponent1(__VLS_71, new __VLS_71(__assign({ 'onEdit': {} }, { title: "Business Documents", canEdit: (true) })));
var __VLS_73 = __VLS_72.apply(void 0, __spreadArray([__assign({ 'onEdit': {} }, { title: "Business Documents", canEdit: (true) })], __VLS_functionalComponentArgsRest(__VLS_72), false));
var __VLS_76;
var __VLS_77 = ({ edit: {} },
    { onEdit: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.$emit('edit-step', 3);
            // @ts-ignore
            [$emit,];
        } });
var __VLS_78 = __VLS_74.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-3 gap-4" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
/** @type {__VLS_StyleScopedClasses['md:grid-cols-3']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
var __VLS_79 = FilePreview_vue_1.default;
// @ts-ignore
var __VLS_80 = __VLS_asFunctionalComponent1(__VLS_79, new __VLS_79({
    file: (__VLS_ctx.formData.registrationPermit),
    label: "Registration Permit",
}));
var __VLS_81 = __VLS_80.apply(void 0, __spreadArray([{
        file: (__VLS_ctx.formData.registrationPermit),
        label: "Registration Permit",
    }], __VLS_functionalComponentArgsRest(__VLS_80), false));
var __VLS_84 = FilePreview_vue_1.default;
// @ts-ignore
var __VLS_85 = __VLS_asFunctionalComponent1(__VLS_84, new __VLS_84({
    file: (__VLS_ctx.formData.taxCertificate),
    label: "Tax Certificate",
}));
var __VLS_86 = __VLS_85.apply(void 0, __spreadArray([{
        file: (__VLS_ctx.formData.taxCertificate),
        label: "Tax Certificate",
    }], __VLS_functionalComponentArgsRest(__VLS_85), false));
var __VLS_89 = FilePreview_vue_1.default;
// @ts-ignore
var __VLS_90 = __VLS_asFunctionalComponent1(__VLS_89, new __VLS_89({
    file: (__VLS_ctx.formData.mayorPermit),
    label: "Mayor's Permit",
}));
var __VLS_91 = __VLS_90.apply(void 0, __spreadArray([{
        file: (__VLS_ctx.formData.mayorPermit),
        label: "Mayor's Permit",
    }], __VLS_functionalComponentArgsRest(__VLS_90), false));
if (__VLS_ctx.formData.additionalNotes) {
    var __VLS_94 = InfoItem_vue_1.default;
    // @ts-ignore
    var __VLS_95 = __VLS_asFunctionalComponent1(__VLS_94, new __VLS_94({
        label: "Additional Notes",
        value: (__VLS_ctx.formData.additionalNotes),
    }));
    var __VLS_96 = __VLS_95.apply(void 0, __spreadArray([{
            label: "Additional Notes",
            value: (__VLS_ctx.formData.additionalNotes),
        }], __VLS_functionalComponentArgsRest(__VLS_95), false));
}
// @ts-ignore
[formData, formData, formData, formData, formData,];
var __VLS_74;
var __VLS_75;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "border border-gray-200 rounded-lg p-6" }));
/** @type {__VLS_StyleScopedClasses['border']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-200']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['p-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-lg font-semibold text-gray-800 mb-4" }));
/** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
/** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "flex items-start" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-start']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.input)(__assign(__assign({ type: "checkbox" }, { class: "mt-1 mr-3" }), { required: true }));
(__VLS_ctx.localForm.termsAccepted);
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
/** @type {__VLS_StyleScopedClasses['mr-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.a, __VLS_intrinsics.a)(__assign({ href: "#" }, { class: "text-blue-600 hover:underline" }));
/** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
/** @type {__VLS_StyleScopedClasses['hover:underline']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "flex items-start" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-start']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.input)(__assign(__assign({ type: "checkbox" }, { class: "mt-1 mr-3" }), { required: true }));
(__VLS_ctx.localForm.privacyAccepted);
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
/** @type {__VLS_StyleScopedClasses['mr-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.a, __VLS_intrinsics.a)(__assign({ href: "#" }, { class: "text-blue-600 hover:underline" }));
/** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
/** @type {__VLS_StyleScopedClasses['hover:underline']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-end pt-6 border-t border-gray-200" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-end']} */ ;
/** @type {__VLS_StyleScopedClasses['pt-6']} */ ;
/** @type {__VLS_StyleScopedClasses['border-t']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-200']} */ ;
var __VLS_99;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_100 = __VLS_asFunctionalComponent1(__VLS_99, new __VLS_99(__assign(__assign(__assign({ 'onClick': {} }, { type: "button" }), { class: "w-1/5" }), { severity: "success", label: "Submit Registration", disabled: (!__VLS_ctx.isStepValid || __VLS_ctx.isSubmitting) })));
var __VLS_101 = __VLS_100.apply(void 0, __spreadArray([__assign(__assign(__assign({ 'onClick': {} }, { type: "button" }), { class: "w-1/5" }), { severity: "success", label: "Submit Registration", disabled: (!__VLS_ctx.isStepValid || __VLS_ctx.isSubmitting) })], __VLS_functionalComponentArgsRest(__VLS_100), false));
var __VLS_104;
var __VLS_105 = ({ click: {} },
    { onClick: (__VLS_ctx.submitRegistration) });
/** @type {__VLS_StyleScopedClasses['w-1/5']} */ ;
var __VLS_102;
var __VLS_103;
var __VLS_106;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_107 = __VLS_asFunctionalComponent1(__VLS_106, new __VLS_106(__assign({ visible: (__VLS_ctx.isSubmitting), modal: true, closable: (false), showHeader: (false) }, { style: ({ width: '300px' }) })));
var __VLS_108 = __VLS_107.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.isSubmitting), modal: true, closable: (false), showHeader: (false) }, { style: ({ width: '300px' }) })], __VLS_functionalComponentArgsRest(__VLS_107), false));
var __VLS_111 = __VLS_109.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col items-center justify-center p-6" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
/** @type {__VLS_StyleScopedClasses['p-6']} */ ;
var __VLS_112;
/** @ts-ignore @type {typeof __VLS_components.ProgressSpinner} */
ProgressSpinner;
// @ts-ignore
var __VLS_113 = __VLS_asFunctionalComponent1(__VLS_112, new __VLS_112(__assign({ style: {} }, { strokeWidth: "4", fill: "transparent", animationDuration: ".5s" })));
var __VLS_114 = __VLS_113.apply(void 0, __spreadArray([__assign({ style: {} }, { strokeWidth: "4", fill: "transparent", animationDuration: ".5s" })], __VLS_functionalComponentArgsRest(__VLS_113), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "mt-4 text-lg font-medium text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
/** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
// @ts-ignore
[localForm, localForm, isStepValid, isSubmitting, isSubmitting, submitRegistration,];
var __VLS_109;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({
    __typeEmits: {},
    __typeProps: {},
});
exports.default = {};
