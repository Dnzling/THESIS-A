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
    // State
    var user = (0, vue_1.ref)(null);
    var token = (0, vue_1.ref)(null);
    var loading = (0, vue_1.ref)(false);
    var error = (0, vue_1.ref)(null);
    var authenticated = (0, vue_1.ref)(false);
    var isAuthResolved = (0, vue_1.ref)(false);
    // Getters / Computed
    var isAuthenticated = (0, vue_1.computed)(function () { return authenticated.value; });
    var currentUser = (0, vue_1.computed)(function () { return user.value; });
    // Role-based getters
    var isAdmin = (0, vue_1.computed)(function () {
        var _a, _b;
        return ((_a = user.value) === null || _a === void 0 ? void 0 : _a.role) === 'admin' || ((_b = user.value) === null || _b === void 0 ? void 0 : _b.role) === 'administrator';
    });
    var isEmailVerified = (0, vue_1.computed)(function () {
        var _a;
        return !!((_a = user.value) === null || _a === void 0 ? void 0 : _a.email_verified_at);
    });
    var userName = (0, vue_1.computed)(function () {
        var _a;
        return ((_a = user.value) === null || _a === void 0 ? void 0 : _a.name) || '';
    });
    var userEmail = (0, vue_1.computed)(function () {
        var _a;
        return ((_a = user.value) === null || _a === void 0 ? void 0 : _a.email) || '';
    });
    var userInitials = (0, vue_1.computed)(function () {
        var _a;
        if (!((_a = user.value) === null || _a === void 0 ? void 0 : _a.name))
            return '';
        var names = user.value.name.split(' ');
        if (names.length >= 2) {
            return "".concat(names[0][0]).concat(names[names.length - 1][0]).toUpperCase();
        }
        return user.value.name.substring(0, 2).toUpperCase();
    });
    // Actions / Methods
    var setAuthenticated = function (value) {
        authenticated.value = value;
    };
    var setUser = function (userData) {
        user.value = userData;
    };
    var setToken = function (newToken) {
        token.value = newToken;
        localStorage.setItem('auth_token', newToken);
        axios_1.default.defaults.headers.common['Authorization'] = "Bearer ".concat(newToken);
    };
    var clearToken = function () {
        token.value = null;
        localStorage.removeItem('auth_token');
        delete axios_1.default.defaults.headers.common['Authorization'];
    };
    var clearError = function () {
        error.value = null;
    };
    var login = function (credentials) { return __awaiter(void 0, void 0, void 0, function () {
        var response, redirectPath, err_1;
        var _a, _b, _c, _d, _e, _f;
        return __generator(this, function (_g) {
            switch (_g.label) {
                case 0:
                    loading.value = true;
                    error.value = null;
                    _g.label = 1;
                case 1:
                    _g.trys.push([1, 5, 6, 7]);
                    // Get CSRF cookie first (required for Laravel Sanctum)
                    return [4 /*yield*/, axios_1.default.get('/sanctum/csrf-cookie')];
                case 2:
                    // Get CSRF cookie first (required for Laravel Sanctum)
                    _g.sent();
                    return [4 /*yield*/, axios_1.default.post('/auth/login', credentials)];
                case 3:
                    response = _g.sent();
                    // Handle token if returned from API
                    if (response.data.token) {
                        setToken(response.data.token);
                    }
                    else if (response.data.access_token) {
                        setToken(response.data.access_token);
                    }
                    // Get user data
                    return [4 /*yield*/, attempt()];
                case 4:
                    // Get user data
                    _g.sent();
                    redirectPath = ((_a = router_1.default.currentRoute.value.query.redirect) === null || _a === void 0 ? void 0 : _a.toString()) || '/dashboard';
                    router_1.default.push(redirectPath);
                    return [2 /*return*/, { success: true, data: response.data }];
                case 5:
                    err_1 = _g.sent();
                    console.error('Login Failed', err_1);
                    // Handle different error formats
                    if ((_c = (_b = err_1.response) === null || _b === void 0 ? void 0 : _b.data) === null || _c === void 0 ? void 0 : _c.errors) {
                        // Validation errors
                        error.value = Object.values(err_1.response.data.errors).flat().join(', ');
                    }
                    else if ((_e = (_d = err_1.response) === null || _d === void 0 ? void 0 : _d.data) === null || _e === void 0 ? void 0 : _e.message) {
                        error.value = err_1.response.data.message;
                    }
                    else {
                        error.value = 'Login failed. Please check your credentials and try again.';
                    }
                    return [2 /*return*/, {
                            success: false,
                            error: error.value,
                            status: (_f = err_1.response) === null || _f === void 0 ? void 0 : _f.status
                        }];
                case 6:
                    loading.value = false;
                    return [7 /*endfinally*/];
                case 7: return [2 /*return*/];
            }
        });
    }); };
    var register = function (credentials) { return __awaiter(void 0, void 0, void 0, function () {
        var response, err_2;
        var _a, _b, _c, _d, _e;
        return __generator(this, function (_f) {
            switch (_f.label) {
                case 0:
                    loading.value = true;
                    error.value = null;
                    _f.label = 1;
                case 1:
                    _f.trys.push([1, 5, 6, 7]);
                    // Get CSRF cookie first
                    return [4 /*yield*/, axios_1.default.get('/sanctum/csrf-cookie')];
                case 2:
                    // Get CSRF cookie first
                    _f.sent();
                    return [4 /*yield*/, axios_1.default.post('/auth/register', credentials)];
                case 3:
                    response = _f.sent();
                    // Handle token if returned from API
                    if (response.data.token) {
                        setToken(response.data.token);
                    }
                    else if (response.data.access_token) {
                        setToken(response.data.access_token);
                    }
                    // Get user data
                    return [4 /*yield*/, attempt()];
                case 4:
                    // Get user data
                    _f.sent();
                    // Redirect to dashboard or email verification page
                    router_1.default.push('/dashboard');
                    return [2 /*return*/, { success: true, data: response.data }];
                case 5:
                    err_2 = _f.sent();
                    console.error('Registration Failed', err_2);
                    // Handle different error formats
                    if ((_b = (_a = err_2.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.errors) {
                        // Validation errors
                        error.value = Object.values(err_2.response.data.errors).flat().join(', ');
                    }
                    else if ((_d = (_c = err_2.response) === null || _c === void 0 ? void 0 : _c.data) === null || _d === void 0 ? void 0 : _d.message) {
                        error.value = err_2.response.data.message;
                    }
                    else {
                        error.value = 'Registration failed. Please try again.';
                    }
                    return [2 /*return*/, {
                            success: false,
                            error: error.value,
                            status: (_e = err_2.response) === null || _e === void 0 ? void 0 : _e.status
                        }];
                case 6:
                    loading.value = false;
                    return [7 /*endfinally*/];
                case 7: return [2 /*return*/];
            }
        });
    }); };
    var logout = function () { return __awaiter(void 0, void 0, void 0, function () {
        var err_3;
        var _a, _b;
        return __generator(this, function (_c) {
            switch (_c.label) {
                case 0:
                    loading.value = true;
                    _c.label = 1;
                case 1:
                    _c.trys.push([1, 4, 5, 6]);
                    if (!token.value) return [3 /*break*/, 3];
                    return [4 /*yield*/, axios_1.default.post('/auth/logout')];
                case 2:
                    _c.sent();
                    _c.label = 3;
                case 3:
                    // Clear all auth state
                    user.value = null;
                    authenticated.value = false;
                    clearToken();
                    // Redirect to login page
                    router_1.default.push('/login');
                    return [2 /*return*/, { success: true }];
                case 4:
                    err_3 = _c.sent();
                    console.error('Logout failed:', err_3);
                    // Still clear local state even if API fails
                    user.value = null;
                    authenticated.value = false;
                    clearToken();
                    // Still redirect to login
                    router_1.default.push('/login');
                    return [2 /*return*/, {
                            success: false,
                            error: ((_b = (_a = err_3.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Logout failed'
                        }];
                case 5:
                    loading.value = false;
                    return [7 /*endfinally*/];
                case 6: return [2 /*return*/];
            }
        });
    }); };
    var attempt = function () { return __awaiter(void 0, void 0, void 0, function () {
        var response, error_1;
        var _a;
        return __generator(this, function (_b) {
            switch (_b.label) {
                case 0:
                    _b.trys.push([0, 2, 3, 4]);
                    loading.value = true;
                    // If we have a token, ensure it's in the headers
                    if (token.value) {
                        axios_1.default.defaults.headers.common['Authorization'] = "Bearer ".concat(token.value);
                    }
                    return [4 /*yield*/, axios_1.default.get('/api/auth/user')];
                case 1:
                    response = _b.sent();
                    setUser(response.data);
                    setAuthenticated(true);
                    return [2 /*return*/, response.data];
                case 2:
                    error_1 = _b.sent();
                    console.log("Auth attempt error: ", error_1);
                    setUser(null);
                    setAuthenticated(false);
                    clearToken(); // Clear token on failure
                    // Don't throw error for 401 as it's expected for unauthenticated users
                    if (((_a = error_1.response) === null || _a === void 0 ? void 0 : _a.status) !== 401) {
                        throw error_1;
                    }
                    return [2 /*return*/, null];
                case 3:
                    loading.value = false;
                    isAuthResolved.value = true;
                    return [7 /*endfinally*/];
                case 4: return [2 /*return*/];
            }
        });
    }); };
    var initialize = function () { return __awaiter(void 0, void 0, void 0, function () {
        var savedToken, error_2;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    savedToken = localStorage.getItem('auth_token');
                    if (!savedToken) return [3 /*break*/, 5];
                    setToken(savedToken);
                    _a.label = 1;
                case 1:
                    _a.trys.push([1, 3, , 4]);
                    return [4 /*yield*/, attempt()];
                case 2:
                    _a.sent();
                    return [3 /*break*/, 4];
                case 3:
                    error_2 = _a.sent();
                    console.error('Failed to restore session:', error_2);
                    clearToken();
                    setUser(null);
                    setAuthenticated(false);
                    return [3 /*break*/, 4];
                case 4: return [3 /*break*/, 6];
                case 5:
                    isAuthResolved.value = true;
                    _a.label = 6;
                case 6: return [2 /*return*/];
            }
        });
    }); };
    var refreshUser = function () { return __awaiter(void 0, void 0, void 0, function () {
        var response, error_3;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    if (!authenticated.value)
                        return [2 /*return*/, null];
                    _a.label = 1;
                case 1:
                    _a.trys.push([1, 3, , 4]);
                    return [4 /*yield*/, axios_1.default.get('/api/auth/user')];
                case 2:
                    response = _a.sent();
                    setUser(response.data);
                    return [2 /*return*/, response.data];
                case 3:
                    error_3 = _a.sent();
                    console.error('Failed to refresh user data:', error_3);
                    return [2 /*return*/, null];
                case 4: return [2 /*return*/];
            }
        });
    }); };
    var updateUser = function (userData) { return __awaiter(void 0, void 0, void 0, function () {
        var response, err_4;
        var _a, _b, _c, _d;
        return __generator(this, function (_e) {
            switch (_e.label) {
                case 0:
                    loading.value = true;
                    error.value = null;
                    _e.label = 1;
                case 1:
                    _e.trys.push([1, 3, 4, 5]);
                    return [4 /*yield*/, axios_1.default.put('/api/auth/user', userData)];
                case 2:
                    response = _e.sent();
                    setUser(response.data);
                    return [2 /*return*/, { success: true, data: response.data }];
                case 3:
                    err_4 = _e.sent();
                    console.error('Failed to update user:', err_4);
                    if ((_b = (_a = err_4.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.errors) {
                        error.value = Object.values(err_4.response.data.errors).flat().join(', ');
                    }
                    else if ((_d = (_c = err_4.response) === null || _c === void 0 ? void 0 : _c.data) === null || _d === void 0 ? void 0 : _d.message) {
                        error.value = err_4.response.data.message;
                    }
                    else {
                        error.value = 'Failed to update user information.';
                    }
                    return [2 /*return*/, { success: false, error: error.value }];
                case 4:
                    loading.value = false;
                    return [7 /*endfinally*/];
                case 5: return [2 /*return*/];
            }
        });
    }); };
    var verifyEmail = function (id, hash) { return __awaiter(void 0, void 0, void 0, function () {
        var response, err_5;
        var _a, _b;
        return __generator(this, function (_c) {
            switch (_c.label) {
                case 0:
                    loading.value = true;
                    error.value = null;
                    _c.label = 1;
                case 1:
                    _c.trys.push([1, 4, 5, 6]);
                    return [4 /*yield*/, axios_1.default.get("/email/verify/".concat(id, "/").concat(hash))];
                case 2:
                    response = _c.sent();
                    // Refresh user data after verification
                    return [4 /*yield*/, refreshUser()];
                case 3:
                    // Refresh user data after verification
                    _c.sent();
                    return [2 /*return*/, { success: true, data: response.data }];
                case 4:
                    err_5 = _c.sent();
                    console.error('Email verification failed:', err_5);
                    error.value = ((_b = (_a = err_5.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Email verification failed.';
                    return [2 /*return*/, { success: false, error: error.value }];
                case 5:
                    loading.value = false;
                    return [7 /*endfinally*/];
                case 6: return [2 /*return*/];
            }
        });
    }); };
    var resendVerificationEmail = function () { return __awaiter(void 0, void 0, void 0, function () {
        var response, err_6;
        var _a, _b;
        return __generator(this, function (_c) {
            switch (_c.label) {
                case 0:
                    loading.value = true;
                    error.value = null;
                    _c.label = 1;
                case 1:
                    _c.trys.push([1, 3, 4, 5]);
                    return [4 /*yield*/, axios_1.default.post('/email/verification-notification')];
                case 2:
                    response = _c.sent();
                    return [2 /*return*/, { success: true, data: response.data }];
                case 3:
                    err_6 = _c.sent();
                    console.error('Failed to resend verification email:', err_6);
                    error.value = ((_b = (_a = err_6.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to resend verification email.';
                    return [2 /*return*/, { success: false, error: error.value }];
                case 4:
                    loading.value = false;
                    return [7 /*endfinally*/];
                case 5: return [2 /*return*/];
            }
        });
    }); };
    var forgotPassword = function (email) { return __awaiter(void 0, void 0, void 0, function () {
        var response, err_7;
        var _a, _b;
        return __generator(this, function (_c) {
            switch (_c.label) {
                case 0:
                    loading.value = true;
                    error.value = null;
                    _c.label = 1;
                case 1:
                    _c.trys.push([1, 4, 5, 6]);
                    return [4 /*yield*/, axios_1.default.get('/sanctum/csrf-cookie')];
                case 2:
                    _c.sent();
                    return [4 /*yield*/, axios_1.default.post('/forgot-password', { email: email })];
                case 3:
                    response = _c.sent();
                    return [2 /*return*/, { success: true, data: response.data }];
                case 4:
                    err_7 = _c.sent();
                    console.error('Forgot password request failed:', err_7);
                    error.value = ((_b = (_a = err_7.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to send password reset email.';
                    return [2 /*return*/, { success: false, error: error.value }];
                case 5:
                    loading.value = false;
                    return [7 /*endfinally*/];
                case 6: return [2 /*return*/];
            }
        });
    }); };
    var resetPassword = function (data) { return __awaiter(void 0, void 0, void 0, function () {
        var response, err_8;
        var _a, _b, _c, _d;
        return __generator(this, function (_e) {
            switch (_e.label) {
                case 0:
                    loading.value = true;
                    error.value = null;
                    _e.label = 1;
                case 1:
                    _e.trys.push([1, 7, 8, 9]);
                    return [4 /*yield*/, axios_1.default.get('/sanctum/csrf-cookie')];
                case 2:
                    _e.sent();
                    return [4 /*yield*/, axios_1.default.post('/reset-password', data)];
                case 3:
                    response = _e.sent();
                    if (!response.data.token) return [3 /*break*/, 5];
                    setToken(response.data.token);
                    return [4 /*yield*/, attempt()];
                case 4:
                    _e.sent();
                    router_1.default.push('/dashboard');
                    return [3 /*break*/, 6];
                case 5:
                    router_1.default.push('/login');
                    _e.label = 6;
                case 6: return [2 /*return*/, { success: true, data: response.data }];
                case 7:
                    err_8 = _e.sent();
                    console.error('Password reset failed:', err_8);
                    if ((_b = (_a = err_8.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.errors) {
                        error.value = Object.values(err_8.response.data.errors).flat().join(', ');
                    }
                    else if ((_d = (_c = err_8.response) === null || _c === void 0 ? void 0 : _c.data) === null || _d === void 0 ? void 0 : _d.message) {
                        error.value = err_8.response.data.message;
                    }
                    else {
                        error.value = 'Failed to reset password.';
                    }
                    return [2 /*return*/, { success: false, error: error.value }];
                case 8:
                    loading.value = false;
                    return [7 /*endfinally*/];
                case 9: return [2 /*return*/];
            }
        });
    }); };
    // Return all state, getters, and actions
    return {
        // State
        user: user,
        token: token,
        loading: loading,
        error: error,
        authenticated: authenticated,
        isAuthResolved: isAuthResolved,
        // Getters
        currentUser: currentUser,
        isAuthenticated: isAuthenticated,
        isAdmin: isAdmin,
        isEmailVerified: isEmailVerified,
        userName: userName,
        userEmail: userEmail,
        userInitials: userInitials,
        // Actions
        login: login,
        register: register,
        logout: logout,
        attempt: attempt,
        initialize: initialize,
        refreshUser: refreshUser,
        updateUser: updateUser,
        verifyEmail: verifyEmail,
        resendVerificationEmail: resendVerificationEmail,
        forgotPassword: forgotPassword,
        resetPassword: resetPassword,
        clearError: clearError,
        // Utility
        setAuthenticated: setAuthenticated,
        setUser: setUser
    };
});
