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
var _a, _b, _c, _d, _e, _f, _g, _h, _j, _k, _l, _m, _o, _p;
Object.defineProperty(exports, "__esModule", { value: true });
var vue_1 = require("vue");
var vue_router_1 = require("vue-router");
var usetoast_1 = require("primevue/usetoast");
var axios_1 = require("axios");
var THREE = require("three");
var GLTFLoader_1 = require("three/examples/jsm/loaders/GLTFLoader");
var OBJLoader_1 = require("three/examples/jsm/loaders/OBJLoader");
var OrbitControls_1 = require("three/examples/jsm/controls/OrbitControls");
var card_1 = require("primevue/card");
var button_1 = require("primevue/button");
var tag_1 = require("primevue/tag");
var badge_1 = require("primevue/badge");
var dialog_1 = require("primevue/dialog");
var skeleton_1 = require("primevue/skeleton");
var progressspinner_1 = require("primevue/progressspinner");
var auth_1 = require("../../../../stores/auth");
var router = (0, vue_router_1.useRouter)();
var toast = (0, usetoast_1.useToast)();
var models = (0, vue_1.ref)([]);
var loading = (0, vue_1.ref)(false);
var viewDialogVisible = (0, vue_1.ref)(false);
var currentModel = (0, vue_1.ref)(null);
var dialogViewerContainer = (0, vue_1.ref)(null);
var modelContainers = (0, vue_1.ref)(new Map());
var modelScenes = (0, vue_1.ref)(new Map());
var modelStates = (0, vue_1.reactive)({});
// Computed
var glbCount = (0, vue_1.computed)(function () {
    return models.value.filter(function (m) { var _a; return ((_a = m.model_format) === null || _a === void 0 ? void 0 : _a.toLowerCase()) === 'glb'; }).length;
});
var totalSizeFormatted = (0, vue_1.computed)(function () {
    var totalBytes = models.value.reduce(function (sum, m) { return sum + (m.file_size_kb * 1024); }, 0);
    return formatFileSize(totalBytes);
});
// Load Models from API
var loadModels = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, error_1;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                loading.value = true;
                _c.label = 1;
            case 1:
                _c.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, axios_1.default.get('/api/product-catalog/assets', {
                        params: {
                            asset_type: '3D_Model'
                        }
                    })];
            case 2:
                response = _c.sent();
                models.value = response.data.data.data || response.data.data || [];
                console.log("Loaded ".concat(models.value.length, " 3D models:"), models.value);
                // Initialize model states
                models.value.forEach(function (model) {
                    modelStates[model.id] = {
                        loaded: false,
                        error: false,
                        autoRotate: false
                    };
                });
                // Load 3D viewers after DOM update
                (0, vue_1.nextTick)(function () {
                    models.value.forEach(function (model) {
                        var container = modelContainers.value.get(model.id);
                        if (container) {
                            load3DModel(model, container);
                        }
                    });
                });
                return [3 /*break*/, 5];
            case 3:
                error_1 = _c.sent();
                console.error('Failed to load 3D models:', error_1);
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_b = (_a = error_1.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to load 3D models',
                    life: 5000
                });
                return [3 /*break*/, 5];
            case 4:
                loading.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var setModelRef = function (modelId, el) {
    if (el) {
        modelContainers.value.set(modelId, el);
    }
};
var load3DModel = function (model, container) {
    var _a, _b, _c, _d;
    if (!container || !model.url) {
        console.error('Missing container or model URL');
        modelStates[model.id].error = true;
        return;
    }
    try {
        var width = container.clientWidth;
        var height = container.clientHeight;
        // Scene
        var scene_1 = new THREE.Scene();
        scene_1.background = new THREE.Color(0xf5f5f5);
        // Camera
        var camera_1 = new THREE.PerspectiveCamera(50, width / height, 0.1, 1000);
        camera_1.position.set(((_a = model.camera_settings) === null || _a === void 0 ? void 0 : _a.angle_x) || 2, ((_b = model.camera_settings) === null || _b === void 0 ? void 0 : _b.angle_y) || 2, ((_c = model.camera_settings) === null || _c === void 0 ? void 0 : _c.zoom) || 5);
        // Renderer
        var renderer_1 = new THREE.WebGLRenderer({ antialias: true, alpha: true });
        renderer_1.setSize(width, height);
        renderer_1.setPixelRatio(window.devicePixelRatio);
        container.appendChild(renderer_1.domElement);
        // Lights
        var ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
        scene_1.add(ambientLight);
        var directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
        directionalLight.position.set(5, 10, 7.5);
        scene_1.add(directionalLight);
        // Controls
        var controls_1 = new OrbitControls_1.OrbitControls(camera_1, renderer_1.domElement);
        controls_1.enableDamping = true;
        controls_1.dampingFactor = 0.05;
        controls_1.minDistance = 1;
        controls_1.maxDistance = 20;
        // ✅ Get auth token
        var authStore = (0, auth_1.useAuthStore)();
        var token = authStore.token || localStorage.getItem('auth_token');
        // Load model
        var modelFormat = (_d = model.model_format) === null || _d === void 0 ? void 0 : _d.toLowerCase();
        console.log("Loading model ".concat(model.id, ":"), model.url);
        if (modelFormat === 'obj') {
            // ✅ OBJ Loader with auth using fetch
            fetch(model.url, {
                headers: {
                    'Authorization': "Bearer ".concat(token),
                    'Accept': '*/*'
                }
            })
                .then(function (response) {
                if (!response.ok) {
                    throw new Error("HTTP ".concat(response.status, ": ").concat(response.statusText));
                }
                return response.text();
            })
                .then(function (objText) {
                var loader = new OBJLoader_1.OBJLoader();
                var object = loader.parse(objText);
                // Center and scale
                var box = new THREE.Box3().setFromObject(object);
                var center = box.getCenter(new THREE.Vector3());
                var size = box.getSize(new THREE.Vector3());
                var maxDim = Math.max(size.x, size.y, size.z);
                var scale = 3 / maxDim;
                object.scale.multiplyScalar(scale);
                object.position.sub(center.multiplyScalar(scale));
                scene_1.add(object);
                modelStates[model.id].loaded = true;
                console.log("Model ".concat(model.id, " loaded successfully"));
                // Animation loop
                var animate = function () {
                    var _a;
                    if (!modelScenes.value.has(model.id))
                        return;
                    requestAnimationFrame(animate);
                    if ((_a = modelStates[model.id]) === null || _a === void 0 ? void 0 : _a.autoRotate) {
                        object.rotation.y += 0.005;
                    }
                    controls_1.update();
                    renderer_1.render(scene_1, camera_1);
                };
                animate();
            })
                .catch(function (error) {
                console.error("Failed to load OBJ model ".concat(model.id, ":"), error);
                modelStates[model.id].error = true;
            });
        }
        else {
            // ✅ GLTF/GLB Loader with auth using fetch
            fetch(model.url, {
                headers: {
                    'Authorization': "Bearer ".concat(token),
                    'Accept': 'application/octet-stream, application/json, */*'
                }
            })
                .then(function (response) {
                if (!response.ok) {
                    throw new Error("HTTP ".concat(response.status, ": ").concat(response.statusText));
                }
                return response.arrayBuffer();
            })
                .then(function (buffer) {
                var loader = new GLTFLoader_1.GLTFLoader();
                loader.parse(buffer, '', function (gltf) {
                    var object = gltf.scene;
                    // Center and scale
                    var box = new THREE.Box3().setFromObject(object);
                    var center = box.getCenter(new THREE.Vector3());
                    var size = box.getSize(new THREE.Vector3());
                    var maxDim = Math.max(size.x, size.y, size.z);
                    var scale = 3 / maxDim;
                    object.scale.multiplyScalar(scale);
                    object.position.sub(center.multiplyScalar(scale));
                    scene_1.add(object);
                    modelStates[model.id].loaded = true;
                    console.log("Model ".concat(model.id, " loaded successfully"));
                    // Animation loop
                    var animate = function () {
                        var _a;
                        if (!modelScenes.value.has(model.id))
                            return;
                        requestAnimationFrame(animate);
                        if ((_a = modelStates[model.id]) === null || _a === void 0 ? void 0 : _a.autoRotate) {
                            object.rotation.y += 0.005;
                        }
                        controls_1.update();
                        renderer_1.render(scene_1, camera_1);
                    };
                    animate();
                }, function (error) {
                    console.error("Failed to parse GLTF model ".concat(model.id, ":"), error);
                    modelStates[model.id].error = true;
                });
            })
                .catch(function (error) {
                console.error("Failed to load GLTF model ".concat(model.id, ":"), error);
                modelStates[model.id].error = true;
            });
        }
        // Store scene for cleanup
        modelScenes.value.set(model.id, { scene: scene_1, renderer: renderer_1, controls: controls_1, camera: camera_1 });
    }
    catch (error) {
        console.error("Error initializing model ".concat(model.id, ":"), error);
        modelStates[model.id].error = true;
    }
};
var retryLoadModel = function (model) {
    modelStates[model.id].error = false;
    modelStates[model.id].loaded = false;
    var container = modelContainers.value.get(model.id);
    if (container) {
        // Clear container
        while (container.firstChild) {
            container.removeChild(container.firstChild);
        }
        // Reload
        load3DModel(model, container);
    }
};
var toggleAutoRotate = function (modelId) {
    if (modelStates[modelId]) {
        modelStates[modelId].autoRotate = !modelStates[modelId].autoRotate;
    }
};
var viewModel = function (model) {
    currentModel.value = model;
    viewDialogVisible.value = true;
    (0, vue_1.nextTick)(function () {
        if (dialogViewerContainer.value) {
            load3DModel(model, dialogViewerContainer.value);
        }
    });
};
var closeViewDialog = function () {
    // Cleanup dialog viewer
    if (dialogViewerContainer.value) {
        while (dialogViewerContainer.value.firstChild) {
            dialogViewerContainer.value.removeChild(dialogViewerContainer.value.firstChild);
        }
    }
    viewDialogVisible.value = false;
    currentModel.value = null;
};
var downloadModel = function (model) {
    window.open(model.url, '_blank');
    toast.add({
        severity: 'success',
        summary: 'Download Started',
        detail: "Downloading ".concat(model.file_name),
        life: 2000
    });
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
// Cleanup on unmount
(0, vue_1.onBeforeUnmount)(function () {
    modelScenes.value.forEach(function (sceneData) {
        var _a, _b;
        (_a = sceneData.renderer) === null || _a === void 0 ? void 0 : _a.dispose();
        (_b = sceneData.controls) === null || _b === void 0 ? void 0 : _b.dispose();
    });
    modelScenes.value.clear();
    modelContainers.value.clear();
});
(0, vue_1.onMounted)(function () {
    loadModels();
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
button_1.default;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ 'onClick': {} }, { label: "Refresh", icon: "pi pi-refresh", severity: "secondary", outlined: true, loading: (__VLS_ctx.loading) })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Refresh", icon: "pi pi-refresh", severity: "secondary", outlined: true, loading: (__VLS_ctx.loading) })], __VLS_functionalComponentArgsRest(__VLS_1), false));
var __VLS_5;
var __VLS_6 = ({ click: {} },
    { onClick: (__VLS_ctx.loadModels) });
var __VLS_3;
var __VLS_4;
var __VLS_7;
/** @ts-ignore @type {typeof __VLS_components.Button} */
button_1.default;
// @ts-ignore
var __VLS_8 = __VLS_asFunctionalComponent1(__VLS_7, new __VLS_7(__assign({ 'onClick': {} }, { label: "Upload New", icon: "pi pi-cloud-upload" })));
var __VLS_9 = __VLS_8.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Upload New", icon: "pi pi-cloud-upload" })], __VLS_functionalComponentArgsRest(__VLS_8), false));
var __VLS_12;
var __VLS_13 = ({ click: {} },
    { onClick: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.router.push({ name: 'merchandising.assets.upload' });
            // @ts-ignore
            [loading, loadModels, router,];
        } });
