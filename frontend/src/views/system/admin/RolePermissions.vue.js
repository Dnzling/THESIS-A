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
var usetoast_1 = require("primevue/usetoast");
var axios_1 = require("axios");
var card_1 = require("primevue/card");
var button_1 = require("primevue/button");
var avatar_1 = require("primevue/avatar");
var tag_1 = require("primevue/tag");
var datatable_1 = require("primevue/datatable");
var column_1 = require("primevue/column");
var dialog_1 = require("primevue/dialog");
var inputtext_1 = require("primevue/inputtext");
var inputnumber_1 = require("primevue/inputnumber");
var textarea_1 = require("primevue/textarea");
var select_1 = require("primevue/select");
var multiselect_1 = require("primevue/multiselect");
var checkbox_1 = require("primevue/checkbox");
var menu_1 = require("primevue/menu");
var tabs_1 = require("primevue/tabs");
var tablist_1 = require("primevue/tablist");
var tab_1 = require("primevue/tab");
var tabpanels_1 = require("primevue/tabpanels");
var tabpanel_1 = require("primevue/tabpanel");
var toast = (0, usetoast_1.useToast)();
// State
var roles = (0, vue_1.ref)([]);
var allPermissions = (0, vue_1.ref)([]);
var navigationItems = (0, vue_1.ref)([]);
var loadingPermissions = (0, vue_1.ref)(false);
var loadingNavigation = (0, vue_1.ref)(false);
var selectedModule = (0, vue_1.ref)(null);
var selectedRole = (0, vue_1.ref)(null);
var selectedRolePermissions = (0, vue_1.ref)([]);
var expandedModules = (0, vue_1.ref)([]);
// Dialogs
var permissionsDialog = (0, vue_1.ref)(false);
var permissionDialog = (0, vue_1.ref)(false);
var navigationDialog = (0, vue_1.ref)(false);
var deletePermissionDialog = (0, vue_1.ref)(false);
var deleteNavigationDialog = (0, vue_1.ref)(false);
var savingPermissions = (0, vue_1.ref)(false);
var savingPermission = (0, vue_1.ref)(false);
var savingNavigation = (0, vue_1.ref)(false);
// Forms
var editingPermission = (0, vue_1.ref)(null);
var permissionToDelete = (0, vue_1.ref)(null);
var permissionForm = (0, vue_1.ref)({
    name: '',
    display_name: '',
    module: null,
    description: '',
    is_active: true
});
var permissionErrors = (0, vue_1.ref)({});
var editingNavigation = (0, vue_1.ref)(null);
var navigationToDelete = (0, vue_1.ref)(null);
var navigationForm = (0, vue_1.ref)({
    name: '',
    display_name: '',
    module: null,
    route_name: '',
    route_path: '',
    icon: '',
    parent_id: null,
    display_order: 0,
    permissions: [],
    is_active: true
});
// Role Menu
var roleMenu = (0, vue_1.ref)();
var roleMenuItems = (0, vue_1.ref)([
    {
        label: 'Edit',
        icon: 'pi pi-pencil',
        command: function () { return console.log('Edit role'); }
    },
    {
        label: 'Manage Permissions',
        icon: 'pi pi-cog',
        command: function () { return openPermissionsDialog(selectedRole.value); }
    },
    {
        separator: true
    },
    {
        label: 'Delete',
        icon: 'pi pi-trash',
        class: 'text-red-500',
        command: function () { return console.log('Delete role'); }
    }
]);
// Modules
var modules = (0, vue_1.ref)([
    { label: 'Admin', value: 'admin' },
    { label: 'HR', value: 'hr' },
    { label: 'Merchandising', value: 'merchandising' },
    { label: 'Inventory', value: 'inventory' },
    { label: 'Sales', value: 'sales' },
    { label: 'Accounting', value: 'accounting' }
]);
// Computed
var filteredPermissions = (0, vue_1.computed)(function () {
    if (!selectedModule.value)
        return allPermissions.value;
    return allPermissions.value.filter(function (p) { return p.module === selectedModule.value; });
});
var permissionsByModule = (0, vue_1.computed)(function () {
    var grouped = allPermissions.value.reduce(function (acc, permission) {
        if (!acc[permission.module]) {
            acc[permission.module] = {
                name: permission.module,
                display_name: permission.module.charAt(0).toUpperCase() + permission.module.slice(1),
                permissions: [],
                total: 0,
                selected: 0
            };
        }
        acc[permission.module].permissions.push(permission);
        acc[permission.module].total++;
        if (selectedRolePermissions.value.includes(permission.id)) {
            acc[permission.module].selected++;
        }
        return acc;
    }, {});
    return Object.values(grouped);
});
var parentNavigationOptions = (0, vue_1.computed)(function () {
    return navigationItems.value.filter(function (nav) { return !nav.parent_id; });
});
// Methods
var loadRoles = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, error_1;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                _a.trys.push([0, 2, , 3]);
                return [4 /*yield*/, axios_1.default.get('/api/admin/roles')];
            case 1:
                response = _a.sent();
                roles.value = response.data.data || response.data;
                return [3 /*break*/, 3];
            case 2:
                error_1 = _a.sent();
                toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load roles', life: 3000 });
                return [3 /*break*/, 3];
            case 3: return [2 /*return*/];
        }
    });
}); };
var loadPermissions = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, error_2;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                loadingPermissions.value = true;
                _a.label = 1;
            case 1:
                _a.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, axios_1.default.get('/api/admin/permissions')];
            case 2:
                response = _a.sent();
                allPermissions.value = response.data.data || response.data;
                return [3 /*break*/, 5];
            case 3:
                error_2 = _a.sent();
                toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load permissions', life: 3000 });
                return [3 /*break*/, 5];
            case 4:
                loadingPermissions.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var loadNavigationItems = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, error_3;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                loadingNavigation.value = true;
                _a.label = 1;
            case 1:
                _a.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, axios_1.default.get('/api/admin/navigation-items')];
            case 2:
                response = _a.sent();
                navigationItems.value = response.data.data || response.data;
                return [3 /*break*/, 5];
            case 3:
                error_3 = _a.sent();
                toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load navigation', life: 3000 });
                return [3 /*break*/, 5];
            case 4:
                loadingNavigation.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var openPermissionsDialog = function (role) { return __awaiter(void 0, void 0, void 0, function () {
    var response, error_4;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                selectedRole.value = role;
                _a.label = 1;
            case 1:
                _a.trys.push([1, 3, , 4]);
                return [4 /*yield*/, axios_1.default.get("/api/admin/roles/".concat(role.id, "/permissions"))];
            case 2:
                response = _a.sent();
                selectedRolePermissions.value = response.data.permissions.map(function (p) { return p.id; });
                return [3 /*break*/, 4];
            case 3:
                error_4 = _a.sent();
                toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load role permissions', life: 3000 });
                return [3 /*break*/, 4];
            case 4:
                permissionsDialog.value = true;
                return [2 /*return*/];
        }
    });
}); };
var saveRolePermissions = function () { return __awaiter(void 0, void 0, void 0, function () {
    var error_5;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                savingPermissions.value = true;
                _c.label = 1;
            case 1:
                _c.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, axios_1.default.post("/api/admin/roles/".concat(selectedRole.value.id, "/permissions"), {
                        permissions: selectedRolePermissions.value
                    })];
            case 2:
                _c.sent();
                toast.add({ severity: 'success', summary: 'Success', detail: 'Permissions updated successfully', life: 3000 });
                permissionsDialog.value = false;
                loadRoles();
                return [3 /*break*/, 5];
            case 3:
                error_5 = _c.sent();
                toast.add({ severity: 'error', summary: 'Error', detail: ((_b = (_a = error_5.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to update permissions', life: 3000 });
                return [3 /*break*/, 5];
            case 4:
                savingPermissions.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var toggleModule = function (moduleName) {
    var index = expandedModules.value.indexOf(moduleName);
    if (index > -1) {
        expandedModules.value.splice(index, 1);
    }
    else {
        expandedModules.value.push(moduleName);
    }
};
var toggleModulePermissions = function (moduleName, checked) {
    var module = permissionsByModule.value.find(function (m) { return m.name === moduleName; });
    if (!module)
        return;
    var permissionIds = module.permissions.map(function (p) { return p.id; });
    if (checked) {
        // Add all module permissions
        selectedRolePermissions.value = __spreadArray([], new Set(__spreadArray(__spreadArray([], selectedRolePermissions.value, true), permissionIds, true)), true);
    }
    else {
        // Remove all module permissions
        selectedRolePermissions.value = selectedRolePermissions.value.filter(function (id) { return !permissionIds.includes(id); });
    }
};
var openCreatePermissionDialog = function () {
    editingPermission.value = null;
    permissionForm.value = {
        name: '',
        display_name: '',
        module: null,
        description: '',
        is_active: true
    };
    permissionErrors.value = {};
    permissionDialog.value = true;
};
var editPermission = function (permission) {
    editingPermission.value = permission;
    permissionForm.value = __assign({}, permission);
    permissionErrors.value = {};
    permissionDialog.value = true;
};
var savePermission = function () { return __awaiter(void 0, void 0, void 0, function () {
    var error_6;
    var _a, _b, _c;
    return __generator(this, function (_d) {
        switch (_d.label) {
            case 0:
                permissionErrors.value = {};
                savingPermission.value = true;
                _d.label = 1;
            case 1:
                _d.trys.push([1, 6, 7, 8]);
                if (!editingPermission.value) return [3 /*break*/, 3];
                return [4 /*yield*/, axios_1.default.put("/api/admin/permissions/".concat(editingPermission.value.id), permissionForm.value)];
            case 2:
                _d.sent();
                toast.add({ severity: 'success', summary: 'Success', detail: 'Permission updated successfully', life: 3000 });
                return [3 /*break*/, 5];
            case 3: return [4 /*yield*/, axios_1.default.post('/api/admin/permissions', permissionForm.value)];
            case 4:
                _d.sent();
                toast.add({ severity: 'success', summary: 'Success', detail: 'Permission created successfully', life: 3000 });
                _d.label = 5;
            case 5:
                permissionDialog.value = false;
                loadPermissions();
                return [3 /*break*/, 8];
            case 6:
                error_6 = _d.sent();
                if (((_a = error_6.response) === null || _a === void 0 ? void 0 : _a.status) === 422) {
                    permissionErrors.value = error_6.response.data.errors || {};
                }
                toast.add({ severity: 'error', summary: 'Error', detail: ((_c = (_b = error_6.response) === null || _b === void 0 ? void 0 : _b.data) === null || _c === void 0 ? void 0 : _c.message) || 'Failed to save permission', life: 3000 });
                return [3 /*break*/, 8];
            case 7:
                savingPermission.value = false;
                return [7 /*endfinally*/];
            case 8: return [2 /*return*/];
        }
    });
}); };
var confirmDeletePermission = function (permission) {
    permissionToDelete.value = permission;
    deletePermissionDialog.value = true;
};
var deletePermission = function () { return __awaiter(void 0, void 0, void 0, function () {
    var error_7;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                _c.trys.push([0, 2, , 3]);
                return [4 /*yield*/, axios_1.default.delete("/api/admin/permissions/".concat(permissionToDelete.value.id))];
            case 1:
                _c.sent();
                toast.add({ severity: 'success', summary: 'Success', detail: 'Permission deleted successfully', life: 3000 });
                deletePermissionDialog.value = false;
                loadPermissions();
                return [3 /*break*/, 3];
            case 2:
                error_7 = _c.sent();
                toast.add({ severity: 'error', summary: 'Error', detail: ((_b = (_a = error_7.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to delete permission', life: 3000 });
                return [3 /*break*/, 3];
            case 3: return [2 /*return*/];
        }
    });
}); };
var openCreateNavigationDialog = function () {
    editingNavigation.value = null;
    navigationForm.value = {
        name: '',
        display_name: '',
        module: null,
        route_name: '',
        route_path: '',
        icon: '',
        parent_id: null,
        display_order: 0,
        permissions: [],
        is_active: true
    };
    navigationDialog.value = true;
};
var editNavigation = function (navigation) {
    var _a;
    editingNavigation.value = navigation;
    navigationForm.value = __assign(__assign({}, navigation), { permissions: ((_a = navigation.permissions) === null || _a === void 0 ? void 0 : _a.map(function (p) { return p.id; })) || [] });
    navigationDialog.value = true;
};
var saveNavigation = function () { return __awaiter(void 0, void 0, void 0, function () {
    var error_8;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                savingNavigation.value = true;
                _c.label = 1;
            case 1:
                _c.trys.push([1, 6, 7, 8]);
                if (!editingNavigation.value) return [3 /*break*/, 3];
                return [4 /*yield*/, axios_1.default.put("/api/admin/navigation-items/".concat(editingNavigation.value.id), navigationForm.value)];
            case 2:
                _c.sent();
                toast.add({ severity: 'success', summary: 'Success', detail: 'Navigation updated successfully', life: 3000 });
                return [3 /*break*/, 5];
            case 3: return [4 /*yield*/, axios_1.default.post('/api/admin/navigation-items', navigationForm.value)];
            case 4:
                _c.sent();
                toast.add({ severity: 'success', summary: 'Success', detail: 'Navigation created successfully', life: 3000 });
                _c.label = 5;
            case 5:
                navigationDialog.value = false;
                loadNavigationItems();
                return [3 /*break*/, 8];
            case 6:
                error_8 = _c.sent();
                toast.add({ severity: 'error', summary: 'Error', detail: ((_b = (_a = error_8.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to save navigation', life: 3000 });
                return [3 /*break*/, 8];
            case 7:
                savingNavigation.value = false;
                return [7 /*endfinally*/];
            case 8: return [2 /*return*/];
        }
    });
}); };
var confirmDeleteNavigation = function (navigation) {
    navigationToDelete.value = navigation;
    deleteNavigationDialog.value = true;
};
var deleteNavigation = function () { return __awaiter(void 0, void 0, void 0, function () {
    var error_9;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                _c.trys.push([0, 2, , 3]);
                return [4 /*yield*/, axios_1.default.delete("/api/admin/navigation-items/".concat(navigationToDelete.value.id))];
            case 1:
                _c.sent();
                toast.add({ severity: 'success', summary: 'Success', detail: 'Navigation deleted successfully', life: 3000 });
                deleteNavigationDialog.value = false;
                loadNavigationItems();
                return [3 /*break*/, 3];
            case 2:
                error_9 = _c.sent();
                toast.add({ severity: 'error', summary: 'Error', detail: ((_b = (_a = error_9.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to delete navigation', life: 3000 });
                return [3 /*break*/, 3];
            case 3: return [2 /*return*/];
        }
    });
}); };
var toggleRoleMenu = function (event, role) {
    selectedRole.value = role;
    roleMenu.value.toggle(event);
};
var openCreateRoleDialog = function () {
    // TODO: Implement create role dialog
    toast.add({ severity: 'info', summary: 'Info', detail: 'Create role feature coming soon', life: 3000 });
};
var getModuleSeverity = function (module) {
    var severityMap = {
        admin: 'danger',
        hr: 'info',
        merchandising: 'success',
        inventory: 'warning',
        sales: 'primary',
        accounting: 'secondary'
    };
    return severityMap[module] || 'info';
};
(0, vue_1.onMounted)(function () {
    loadRoles();
    loadPermissions();
    loadNavigationItems();
});
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-6" }));
/** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.h2, __VLS_intrinsics.h2)(__assign({ class: "text-2xl font-bold text-gray-800" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
var __VLS_0;
/** @ts-ignore @type {typeof __VLS_components.Button} */
button_1.default;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ 'onClick': {} }, { label: "Create Role", icon: "pi pi-plus" })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Create Role", icon: "pi pi-plus" })], __VLS_functionalComponentArgsRest(__VLS_1), false));
var __VLS_5;
var __VLS_6 = ({ click: {} },
    { onClick: (__VLS_ctx.openCreateRoleDialog) });
var __VLS_3;
var __VLS_4;
var __VLS_7;
/** @ts-ignore @type {typeof __VLS_components.Tabs | typeof __VLS_components.Tabs} */
tabs_1.default;
// @ts-ignore
var __VLS_8 = __VLS_asFunctionalComponent1(__VLS_7, new __VLS_7({
    value: "0",
}));
var __VLS_9 = __VLS_8.apply(void 0, __spreadArray([{
        value: "0",
    }], __VLS_functionalComponentArgsRest(__VLS_8), false));
var __VLS_12 = __VLS_10.slots.default;
var __VLS_13;
/** @ts-ignore @type {typeof __VLS_components.TabList | typeof __VLS_components.TabList} */
tablist_1.default;
// @ts-ignore
var __VLS_14 = __VLS_asFunctionalComponent1(__VLS_13, new __VLS_13({}));
var __VLS_15 = __VLS_14.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_14), false));
var __VLS_18 = __VLS_16.slots.default;
var __VLS_19;
/** @ts-ignore @type {typeof __VLS_components.Tab | typeof __VLS_components.Tab} */
tab_1.default;
// @ts-ignore
var __VLS_20 = __VLS_asFunctionalComponent1(__VLS_19, new __VLS_19({
    value: "0",
}));
var __VLS_21 = __VLS_20.apply(void 0, __spreadArray([{
        value: "0",
    }], __VLS_functionalComponentArgsRest(__VLS_20), false));
var __VLS_24 = __VLS_22.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-users mr-2" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-users']} */ ;
/** @type {__VLS_StyleScopedClasses['mr-2']} */ ;
// @ts-ignore
[openCreateRoleDialog,];
var __VLS_22;
var __VLS_25;
/** @ts-ignore @type {typeof __VLS_components.Tab | typeof __VLS_components.Tab} */
tab_1.default;
// @ts-ignore
var __VLS_26 = __VLS_asFunctionalComponent1(__VLS_25, new __VLS_25({
    value: "1",
}));
var __VLS_27 = __VLS_26.apply(void 0, __spreadArray([{
        value: "1",
    }], __VLS_functionalComponentArgsRest(__VLS_26), false));
var __VLS_30 = __VLS_28.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-lock mr-2" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-lock']} */ ;
/** @type {__VLS_StyleScopedClasses['mr-2']} */ ;
// @ts-ignore
[];
var __VLS_28;
var __VLS_31;
/** @ts-ignore @type {typeof __VLS_components.Tab | typeof __VLS_components.Tab} */
tab_1.default;
// @ts-ignore
var __VLS_32 = __VLS_asFunctionalComponent1(__VLS_31, new __VLS_31({
    value: "2",
}));
var __VLS_33 = __VLS_32.apply(void 0, __spreadArray([{
        value: "2",
    }], __VLS_functionalComponentArgsRest(__VLS_32), false));
var __VLS_36 = __VLS_34.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-sitemap mr-2" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-sitemap']} */ ;
/** @type {__VLS_StyleScopedClasses['mr-2']} */ ;
// @ts-ignore
[];
var __VLS_34;
// @ts-ignore
[];
var __VLS_16;
var __VLS_37;
/** @ts-ignore @type {typeof __VLS_components.TabPanels | typeof __VLS_components.TabPanels} */
tabpanels_1.default;
// @ts-ignore
var __VLS_38 = __VLS_asFunctionalComponent1(__VLS_37, new __VLS_37({}));
var __VLS_39 = __VLS_38.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_38), false));
var __VLS_42 = __VLS_40.slots.default;
var __VLS_43;
/** @ts-ignore @type {typeof __VLS_components.TabPanel | typeof __VLS_components.TabPanel} */
tabpanel_1.default;
// @ts-ignore
var __VLS_44 = __VLS_asFunctionalComponent1(__VLS_43, new __VLS_43({
    value: "0",
}));
var __VLS_45 = __VLS_44.apply(void 0, __spreadArray([{
        value: "0",
    }], __VLS_functionalComponentArgsRest(__VLS_44), false));
var __VLS_48 = __VLS_46.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-3 mt-6" }));
/** @type {__VLS_StyleScopedClasses['space-y-3']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-12 gap-4 px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider bg-gray-50 rounded-lg" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-12']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
/** @type {__VLS_StyleScopedClasses['px-4']} */ ;
/** @type {__VLS_StyleScopedClasses['py-2']} */ ;
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
/** @type {__VLS_StyleScopedClasses['uppercase']} */ ;
/** @type {__VLS_StyleScopedClasses['tracking-wider']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "col-span-4" }));
/** @type {__VLS_StyleScopedClasses['col-span-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "col-span-3" }));
/** @type {__VLS_StyleScopedClasses['col-span-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "col-span-1 text-center" }));
/** @type {__VLS_StyleScopedClasses['col-span-1']} */ ;
/** @type {__VLS_StyleScopedClasses['text-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "col-span-1 text-center" }));
/** @type {__VLS_StyleScopedClasses['col-span-1']} */ ;
/** @type {__VLS_StyleScopedClasses['text-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "col-span-3 text-right" }));
/** @type {__VLS_StyleScopedClasses['col-span-3']} */ ;
/** @type {__VLS_StyleScopedClasses['text-right']} */ ;
var _loop_1 = function (role) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (role.id) }, { class: "grid grid-cols-12 gap-4 items-center p-4 bg-white border border-gray-200 rounded-lg hover:shadow-sm transition-shadow" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-12']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
    /** @type {__VLS_StyleScopedClasses['border']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-gray-200']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['hover:shadow-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['transition-shadow']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "col-span-4 flex items-center gap-3" }));
    /** @type {__VLS_StyleScopedClasses['col-span-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
    var __VLS_49 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Avatar} */
    avatar_1.default;
    // @ts-ignore
    var __VLS_50 = __VLS_asFunctionalComponent1(__VLS_49, new __VLS_49(__assign(__assign({ label: (role.name[0].toUpperCase()) }, { class: "bg-blue-100 text-blue-600" }), { shape: "circle", size: "normal" })));
    var __VLS_51 = __VLS_50.apply(void 0, __spreadArray([__assign(__assign({ label: (role.name[0].toUpperCase()) }, { class: "bg-blue-100 text-blue-600" }), { shape: "circle", size: "normal" })], __VLS_functionalComponentArgsRest(__VLS_50), false));
    /** @type {__VLS_StyleScopedClasses['bg-blue-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "font-semibold text-gray-800" }));
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
    (role.display_name);
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    (role.name);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "col-span-3" }));
    /** @type {__VLS_StyleScopedClasses['col-span-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600 truncate" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['truncate']} */ ;
    (role.description || '—');
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "col-span-1 text-center" }));
    /** @type {__VLS_StyleScopedClasses['col-span-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    var __VLS_54 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    tag_1.default;
    // @ts-ignore
    var __VLS_55 = __VLS_asFunctionalComponent1(__VLS_54, new __VLS_54({
        value: (role.permissions_count || 0),
        severity: "info",
    }));
    var __VLS_56 = __VLS_55.apply(void 0, __spreadArray([{
            value: (role.permissions_count || 0),
            severity: "info",
        }], __VLS_functionalComponentArgsRest(__VLS_55), false));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "col-span-1 text-center" }));
    /** @type {__VLS_StyleScopedClasses['col-span-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    var __VLS_59 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    tag_1.default;
    // @ts-ignore
    var __VLS_60 = __VLS_asFunctionalComponent1(__VLS_59, new __VLS_59({
        value: (role.users_count || 0),
        severity: "success",
    }));
    var __VLS_61 = __VLS_60.apply(void 0, __spreadArray([{
            value: (role.users_count || 0),
            severity: "success",
        }], __VLS_functionalComponentArgsRest(__VLS_60), false));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "col-span-3 flex justify-end gap-2" }));
    /** @type {__VLS_StyleScopedClasses['col-span-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-end']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    var __VLS_64 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_65 = __VLS_asFunctionalComponent1(__VLS_64, new __VLS_64(__assign({ 'onClick': {} }, { label: "Permissions", icon: "pi pi-cog", outlined: true, size: "small" })));
    var __VLS_66 = __VLS_65.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Permissions", icon: "pi pi-cog", outlined: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_65), false));
    var __VLS_69 = void 0;
    var __VLS_70 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.openPermissionsDialog(role);
                // @ts-ignore
                [roles, openPermissionsDialog,];
            } });
    var __VLS_71 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_72 = __VLS_asFunctionalComponent1(__VLS_71, new __VLS_71(__assign({ 'onClick': {} }, { icon: "pi pi-ellipsis-v", text: true, rounded: true, size: "small", 'aria-haspopup': "true", 'aria-controls': ("role_menu_".concat(role.id)) })));
    var __VLS_73 = __VLS_72.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-ellipsis-v", text: true, rounded: true, size: "small", 'aria-haspopup': "true", 'aria-controls': ("role_menu_".concat(role.id)) })], __VLS_functionalComponentArgsRest(__VLS_72), false));
    var __VLS_76 = void 0;
    var __VLS_77 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.toggleRoleMenu($event, role);
                // @ts-ignore
                [toggleRoleMenu,];
            } });
    // @ts-ignore
    [];
};
var __VLS_67, __VLS_68, __VLS_74, __VLS_75;
for (var _i = 0, _c = __VLS_vFor((__VLS_ctx.roles)); _i < _c.length; _i++) {
    var role = _c[_i][0];
    _loop_1(role);
}
if (__VLS_ctx.roles.length === 0) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center py-12" }));
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['py-12']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-center mb-4" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center" }));
    /** @type {__VLS_StyleScopedClasses['w-16']} */ ;
    /** @type {__VLS_StyleScopedClasses['h-16']} */ ;
    /** @type {__VLS_StyleScopedClasses['bg-gray-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-users text-2xl text-gray-400" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-users']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-lg font-semibold text-gray-800 mb-2" }));
    /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
}
// @ts-ignore
[roles,];
var __VLS_46;
var __VLS_78;
/** @ts-ignore @type {typeof __VLS_components.TabPanel | typeof __VLS_components.TabPanel} */
tabpanel_1.default;
// @ts-ignore
var __VLS_79 = __VLS_asFunctionalComponent1(__VLS_78, new __VLS_78({
    value: "1",
}));
var __VLS_80 = __VLS_79.apply(void 0, __spreadArray([{
        value: "1",
    }], __VLS_functionalComponentArgsRest(__VLS_79), false));
var __VLS_83 = __VLS_81.slots.default;
var __VLS_84;
/** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
card_1.default;
// @ts-ignore
var __VLS_85 = __VLS_asFunctionalComponent1(__VLS_84, new __VLS_84(__assign({ class: "mt-6" })));
var __VLS_86 = __VLS_85.apply(void 0, __spreadArray([__assign({ class: "mt-6" })], __VLS_functionalComponentArgsRest(__VLS_85), false));
/** @type {__VLS_StyleScopedClasses['mt-6']} */ ;
var __VLS_89 = __VLS_87.slots.default;
{
    var __VLS_90 = __VLS_87.slots.title;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
    var __VLS_91 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_92 = __VLS_asFunctionalComponent1(__VLS_91, new __VLS_91(__assign({ 'onClick': {} }, { label: "Add Permission", icon: "pi pi-plus", size: "small" })));
    var __VLS_93 = __VLS_92.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Add Permission", icon: "pi pi-plus", size: "small" })], __VLS_functionalComponentArgsRest(__VLS_92), false));
    var __VLS_96 = void 0;
    var __VLS_97 = ({ click: {} },
        { onClick: (__VLS_ctx.openCreatePermissionDialog) });
    var __VLS_94;
    var __VLS_95;
    // @ts-ignore
    [openCreatePermissionDialog,];
}
{
    var __VLS_98 = __VLS_87.slots.content;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "mb-4" }));
    /** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
    var __VLS_99 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select} */
    select_1.default;
    // @ts-ignore
    var __VLS_100 = __VLS_asFunctionalComponent1(__VLS_99, new __VLS_99(__assign(__assign({ modelValue: (__VLS_ctx.selectedModule), options: (__VLS_ctx.modules), optionLabel: "label", optionValue: "value", placeholder: "Filter by Module" }, { class: "w-full md:w-1/3" }), { showClear: true })));
    var __VLS_101 = __VLS_100.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.selectedModule), options: (__VLS_ctx.modules), optionLabel: "label", optionValue: "value", placeholder: "Filter by Module" }, { class: "w-full md:w-1/3" }), { showClear: true })], __VLS_functionalComponentArgsRest(__VLS_100), false));
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    /** @type {__VLS_StyleScopedClasses['md:w-1/3']} */ ;
    var __VLS_104 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
    datatable_1.default;
    // @ts-ignore
    var __VLS_105 = __VLS_asFunctionalComponent1(__VLS_104, new __VLS_104({
        value: (__VLS_ctx.filteredPermissions),
        loading: (__VLS_ctx.loadingPermissions),
        paginator: true,
        rows: (20),
        stripedRows: true,
        showGridlines: true,
    }));
    var __VLS_106 = __VLS_105.apply(void 0, __spreadArray([{
            value: (__VLS_ctx.filteredPermissions),
            loading: (__VLS_ctx.loadingPermissions),
            paginator: true,
            rows: (20),
            stripedRows: true,
            showGridlines: true,
        }], __VLS_functionalComponentArgsRest(__VLS_105), false));
    var __VLS_109 = __VLS_107.slots.default;
    var __VLS_110 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_111 = __VLS_asFunctionalComponent1(__VLS_110, new __VLS_110(__assign({ field: "name", header: "Permission Name", sortable: true }, { style: {} })));
    var __VLS_112 = __VLS_111.apply(void 0, __spreadArray([__assign({ field: "name", header: "Permission Name", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_111), false));
    var __VLS_115 = __VLS_113.slots.default;
    {
        var __VLS_116 = __VLS_113.slots.body;
        var data = __VLS_vSlot(__VLS_116)[0].data;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-lock text-gray-400" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-lock']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.code, __VLS_intrinsics.code)(__assign({ class: "text-sm bg-gray-100 px-2 py-1 rounded" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['bg-gray-100']} */ ;
        /** @type {__VLS_StyleScopedClasses['px-2']} */ ;
        /** @type {__VLS_StyleScopedClasses['py-1']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
        (data.name);
        // @ts-ignore
        [selectedModule, modules, filteredPermissions, loadingPermissions,];
    }
    // @ts-ignore
    [];
    var __VLS_113;
    var __VLS_117 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_118 = __VLS_asFunctionalComponent1(__VLS_117, new __VLS_117(__assign({ field: "display_name", header: "Display Name", sortable: true }, { style: {} })));
    var __VLS_119 = __VLS_118.apply(void 0, __spreadArray([__assign({ field: "display_name", header: "Display Name", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_118), false));
    var __VLS_122 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_123 = __VLS_asFunctionalComponent1(__VLS_122, new __VLS_122(__assign({ field: "module", header: "Module", sortable: true }, { style: {} })));
    var __VLS_124 = __VLS_123.apply(void 0, __spreadArray([__assign({ field: "module", header: "Module", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_123), false));
    var __VLS_127 = __VLS_125.slots.default;
    {
        var __VLS_128 = __VLS_125.slots.body;
        var data = __VLS_vSlot(__VLS_128)[0].data;
        var __VLS_129 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        tag_1.default;
        // @ts-ignore
        var __VLS_130 = __VLS_asFunctionalComponent1(__VLS_129, new __VLS_129({
            value: (data.module),
            severity: (__VLS_ctx.getModuleSeverity(data.module)),
        }));
        var __VLS_131 = __VLS_130.apply(void 0, __spreadArray([{
                value: (data.module),
                severity: (__VLS_ctx.getModuleSeverity(data.module)),
            }], __VLS_functionalComponentArgsRest(__VLS_130), false));
        // @ts-ignore
        [getModuleSeverity,];
    }
    // @ts-ignore
    [];
    var __VLS_125;
    var __VLS_134 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_135 = __VLS_asFunctionalComponent1(__VLS_134, new __VLS_134(__assign({ field: "description", header: "Description" }, { style: {} })));
    var __VLS_136 = __VLS_135.apply(void 0, __spreadArray([__assign({ field: "description", header: "Description" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_135), false));
    var __VLS_139 = __VLS_137.slots.default;
    {
        var __VLS_140 = __VLS_137.slots.body;
        var data = __VLS_vSlot(__VLS_140)[0].data;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm text-gray-600" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        (data.description || '-');
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_137;
    var __VLS_141 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_142 = __VLS_asFunctionalComponent1(__VLS_141, new __VLS_141(__assign({ field: "is_active", header: "Status", sortable: true }, { style: {} })));
    var __VLS_143 = __VLS_142.apply(void 0, __spreadArray([__assign({ field: "is_active", header: "Status", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_142), false));
    var __VLS_146 = __VLS_144.slots.default;
    {
        var __VLS_147 = __VLS_144.slots.body;
        var data = __VLS_vSlot(__VLS_147)[0].data;
        var __VLS_148 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        tag_1.default;
        // @ts-ignore
        var __VLS_149 = __VLS_asFunctionalComponent1(__VLS_148, new __VLS_148({
            value: (data.is_active ? 'Active' : 'Inactive'),
            severity: (data.is_active ? 'success' : 'danger'),
        }));
        var __VLS_150 = __VLS_149.apply(void 0, __spreadArray([{
                value: (data.is_active ? 'Active' : 'Inactive'),
                severity: (data.is_active ? 'success' : 'danger'),
            }], __VLS_functionalComponentArgsRest(__VLS_149), false));
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_144;
    var __VLS_153 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_154 = __VLS_asFunctionalComponent1(__VLS_153, new __VLS_153(__assign({ header: "Actions" }, { style: {} })));
    var __VLS_155 = __VLS_154.apply(void 0, __spreadArray([__assign({ header: "Actions" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_154), false));
    var __VLS_158 = __VLS_156.slots.default;
    {
        var __VLS_159 = __VLS_156.slots.body;
        var data_1 = __VLS_vSlot(__VLS_159)[0].data;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        var __VLS_160 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_161 = __VLS_asFunctionalComponent1(__VLS_160, new __VLS_160(__assign({ 'onClick': {} }, { icon: "pi pi-pencil", text: true, rounded: true, severity: "warning" })));
        var __VLS_162 = __VLS_161.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-pencil", text: true, rounded: true, severity: "warning" })], __VLS_functionalComponentArgsRest(__VLS_161), false));
        var __VLS_165 = void 0;
        var __VLS_166 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    __VLS_ctx.editPermission(data_1);
                    // @ts-ignore
                    [editPermission,];
                } });
        __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('Edit') }), null, null);
        var __VLS_163;
        var __VLS_164;
        var __VLS_167 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_168 = __VLS_asFunctionalComponent1(__VLS_167, new __VLS_167(__assign({ 'onClick': {} }, { icon: "pi pi-trash", text: true, rounded: true, severity: "danger" })));
        var __VLS_169 = __VLS_168.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-trash", text: true, rounded: true, severity: "danger" })], __VLS_functionalComponentArgsRest(__VLS_168), false));
        var __VLS_172 = void 0;
        var __VLS_173 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    __VLS_ctx.confirmDeletePermission(data_1);
                    // @ts-ignore
                    [vTooltip, confirmDeletePermission,];
                } });
        __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('Delete') }), null, null);
        var __VLS_170;
        var __VLS_171;
        // @ts-ignore
        [vTooltip,];
    }
    // @ts-ignore
    [];
    var __VLS_156;
    // @ts-ignore
    [];
    var __VLS_107;
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_87;
// @ts-ignore
[];
var __VLS_81;
var __VLS_174;
/** @ts-ignore @type {typeof __VLS_components.TabPanel | typeof __VLS_components.TabPanel} */
tabpanel_1.default;
// @ts-ignore
var __VLS_175 = __VLS_asFunctionalComponent1(__VLS_174, new __VLS_174({
    value: "2",
}));
var __VLS_176 = __VLS_175.apply(void 0, __spreadArray([{
        value: "2",
    }], __VLS_functionalComponentArgsRest(__VLS_175), false));
var __VLS_179 = __VLS_177.slots.default;
var __VLS_180;
/** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
card_1.default;
// @ts-ignore
var __VLS_181 = __VLS_asFunctionalComponent1(__VLS_180, new __VLS_180(__assign({ class: "mt-6" })));
var __VLS_182 = __VLS_181.apply(void 0, __spreadArray([__assign({ class: "mt-6" })], __VLS_functionalComponentArgsRest(__VLS_181), false));
/** @type {__VLS_StyleScopedClasses['mt-6']} */ ;
var __VLS_185 = __VLS_183.slots.default;
{
    var __VLS_186 = __VLS_183.slots.title;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
    var __VLS_187 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_188 = __VLS_asFunctionalComponent1(__VLS_187, new __VLS_187(__assign({ 'onClick': {} }, { label: "Add Navigation", icon: "pi pi-plus", size: "small" })));
    var __VLS_189 = __VLS_188.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Add Navigation", icon: "pi pi-plus", size: "small" })], __VLS_functionalComponentArgsRest(__VLS_188), false));
    var __VLS_192 = void 0;
    var __VLS_193 = ({ click: {} },
        { onClick: (__VLS_ctx.openCreateNavigationDialog) });
    var __VLS_190;
    var __VLS_191;
    // @ts-ignore
    [openCreateNavigationDialog,];
}
{
    var __VLS_194 = __VLS_183.slots.content;
    var __VLS_195 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
    datatable_1.default;
    // @ts-ignore
    var __VLS_196 = __VLS_asFunctionalComponent1(__VLS_195, new __VLS_195({
        value: (__VLS_ctx.navigationItems),
        loading: (__VLS_ctx.loadingNavigation),
        paginator: true,
        rows: (20),
        stripedRows: true,
    }));
    var __VLS_197 = __VLS_196.apply(void 0, __spreadArray([{
            value: (__VLS_ctx.navigationItems),
            loading: (__VLS_ctx.loadingNavigation),
            paginator: true,
            rows: (20),
            stripedRows: true,
        }], __VLS_functionalComponentArgsRest(__VLS_196), false));
    var __VLS_200 = __VLS_198.slots.default;
    var __VLS_201 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_202 = __VLS_asFunctionalComponent1(__VLS_201, new __VLS_201(__assign({ field: "display_name", header: "Navigation Item", sortable: true }, { style: {} })));
    var __VLS_203 = __VLS_202.apply(void 0, __spreadArray([__assign({ field: "display_name", header: "Navigation Item", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_202), false));
    var __VLS_206 = __VLS_204.slots.default;
    {
        var __VLS_207 = __VLS_204.slots.body;
        var data = __VLS_vSlot(__VLS_207)[0].data;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: (data.icon || 'pi pi-circle') }, { class: "text-blue-600" }));
        /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (data.display_name);
        // @ts-ignore
        [navigationItems, loadingNavigation,];
    }
    // @ts-ignore
    [];
    var __VLS_204;
    var __VLS_208 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_209 = __VLS_asFunctionalComponent1(__VLS_208, new __VLS_208(__assign({ field: "route_path", header: "Route", sortable: true }, { style: {} })));
    var __VLS_210 = __VLS_209.apply(void 0, __spreadArray([__assign({ field: "route_path", header: "Route", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_209), false));
    var __VLS_213 = __VLS_211.slots.default;
    {
        var __VLS_214 = __VLS_211.slots.body;
        var data = __VLS_vSlot(__VLS_214)[0].data;
        __VLS_asFunctionalElement1(__VLS_intrinsics.code, __VLS_intrinsics.code)(__assign({ class: "text-sm bg-gray-100 px-2 py-1 rounded" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['bg-gray-100']} */ ;
        /** @type {__VLS_StyleScopedClasses['px-2']} */ ;
        /** @type {__VLS_StyleScopedClasses['py-1']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
        (data.route_path);
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_211;
    var __VLS_215 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_216 = __VLS_asFunctionalComponent1(__VLS_215, new __VLS_215(__assign({ field: "module", header: "Module", sortable: true }, { style: {} })));
    var __VLS_217 = __VLS_216.apply(void 0, __spreadArray([__assign({ field: "module", header: "Module", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_216), false));
    var __VLS_220 = __VLS_218.slots.default;
    {
        var __VLS_221 = __VLS_218.slots.body;
        var data = __VLS_vSlot(__VLS_221)[0].data;
        var __VLS_222 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        tag_1.default;
        // @ts-ignore
        var __VLS_223 = __VLS_asFunctionalComponent1(__VLS_222, new __VLS_222({
            value: (data.module),
            severity: "info",
        }));
        var __VLS_224 = __VLS_223.apply(void 0, __spreadArray([{
                value: (data.module),
                severity: "info",
            }], __VLS_functionalComponentArgsRest(__VLS_223), false));
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_218;
    var __VLS_227 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_228 = __VLS_asFunctionalComponent1(__VLS_227, new __VLS_227(__assign({ field: "display_order", header: "Order", sortable: true }, { style: {} })));
    var __VLS_229 = __VLS_228.apply(void 0, __spreadArray([__assign({ field: "display_order", header: "Order", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_228), false));
    var __VLS_232 = __VLS_230.slots.default;
    {
        var __VLS_233 = __VLS_230.slots.body;
        var data = __VLS_vSlot(__VLS_233)[0].data;
        var __VLS_234 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        tag_1.default;
        // @ts-ignore
        var __VLS_235 = __VLS_asFunctionalComponent1(__VLS_234, new __VLS_234({
            value: (data.display_order),
        }));
        var __VLS_236 = __VLS_235.apply(void 0, __spreadArray([{
                value: (data.display_order),
            }], __VLS_functionalComponentArgsRest(__VLS_235), false));
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_230;
    var __VLS_239 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_240 = __VLS_asFunctionalComponent1(__VLS_239, new __VLS_239(__assign({ field: "is_active", header: "Status", sortable: true }, { style: {} })));
    var __VLS_241 = __VLS_240.apply(void 0, __spreadArray([__assign({ field: "is_active", header: "Status", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_240), false));
    var __VLS_244 = __VLS_242.slots.default;
    {
        var __VLS_245 = __VLS_242.slots.body;
        var data = __VLS_vSlot(__VLS_245)[0].data;
        var __VLS_246 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        tag_1.default;
        // @ts-ignore
        var __VLS_247 = __VLS_asFunctionalComponent1(__VLS_246, new __VLS_246({
            value: (data.is_active ? 'Active' : 'Inactive'),
            severity: (data.is_active ? 'success' : 'danger'),
        }));
        var __VLS_248 = __VLS_247.apply(void 0, __spreadArray([{
                value: (data.is_active ? 'Active' : 'Inactive'),
                severity: (data.is_active ? 'success' : 'danger'),
            }], __VLS_functionalComponentArgsRest(__VLS_247), false));
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_242;
    var __VLS_251 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_252 = __VLS_asFunctionalComponent1(__VLS_251, new __VLS_251(__assign({ header: "Actions" }, { style: {} })));
    var __VLS_253 = __VLS_252.apply(void 0, __spreadArray([__assign({ header: "Actions" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_252), false));
    var __VLS_256 = __VLS_254.slots.default;
    {
        var __VLS_257 = __VLS_254.slots.body;
        var data_2 = __VLS_vSlot(__VLS_257)[0].data;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        var __VLS_258 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_259 = __VLS_asFunctionalComponent1(__VLS_258, new __VLS_258(__assign({ 'onClick': {} }, { icon: "pi pi-pencil", text: true, rounded: true, severity: "warning" })));
        var __VLS_260 = __VLS_259.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-pencil", text: true, rounded: true, severity: "warning" })], __VLS_functionalComponentArgsRest(__VLS_259), false));
        var __VLS_263 = void 0;
        var __VLS_264 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    __VLS_ctx.editNavigation(data_2);
                    // @ts-ignore
                    [editNavigation,];
                } });
        __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('Edit') }), null, null);
        var __VLS_261;
        var __VLS_262;
        var __VLS_265 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_266 = __VLS_asFunctionalComponent1(__VLS_265, new __VLS_265(__assign({ 'onClick': {} }, { icon: "pi pi-trash", text: true, rounded: true, severity: "danger" })));
        var __VLS_267 = __VLS_266.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-trash", text: true, rounded: true, severity: "danger" })], __VLS_functionalComponentArgsRest(__VLS_266), false));
        var __VLS_270 = void 0;
        var __VLS_271 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    __VLS_ctx.confirmDeleteNavigation(data_2);
                    // @ts-ignore
                    [vTooltip, confirmDeleteNavigation,];
                } });
        __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('Delete') }), null, null);
        var __VLS_268;
        var __VLS_269;
        // @ts-ignore
        [vTooltip,];
    }
    // @ts-ignore
    [];
    var __VLS_254;
    // @ts-ignore
    [];
    var __VLS_198;
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_183;
// @ts-ignore
[];
var __VLS_177;
// @ts-ignore
[];
var __VLS_40;
// @ts-ignore
[];
var __VLS_10;
var __VLS_272;
/** @ts-ignore @type {typeof __VLS_components.Menu} */
menu_1.default;
// @ts-ignore
var __VLS_273 = __VLS_asFunctionalComponent1(__VLS_272, new __VLS_272({
    ref: "roleMenu",
    model: (__VLS_ctx.roleMenuItems),
    popup: (true),
}));
var __VLS_274 = __VLS_273.apply(void 0, __spreadArray([{
        ref: "roleMenu",
        model: (__VLS_ctx.roleMenuItems),
        popup: (true),
    }], __VLS_functionalComponentArgsRest(__VLS_273), false));
var __VLS_277 = {};
var __VLS_275;
var __VLS_279;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
dialog_1.default;
// @ts-ignore
var __VLS_280 = __VLS_asFunctionalComponent1(__VLS_279, new __VLS_279(__assign(__assign({ visible: (__VLS_ctx.permissionsDialog) }, { style: ({ width: '800px' }) }), { header: "Manage Permissions", modal: (true), maximizable: true })));
var __VLS_281 = __VLS_280.apply(void 0, __spreadArray([__assign(__assign({ visible: (__VLS_ctx.permissionsDialog) }, { style: ({ width: '800px' }) }), { header: "Manage Permissions", modal: (true), maximizable: true })], __VLS_functionalComponentArgsRest(__VLS_280), false));
var __VLS_284 = __VLS_282.slots.default;
if (__VLS_ctx.selectedRole) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
    /** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-blue-50 p-4 rounded-lg border border-blue-200" }));
    /** @type {__VLS_StyleScopedClasses['bg-blue-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['border']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-blue-200']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "font-semibold text-blue-900" }));
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-900']} */ ;
    (__VLS_ctx.selectedRole.display_name);
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-blue-700" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-700']} */ ;
    (__VLS_ctx.selectedRole.description);
    var _loop_2 = function (module) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (module.name) }, { class: "border border-gray-200 rounded-lg overflow-hidden" }));
        /** @type {__VLS_StyleScopedClasses['border']} */ ;
        /** @type {__VLS_StyleScopedClasses['border-gray-200']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        /** @type {__VLS_StyleScopedClasses['overflow-hidden']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                if (!(__VLS_ctx.selectedRole))
                    return;
                __VLS_ctx.toggleModule(module.name);
                // @ts-ignore
                [roleMenuItems, permissionsDialog, selectedRole, selectedRole, selectedRole, permissionsByModule, toggleModule,];
            } }, { class: "bg-gray-100 px-4 py-3 flex items-center justify-between cursor-pointer hover:bg-gray-200" }));
        /** @type {__VLS_StyleScopedClasses['bg-gray-100']} */ ;
        /** @type {__VLS_StyleScopedClasses['px-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['py-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
        /** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
        /** @type {__VLS_StyleScopedClasses['hover:bg-gray-200']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-3" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: (__VLS_ctx.expandedModules.includes(module.name) ? 'pi pi-chevron-down' : 'pi pi-chevron-right') }, { class: "text-gray-600" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.h4, __VLS_intrinsics.h4)(__assign({ class: "font-semibold text-gray-800" }));
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
        (module.display_name);
        var __VLS_285 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        tag_1.default;
        // @ts-ignore
        var __VLS_286 = __VLS_asFunctionalComponent1(__VLS_285, new __VLS_285({
            value: ("".concat(module.selected, "/").concat(module.total)),
            severity: "info",
        }));
        var __VLS_287 = __VLS_286.apply(void 0, __spreadArray([{
                value: ("".concat(module.selected, "/").concat(module.total)),
                severity: "info",
            }], __VLS_functionalComponentArgsRest(__VLS_286), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-gray-600" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        var __VLS_290 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Checkbox} */
        checkbox_1.default;
        // @ts-ignore
        var __VLS_291 = __VLS_asFunctionalComponent1(__VLS_290, new __VLS_290(__assign({ 'onUpdate:modelValue': {} }, { modelValue: (module.selected === module.total), binary: (true) })));
        var __VLS_292 = __VLS_291.apply(void 0, __spreadArray([__assign({ 'onUpdate:modelValue': {} }, { modelValue: (module.selected === module.total), binary: (true) })], __VLS_functionalComponentArgsRest(__VLS_291), false));
        var __VLS_295 = void 0;
        var __VLS_296 = ({ 'update:modelValue': {} },
            { 'onUpdate:modelValue': function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!(__VLS_ctx.selectedRole))
                        return;
                    __VLS_ctx.toggleModulePermissions(module.name, $event);
                    // @ts-ignore
                    [expandedModules, toggleModulePermissions,];
                } });
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "p-4 space-y-2" }));
        __VLS_asFunctionalDirective(__VLS_directives.vShow, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { value: (__VLS_ctx.expandedModules.includes(module.name)) }), null, null);
        /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['space-y-2']} */ ;
        for (var _f = 0, _g = __VLS_vFor((module.permissions)); _f < _g.length; _f++) {
            var permission = _g[_f][0];
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (permission.id) }, { class: "flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
            /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
            /** @type {__VLS_StyleScopedClasses['hover:bg-gray-50']} */ ;
            /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex-1" }));
            /** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.code, __VLS_intrinsics.code)(__assign({ class: "text-sm bg-gray-100 px-2 py-1 rounded" }));
            /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
            /** @type {__VLS_StyleScopedClasses['bg-gray-100']} */ ;
            /** @type {__VLS_StyleScopedClasses['px-2']} */ ;
            /** @type {__VLS_StyleScopedClasses['py-1']} */ ;
            /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
            (permission.display_name);
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600 mt-1" }));
            /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
            /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
            (permission.name);
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
            /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
            (permission.description);
            var __VLS_297 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Checkbox} */
            checkbox_1.default;
            // @ts-ignore
            var __VLS_298 = __VLS_asFunctionalComponent1(__VLS_297, new __VLS_297({
                modelValue: (__VLS_ctx.selectedRolePermissions),
                value: (permission.id),
            }));
            var __VLS_299 = __VLS_298.apply(void 0, __spreadArray([{
                    modelValue: (__VLS_ctx.selectedRolePermissions),
                    value: (permission.id),
                }], __VLS_functionalComponentArgsRest(__VLS_298), false));
            // @ts-ignore
            [expandedModules, selectedRolePermissions,];
        }
        // @ts-ignore
        [];
    };
    var __VLS_293, __VLS_294;
    for (var _d = 0, _e = __VLS_vFor((__VLS_ctx.permissionsByModule)); _d < _e.length; _d++) {
        var module = _e[_d][0];
        _loop_2(module);
    }
}
{
    var __VLS_302 = __VLS_282.slots.footer;
    var __VLS_303 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_304 = __VLS_asFunctionalComponent1(__VLS_303, new __VLS_303(__assign({ 'onClick': {} }, { label: "Cancel", text: true })));
    var __VLS_305 = __VLS_304.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", text: true })], __VLS_functionalComponentArgsRest(__VLS_304), false));
    var __VLS_308 = void 0;
    var __VLS_309 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.permissionsDialog = false;
                // @ts-ignore
                [permissionsDialog,];
            } });
    var __VLS_306;
    var __VLS_307;
    var __VLS_310 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_311 = __VLS_asFunctionalComponent1(__VLS_310, new __VLS_310(__assign({ 'onClick': {} }, { label: "Save Permissions", icon: "pi pi-check", loading: (__VLS_ctx.savingPermissions) })));
    var __VLS_312 = __VLS_311.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Save Permissions", icon: "pi pi-check", loading: (__VLS_ctx.savingPermissions) })], __VLS_functionalComponentArgsRest(__VLS_311), false));
    var __VLS_315 = void 0;
    var __VLS_316 = ({ click: {} },
        { onClick: (__VLS_ctx.saveRolePermissions) });
    var __VLS_313;
    var __VLS_314;
    // @ts-ignore
    [savingPermissions, saveRolePermissions,];
}
// @ts-ignore
[];
var __VLS_282;
var __VLS_317;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
dialog_1.default;
// @ts-ignore
var __VLS_318 = __VLS_asFunctionalComponent1(__VLS_317, new __VLS_317(__assign(__assign({ visible: (__VLS_ctx.permissionDialog) }, { style: ({ width: '500px' }) }), { header: (__VLS_ctx.editingPermission ? 'Edit Permission' : 'Create Permission'), modal: (true) })));
var __VLS_319 = __VLS_318.apply(void 0, __spreadArray([__assign(__assign({ visible: (__VLS_ctx.permissionDialog) }, { style: ({ width: '500px' }) }), { header: (__VLS_ctx.editingPermission ? 'Edit Permission' : 'Create Permission'), modal: (true) })], __VLS_functionalComponentArgsRest(__VLS_318), false));
var __VLS_322 = __VLS_320.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
/** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-sm font-semibold text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
var __VLS_323;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
inputtext_1.default;
// @ts-ignore
var __VLS_324 = __VLS_asFunctionalComponent1(__VLS_323, new __VLS_323(__assign({ modelValue: (__VLS_ctx.permissionForm.name), placeholder: "e.g., merchandising.products.view" }, { class: ({ 'p-invalid': __VLS_ctx.permissionErrors.name }) })));
var __VLS_325 = __VLS_324.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.permissionForm.name), placeholder: "e.g., merchandising.products.view" }, { class: ({ 'p-invalid': __VLS_ctx.permissionErrors.name }) })], __VLS_functionalComponentArgsRest(__VLS_324), false));
/** @type {__VLS_StyleScopedClasses['p-invalid']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
if (__VLS_ctx.permissionErrors.name) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-red-500" }));
    /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
    (__VLS_ctx.permissionErrors.name);
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-sm font-semibold text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
var __VLS_328;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
inputtext_1.default;
// @ts-ignore
var __VLS_329 = __VLS_asFunctionalComponent1(__VLS_328, new __VLS_328(__assign({ modelValue: (__VLS_ctx.permissionForm.display_name), placeholder: "e.g., View Products" }, { class: ({ 'p-invalid': __VLS_ctx.permissionErrors.display_name }) })));
var __VLS_330 = __VLS_329.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.permissionForm.display_name), placeholder: "e.g., View Products" }, { class: ({ 'p-invalid': __VLS_ctx.permissionErrors.display_name }) })], __VLS_functionalComponentArgsRest(__VLS_329), false));
/** @type {__VLS_StyleScopedClasses['p-invalid']} */ ;
if (__VLS_ctx.permissionErrors.display_name) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-red-500" }));
    /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
    (__VLS_ctx.permissionErrors.display_name);
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-sm font-semibold text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
var __VLS_333;
/** @ts-ignore @type {typeof __VLS_components.Select} */
select_1.default;
// @ts-ignore
var __VLS_334 = __VLS_asFunctionalComponent1(__VLS_333, new __VLS_333(__assign({ modelValue: (__VLS_ctx.permissionForm.module), options: (__VLS_ctx.modules), optionLabel: "label", optionValue: "value", placeholder: "Select Module" }, { class: ({ 'p-invalid': __VLS_ctx.permissionErrors.module }) })));
var __VLS_335 = __VLS_334.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.permissionForm.module), options: (__VLS_ctx.modules), optionLabel: "label", optionValue: "value", placeholder: "Select Module" }, { class: ({ 'p-invalid': __VLS_ctx.permissionErrors.module }) })], __VLS_functionalComponentArgsRest(__VLS_334), false));
/** @type {__VLS_StyleScopedClasses['p-invalid']} */ ;
if (__VLS_ctx.permissionErrors.module) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-red-500" }));
    /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
    (__VLS_ctx.permissionErrors.module);
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-sm font-semibold text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
var __VLS_338;
/** @ts-ignore @type {typeof __VLS_components.Textarea} */
textarea_1.default;
// @ts-ignore
var __VLS_339 = __VLS_asFunctionalComponent1(__VLS_338, new __VLS_338({
    modelValue: (__VLS_ctx.permissionForm.description),
    rows: "3",
    placeholder: "Describe what this permission allows...",
}));
var __VLS_340 = __VLS_339.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.permissionForm.description),
        rows: "3",
        placeholder: "Describe what this permission allows...",
    }], __VLS_functionalComponentArgsRest(__VLS_339), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
