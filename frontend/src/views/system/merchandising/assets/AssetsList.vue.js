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
var _a, _b, _c;
Object.defineProperty(exports, "__esModule", { value: true });
var vue_1 = require("vue");
var usetoast_1 = require("primevue/usetoast");
var auth_1 = require("../../../../stores/auth");
var merchandising_service_1 = require("../../../../services/merchandising.service");
var vue_router_1 = require("vue-router");
var router = (0, vue_router_1.useRouter)();
var toast = (0, usetoast_1.useToast)();
var authStore = (0, auth_1.useAuthStore)();
// State
var assets = (0, vue_1.ref)([]);
var products = (0, vue_1.ref)([]);
var loading = (0, vue_1.ref)(false);
var deleting = (0, vue_1.ref)(false);
var viewDialogVisible = (0, vue_1.ref)(false);
var deleteDialogVisible = (0, vue_1.ref)(false);
var currentAsset = (0, vue_1.ref)(null);
var selectedAssets = (0, vue_1.ref)([]);
var searchQuery = (0, vue_1.ref)('');
var viewMode = (0, vue_1.ref)('grid');
var filters = (0, vue_1.reactive)({
    asset_type: null,
    product_id: null
});
// ✅ Only non-3D asset types
var assetTypes = [
    { label: 'Main Images', value: 'Image_Main' },
    { label: 'Gallery Images', value: 'Image_Gallery' },
    { label: 'Videos', value: 'Video_Product' },
    { label: 'PDFs/Manuals', value: 'Manual_PDF' }
];
// Computed
var assetStats = (0, vue_1.computed)(function () {
    var stats = [
        {
            type: 'Image',
            label: 'Images',
            icon: 'pi pi-image',
            iconColor: 'text-blue-600',
            bgColor: 'bg-blue-100',
            count: 0,
            totalSize: 0
        },
        {
            type: 'Video',
            label: 'Videos',
            icon: 'pi pi-video',
            iconColor: 'text-purple-600',
            bgColor: 'bg-purple-100',
            count: 0,
            totalSize: 0
        },
        {
            type: 'PDF',
            label: 'PDFs',
            icon: 'pi pi-file-pdf',
            iconColor: 'text-red-600',
            bgColor: 'bg-red-100',
            count: 0,
            totalSize: 0
        },
        {
            type: 'Total',
            label: 'Total Assets',
            icon: 'pi pi-folder',
            iconColor: 'text-gray-600',
            bgColor: 'bg-gray-100',
            count: assets.value.length,
            totalSize: assets.value.reduce(function (sum, asset) { return sum + (asset.file_size_kb * 1024); }, 0)
        }
    ];
    assets.value.forEach(function (asset) {
        if (asset.asset_type.includes('Image')) {
            stats[0].count++;
            stats[0].totalSize += asset.file_size_kb * 1024;
        }
        else if (asset.asset_type === 'Video_Product') {
            stats[1].count++;
            stats[1].totalSize += asset.file_size_kb * 1024;
        }
        else if (asset.asset_type === 'Manual_PDF') {
            stats[2].count++;
            stats[2].totalSize += asset.file_size_kb * 1024;
        }
    });
    return stats;
});
// ✅ Load images with authentication
var loadImageWithAuth = function (asset) { return __awaiter(void 0, void 0, void 0, function () {
    var token, response, blob, error_1;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                if (!asset.url)
                    return [2 /*return*/, null];
                _a.label = 1;
            case 1:
                _a.trys.push([1, 4, , 5]);
                token = authStore.token || localStorage.getItem('auth_token');
                return [4 /*yield*/, fetch(asset.url, {
                        headers: {
                            'Authorization': "Bearer ".concat(token),
                            'Accept': 'image/*'
                        }
                    })];
            case 2:
                response = _a.sent();
                if (!response.ok) {
                    throw new Error("HTTP ".concat(response.status));
                }
                return [4 /*yield*/, response.blob()];
            case 3:
                blob = _a.sent();
                return [2 /*return*/, URL.createObjectURL(blob)];
            case 4:
                error_1 = _a.sent();
                console.error('Failed to load image:', error_1);
                return [2 /*return*/, null];
            case 5: return [2 /*return*/];
        }
    });
}); };
// Methods
var loadAssets = function () { return __awaiter(void 0, void 0, void 0, function () {
    var params, response, _i, _a, asset, _b, error_2;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                loading.value = true;
                _c.label = 1;
            case 1:
                _c.trys.push([1, 7, 8, 9]);
                params = __assign(__assign({}, filters), { 
                    // ✅ Exclude 3D models from this list
                    exclude_type: '3D_Model' });
                if (searchQuery.value)
                    params.search = searchQuery.value;
                return [4 /*yield*/, merchandising_service_1.default.getAssets(params)
                    // ✅ Filter out 3D models on frontend as backup
                ];
            case 2:
                response = _c.sent();
                // ✅ Filter out 3D models on frontend as backup
                assets.value = response.data.data.filter(function (asset) { return asset.asset_type !== '3D_Model'; });
                _i = 0, _a = assets.value;
                _c.label = 3;
            case 3:
                if (!(_i < _a.length)) return [3 /*break*/, 6];
                asset = _a[_i];
                if (!asset.asset_type.includes('Image')) return [3 /*break*/, 5];
                _b = asset;
                return [4 /*yield*/, loadImageWithAuth(asset)];
            case 4:
                _b.auth_url = _c.sent();
                _c.label = 5;
            case 5:
                _i++;
                return [3 /*break*/, 3];
            case 6: return [3 /*break*/, 9];
            case 7:
                error_2 = _c.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Failed to load assets',
                    life: 3000
                });
                return [3 /*break*/, 9];
            case 8:
                loading.value = false;
                return [7 /*endfinally*/];
            case 9: return [2 /*return*/];
        }
    });
}); };
var loadProducts = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, error_3;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                _a.trys.push([0, 2, , 3]);
                return [4 /*yield*/, merchandising_service_1.default.getProducts({ per_page: 1000 })];
            case 1:
                response = _a.sent();
                products.value = response.data.data;
                return [3 /*break*/, 3];
            case 2:
                error_3 = _a.sent();
                console.error('Failed to load products:', error_3);
                return [3 /*break*/, 3];
            case 3: return [2 /*return*/];
        }
    });
}); };
var onSearch = function () {
    loadAssets();
};
var toggleViewMode = function () {
    viewMode.value = viewMode.value === 'grid' ? 'list' : 'grid';
};
var viewAsset = function (asset) { return __awaiter(void 0, void 0, void 0, function () {
    var _a;
    return __generator(this, function (_b) {
        switch (_b.label) {
            case 0:
                currentAsset.value = __assign({}, asset);
                if (!(asset.asset_type.includes('Image') && !asset.auth_url)) return [3 /*break*/, 2];
                _a = currentAsset.value;
                return [4 /*yield*/, loadImageWithAuth(asset)];
            case 1:
                _a.auth_url = _b.sent();
                _b.label = 2;
            case 2:
                viewDialogVisible.value = true;
                return [2 /*return*/];
        }
    });
}); };
var downloadAsset = function (asset) {
    window.open(asset.url, '_blank');
    toast.add({
        severity: 'success',
        summary: 'Download Started',
        detail: "Downloading ".concat(asset.file_name),
        life: 2000
    });
};
var confirmDelete = function (asset) {
    currentAsset.value = asset;
    deleteDialogVisible.value = true;
};
var deleteAsset = function () { return __awaiter(void 0, void 0, void 0, function () {
    var error_4;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                deleting.value = true;
                _c.label = 1;
            case 1:
                _c.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, merchandising_service_1.default.deleteAsset(currentAsset.value.id)];
            case 2:
                _c.sent();
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Asset deleted successfully',
                    life: 3000
                });
                deleteDialogVisible.value = false;
                loadAssets();
                return [3 /*break*/, 5];
            case 3:
                error_4 = _c.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_b = (_a = error_4.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to delete asset',
                    life: 3000
                });
                return [3 /*break*/, 5];
            case 4:
                deleting.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var bulkDeleteAssets = function () { return __awaiter(void 0, void 0, void 0, function () {
    var _i, _a, assetId, error_5;
    return __generator(this, function (_b) {
        switch (_b.label) {
            case 0:
                if (selectedAssets.value.length === 0)
                    return [2 /*return*/];
                _b.label = 1;
            case 1:
                _b.trys.push([1, 6, , 7]);
                _i = 0, _a = selectedAssets.value;
                _b.label = 2;
            case 2:
                if (!(_i < _a.length)) return [3 /*break*/, 5];
                assetId = _a[_i];
                return [4 /*yield*/, merchandising_service_1.default.deleteAsset(assetId)];
            case 3:
                _b.sent();
                _b.label = 4;
            case 4:
                _i++;
                return [3 /*break*/, 2];
            case 5:
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: "".concat(selectedAssets.value.length, " assets deleted"),
                    life: 3000
                });
                selectedAssets.value = [];
                loadAssets();
                return [3 /*break*/, 7];
            case 6:
                error_5 = _b.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Failed to delete some assets',
                    life: 3000
                });
                return [3 /*break*/, 7];
            case 7: return [2 /*return*/];
        }
    });
}); };
var handleImageError = function (event) {
    var img = event.target;
    img.src = '/placeholder-image.png';
};
var getAssetTypeLabel = function (type) {
    var labels = {
        'Image_Main': 'Main Image',
        'Image_Gallery': 'Gallery',
        'Video_Product': 'Video',
        'Manual_PDF': 'PDF'
    };
    return labels[type] || type;
};
var getAssetTypeSeverity = function (type) {
    var severities = {
        'Image_Main': 'success',
        'Image_Gallery': 'primary',
        'Video_Product': 'warning',
        'Manual_PDF': 'danger'
    };
    return severities[type] || 'secondary';
};
var getAssetIcon = function (type) {
    var icons = {
        'Image_Main': 'pi pi-image',
        'Image_Gallery': 'pi pi-images',
        'Video_Product': 'pi pi-video',
        'Manual_PDF': 'pi pi-file-pdf'
    };
    return icons[type] || 'pi pi-file';
};
var formatFileSize = function (bytes) {
    if (bytes === 0)
        return '0 Bytes';
    var k = 1024;
    var sizes = ['Bytes', 'KB', 'MB', 'GB'];
    var i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};
