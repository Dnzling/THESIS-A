"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
// axios.ts
var axios_1 = require("axios");
var router_1 = require("./router");
// ==================== PENDING REQUEST DEDUPLICATION ====================
// Tracks in-flight GET requests by key. If the same GET fires again before
// the first one completes, the duplicate is cancelled immediately.
// POST/PUT/DELETE are never deduplicated (they are intentional mutations).
var pendingRequests = new Map();
var generateKey = function (config) {
    var _a, _b;
    // Only deduplicate GET requests
    if (((_a = config.method) === null || _a === void 0 ? void 0 : _a.toLowerCase()) !== 'get')
        return '';
    return "GET:".concat(config.url, ":").concat(JSON.stringify((_b = config.params) !== null && _b !== void 0 ? _b : {}));
};
// ==================== AXIOS INSTANCE ====================
var axiosClient = axios_1.default.create({
    baseURL: import.meta.env.VITE_API_BASE_URL,
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
    },
    withCredentials: true,
    timeout: 30000,
});
// ==================== REQUEST INTERCEPTOR ====================
axiosClient.interceptors.request.use(function (config) {
    // Attach auth token
    var token = localStorage.getItem('auth_token') || localStorage.getItem('access_token');
    if (token) {
        config.headers.Authorization = "Bearer ".concat(token);
    }
    var key = generateKey(config);
    if (key) {
        // If an identical GET is already in-flight, cancel the NEW duplicate
        if (pendingRequests.has(key)) {
            var controller = new AbortController();
            controller.abort("Duplicate GET cancelled: ".concat(config.url));
            config.signal = controller.signal;
        }
        else {
            // Register this request
            var controller = new AbortController();
            pendingRequests.set(key, controller);
            config.signal = controller.signal;
        }
    }
    return config;
}, function (error) { return Promise.reject(error); });
// ==================== RESPONSE INTERCEPTOR ====================
axiosClient.interceptors.response.use(function (response) {
    // Clean up on success
    var key = generateKey(response.config);
    if (key)
        pendingRequests.delete(key);
    return response;
}, function (error) {
    var _a, _b;
    // Clean up on error/cancel
    if (error.config) {
        var key = generateKey(error.config);
        if (key)
            pendingRequests.delete(key);
    }
    // Silently swallow cancelled duplicate requests
    if (axios_1.default.isCancel(error) || error.name === 'CanceledError' || error.code === 'ERR_CANCELED') {
        return new Promise(function () { }); // never resolves — caller gets no response
    }
    // Handle 401 Unauthorized
    if (((_a = error.response) === null || _a === void 0 ? void 0 : _a.status) === 401) {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('access_token');
        localStorage.removeItem('user');
        var currentRoute = (_b = router_1.default.currentRoute.value) === null || _b === void 0 ? void 0 : _b.name;
        if (currentRoute !== 'Login') {
            router_1.default.push({ name: 'Login' });
        }
    }
    return Promise.reject(error);
});
exports.default = axiosClient;
