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
var _a, _b, _c, _d;
Object.defineProperty(exports, "__esModule", { value: true });
var vue_1 = require("vue");
var vue_router_1 = require("vue-router");
var usetoast_1 = require("primevue/usetoast");
var THREE = require("three");
var GLTFLoader_1 = require("three/examples/jsm/loaders/GLTFLoader");
var OBJLoader_1 = require("three/examples/jsm/loaders/OBJLoader");
var OrbitControls_1 = require("three/examples/jsm/controls/OrbitControls");
var merchandising_service_1 = require("../../../../services/merchandising.service");
var card_1 = require("primevue/card");
var button_1 = require("primevue/button");
var tag_1 = require("primevue/tag");
var badge_1 = require("primevue/badge");
var datatable_1 = require("primevue/datatable");
var column_1 = require("primevue/column");
var dialog_1 = require("primevue/dialog");
var skeleton_1 = require("primevue/skeleton");
var progressspinner_1 = require("primevue/progressspinner");
var route = (0, vue_router_1.useRoute)();
var router = (0, vue_router_1.useRouter)();
var toast = (0, usetoast_1.useToast)();
var productId = (0, vue_1.computed)(function () { return Number(route.params.id); });
var loading = (0, vue_1.ref)(false);
var loading3D = (0, vue_1.ref)(false);
var model3DError = (0, vue_1.ref)(false);
var deleting = (0, vue_1.ref)(false);
var deleteDialogVisible = (0, vue_1.ref)(false);
var galleryVisible = (0, vue_1.ref)(false);
var autoRotate = (0, vue_1.ref)(false);
var product = (0, vue_1.ref)(null);
var variations = (0, vue_1.ref)([]);
var allAssets = (0, vue_1.ref)([]);
var primary3DModel = (0, vue_1.ref)(null);
var productImages = (0, vue_1.ref)([]);
var selectedImage = (0, vue_1.ref)(null);
// 3D Viewer refs
var viewer3DContainer = (0, vue_1.ref)(null);
var scene = null;
var camera = null;
var renderer = null;
var controls = null;
var model = null;
var animationId = null;
var loadProduct = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, error_1;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                loading.value = true;
                _c.label = 1;
            case 1:
                _c.trys.push([1, 5, 6, 7]);
                return [4 /*yield*/, merchandising_service_1.default.getProduct(productId.value)];
            case 2:
                response = _c.sent();
                product.value = response.data;
                if (!response.data.id) return [3 /*break*/, 4];
                return [4 /*yield*/, Promise.all([
                        loadVariations(),
                        loadAssets()
                    ])];
            case 3:
                _c.sent();
                _c.label = 4;
            case 4: return [3 /*break*/, 7];
            case 5:
                error_1 = _c.sent();
                console.error('Failed to load product:', error_1);
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_b = (_a = error_1.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to load product',
                    life: 5000
                });
                return [3 /*break*/, 7];
            case 6:
                loading.value = false;
                return [7 /*endfinally*/];
            case 7: return [2 /*return*/];
        }
    });
}); };
var loadVariations = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, error_2;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                _a.trys.push([0, 2, , 3]);
                return [4 /*yield*/, merchandising_service_1.default.getVariationsByProduct(productId.value)];
            case 1:
                response = _a.sent();
                variations.value = response.data.variations || response.data.data || [];
                return [3 /*break*/, 3];
            case 2:
                error_2 = _a.sent();
                console.error('Failed to load variations:', error_2);
                return [3 /*break*/, 3];
            case 3: return [2 /*return*/];
        }
    });
}); };
var loadAssets = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, models, mainImages, galleryImages, error_3;
    var _a, _b, _c;
    return __generator(this, function (_d) {
        switch (_d.label) {
            case 0:
                _d.trys.push([0, 2, , 3]);
                return [4 /*yield*/, merchandising_service_1.default.getAssetsByProduct(productId.value)];
            case 1:
                response = _d.sent();
                allAssets.value = response.data.all_assets || [];
                models = ((_a = response.data.assets_by_type) === null || _a === void 0 ? void 0 : _a['3D_Model']) || [];
                primary3DModel.value = models.find(function (m) { return m.is_primary; }) || models[0] || null;
                mainImages = ((_b = response.data.assets_by_type) === null || _b === void 0 ? void 0 : _b['Image_Main']) || [];
                galleryImages = ((_c = response.data.assets_by_type) === null || _c === void 0 ? void 0 : _c['Image_Gallery']) || [];
                productImages.value = __spreadArray(__spreadArray([], mainImages, true), galleryImages, true);
                // Initialize 3D viewer if model exists
                if (primary3DModel.value && viewer3DContainer.value) {
                    (0, vue_1.nextTick)(function () {
                        init3DViewer();
                    });
                }
                return [3 /*break*/, 3];
            case 2:
                error_3 = _d.sent();
                console.error('Failed to load assets:', error_3);
                return [3 /*break*/, 3];
            case 3: return [2 /*return*/];
        }
    });
}); };
var init3DViewer = function () {
    var _a, _b, _c, _d;
    if (!viewer3DContainer.value || !primary3DModel.value) {
        console.warn('Cannot init 3D viewer: missing container or model');
        return;
    }
    loading3D.value = true;
    model3DError.value = false;
    try {
        // Cleanup existing scene
        cleanup3DScene();
        var container = viewer3DContainer.value;
        var width = container.clientWidth;
        var height = container.clientHeight;
        // Scene setup
        scene = new THREE.Scene();
        scene.background = new THREE.Color(0xf5f5f5);
        // Camera
        camera = new THREE.PerspectiveCamera(50, width / height, 0.1, 1000);
        camera.position.set(((_a = primary3DModel.value.camera_settings) === null || _a === void 0 ? void 0 : _a.angle_x) || 2, ((_b = primary3DModel.value.camera_settings) === null || _b === void 0 ? void 0 : _b.angle_y) || 2, ((_c = primary3DModel.value.camera_settings) === null || _c === void 0 ? void 0 : _c.zoom) || 5);
        // Renderer
        renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
        renderer.setSize(width, height);
        renderer.setPixelRatio(window.devicePixelRatio);
        renderer.shadowMap.enabled = true;
        renderer.shadowMap.type = THREE.PCFSoftShadowMap;
        container.appendChild(renderer.domElement);
        // Lights
        var ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
        scene.add(ambientLight);
        var directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
        directionalLight.position.set(5, 10, 7.5);
        directionalLight.castShadow = true;
        scene.add(directionalLight);
        var fillLight = new THREE.DirectionalLight(0xffffff, 0.3);
        fillLight.position.set(-5, 0, -5);
        scene.add(fillLight);
        // Controls
        controls = new OrbitControls_1.OrbitControls(camera, renderer.domElement);
        controls.enableDamping = true;
        controls.dampingFactor = 0.05;
        controls.minDistance = 1;
        controls.maxDistance = 20;
        controls.maxPolarAngle = Math.PI / 2;
        // Load 3D model
        var modelFormat_1 = (_d = primary3DModel.value.model_format) === null || _d === void 0 ? void 0 : _d.toLowerCase();
        var loader = modelFormat_1 === 'obj' ? new OBJLoader_1.OBJLoader() : new GLTFLoader_1.GLTFLoader();
        console.log('Loading 3D model:', primary3DModel.value.url, 'Format:', modelFormat_1);
        loader.load(primary3DModel.value.url, function (loadedModel) {
            model = modelFormat_1 === 'obj' ? loadedModel : loadedModel.scene;
            // Center and scale model
            var box = new THREE.Box3().setFromObject(model);
            var center = box.getCenter(new THREE.Vector3());
            var size = box.getSize(new THREE.Vector3());
            var maxDim = Math.max(size.x, size.y, size.z);
            var scale = 3 / maxDim;
            model.scale.multiplyScalar(scale);
            model.position.sub(center.multiplyScalar(scale));
            // Enable shadows
            model.traverse(function (child) {
                if (child.isMesh) {
                    child.castShadow = true;
                    child.receiveShadow = true;
                }
            });
            scene === null || scene === void 0 ? void 0 : scene.add(model);
            loading3D.value = false;
            console.log('3D model loaded successfully');
            // Start animation
            animate();
        }, function (progress) {
            var percent = (progress.loaded / progress.total * 100).toFixed(0);
            console.log("Loading 3D model: ".concat(percent, "%"));
        }, function (error) {
            console.error('Error loading 3D model:', error);
            loading3D.value = false;
            model3DError.value = true;
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to load 3D model',
                life: 3000
            });
        });
    }
    catch (error) {
        console.error('Error initializing 3D viewer:', error);
        loading3D.value = false;
        model3DError.value = true;
    }
};
var animate = function () {
    if (!scene || !camera || !renderer || !controls)
        return;
    animationId = requestAnimationFrame(animate);
    if (autoRotate.value && model) {
        model.rotation.y += 0.005;
    }
    controls.update();
    renderer.render(scene, camera);
};
var reset3DView = function () {
    if (controls) {
        controls.reset();
    }
};
var toggleAutoRotate = function () {
    autoRotate.value = !autoRotate.value;
};
var take3DScreenshot = function () {
    if (!renderer)
        return;
    var dataURL = renderer.domElement.toDataURL('image/png');
    var link = document.createElement('a');
    link.download = "".concat(product.value.sku, "-3d-preview.png");
    link.href = dataURL;
    link.click();
    toast.add({
        severity: 'success',
        summary: 'Screenshot Saved',
        detail: '3D preview downloaded',
        life: 2000
    });
};
var toggle3DFullscreen = function () {
    if (!viewer3DContainer.value)
        return;
    if (viewer3DContainer.value.requestFullscreen) {
        viewer3DContainer.value.requestFullscreen();
    }
};
var retryLoad3D = function () {
    model3DError.value = false;
    init3DViewer();
};
var cleanup3DScene = function () {
    if (animationId) {
        cancelAnimationFrame(animationId);
        animationId = null;
    }
    if (renderer) {
        renderer.dispose();
        if (viewer3DContainer.value && renderer.domElement.parentNode === viewer3DContainer.value) {
            viewer3DContainer.value.removeChild(renderer.domElement);
        }
        renderer = null;
    }
    if (controls) {
        controls.dispose();
        controls = null;
    }
    scene = null;
    camera = null;
    model = null;
};
var confirmDelete = function () {
    deleteDialogVisible.value = true;
};
var deleteProduct = function () { return __awaiter(void 0, void 0, void 0, function () {
    var error_4;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                deleting.value = true;
                _c.label = 1;
            case 1:
                _c.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, merchandising_service_1.default.deleteProduct(productId.value)];
            case 2:
                _c.sent();
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Product deleted successfully',
                    life: 3000
                });
                router.push({ name: 'merchandising.products' });
                return [3 /*break*/, 5];
            case 3:
                error_4 = _c.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_b = (_a = error_4.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to delete product',
                    life: 3000
                });
                return [3 /*break*/, 5];
            case 4:
                deleting.value = false;
                deleteDialogVisible.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var manageVariations = function () {
    router.push({
        name: 'merchandising.variations',
        query: { product_id: productId.value }
    });
};
var openImageGallery = function () {
    galleryVisible.value = true;
};
var downloadAsset = function (asset) {
    window.open(asset.url, '_blank');
    toast.add({
        severity: 'success',
        summary: 'Download Started',
        detail: "Downloading ".concat(asset.file_name),
        life: 2000
    });
};
var handleImageError = function (event) {
    var img = event.target;
    img.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="400" height="300"%3E%3Crect fill="%23f0f0f0" width="400" height="300"/%3E%3Ctext fill="%23999" x="50%25" y="50%25" dominant-baseline="middle" text-anchor="middle"%3EImage not found%3C/text%3E%3C/svg%3E';
};
var getAssetIcon = function (assetType) {
    var icons = {
        '3D_Model': 'pi pi-cube',
        '3D_Thumbnail': 'pi pi-image',
        'Image_Main': 'pi pi-image',
        'Image_Gallery': 'pi pi-images',
        'Image_360': 'pi pi-sync',
        'Video_Product': 'pi pi-video',
        'Video_Assembly': 'pi pi-wrench',
        'Manual_PDF': 'pi pi-file-pdf',
        'Texture_Map': 'pi pi-palette'
    };
    return icons[assetType] || 'pi pi-file';
};
var getAssetTypeLabel = function (assetType) {
    return assetType.replace(/_/g, ' ');
};
var getStockSeverity = function (status) {
    var severities = {
        'In Stock': 'success',
        'Low Stock': 'warning',
        'Out of Stock': 'danger',
        'Pre-order': 'info'
    };
    return severities[status] || 'secondary';
};
var formatPrice = function (price) {
    return new Intl.NumberFormat('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(price);
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
// Watch for 3D model changes
(0, vue_1.watch)(primary3DModel, function (newModel) {
    if (newModel && viewer3DContainer.value) {
        (0, vue_1.nextTick)(function () {
            init3DViewer();
        });
    }
});
(0, vue_1.onMounted)(function () {
    loadProduct();
});
// Cleanup on unmount
(0, vue_1.onBeforeUnmount)(function () {
    cleanup3DScene();
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
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:flex-row']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-3" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
var __VLS_0;
/** @ts-ignore @type {typeof __VLS_components.Button} */
button_1.default;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ 'onClick': {} }, { icon: "pi pi-arrow-left", text: true, rounded: true })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-arrow-left", text: true, rounded: true })], __VLS_functionalComponentArgsRest(__VLS_1), false));
var __VLS_5;
var __VLS_6 = ({ click: {} },
    { onClick: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.router.push({ name: 'merchandising.products' });
            // @ts-ignore
            [router,];
        } });
var __VLS_3;
var __VLS_4;
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
var __VLS_7;
/** @ts-ignore @type {typeof __VLS_components.Button} */
button_1.default;
// @ts-ignore
var __VLS_8 = __VLS_asFunctionalComponent1(__VLS_7, new __VLS_7(__assign({ 'onClick': {} }, { label: "Edit", icon: "pi pi-pencil", severity: "warning" })));
var __VLS_9 = __VLS_8.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Edit", icon: "pi pi-pencil", severity: "warning" })], __VLS_functionalComponentArgsRest(__VLS_8), false));
var __VLS_12;
var __VLS_13 = ({ click: {} },
    { onClick: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.router.push({ name: 'merchandising.products.edit', params: { id: __VLS_ctx.productId } });
            // @ts-ignore
            [router, productId,];
        } });
