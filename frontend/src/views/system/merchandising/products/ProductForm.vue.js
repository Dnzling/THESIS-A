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
var vue_router_1 = require("vue-router");
var usetoast_1 = require("primevue/usetoast");
var card_1 = require("primevue/card");
var button_1 = require("primevue/button");
var inputtext_1 = require("primevue/inputtext");
var inputnumber_1 = require("primevue/inputnumber");
var textarea_1 = require("primevue/textarea");
var select_1 = require("primevue/select");
var checkbox_1 = require("primevue/checkbox");
var fileupload_1 = require("primevue/fileupload");
var skeleton_1 = require("primevue/skeleton");
var datepicker_1 = require("primevue/datepicker");
var tag_1 = require("primevue/tag");
var badge_1 = require("primevue/badge");
var merchandising_service_1 = require("../../../../services/merchandising.service");
var route = (0, vue_router_1.useRoute)();
var router = (0, vue_router_1.useRouter)();
var toast = (0, usetoast_1.useToast)();
var isEditMode = (0, vue_1.computed)(function () { return !!route.params.id; });
var submitting = (0, vue_1.ref)(false);
var loadingData = (0, vue_1.ref)(false);
var loadingCategories = (0, vue_1.ref)(false);
var existingModel = (0, vue_1.ref)(null);
var originalBasePrice = (0, vue_1.ref)(0);
var form = (0, vue_1.ref)({
    product_name: '',
    sku: '',
    category_id: null,
    subcategory_id: null,
    brand: '',
    collection_name: '',
    stock_status: 'In Stock',
    description: '',
    base_price: null,
    discounted_price: null,
    tax_rate: 12.00,
    length_cm: null,
    width_cm: null,
    height_cm: null,
    weight_kg: null,
    assembly_required: false,
    is_featured: false,
    is_new_arrival: false,
    is_bestseller: false,
    is_active: true,
    meta_title: '',
    meta_description: '',
    meta_keywords: '',
    published_at: null,
    price_change_reason: '',
    // 3D Model fields
    modelFile: null,
    imageFiles: [],
    default_camera_angle_x: 0,
    default_camera_angle_y: 15,
    default_zoom_level: 1.5
});
var errors = (0, vue_1.ref)({});
var categories = (0, vue_1.ref)([]);
var subcategories = (0, vue_1.computed)(function () {
    if (!form.value.category_id)
        return [];
    return categories.value.filter(function (c) { return c.parent_category_id === form.value.category_id; });
});
var stockStatusOptions = ['In Stock', 'Low Stock', 'Out of Stock', 'Pre-order'];
var loadCategories = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, error_1;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                loadingCategories.value = true;
                _a.label = 1;
            case 1:
                _a.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, merchandising_service_1.default.getCategories()];
            case 2:
                response = _a.sent();
                categories.value = response.data.data;
                return [3 /*break*/, 5];
            case 3:
                error_1 = _a.sent();
                console.error('Failed to load categories:', error_1);
                categories.value = [];
                return [3 /*break*/, 5];
            case 4:
                loadingCategories.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var loadProduct = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, product, error_2;
    var _a, _b, _c;
    return __generator(this, function (_d) {
        switch (_d.label) {
            case 0:
                if (!isEditMode.value)
                    return [2 /*return*/];
                loadingData.value = true;
                _d.label = 1;
            case 1:
                _d.trys.push([1, 5, 6, 7]);
                return [4 /*yield*/, merchandising_service_1.default.getProduct(Number(route.params.id))];
            case 2:
                response = _d.sent();
                product = response.data;
                // Properly map all fields with date conversion
                Object.assign(form.value, {
                    product_name: product.product_name || '',
                    sku: product.sku || '',
                    category_id: product.category_id,
                    subcategory_id: product.subcategory_id,
                    brand: product.brand || '',
                    collection_name: product.collection_name || '',
                    stock_status: product.stock_status || 'In Stock',
                    description: product.description || '',
                    base_price: product.base_price,
                    discounted_price: product.discounted_price,
                    tax_rate: product.tax_rate || 12.00,
                    length_cm: product.length_cm,
                    width_cm: product.width_cm,
                    height_cm: product.height_cm,
                    weight_kg: product.weight_kg,
                    assembly_required: product.assembly_required || false,
                    is_featured: product.is_featured || false,
                    is_new_arrival: product.is_new_arrival || false,
                    is_bestseller: product.is_bestseller || false,
                    is_active: (_a = product.is_active) !== null && _a !== void 0 ? _a : true,
                    meta_title: product.meta_title || '',
                    meta_description: product.meta_description || '',
                    meta_keywords: product.meta_keywords || '',
                    // ✅ Convert string date to Date object for DatePicker
                    published_at: product.published_at ? new Date(product.published_at) : null,
                    price_change_reason: '',
                    // Keep existing 3D settings
                    default_camera_angle_x: form.value.default_camera_angle_x,
                    default_camera_angle_y: form.value.default_camera_angle_y,
                    default_zoom_level: form.value.default_zoom_level
                });
                originalBasePrice.value = product.base_price;
                if (!product.id) return [3 /*break*/, 4];
                return [4 /*yield*/, loadProductAssets(product.id)];
            case 3:
                _d.sent();
                _d.label = 4;
            case 4: return [3 /*break*/, 7];
            case 5:
                error_2 = _d.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_c = (_b = error_2.response) === null || _b === void 0 ? void 0 : _b.data) === null || _c === void 0 ? void 0 : _c.message) || 'Failed to load product',
                    life: 5000
                });
                router.push({ name: 'merchandising.products' });
                return [3 /*break*/, 7];
            case 6:
                loadingData.value = false;
                return [7 /*endfinally*/];
            case 7: return [2 /*return*/];
        }
    });
}); };
var loadProductAssets = function (productId) { return __awaiter(void 0, void 0, void 0, function () {
    var response, models, error_3;
    var _a;
    return __generator(this, function (_b) {
        switch (_b.label) {
            case 0:
                _b.trys.push([0, 2, , 3]);
                return [4 /*yield*/, merchandising_service_1.default.getAssetsByProduct(productId)];
            case 1:
                response = _b.sent();
                models = ((_a = response.data.assets_by_type) === null || _a === void 0 ? void 0 : _a['3D_Model']) || [];
                if (models.length > 0) {
                    existingModel.value = models.find(function (m) { return m.is_primary; }) || models[0];
                    if (existingModel.value) {
                        form.value.default_camera_angle_x = existingModel.value.default_camera_angle_x || 0;
                        form.value.default_camera_angle_y = existingModel.value.default_camera_angle_y || 15;
                        form.value.default_zoom_level = existingModel.value.default_zoom_level || 1.5;
                    }
                }
                return [3 /*break*/, 3];
            case 2:
                error_3 = _b.sent();
                console.error('Failed to load product assets:', error_3);
                return [3 /*break*/, 3];
            case 3: return [2 /*return*/];
        }
    });
}); };
var onCategoryChange = function () {
    form.value.subcategory_id = null;
    generateSKU();
};
// SKU Generation Logic
var generateSKU = function () { return __awaiter(void 0, void 0, void 0, function () {
    var category, categoryCode, brandCode, baseSKU, response, existingProducts, maxSequence_1, nextSequence, error_4, randomSeq;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                if (!form.value.product_name || !form.value.category_id)
                    return [2 /*return*/];
                category = categories.value.find(function (c) { return c.id === form.value.category_id; });
                if (!category)
                    return [2 /*return*/];
                categoryCode = category.category_code || 'GEN';
                brandCode = form.value.brand
                    ? form.value.brand.substring(0, 3).toUpperCase()
                    : form.value.product_name.substring(0, 3).toUpperCase();
                baseSKU = "".concat(categoryCode, "-").concat(brandCode);
                _a.label = 1;
            case 1:
                _a.trys.push([1, 3, , 4]);
                return [4 /*yield*/, merchandising_service_1.default.getProducts({ search: baseSKU })];
            case 2:
                response = _a.sent();
                existingProducts = response.data.data || [];
                maxSequence_1 = 0;
                existingProducts.forEach(function (p) {
                    var _a;
                    var match = (_a = p.sku) === null || _a === void 0 ? void 0 : _a.match(new RegExp("".concat(baseSKU, "-(\\d+)")));
                    if (match) {
                        var seq = parseInt(match[1]);
                        if (seq > maxSequence_1)
                            maxSequence_1 = seq;
                    }
                });
                nextSequence = (maxSequence_1 + 1).toString().padStart(3, '0');
                form.value.sku = "".concat(baseSKU, "-").concat(nextSequence);
                return [3 /*break*/, 4];
            case 3:
                error_4 = _a.sent();
                randomSeq = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
                form.value.sku = "".concat(baseSKU, "-").concat(randomSeq);
                return [3 /*break*/, 4];
            case 4: return [2 /*return*/];
        }
    });
}); };
var copySKU = function () {
    if (!form.value.sku)
        return;
    navigator.clipboard.writeText(form.value.sku);
    toast.add({
        severity: 'success',
        summary: 'Copied!',
        detail: 'SKU copied to clipboard',
        life: 2000
    });
};
var handleModelSelect = function (event) {
    var file = event.target.files[0];
    if (!file)
        return;
    if (file.size > 100000000) {
        toast.add({
            severity: 'error',
            summary: 'File too large',
            detail: 'Model file must be less than 100MB',
            life: 3000
        });
        return;
    }
    form.value.modelFile = file;
};
var handleModelDrop = function (event) {
    var _a;
    var file = (_a = event.dataTransfer) === null || _a === void 0 ? void 0 : _a.files[0];
    if (!file)
        return;
    if (!file.name.endsWith('.glb') && !file.name.endsWith('.gltf')) {
        toast.add({
            severity: 'error',
            summary: 'Invalid file',
            detail: 'Only GLB and GLTF files are supported',
            life: 3000
        });
        return;
    }
    form.value.modelFile = file;
};
var removeModel = function () {
    form.value.modelFile = null;
    if ($refs.modelInput) {
        $refs.modelInput.value = null;
    }
};
var deleteExistingModel = function () { return __awaiter(void 0, void 0, void 0, function () {
    var error_5;
    var _a;
    return __generator(this, function (_b) {
        switch (_b.label) {
            case 0:
                if (!((_a = existingModel.value) === null || _a === void 0 ? void 0 : _a.id))
                    return [2 /*return*/];
                _b.label = 1;
            case 1:
                _b.trys.push([1, 3, , 4]);
                return [4 /*yield*/, merchandising_service_1.default.deleteAsset(existingModel.value.id)];
            case 2:
                _b.sent();
                existingModel.value = null;
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: '3D model deleted',
                    life: 3000
                });
                return [3 /*break*/, 4];
            case 3:
                error_5 = _b.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Failed to delete model',
                    life: 3000
                });
                return [3 /*break*/, 4];
            case 4: return [2 /*return*/];
        }
    });
}); };
var handleImageSelect = function (event) {
    form.value.imageFiles = Array.from(event.files);
};
var getImagePreview = function (file) {
    return URL.createObjectURL(file);
};
var removeImage = function (index) {
    form.value.imageFiles.splice(index, 1);
};
var formatFileSize = function (bytes) {
    if (bytes === 0)
        return '0 Bytes';
    var k = 1024;
    var sizes = ['Bytes', 'KB', 'MB', 'GB'];
    var i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};
