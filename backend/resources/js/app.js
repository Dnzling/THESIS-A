import '../css/app.css';
import './bootstrap';
import './axios';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { createPinia } from 'pinia';
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

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
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
            Card,
            InputText,
            Password,
            Button,
            Checkbox,
            Message,
            Dialog,
            ProgressSpinner,
            DatePicker,
            InputSwitch,
            DataTable,
            Column,
            Accordion,
            AccordionTab,
            MultiSelect,
            Select,
            InputNumber,
            InputMask,
            Textarea,
            Toast,
        };

        Object.entries(components).forEach(([name, component]) => {
            app.component(name, component);
        });

        const authStore = useAuthStore(pinia);
        if (authStore.token) {
            axios.defaults.headers.common.Authorization = `Bearer ${authStore.token}`;
            authStore.initialize().catch((err) => {
                console.warn('Failed to initialize auth store:', err);
            });
        }

        return app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