var __VLS_10;
var __VLS_11;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-3 gap-4" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
/** @type {__VLS_StyleScopedClasses['md:grid-cols-3']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
var __VLS_14;
/** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
card_1.default;
// @ts-ignore
var __VLS_15 = __VLS_asFunctionalComponent1(__VLS_14, new __VLS_14({}));
var __VLS_16 = __VLS_15.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_15), false));
var __VLS_19 = __VLS_17.slots.default;
{
    var __VLS_20 = __VLS_17.slots.content;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-3xl font-bold text-indigo-600 mt-1" }));
    /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-indigo-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
    (__VLS_ctx.models.length);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-indigo-100 p-4 rounded-full" }));
    /** @type {__VLS_StyleScopedClasses['bg-indigo-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-cube text-indigo-600 text-3xl" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-cube']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-indigo-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
    // @ts-ignore
    [models,];
}
// @ts-ignore
[];
var __VLS_17;
var __VLS_21;
/** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
card_1.default;
// @ts-ignore
var __VLS_22 = __VLS_asFunctionalComponent1(__VLS_21, new __VLS_21({}));
var __VLS_23 = __VLS_22.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_22), false));
var __VLS_26 = __VLS_24.slots.default;
{
    var __VLS_27 = __VLS_24.slots.content;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-3xl font-bold text-blue-600 mt-1" }));
    /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
    (__VLS_ctx.glbCount);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-blue-100 p-4 rounded-full" }));
    /** @type {__VLS_StyleScopedClasses['bg-blue-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-box text-blue-600 text-3xl" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-box']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
    // @ts-ignore
    [glbCount,];
}
// @ts-ignore
[];
var __VLS_24;
var __VLS_28;
/** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
card_1.default;
// @ts-ignore
var __VLS_29 = __VLS_asFunctionalComponent1(__VLS_28, new __VLS_28({}));
var __VLS_30 = __VLS_29.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_29), false));
var __VLS_33 = __VLS_31.slots.default;
{
    var __VLS_34 = __VLS_31.slots.content;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-3xl font-bold text-green-600 mt-1" }));
    /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-green-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
    (__VLS_ctx.totalSizeFormatted);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-green-100 p-4 rounded-full" }));
    /** @type {__VLS_StyleScopedClasses['bg-green-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-database text-green-600 text-3xl" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-database']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-green-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
    // @ts-ignore
    [totalSizeFormatted,];
}
// @ts-ignore
[];
var __VLS_31;
if (__VLS_ctx.loading && __VLS_ctx.models.length === 0) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['md:grid-cols-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['lg:grid-cols-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-6']} */ ;
    for (var _i = 0, _q = __VLS_vFor((6)); _i < _q.length; _i++) {
        var i = _q[_i][0];
        var __VLS_35 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Skeleton} */
        skeleton_1.default;
        // @ts-ignore
        var __VLS_36 = __VLS_asFunctionalComponent1(__VLS_35, new __VLS_35(__assign({ key: (i), height: "400px" }, { class: "rounded-lg" })));
        var __VLS_37 = __VLS_36.apply(void 0, __spreadArray([__assign({ key: (i), height: "400px" }, { class: "rounded-lg" })], __VLS_functionalComponentArgsRest(__VLS_36), false));
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        // @ts-ignore
        [loading, models,];
    }
}
else if (__VLS_ctx.models.length > 0) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['md:grid-cols-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['lg:grid-cols-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-6']} */ ;
    var _loop_1 = function (model) {
        var __VLS_40 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
        card_1.default;
        // @ts-ignore
        var __VLS_41 = __VLS_asFunctionalComponent1(__VLS_40, new __VLS_40(__assign({ key: (model.id) }, { class: "hover:shadow-2xl transition-shadow" })));
        var __VLS_42 = __VLS_41.apply(void 0, __spreadArray([__assign({ key: (model.id) }, { class: "hover:shadow-2xl transition-shadow" })], __VLS_functionalComponentArgsRest(__VLS_41), false));
        /** @type {__VLS_StyleScopedClasses['hover:shadow-2xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['transition-shadow']} */ ;
        var __VLS_45 = __VLS_43.slots.default;
        {
            var __VLS_46 = __VLS_43.slots.content;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
            /** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign(__assign({ ref: (function (el) { return __VLS_ctx.setModelRef(model.id, el); }) }, { class: "relative bg-gradient-to-br from-gray-50 to-gray-100 rounded-lg overflow-hidden" }), { style: {} }));
            /** @type {__VLS_StyleScopedClasses['relative']} */ ;
            /** @type {__VLS_StyleScopedClasses['bg-gradient-to-br']} */ ;
            /** @type {__VLS_StyleScopedClasses['from-gray-50']} */ ;
            /** @type {__VLS_StyleScopedClasses['to-gray-100']} */ ;
            /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
            /** @type {__VLS_StyleScopedClasses['overflow-hidden']} */ ;
            if (!((_a = __VLS_ctx.modelStates[model.id]) === null || _a === void 0 ? void 0 : _a.loaded)) {
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
                var __VLS_47 = void 0;
                /** @ts-ignore @type {typeof __VLS_components.ProgressSpinner} */
                progressspinner_1.default;
                // @ts-ignore
                var __VLS_48 = __VLS_asFunctionalComponent1(__VLS_47, new __VLS_47(__assign({ style: {} }, { strokeWidth: "4" })));
                var __VLS_49 = __VLS_48.apply(void 0, __spreadArray([__assign({ style: {} }, { strokeWidth: "4" })], __VLS_functionalComponentArgsRest(__VLS_48), false));
                __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600 mt-2" }));
                /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
                /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
            }
            if ((_b = __VLS_ctx.modelStates[model.id]) === null || _b === void 0 ? void 0 : _b.error) {
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
                var __VLS_52 = void 0;
                /** @ts-ignore @type {typeof __VLS_components.Button} */
                button_1.default;
                // @ts-ignore
                var __VLS_53 = __VLS_asFunctionalComponent1(__VLS_52, new __VLS_52(__assign(__assign({ 'onClick': {} }, { label: "Retry", size: "small" }), { class: "mt-2" })));
                var __VLS_54 = __VLS_53.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { label: "Retry", size: "small" }), { class: "mt-2" })], __VLS_functionalComponentArgsRest(__VLS_53), false));
                var __VLS_57 = void 0;
                var __VLS_58 = ({ click: {} },
                    { onClick: function () {
                            var _a;
                            var _b = [];
                            for (var _i = 0; _i < arguments.length; _i++) {
                                _b[_i] = arguments[_i];
                            }
                            var $event = _b[0];
                            if (!!(__VLS_ctx.loading && __VLS_ctx.models.length === 0))
                                return;
                            if (!(__VLS_ctx.models.length > 0))
                                return;
                            if (!((_a = __VLS_ctx.modelStates[model.id]) === null || _a === void 0 ? void 0 : _a.error))
                                return;
                            __VLS_ctx.retryLoadModel(model);
                            // @ts-ignore
                            [models, models, setModelRef, modelStates, modelStates, retryLoadModel,];
                        } });
                /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
            }
            if ((_c = __VLS_ctx.modelStates[model.id]) === null || _c === void 0 ? void 0 : _c.loaded) {
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "absolute top-2 right-2 z-20" }));
                /** @type {__VLS_StyleScopedClasses['absolute']} */ ;
                /** @type {__VLS_StyleScopedClasses['top-2']} */ ;
                /** @type {__VLS_StyleScopedClasses['right-2']} */ ;
                /** @type {__VLS_StyleScopedClasses['z-20']} */ ;
                var __VLS_59 = void 0;
                /** @ts-ignore @type {typeof __VLS_components.Button} */
                button_1.default;
                // @ts-ignore
                var __VLS_60 = __VLS_asFunctionalComponent1(__VLS_59, new __VLS_59(__assign({ 'onClick': {} }, { icon: (((_d = __VLS_ctx.modelStates[model.id]) === null || _d === void 0 ? void 0 : _d.autoRotate) ? 'pi pi-pause' : 'pi pi-play'), rounded: true, size: "small", severity: (((_e = __VLS_ctx.modelStates[model.id]) === null || _e === void 0 ? void 0 : _e.autoRotate) ? 'warning' : 'secondary') })));
                var __VLS_61 = __VLS_60.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: (((_f = __VLS_ctx.modelStates[model.id]) === null || _f === void 0 ? void 0 : _f.autoRotate) ? 'pi pi-pause' : 'pi pi-play'), rounded: true, size: "small", severity: (((_g = __VLS_ctx.modelStates[model.id]) === null || _g === void 0 ? void 0 : _g.autoRotate) ? 'warning' : 'secondary') })], __VLS_functionalComponentArgsRest(__VLS_60), false));
                var __VLS_64 = void 0;
                var __VLS_65 = ({ click: {} },
                    { onClick: function () {
                            var _a;
                            var _b = [];
                            for (var _i = 0; _i < arguments.length; _i++) {
                                _b[_i] = arguments[_i];
                            }
                            var $event = _b[0];
                            if (!!(__VLS_ctx.loading && __VLS_ctx.models.length === 0))
                                return;
                            if (!(__VLS_ctx.models.length > 0))
                                return;
                            if (!((_a = __VLS_ctx.modelStates[model.id]) === null || _a === void 0 ? void 0 : _a.loaded))
                                return;
                            __VLS_ctx.toggleAutoRotate(model.id);
                            // @ts-ignore
                            [modelStates, modelStates, modelStates, toggleAutoRotate,];
                        } });
            }
            if (model.is_primary) {
                var __VLS_66 = void 0;
                /** @ts-ignore @type {typeof __VLS_components.Badge} */
                badge_1.default;
                // @ts-ignore
                var __VLS_67 = __VLS_asFunctionalComponent1(__VLS_66, new __VLS_66(__assign({ value: "Primary", severity: "success" }, { class: "absolute top-2 left-2 z-20" })));
                var __VLS_68 = __VLS_67.apply(void 0, __spreadArray([__assign({ value: "Primary", severity: "success" }, { class: "absolute top-2 left-2 z-20" })], __VLS_functionalComponentArgsRest(__VLS_67), false));
                /** @type {__VLS_StyleScopedClasses['absolute']} */ ;
                /** @type {__VLS_StyleScopedClasses['top-2']} */ ;
                /** @type {__VLS_StyleScopedClasses['left-2']} */ ;
                /** @type {__VLS_StyleScopedClasses['z-20']} */ ;
            }
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
            __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-lg font-bold text-gray-900 truncate" }));
            /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
            /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
            /** @type {__VLS_StyleScopedClasses['truncate']} */ ;
            (model.file_name);
            if (model.product) {
                __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600 mt-1 truncate" }));
                /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
                /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
                /** @type {__VLS_StyleScopedClasses['truncate']} */ ;
                (model.product.product_name);
            }
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2 mt-3" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
            /** @type {__VLS_StyleScopedClasses['mt-3']} */ ;
            var __VLS_71 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Tag} */
            tag_1.default;
            // @ts-ignore
            var __VLS_72 = __VLS_asFunctionalComponent1(__VLS_71, new __VLS_71({
                value: ((_h = model.model_format) === null || _h === void 0 ? void 0 : _h.toUpperCase()),
                severity: "info",
                size: "small",
            }));
            var __VLS_73 = __VLS_72.apply(void 0, __spreadArray([{
                    value: ((_j = model.model_format) === null || _j === void 0 ? void 0 : _j.toUpperCase()),
                    severity: "info",
                    size: "small",
                }], __VLS_functionalComponentArgsRest(__VLS_72), false));
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-xs text-gray-500" }));
            /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
            (__VLS_ctx.formatFileSize(model.file_size_kb * 1024));
            if (model.camera_settings) {
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "mt-3 p-3 bg-gray-50 rounded-lg" }));
                /** @type {__VLS_StyleScopedClasses['mt-3']} */ ;
                /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
                /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
                /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs font-semibold text-gray-700 mb-2" }));
                /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
                /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
                /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-3 gap-2 text-xs text-gray-600" }));
                /** @type {__VLS_StyleScopedClasses['grid']} */ ;
                /** @type {__VLS_StyleScopedClasses['grid-cols-3']} */ ;
                /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
                __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "block text-gray-500" }));
                /** @type {__VLS_StyleScopedClasses['block']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-medium" }));
                /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
                (model.camera_settings.angle_x);
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
                __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "block text-gray-500" }));
                /** @type {__VLS_StyleScopedClasses['block']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-medium" }));
                /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
                (model.camera_settings.angle_y);
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
                __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "block text-gray-500" }));
                /** @type {__VLS_StyleScopedClasses['block']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-medium" }));
                /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
                (model.camera_settings.zoom);
            }
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2 mt-4" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
            /** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
            var __VLS_76 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            button_1.default;
            // @ts-ignore
            var __VLS_77 = __VLS_asFunctionalComponent1(__VLS_76, new __VLS_76(__assign(__assign({ 'onClick': {} }, { label: "View", icon: "pi pi-eye", size: "small" }), { class: "flex-1" })));
            var __VLS_78 = __VLS_77.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { label: "View", icon: "pi pi-eye", size: "small" }), { class: "flex-1" })], __VLS_functionalComponentArgsRest(__VLS_77), false));
            var __VLS_81 = void 0;
            var __VLS_82 = ({ click: {} },
                { onClick: function () {
                        var _a = [];
                        for (var _i = 0; _i < arguments.length; _i++) {
                            _a[_i] = arguments[_i];
                        }
                        var $event = _a[0];
                        if (!!(__VLS_ctx.loading && __VLS_ctx.models.length === 0))
                            return;
                        if (!(__VLS_ctx.models.length > 0))
                            return;
                        __VLS_ctx.viewModel(model);
                        // @ts-ignore
                        [formatFileSize, viewModel,];
                    } });
            /** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
            var __VLS_83 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            button_1.default;
            // @ts-ignore
            var __VLS_84 = __VLS_asFunctionalComponent1(__VLS_83, new __VLS_83(__assign(__assign({ 'onClick': {} }, { label: "Download", icon: "pi pi-download", severity: "secondary", size: "small" }), { class: "flex-1" })));
            var __VLS_85 = __VLS_84.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { label: "Download", icon: "pi pi-download", severity: "secondary", size: "small" }), { class: "flex-1" })], __VLS_functionalComponentArgsRest(__VLS_84), false));
            var __VLS_88 = void 0;
            var __VLS_89 = ({ click: {} },
                { onClick: function () {
                        var _a = [];
                        for (var _i = 0; _i < arguments.length; _i++) {
                            _a[_i] = arguments[_i];
                        }
                        var $event = _a[0];
                        if (!!(__VLS_ctx.loading && __VLS_ctx.models.length === 0))
                            return;
                        if (!(__VLS_ctx.models.length > 0))
                            return;
                        __VLS_ctx.downloadModel(model);
                        // @ts-ignore
                        [downloadModel,];
                    } });
            /** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
            // @ts-ignore
            [];
        }
        // @ts-ignore
        [];
        // @ts-ignore
        [];
    };
    var __VLS_55, __VLS_56, __VLS_62, __VLS_63, __VLS_79, __VLS_80, __VLS_86, __VLS_87, __VLS_43;
    for (var _r = 0, _s = __VLS_vFor((__VLS_ctx.models)); _r < _s.length; _r++) {
        var model = _s[_r][0];
        _loop_1(model);
    }
}
else {
    var __VLS_90 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    card_1.default;
    // @ts-ignore
    var __VLS_91 = __VLS_asFunctionalComponent1(__VLS_90, new __VLS_90({}));
    var __VLS_92 = __VLS_91.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_91), false));
    var __VLS_95 = __VLS_93.slots.default;
    {
        var __VLS_96 = __VLS_93.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center py-12" }));
        /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['py-12']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-cube text-6xl text-gray-300 mb-4" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-cube']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-6xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-300']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-xl font-semibold text-gray-800 mb-2" }));
        /** @type {__VLS_StyleScopedClasses['text-xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600 mb-4" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
        var __VLS_97 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_98 = __VLS_asFunctionalComponent1(__VLS_97, new __VLS_97(__assign({ 'onClick': {} }, { label: "Upload 3D Model", icon: "pi pi-cloud-upload" })));
        var __VLS_99 = __VLS_98.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Upload 3D Model", icon: "pi pi-cloud-upload" })], __VLS_functionalComponentArgsRest(__VLS_98), false));
        var __VLS_102 = void 0;
        var __VLS_103 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!!(__VLS_ctx.loading && __VLS_ctx.models.length === 0))
                        return;
                    if (!!(__VLS_ctx.models.length > 0))
                        return;
                    __VLS_ctx.$router.push({ name: 'merchandising.assets.upload' });
                    // @ts-ignore
                    [$router,];
                } });
        var __VLS_100;
        var __VLS_101;
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_93;
}
var __VLS_104;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
dialog_1.default;
// @ts-ignore
var __VLS_105 = __VLS_asFunctionalComponent1(__VLS_104, new __VLS_104(__assign({ visible: (__VLS_ctx.viewDialogVisible), header: ((_k = __VLS_ctx.currentModel) === null || _k === void 0 ? void 0 : _k.file_name), modal: (true) }, { class: "w-full max-w-4xl" })));
var __VLS_106 = __VLS_105.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.viewDialogVisible), header: ((_l = __VLS_ctx.currentModel) === null || _l === void 0 ? void 0 : _l.file_name), modal: (true) }, { class: "w-full max-w-4xl" })], __VLS_functionalComponentArgsRest(__VLS_105), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['max-w-4xl']} */ ;
var __VLS_109 = __VLS_107.slots.default;
if (__VLS_ctx.currentModel) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
    /** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign(__assign({ ref: "dialogViewerContainer" }, { class: "bg-gradient-to-br from-gray-100 to-gray-200 rounded-lg" }), { style: {} }));
    /** @type {__VLS_StyleScopedClasses['bg-gradient-to-br']} */ ;
    /** @type {__VLS_StyleScopedClasses['from-gray-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['to-gray-200']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
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
    var __VLS_110 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    tag_1.default;
    // @ts-ignore
    var __VLS_111 = __VLS_asFunctionalComponent1(__VLS_110, new __VLS_110({
        value: ((_m = __VLS_ctx.currentModel.model_format) === null || _m === void 0 ? void 0 : _m.toUpperCase()),
        severity: "info",
    }));
    var __VLS_112 = __VLS_111.apply(void 0, __spreadArray([{
            value: ((_o = __VLS_ctx.currentModel.model_format) === null || _o === void 0 ? void 0 : _o.toUpperCase()),
            severity: "info",
        }], __VLS_functionalComponentArgsRest(__VLS_111), false));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mb-1" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm font-semibold" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    (__VLS_ctx.formatFileSize(__VLS_ctx.currentModel.file_size_kb * 1024));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mb-1" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm font-semibold" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    (((_p = __VLS_ctx.currentModel.product) === null || _p === void 0 ? void 0 : _p.product_name) || 'N/A');
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mb-1" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm font-semibold" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    (__VLS_ctx.formatDate(__VLS_ctx.currentModel.created_at));
}
{
    var __VLS_115 = __VLS_107.slots.footer;
    var __VLS_116 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_117 = __VLS_asFunctionalComponent1(__VLS_116, new __VLS_116(__assign({ 'onClick': {} }, { label: "Download", icon: "pi pi-download" })));
    var __VLS_118 = __VLS_117.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Download", icon: "pi pi-download" })], __VLS_functionalComponentArgsRest(__VLS_117), false));
    var __VLS_121 = void 0;
    var __VLS_122 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.downloadModel(__VLS_ctx.currentModel);
                // @ts-ignore
                [formatFileSize, downloadModel, viewDialogVisible, currentModel, currentModel, currentModel, currentModel, currentModel, currentModel, currentModel, formatDate,];
            } });
    var __VLS_119;
    var __VLS_120;
    var __VLS_123 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_124 = __VLS_asFunctionalComponent1(__VLS_123, new __VLS_123(__assign({ 'onClick': {} }, { label: "Close", severity: "secondary", outlined: true })));
    var __VLS_125 = __VLS_124.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Close", severity: "secondary", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_124), false));
    var __VLS_128 = void 0;
    var __VLS_129 = ({ click: {} },
        { onClick: (__VLS_ctx.closeViewDialog) });
    var __VLS_126;
    var __VLS_127;
    // @ts-ignore
    [closeViewDialog,];
}
// @ts-ignore
[];
var __VLS_107;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
