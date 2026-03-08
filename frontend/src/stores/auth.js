"use strict";
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
Object.defineProperty(exports, "__esModule", { value: true });
exports.useAuthStore = void 0;
var pinia_1 = require("pinia");
var vue_1 = require("vue");
var axios_1 = require("axios");
var router_1 = require("../router");
exports.useAuthStore = (0, pinia_1.defineStore)('auth', function () {
    // ==========================================
    // STATE
    // ==========================================
    var token = (0, vue_1.ref)(localStorage.getItem('auth_token'));
    var user = (0, vue_1.ref)(JSON.parse(localStorage.getItem('user') || 'null'));
    var loading = (0, vue_1.ref)(false);
    var error = (0, vue_1.ref)(null);
    // RBAC State
    var permissions = (0, vue_1.ref)([]);
    var navigation = (0, vue_1.ref)([]);
    var permissionsLoaded = (0, vue_1.ref)(false);
    var isLoadingPermissions = (0, vue_1.ref)(false);
    // ==========================================
    // GETTERS
    // ==========================================
    var isAuthenticated = (0, vue_1.computed)(function () { return !!token.value; });
    var currentUser = (0, vue_1.computed)(function () { return user.value; });
    var userRole = (0, vue_1.computed)(function () { var _a; return ((_a = user.value) === null || _a === void 0 ? void 0 : _a.role) || null; });
    var userAbilities = (0, vue_1.computed)(function () { var _a; return ((_a = user.value) === null || _a === void 0 ? void 0 : _a.abilities) || []; });
    // Default route based on role
    var defaultRoute = (0, vue_1.computed)(function () {
        var _a;
        switch ((_a = user.value) === null || _a === void 0 ? void 0 : _a.role) {
            case 'super_admin':
                return '/admin/dashboard';
            case 'store_admin':
            case 'store_manager':
                return '/system/index';
            case 'hr_manager':
                return '/hr/index';
            case 'warehouse_manager':
            case 'inventory_staff':
                return '/merchandising/products';
            case 'sales_staff':
                return '/merchandising/products';
            default:
                return '/login';
        }
    });
    // ==========================================
    // RBAC ACTIONS
    // ==========================================
    /**
     * Load user permissions and navigation from backend
     */
    var loadPermissions = function () { return __awaiter(void 0, void 0, void 0, function () {
        var response, err_1, cachedNav, cachedPerms;
        var _a;
        return __generator(this, function (_b) {
            switch (_b.label) {
                case 0:
                    // Prevent duplicate calls
                    if (isLoadingPermissions.value) {
                        console.log('⏸️ Permissions already loading, skipping...');
                        return [2 /*return*/];
                    }
                    if (permissionsLoaded.value) {
                        console.log('✅ Permissions already loaded, skipping...');
                        return [2 /*return*/];
                    }
                    if (!token.value) {
                        console.warn('⚠️ Cannot load permissions - no token');
                        return [2 /*return*/];
                    }
                    isLoadingPermissions.value = true;
                    _b.label = 1;
                case 1:
                    _b.trys.push([1, 3, 6, 7]);
                    console.log('📥 Loading user permissions and navigation...');
                    return [4 /*yield*/, axios_1.default.get('/api/user/navigation')
                        // ✅ Direct assignment from API response
                    ];
                case 2:
                    response = _b.sent();
                    // ✅ Direct assignment from API response
                    permissions.value = response.data.permissions || [];
                    navigation.value = response.data.navigation || [];
                    permissionsLoaded.value = true;
                    // Cache in localStorage
                    localStorage.setItem('navigation', JSON.stringify(navigation.value));
                    localStorage.setItem('permissions', JSON.stringify(permissions.value));
                    console.log('✅ Permissions loaded:', permissions.value.length, 'permissions');
                    console.log('✅ Navigation loaded:', navigation.value.length, 'items');
                    return [3 /*break*/, 7];
                case 3:
                    err_1 = _b.sent();
                    console.error('❌ Failed to load permissions:', err_1);
                    cachedNav = localStorage.getItem('navigation');
                    cachedPerms = localStorage.getItem('permissions');
                    if (cachedNav && cachedPerms) {
                        console.log('📦 Loading navigation from cache...');
                        navigation.value = JSON.parse(cachedNav);
                        permissions.value = JSON.parse(cachedPerms);
                        permissionsLoaded.value = true;
                    }
                    else {
                        permissionsLoaded.value = false;
                    }
                    if (!(((_a = err_1.response) === null || _a === void 0 ? void 0 : _a.status) === 401)) return [3 /*break*/, 5];
                    return [4 /*yield*/, logout()];
                case 4:
                    _b.sent();
                    _b.label = 5;
                case 5: return [3 /*break*/, 7];
                case 6:
                    isLoadingPermissions.value = false;
                    return [7 /*endfinally*/];
                case 7: return [2 /*return*/];
            }
        });
    }); };
    /**
     * ✅ Fetch navigation (can be called separately to refresh)
     */
    var fetchNavigation = function () { return __awaiter(void 0, void 0, void 0, function () {
        var response, error_1, cached;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    if (!token.value) {
                        console.warn('⚠️ Cannot fetch navigation - no token');
                        return [2 /*return*/];
                    }
                    _a.label = 1;
                case 1:
                    _a.trys.push([1, 3, , 4]);
                    console.log('🔄 Fetching navigation...');
                    return [4 /*yield*/, axios_1.default.get('/api/user/navigation')];
                case 2:
                    response = _a.sent();
                    permissions.value = response.data.permissions || [];
                    navigation.value = response.data.navigation || [];
                    // Update cache
                    localStorage.setItem('navigation', JSON.stringify(navigation.value));
                    localStorage.setItem('permissions', JSON.stringify(permissions.value));
                    console.log('✅ Navigation refreshed:', navigation.value.length, 'items');
                    return [3 /*break*/, 4];
                case 3:
                    error_1 = _a.sent();
                    console.error('❌ Failed to fetch navigation:', error_1);
                    cached = localStorage.getItem('navigation');
                    if (cached) {
                        navigation.value = JSON.parse(cached);
                    }
                    return [3 /*break*/, 4];
                case 4: return [2 /*return*/];
            }
        });
    }); };
    /**
     * Check if user has a specific permission
     */
    var hasPermission = function (permission) {
        return permissions.value.includes(permission);
    };
    /**
     * Check if user has ANY of the permissions
     */
    var hasAnyPermission = function (perms) {
        return perms.some(function (p) { return permissions.value.includes(p); });
    };
    /**
     * Check if user has ALL permissions
     */
    var hasAllPermissions = function (perms) {
        return perms.every(function (p) { return permissions.value.includes(p); });
    };
    /**
     * Check if user has ability (legacy support)
     */
    var hasAbility = function (ability) {
        var _a, _b;
        return ((_b = (_a = user.value) === null || _a === void 0 ? void 0 : _a.abilities) === null || _b === void 0 ? void 0 : _b.includes(ability)) || false;
    };
    /**
     * Get navigation items for a specific module
     */
    var getNavigationByModule = function (module) {
        return navigation.value
            .filter(function (item) { return item.module === module && !item.parent_id && item.is_active; })
            .sort(function (a, b) { return a.display_order - b.display_order; });
    };
    /**
     * ✅ Get navigation items by module and section
     */
    var getNavigationBySection = function (module, section) {
        return navigation.value
            .filter(function (item) {
            return item.module === module &&
                item.section === section &&
                !item.parent_id &&
                item.is_active;
        })
            .sort(function (a, b) { return a.display_order - b.display_order; });
    };
    /**
     * Get child navigation items
     */
    var getChildNavigation = function (parentId) {
        return navigation.value
            .filter(function (item) { return item.parent_id === parentId && item.is_active; })
            .sort(function (a, b) { return a.display_order - b.display_order; });
    };
    /**
     * ✅ Check if navigation has specific section
     */
    var hasNavigationSection = function (module, section) {
        return navigation.value.some(function (item) {
            return item.module === module &&
                item.section === section &&
                item.is_active;
        });
    };
    // ==========================================
    // AUTH ACTIONS
    // ==========================================
    /**
     * Login user
     */
    var login = function (email, password) { return __awaiter(void 0, void 0, void 0, function () {
        var response, accessToken, userData, clockInError_1, err_2;
        var _a, _b, _c, _d;
        return __generator(this, function (_e) {
            switch (_e.label) {
                case 0:
                    loading.value = true;
                    error.value = null;
                    _e.label = 1;
                case 1:
                    _e.trys.push([1, 9, 10, 11]);
                    // Get CSRF cookie
                    return [4 /*yield*/, axios_1.default.get('/sanctum/csrf-cookie')
                        // Make login request
                    ];
                case 2:
                    // Get CSRF cookie
                    _e.sent();
                    return [4 /*yield*/, axios_1.default.post('/api/auth/login', {
                            email: email,
                            password: password,
                            device_name: 'web_browser',
                        })];
                case 3:
                    response = _e.sent();
                    accessToken = ((_a = response.data.data) === null || _a === void 0 ? void 0 : _a.access_token) || response.data.token;
                    userData = ((_b = response.data.data) === null || _b === void 0 ? void 0 : _b.user) || response.data.user;
                    token.value = accessToken;
                    user.value = userData;
                    localStorage.setItem('auth_token', accessToken);
                    localStorage.setItem('user', JSON.stringify(userData));
                    axios_1.default.defaults.headers.common['Authorization'] = "Bearer ".concat(accessToken);
                    // Load permissions ONCE
                    return [4 /*yield*/, loadPermissions()
                        // Clock in ONLY ONCE
                    ];
                case 4:
                    // Load permissions ONCE
                    _e.sent();
                    _e.label = 5;
                case 5:
                    _e.trys.push([5, 7, , 8]);
                    return [4 /*yield*/, axios_1.default.post('/api/attendances/clock-in', {
                            user_id: userData.id,
                            method: 'web'
                        })];
                case 6:
                    _e.sent();
                    console.log('✅ Clock-in successful');
                    return [3 /*break*/, 8];
                case 7:
                    clockInError_1 = _e.sent();
                    console.warn('⚠️ Clock-in failed:', clockInError_1);
                    return [3 /*break*/, 8];
                case 8: return [2 /*return*/, response];
                case 9:
                    err_2 = _e.sent();
                    console.error('❌ Login error:', err_2);
                    error.value = ((_d = (_c = err_2.response) === null || _c === void 0 ? void 0 : _c.data) === null || _d === void 0 ? void 0 : _d.message) || err_2.message || 'Login failed';
                    throw err_2;
                case 10:
                    loading.value = false;
                    return [7 /*endfinally*/];
                case 11: return [2 /*return*/];
            }
        });
    }); };
    /**
     * Logout user
     */
    var logout = function () { return __awaiter(void 0, void 0, void 0, function () {
        var err_3;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    _a.trys.push([0, 3, 4, 5]);
                    if (!(token.value && user.value)) return [3 /*break*/, 2];
                    return [4 /*yield*/, axios_1.default.post('/api/auth/logout-with-clock-out', {
                            user_id: user.value.id,
                            method: 'web'
                        })];
                case 1:
                    _a.sent();
                    _a.label = 2;
                case 2: return [3 /*break*/, 5];
                case 3:
                    err_3 = _a.sent();
                    console.warn('⚠️ Logout API error:', err_3);
                    return [3 /*break*/, 5];
                case 4:
                    // Clear everything
                    token.value = null;
                    user.value = null;
                    permissions.value = [];
                    navigation.value = [];
                    permissionsLoaded.value = false;
                    isLoadingPermissions.value = false;
                    localStorage.removeItem('auth_token');
                    localStorage.removeItem('user');
                    localStorage.removeItem('navigation');
                    localStorage.removeItem('permissions');
                    delete axios_1.default.defaults.headers.common['Authorization'];
                    // Clear all cookies
                    document.cookie.split(";").forEach(function (c) {
                        document.cookie = c.replace(/^ +/, "")
                            .replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/");
                    });
                    router_1.default.push({ name: 'Login' });
                    return [7 /*endfinally*/];
                case 5: return [2 /*return*/];
            }
        });
    }); };
    /**
     * Fetch current user data
     */
    var fetchCurrentUser = function () { return __awaiter(void 0, void 0, void 0, function () {
        var response, err_4;
        var _a;
        return __generator(this, function (_b) {
            switch (_b.label) {
                case 0:
                    if (!token.value)
                        return [2 /*return*/];
                    _b.label = 1;
                case 1:
                    _b.trys.push([1, 4, , 7]);
                    return [4 /*yield*/, axios_1.default.get('/api/auth/user')];
                case 2:
                    response = _b.sent();
                    user.value = response.data;
                    localStorage.setItem('user', JSON.stringify(response.data));
                    // Reload permissions when user data is refreshed
                    permissionsLoaded.value = false;
                    isLoadingPermissions.value = false;
                    return [4 /*yield*/, loadPermissions()];
                case 3:
                    _b.sent();
                    return [2 /*return*/, response.data];
                case 4:
                    err_4 = _b.sent();
                    if (!(((_a = err_4.response) === null || _a === void 0 ? void 0 : _a.status) === 401)) return [3 /*break*/, 6];
                    return [4 /*yield*/, logout()];
                case 5:
                    _b.sent();
                    _b.label = 6;
                case 6: throw err_4;
                case 7: return [2 /*return*/];
            }
        });
    }); };
    /**
     * Initialize auth on app load
     */
    var initialize = function () { return __awaiter(void 0, void 0, void 0, function () {
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    if (!(token.value && user.value && !permissionsLoaded.value && !isLoadingPermissions.value)) return [3 /*break*/, 2];
                    console.log('🔄 Initializing auth store...');
                    axios_1.default.defaults.headers.common['Authorization'] = "Bearer ".concat(token.value);
                    return [4 /*yield*/, loadPermissions()];
                case 1:
                    _a.sent();
                    _a.label = 2;
                case 2: return [2 /*return*/];
            }
        });
    }); };
    // ==========================================
    // RETURN PUBLIC API
    // ==========================================
    return {
        // State
        token: token,
        user: user,
        loading: loading,
        error: error,
        permissions: permissions,
        navigation: navigation,
        permissionsLoaded: permissionsLoaded,
        isLoadingPermissions: isLoadingPermissions,
        // Getters
        isAuthenticated: isAuthenticated,
        currentUser: currentUser,
        userRole: userRole,
        userAbilities: userAbilities,
        defaultRoute: defaultRoute,
        // Auth Actions
        login: login,
        logout: logout,
        fetchCurrentUser: fetchCurrentUser,
        initialize: initialize,
        // Permission Actions
        loadPermissions: loadPermissions,
        fetchNavigation: fetchNavigation,
        hasPermission: hasPermission,
        hasAnyPermission: hasAnyPermission,
        hasAllPermissions: hasAllPermissions,
        hasAbility: hasAbility,
        // Navigation Helpers
        getNavigationByModule: getNavigationByModule,
        getNavigationBySection: getNavigationBySection,
        getChildNavigation: getChildNavigation,
        hasNavigationSection: hasNavigationSection,
    };
});
