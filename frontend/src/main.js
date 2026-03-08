"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
// main.ts or main.js
var vue_1 = require("vue");
var pinia_1 = require("pinia");
var auth_1 = require("./stores/auth"); // ✅ Uncommented
require("./style.css");
var config_1 = require("primevue/config");
var aura_1 = require("@primeuix/themes/aura");
require("primeicons/primeicons.css");
var App_vue_1 = require("./App.vue");
var router_1 = require("./router");
var axios_1 = require("axios");
var card_1 = require("primevue/card");
var inputtext_1 = require("primevue/inputtext");
var password_1 = require("primevue/password");
var button_1 = require("primevue/button");
var checkbox_1 = require("primevue/checkbox");
var message_1 = require("primevue/message");
var dialog_1 = require("primevue/dialog");
var progressspinner_1 = require("primevue/progressspinner");
var toastservice_1 = require("primevue/toastservice");
var confirmationservice_1 = require("primevue/confirmationservice");
var datatable_1 = require("primevue/datatable");
var column_1 = require("primevue/column");
var badge_1 = require("primevue/badge");
var datepicker_1 = require("primevue/datepicker");
var tag_1 = require("primevue/tag");
var select_1 = require("primevue/select");
var avatar_1 = require("primevue/avatar");
var iconfield_1 = require("primevue/iconfield");
var inputicon_1 = require("primevue/inputicon");
var row_1 = require("primevue/row");
var radiobutton_1 = require("primevue/radiobutton");
var tabs_1 = require("primevue/tabs");
var tablist_1 = require("primevue/tablist");
var tab_1 = require("primevue/tab");
var tabpanels_1 = require("primevue/tabpanels");
var tabpanel_1 = require("primevue/tabpanel");
var inputnumber_1 = require("primevue/inputnumber");
var tooltip_1 = require("primevue/tooltip");
var textarea_1 = require("primevue/textarea");
var scrolltop_1 = require("primevue/scrolltop");
var skeleton_1 = require("primevue/skeleton");
var paginator_1 = require("primevue/paginator");
var fileupload_1 = require("primevue/fileupload");
var tree_1 = require("primevue/tree");
// ==================== AXIOS CONFIGURATION ====================
axios_1.default.defaults.baseURL = 'http://localhost:8000';
axios_1.default.defaults.withCredentials = true;
axios_1.default.defaults.withXSRFToken = true;
// ==================== DUPLICATE REQUEST PREVENTION ====================
var pendingRequests = new Map();
var generateRequestKey = function (config) {
    var _a, _b, _c;
    // ✅ Deduplicate ALL requests (not just GET)
    return "".concat((_a = config.method) === null || _a === void 0 ? void 0 : _a.toUpperCase(), ":").concat(config.url, ":").concat(JSON.stringify((_c = (_b = config.data) !== null && _b !== void 0 ? _b : config.params) !== null && _c !== void 0 ? _c : {}));
};
axios_1.default.interceptors.request.use(function (config) {
    var key = generateRequestKey(config);
    if (pendingRequests.has(key)) {
        console.warn('⚠️ Duplicate request detected:', key);
        var controller = new AbortController();
        controller.abort();
        config.signal = controller.signal;
    }
    else {
        var controller = new AbortController();
        pendingRequests.set(key, controller);
        config.signal = controller.signal;
    }
    return config;
});
axios_1.default.interceptors.response.use(function (response) {
    var key = generateRequestKey(response.config);
    pendingRequests.delete(key);
    return response;
}, function (error) {
    var _a;
    if (error.config) {
        var key = generateRequestKey(error.config);
        pendingRequests.delete(key);
    }
    if (error.name === 'CanceledError' || error.code === 'ERR_CANCELED') {
        console.log('🚫 Duplicate request cancelled:', (_a = error.config) === null || _a === void 0 ? void 0 : _a.url);
        return new Promise(function () { });
    }
    return Promise.reject(error);
});
// ==================== APP INITIALIZATION ====================
var app = (0, vue_1.createApp)(App_vue_1.default);
var pinia = (0, pinia_1.createPinia)();
// Install Pinia FIRST (required for auth store)
app.use(pinia);
// Configure PrimeVue
app.use(config_1.default, {
    theme: {
        preset: aura_1.default,
        options: {
            darkModeSelector: '.my-app-dark',
        }
    }
});
app.use(toastservice_1.default);
app.use(confirmationservice_1.default);
app.use(router_1.default);
// Register PrimeVue components
var components = {
    Tree: tree_1.default,
    FileUpload: fileupload_1.default,
    Paginator: paginator_1.default,
    Skeleton: skeleton_1.default,
    ScrollTop: scrolltop_1.default,
    Tabs: tabs_1.default,
    Tab: tab_1.default,
    TabList: tablist_1.default,
    TabPanels: tabpanels_1.default,
    TabPanel: tabpanel_1.default,
    InputNumber: inputnumber_1.default,
    Card: card_1.default,
    Avatar: avatar_1.default,
    InputText: inputtext_1.default,
    Textarea: textarea_1.default,
    InputIcon: inputicon_1.default,
    Password: password_1.default,
    Button: button_1.default,
    Checkbox: checkbox_1.default,
    Message: message_1.default,
    Dialog: dialog_1.default,
    ProgressSpinner: progressspinner_1.default,
    DataTable: datatable_1.default,
    Column: column_1.default,
    Row: row_1.default,
    Badge: badge_1.default,
    DatePicker: datepicker_1.default,
    Tag: tag_1.default,
    Select: select_1.default,
    IconField: iconfield_1.default,
    RadioButton: radiobutton_1.default,
};
Object.entries(components).forEach(function (_a) {
    var name = _a[0], component = _a[1];
    app.component(name, component);
});
app.directive('tooltip', tooltip_1.default);
// ==================== AUTH INITIALIZATION ====================
// Initialize auth store and load permissions BEFORE mounting
var authStore = (0, auth_1.useAuthStore)();
// Set Authorization header if token exists in localStorage
if (authStore.token) {
    axios_1.default.defaults.headers.common['Authorization'] = "Bearer ".concat(authStore.token);
    console.log('🔐 Token restored from localStorage');
    // Initialize permissions (async, but don't wait)
    authStore.initialize().catch(function (err) {
        console.warn('⚠️ Failed to initialize permissions:', err);
    });
}
// ✅ Mount immediately (don't wait for initialize)
app.mount('#app');