var formatDate = function (date) {
    if (!date)
        return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};
(0, vue_1.onMounted)(function () {
    loadProducts();
    loadAssets();
});
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-6" }));
/** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:flex-row']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.h2, __VLS_intrinsics.h2)(__assign({ class: "text-2xl font-bold text-gray-800" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
var __VLS_0;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ 'onClick': {} }, { label: "3D Models Gallery", icon: "pi pi-cube", severity: "info", outlined: true })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "3D Models Gallery", icon: "pi pi-cube", severity: "info", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_1), false));
var __VLS_5;
var __VLS_6 = ({ click: {} },
    { onClick: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.router.push({ name: 'merchandising.3d-gallery' });
            // @ts-ignore
            [router,];
        } });
var __VLS_3;
var __VLS_4;
if (__VLS_ctx.authStore.hasPermission('merchandising.assets.upload')) {
    var __VLS_7 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_8 = __VLS_asFunctionalComponent1(__VLS_7, new __VLS_7(__assign({ 'onClick': {} }, { label: "Upload Assets", icon: "pi pi-cloud-upload" })));
    var __VLS_9 = __VLS_8.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Upload Assets", icon: "pi pi-cloud-upload" })], __VLS_functionalComponentArgsRest(__VLS_8), false));
    var __VLS_12 = void 0;
    var __VLS_13 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                if (!(__VLS_ctx.authStore.hasPermission('merchandising.assets.upload')))
                    return;
                __VLS_ctx.router.push({ name: 'merchandising.assets.upload' });
                // @ts-ignore
                [router, authStore,];
            } });
    var __VLS_10;
    var __VLS_11;
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-4 gap-4" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
/** @type {__VLS_StyleScopedClasses['md:grid-cols-4']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
for (var _i = 0, _d = __VLS_vFor((__VLS_ctx.assetStats)); _i < _d.length; _i++) {
    var stat = _d[_i][0];
    var __VLS_14 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    Card;
    // @ts-ignore
    var __VLS_15 = __VLS_asFunctionalComponent1(__VLS_14, new __VLS_14(__assign({ key: (stat.type) }, { class: "hover:shadow-lg transition-shadow" })));
    var __VLS_16 = __VLS_15.apply(void 0, __spreadArray([__assign({ key: (stat.type) }, { class: "hover:shadow-lg transition-shadow" })], __VLS_functionalComponentArgsRest(__VLS_15), false));
    /** @type {__VLS_StyleScopedClasses['hover:shadow-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['transition-shadow']} */ ;
    var __VLS_19 = __VLS_17.slots.default;
    {
        var __VLS_20 = __VLS_17.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center" }));
        /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: ([stat.bgColor, 'inline-flex p-4 rounded-full mb-3']) }));
        /** @type {__VLS_StyleScopedClasses['inline-flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: ([stat.icon, stat.iconColor, 'text-2xl']) }));
        /** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        (stat.label);
        __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-2xl font-bold text-gray-900 mt-1" }));
        /** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
        (stat.count);
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500 mt-1" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
        (__VLS_ctx.formatFileSize(stat.totalSize));
        // @ts-ignore
        [assetStats, formatFileSize,];
    }
    // @ts-ignore
    [];
    var __VLS_17;
    // @ts-ignore
    [];
}
var __VLS_21;
/** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
Card;
// @ts-ignore
var __VLS_22 = __VLS_asFunctionalComponent1(__VLS_21, new __VLS_21({}));
var __VLS_23 = __VLS_22.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_22), false));
var __VLS_26 = __VLS_24.slots.default;
{
    var __VLS_27 = __VLS_24.slots.content;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-4 gap-4" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['md:grid-cols-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    var __VLS_28 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.IconField | typeof __VLS_components.IconField} */
    IconField;
    // @ts-ignore
    var __VLS_29 = __VLS_asFunctionalComponent1(__VLS_28, new __VLS_28({}));
    var __VLS_30 = __VLS_29.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_29), false));
    var __VLS_33 = __VLS_31.slots.default;
    var __VLS_34 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputIcon} */
    InputIcon;
    // @ts-ignore
    var __VLS_35 = __VLS_asFunctionalComponent1(__VLS_34, new __VLS_34(__assign({ class: "pi pi-search" })));
    var __VLS_36 = __VLS_35.apply(void 0, __spreadArray([__assign({ class: "pi pi-search" })], __VLS_functionalComponentArgsRest(__VLS_35), false));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-search']} */ ;
    var __VLS_39 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputText} */
    InputText;
    // @ts-ignore
    var __VLS_40 = __VLS_asFunctionalComponent1(__VLS_39, new __VLS_39(__assign(__assign({ 'onInput': {} }, { modelValue: (__VLS_ctx.searchQuery), placeholder: "Search assets..." }), { class: "w-full" })));
    var __VLS_41 = __VLS_40.apply(void 0, __spreadArray([__assign(__assign({ 'onInput': {} }, { modelValue: (__VLS_ctx.searchQuery), placeholder: "Search assets..." }), { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_40), false));
    var __VLS_44 = void 0;
    var __VLS_45 = ({ input: {} },
        { onInput: (__VLS_ctx.onSearch) });
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    var __VLS_42;
    var __VLS_43;
    // @ts-ignore
    [searchQuery, onSearch,];
    var __VLS_31;
    var __VLS_46 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select} */
    Select;
    // @ts-ignore
    var __VLS_47 = __VLS_asFunctionalComponent1(__VLS_46, new __VLS_46(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.asset_type), options: (__VLS_ctx.assetTypes), optionLabel: "label", optionValue: "value", placeholder: "All Types", showClear: true })));
    var __VLS_48 = __VLS_47.apply(void 0, __spreadArray([__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.asset_type), options: (__VLS_ctx.assetTypes), optionLabel: "label", optionValue: "value", placeholder: "All Types", showClear: true })], __VLS_functionalComponentArgsRest(__VLS_47), false));
    var __VLS_51 = void 0;
    var __VLS_52 = ({ change: {} },
        { onChange: (__VLS_ctx.loadAssets) });
    var __VLS_49;
    var __VLS_50;
    var __VLS_53 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select} */
    Select;
    // @ts-ignore
    var __VLS_54 = __VLS_asFunctionalComponent1(__VLS_53, new __VLS_53(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.product_id), options: (__VLS_ctx.products), optionLabel: "product_name", optionValue: "id", placeholder: "All Products", showClear: true, filter: true })));
    var __VLS_55 = __VLS_54.apply(void 0, __spreadArray([__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.product_id), options: (__VLS_ctx.products), optionLabel: "product_name", optionValue: "id", placeholder: "All Products", showClear: true, filter: true })], __VLS_functionalComponentArgsRest(__VLS_54), false));
    var __VLS_58 = void 0;
    var __VLS_59 = ({ change: {} },
        { onChange: (__VLS_ctx.loadAssets) });
    var __VLS_56;
    var __VLS_57;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    var __VLS_60 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_61 = __VLS_asFunctionalComponent1(__VLS_60, new __VLS_60(__assign(__assign({ 'onClick': {} }, { label: (__VLS_ctx.viewMode === 'grid' ? 'Grid' : 'List'), icon: (__VLS_ctx.viewMode === 'grid' ? 'pi pi-th-large' : 'pi pi-list'), severity: (__VLS_ctx.viewMode === 'grid' ? 'primary' : 'secondary'), outlined: true }), { class: "flex-1" })));
    var __VLS_62 = __VLS_61.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { label: (__VLS_ctx.viewMode === 'grid' ? 'Grid' : 'List'), icon: (__VLS_ctx.viewMode === 'grid' ? 'pi pi-th-large' : 'pi pi-list'), severity: (__VLS_ctx.viewMode === 'grid' ? 'primary' : 'secondary'), outlined: true }), { class: "flex-1" })], __VLS_functionalComponentArgsRest(__VLS_61), false));
    var __VLS_65 = void 0;
    var __VLS_66 = ({ click: {} },
        { onClick: (__VLS_ctx.toggleViewMode) });
    /** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
    var __VLS_63;
    var __VLS_64;
    var __VLS_67 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_68 = __VLS_asFunctionalComponent1(__VLS_67, new __VLS_67(__assign({ 'onClick': {} }, { label: "Bulk Delete", icon: "pi pi-trash", severity: "danger", outlined: true, disabled: (__VLS_ctx.selectedAssets.length === 0) })));
    var __VLS_69 = __VLS_68.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Bulk Delete", icon: "pi pi-trash", severity: "danger", outlined: true, disabled: (__VLS_ctx.selectedAssets.length === 0) })], __VLS_functionalComponentArgsRest(__VLS_68), false));
    var __VLS_72 = void 0;
    var __VLS_73 = ({ click: {} },
        { onClick: (__VLS_ctx.bulkDeleteAssets) });
    var __VLS_70;
    var __VLS_71;
    // @ts-ignore
    [filters, filters, assetTypes, loadAssets, loadAssets, products, viewMode, viewMode, viewMode, toggleViewMode, selectedAssets, bulkDeleteAssets,];
}
// @ts-ignore
[];
var __VLS_24;
if (__VLS_ctx.loading) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['md:grid-cols-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['lg:grid-cols-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    for (var _e = 0, _f = __VLS_vFor((8)); _e < _f.length; _e++) {
        var i = _f[_e][0];
        var __VLS_74 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Skeleton} */
        Skeleton;
        // @ts-ignore
        var __VLS_75 = __VLS_asFunctionalComponent1(__VLS_74, new __VLS_74(__assign({ key: (i), height: "250px" }, { class: "rounded-lg" })));
        var __VLS_76 = __VLS_75.apply(void 0, __spreadArray([__assign({ key: (i), height: "250px" }, { class: "rounded-lg" })], __VLS_functionalComponentArgsRest(__VLS_75), false));
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        // @ts-ignore
        [loading,];
    }
}
else if (__VLS_ctx.viewMode === 'grid' && __VLS_ctx.assets.length > 0) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['md:grid-cols-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['lg:grid-cols-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    var _loop_1 = function (asset) {
        var __VLS_79 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
        Card;
        // @ts-ignore
        var __VLS_80 = __VLS_asFunctionalComponent1(__VLS_79, new __VLS_79(__assign(__assign({ 'onClick': {} }, { key: (asset.id) }), { class: "hover:shadow-lg transition-shadow cursor-pointer" })));
        var __VLS_81 = __VLS_80.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { key: (asset.id) }), { class: "hover:shadow-lg transition-shadow cursor-pointer" })], __VLS_functionalComponentArgsRest(__VLS_80), false));
        var __VLS_84 = void 0;
        var __VLS_85 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!!(__VLS_ctx.loading))
                        return;
                    if (!(__VLS_ctx.viewMode === 'grid' && __VLS_ctx.assets.length > 0))
                        return;
                    __VLS_ctx.viewAsset(asset);
                    // @ts-ignore
                    [viewMode, assets, assets, viewAsset,];
                } });
        /** @type {__VLS_StyleScopedClasses['hover:shadow-lg']} */ ;
        /** @type {__VLS_StyleScopedClasses['transition-shadow']} */ ;
        /** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
        var __VLS_86 = __VLS_82.slots.default;
        {
            var __VLS_87 = __VLS_82.slots.content;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-3" }));
            /** @type {__VLS_StyleScopedClasses['space-y-3']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "relative aspect-video bg-gray-100 rounded-lg overflow-hidden group" }));
            /** @type {__VLS_StyleScopedClasses['relative']} */ ;
            /** @type {__VLS_StyleScopedClasses['aspect-video']} */ ;
            /** @type {__VLS_StyleScopedClasses['bg-gray-100']} */ ;
            /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
            /** @type {__VLS_StyleScopedClasses['overflow-hidden']} */ ;
            /** @type {__VLS_StyleScopedClasses['group']} */ ;
            if (asset.asset_type.includes('Image')) {
                __VLS_asFunctionalElement1(__VLS_intrinsics.img)(__assign(__assign({ onError: (__VLS_ctx.handleImageError) }, { src: (asset.auth_url || asset.thumbnail_url || asset.url), alt: (asset.file_name) }), { class: "w-full h-full object-cover" }));
                /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
                /** @type {__VLS_StyleScopedClasses['h-full']} */ ;
                /** @type {__VLS_StyleScopedClasses['object-cover']} */ ;
            }
            else if (asset.asset_type === 'Video_Product') {
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-full h-full flex items-center justify-center bg-gradient-to-br from-purple-50 to-pink-100" }));
                /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
                /** @type {__VLS_StyleScopedClasses['h-full']} */ ;
                /** @type {__VLS_StyleScopedClasses['flex']} */ ;
                /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
                /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
                /** @type {__VLS_StyleScopedClasses['bg-gradient-to-br']} */ ;
                /** @type {__VLS_StyleScopedClasses['from-purple-50']} */ ;
                /** @type {__VLS_StyleScopedClasses['to-pink-100']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-video text-6xl text-purple-600" }));
                /** @type {__VLS_StyleScopedClasses['pi']} */ ;
                /** @type {__VLS_StyleScopedClasses['pi-video']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-6xl']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-purple-600']} */ ;
            }
            else if (asset.asset_type === 'Manual_PDF') {
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-full h-full flex items-center justify-center bg-gradient-to-br from-red-50 to-orange-100" }));
                /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
                /** @type {__VLS_StyleScopedClasses['h-full']} */ ;
                /** @type {__VLS_StyleScopedClasses['flex']} */ ;
                /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
                /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
                /** @type {__VLS_StyleScopedClasses['bg-gradient-to-br']} */ ;
                /** @type {__VLS_StyleScopedClasses['from-red-50']} */ ;
                /** @type {__VLS_StyleScopedClasses['to-orange-100']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-file-pdf text-6xl text-red-600" }));
                /** @type {__VLS_StyleScopedClasses['pi']} */ ;
                /** @type {__VLS_StyleScopedClasses['pi-file-pdf']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-6xl']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-red-600']} */ ;
            }
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-all flex items-center justify-center opacity-0 group-hover:opacity-100" }));
            /** @type {__VLS_StyleScopedClasses['absolute']} */ ;
            /** @type {__VLS_StyleScopedClasses['inset-0']} */ ;
            /** @type {__VLS_StyleScopedClasses['bg-black']} */ ;
            /** @type {__VLS_StyleScopedClasses['bg-opacity-0']} */ ;
            /** @type {__VLS_StyleScopedClasses['group-hover:bg-opacity-50']} */ ;
            /** @type {__VLS_StyleScopedClasses['transition-all']} */ ;
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['opacity-0']} */ ;
            /** @type {__VLS_StyleScopedClasses['group-hover:opacity-100']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
            var __VLS_88 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            Button;
            // @ts-ignore
            var __VLS_89 = __VLS_asFunctionalComponent1(__VLS_88, new __VLS_88(__assign({ 'onClick': {} }, { icon: "pi pi-eye", rounded: true })));
            var __VLS_90 = __VLS_89.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-eye", rounded: true })], __VLS_functionalComponentArgsRest(__VLS_89), false));
            var __VLS_93 = void 0;
            var __VLS_94 = ({ click: {} },
                { onClick: function () {
                        var _a = [];
                        for (var _i = 0; _i < arguments.length; _i++) {
                            _a[_i] = arguments[_i];
                        }
                        var $event = _a[0];
                        if (!!(__VLS_ctx.loading))
                            return;
                        if (!(__VLS_ctx.viewMode === 'grid' && __VLS_ctx.assets.length > 0))
                            return;
                        __VLS_ctx.viewAsset(asset);
                        // @ts-ignore
                        [viewAsset, handleImageError,];
                    } });
            var __VLS_95 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            Button;
            // @ts-ignore
            var __VLS_96 = __VLS_asFunctionalComponent1(__VLS_95, new __VLS_95(__assign({ 'onClick': {} }, { icon: "pi pi-download", rounded: true, severity: "secondary" })));
            var __VLS_97 = __VLS_96.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-download", rounded: true, severity: "secondary" })], __VLS_functionalComponentArgsRest(__VLS_96), false));
            var __VLS_100 = void 0;
            var __VLS_101 = ({ click: {} },
                { onClick: function () {
                        var _a = [];
                        for (var _i = 0; _i < arguments.length; _i++) {
                            _a[_i] = arguments[_i];
                        }
                        var $event = _a[0];
                        if (!!(__VLS_ctx.loading))
                            return;
                        if (!(__VLS_ctx.viewMode === 'grid' && __VLS_ctx.assets.length > 0))
                            return;
                        __VLS_ctx.downloadAsset(asset);
                        // @ts-ignore
                        [downloadAsset,];
                    } });
            if (__VLS_ctx.authStore.hasPermission('merchandising.assets.delete')) {
                var __VLS_102 = void 0;
                /** @ts-ignore @type {typeof __VLS_components.Button} */
                Button;
                // @ts-ignore
                var __VLS_103 = __VLS_asFunctionalComponent1(__VLS_102, new __VLS_102(__assign({ 'onClick': {} }, { icon: "pi pi-trash", rounded: true, severity: "danger" })));
                var __VLS_104 = __VLS_103.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-trash", rounded: true, severity: "danger" })], __VLS_functionalComponentArgsRest(__VLS_103), false));
                var __VLS_107 = void 0;
                var __VLS_108 = ({ click: {} },
                    { onClick: function () {
                            var _a = [];
                            for (var _i = 0; _i < arguments.length; _i++) {
                                _a[_i] = arguments[_i];
                            }
                            var $event = _a[0];
                            if (!!(__VLS_ctx.loading))
                                return;
                            if (!(__VLS_ctx.viewMode === 'grid' && __VLS_ctx.assets.length > 0))
                                return;
                            if (!(__VLS_ctx.authStore.hasPermission('merchandising.assets.delete')))
                                return;
                            __VLS_ctx.confirmDelete(asset);
                            // @ts-ignore
                            [authStore, confirmDelete,];
                        } });
            }
            if (asset.is_primary) {
                var __VLS_109 = void 0;
                /** @ts-ignore @type {typeof __VLS_components.Badge} */
                Badge;
                // @ts-ignore
                var __VLS_110 = __VLS_asFunctionalComponent1(__VLS_109, new __VLS_109(__assign({ value: "Primary", severity: "success" }, { class: "absolute top-2 left-2" })));
                var __VLS_111 = __VLS_110.apply(void 0, __spreadArray([__assign({ value: "Primary", severity: "success" }, { class: "absolute top-2 left-2" })], __VLS_functionalComponentArgsRest(__VLS_110), false));
                /** @type {__VLS_StyleScopedClasses['absolute']} */ ;
                /** @type {__VLS_StyleScopedClasses['top-2']} */ ;
                /** @type {__VLS_StyleScopedClasses['left-2']} */ ;
            }
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ onClick: function () { } }, { class: "absolute top-2 right-2" }));
            /** @type {__VLS_StyleScopedClasses['absolute']} */ ;
            /** @type {__VLS_StyleScopedClasses['top-2']} */ ;
            /** @type {__VLS_StyleScopedClasses['right-2']} */ ;
            var __VLS_114 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Checkbox} */
            Checkbox;
            // @ts-ignore
            var __VLS_115 = __VLS_asFunctionalComponent1(__VLS_114, new __VLS_114({
                modelValue: (__VLS_ctx.selectedAssets),
                value: (asset.id),
                binary: (false),
            }));
            var __VLS_116 = __VLS_115.apply(void 0, __spreadArray([{
                    modelValue: (__VLS_ctx.selectedAssets),
                    value: (asset.id),
                    binary: (false),
                }], __VLS_functionalComponentArgsRest(__VLS_115), false));
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm font-semibold text-gray-900 truncate" }));
            /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
            /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
            /** @type {__VLS_StyleScopedClasses['truncate']} */ ;
            (asset.file_name);
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2 mt-2" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
            /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
            var __VLS_119 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Tag} */
            Tag;
            // @ts-ignore
            var __VLS_120 = __VLS_asFunctionalComponent1(__VLS_119, new __VLS_119({
                value: (__VLS_ctx.getAssetTypeLabel(asset.asset_type)),
                severity: (__VLS_ctx.getAssetTypeSeverity(asset.asset_type)),
                size: "small",
            }));
            var __VLS_121 = __VLS_120.apply(void 0, __spreadArray([{
                    value: (__VLS_ctx.getAssetTypeLabel(asset.asset_type)),
                    severity: (__VLS_ctx.getAssetTypeSeverity(asset.asset_type)),
                    size: "small",
                }], __VLS_functionalComponentArgsRest(__VLS_120), false));
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-xs text-gray-500" }));
            /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
            (__VLS_ctx.formatFileSize(asset.file_size_kb * 1024));
            if (asset.product) {
                __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mt-2 truncate" }));
                /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
                /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
                /** @type {__VLS_StyleScopedClasses['truncate']} */ ;
                (asset.product.product_name);
            }
            // @ts-ignore
            [formatFileSize, selectedAssets, getAssetTypeLabel, getAssetTypeSeverity,];
        }
        // @ts-ignore
        [];
        // @ts-ignore
        [];
    };
    var __VLS_91, __VLS_92, __VLS_98, __VLS_99, __VLS_105, __VLS_106, __VLS_82, __VLS_83;
    for (var _g = 0, _h = __VLS_vFor((__VLS_ctx.assets)); _g < _h.length; _g++) {
        var asset = _h[_g][0];
        _loop_1(asset);
    }
}
else if (__VLS_ctx.viewMode === 'list' && __VLS_ctx.assets.length > 0) {
    var __VLS_124 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    Card;
    // @ts-ignore
    var __VLS_125 = __VLS_asFunctionalComponent1(__VLS_124, new __VLS_124({}));
    var __VLS_126 = __VLS_125.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_125), false));
    var __VLS_129 = __VLS_127.slots.default;
    {
        var __VLS_130 = __VLS_127.slots.content;
        var __VLS_131 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
        DataTable;
        // @ts-ignore
        var __VLS_132 = __VLS_asFunctionalComponent1(__VLS_131, new __VLS_131(__assign({ value: (__VLS_ctx.assets), paginator: (true), rows: (15), rowsPerPageOptions: ([15, 30, 50]), dataKey: "id", selection: (__VLS_ctx.selectedAssets), stripedRows: true }, { class: "p-datatable-sm" })));
        var __VLS_133 = __VLS_132.apply(void 0, __spreadArray([__assign({ value: (__VLS_ctx.assets), paginator: (true), rows: (15), rowsPerPageOptions: ([15, 30, 50]), dataKey: "id", selection: (__VLS_ctx.selectedAssets), stripedRows: true }, { class: "p-datatable-sm" })], __VLS_functionalComponentArgsRest(__VLS_132), false));
        /** @type {__VLS_StyleScopedClasses['p-datatable-sm']} */ ;
        var __VLS_136 = __VLS_134.slots.default;
        {
            var __VLS_137 = __VLS_134.slots.empty;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center py-12" }));
            /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['py-12']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-folder-open text-6xl text-gray-300" }));
            /** @type {__VLS_StyleScopedClasses['pi']} */ ;
            /** @type {__VLS_StyleScopedClasses['pi-folder-open']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-6xl']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-300']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600 mt-4" }));
            /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
            /** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
            // @ts-ignore
            [viewMode, selectedAssets, assets, assets,];
        }
        var __VLS_138 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_139 = __VLS_asFunctionalComponent1(__VLS_138, new __VLS_138({
            selectionMode: "multiple",
            headerStyle: "width: 3rem",
        }));
        var __VLS_140 = __VLS_139.apply(void 0, __spreadArray([{
                selectionMode: "multiple",
                headerStyle: "width: 3rem",
            }], __VLS_functionalComponentArgsRest(__VLS_139), false));
        var __VLS_143 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_144 = __VLS_asFunctionalComponent1(__VLS_143, new __VLS_143(__assign({ header: "Preview" }, { style: {} })));
        var __VLS_145 = __VLS_144.apply(void 0, __spreadArray([__assign({ header: "Preview" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_144), false));
        var __VLS_148 = __VLS_146.slots.default;
        {
            var __VLS_149 = __VLS_146.slots.body;
            var data = __VLS_vSlot(__VLS_149)[0].data;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-16 h-16 bg-gray-100 rounded flex items-center justify-center overflow-hidden" }));
            /** @type {__VLS_StyleScopedClasses['w-16']} */ ;
            /** @type {__VLS_StyleScopedClasses['h-16']} */ ;
            /** @type {__VLS_StyleScopedClasses['bg-gray-100']} */ ;
            /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['overflow-hidden']} */ ;
            if (data.asset_type.includes('Image')) {
                __VLS_asFunctionalElement1(__VLS_intrinsics.img)(__assign(__assign({ onError: (__VLS_ctx.handleImageError) }, { src: (data.auth_url || data.thumbnail_url || data.url) }), { class: "w-full h-full object-cover rounded" }));
                /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
                /** @type {__VLS_StyleScopedClasses['h-full']} */ ;
                /** @type {__VLS_StyleScopedClasses['object-cover']} */ ;
                /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
            }
            else {
                __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: (__VLS_ctx.getAssetIcon(data.asset_type)) }, { class: "text-2xl text-gray-600" }));
                /** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
            }
            // @ts-ignore
            [handleImageError, getAssetIcon,];
        }
        // @ts-ignore
        [];
        var __VLS_146;
        var __VLS_150 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_151 = __VLS_asFunctionalComponent1(__VLS_150, new __VLS_150({
            field: "file_name",
            header: "File Name",
            sortable: true,
        }));
        var __VLS_152 = __VLS_151.apply(void 0, __spreadArray([{
                field: "file_name",
                header: "File Name",
                sortable: true,
            }], __VLS_functionalComponentArgsRest(__VLS_151), false));
        var __VLS_155 = __VLS_153.slots.default;
        {
            var __VLS_156 = __VLS_153.slots.body;
            var data = __VLS_vSlot(__VLS_156)[0].data;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-semibold text-gray-900" }));
            /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
            (data.file_name);
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500 mt-1" }));
            /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
            /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
            (data.alt_text || 'No description');
            // @ts-ignore
            [];
        }
        // @ts-ignore
        [];
        var __VLS_153;
        var __VLS_157 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_158 = __VLS_asFunctionalComponent1(__VLS_157, new __VLS_157({
            field: "asset_type",
            header: "Type",
            sortable: true,
        }));
        var __VLS_159 = __VLS_158.apply(void 0, __spreadArray([{
                field: "asset_type",
                header: "Type",
                sortable: true,
            }], __VLS_functionalComponentArgsRest(__VLS_158), false));
        var __VLS_162 = __VLS_160.slots.default;
        {
            var __VLS_163 = __VLS_160.slots.body;
            var data = __VLS_vSlot(__VLS_163)[0].data;
            var __VLS_164 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Tag} */
            Tag;
            // @ts-ignore
            var __VLS_165 = __VLS_asFunctionalComponent1(__VLS_164, new __VLS_164({
                value: (__VLS_ctx.getAssetTypeLabel(data.asset_type)),
                severity: (__VLS_ctx.getAssetTypeSeverity(data.asset_type)),
            }));
            var __VLS_166 = __VLS_165.apply(void 0, __spreadArray([{
                    value: (__VLS_ctx.getAssetTypeLabel(data.asset_type)),
                    severity: (__VLS_ctx.getAssetTypeSeverity(data.asset_type)),
                }], __VLS_functionalComponentArgsRest(__VLS_165), false));
            // @ts-ignore
            [getAssetTypeLabel, getAssetTypeSeverity,];
        }
        // @ts-ignore
        [];
        var __VLS_160;
        var __VLS_169 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_170 = __VLS_asFunctionalComponent1(__VLS_169, new __VLS_169({
            field: "product.product_name",
            header: "Product",
            sortable: true,
        }));
        var __VLS_171 = __VLS_170.apply(void 0, __spreadArray([{
                field: "product.product_name",
                header: "Product",
                sortable: true,
            }], __VLS_functionalComponentArgsRest(__VLS_170), false));
        var __VLS_174 = __VLS_172.slots.default;
        {
            var __VLS_175 = __VLS_172.slots.body;
            var data = __VLS_vSlot(__VLS_175)[0].data;
            if (data.product) {
                __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm" }));
                /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
                (data.product.product_name);
            }
            else {
                __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm text-gray-400 italic" }));
                /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
                /** @type {__VLS_StyleScopedClasses['italic']} */ ;
            }
            // @ts-ignore
            [];
        }
        // @ts-ignore
        [];
        var __VLS_172;
        var __VLS_176 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_177 = __VLS_asFunctionalComponent1(__VLS_176, new __VLS_176({
            field: "file_size_kb",
            header: "Size",
            sortable: true,
        }));
        var __VLS_178 = __VLS_177.apply(void 0, __spreadArray([{
                field: "file_size_kb",
                header: "Size",
                sortable: true,
            }], __VLS_functionalComponentArgsRest(__VLS_177), false));
        var __VLS_181 = __VLS_179.slots.default;
        {
            var __VLS_182 = __VLS_179.slots.body;
            var data = __VLS_vSlot(__VLS_182)[0].data;
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm" }));
            /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
            (__VLS_ctx.formatFileSize(data.file_size_kb * 1024));
            // @ts-ignore
            [formatFileSize,];
        }
        // @ts-ignore
        [];
        var __VLS_179;
        var __VLS_183 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_184 = __VLS_asFunctionalComponent1(__VLS_183, new __VLS_183({
            field: "is_primary",
            header: "Primary",
        }));
        var __VLS_185 = __VLS_184.apply(void 0, __spreadArray([{
                field: "is_primary",
                header: "Primary",
            }], __VLS_functionalComponentArgsRest(__VLS_184), false));
        var __VLS_188 = __VLS_186.slots.default;
        {
            var __VLS_189 = __VLS_186.slots.body;
            var data = __VLS_vSlot(__VLS_189)[0].data;
            if (data.is_primary) {
                var __VLS_190 = void 0;
                /** @ts-ignore @type {typeof __VLS_components.Tag} */
                Tag;
                // @ts-ignore
                var __VLS_191 = __VLS_asFunctionalComponent1(__VLS_190, new __VLS_190({
                    value: "Yes",
                    severity: "success",
                    size: "small",
                }));
                var __VLS_192 = __VLS_191.apply(void 0, __spreadArray([{
                        value: "Yes",
                        severity: "success",
                        size: "small",
                    }], __VLS_functionalComponentArgsRest(__VLS_191), false));
            }
            else {
                __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm text-gray-400" }));
                /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
            }
            // @ts-ignore
            [];
        }
        // @ts-ignore
        [];
        var __VLS_186;
        var __VLS_195 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_196 = __VLS_asFunctionalComponent1(__VLS_195, new __VLS_195({
            field: "created_at",
            header: "Uploaded",
            sortable: true,
        }));
        var __VLS_197 = __VLS_196.apply(void 0, __spreadArray([{
                field: "created_at",
                header: "Uploaded",
                sortable: true,
            }], __VLS_functionalComponentArgsRest(__VLS_196), false));
        var __VLS_200 = __VLS_198.slots.default;
        {
            var __VLS_201 = __VLS_198.slots.body;
            var data = __VLS_vSlot(__VLS_201)[0].data;
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm" }));
            /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
            (__VLS_ctx.formatDate(data.created_at));
            // @ts-ignore
            [formatDate,];
        }
        // @ts-ignore
        [];
        var __VLS_198;
        var __VLS_202 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_203 = __VLS_asFunctionalComponent1(__VLS_202, new __VLS_202({
            header: "Actions",
            frozen: (true),
            alignFrozen: "right",
        }));
        var __VLS_204 = __VLS_203.apply(void 0, __spreadArray([{
                header: "Actions",
                frozen: (true),
                alignFrozen: "right",
            }], __VLS_functionalComponentArgsRest(__VLS_203), false));
        var __VLS_207 = __VLS_205.slots.default;
        {
            var __VLS_208 = __VLS_205.slots.body;
            var data_1 = __VLS_vSlot(__VLS_208)[0].data;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-1" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['gap-1']} */ ;
            var __VLS_209 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            Button;
            // @ts-ignore
            var __VLS_210 = __VLS_asFunctionalComponent1(__VLS_209, new __VLS_209(__assign({ 'onClick': {} }, { icon: "pi pi-eye", severity: "info", text: true, rounded: true, size: "small" })));
            var __VLS_211 = __VLS_210.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-eye", severity: "info", text: true, rounded: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_210), false));
            var __VLS_214 = void 0;
            var __VLS_215 = ({ click: {} },
                { onClick: function () {
                        var _a = [];
                        for (var _i = 0; _i < arguments.length; _i++) {
                            _a[_i] = arguments[_i];
                        }
                        var $event = _a[0];
                        if (!!(__VLS_ctx.loading))
                            return;
                        if (!!(__VLS_ctx.viewMode === 'grid' && __VLS_ctx.assets.length > 0))
                            return;
                        if (!(__VLS_ctx.viewMode === 'list' && __VLS_ctx.assets.length > 0))
                            return;
                        __VLS_ctx.viewAsset(data_1);
                        // @ts-ignore
                        [viewAsset,];
                    } });
            __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('View') }), null, null);
            var __VLS_212;
            var __VLS_213;
            var __VLS_216 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            Button;
            // @ts-ignore
            var __VLS_217 = __VLS_asFunctionalComponent1(__VLS_216, new __VLS_216(__assign({ 'onClick': {} }, { icon: "pi pi-download", severity: "secondary", text: true, rounded: true, size: "small" })));
            var __VLS_218 = __VLS_217.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-download", severity: "secondary", text: true, rounded: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_217), false));
            var __VLS_221 = void 0;
            var __VLS_222 = ({ click: {} },
                { onClick: function () {
                        var _a = [];
                        for (var _i = 0; _i < arguments.length; _i++) {
                            _a[_i] = arguments[_i];
                        }
                        var $event = _a[0];
                        if (!!(__VLS_ctx.loading))
                            return;
                        if (!!(__VLS_ctx.viewMode === 'grid' && __VLS_ctx.assets.length > 0))
                            return;
                        if (!(__VLS_ctx.viewMode === 'list' && __VLS_ctx.assets.length > 0))
                            return;
                        __VLS_ctx.downloadAsset(data_1);
                        // @ts-ignore
                        [downloadAsset, vTooltip,];
                    } });
            __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('Download') }), null, null);
            var __VLS_219;
            var __VLS_220;
            if (__VLS_ctx.authStore.hasPermission('merchandising.assets.delete')) {
                var __VLS_223 = void 0;
                /** @ts-ignore @type {typeof __VLS_components.Button} */
                Button;
                // @ts-ignore
                var __VLS_224 = __VLS_asFunctionalComponent1(__VLS_223, new __VLS_223(__assign({ 'onClick': {} }, { icon: "pi pi-trash", severity: "danger", text: true, rounded: true, size: "small" })));
                var __VLS_225 = __VLS_224.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-trash", severity: "danger", text: true, rounded: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_224), false));
                var __VLS_228 = void 0;
                var __VLS_229 = ({ click: {} },
                    { onClick: function () {
                            var _a = [];
                            for (var _i = 0; _i < arguments.length; _i++) {
                                _a[_i] = arguments[_i];
                            }
                            var $event = _a[0];
                            if (!!(__VLS_ctx.loading))
                                return;
                            if (!!(__VLS_ctx.viewMode === 'grid' && __VLS_ctx.assets.length > 0))
                                return;
                            if (!(__VLS_ctx.viewMode === 'list' && __VLS_ctx.assets.length > 0))
                                return;
                            if (!(__VLS_ctx.authStore.hasPermission('merchandising.assets.delete')))
                                return;
                            __VLS_ctx.confirmDelete(data_1);
                            // @ts-ignore
                            [authStore, confirmDelete, vTooltip,];
                        } });
                __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('Delete') }), null, null);
                var __VLS_226;
                var __VLS_227;
            }
            // @ts-ignore
            [vTooltip,];
        }
        // @ts-ignore
        [];
        var __VLS_205;
        // @ts-ignore
        [];
        var __VLS_134;
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_127;
}
else {
    var __VLS_230 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    Card;
    // @ts-ignore
    var __VLS_231 = __VLS_asFunctionalComponent1(__VLS_230, new __VLS_230({}));
    var __VLS_232 = __VLS_231.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_231), false));
    var __VLS_235 = __VLS_233.slots.default;
    {
        var __VLS_236 = __VLS_233.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center py-12" }));
        /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['py-12']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-cloud-upload text-6xl text-gray-300" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-cloud-upload']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-6xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-300']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600 mt-4 text-lg" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-500 text-sm mt-2" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-3 justify-center mt-6" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-6']} */ ;
        var __VLS_237 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_238 = __VLS_asFunctionalComponent1(__VLS_237, new __VLS_237(__assign({ 'onClick': {} }, { label: "Upload Media Assets", icon: "pi pi-cloud-upload" })));
        var __VLS_239 = __VLS_238.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Upload Media Assets", icon: "pi pi-cloud-upload" })], __VLS_functionalComponentArgsRest(__VLS_238), false));
        var __VLS_242 = void 0;
        var __VLS_243 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!!(__VLS_ctx.loading))
                        return;
                    if (!!(__VLS_ctx.viewMode === 'grid' && __VLS_ctx.assets.length > 0))
                        return;
                    if (!!(__VLS_ctx.viewMode === 'list' && __VLS_ctx.assets.length > 0))
                        return;
                    __VLS_ctx.$router.push({ name: 'merchandising.assets.upload' });
                    // @ts-ignore
                    [$router,];
                } });
        var __VLS_240;
        var __VLS_241;
        var __VLS_244 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_245 = __VLS_asFunctionalComponent1(__VLS_244, new __VLS_244(__assign({ 'onClick': {} }, { label: "View 3D Models", icon: "pi pi-cube", severity: "info", outlined: true })));
        var __VLS_246 = __VLS_245.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "View 3D Models", icon: "pi pi-cube", severity: "info", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_245), false));
        var __VLS_249 = void 0;
        var __VLS_250 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!!(__VLS_ctx.loading))
                        return;
                    if (!!(__VLS_ctx.viewMode === 'grid' && __VLS_ctx.assets.length > 0))
                        return;
                    if (!!(__VLS_ctx.viewMode === 'list' && __VLS_ctx.assets.length > 0))
                        return;
                    __VLS_ctx.$router.push({ name: 'merchandising.3d-gallery' });
                    // @ts-ignore
                    [$router,];
                } });
        var __VLS_247;
        var __VLS_248;
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_233;
}
var __VLS_251;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_252 = __VLS_asFunctionalComponent1(__VLS_251, new __VLS_251(__assign({ visible: (__VLS_ctx.viewDialogVisible), header: ((_a = __VLS_ctx.currentAsset) === null || _a === void 0 ? void 0 : _a.file_name), modal: (true) }, { class: "w-full max-w-4xl" })));
var __VLS_253 = __VLS_252.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.viewDialogVisible), header: ((_b = __VLS_ctx.currentAsset) === null || _b === void 0 ? void 0 : _b.file_name), modal: (true) }, { class: "w-full max-w-4xl" })], __VLS_functionalComponentArgsRest(__VLS_252), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['max-w-4xl']} */ ;
var __VLS_256 = __VLS_254.slots.default;
if (__VLS_ctx.currentAsset) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
    /** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-100 rounded-lg p-8 flex items-center justify-center" }, { style: {} }));
    /** @type {__VLS_StyleScopedClasses['bg-gray-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-8']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
    if (__VLS_ctx.currentAsset.asset_type.includes('Image')) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.img)(__assign({ src: (__VLS_ctx.currentAsset.auth_url || __VLS_ctx.currentAsset.url), alt: (__VLS_ctx.currentAsset.file_name) }, { class: "max-w-full max-h-96 object-contain" }));
        /** @type {__VLS_StyleScopedClasses['max-w-full']} */ ;
        /** @type {__VLS_StyleScopedClasses['max-h-96']} */ ;
        /** @type {__VLS_StyleScopedClasses['object-contain']} */ ;
    }
    else if (__VLS_ctx.currentAsset.asset_type === 'Video_Product') {
        __VLS_asFunctionalElement1(__VLS_intrinsics.video, __VLS_intrinsics.video)(__assign({ src: (__VLS_ctx.currentAsset.url), controls: true }, { class: "max-w-full max-h-96" }));
        /** @type {__VLS_StyleScopedClasses['max-w-full']} */ ;
        /** @type {__VLS_StyleScopedClasses['max-h-96']} */ ;
    }
    else if (__VLS_ctx.currentAsset.asset_type === 'Manual_PDF') {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center" }));
        /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-file-pdf text-8xl text-red-600 mb-4" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-file-pdf']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-8xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-red-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600 text-lg font-semibold" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        (__VLS_ctx.currentAsset.file_name);
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-500 text-sm mt-2" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
    }
    else {
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: (__VLS_ctx.getAssetIcon(__VLS_ctx.currentAsset.asset_type)) }, { class: "text-8xl text-gray-400" }));
        /** @type {__VLS_StyleScopedClasses['text-8xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-4 p-4 bg-gray-50 rounded-lg" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mb-1" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
    var __VLS_257 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    Tag;
    // @ts-ignore
    var __VLS_258 = __VLS_asFunctionalComponent1(__VLS_257, new __VLS_257({
        value: (__VLS_ctx.getAssetTypeLabel(__VLS_ctx.currentAsset.asset_type)),
        severity: (__VLS_ctx.getAssetTypeSeverity(__VLS_ctx.currentAsset.asset_type)),
    }));
    var __VLS_259 = __VLS_258.apply(void 0, __spreadArray([{
            value: (__VLS_ctx.getAssetTypeLabel(__VLS_ctx.currentAsset.asset_type)),
            severity: (__VLS_ctx.getAssetTypeSeverity(__VLS_ctx.currentAsset.asset_type)),
        }], __VLS_functionalComponentArgsRest(__VLS_258), false));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mb-1" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm font-semibold" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    (__VLS_ctx.formatFileSize(__VLS_ctx.currentAsset.file_size_kb * 1024));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mb-1" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm font-semibold" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    (((_c = __VLS_ctx.currentAsset.product) === null || _c === void 0 ? void 0 : _c.product_name) || 'N/A');
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mb-1" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm font-semibold" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    (__VLS_ctx.formatDate(__VLS_ctx.currentAsset.created_at));
    if (__VLS_ctx.currentAsset.alt_text) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "col-span-2" }));
        /** @type {__VLS_StyleScopedClasses['col-span-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mb-1" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        (__VLS_ctx.currentAsset.alt_text);
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "col-span-2" }));
    /** @type {__VLS_StyleScopedClasses['col-span-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mb-1" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs font-mono text-blue-600 break-all" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-mono']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['break-all']} */ ;
    (__VLS_ctx.currentAsset.url);
}
{
    var __VLS_262 = __VLS_254.slots.footer;
    var __VLS_263 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_264 = __VLS_asFunctionalComponent1(__VLS_263, new __VLS_263(__assign({ 'onClick': {} }, { label: "Download", icon: "pi pi-download" })));
    var __VLS_265 = __VLS_264.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Download", icon: "pi pi-download" })], __VLS_functionalComponentArgsRest(__VLS_264), false));
    var __VLS_268 = void 0;
    var __VLS_269 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.downloadAsset(__VLS_ctx.currentAsset);
                // @ts-ignore
                [formatFileSize, downloadAsset, getAssetTypeLabel, getAssetTypeSeverity, getAssetIcon, formatDate, viewDialogVisible, currentAsset, currentAsset, currentAsset, currentAsset, currentAsset, currentAsset, currentAsset, currentAsset, currentAsset, currentAsset, currentAsset, currentAsset, currentAsset, currentAsset, currentAsset, currentAsset, currentAsset, currentAsset, currentAsset, currentAsset,];
            } });
    var __VLS_266;
    var __VLS_267;
    var __VLS_270 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_271 = __VLS_asFunctionalComponent1(__VLS_270, new __VLS_270(__assign({ 'onClick': {} }, { label: "Close", severity: "secondary", outlined: true })));
    var __VLS_272 = __VLS_271.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Close", severity: "secondary", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_271), false));
    var __VLS_275 = void 0;
    var __VLS_276 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.viewDialogVisible = false;
                // @ts-ignore
                [viewDialogVisible,];
            } });
    var __VLS_273;
    var __VLS_274;
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_254;
var __VLS_277;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_278 = __VLS_asFunctionalComponent1(__VLS_277, new __VLS_277(__assign({ visible: (__VLS_ctx.deleteDialogVisible), header: "Confirm Delete", modal: (true) }, { class: "w-96" })));
var __VLS_279 = __VLS_278.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.deleteDialogVisible), header: "Confirm Delete", modal: (true) }, { class: "w-96" })], __VLS_functionalComponentArgsRest(__VLS_278), false));
/** @type {__VLS_StyleScopedClasses['w-96']} */ ;
var __VLS_282 = __VLS_280.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-3" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-exclamation-triangle text-4xl text-red-600" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-exclamation-triangle']} */ ;
/** @type {__VLS_StyleScopedClasses['text-4xl']} */ ;
/** @type {__VLS_StyleScopedClasses['text-red-600']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-semibold" }));
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
{
    var __VLS_283 = __VLS_280.slots.footer;
    var __VLS_284 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_285 = __VLS_asFunctionalComponent1(__VLS_284, new __VLS_284(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", text: true })));
    var __VLS_286 = __VLS_285.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", text: true })], __VLS_functionalComponentArgsRest(__VLS_285), false));
    var __VLS_289 = void 0;
    var __VLS_290 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.deleteDialogVisible = false;
                // @ts-ignore
                [deleteDialogVisible, deleteDialogVisible,];
            } });
    var __VLS_287;
    var __VLS_288;
    var __VLS_291 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_292 = __VLS_asFunctionalComponent1(__VLS_291, new __VLS_291(__assign({ 'onClick': {} }, { label: "Delete", severity: "danger", loading: (__VLS_ctx.deleting) })));
    var __VLS_293 = __VLS_292.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Delete", severity: "danger", loading: (__VLS_ctx.deleting) })], __VLS_functionalComponentArgsRest(__VLS_292), false));
    var __VLS_296 = void 0;
    var __VLS_297 = ({ click: {} },
        { onClick: (__VLS_ctx.deleteAsset) });
    var __VLS_294;
    var __VLS_295;
    // @ts-ignore
    [deleting, deleteAsset,];
}
// @ts-ignore
[];
var __VLS_280;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
