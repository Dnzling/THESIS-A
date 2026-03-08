// main.ts or main.js
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { useAuthStore } from './stores/auth' // ✅ Uncommented
import './style.css'
import PrimeVue from 'primevue/config'
import Aura from '@primeuix/themes/aura'
import 'primeicons/primeicons.css'
import App from './App.vue'
import router from './router'
import axios from 'axios'

import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'
import Checkbox from 'primevue/checkbox'
import Message from 'primevue/message'
import Dialog from 'primevue/dialog'
import ProgressSpinner from 'primevue/progressspinner'
import ToastService from 'primevue/toastservice'
import ConfirmationService from 'primevue/confirmationservice'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Badge from 'primevue/badge'
import DatePicker from 'primevue/datepicker'
import Tag from 'primevue/tag'
import Select from 'primevue/select'
import Avatar from 'primevue/avatar'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Row from 'primevue/row'
import RadioButton from 'primevue/radiobutton'
import Tabs from 'primevue/tabs'
import TabList from 'primevue/tablist'
import Tab from 'primevue/tab'
import TabPanels from 'primevue/tabpanels'
import TabPanel from 'primevue/tabpanel'
import InputNumber from 'primevue/inputnumber'
import Tooltip from 'primevue/tooltip'
import Textarea from 'primevue/textarea'
import ScrollTop from 'primevue/scrolltop'
import Skeleton from 'primevue/skeleton'
import Paginator from 'primevue/paginator'
import FileUpload from 'primevue/fileupload'
import Tree from 'primevue/tree'






// ==================== AXIOS CONFIGURATION ====================
axios.defaults.baseURL = 'http://localhost:8000'
axios.defaults.withCredentials = true
axios.defaults.withXSRFToken = true

// ==================== DUPLICATE REQUEST PREVENTION ====================
const pendingRequests = new Map<string, AbortController>()

const generateRequestKey = (config: any): string => {
  // ✅ Deduplicate ALL requests (not just GET)
  return `${config.method?.toUpperCase()}:${config.url}:${JSON.stringify(config.data ?? config.params ?? {})}`
}

axios.interceptors.request.use((config) => {
  const key = generateRequestKey(config)

  if (pendingRequests.has(key)) {
    console.warn('⚠️ Duplicate request detected:', key)
    const controller = new AbortController()
    controller.abort()
    config.signal = controller.signal
  } else {
    const controller = new AbortController()
    pendingRequests.set(key, controller)
    config.signal = controller.signal
  }

  return config
})

axios.interceptors.response.use(
  (response) => {
    const key = generateRequestKey(response.config)
    pendingRequests.delete(key)
    return response
  },
  (error) => {
    if (error.config) {
      const key = generateRequestKey(error.config)
      pendingRequests.delete(key)
    }

    if (error.name === 'CanceledError' || error.code === 'ERR_CANCELED') {
      console.log('🚫 Duplicate request cancelled:', error.config?.url)
      return new Promise(() => { })
    }

    return Promise.reject(error)
  }
)

// ==================== APP INITIALIZATION ====================
const app = createApp(App)
const pinia = createPinia()

// Install Pinia FIRST (required for auth store)
app.use(pinia)

// Configure PrimeVue
app.use(PrimeVue, {
  theme: {
    preset: Aura,
    options: {
      darkModeSelector: '.my-app-dark',
    }
  }
})

app.use(ToastService)
app.use(ConfirmationService)
app.use(router)

// Register PrimeVue components
const components = {
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
}

Object.entries(components).forEach(([name, component]) => {
  app.component(name, component)
})

app.directive('tooltip', Tooltip)

// ==================== AUTH INITIALIZATION ====================
// Initialize auth store and load permissions BEFORE mounting
const authStore = useAuthStore()

// Set Authorization header if token exists in localStorage
if (authStore.token) {
  axios.defaults.headers.common['Authorization'] = `Bearer ${authStore.token}`
  console.log('🔐 Token restored from localStorage')

  // Initialize permissions (async, but don't wait)
  authStore.initialize().catch(err => {
    console.warn('⚠️ Failed to initialize permissions:', err)
  })
}

// ✅ Mount immediately (don't wait for initialize)
app.mount('#app')