var __VLS_343;
/** @ts-ignore @type {typeof __VLS_components.Checkbox} */
checkbox_1.default;
// @ts-ignore
var __VLS_344 = __VLS_asFunctionalComponent1(__VLS_343, new __VLS_343({
    modelValue: (__VLS_ctx.permissionForm.is_active),
    inputId: "is_active",
    binary: (true),
}));
var __VLS_345 = __VLS_344.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.permissionForm.is_active),
        inputId: "is_active",
        binary: (true),
    }], __VLS_functionalComponentArgsRest(__VLS_344), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "is_active" }, { class: "text-sm text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
{
    var __VLS_348 = __VLS_320.slots.footer;
    var __VLS_349 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_350 = __VLS_asFunctionalComponent1(__VLS_349, new __VLS_349(__assign({ 'onClick': {} }, { label: "Cancel", text: true })));
    var __VLS_351 = __VLS_350.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", text: true })], __VLS_functionalComponentArgsRest(__VLS_350), false));
    var __VLS_354 = void 0;
    var __VLS_355 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.permissionDialog = false;
                // @ts-ignore
                [modules, permissionDialog, permissionDialog, editingPermission, permissionForm, permissionForm, permissionForm, permissionForm, permissionForm, permissionErrors, permissionErrors, permissionErrors, permissionErrors, permissionErrors, permissionErrors, permissionErrors, permissionErrors, permissionErrors,];
            } });
    var __VLS_352;
    var __VLS_353;
    var __VLS_356 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_357 = __VLS_asFunctionalComponent1(__VLS_356, new __VLS_356(__assign({ 'onClick': {} }, { label: (__VLS_ctx.editingPermission ? 'Update' : 'Create'), icon: "pi pi-check", loading: (__VLS_ctx.savingPermission) })));
    var __VLS_358 = __VLS_357.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: (__VLS_ctx.editingPermission ? 'Update' : 'Create'), icon: "pi pi-check", loading: (__VLS_ctx.savingPermission) })], __VLS_functionalComponentArgsRest(__VLS_357), false));
    var __VLS_361 = void 0;
    var __VLS_362 = ({ click: {} },
        { onClick: (__VLS_ctx.savePermission) });
    var __VLS_359;
    var __VLS_360;
    // @ts-ignore
    [editingPermission, savingPermission, savePermission,];
}
// @ts-ignore
[];
var __VLS_320;
var __VLS_363;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
dialog_1.default;
// @ts-ignore
var __VLS_364 = __VLS_asFunctionalComponent1(__VLS_363, new __VLS_363(__assign(__assign({ visible: (__VLS_ctx.navigationDialog) }, { style: ({ width: '600px' }) }), { header: (__VLS_ctx.editingNavigation ? 'Edit Navigation' : 'Create Navigation'), modal: (true) })));
var __VLS_365 = __VLS_364.apply(void 0, __spreadArray([__assign(__assign({ visible: (__VLS_ctx.navigationDialog) }, { style: ({ width: '600px' }) }), { header: (__VLS_ctx.editingNavigation ? 'Edit Navigation' : 'Create Navigation'), modal: (true) })], __VLS_functionalComponentArgsRest(__VLS_364), false));
var __VLS_368 = __VLS_366.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
/** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-4" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-sm font-semibold text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
var __VLS_369;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
inputtext_1.default;
// @ts-ignore
var __VLS_370 = __VLS_asFunctionalComponent1(__VLS_369, new __VLS_369({
    modelValue: (__VLS_ctx.navigationForm.name),
    placeholder: "e.g., merchandising.products",
}));
var __VLS_371 = __VLS_370.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.navigationForm.name),
        placeholder: "e.g., merchandising.products",
    }], __VLS_functionalComponentArgsRest(__VLS_370), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-sm font-semibold text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
var __VLS_374;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
inputtext_1.default;
// @ts-ignore
var __VLS_375 = __VLS_asFunctionalComponent1(__VLS_374, new __VLS_374({
    modelValue: (__VLS_ctx.navigationForm.display_name),
    placeholder: "e.g., All Products",
}));
var __VLS_376 = __VLS_375.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.navigationForm.display_name),
        placeholder: "e.g., All Products",
    }], __VLS_functionalComponentArgsRest(__VLS_375), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-4" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-sm font-semibold text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
var __VLS_379;
/** @ts-ignore @type {typeof __VLS_components.Select} */
select_1.default;
// @ts-ignore
var __VLS_380 = __VLS_asFunctionalComponent1(__VLS_379, new __VLS_379({
    modelValue: (__VLS_ctx.navigationForm.module),
    options: (__VLS_ctx.modules),
    optionLabel: "label",
    optionValue: "value",
    placeholder: "Select Module",
}));
var __VLS_381 = __VLS_380.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.navigationForm.module),
        options: (__VLS_ctx.modules),
        optionLabel: "label",
        optionValue: "value",
        placeholder: "Select Module",
    }], __VLS_functionalComponentArgsRest(__VLS_380), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-sm font-semibold text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