var validateForm = function () {
    errors.value = {};
    if (!form.value.product_name)
        errors.value.product_name = 'Product name is required';
    if (!form.value.sku)
        errors.value.sku = 'SKU is required';
    if (!form.value.category_id)
        errors.value.category_id = 'Category is required';
    if (!form.value.base_price || form.value.base_price <= 0)
        errors.value.base_price = 'Base price must be greater than 0';
    if (isEditMode.value && originalBasePrice.value !== form.value.base_price && !form.value.price_change_reason) {
        errors.value.price_change_reason = 'Price change reason is required';
    }
    if (!isEditMode.value && !form.value.modelFile && !existingModel.value) {
        toast.add({
            severity: 'warn',
            summary: 'Missing 3D Model',
            detail: 'Please upload a primary 3D model',
            life: 3000
        });
        return false;
    }
    return Object.keys(errors.value).length === 0;
};
var handleSubmit = function () { return __awaiter(void 0, void 0, void 0, function () {
    var productId, submitData, response, error_6;
    var _a, _b, _c;
    return __generator(this, function (_d) {
        switch (_d.label) {
            case 0:
                if (!validateForm()) {
                    toast.add({
                        severity: 'warn',
                        summary: 'Validation Error',
                        detail: 'Please fill in all required fields',
                        life: 3000
                    });
                    return [2 /*return*/];
                }
                submitting.value = true;
                _d.label = 1;
            case 1:
                _d.trys.push([1, 10, 11, 12]);
                productId = void 0;
                submitData = {
                    product_name: form.value.product_name,
                    sku: form.value.sku,
                    category_id: form.value.category_id,
                    subcategory_id: form.value.subcategory_id,
                    brand: form.value.brand,
                    collection_name: form.value.collection_name,
                    stock_status: form.value.stock_status,
                    description: form.value.description,
                    base_price: form.value.base_price,
                    discounted_price: form.value.discounted_price,
                    tax_rate: form.value.tax_rate,
                    length_cm: form.value.length_cm,
                    width_cm: form.value.width_cm,
                    height_cm: form.value.height_cm,
                    weight_kg: form.value.weight_kg,
                    assembly_required: form.value.assembly_required,
                    is_featured: form.value.is_featured,
                    is_new_arrival: form.value.is_new_arrival,
                    is_bestseller: form.value.is_bestseller,
                    is_active: form.value.is_active,
                    meta_title: form.value.meta_title,
                    meta_description: form.value.meta_description,
                    meta_keywords: form.value.meta_keywords,
                    // ✅ Convert Date object to ISO string
                    published_at: form.value.published_at instanceof Date
                        ? form.value.published_at.toISOString()
                        : form.value.published_at,
                    price_change_reason: form.value.price_change_reason
                };
                if (!isEditMode.value) return [3 /*break*/, 3];
                return [4 /*yield*/, merchandising_service_1.default.updateProduct(Number(route.params.id), submitData)];
            case 2:
                _d.sent();
                productId = Number(route.params.id);
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Product updated successfully',
                    life: 3000
                });
                return [3 /*break*/, 5];
            case 3: return [4 /*yield*/, merchandising_service_1.default.createProduct(submitData)];
            case 4:
                response = _d.sent();
                productId = response.data.id;
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Product created successfully',
                    life: 3000
                });
                _d.label = 5;
            case 5:
                if (!form.value.modelFile) return [3 /*break*/, 7];
                return [4 /*yield*/, upload3DModel(productId)];
            case 6:
                _d.sent();
                _d.label = 7;
            case 7:
                if (!(form.value.imageFiles && form.value.imageFiles.length > 0)) return [3 /*break*/, 9];
                return [4 /*yield*/, uploadImages(productId)];
            case 8:
                _d.sent();
                _d.label = 9;
            case 9:
                router.push({ name: 'merchandising.products' });
                return [3 /*break*/, 12];
            case 10:
                error_6 = _d.sent();
                console.error('Form submission error:', error_6);
                if (((_a = error_6.response) === null || _a === void 0 ? void 0 : _a.status) === 422) {
                    errors.value = error_6.response.data.errors || {};
                }
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_c = (_b = error_6.response) === null || _b === void 0 ? void 0 : _b.data) === null || _c === void 0 ? void 0 : _c.message) || 'Failed to save product',
                    life: 5000
                });
                return [3 /*break*/, 12];
            case 11:
                submitting.value = false;
                return [7 /*endfinally*/];
            case 12: return [2 /*return*/];
        }
    });
}); };
// In ProductForm2.vue - Update these functions
var upload3DModel = function (productId) { return __awaiter(void 0, void 0, void 0, function () {
    var formData, response, error_7;
    var _a, _b, _c;
    return __generator(this, function (_d) {
        switch (_d.label) {
            case 0:
                if (!form.value.modelFile)
                    return [2 /*return*/];
                _d.label = 1;
            case 1:
                _d.trys.push([1, 3, , 4]);
                formData = new FormData();
                formData.append('product_id', productId.toString());
                formData.append('asset_type', '3D_Model');
                formData.append('asset_file', form.value.modelFile); // ✅ Note: asset_file not model_file
                formData.append('is_primary', '1'); // ✅ Use '1' instead of 'true'
                formData.append('model_format', ((_a = form.value.modelFile.name.split('.').pop()) === null || _a === void 0 ? void 0 : _a.toLowerCase()) || 'glb');
                formData.append('default_camera_angle_x', form.value.default_camera_angle_x.toString());
                formData.append('default_camera_angle_y', form.value.default_camera_angle_y.toString());
                formData.append('default_zoom_level', form.value.default_zoom_level.toString());
                console.log('Uploading 3D model:', {
                    productId: productId,
                    fileName: form.value.modelFile.name,
                    size: form.value.modelFile.size
                });
                return [4 /*yield*/, merchandising_service_1.default.uploadAsset(formData)];
            case 2:
                response = _d.sent();
                console.log('3D model upload response:', response);
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: '3D model uploaded successfully',
                    life: 3000
                });
                return [3 /*break*/, 4];
            case 3:
                error_7 = _d.sent();
                console.error('3D model upload error:', error_7.response || error_7);
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_c = (_b = error_7.response) === null || _b === void 0 ? void 0 : _b.data) === null || _c === void 0 ? void 0 : _c.message) || 'Failed to upload 3D model',
                    life: 3000
                });
                throw error_7; // Re-throw to handle in main submit
            case 4: return [2 /*return*/];
        }
    });
}); };
var uploadImages = function (productId) { return __awaiter(void 0, void 0, void 0, function () {
    var i, formData, error_8;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                if (!form.value.imageFiles || form.value.imageFiles.length === 0)
                    return [2 /*return*/];
                _c.label = 1;
            case 1:
                _c.trys.push([1, 6, , 7]);
                i = 0;
                _c.label = 2;
            case 2:
                if (!(i < form.value.imageFiles.length)) return [3 /*break*/, 5];
                formData = new FormData();
                formData.append('product_id', productId.toString());
                formData.append('asset_type', i === 0 ? 'Image_Main' : 'Image_Gallery');
                formData.append('asset_file', form.value.imageFiles[i]); // ✅ Note: asset_file
                formData.append('is_primary', i === 0 ? '1' : '0'); // ✅ Use '1'/'0'
                formData.append('display_order', i.toString());
                console.log("Uploading image ".concat(i + 1, "/").concat(form.value.imageFiles.length));
                return [4 /*yield*/, merchandising_service_1.default.uploadAsset(formData)];
            case 3:
                _c.sent();
                _c.label = 4;
            case 4:
                i++;
                return [3 /*break*/, 2];
            case 5:
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: "".concat(form.value.imageFiles.length, " images uploaded successfully"),
                    life: 3000
                });
                return [3 /*break*/, 7];
            case 6:
                error_8 = _c.sent();
                console.error('Image upload error:', error_8.response || error_8);
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_b = (_a = error_8.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to upload some images',
                    life: 3000
                });
                throw error_8;
            case 7: return [2 /*return*/];
        }
    });
}); };
(0, vue_1.onMounted)(function () {
    loadCategories();
    loadProduct();
});
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "max-w-7xl mx-auto space-y-6 pb-6" }));
/** @type {__VLS_StyleScopedClasses['max-w-7xl']} */ ;
/** @type {__VLS_StyleScopedClasses['mx-auto']} */ ;
/** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
/** @type {__VLS_StyleScopedClasses['pb-6']} */ ;
if (__VLS_ctx.loadingData) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 lg:grid-cols-3 gap-6" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['lg:grid-cols-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-6']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "lg:col-span-2 space-y-6" }));
    /** @type {__VLS_StyleScopedClasses['lg:col-span-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
    var __VLS_0 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Skeleton} */
    skeleton_1.default;
    // @ts-ignore
    var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ height: "400px" }, { class: "rounded-lg" })));
    var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ height: "400px" }, { class: "rounded-lg" })], __VLS_functionalComponentArgsRest(__VLS_1), false));
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    var __VLS_5 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Skeleton} */
    skeleton_1.default;
    // @ts-ignore
    var __VLS_6 = __VLS_asFunctionalComponent1(__VLS_5, new __VLS_5(__assign({ height: "300px" }, { class: "rounded-lg" })));
    var __VLS_7 = __VLS_6.apply(void 0, __spreadArray([__assign({ height: "300px" }, { class: "rounded-lg" })], __VLS_functionalComponentArgsRest(__VLS_6), false));
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "lg:col-span-1" }));
    /** @type {__VLS_StyleScopedClasses['lg:col-span-1']} */ ;
    var __VLS_10 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Skeleton} */
    skeleton_1.default;
    // @ts-ignore
    var __VLS_11 = __VLS_asFunctionalComponent1(__VLS_10, new __VLS_10(__assign({ height: "600px" }, { class: "rounded-lg" })));
    var __VLS_12 = __VLS_11.apply(void 0, __spreadArray([__assign({ height: "600px" }, { class: "rounded-lg" })], __VLS_functionalComponentArgsRest(__VLS_11), false));
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
}
else {
    __VLS_asFunctionalElement1(__VLS_intrinsics.form, __VLS_intrinsics.form)(__assign({ onSubmit: (__VLS_ctx.handleSubmit) }));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 lg:grid-cols-3 gap-6" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['lg:grid-cols-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-6']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "lg:col-span-2 space-y-6" }));
    /** @type {__VLS_StyleScopedClasses['lg:col-span-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
    var __VLS_15 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    card_1.default;
    // @ts-ignore
    var __VLS_16 = __VLS_asFunctionalComponent1(__VLS_15, new __VLS_15({}));
    var __VLS_17 = __VLS_16.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_16), false));
    var __VLS_20 = __VLS_18.slots.default;
    {
        var __VLS_21 = __VLS_18.slots.title;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-info-circle text-blue-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-info-circle']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        // @ts-ignore
        [loadingData, handleSubmit,];
    }
    {
        var __VLS_22 = __VLS_18.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
        /** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "product_name" }, { class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
        /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
        var __VLS_23 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.InputText} */
        inputtext_1.default;
        // @ts-ignore
        var __VLS_24 = __VLS_asFunctionalComponent1(__VLS_23, new __VLS_23(__assign(__assign({ 'onInput': {} }, { id: "product_name", modelValue: (__VLS_ctx.form.product_name), placeholder: "e.g., Modern L-Shaped Sectional Sofa" }), { class: ({ 'p-invalid': __VLS_ctx.errors.product_name }) })));
        var __VLS_25 = __VLS_24.apply(void 0, __spreadArray([__assign(__assign({ 'onInput': {} }, { id: "product_name", modelValue: (__VLS_ctx.form.product_name), placeholder: "e.g., Modern L-Shaped Sectional Sofa" }), { class: ({ 'p-invalid': __VLS_ctx.errors.product_name }) })], __VLS_functionalComponentArgsRest(__VLS_24), false));
        var __VLS_28 = void 0;
        var __VLS_29 = ({ input: {} },
            { onInput: (__VLS_ctx.generateSKU) });
        /** @type {__VLS_StyleScopedClasses['p-invalid']} */ ;
        var __VLS_26;
        var __VLS_27;
        if (__VLS_ctx.errors.product_name) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-red-500" }));
            /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
            (__VLS_ctx.errors.product_name);
        }
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "sku" }, { class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
        /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        var __VLS_30 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.InputText} */
        inputtext_1.default;
        // @ts-ignore
        var __VLS_31 = __VLS_asFunctionalComponent1(__VLS_30, new __VLS_30(__assign(__assign(__assign({ id: "sku", modelValue: (__VLS_ctx.form.sku), placeholder: "Will be auto-generated" }, { class: ({ 'p-invalid': __VLS_ctx.errors.sku }) }), { readonly: true }), { class: "flex-1" })));
        var __VLS_32 = __VLS_31.apply(void 0, __spreadArray([__assign(__assign(__assign({ id: "sku", modelValue: (__VLS_ctx.form.sku), placeholder: "Will be auto-generated" }, { class: ({ 'p-invalid': __VLS_ctx.errors.sku }) }), { readonly: true }), { class: "flex-1" })], __VLS_functionalComponentArgsRest(__VLS_31), false));
        /** @type {__VLS_StyleScopedClasses['p-invalid']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
        var __VLS_35 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_36 = __VLS_asFunctionalComponent1(__VLS_35, new __VLS_35(__assign({ 'onClick': {} }, { icon: "pi pi-copy", severity: "secondary", outlined: true, disabled: (!__VLS_ctx.form.sku) })));
        var __VLS_37 = __VLS_36.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-copy", severity: "secondary", outlined: true, disabled: (!__VLS_ctx.form.sku) })], __VLS_functionalComponentArgsRest(__VLS_36), false));
        var __VLS_40 = void 0;
        var __VLS_41 = ({ click: {} },
            { onClick: (__VLS_ctx.copySKU) });
        __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('Copy SKU') }), null, null);
        var __VLS_38;
        var __VLS_39;
        var __VLS_42 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_43 = __VLS_asFunctionalComponent1(__VLS_42, new __VLS_42(__assign({ 'onClick': {} }, { icon: "pi pi-refresh", severity: "secondary", outlined: true, disabled: (!__VLS_ctx.form.product_name || !__VLS_ctx.form.category_id) })));
        var __VLS_44 = __VLS_43.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-refresh", severity: "secondary", outlined: true, disabled: (!__VLS_ctx.form.product_name || !__VLS_ctx.form.category_id) })], __VLS_functionalComponentArgsRest(__VLS_43), false));
        var __VLS_47 = void 0;
        var __VLS_48 = ({ click: {} },
            { onClick: (__VLS_ctx.generateSKU) });
        __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('Regenerate SKU') }), null, null);
        var __VLS_45;
        var __VLS_46;
        __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        if (__VLS_ctx.errors.sku) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-red-500" }));
            /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
            (__VLS_ctx.errors.sku);
        }
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-2 gap-4" }));
        /** @type {__VLS_StyleScopedClasses['grid']} */ ;
        /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
        /** @type {__VLS_StyleScopedClasses['md:grid-cols-2']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "category" }, { class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
        /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
        var __VLS_49 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Select} */
        select_1.default;
        // @ts-ignore
        var __VLS_50 = __VLS_asFunctionalComponent1(__VLS_49, new __VLS_49(__assign(__assign(__assign({ 'onChange': {} }, { id: "category", modelValue: (__VLS_ctx.form.category_id), options: (__VLS_ctx.categories), optionLabel: "category_name", optionValue: "id", placeholder: "Select a category" }), { class: ({ 'p-invalid': __VLS_ctx.errors.category_id }) }), { loading: (__VLS_ctx.loadingCategories) })));
        var __VLS_51 = __VLS_50.apply(void 0, __spreadArray([__assign(__assign(__assign({ 'onChange': {} }, { id: "category", modelValue: (__VLS_ctx.form.category_id), options: (__VLS_ctx.categories), optionLabel: "category_name", optionValue: "id", placeholder: "Select a category" }), { class: ({ 'p-invalid': __VLS_ctx.errors.category_id }) }), { loading: (__VLS_ctx.loadingCategories) })], __VLS_functionalComponentArgsRest(__VLS_50), false));
        var __VLS_54 = void 0;
        var __VLS_55 = ({ change: {} },
            { onChange: (__VLS_ctx.onCategoryChange) });
        /** @type {__VLS_StyleScopedClasses['p-invalid']} */ ;
        var __VLS_52;
        var __VLS_53;
        if (__VLS_ctx.errors.category_id) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-red-500" }));
            /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
            (__VLS_ctx.errors.category_id);
        }
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "subcategory" }, { class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        var __VLS_56 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Select} */
        select_1.default;
        // @ts-ignore
        var __VLS_57 = __VLS_asFunctionalComponent1(__VLS_56, new __VLS_56({
            id: "subcategory",
            modelValue: (__VLS_ctx.form.subcategory_id),
            options: (__VLS_ctx.subcategories),
            optionLabel: "category_name",
            optionValue: "id",
            placeholder: "Select subcategory",
            showClear: true,
            disabled: (!__VLS_ctx.form.category_id),
        }));
        var __VLS_58 = __VLS_57.apply(void 0, __spreadArray([{
                id: "subcategory",
                modelValue: (__VLS_ctx.form.subcategory_id),
                options: (__VLS_ctx.subcategories),
                optionLabel: "category_name",
                optionValue: "id",
                placeholder: "Select subcategory",
                showClear: true,
                disabled: (!__VLS_ctx.form.category_id),
            }], __VLS_functionalComponentArgsRest(__VLS_57), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-2 gap-4" }));
        /** @type {__VLS_StyleScopedClasses['grid']} */ ;
        /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
        /** @type {__VLS_StyleScopedClasses['md:grid-cols-2']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "brand" }, { class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        var __VLS_61 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.InputText} */
        inputtext_1.default;
        // @ts-ignore
        var __VLS_62 = __VLS_asFunctionalComponent1(__VLS_61, new __VLS_61(__assign({ 'onInput': {} }, { id: "brand", modelValue: (__VLS_ctx.form.brand), placeholder: "e.g., IKEA, Ashley Furniture" })));
        var __VLS_63 = __VLS_62.apply(void 0, __spreadArray([__assign({ 'onInput': {} }, { id: "brand", modelValue: (__VLS_ctx.form.brand), placeholder: "e.g., IKEA, Ashley Furniture" })], __VLS_functionalComponentArgsRest(__VLS_62), false));
        var __VLS_66 = void 0;
        var __VLS_67 = ({ input: {} },
            { onInput: (__VLS_ctx.generateSKU) });
        var __VLS_64;
        var __VLS_65;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "collection" }, { class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        var __VLS_68 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.InputText} */
        inputtext_1.default;
        // @ts-ignore
        var __VLS_69 = __VLS_asFunctionalComponent1(__VLS_68, new __VLS_68({
            id: "collection",
            modelValue: (__VLS_ctx.form.collection_name),
            placeholder: "e.g., Summer 2024",
        }));
        var __VLS_70 = __VLS_69.apply(void 0, __spreadArray([{
                id: "collection",
                modelValue: (__VLS_ctx.form.collection_name),
                placeholder: "e.g., Summer 2024",
            }], __VLS_functionalComponentArgsRest(__VLS_69), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "stock_status" }, { class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
        /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
        var __VLS_73 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Select} */
        select_1.default;
        // @ts-ignore
        var __VLS_74 = __VLS_asFunctionalComponent1(__VLS_73, new __VLS_73({
            id: "stock_status",
            modelValue: (__VLS_ctx.form.stock_status),
            options: (__VLS_ctx.stockStatusOptions),
            placeholder: "Select stock status",
        }));
        var __VLS_75 = __VLS_74.apply(void 0, __spreadArray([{
                id: "stock_status",
                modelValue: (__VLS_ctx.form.stock_status),
                options: (__VLS_ctx.stockStatusOptions),
                placeholder: "Select stock status",
            }], __VLS_functionalComponentArgsRest(__VLS_74), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "description" }, { class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        var __VLS_78 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Textarea} */
        textarea_1.default;
        // @ts-ignore
        var __VLS_79 = __VLS_asFunctionalComponent1(__VLS_78, new __VLS_78({
            id: "description",
            modelValue: (__VLS_ctx.form.description),
            rows: "5",
            placeholder: "Enter detailed product description...",
        }));
        var __VLS_80 = __VLS_79.apply(void 0, __spreadArray([{
                id: "description",
                modelValue: (__VLS_ctx.form.description),
                rows: "5",
                placeholder: "Enter detailed product description...",
            }], __VLS_functionalComponentArgsRest(__VLS_79), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-wrap gap-4 pt-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-wrap']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['pt-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        var __VLS_83 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Checkbox} */
        checkbox_1.default;
        // @ts-ignore
        var __VLS_84 = __VLS_asFunctionalComponent1(__VLS_83, new __VLS_83({
            modelValue: (__VLS_ctx.form.is_featured),
            inputId: "featured",
            binary: (true),
        }));
        var __VLS_85 = __VLS_84.apply(void 0, __spreadArray([{
                modelValue: (__VLS_ctx.form.is_featured),
                inputId: "featured",
                binary: (true),
            }], __VLS_functionalComponentArgsRest(__VLS_84), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "featured" }, { class: "text-sm font-medium cursor-pointer" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        /** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        var __VLS_88 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Checkbox} */
        checkbox_1.default;
        // @ts-ignore
        var __VLS_89 = __VLS_asFunctionalComponent1(__VLS_88, new __VLS_88({
            modelValue: (__VLS_ctx.form.is_new_arrival),
            inputId: "newArrival",
            binary: (true),
        }));
        var __VLS_90 = __VLS_89.apply(void 0, __spreadArray([{
                modelValue: (__VLS_ctx.form.is_new_arrival),
                inputId: "newArrival",
                binary: (true),
            }], __VLS_functionalComponentArgsRest(__VLS_89), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "newArrival" }, { class: "text-sm font-medium cursor-pointer" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        /** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        var __VLS_93 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Checkbox} */
        checkbox_1.default;
        // @ts-ignore
        var __VLS_94 = __VLS_asFunctionalComponent1(__VLS_93, new __VLS_93({
            modelValue: (__VLS_ctx.form.is_bestseller),
            inputId: "bestseller",
            binary: (true),
        }));
        var __VLS_95 = __VLS_94.apply(void 0, __spreadArray([{
                modelValue: (__VLS_ctx.form.is_bestseller),
                inputId: "bestseller",
                binary: (true),
            }], __VLS_functionalComponentArgsRest(__VLS_94), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "bestseller" }, { class: "text-sm font-medium cursor-pointer" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        /** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        var __VLS_98 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Checkbox} */
        checkbox_1.default;
        // @ts-ignore
        var __VLS_99 = __VLS_asFunctionalComponent1(__VLS_98, new __VLS_98({
            modelValue: (__VLS_ctx.form.assembly_required),
            inputId: "assembly",
            binary: (true),
        }));
        var __VLS_100 = __VLS_99.apply(void 0, __spreadArray([{
                modelValue: (__VLS_ctx.form.assembly_required),
                inputId: "assembly",
                binary: (true),
            }], __VLS_functionalComponentArgsRest(__VLS_99), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "assembly" }, { class: "text-sm font-medium cursor-pointer" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        /** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        var __VLS_103 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Checkbox} */
        checkbox_1.default;
        // @ts-ignore
        var __VLS_104 = __VLS_asFunctionalComponent1(__VLS_103, new __VLS_103({
            modelValue: (__VLS_ctx.form.is_active),
            inputId: "active",
            binary: (true),
        }));
        var __VLS_105 = __VLS_104.apply(void 0, __spreadArray([{
                modelValue: (__VLS_ctx.form.is_active),
                inputId: "active",
                binary: (true),
            }], __VLS_functionalComponentArgsRest(__VLS_104), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "active" }, { class: "text-sm font-medium cursor-pointer" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        /** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
        // @ts-ignore
        [form, form, form, form, form, form, form, form, form, form, form, form, form, form, form, form, form, errors, errors, errors, errors, errors, errors, errors, errors, errors, generateSKU, generateSKU, generateSKU, copySKU, vTooltip, vTooltip, categories, loadingCategories, onCategoryChange, subcategories, stockStatusOptions,];
    }
    // @ts-ignore
    [];
    var __VLS_18;
    var __VLS_108 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    card_1.default;
    // @ts-ignore
    var __VLS_109 = __VLS_asFunctionalComponent1(__VLS_108, new __VLS_108({}));
    var __VLS_110 = __VLS_109.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_109), false));
    var __VLS_113 = __VLS_111.slots.default;
    {
        var __VLS_114 = __VLS_111.slots.title;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-dollar text-green-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-dollar']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-green-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        // @ts-ignore
        [];
    }
    {
        var __VLS_115 = __VLS_111.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-3 gap-4" }));
        /** @type {__VLS_StyleScopedClasses['grid']} */ ;
        /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
        /** @type {__VLS_StyleScopedClasses['md:grid-cols-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "base_price" }, { class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
        /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
        var __VLS_116 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
        inputnumber_1.default;
        // @ts-ignore
        var __VLS_117 = __VLS_asFunctionalComponent1(__VLS_116, new __VLS_116(__assign(__assign({ id: "base_price", modelValue: (__VLS_ctx.form.base_price), mode: "currency", currency: "PHP", locale: "en-PH" }, { class: ({ 'p-invalid': __VLS_ctx.errors.base_price }) }), { min: (0), fluid: true })));
        var __VLS_118 = __VLS_117.apply(void 0, __spreadArray([__assign(__assign({ id: "base_price", modelValue: (__VLS_ctx.form.base_price), mode: "currency", currency: "PHP", locale: "en-PH" }, { class: ({ 'p-invalid': __VLS_ctx.errors.base_price }) }), { min: (0), fluid: true })], __VLS_functionalComponentArgsRest(__VLS_117), false));
        /** @type {__VLS_StyleScopedClasses['p-invalid']} */ ;
        if (__VLS_ctx.errors.base_price) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-red-500" }));
            /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
            (__VLS_ctx.errors.base_price);
        }
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "discounted_price" }, { class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        var __VLS_121 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
        inputnumber_1.default;
        // @ts-ignore
        var __VLS_122 = __VLS_asFunctionalComponent1(__VLS_121, new __VLS_121({
            id: "discounted_price",
            modelValue: (__VLS_ctx.form.discounted_price),
            mode: "currency",
            currency: "PHP",
            locale: "en-PH",
            min: (0),
            fluid: true,
        }));
        var __VLS_123 = __VLS_122.apply(void 0, __spreadArray([{
                id: "discounted_price",
                modelValue: (__VLS_ctx.form.discounted_price),
                mode: "currency",
                currency: "PHP",
                locale: "en-PH",
                min: (0),
                fluid: true,
            }], __VLS_functionalComponentArgsRest(__VLS_122), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-gray-500 text-xs" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "tax_rate" }, { class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        var __VLS_126 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
        inputnumber_1.default;
        // @ts-ignore
        var __VLS_127 = __VLS_asFunctionalComponent1(__VLS_126, new __VLS_126({
            id: "tax_rate",
            modelValue: (__VLS_ctx.form.tax_rate),
            suffix: "%",
            min: (0),
            max: (100),
            minFractionDigits: (2),
            fluid: true,
        }));
        var __VLS_128 = __VLS_127.apply(void 0, __spreadArray([{
                id: "tax_rate",
                modelValue: (__VLS_ctx.form.tax_rate),
                suffix: "%",
                min: (0),
                max: (100),
                minFractionDigits: (2),
                fluid: true,
            }], __VLS_functionalComponentArgsRest(__VLS_127), false));
        if (__VLS_ctx.isEditMode && __VLS_ctx.originalBasePrice !== __VLS_ctx.form.base_price) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "mt-4" }));
            /** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "price_change_reason" }, { class: "text-sm font-semibold text-gray-700 block mb-2" }));
            /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
            /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
            /** @type {__VLS_StyleScopedClasses['block']} */ ;
            /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
            /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
            var __VLS_131 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.InputText} */
            inputtext_1.default;
            // @ts-ignore
            var __VLS_132 = __VLS_asFunctionalComponent1(__VLS_131, new __VLS_131(__assign({ id: "price_change_reason", modelValue: (__VLS_ctx.form.price_change_reason), placeholder: "e.g., Seasonal discount, Supplier cost increase" }, { class: "w-full" })));
            var __VLS_133 = __VLS_132.apply(void 0, __spreadArray([__assign({ id: "price_change_reason", modelValue: (__VLS_ctx.form.price_change_reason), placeholder: "e.g., Seasonal discount, Supplier cost increase" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_132), false));
            /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
        }
        // @ts-ignore
        [form, form, form, form, form, errors, errors, errors, isEditMode, originalBasePrice,];
    }
    // @ts-ignore
    [];
    var __VLS_111;
    var __VLS_136 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    card_1.default;
    // @ts-ignore
    var __VLS_137 = __VLS_asFunctionalComponent1(__VLS_136, new __VLS_136({}));
    var __VLS_138 = __VLS_137.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_137), false));
    var __VLS_141 = __VLS_139.slots.default;
    {
        var __VLS_142 = __VLS_139.slots.title;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-box text-purple-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-box']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-purple-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        // @ts-ignore
        [];
    }
    {
        var __VLS_143 = __VLS_139.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 md:grid-cols-4 gap-4" }));
        /** @type {__VLS_StyleScopedClasses['grid']} */ ;
        /** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
        /** @type {__VLS_StyleScopedClasses['md:grid-cols-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "length" }, { class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        var __VLS_144 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
        inputnumber_1.default;
        // @ts-ignore
        var __VLS_145 = __VLS_asFunctionalComponent1(__VLS_144, new __VLS_144({
            id: "length",
            modelValue: (__VLS_ctx.form.length_cm),
            minFractionDigits: (2),
            suffix: " cm",
            min: (0),
            fluid: true,
        }));
        var __VLS_146 = __VLS_145.apply(void 0, __spreadArray([{
                id: "length",
                modelValue: (__VLS_ctx.form.length_cm),
                minFractionDigits: (2),
                suffix: " cm",
                min: (0),
                fluid: true,
            }], __VLS_functionalComponentArgsRest(__VLS_145), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "width" }, { class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        var __VLS_149 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
        inputnumber_1.default;
        // @ts-ignore
        var __VLS_150 = __VLS_asFunctionalComponent1(__VLS_149, new __VLS_149({
            id: "width",
            modelValue: (__VLS_ctx.form.width_cm),
            minFractionDigits: (2),
            suffix: " cm",
            min: (0),
            fluid: true,
        }));
        var __VLS_151 = __VLS_150.apply(void 0, __spreadArray([{
                id: "width",
                modelValue: (__VLS_ctx.form.width_cm),
                minFractionDigits: (2),
                suffix: " cm",
                min: (0),
                fluid: true,
            }], __VLS_functionalComponentArgsRest(__VLS_150), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "height" }, { class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        var __VLS_154 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
        inputnumber_1.default;
        // @ts-ignore
        var __VLS_155 = __VLS_asFunctionalComponent1(__VLS_154, new __VLS_154({
            id: "height",
            modelValue: (__VLS_ctx.form.height_cm),
            minFractionDigits: (2),
            suffix: " cm",
            min: (0),
            fluid: true,
        }));
        var __VLS_156 = __VLS_155.apply(void 0, __spreadArray([{
                id: "height",
                modelValue: (__VLS_ctx.form.height_cm),
                minFractionDigits: (2),
                suffix: " cm",
                min: (0),
                fluid: true,
            }], __VLS_functionalComponentArgsRest(__VLS_155), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "weight" }, { class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        var __VLS_159 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
        inputnumber_1.default;
        // @ts-ignore
        var __VLS_160 = __VLS_asFunctionalComponent1(__VLS_159, new __VLS_159({
            id: "weight",
            modelValue: (__VLS_ctx.form.weight_kg),
            minFractionDigits: (2),
            suffix: " kg",
            min: (0),
            fluid: true,
        }));
        var __VLS_161 = __VLS_160.apply(void 0, __spreadArray([{
                id: "weight",
                modelValue: (__VLS_ctx.form.weight_kg),
                minFractionDigits: (2),
                suffix: " kg",
                min: (0),
                fluid: true,
            }], __VLS_functionalComponentArgsRest(__VLS_160), false));
        // @ts-ignore
        [form, form, form, form,];
    }
    // @ts-ignore
    [];
    var __VLS_139;
    var __VLS_164 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    card_1.default;
    // @ts-ignore
    var __VLS_165 = __VLS_asFunctionalComponent1(__VLS_164, new __VLS_164({}));
    var __VLS_166 = __VLS_165.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_165), false));
    var __VLS_169 = __VLS_167.slots.default;
    {
        var __VLS_170 = __VLS_167.slots.title;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-search text-orange-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-search']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-orange-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        // @ts-ignore
        [];
    }
    {
        var __VLS_171 = __VLS_167.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
        /** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "meta_title" }, { class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        var __VLS_172 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.InputText} */
        inputtext_1.default;
        // @ts-ignore
        var __VLS_173 = __VLS_asFunctionalComponent1(__VLS_172, new __VLS_172({
            id: "meta_title",
            modelValue: (__VLS_ctx.form.meta_title),
            placeholder: "SEO optimized title",
            maxlength: "60",
        }));
        var __VLS_174 = __VLS_173.apply(void 0, __spreadArray([{
                id: "meta_title",
                modelValue: (__VLS_ctx.form.meta_title),
                placeholder: "SEO optimized title",
                maxlength: "60",
            }], __VLS_functionalComponentArgsRest(__VLS_173), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (((_a = __VLS_ctx.form.meta_title) === null || _a === void 0 ? void 0 : _a.length) || 0);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "meta_description" }, { class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        var __VLS_177 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Textarea} */
        textarea_1.default;
        // @ts-ignore
        var __VLS_178 = __VLS_asFunctionalComponent1(__VLS_177, new __VLS_177({
            id: "meta_description",
            modelValue: (__VLS_ctx.form.meta_description),
            rows: "3",
            placeholder: "SEO optimized description",
            maxlength: "160",
        }));
        var __VLS_179 = __VLS_178.apply(void 0, __spreadArray([{
                id: "meta_description",
                modelValue: (__VLS_ctx.form.meta_description),
                rows: "3",
                placeholder: "SEO optimized description",
                maxlength: "160",
            }], __VLS_functionalComponentArgsRest(__VLS_178), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (((_b = __VLS_ctx.form.meta_description) === null || _b === void 0 ? void 0 : _b.length) || 0);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "meta_keywords" }, { class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        var __VLS_182 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.InputText} */
        inputtext_1.default;
        // @ts-ignore
        var __VLS_183 = __VLS_asFunctionalComponent1(__VLS_182, new __VLS_182({
            id: "meta_keywords",
            modelValue: (__VLS_ctx.form.meta_keywords),
            placeholder: "furniture, sofa, modern, living room",
        }));
        var __VLS_184 = __VLS_183.apply(void 0, __spreadArray([{
                id: "meta_keywords",
                modelValue: (__VLS_ctx.form.meta_keywords),
                placeholder: "furniture, sofa, modern, living room",
            }], __VLS_functionalComponentArgsRest(__VLS_183), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "published_at" }, { class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        var __VLS_187 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.DatePicker} */
        datepicker_1.default;
        // @ts-ignore
        var __VLS_188 = __VLS_asFunctionalComponent1(__VLS_187, new __VLS_187(__assign({ id: "published_at", modelValue: (__VLS_ctx.form.published_at), showTime: true, hourFormat: "24" }, { class: "w-full" })));
        var __VLS_189 = __VLS_188.apply(void 0, __spreadArray([__assign({ id: "published_at", modelValue: (__VLS_ctx.form.published_at), showTime: true, hourFormat: "24" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_188), false));
        /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
        // @ts-ignore
        [form, form, form, form, form, form,];
    }
    // @ts-ignore
    [];
    var __VLS_167;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "lg:col-span-1" }));
    /** @type {__VLS_StyleScopedClasses['lg:col-span-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "sticky top-6" }));
    /** @type {__VLS_StyleScopedClasses['sticky']} */ ;
    /** @type {__VLS_StyleScopedClasses['top-6']} */ ;
    var __VLS_192 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    card_1.default;
    // @ts-ignore
    var __VLS_193 = __VLS_asFunctionalComponent1(__VLS_192, new __VLS_192({}));
    var __VLS_194 = __VLS_193.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_193), false));
    var __VLS_197 = __VLS_195.slots.default;
    {
        var __VLS_198 = __VLS_195.slots.title;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-cube text-indigo-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-cube']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-indigo-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        // @ts-ignore
        [];
    }
    {
        var __VLS_199 = __VLS_195.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-6" }));
        /** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-3" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
        /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
        if (!__VLS_ctx.form.modelFile && !__VLS_ctx.existingModel) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign(__assign(__assign({ onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!!(__VLS_ctx.loadingData))
                        return;
                    if (!(!__VLS_ctx.form.modelFile && !__VLS_ctx.existingModel))
                        return;
                    __VLS_ctx.$refs.modelInput.click();
                    // @ts-ignore
                    [form, existingModel, $refs,];
                } }, { onDragover: function () { } }), { onDrop: (__VLS_ctx.handleModelDrop) }), { class: "border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-400 transition-colors cursor-pointer" }));
            /** @type {__VLS_StyleScopedClasses['border-2']} */ ;
            /** @type {__VLS_StyleScopedClasses['border-dashed']} */ ;
            /** @type {__VLS_StyleScopedClasses['border-gray-300']} */ ;
            /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
            /** @type {__VLS_StyleScopedClasses['p-6']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['hover:border-blue-400']} */ ;
            /** @type {__VLS_StyleScopedClasses['transition-colors']} */ ;
            /** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-cloud-upload text-4xl text-gray-400 mb-3 block" }));
            /** @type {__VLS_StyleScopedClasses['pi']} */ ;
            /** @type {__VLS_StyleScopedClasses['pi-cloud-upload']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-4xl']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
            /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
            /** @type {__VLS_StyleScopedClasses['block']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm font-medium text-gray-700 mb-1" }));
            /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
            /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
            /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
            /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-400 mt-2" }));
            /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
            /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
        }
        __VLS_asFunctionalElement1(__VLS_intrinsics.input)(__assign(__assign({ onChange: (__VLS_ctx.handleModelSelect) }, { ref: "modelInput", type: "file", accept: ".glb,.gltf" }), { class: "hidden" }));
        /** @type {__VLS_StyleScopedClasses['hidden']} */ ;
        if (__VLS_ctx.form.modelFile) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-linear-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-4" }));
            /** @type {__VLS_StyleScopedClasses['bg-linear-to-br']} */ ;
            /** @type {__VLS_StyleScopedClasses['from-blue-50']} */ ;
            /** @type {__VLS_StyleScopedClasses['to-indigo-50']} */ ;
            /** @type {__VLS_StyleScopedClasses['border']} */ ;
            /** @type {__VLS_StyleScopedClasses['border-blue-200']} */ ;
            /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
            /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-start justify-between mb-3" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['items-start']} */ ;
            /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
            /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-3" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-blue-600 rounded-lg p-2" }));
            /** @type {__VLS_StyleScopedClasses['bg-blue-600']} */ ;
            /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
            /** @type {__VLS_StyleScopedClasses['p-2']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-cube text-white text-xl" }));
            /** @type {__VLS_StyleScopedClasses['pi']} */ ;
            /** @type {__VLS_StyleScopedClasses['pi-cube']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-white']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-xl']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm font-semibold text-gray-800" }));
            /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
            /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
            (__VLS_ctx.form.modelFile.name);
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600" }));
            /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
            (__VLS_ctx.formatFileSize(__VLS_ctx.form.modelFile.size));
            var __VLS_200 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            button_1.default;
            // @ts-ignore
            var __VLS_201 = __VLS_asFunctionalComponent1(__VLS_200, new __VLS_200(__assign({ 'onClick': {} }, { icon: "pi pi-times", severity: "danger", text: true, rounded: true, size: "small" })));
            var __VLS_202 = __VLS_201.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-times", severity: "danger", text: true, rounded: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_201), false));
            var __VLS_205 = void 0;
            var __VLS_206 = ({ click: {} },
                { onClick: (__VLS_ctx.removeModel) });
            var __VLS_203;
            var __VLS_204;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2 text-xs text-green-700 bg-green-50 px-3 py-2 rounded" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-green-700']} */ ;
            /** @type {__VLS_StyleScopedClasses['bg-green-50']} */ ;
            /** @type {__VLS_StyleScopedClasses['px-3']} */ ;
            /** @type {__VLS_StyleScopedClasses['py-2']} */ ;
            /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-check-circle" }));
            /** @type {__VLS_StyleScopedClasses['pi']} */ ;
            /** @type {__VLS_StyleScopedClasses['pi-check-circle']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        }
        if (__VLS_ctx.existingModel) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 border border-gray-200 rounded-lg p-4" }));
            /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
            /** @type {__VLS_StyleScopedClasses['border']} */ ;
            /** @type {__VLS_StyleScopedClasses['border-gray-200']} */ ;
            /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
            /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-start justify-between mb-3" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['items-start']} */ ;
            /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
            /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-3" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-600 rounded-lg p-2" }));
            /** @type {__VLS_StyleScopedClasses['bg-gray-600']} */ ;
            /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
            /** @type {__VLS_StyleScopedClasses['p-2']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-cube text-white text-xl" }));
            /** @type {__VLS_StyleScopedClasses['pi']} */ ;
            /** @type {__VLS_StyleScopedClasses['pi-cube']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-white']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-xl']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm font-semibold text-gray-800" }));
            /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
            /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
            (__VLS_ctx.existingModel.file_name);
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600" }));
            /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
            (__VLS_ctx.formatFileSize(__VLS_ctx.existingModel.file_size_kb * 1024));
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500 mt-1" }));
            /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
            /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
            ((_c = __VLS_ctx.existingModel.model_format) === null || _c === void 0 ? void 0 : _c.toUpperCase());
            var __VLS_207 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            button_1.default;
            // @ts-ignore
            var __VLS_208 = __VLS_asFunctionalComponent1(__VLS_207, new __VLS_207(__assign({ 'onClick': {} }, { icon: "pi pi-trash", severity: "danger", text: true, rounded: true, size: "small" })));
            var __VLS_209 = __VLS_208.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-trash", severity: "danger", text: true, rounded: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_208), false));
            var __VLS_212 = void 0;
            var __VLS_213 = ({ click: {} },
                { onClick: (__VLS_ctx.deleteExistingModel) });
            var __VLS_210;
            var __VLS_211;
            var __VLS_214 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Tag} */
            tag_1.default;
            // @ts-ignore
            var __VLS_215 = __VLS_asFunctionalComponent1(__VLS_214, new __VLS_214(__assign({ value: "Uploaded", severity: "success" }, { class: "w-full justify-center" })));
            var __VLS_216 = __VLS_215.apply(void 0, __spreadArray([__assign({ value: "Uploaded", severity: "success" }, { class: "w-full justify-center" })], __VLS_functionalComponentArgsRest(__VLS_215), false));
            /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
            /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
        }
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "border-t border-gray-200 pt-4" }));
        /** @type {__VLS_StyleScopedClasses['border-t']} */ ;
        /** @type {__VLS_StyleScopedClasses['border-gray-200']} */ ;
        /** @type {__VLS_StyleScopedClasses['pt-4']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.h4, __VLS_intrinsics.h4)(__assign({ class: "text-sm font-semibold text-gray-700 mb-3" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-3" }));
        /** @type {__VLS_StyleScopedClasses['space-y-3']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-xs font-medium text-gray-600" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        var __VLS_219 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
        inputnumber_1.default;
        // @ts-ignore
        var __VLS_220 = __VLS_asFunctionalComponent1(__VLS_219, new __VLS_219({
            modelValue: (__VLS_ctx.form.default_camera_angle_x),
            min: (-180),
            max: (180),
            suffix: "°",
            showButtons: true,
            buttonLayout: "horizontal",
            step: (5),
        }));
        var __VLS_221 = __VLS_220.apply(void 0, __spreadArray([{
                modelValue: (__VLS_ctx.form.default_camera_angle_x),
                min: (-180),
                max: (180),
                suffix: "°",
                showButtons: true,
                buttonLayout: "horizontal",
                step: (5),
            }], __VLS_functionalComponentArgsRest(__VLS_220), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-xs font-medium text-gray-600" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        var __VLS_224 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
        inputnumber_1.default;
        // @ts-ignore
        var __VLS_225 = __VLS_asFunctionalComponent1(__VLS_224, new __VLS_224({
            modelValue: (__VLS_ctx.form.default_camera_angle_y),
            min: (-180),
            max: (180),
            suffix: "°",
            showButtons: true,
            buttonLayout: "horizontal",
            step: (5),
        }));
        var __VLS_226 = __VLS_225.apply(void 0, __spreadArray([{
                modelValue: (__VLS_ctx.form.default_camera_angle_y),
                min: (-180),
                max: (180),
                suffix: "°",
                showButtons: true,
                buttonLayout: "horizontal",
                step: (5),
            }], __VLS_functionalComponentArgsRest(__VLS_225), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-xs font-medium text-gray-600" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        var __VLS_229 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
        inputnumber_1.default;
        // @ts-ignore
        var __VLS_230 = __VLS_asFunctionalComponent1(__VLS_229, new __VLS_229({
            modelValue: (__VLS_ctx.form.default_zoom_level),
            min: (0.1),
            max: (10),
            minFractionDigits: (1),
            showButtons: true,
            buttonLayout: "horizontal",
            step: (0.1),
        }));
        var __VLS_231 = __VLS_230.apply(void 0, __spreadArray([{
                modelValue: (__VLS_ctx.form.default_zoom_level),
                min: (0.1),
                max: (10),
                minFractionDigits: (1),
                showButtons: true,
                buttonLayout: "horizontal",
                step: (0.1),
            }], __VLS_functionalComponentArgsRest(__VLS_230), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "border-t border-gray-200 pt-4" }));
        /** @type {__VLS_StyleScopedClasses['border-t']} */ ;
        /** @type {__VLS_StyleScopedClasses['border-gray-200']} */ ;
        /** @type {__VLS_StyleScopedClasses['pt-4']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.h4, __VLS_intrinsics.h4)(__assign({ class: "text-sm font-semibold text-gray-700 mb-3" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
        var __VLS_234 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.FileUpload} */
        fileupload_1.default;
        // @ts-ignore
        var __VLS_235 = __VLS_asFunctionalComponent1(__VLS_234, new __VLS_234(__assign(__assign({ 'onSelect': {} }, { mode: "basic", name: "images[]", accept: "image/*", maxFileSize: (5000000), multiple: (true), auto: (false), chooseLabel: "Upload Images" }), { class: "w-full" })));
        var __VLS_236 = __VLS_235.apply(void 0, __spreadArray([__assign(__assign({ 'onSelect': {} }, { mode: "basic", name: "images[]", accept: "image/*", maxFileSize: (5000000), multiple: (true), auto: (false), chooseLabel: "Upload Images" }), { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_235), false));
        var __VLS_239 = void 0;
        var __VLS_240 = ({ select: {} },
            { onSelect: (__VLS_ctx.handleImageSelect) });
        /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
        var __VLS_237;
        var __VLS_238;
        __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-gray-500 text-xs block mt-2" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['block']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
        if (__VLS_ctx.form.imageFiles && __VLS_ctx.form.imageFiles.length > 0) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-2 mt-4" }));
            /** @type {__VLS_StyleScopedClasses['grid']} */ ;
            /** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
            /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
            /** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
            var _loop_1 = function (image, index) {
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (index) }, { class: "relative group" }));
                /** @type {__VLS_StyleScopedClasses['relative']} */ ;
                /** @type {__VLS_StyleScopedClasses['group']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.img)(__assign({ src: (__VLS_ctx.getImagePreview(image)) }, { class: "w-full h-24 object-cover rounded-lg border border-gray-200" }));
                /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
                /** @type {__VLS_StyleScopedClasses['h-24']} */ ;
                /** @type {__VLS_StyleScopedClasses['object-cover']} */ ;
                /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
                /** @type {__VLS_StyleScopedClasses['border']} */ ;
                /** @type {__VLS_StyleScopedClasses['border-gray-200']} */ ;
                if (index === 0) {
                    var __VLS_241 = void 0;
                    /** @ts-ignore @type {typeof __VLS_components.Badge} */
                    badge_1.default;
                    // @ts-ignore
                    var __VLS_242 = __VLS_asFunctionalComponent1(__VLS_241, new __VLS_241(__assign({ value: "Primary", severity: "success" }, { class: "absolute top-1 left-1 text-xs" })));
                    var __VLS_243 = __VLS_242.apply(void 0, __spreadArray([__assign({ value: "Primary", severity: "success" }, { class: "absolute top-1 left-1 text-xs" })], __VLS_functionalComponentArgsRest(__VLS_242), false));
                    /** @type {__VLS_StyleScopedClasses['absolute']} */ ;
                    /** @type {__VLS_StyleScopedClasses['top-1']} */ ;
                    /** @type {__VLS_StyleScopedClasses['left-1']} */ ;
                    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
                }
                var __VLS_246 = void 0;
                /** @ts-ignore @type {typeof __VLS_components.Button} */
                button_1.default;
                // @ts-ignore
                var __VLS_247 = __VLS_asFunctionalComponent1(__VLS_246, new __VLS_246(__assign(__assign({ 'onClick': {} }, { icon: "pi pi-times", severity: "danger", rounded: true, size: "small" }), { class: "absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity" })));
                var __VLS_248 = __VLS_247.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { icon: "pi pi-times", severity: "danger", rounded: true, size: "small" }), { class: "absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity" })], __VLS_functionalComponentArgsRest(__VLS_247), false));
                var __VLS_251 = void 0;
                var __VLS_252 = ({ click: {} },
                    { onClick: function () {
                            var _a = [];
                            for (var _i = 0; _i < arguments.length; _i++) {
                                _a[_i] = arguments[_i];
                            }
                            var $event = _a[0];
                            if (!!(__VLS_ctx.loadingData))
                                return;
                            if (!(__VLS_ctx.form.imageFiles && __VLS_ctx.form.imageFiles.length > 0))
                                return;
                            __VLS_ctx.removeImage(index);
                            // @ts-ignore
                            [form, form, form, form, form, form, form, form, form, existingModel, existingModel, existingModel, existingModel, handleModelDrop, handleModelSelect, formatFileSize, formatFileSize, removeModel, deleteExistingModel, handleImageSelect, getImagePreview, removeImage,];
                        } });
                /** @type {__VLS_StyleScopedClasses['absolute']} */ ;
                /** @type {__VLS_StyleScopedClasses['top-1']} */ ;
                /** @type {__VLS_StyleScopedClasses['right-1']} */ ;
                /** @type {__VLS_StyleScopedClasses['opacity-0']} */ ;
                /** @type {__VLS_StyleScopedClasses['group-hover:opacity-100']} */ ;
                /** @type {__VLS_StyleScopedClasses['transition-opacity']} */ ;
                // @ts-ignore
                [];
            };
            var __VLS_249, __VLS_250;
            for (var _i = 0, _d = __VLS_vFor((__VLS_ctx.form.imageFiles)); _i < _d.length; _i++) {
                var _e = _d[_i], image = _e[0], index = _e[1];
                _loop_1(image, index);
            }
        }
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-blue-50 border border-blue-200 rounded-lg p-3" }));
        /** @type {__VLS_StyleScopedClasses['bg-blue-50']} */ ;
        /** @type {__VLS_StyleScopedClasses['border']} */ ;
        /** @type {__VLS_StyleScopedClasses['border-blue-200']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-start gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-start']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-info-circle text-blue-600 text-sm mt-0.5" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-info-circle']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-0.5']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-blue-800" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-blue-800']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-semibold mb-1" }));
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.ul, __VLS_intrinsics.ul)(__assign({ class: "list-disc list-inside space-y-1" }));
        /** @type {__VLS_StyleScopedClasses['list-disc']} */ ;
        /** @type {__VLS_StyleScopedClasses['list-inside']} */ ;
        /** @type {__VLS_StyleScopedClasses['space-y-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.li, __VLS_intrinsics.li)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.li, __VLS_intrinsics.li)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.li, __VLS_intrinsics.li)({});
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_195;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-end gap-3 mt-6 pt-6 border-t border-gray-200" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-end']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-6']} */ ;
    /** @type {__VLS_StyleScopedClasses['pt-6']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-t']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-gray-200']} */ ;
    var __VLS_253 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_254 = __VLS_asFunctionalComponent1(__VLS_253, new __VLS_253(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", outlined: true })));
    var __VLS_255 = __VLS_254.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_254), false));
    var __VLS_258 = void 0;
    var __VLS_259 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                if (!!(__VLS_ctx.loadingData))
                    return;
                __VLS_ctx.router.push({ name: 'merchandising.products' });
                // @ts-ignore
                [router,];
            } });
    var __VLS_256;
    var __VLS_257;
    var __VLS_260 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_261 = __VLS_asFunctionalComponent1(__VLS_260, new __VLS_260(__assign({ 'onClick': {} }, { label: (__VLS_ctx.isEditMode ? 'Update Product' : 'Create Product'), icon: "pi pi-check", loading: (__VLS_ctx.submitting) })));
    var __VLS_262 = __VLS_261.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: (__VLS_ctx.isEditMode ? 'Update Product' : 'Create Product'), icon: "pi pi-check", loading: (__VLS_ctx.submitting) })], __VLS_functionalComponentArgsRest(__VLS_261), false));
    var __VLS_265 = void 0;
    var __VLS_266 = ({ click: {} },
        { onClick: (__VLS_ctx.handleSubmit) });
    var __VLS_263;
    var __VLS_264;
}
// @ts-ignore
[handleSubmit, isEditMode, submitting,];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
