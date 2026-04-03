import '../css/app.css';
import './bootstrap';
import './axios';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { createPinia } from 'pinia';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import EcommerceLayout from '@/Layouts/EcommerceLayout.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimeVue from 'primevue/config';
import Aura from '@primeuix/themes/aura';
import 'primeicons/primeicons.css';
import ToastService from 'primevue/toastservice';
import ConfirmationService from 'primevue/confirmationservice';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { useAuthStore } from '@/stores/auth';
import axios from 'axios';

import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import Message from 'primevue/message';
import Dialog from 'primevue/dialog';
import ProgressSpinner from 'primevue/progressspinner';
import DatePicker from 'primevue/datepicker';
import InputSwitch from 'primevue/inputswitch';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Accordion from 'primevue/accordion';
import AccordionTab from 'primevue/accordiontab';
import MultiSelect from 'primevue/multiselect';
import Select from 'primevue/select';
import InputNumber from 'primevue/inputnumber';
import InputMask from 'primevue/inputmask';
import Textarea from 'primevue/textarea';
import Toast from 'primevue/toast';
import Badge from 'primevue/badge';
import Tag from 'primevue/tag';
import Avatar from 'primevue/avatar';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Row from 'primevue/row';
import RadioButton from 'primevue/radiobutton';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabView from 'primevue/tabview';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import Tooltip from 'primevue/tooltip';
import ScrollTop from 'primevue/scrolltop';
import Skeleton from 'primevue/skeleton';
import Paginator from 'primevue/paginator';
import FileUpload from 'primevue/fileupload';
import Tree from 'primevue/tree';
import Divider from 'primevue/divider';
import Steps from 'primevue/steps';
import ProgressBar from 'primevue/progressbar';
import ConfirmDialog from 'primevue/confirmdialog';
import PrimeChart from 'primevue/chart';
import MeterGroup from 'primevue/metergroup';
import InputOtp from 'primevue/inputotp';
import Menu from 'primevue/menu';
import Timeline from 'primevue/timeline';
import ColorPicker from 'primevue/colorpicker';
import SelectButton from 'primevue/selectbutton';
import Carousel from 'primevue/carousel';
import OverlayPanel from 'primevue/overlaypanel';
import Popover from 'primevue/popover';
import IftaLabel  from 'primevue/iftalabel';
import FloatLabel from 'primevue/floatlabel';
import { Link, router as inertiaRouter } from '@inertiajs/vue3';

const appName = import.meta.env.VITE_APP_NAME || 'Furnisync';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ).then((module) => {
            const component = module.default;
            if (!component.layout) {
                if (name.startsWith('System/Admin/')) {
                    component.layout = AdminLayout;
                } else if (name.startsWith('System/Ecommerce/')) {
                    component.layout = EcommerceLayout;
                } else if (
                    name.startsWith('Auth/') ||
                    name.startsWith('Profile/') ||
                    name.startsWith('System/HR/JobPortal/') ||
                    name.startsWith('System/HR/Applicant/')
                ) {
                    component.layout = GuestLayout;
                } else if (name.startsWith('System/')) {
                    component.layout = SystemLayout;
                }
            }
            return module;
        }),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        const pinia = createPinia();

        app.use(plugin);
        app.use(ZiggyVue);
        app.use(pinia);
        app.use(PrimeVue, {
            theme: {
                preset: Aura,
                options: {
                    darkModeSelector: '.my-app-dark',
                },
            },
        });
        app.use(ToastService);
        app.use(ConfirmationService);

        const components = {
            IftaLabel,
            FloatLabel,
            Carousel,
            SelectButton,
            ColorPicker,
            Timeline,
            TabView,
            MeterGroup,
            PrimeChart,
            Menu,
            InputMask,
            InputOtp,
            ConfirmDialog,
            ProgressBar,
            Steps,
            MultiSelect,
            Toast,
            Divider,
            Tree,
            FileUpload,
            Paginator,
            Skeleton,
            ScrollTop,
            Tabs,
            Tab,
            TabList,
            TabPanels,
            TabPanel,
            InputNumber,
            Card,
            Avatar,
            InputText,
            Textarea,
            InputIcon,
            Password,
            Button,
            Checkbox,
            Message,
            Dialog,
            ProgressSpinner,
            DataTable,
            Column,
            Row,
            Badge,
            DatePicker,
            Tag,
            Select,
            IconField,
            RadioButton,
            Accordion,
            AccordionTab,
            InputSwitch,
            OverlayPanel,
            Popover,
        };

        Object.entries(components).forEach(([name, component]) => {
            app.component(name, component);
        });

        const routeNameAliases = {
            'inventory.products': 'inventory.products.index',
            'inventory.products.create': 'inventory.products.index',
            'inventory.locations': 'inventory.locations.index',
            'inventory.serial-numbers': 'inventory.serial-numbers.index',
            'inventory.serial-numbers.detail': 'inventory.serial-numbers.show',
            'inventory.stock-counts': 'inventory.stock-counts.index',
            'inventory.warehouses': 'inventory.warehouses.index',
            'supplier-detail': 'admin.suppliers.detail',
        };

        const resolveToUrl = (to) => {
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

        const RouterLinkCompat = {
            name: 'RouterLinkCompat',
            props: {
                to: {
                    type: [String, Object],
                    default: '',
                },
                href: {
                    type: [String, Object],
                    default: '',
                },
                method: {
                    type: String,
                    default: 'get',
                },
            },
            render() {
                const target = this.href || this.to;
                return h(
                    Link,
                    {
                        ...this.$attrs,
                        href: resolveToUrl(target),
                        method: this.method,
                    },
                    this.$slots,
                );
            },
        };

        app.component('RouterLink', RouterLinkCompat);
        app.component('router-link', RouterLinkCompat);
        app.component('RouterView', {
            name: 'RouterView',
            render() {
                return this.$slots.default ? this.$slots.default() : null;
            },
        });
        app.component('router-view', {
            name: 'router-view',
            render() {
                return this.$slots.default ? this.$slots.default() : null;
            },
        });

        app.directive('tooltip', Tooltip);

        app.config.globalProperties.$router = {
            push: (to) => inertiaRouter.visit(resolveToUrl(to)),
            replace: (to) => inertiaRouter.visit(resolveToUrl(to), { replace: true }),
            back: () => window.history.back(),
        };

        const authStore = useAuthStore(pinia);
        if (authStore.token) {
            axios.defaults.headers.common.Authorization = `Bearer ${authStore.token}`;
            console.log('Auth token initialized:', authStore.token);
            if (authStore.user) {
                authStore.initialize().catch((err) => {
                    console.warn('Failed to initialize auth store:', err);
                });
            } else {
                authStore.fetchCurrentUser().catch((err) => {
                    console.warn('Failed to hydrate user:', err);
                });
            }
        }

        return app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