var __VLS_384;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
inputtext_1.default;
// @ts-ignore
var __VLS_385 = __VLS_asFunctionalComponent1(__VLS_384, new __VLS_384({
    modelValue: (__VLS_ctx.navigationForm.icon),
    placeholder: "e.g., pi pi-box",
}));
var __VLS_386 = __VLS_385.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.navigationForm.icon),
        placeholder: "e.g., pi pi-box",
    }], __VLS_functionalComponentArgsRest(__VLS_385), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-4" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-sm font-semibold text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
var __VLS_389;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
inputtext_1.default;
// @ts-ignore
var __VLS_390 = __VLS_asFunctionalComponent1(__VLS_389, new __VLS_389({
    modelValue: (__VLS_ctx.navigationForm.route_name),
    placeholder: "e.g., merchandising.products",
}));
var __VLS_391 = __VLS_390.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.navigationForm.route_name),
        placeholder: "e.g., merchandising.products",
    }], __VLS_functionalComponentArgsRest(__VLS_390), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-sm font-semibold text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
var __VLS_394;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
inputtext_1.default;
// @ts-ignore
var __VLS_395 = __VLS_asFunctionalComponent1(__VLS_394, new __VLS_394({
    modelValue: (__VLS_ctx.navigationForm.route_path),
    placeholder: "e.g., /merchandising/products",
}));
var __VLS_396 = __VLS_395.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.navigationForm.route_path),
        placeholder: "e.g., /merchandising/products",
    }], __VLS_functionalComponentArgsRest(__VLS_395), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-4" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-sm font-semibold text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
