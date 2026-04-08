import { reactive } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

declare const route: any;

const routeNameAliases: Record<string, string> = {
    'inventory.products': 'inventory.products.index',
    'inventory.products.create': 'inventory.products.index',
    'inventory.locations': 'inventory.locations.index',
    'inventory.serial-numbers': 'inventory.serial-numbers.index',
    'inventory.serial-numbers.detail': 'inventory.serial-numbers.show',
    'inventory.stock-counts': 'inventory.stock-counts.index',
    'inventory.warehouses': 'inventory.warehouses.index',
    'supplier-detail': 'admin.suppliers.detail',
};

const resolveToUrl = (to: any) => {
    if (typeof to === 'string') return to;
    if (!to) return window.location.pathname;
    if (to.name && typeof route === 'function') {
        const params = to.params || {};
        const query = to.query || {};
        const resolvedName = routeNameAliases[to.name] || to.name;
        try {
            return route(resolvedName, { ...params, _query: query });
        } catch (error) {
            console.warn('Route name resolution failed:', resolvedName, error);
        }
    }
    if (to.path) return to.path;
    return window.location.pathname;
};

export const useRouter = () => {
    return {
        push: (to: any) => router.visit(resolveToUrl(to)),
        replace: (to: any) => router.visit(resolveToUrl(to), { replace: true }),
        back: () => window.history.back(),
    };
};

export const useRoute = () => {
    const page = usePage();
    return reactive({
        get params() {
            return (page.props.routeParams || page.props.params || {}) as Record<string, any>;
        },
        get query() {
            return (page.props.query || {}) as Record<string, any>;
        },
        get name() {
            return (page.props.routeName || null) as string | null;
        },
        get path() {
            return page.url || '/';
        },
        get fullPath() {
            return page.url || '/';
        },
    });
};