var __VLS_10;
var __VLS_11;
var __VLS_14;
/** @ts-ignore @type {typeof __VLS_components.Button} */
button_1.default;
// @ts-ignore
var __VLS_15 = __VLS_asFunctionalComponent1(__VLS_14, new __VLS_14(__assign({ 'onClick': {} }, { label: "Delete", icon: "pi pi-trash", severity: "danger", outlined: true })));
var __VLS_16 = __VLS_15.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Delete", icon: "pi pi-trash", severity: "danger", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_15), false));
var __VLS_19;
var __VLS_20 = ({ click: {} },
    { onClick: (__VLS_ctx.confirmDelete) });
var __VLS_17;
var __VLS_18;
if (__VLS_ctx.loading) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 lg:grid-cols-3 gap-6" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['lg:grid-cols-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-6']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "lg:col-span-2 space-y-6" }));
    /** @type {__VLS_StyleScopedClasses['lg:col-span-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
    var __VLS_21 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Skeleton} */
    skeleton_1.default;
    // @ts-ignore
    var __VLS_22 = __VLS_asFunctionalComponent1(__VLS_21, new __VLS_21(__assign({ height: "400px" }, { class: "rounded-lg" })));
    var __VLS_23 = __VLS_22.apply(void 0, __spreadArray([__assign({ height: "400px" }, { class: "rounded-lg" })], __VLS_functionalComponentArgsRest(__VLS_22), false));
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    var __VLS_26 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Skeleton} */
    skeleton_1.default;
    // @ts-ignore
    var __VLS_27 = __VLS_asFunctionalComponent1(__VLS_26, new __VLS_26(__assign({ height: "300px" }, { class: "rounded-lg" })));
    var __VLS_28 = __VLS_27.apply(void 0, __spreadArray([__assign({ height: "300px" }, { class: "rounded-lg" })], __VLS_functionalComponentArgsRest(__VLS_27), false));
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "lg:col-span-1" }));
    /** @type {__VLS_StyleScopedClasses['lg:col-span-1']} */ ;
    var __VLS_31 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Skeleton} */
    skeleton_1.default;
    // @ts-ignore
    var __VLS_32 = __VLS_asFunctionalComponent1(__VLS_31, new __VLS_31(__assign({ height: "600px" }, { class: "rounded-lg" })));
    var __VLS_33 = __VLS_32.apply(void 0, __spreadArray([__assign({ height: "600px" }, { class: "rounded-lg" })], __VLS_functionalComponentArgsRest(__VLS_32), false));
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
}
else if (__VLS_ctx.product) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 lg:grid-cols-3 gap-6" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['lg:grid-cols-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-6']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "lg:col-span-2 space-y-6" }));
    /** @type {__VLS_StyleScopedClasses['lg:col-span-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
    var __VLS_36 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    card_1.default;
    // @ts-ignore
    var __VLS_37 = __VLS_asFunctionalComponent1(__VLS_36, new __VLS_36({}));
    var __VLS_38 = __VLS_37.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_37), false));
    var __VLS_41 = __VLS_39.slots.default;
    {
        var __VLS_42 = __VLS_39.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
        /** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['sm:flex-row']} */ ;
        /** @type {__VLS_StyleScopedClasses['sm:items-start']} */ ;
        /** @type {__VLS_StyleScopedClasses['sm:justify-between']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.h1, __VLS_intrinsics.h1)(__assign({ class: "text-3xl font-bold text-gray-900" }));
        /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
        (__VLS_ctx.product.product_name);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-wrap items-center gap-2 mt-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-wrap']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
        var __VLS_43 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        tag_1.default;
        // @ts-ignore
        var __VLS_44 = __VLS_asFunctionalComponent1(__VLS_43, new __VLS_43(__assign({ value: (__VLS_ctx.product.sku), severity: "secondary" }, { class: "font-mono" })));
        var __VLS_45 = __VLS_44.apply(void 0, __spreadArray([__assign({ value: (__VLS_ctx.product.sku), severity: "secondary" }, { class: "font-mono" })], __VLS_functionalComponentArgsRest(__VLS_44), false));
        /** @type {__VLS_StyleScopedClasses['font-mono']} */ ;
        var __VLS_48 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        tag_1.default;
        // @ts-ignore
        var __VLS_49 = __VLS_asFunctionalComponent1(__VLS_48, new __VLS_48({
            value: (__VLS_ctx.product.is_active ? 'Active' : 'Inactive'),
            severity: (__VLS_ctx.product.is_active ? 'success' : 'secondary'),
        }));
        var __VLS_50 = __VLS_49.apply(void 0, __spreadArray([{
                value: (__VLS_ctx.product.is_active ? 'Active' : 'Inactive'),
                severity: (__VLS_ctx.product.is_active ? 'success' : 'secondary'),
            }], __VLS_functionalComponentArgsRest(__VLS_49), false));
        if (__VLS_ctx.product.is_featured) {
            var __VLS_53 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Tag} */
            tag_1.default;
            // @ts-ignore
            var __VLS_54 = __VLS_asFunctionalComponent1(__VLS_53, new __VLS_53({
                value: "Featured",
                severity: "warning",
            }));
            var __VLS_55 = __VLS_54.apply(void 0, __spreadArray([{
                    value: "Featured",
                    severity: "warning",
                }], __VLS_functionalComponentArgsRest(__VLS_54), false));
        }
        if (__VLS_ctx.product.is_new_arrival) {
            var __VLS_58 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Tag} */
            tag_1.default;
            // @ts-ignore
            var __VLS_59 = __VLS_asFunctionalComponent1(__VLS_58, new __VLS_58({
                value: "New Arrival",
                severity: "info",
            }));
            var __VLS_60 = __VLS_59.apply(void 0, __spreadArray([{
                    value: "New Arrival",
                    severity: "info",
                }], __VLS_functionalComponentArgsRest(__VLS_59), false));
        }
        if (__VLS_ctx.product.is_bestseller) {
            var __VLS_63 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Tag} */
            tag_1.default;
            // @ts-ignore
            var __VLS_64 = __VLS_asFunctionalComponent1(__VLS_63, new __VLS_63({
                value: "Bestseller",
                icon: "pi pi-star-fill",
                severity: "success",
            }));
            var __VLS_65 = __VLS_64.apply(void 0, __spreadArray([{
                    value: "Bestseller",
                    icon: "pi pi-star-fill",
                    severity: "success",
                }], __VLS_functionalComponentArgsRest(__VLS_64), false));
        }
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-right" }));
        /** @type {__VLS_StyleScopedClasses['text-right']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-3xl font-bold text-green-600" }));
        /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-green-600']} */ ;
        (__VLS_ctx.formatPrice(__VLS_ctx.product.base_price));
        if (__VLS_ctx.product.discounted_price) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-lg text-gray-500 line-through" }));
            /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
            /** @type {__VLS_StyleScopedClasses['line-through']} */ ;
            (__VLS_ctx.formatPrice(__VLS_ctx.product.discounted_price));
        }
        if (__VLS_ctx.product.tax_rate) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600 mt-1" }));
            /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
            /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
            (__VLS_ctx.product.tax_rate);
        }
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 md:grid-cols-4 gap-4 p-4 bg-gray-50 rounded-lg" }));
        /** @type {__VLS_StyleScopedClasses['grid']} */ ;
        /** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
        /** @type {__VLS_StyleScopedClasses['md:grid-cols-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mb-1" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm font-semibold text-gray-900" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
        (((_a = __VLS_ctx.product.category) === null || _a === void 0 ? void 0 : _a.category_name) || 'N/A');
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mb-1" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
        var __VLS_68 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        tag_1.default;
        // @ts-ignore
        var __VLS_69 = __VLS_asFunctionalComponent1(__VLS_68, new __VLS_68({
            value: (__VLS_ctx.product.stock_status),
            severity: (__VLS_ctx.getStockSeverity(__VLS_ctx.product.stock_status)),
        }));
        var __VLS_70 = __VLS_69.apply(void 0, __spreadArray([{
                value: (__VLS_ctx.product.stock_status),
                severity: (__VLS_ctx.getStockSeverity(__VLS_ctx.product.stock_status)),
            }], __VLS_functionalComponentArgsRest(__VLS_69), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mb-1" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm font-semibold text-gray-900" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
        (__VLS_ctx.product.brand || 'N/A');
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mb-1" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm font-semibold text-gray-900" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
        (__VLS_ctx.product.variations_count || 0);
        if (__VLS_ctx.product.description) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
            __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-sm font-semibold text-gray-700 mb-2" }));
            /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
            /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
            /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-700 leading-relaxed" }));
            /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
            /** @type {__VLS_StyleScopedClasses['leading-relaxed']} */ ;
            (__VLS_ctx.product.description);
        }
        // @ts-ignore
        [confirmDelete, loading, product, product, product, product, product, product, product, product, product, product, product, product, product, product, product, product, product, product, product, product, formatPrice, formatPrice, getStockSeverity,];
    }
    // @ts-ignore
    [];
    var __VLS_39;
    var __VLS_73 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    card_1.default;
    // @ts-ignore
    var __VLS_74 = __VLS_asFunctionalComponent1(__VLS_73, new __VLS_73({}));
    var __VLS_75 = __VLS_74.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_74), false));
    var __VLS_78 = __VLS_76.slots.default;
    {
        var __VLS_79 = __VLS_76.slots.title;
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
        var __VLS_80 = __VLS_76.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 md:grid-cols-4 gap-6" }));
        /** @type {__VLS_StyleScopedClasses['grid']} */ ;
        /** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
        /** @type {__VLS_StyleScopedClasses['md:grid-cols-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-6']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mb-1" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-lg font-semibold text-gray-900" }));
        /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
        (__VLS_ctx.product.length_cm ? "".concat(__VLS_ctx.product.length_cm, " cm") : 'N/A');
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mb-1" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-lg font-semibold text-gray-900" }));
        /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
        (__VLS_ctx.product.width_cm ? "".concat(__VLS_ctx.product.width_cm, " cm") : 'N/A');
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mb-1" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-lg font-semibold text-gray-900" }));
        /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
        (__VLS_ctx.product.height_cm ? "".concat(__VLS_ctx.product.height_cm, " cm") : 'N/A');
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mb-1" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-lg font-semibold text-gray-900" }));
        /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
        (__VLS_ctx.product.weight_kg ? "".concat(__VLS_ctx.product.weight_kg, " kg") : 'N/A');
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "mt-6 flex flex-wrap gap-3" }));
        /** @type {__VLS_StyleScopedClasses['mt-6']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-wrap']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
        if (__VLS_ctx.product.assembly_required) {
            var __VLS_81 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Tag} */
            tag_1.default;
            // @ts-ignore
            var __VLS_82 = __VLS_asFunctionalComponent1(__VLS_81, new __VLS_81({
                value: "Assembly Required",
                severity: "info",
                icon: "pi pi-wrench",
            }));
            var __VLS_83 = __VLS_82.apply(void 0, __spreadArray([{
                    value: "Assembly Required",
                    severity: "info",
                    icon: "pi pi-wrench",
                }], __VLS_functionalComponentArgsRest(__VLS_82), false));
        }
        else {
            var __VLS_86 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Tag} */
            tag_1.default;
            // @ts-ignore
            var __VLS_87 = __VLS_asFunctionalComponent1(__VLS_86, new __VLS_86({
                value: "No Assembly",
                severity: "success",
                icon: "pi pi-check",
            }));
            var __VLS_88 = __VLS_87.apply(void 0, __spreadArray([{
                    value: "No Assembly",
                    severity: "success",
                    icon: "pi pi-check",
                }], __VLS_functionalComponentArgsRest(__VLS_87), false));
        }
        if (__VLS_ctx.product.collection_name) {
            var __VLS_91 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Tag} */
            tag_1.default;
            // @ts-ignore
            var __VLS_92 = __VLS_asFunctionalComponent1(__VLS_91, new __VLS_91({
                value: ("Collection: ".concat(__VLS_ctx.product.collection_name)),
            }));
            var __VLS_93 = __VLS_92.apply(void 0, __spreadArray([{
                    value: ("Collection: ".concat(__VLS_ctx.product.collection_name)),
                }], __VLS_functionalComponentArgsRest(__VLS_92), false));
        }
        // @ts-ignore
        [product, product, product, product, product, product, product, product, product, product, product,];
    }
    // @ts-ignore
    [];
    var __VLS_76;
    if (__VLS_ctx.variations && __VLS_ctx.variations.length > 0) {
        var __VLS_96 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
        card_1.default;
        // @ts-ignore
        var __VLS_97 = __VLS_asFunctionalComponent1(__VLS_96, new __VLS_96({}));
        var __VLS_98 = __VLS_97.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_97), false));
        var __VLS_101 = __VLS_99.slots.default;
        {
            var __VLS_102 = __VLS_99.slots.title;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-th-large text-indigo-600" }));
            /** @type {__VLS_StyleScopedClasses['pi']} */ ;
            /** @type {__VLS_StyleScopedClasses['pi-th-large']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-indigo-600']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
            var __VLS_103 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            button_1.default;
            // @ts-ignore
            var __VLS_104 = __VLS_asFunctionalComponent1(__VLS_103, new __VLS_103(__assign({ 'onClick': {} }, { label: "Manage Variations", icon: "pi pi-cog", size: "small", text: true })));
            var __VLS_105 = __VLS_104.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Manage Variations", icon: "pi pi-cog", size: "small", text: true })], __VLS_functionalComponentArgsRest(__VLS_104), false));
            var __VLS_108 = void 0;
            var __VLS_109 = ({ click: {} },
                { onClick: (__VLS_ctx.manageVariations) });
            var __VLS_106;
            var __VLS_107;
            // @ts-ignore
            [variations, variations, manageVariations,];
        }
        {
            var __VLS_110 = __VLS_99.slots.content;
            var __VLS_111 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
            datatable_1.default;
            // @ts-ignore
            var __VLS_112 = __VLS_asFunctionalComponent1(__VLS_111, new __VLS_111(__assign({ value: (__VLS_ctx.variations) }, { class: "p-datatable-sm" })));
            var __VLS_113 = __VLS_112.apply(void 0, __spreadArray([__assign({ value: (__VLS_ctx.variations) }, { class: "p-datatable-sm" })], __VLS_functionalComponentArgsRest(__VLS_112), false));
            /** @type {__VLS_StyleScopedClasses['p-datatable-sm']} */ ;
            var __VLS_116 = __VLS_114.slots.default;
            var __VLS_117 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
            column_1.default;
            // @ts-ignore
            var __VLS_118 = __VLS_asFunctionalComponent1(__VLS_117, new __VLS_117({
                field: "variation_name",
                header: "Variation",
            }));
            var __VLS_119 = __VLS_118.apply(void 0, __spreadArray([{
                    field: "variation_name",
                    header: "Variation",
                }], __VLS_functionalComponentArgsRest(__VLS_118), false));
            var __VLS_122 = __VLS_120.slots.default;
            {
                var __VLS_123 = __VLS_120.slots.body;
                var data = __VLS_vSlot(__VLS_123)[0].data;
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
                /** @type {__VLS_StyleScopedClasses['flex']} */ ;
                /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
                /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
                if (data.color_hex) {
                    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ style: ({ backgroundColor: data.color_hex }) }, { class: "w-6 h-6 rounded border border-gray-300" }));
                    /** @type {__VLS_StyleScopedClasses['w-6']} */ ;
                    /** @type {__VLS_StyleScopedClasses['h-6']} */ ;
                    /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
                    /** @type {__VLS_StyleScopedClasses['border']} */ ;
                    /** @type {__VLS_StyleScopedClasses['border-gray-300']} */ ;
                }
                __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-medium" }));
                /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
                (data.variation_name);
                // @ts-ignore
                [variations,];
            }
            // @ts-ignore
            [];
            var __VLS_120;
            var __VLS_124 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
            column_1.default;
            // @ts-ignore
            var __VLS_125 = __VLS_asFunctionalComponent1(__VLS_124, new __VLS_124({
                field: "variation_sku",
                header: "SKU",
            }));
            var __VLS_126 = __VLS_125.apply(void 0, __spreadArray([{
                    field: "variation_sku",
                    header: "SKU",
                }], __VLS_functionalComponentArgsRest(__VLS_125), false));
            var __VLS_129 = __VLS_127.slots.default;
            {
                var __VLS_130 = __VLS_127.slots.body;
                var data = __VLS_vSlot(__VLS_130)[0].data;
                __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-mono text-sm" }));
                /** @type {__VLS_StyleScopedClasses['font-mono']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
                (data.variation_sku);
                // @ts-ignore
                [];
            }
            // @ts-ignore
            [];
            var __VLS_127;
            var __VLS_131 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
            column_1.default;
            // @ts-ignore
            var __VLS_132 = __VLS_asFunctionalComponent1(__VLS_131, new __VLS_131({
                header: "Attributes",
            }));
            var __VLS_133 = __VLS_132.apply(void 0, __spreadArray([{
                    header: "Attributes",
                }], __VLS_functionalComponentArgsRest(__VLS_132), false));
            var __VLS_136 = __VLS_134.slots.default;
            {
                var __VLS_137 = __VLS_134.slots.body;
                var data = __VLS_vSlot(__VLS_137)[0].data;
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-wrap gap-1" }));
                /** @type {__VLS_StyleScopedClasses['flex']} */ ;
                /** @type {__VLS_StyleScopedClasses['flex-wrap']} */ ;
                /** @type {__VLS_StyleScopedClasses['gap-1']} */ ;
                if (data.color) {
                    var __VLS_138 = void 0;
                    /** @ts-ignore @type {typeof __VLS_components.Tag} */
                    tag_1.default;
                    // @ts-ignore
                    var __VLS_139 = __VLS_asFunctionalComponent1(__VLS_138, new __VLS_138({
                        value: (data.color),
                        severity: "info",
                        size: "small",
                    }));
                    var __VLS_140 = __VLS_139.apply(void 0, __spreadArray([{
                            value: (data.color),
                            severity: "info",
                            size: "small",
                        }], __VLS_functionalComponentArgsRest(__VLS_139), false));
                }
                if (data.size) {
                    var __VLS_143 = void 0;
                    /** @ts-ignore @type {typeof __VLS_components.Tag} */
                    tag_1.default;
                    // @ts-ignore
                    var __VLS_144 = __VLS_asFunctionalComponent1(__VLS_143, new __VLS_143({
                        value: (data.size),
                        severity: "secondary",
                        size: "small",
                    }));
                    var __VLS_145 = __VLS_144.apply(void 0, __spreadArray([{
                            value: (data.size),
                            severity: "secondary",
                            size: "small",
                        }], __VLS_functionalComponentArgsRest(__VLS_144), false));
                }
                if (data.material) {
                    var __VLS_148 = void 0;
                    /** @ts-ignore @type {typeof __VLS_components.Tag} */
                    tag_1.default;
                    // @ts-ignore
                    var __VLS_149 = __VLS_asFunctionalComponent1(__VLS_148, new __VLS_148({
                        value: (data.material),
                        severity: "success",
                        size: "small",
                    }));
                    var __VLS_150 = __VLS_149.apply(void 0, __spreadArray([{
                            value: (data.material),
                            severity: "success",
                            size: "small",
                        }], __VLS_functionalComponentArgsRest(__VLS_149), false));
                }
                // @ts-ignore
                [];
            }
            // @ts-ignore
            [];
            var __VLS_134;
            var __VLS_153 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
            column_1.default;
            // @ts-ignore
            var __VLS_154 = __VLS_asFunctionalComponent1(__VLS_153, new __VLS_153({
                field: "final_price",
                header: "Price",
            }));
            var __VLS_155 = __VLS_154.apply(void 0, __spreadArray([{
                    field: "final_price",
                    header: "Price",
                }], __VLS_functionalComponentArgsRest(__VLS_154), false));
            var __VLS_158 = __VLS_156.slots.default;
            {
                var __VLS_159 = __VLS_156.slots.body;
                var data = __VLS_vSlot(__VLS_159)[0].data;
                __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-semibold" }));
                /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
                (__VLS_ctx.formatPrice(data.final_price || 0));
                if (data.price_adjustment !== 0) {
                    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-xs text-gray-600 ml-2" }));
                    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
                    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
                    /** @type {__VLS_StyleScopedClasses['ml-2']} */ ;
                    (data.price_adjustment > 0 ? '+' : '');
                    (__VLS_ctx.formatPrice(data.price_adjustment));
                }
                // @ts-ignore
                [formatPrice, formatPrice,];
            }
            // @ts-ignore
            [];
            var __VLS_156;
            var __VLS_160 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
            column_1.default;
            // @ts-ignore
            var __VLS_161 = __VLS_asFunctionalComponent1(__VLS_160, new __VLS_160({
                field: "stock_quantity",
                header: "Stock",
            }));
            var __VLS_162 = __VLS_161.apply(void 0, __spreadArray([{
                    field: "stock_quantity",
                    header: "Stock",
                }], __VLS_functionalComponentArgsRest(__VLS_161), false));
            var __VLS_165 = __VLS_163.slots.default;
            {
                var __VLS_166 = __VLS_163.slots.body;
                var data = __VLS_vSlot(__VLS_166)[0].data;
                var __VLS_167 = void 0;
                /** @ts-ignore @type {typeof __VLS_components.Badge} */
                badge_1.default;
                // @ts-ignore
                var __VLS_168 = __VLS_asFunctionalComponent1(__VLS_167, new __VLS_167({
                    value: (data.stock_quantity),
                    severity: (data.stock_quantity > 10 ? 'success' : 'warning'),
                }));
                var __VLS_169 = __VLS_168.apply(void 0, __spreadArray([{
                        value: (data.stock_quantity),
                        severity: (data.stock_quantity > 10 ? 'success' : 'warning'),
                    }], __VLS_functionalComponentArgsRest(__VLS_168), false));
                // @ts-ignore
                [];
            }
            // @ts-ignore
            [];
            var __VLS_163;
            var __VLS_172 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
            column_1.default;
            // @ts-ignore
            var __VLS_173 = __VLS_asFunctionalComponent1(__VLS_172, new __VLS_172({
                field: "is_active",
                header: "Status",
            }));
            var __VLS_174 = __VLS_173.apply(void 0, __spreadArray([{
                    field: "is_active",
                    header: "Status",
                }], __VLS_functionalComponentArgsRest(__VLS_173), false));
            var __VLS_177 = __VLS_175.slots.default;
            {
                var __VLS_178 = __VLS_175.slots.body;
                var data = __VLS_vSlot(__VLS_178)[0].data;
                var __VLS_179 = void 0;
                /** @ts-ignore @type {typeof __VLS_components.Tag} */
                tag_1.default;
                // @ts-ignore
                var __VLS_180 = __VLS_asFunctionalComponent1(__VLS_179, new __VLS_179({
                    value: (data.is_active ? 'Active' : 'Inactive'),
                    severity: (data.is_active ? 'success' : 'secondary'),
                }));
                var __VLS_181 = __VLS_180.apply(void 0, __spreadArray([{
                        value: (data.is_active ? 'Active' : 'Inactive'),
                        severity: (data.is_active ? 'success' : 'secondary'),
                    }], __VLS_functionalComponentArgsRest(__VLS_180), false));
                // @ts-ignore
                [];
            }
            // @ts-ignore
            [];
            var __VLS_175;
            // @ts-ignore
            [];
            var __VLS_114;
            // @ts-ignore
            [];
        }
        // @ts-ignore
        [];
        var __VLS_99;
    }
    var __VLS_184 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    card_1.default;
    // @ts-ignore
    var __VLS_185 = __VLS_asFunctionalComponent1(__VLS_184, new __VLS_184({}));
    var __VLS_186 = __VLS_185.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_185), false));
    var __VLS_189 = __VLS_187.slots.default;
    {
        var __VLS_190 = __VLS_187.slots.title;
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
        [];
    }
    {
        var __VLS_191 = __VLS_187.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-2 gap-6" }));
        /** @type {__VLS_StyleScopedClasses['grid']} */ ;
        /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
        /** @type {__VLS_StyleScopedClasses['md:grid-cols-2']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-6']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.h4, __VLS_intrinsics.h4)(__assign({ class: "text-sm font-semibold text-gray-700 mb-3" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-2" }));
        /** @type {__VLS_StyleScopedClasses['space-y-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-900" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
        (__VLS_ctx.product.meta_title || 'Not set');
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-900" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
        (__VLS_ctx.product.meta_description || 'Not set');
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-900" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
        (__VLS_ctx.product.meta_keywords || 'Not set');
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.h4, __VLS_intrinsics.h4)(__assign({ class: "text-sm font-semibold text-gray-700 mb-3" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-2" }));
        /** @type {__VLS_StyleScopedClasses['space-y-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-900" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
        (__VLS_ctx.formatDate(__VLS_ctx.product.created_at));
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-900" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
        (__VLS_ctx.formatDate(__VLS_ctx.product.updated_at));
        if (__VLS_ctx.product.published_at) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600" }));
            /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-900" }));
            /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
            (__VLS_ctx.formatDate(__VLS_ctx.product.published_at));
        }
        // @ts-ignore
        [product, product, product, product, product, product, product, formatDate, formatDate, formatDate,];
    }
    // @ts-ignore
    [];
    var __VLS_187;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "lg:col-span-1" }));
    /** @type {__VLS_StyleScopedClasses['lg:col-span-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "sticky top-6 space-y-6" }));
    /** @type {__VLS_StyleScopedClasses['sticky']} */ ;
    /** @type {__VLS_StyleScopedClasses['top-6']} */ ;
    /** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
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
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
        /** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
        if (__VLS_ctx.primary3DModel) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign(__assign({ ref: "viewer3DContainer" }, { class: "relative bg-gradient-to-br from-gray-100 to-gray-200 rounded-lg overflow-hidden" }), { style: {} }));
            /** @type {__VLS_StyleScopedClasses['relative']} */ ;
            /** @type {__VLS_StyleScopedClasses['bg-gradient-to-br']} */ ;
            /** @type {__VLS_StyleScopedClasses['from-gray-100']} */ ;
            /** @type {__VLS_StyleScopedClasses['to-gray-200']} */ ;
            /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
            /** @type {__VLS_StyleScopedClasses['overflow-hidden']} */ ;
            if (__VLS_ctx.loading3D) {
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "absolute inset-0 flex items-center justify-center bg-white/90 z-10" }));
                /** @type {__VLS_StyleScopedClasses['absolute']} */ ;
                /** @type {__VLS_StyleScopedClasses['inset-0']} */ ;
                /** @type {__VLS_StyleScopedClasses['flex']} */ ;
                /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
                /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
                /** @type {__VLS_StyleScopedClasses['bg-white/90']} */ ;
                /** @type {__VLS_StyleScopedClasses['z-10']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center" }));
                /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
                var __VLS_200 = void 0;
                /** @ts-ignore @type {typeof __VLS_components.ProgressSpinner} */
                progressspinner_1.default;
                // @ts-ignore
                var __VLS_201 = __VLS_asFunctionalComponent1(__VLS_200, new __VLS_200(__assign({ style: {} }, { strokeWidth: "4" })));
                var __VLS_202 = __VLS_201.apply(void 0, __spreadArray([__assign({ style: {} }, { strokeWidth: "4" })], __VLS_functionalComponentArgsRest(__VLS_201), false));
                __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600 mt-2" }));
                /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
                /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
            }
            if (__VLS_ctx.model3DError) {
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "absolute inset-0 flex items-center justify-center bg-red-50 z-10" }));
                /** @type {__VLS_StyleScopedClasses['absolute']} */ ;
                /** @type {__VLS_StyleScopedClasses['inset-0']} */ ;
                /** @type {__VLS_StyleScopedClasses['flex']} */ ;
                /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
                /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
                /** @type {__VLS_StyleScopedClasses['bg-red-50']} */ ;
                /** @type {__VLS_StyleScopedClasses['z-10']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center p-4" }));
                /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
                /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-exclamation-triangle text-4xl text-red-500 mb-2" }));
                /** @type {__VLS_StyleScopedClasses['pi']} */ ;
                /** @type {__VLS_StyleScopedClasses['pi-exclamation-triangle']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-4xl']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
                /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-red-700" }));
                /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-red-700']} */ ;
                var __VLS_205 = void 0;
                /** @ts-ignore @type {typeof __VLS_components.Button} */
                button_1.default;
                // @ts-ignore
                var __VLS_206 = __VLS_asFunctionalComponent1(__VLS_205, new __VLS_205(__assign(__assign({ 'onClick': {} }, { label: "Retry", size: "small" }), { class: "mt-2" })));
                var __VLS_207 = __VLS_206.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { label: "Retry", size: "small" }), { class: "mt-2" })], __VLS_functionalComponentArgsRest(__VLS_206), false));
                var __VLS_210 = void 0;
                var __VLS_211 = ({ click: {} },
                    { onClick: (__VLS_ctx.retryLoad3D) });
                /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
                var __VLS_208;
                var __VLS_209;
            }
            if (!__VLS_ctx.loading3D && !__VLS_ctx.model3DError) {
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "absolute bottom-4 left-4 right-4 bg-white/95 backdrop-blur rounded-lg p-3 shadow-lg z-20" }));
                /** @type {__VLS_StyleScopedClasses['absolute']} */ ;
                /** @type {__VLS_StyleScopedClasses['bottom-4']} */ ;
                /** @type {__VLS_StyleScopedClasses['left-4']} */ ;
                /** @type {__VLS_StyleScopedClasses['right-4']} */ ;
                /** @type {__VLS_StyleScopedClasses['bg-white/95']} */ ;
                /** @type {__VLS_StyleScopedClasses['backdrop-blur']} */ ;
                /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
                /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
                /** @type {__VLS_StyleScopedClasses['shadow-lg']} */ ;
                /** @type {__VLS_StyleScopedClasses['z-20']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between gap-2" }));
                /** @type {__VLS_StyleScopedClasses['flex']} */ ;
                /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
                /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
                /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
                var __VLS_212 = void 0;
                /** @ts-ignore @type {typeof __VLS_components.Button} */
                button_1.default;
                // @ts-ignore
                var __VLS_213 = __VLS_asFunctionalComponent1(__VLS_212, new __VLS_212(__assign({ 'onClick': {} }, { icon: "pi pi-replay", text: true, rounded: true, size: "small" })));
                var __VLS_214 = __VLS_213.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-replay", text: true, rounded: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_213), false));
                var __VLS_217 = void 0;
                var __VLS_218 = ({ click: {} },
                    { onClick: (__VLS_ctx.reset3DView) });
                __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('Reset View') }), null, null);
                var __VLS_215;
                var __VLS_216;
                var __VLS_219 = void 0;
                /** @ts-ignore @type {typeof __VLS_components.Button} */
                button_1.default;
                // @ts-ignore
                var __VLS_220 = __VLS_asFunctionalComponent1(__VLS_219, new __VLS_219(__assign({ 'onClick': {} }, { icon: "pi pi-camera", text: true, rounded: true, size: "small" })));
                var __VLS_221 = __VLS_220.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-camera", text: true, rounded: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_220), false));
                var __VLS_224 = void 0;
                var __VLS_225 = ({ click: {} },
                    { onClick: (__VLS_ctx.take3DScreenshot) });
                __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('Screenshot') }), null, null);
                var __VLS_222;
                var __VLS_223;
                var __VLS_226 = void 0;
                /** @ts-ignore @type {typeof __VLS_components.Button} */
                button_1.default;
                // @ts-ignore
                var __VLS_227 = __VLS_asFunctionalComponent1(__VLS_226, new __VLS_226(__assign(__assign({ 'onClick': {} }, { icon: "pi pi-sync", text: true, rounded: true, size: "small" }), { class: ({ 'bg-blue-100': __VLS_ctx.autoRotate }) })));
                var __VLS_228 = __VLS_227.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { icon: "pi pi-sync", text: true, rounded: true, size: "small" }), { class: ({ 'bg-blue-100': __VLS_ctx.autoRotate }) })], __VLS_functionalComponentArgsRest(__VLS_227), false));
                var __VLS_231 = void 0;
                var __VLS_232 = ({ click: {} },
                    { onClick: (__VLS_ctx.toggleAutoRotate) });
                __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('Auto Rotate') }), null, null);
                /** @type {__VLS_StyleScopedClasses['bg-blue-100']} */ ;
                var __VLS_229;
                var __VLS_230;
                var __VLS_233 = void 0;
                /** @ts-ignore @type {typeof __VLS_components.Button} */
                button_1.default;
                // @ts-ignore
                var __VLS_234 = __VLS_asFunctionalComponent1(__VLS_233, new __VLS_233(__assign({ 'onClick': {} }, { icon: "pi pi-external-link", text: true, rounded: true, size: "small" })));
                var __VLS_235 = __VLS_234.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-external-link", text: true, rounded: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_234), false));
                var __VLS_238 = void 0;
                var __VLS_239 = ({ click: {} },
                    { onClick: (__VLS_ctx.toggle3DFullscreen) });
                __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('Fullscreen') }), null, null);
                var __VLS_236;
                var __VLS_237;
            }
        }
        else {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-100 rounded-lg p-8 text-center" }, { style: {} }));
            /** @type {__VLS_StyleScopedClasses['bg-gray-100']} */ ;
            /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
            /** @type {__VLS_StyleScopedClasses['p-8']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-cube text-6xl text-gray-400 mb-4" }));
            /** @type {__VLS_StyleScopedClasses['pi']} */ ;
            /** @type {__VLS_StyleScopedClasses['pi-cube']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-6xl']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
            /** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600 font-medium" }));
            /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
            /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500 mt-2" }));
            /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
            /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
            var __VLS_240 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            button_1.default;
            // @ts-ignore
            var __VLS_241 = __VLS_asFunctionalComponent1(__VLS_240, new __VLS_240(__assign(__assign({ 'onClick': {} }, { label: "Upload 3D Model", icon: "pi pi-upload", size: "small" }), { class: "mt-4" })));
            var __VLS_242 = __VLS_241.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { label: "Upload 3D Model", icon: "pi pi-upload", size: "small" }), { class: "mt-4" })], __VLS_functionalComponentArgsRest(__VLS_241), false));
            var __VLS_245 = void 0;
            var __VLS_246 = ({ click: {} },
                { onClick: function () {
                        var _a = [];
                        for (var _i = 0; _i < arguments.length; _i++) {
                            _a[_i] = arguments[_i];
                        }
                        var $event = _a[0];
                        if (!!(__VLS_ctx.loading))
                            return;
                        if (!(__VLS_ctx.product))
                            return;
                        if (!!(__VLS_ctx.primary3DModel))
                            return;
                        __VLS_ctx.router.push({ name: 'merchandising.assets.upload' });
                        // @ts-ignore
                        [router, primary3DModel, loading3D, loading3D, model3DError, model3DError, retryLoad3D, reset3DView, vTooltip, vTooltip, vTooltip, vTooltip, take3DScreenshot, autoRotate, toggleAutoRotate, toggle3DFullscreen,];
                    } });
            /** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
            var __VLS_243;
            var __VLS_244;
        }
        if (__VLS_ctx.primary3DModel) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 rounded-lg p-3" }));
            /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
            /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
            /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between mb-2" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
            /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-xs font-semibold text-gray-700" }));
            /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
            /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
            var __VLS_247 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Tag} */
            tag_1.default;
            // @ts-ignore
            var __VLS_248 = __VLS_asFunctionalComponent1(__VLS_247, new __VLS_247({
                value: ((_b = __VLS_ctx.primary3DModel.model_format) === null || _b === void 0 ? void 0 : _b.toUpperCase()),
                severity: "info",
                size: "small",
            }));
            var __VLS_249 = __VLS_248.apply(void 0, __spreadArray([{
                    value: ((_c = __VLS_ctx.primary3DModel.model_format) === null || _c === void 0 ? void 0 : _c.toUpperCase()),
                    severity: "info",
                    size: "small",
                }], __VLS_functionalComponentArgsRest(__VLS_248), false));
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-1 text-xs text-gray-600" }));
            /** @type {__VLS_StyleScopedClasses['space-y-1']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-medium" }));
            /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
            (__VLS_ctx.formatFileSize(__VLS_ctx.primary3DModel.file_size_kb * 1024));
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-medium" }));
            /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
            (__VLS_ctx.formatDate(__VLS_ctx.primary3DModel.created_at));
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
            var __VLS_252 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Tag} */
            tag_1.default;
            // @ts-ignore
            var __VLS_253 = __VLS_asFunctionalComponent1(__VLS_252, new __VLS_252({
                value: (__VLS_ctx.primary3DModel.is_ar_compatible ? 'Yes' : 'No'),
                severity: (__VLS_ctx.primary3DModel.is_ar_compatible ? 'success' : 'secondary'),
                size: "small",
            }));
            var __VLS_254 = __VLS_253.apply(void 0, __spreadArray([{
                    value: (__VLS_ctx.primary3DModel.is_ar_compatible ? 'Yes' : 'No'),
                    severity: (__VLS_ctx.primary3DModel.is_ar_compatible ? 'success' : 'secondary'),
                    size: "small",
                }], __VLS_functionalComponentArgsRest(__VLS_253), false));
        }
        // @ts-ignore
        [formatDate, primary3DModel, primary3DModel, primary3DModel, primary3DModel, primary3DModel, primary3DModel, formatFileSize,];
    }
    // @ts-ignore
    [];
    var __VLS_195;
    var __VLS_257 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    card_1.default;
    // @ts-ignore
    var __VLS_258 = __VLS_asFunctionalComponent1(__VLS_257, new __VLS_257({}));
    var __VLS_259 = __VLS_258.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_258), false));
    var __VLS_262 = __VLS_260.slots.default;
    {
        var __VLS_263 = __VLS_260.slots.title;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-images text-pink-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-images']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-pink-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        // @ts-ignore
        [];
    }
    {
        var __VLS_264 = __VLS_260.slots.content;
        if (__VLS_ctx.productImages && __VLS_ctx.productImages.length > 0) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
            /** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "relative rounded-lg overflow-hidden bg-gray-100" }, { style: {} }));
            /** @type {__VLS_StyleScopedClasses['relative']} */ ;
            /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
            /** @type {__VLS_StyleScopedClasses['overflow-hidden']} */ ;
            /** @type {__VLS_StyleScopedClasses['bg-gray-100']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.img)(__assign(__assign({ onError: (__VLS_ctx.handleImageError) }, { src: (__VLS_ctx.selectedImage || ((_d = __VLS_ctx.productImages[0]) === null || _d === void 0 ? void 0 : _d.url)), alt: (__VLS_ctx.product.product_name) }), { class: "w-full h-full object-cover" }));
            /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
            /** @type {__VLS_StyleScopedClasses['h-full']} */ ;
            /** @type {__VLS_StyleScopedClasses['object-cover']} */ ;
            var __VLS_265 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            button_1.default;
            // @ts-ignore
            var __VLS_266 = __VLS_asFunctionalComponent1(__VLS_265, new __VLS_265(__assign(__assign(__assign({ 'onClick': {} }, { icon: "pi pi-search-plus" }), { class: "absolute top-2 right-2" }), { rounded: true })));
            var __VLS_267 = __VLS_266.apply(void 0, __spreadArray([__assign(__assign(__assign({ 'onClick': {} }, { icon: "pi pi-search-plus" }), { class: "absolute top-2 right-2" }), { rounded: true })], __VLS_functionalComponentArgsRest(__VLS_266), false));
            var __VLS_270 = void 0;
            var __VLS_271 = ({ click: {} },
                { onClick: (__VLS_ctx.openImageGallery) });
            /** @type {__VLS_StyleScopedClasses['absolute']} */ ;
            /** @type {__VLS_StyleScopedClasses['top-2']} */ ;
            /** @type {__VLS_StyleScopedClasses['right-2']} */ ;
            var __VLS_268;
            var __VLS_269;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-4 gap-2" }));
            /** @type {__VLS_StyleScopedClasses['grid']} */ ;
            /** @type {__VLS_StyleScopedClasses['grid-cols-4']} */ ;
            /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
            var _loop_1 = function (image, index) {
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign(__assign(__assign({ onClick: function () {
                        var _a = [];
                        for (var _i = 0; _i < arguments.length; _i++) {
                            _a[_i] = arguments[_i];
                        }
                        var $event = _a[0];
                        if (!!(__VLS_ctx.loading))
                            return;
                        if (!(__VLS_ctx.product))
                            return;
                        if (!(__VLS_ctx.productImages && __VLS_ctx.productImages.length > 0))
                            return;
                        __VLS_ctx.selectedImage = image.url;
                        // @ts-ignore
                        [product, productImages, productImages, productImages, productImages, handleImageError, selectedImage, selectedImage, openImageGallery,];
                    } }, { key: (index) }), { class: "relative rounded-lg overflow-hidden cursor-pointer border-2 transition-all" }), { class: (__VLS_ctx.selectedImage === image.url ? 'border-blue-500' : 'border-transparent hover:border-gray-300') }));
                /** @type {__VLS_StyleScopedClasses['relative']} */ ;
                /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
                /** @type {__VLS_StyleScopedClasses['overflow-hidden']} */ ;
                /** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
                /** @type {__VLS_StyleScopedClasses['border-2']} */ ;
                /** @type {__VLS_StyleScopedClasses['transition-all']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.img)(__assign(__assign({ onError: (__VLS_ctx.handleImageError) }, { src: (image.thumbnail_url || image.url), alt: ("Thumbnail ".concat(index + 1)) }), { class: "w-full h-20 object-cover" }));
                /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
                /** @type {__VLS_StyleScopedClasses['h-20']} */ ;
                /** @type {__VLS_StyleScopedClasses['object-cover']} */ ;
                if (image.is_primary) {
                    var __VLS_272 = void 0;
                    /** @ts-ignore @type {typeof __VLS_components.Badge} */
                    badge_1.default;
                    // @ts-ignore
                    var __VLS_273 = __VLS_asFunctionalComponent1(__VLS_272, new __VLS_272(__assign({ value: "Main", severity: "success" }, { class: "absolute top-1 left-1 text-xs" })));
                    var __VLS_274 = __VLS_273.apply(void 0, __spreadArray([__assign({ value: "Main", severity: "success" }, { class: "absolute top-1 left-1 text-xs" })], __VLS_functionalComponentArgsRest(__VLS_273), false));
                    /** @type {__VLS_StyleScopedClasses['absolute']} */ ;
                    /** @type {__VLS_StyleScopedClasses['top-1']} */ ;
                    /** @type {__VLS_StyleScopedClasses['left-1']} */ ;
                    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
                }
                // @ts-ignore
                [handleImageError, selectedImage,];
            };
            for (var _i = 0, _e = __VLS_vFor((__VLS_ctx.productImages.slice(0, 4))); _i < _e.length; _i++) {
                var _f = _e[_i], image = _f[0], index = _f[1];
                _loop_1(image, index);
            }
        }
        else {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center py-8" }));
            /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['py-8']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-image text-4xl text-gray-400 mb-3 block" }));
            /** @type {__VLS_StyleScopedClasses['pi']} */ ;
            /** @type {__VLS_StyleScopedClasses['pi-image']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-4xl']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
            /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
            /** @type {__VLS_StyleScopedClasses['block']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600" }));
            /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
            var __VLS_277 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            button_1.default;
            // @ts-ignore
            var __VLS_278 = __VLS_asFunctionalComponent1(__VLS_277, new __VLS_277(__assign(__assign({ 'onClick': {} }, { label: "Upload Images", icon: "pi pi-upload", size: "small" }), { class: "mt-3" })));
            var __VLS_279 = __VLS_278.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { label: "Upload Images", icon: "pi pi-upload", size: "small" }), { class: "mt-3" })], __VLS_functionalComponentArgsRest(__VLS_278), false));
            var __VLS_282 = void 0;
            var __VLS_283 = ({ click: {} },
                { onClick: function () {
                        var _a = [];
                        for (var _i = 0; _i < arguments.length; _i++) {
                            _a[_i] = arguments[_i];
                        }
                        var $event = _a[0];
                        if (!!(__VLS_ctx.loading))
                            return;
                        if (!(__VLS_ctx.product))
                            return;
                        if (!!(__VLS_ctx.productImages && __VLS_ctx.productImages.length > 0))
                            return;
                        __VLS_ctx.router.push({ name: 'merchandising.assets.upload' });
                        // @ts-ignore
                        [router,];
                    } });
            /** @type {__VLS_StyleScopedClasses['mt-3']} */ ;
            var __VLS_280;
            var __VLS_281;
        }
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_260;
    var __VLS_284 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    card_1.default;
    // @ts-ignore
    var __VLS_285 = __VLS_asFunctionalComponent1(__VLS_284, new __VLS_284({}));
    var __VLS_286 = __VLS_285.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_285), false));
    var __VLS_289 = __VLS_287.slots.default;
    {
        var __VLS_290 = __VLS_287.slots.title;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-folder text-yellow-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-folder']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-yellow-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        (__VLS_ctx.allAssets.length);
        // @ts-ignore
        [allAssets,];
    }
    {
        var __VLS_291 = __VLS_287.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-2 max-h-96 overflow-y-auto" }));
        /** @type {__VLS_StyleScopedClasses['space-y-2']} */ ;
        /** @type {__VLS_StyleScopedClasses['max-h-96']} */ ;
        /** @type {__VLS_StyleScopedClasses['overflow-y-auto']} */ ;
        if (!__VLS_ctx.allAssets || __VLS_ctx.allAssets.length === 0) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center py-4 text-gray-500 text-sm" }));
            /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['py-4']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        }
        else {
            var _loop_2 = function (asset) {
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (asset.id) }, { class: "flex items-center justify-between p-2 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors" }));
                /** @type {__VLS_StyleScopedClasses['flex']} */ ;
                /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
                /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
                /** @type {__VLS_StyleScopedClasses['p-2']} */ ;
                /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
                /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
                /** @type {__VLS_StyleScopedClasses['hover:bg-gray-100']} */ ;
                /** @type {__VLS_StyleScopedClasses['transition-colors']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2 flex-1 min-w-0" }));
                /** @type {__VLS_StyleScopedClasses['flex']} */ ;
                /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
                /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
                /** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
                /** @type {__VLS_StyleScopedClasses['min-w-0']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: (__VLS_ctx.getAssetIcon(asset.asset_type)) }, { class: "text-gray-600 flex-shrink-0" }));
                /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
                /** @type {__VLS_StyleScopedClasses['flex-shrink-0']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "min-w-0 flex-1" }));
                /** @type {__VLS_StyleScopedClasses['min-w-0']} */ ;
                /** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm font-medium text-gray-900 truncate" }));
                /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
                /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
                /** @type {__VLS_StyleScopedClasses['truncate']} */ ;
                (asset.file_name);
                __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
                /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
                (__VLS_ctx.getAssetTypeLabel(asset.asset_type));
                (__VLS_ctx.formatFileSize(asset.file_size_kb * 1024));
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-1 flex-shrink-0" }));
                /** @type {__VLS_StyleScopedClasses['flex']} */ ;
                /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
                /** @type {__VLS_StyleScopedClasses['gap-1']} */ ;
                /** @type {__VLS_StyleScopedClasses['flex-shrink-0']} */ ;
                if (asset.is_primary) {
                    var __VLS_292 = void 0;
                    /** @ts-ignore @type {typeof __VLS_components.Tag} */
                    tag_1.default;
                    // @ts-ignore
                    var __VLS_293 = __VLS_asFunctionalComponent1(__VLS_292, new __VLS_292({
                        value: "Primary",
                        severity: "success",
                        size: "small",
                    }));
                    var __VLS_294 = __VLS_293.apply(void 0, __spreadArray([{
                            value: "Primary",
                            severity: "success",
                            size: "small",
                        }], __VLS_functionalComponentArgsRest(__VLS_293), false));
                }
                var __VLS_297 = void 0;
                /** @ts-ignore @type {typeof __VLS_components.Button} */
                button_1.default;
                // @ts-ignore
                var __VLS_298 = __VLS_asFunctionalComponent1(__VLS_297, new __VLS_297(__assign({ 'onClick': {} }, { icon: "pi pi-download", text: true, rounded: true, size: "small" })));
                var __VLS_299 = __VLS_298.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-download", text: true, rounded: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_298), false));
                var __VLS_302 = void 0;
                var __VLS_303 = ({ click: {} },
                    { onClick: function () {
                            var _a = [];
                            for (var _i = 0; _i < arguments.length; _i++) {
                                _a[_i] = arguments[_i];
                            }
                            var $event = _a[0];
                            if (!!(__VLS_ctx.loading))
                                return;
                            if (!(__VLS_ctx.product))
                                return;
                            if (!!(!__VLS_ctx.allAssets || __VLS_ctx.allAssets.length === 0))
                                return;
                            __VLS_ctx.downloadAsset(asset);
                            // @ts-ignore
                            [formatFileSize, allAssets, allAssets, allAssets, getAssetIcon, getAssetTypeLabel, downloadAsset,];
                        } });
                __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('Download') }), null, null);
                // @ts-ignore
                [vTooltip,];
            };
            var __VLS_300, __VLS_301;
            for (var _g = 0, _h = __VLS_vFor((__VLS_ctx.allAssets)); _g < _h.length; _g++) {
                var asset = _h[_g][0];
                _loop_2(asset);
            }
        }
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_287;
}
else {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center py-12" }));
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['py-12']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-exclamation-triangle text-6xl text-red-500 mb-4" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-exclamation-triangle']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-6xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-xl font-semibold text-gray-800 mb-2" }));
    /** @type {__VLS_StyleScopedClasses['text-xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600 mb-4" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
    var __VLS_304 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_305 = __VLS_asFunctionalComponent1(__VLS_304, new __VLS_304(__assign({ 'onClick': {} }, { label: "Back to Products", icon: "pi pi-arrow-left" })));
    var __VLS_306 = __VLS_305.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Back to Products", icon: "pi pi-arrow-left" })], __VLS_functionalComponentArgsRest(__VLS_305), false));
    var __VLS_309 = void 0;
    var __VLS_310 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                if (!!(__VLS_ctx.loading))
                    return;
                if (!!(__VLS_ctx.product))
                    return;
                __VLS_ctx.router.push({ name: 'merchandising.products' });
                // @ts-ignore
                [router,];
            } });
    var __VLS_307;
    var __VLS_308;
}
var __VLS_311;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
dialog_1.default;
// @ts-ignore
var __VLS_312 = __VLS_asFunctionalComponent1(__VLS_311, new __VLS_311(__assign({ visible: (__VLS_ctx.deleteDialogVisible), header: "Confirm Delete", modal: (true) }, { class: "w-96" })));
var __VLS_313 = __VLS_312.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.deleteDialogVisible), header: "Confirm Delete", modal: (true) }, { class: "w-96" })], __VLS_functionalComponentArgsRest(__VLS_312), false));
/** @type {__VLS_StyleScopedClasses['w-96']} */ ;
var __VLS_316 = __VLS_314.slots.default;
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
    var __VLS_317 = __VLS_314.slots.footer;
    var __VLS_318 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_319 = __VLS_asFunctionalComponent1(__VLS_318, new __VLS_318(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", text: true })));
    var __VLS_320 = __VLS_319.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", text: true })], __VLS_functionalComponentArgsRest(__VLS_319), false));
    var __VLS_323 = void 0;
    var __VLS_324 = ({ click: {} },
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
    var __VLS_321;
    var __VLS_322;
    var __VLS_325 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_326 = __VLS_asFunctionalComponent1(__VLS_325, new __VLS_325(__assign({ 'onClick': {} }, { label: "Delete", severity: "danger", loading: (__VLS_ctx.deleting) })));
    var __VLS_327 = __VLS_326.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Delete", severity: "danger", loading: (__VLS_ctx.deleting) })], __VLS_functionalComponentArgsRest(__VLS_326), false));
    var __VLS_330 = void 0;
    var __VLS_331 = ({ click: {} },
        { onClick: (__VLS_ctx.deleteProduct) });
    var __VLS_328;
    var __VLS_329;
    // @ts-ignore
    [deleting, deleteProduct,];
}
// @ts-ignore
[];
var __VLS_314;
var __VLS_332;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
dialog_1.default;
// @ts-ignore
var __VLS_333 = __VLS_asFunctionalComponent1(__VLS_332, new __VLS_332(__assign({ visible: (__VLS_ctx.galleryVisible), modal: (true) }, { class: "w-full max-w-4xl" })));
var __VLS_334 = __VLS_333.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.galleryVisible), modal: (true) }, { class: "w-full max-w-4xl" })], __VLS_functionalComponentArgsRest(__VLS_333), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['max-w-4xl']} */ ;
var __VLS_337 = __VLS_335.slots.default;
{
    var __VLS_338 = __VLS_335.slots.header;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-images" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-images']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
    // @ts-ignore
    [galleryVisible,];
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 md:grid-cols-3 gap-4" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['md:grid-cols-3']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
var _loop_3 = function (image, index) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (index) }, { class: "relative rounded-lg overflow-hidden" }));
    /** @type {__VLS_StyleScopedClasses['relative']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['overflow-hidden']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.img)(__assign(__assign(__assign({ onClick: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.selectedImage = image.url;
            __VLS_ctx.galleryVisible = false;
            // @ts-ignore
            [productImages, selectedImage, galleryVisible,];
        } }, { onError: (__VLS_ctx.handleImageError) }), { src: (image.url), alt: ("Image ".concat(index + 1)) }), { class: "w-full h-48 object-cover cursor-pointer hover:opacity-90 transition-opacity" }));
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    /** @type {__VLS_StyleScopedClasses['h-48']} */ ;
    /** @type {__VLS_StyleScopedClasses['object-cover']} */ ;
    /** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
    /** @type {__VLS_StyleScopedClasses['hover:opacity-90']} */ ;
    /** @type {__VLS_StyleScopedClasses['transition-opacity']} */ ;
    if (image.is_primary) {
        var __VLS_339 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Badge} */
        badge_1.default;
        // @ts-ignore
        var __VLS_340 = __VLS_asFunctionalComponent1(__VLS_339, new __VLS_339(__assign({ value: "Primary", severity: "success" }, { class: "absolute top-2 left-2" })));
        var __VLS_341 = __VLS_340.apply(void 0, __spreadArray([__assign({ value: "Primary", severity: "success" }, { class: "absolute top-2 left-2" })], __VLS_functionalComponentArgsRest(__VLS_340), false));
        /** @type {__VLS_StyleScopedClasses['absolute']} */ ;
        /** @type {__VLS_StyleScopedClasses['top-2']} */ ;
        /** @type {__VLS_StyleScopedClasses['left-2']} */ ;
    }
    // @ts-ignore
    [handleImageError,];
};
for (var _j = 0, _k = __VLS_vFor((__VLS_ctx.productImages)); _j < _k.length; _j++) {
    var _l = _k[_j], image = _l[0], index = _l[1];
    _loop_3(image, index);
}
// @ts-ignore
[];
var __VLS_335;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