var __VLS_399;
/** @ts-ignore @type {typeof __VLS_components.InputNumber} */
inputnumber_1.default;
// @ts-ignore
var __VLS_400 = __VLS_asFunctionalComponent1(__VLS_399, new __VLS_399({
    modelValue: (__VLS_ctx.navigationForm.display_order),
    min: (0),
}));
var __VLS_401 = __VLS_400.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.navigationForm.display_order),
        min: (0),
    }], __VLS_functionalComponentArgsRest(__VLS_400), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-sm font-semibold text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
var __VLS_404;
/** @ts-ignore @type {typeof __VLS_components.Select} */
select_1.default;
// @ts-ignore
var __VLS_405 = __VLS_asFunctionalComponent1(__VLS_404, new __VLS_404({
    modelValue: (__VLS_ctx.navigationForm.parent_id),
    options: (__VLS_ctx.parentNavigationOptions),
    optionLabel: "display_name",
    optionValue: "id",
    placeholder: "None (Top Level)",
    showClear: true,
}));
var __VLS_406 = __VLS_405.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.navigationForm.parent_id),
        options: (__VLS_ctx.parentNavigationOptions),
        optionLabel: "display_name",
        optionValue: "id",
        placeholder: "None (Top Level)",
        showClear: true,
    }], __VLS_functionalComponentArgsRest(__VLS_405), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-sm font-semibold text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
var __VLS_409;
/** @ts-ignore @type {typeof __VLS_components.MultiSelect} */
multiselect_1.default;
// @ts-ignore
var __VLS_410 = __VLS_asFunctionalComponent1(__VLS_409, new __VLS_409(__assign({ modelValue: (__VLS_ctx.navigationForm.permissions), options: (__VLS_ctx.allPermissions), optionLabel: "display_name", optionValue: "id", placeholder: "Select permissions", display: "chip", filter: (true) }, { class: "w-full" })));
var __VLS_411 = __VLS_410.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.navigationForm.permissions), options: (__VLS_ctx.allPermissions), optionLabel: "display_name", optionValue: "id", placeholder: "Select permissions", display: "chip", filter: (true) }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_410), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
var __VLS_414;
/** @ts-ignore @type {typeof __VLS_components.Checkbox} */
checkbox_1.default;
// @ts-ignore
var __VLS_415 = __VLS_asFunctionalComponent1(__VLS_414, new __VLS_414({
    modelValue: (__VLS_ctx.navigationForm.is_active),
    inputId: "nav_active",
    binary: (true),
}));
var __VLS_416 = __VLS_415.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.navigationForm.is_active),
        inputId: "nav_active",
        binary: (true),
    }], __VLS_functionalComponentArgsRest(__VLS_415), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "nav_active" }, { class: "text-sm text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
{
    var __VLS_419 = __VLS_366.slots.footer;
    var __VLS_420 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_421 = __VLS_asFunctionalComponent1(__VLS_420, new __VLS_420(__assign({ 'onClick': {} }, { label: "Cancel", text: true })));
    var __VLS_422 = __VLS_421.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", text: true })], __VLS_functionalComponentArgsRest(__VLS_421), false));
    var __VLS_425 = void 0;
    var __VLS_426 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.navigationDialog = false;
                // @ts-ignore
                [modules, navigationDialog, navigationDialog, editingNavigation, navigationForm, navigationForm, navigationForm, navigationForm, navigationForm, navigationForm, navigationForm, navigationForm, navigationForm, navigationForm, parentNavigationOptions, allPermissions,];
            } });
    var __VLS_423;
    var __VLS_424;
    var __VLS_427 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_428 = __VLS_asFunctionalComponent1(__VLS_427, new __VLS_427(__assign({ 'onClick': {} }, { label: (__VLS_ctx.editingNavigation ? 'Update' : 'Create'), icon: "pi pi-check", loading: (__VLS_ctx.savingNavigation) })));
    var __VLS_429 = __VLS_428.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: (__VLS_ctx.editingNavigation ? 'Update' : 'Create'), icon: "pi pi-check", loading: (__VLS_ctx.savingNavigation) })], __VLS_functionalComponentArgsRest(__VLS_428), false));
    var __VLS_432 = void 0;
    var __VLS_433 = ({ click: {} },
        { onClick: (__VLS_ctx.saveNavigation) });
    var __VLS_430;
    var __VLS_431;
    // @ts-ignore
    [editingNavigation, savingNavigation, saveNavigation,];
}
// @ts-ignore
[];
var __VLS_366;
var __VLS_434;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
dialog_1.default;
// @ts-ignore
var __VLS_435 = __VLS_asFunctionalComponent1(__VLS_434, new __VLS_434(__assign(__assign({ visible: (__VLS_ctx.deletePermissionDialog) }, { style: ({ width: '450px' }) }), { header: "Confirm Delete", modal: (true) })));
var __VLS_436 = __VLS_435.apply(void 0, __spreadArray([__assign(__assign({ visible: (__VLS_ctx.deletePermissionDialog) }, { style: ({ width: '450px' }) }), { header: "Confirm Delete", modal: (true) })], __VLS_functionalComponentArgsRest(__VLS_435), false));
var __VLS_439 = __VLS_437.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-4" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-exclamation-triangle text-4xl text-orange-500" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-exclamation-triangle']} */ ;
/** @type {__VLS_StyleScopedClasses['text-4xl']} */ ;
/** @type {__VLS_StyleScopedClasses['text-orange-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.b, __VLS_intrinsics.b)({});
((_a = __VLS_ctx.permissionToDelete) === null || _a === void 0 ? void 0 : _a.name);
{
    var __VLS_440 = __VLS_437.slots.footer;
    var __VLS_441 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_442 = __VLS_asFunctionalComponent1(__VLS_441, new __VLS_441(__assign({ 'onClick': {} }, { label: "Cancel", text: true })));
    var __VLS_443 = __VLS_442.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", text: true })], __VLS_functionalComponentArgsRest(__VLS_442), false));
    var __VLS_446 = void 0;
    var __VLS_447 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.deletePermissionDialog = false;
                // @ts-ignore
                [deletePermissionDialog, deletePermissionDialog, permissionToDelete,];
            } });
    var __VLS_444;
    var __VLS_445;
    var __VLS_448 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_449 = __VLS_asFunctionalComponent1(__VLS_448, new __VLS_448(__assign({ 'onClick': {} }, { label: "Delete", severity: "danger" })));
    var __VLS_450 = __VLS_449.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Delete", severity: "danger" })], __VLS_functionalComponentArgsRest(__VLS_449), false));
    var __VLS_453 = void 0;
    var __VLS_454 = ({ click: {} },
        { onClick: (__VLS_ctx.deletePermission) });
    var __VLS_451;
    var __VLS_452;
    // @ts-ignore
    [deletePermission,];
}
// @ts-ignore
[];
var __VLS_437;
var __VLS_455;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
dialog_1.default;
// @ts-ignore
var __VLS_456 = __VLS_asFunctionalComponent1(__VLS_455, new __VLS_455(__assign(__assign({ visible: (__VLS_ctx.deleteNavigationDialog) }, { style: ({ width: '450px' }) }), { header: "Confirm Delete", modal: (true) })));
var __VLS_457 = __VLS_456.apply(void 0, __spreadArray([__assign(__assign({ visible: (__VLS_ctx.deleteNavigationDialog) }, { style: ({ width: '450px' }) }), { header: "Confirm Delete", modal: (true) })], __VLS_functionalComponentArgsRest(__VLS_456), false));
var __VLS_460 = __VLS_458.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-4" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-exclamation-triangle text-4xl text-orange-500" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-exclamation-triangle']} */ ;
/** @type {__VLS_StyleScopedClasses['text-4xl']} */ ;
/** @type {__VLS_StyleScopedClasses['text-orange-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.b, __VLS_intrinsics.b)({});
((_b = __VLS_ctx.navigationToDelete) === null || _b === void 0 ? void 0 : _b.display_name);
{
    var __VLS_461 = __VLS_458.slots.footer;
    var __VLS_462 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_463 = __VLS_asFunctionalComponent1(__VLS_462, new __VLS_462(__assign({ 'onClick': {} }, { label: "Cancel", text: true })));
    var __VLS_464 = __VLS_463.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", text: true })], __VLS_functionalComponentArgsRest(__VLS_463), false));
    var __VLS_467 = void 0;
    var __VLS_468 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.deleteNavigationDialog = false;
                // @ts-ignore
                [deleteNavigationDialog, deleteNavigationDialog, navigationToDelete,];
            } });
    var __VLS_465;
    var __VLS_466;
    var __VLS_469 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_470 = __VLS_asFunctionalComponent1(__VLS_469, new __VLS_469(__assign({ 'onClick': {} }, { label: "Delete", severity: "danger" })));
    var __VLS_471 = __VLS_470.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Delete", severity: "danger" })], __VLS_functionalComponentArgsRest(__VLS_470), false));
    var __VLS_474 = void 0;
    var __VLS_475 = ({ click: {} },
        { onClick: (__VLS_ctx.deleteNavigation) });
    var __VLS_472;
    var __VLS_473;
    // @ts-ignore
    [deleteNavigation,];
}
// @ts-ignore
[];
var __VLS_458;
// @ts-ignore
var __VLS_278 = __VLS_277;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
