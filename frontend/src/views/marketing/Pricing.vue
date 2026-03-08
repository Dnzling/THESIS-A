<template>
  <div class="min-h-screen bg-linear-to-b from-gray-50 to-white">
    <!-- Navigation -->
    <TopNav />
    
    <!-- Hero Section -->
    <section class="pt-20 pb-16 px-4 sm:px-6 lg:px-8">
      <div class="max-w-7xl mx-auto text-center">
        <div class="inline-flex items-center px-4 py-2 bg-blue-100 text-blue-700 rounded-full text-sm font-semibold mb-6">
          <i class="pi pi-tag mr-2"></i>
          Simple & Transparent Pricing
        </div>
        
        <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-gray-900 mb-6">
          Choose Your
          <span class="text-transparent bg-clip-text bg-linear-to-r from-blue-600 to-purple-600">
            Furniture Management Plan
          </span>
        </h1>
        
        <p class="text-xl text-gray-600 max-w-3xl mx-auto mb-10">
          Start for free, upgrade when you need. No hidden fees, cancel anytime.
          Perfect for businesses of all sizes.
        </p>
        
        <!-- Toggle: Monthly/Yearly -->
        <div class="flex items-center justify-center space-x-4 mb-12">
          <span class="text-gray-700 font-medium">Monthly</span>
          <InputSwitch 
            v-model="isYearly" 
            @change="toggleBillingCycle"
          />
          <div class="flex items-center">
            <span class="text-gray-700 font-medium">Yearly</span>
            <span class="ml-2 px-2 py-1 bg-green-100 text-green-700 text-xs font-semibold rounded">
              Save 20%
            </span>
          </div>
        </div>
      </div>
    </section>

    <!-- Pricing Cards -->
    <section class="py-12 px-4 sm:px-6 lg:px-8">
      <div class="max-w-6xl mx-auto">
        <div class="grid md:grid-cols-2 gap-8 lg:gap-12">
          <!-- Simple Plan -->
          <Card class="border-2 border-gray-200 hover:border-blue-300 transition-all duration-300">
            <template #header>
              <div class="text-center p-8">
                <div class="w-16 h-16 mx-auto bg-blue-100 rounded-xl flex items-center justify-center mb-6">
                  <i class="pi pi-box text-blue-600 text-2xl"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Simple</h3>
                <p class="text-gray-600">Perfect for small furniture stores</p>
              </div>
            </template>
            
            <template #content>
              <div class="p-6 pt-0">
                <!-- Price -->
                <div class="text-center mb-8">
                  <div class="flex items-center justify-center">
                    <span class="text-5xl font-bold text-gray-900">₱{{ simplePrice }}</span>
                    <span class="text-gray-500 ml-2">/{{ billingPeriod }}</span>
                  </div>
                  <p class="text-gray-500 mt-2">Billed {{ isYearly ? 'yearly' : 'monthly' }}</p>
                </div>

                <!-- Features -->
                <div class="space-y-4 mb-8">
                  <div v-for="feature in simpleFeatures" :key="feature" class="flex items-center">
                    <i class="pi pi-check text-green-500 mr-3"></i>
                    <span class="text-gray-700">{{ feature }}</span>
                  </div>
                  
                  <div class="flex items-center text-gray-400">
                    <i class="pi pi-times mr-3"></i>
                    <span class="line-through">Advanced Analytics</span>
                  </div>
                  <div class="flex items-center text-gray-400">
                    <i class="pi pi-times mr-3"></i>
                    <span class="line-through">Custom API Access</span>
                  </div>
                </div>

                <!-- CTA Button -->
                <Button 
                  label="Get Started Free" 
                  class="w-full bg-linear-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 border-none"
                  @click="selectPlan('simple')"
                >
                  <template #icon>
                    <i class="pi pi-rocket mr-2"></i>
                  </template>
                </Button>
              </div>
            </template>
            
            <template #footer>
              <div class="text-center p-4 border-t border-gray-100">
                <p class="text-sm text-gray-500">
                  <i class="pi pi-shield mr-1"></i>
                  14-day free trial included
                </p>
              </div>
            </template>
          </Card>

          <!-- Unlimited Plan (Featured) -->
          <Card class="border-2 border-purple-300 shadow-xl relative overflow-hidden">
            <!-- Featured Badge -->
            <div class="absolute top-0 right-0">
              <div class="bg-linear-to-r from-purple-600 to-pink-600 text-white px-6 py-2 transform rotate-45 translate-x-8 translate-y-4">
                <span class="text-sm font-bold">MOST POPULAR</span>
              </div>
            </div>
            
            <template #header>
              <div class="text-center p-8">
                <div class="w-16 h-16 mx-auto bg-linear-to-r from-purple-100 to-pink-100 rounded-xl flex items-center justify-center mb-6">
                  <i class="pi pi-infinity text-purple-600 text-2xl"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Unlimited</h3>
                <p class="text-gray-600">For growing furniture businesses</p>
              </div>
            </template>
            
            <template #content>
              <div class="p-6 pt-0">
                <!-- Price -->
                <div class="text-center mb-8">
                  <div class="flex items-center justify-center">
                    <span class="text-5xl font-bold text-gray-900">₱{{ unlimitedPrice }}</span>
                    <span class="text-gray-500 ml-2">/{{ billingPeriod }}</span>
                  </div>
                  <p class="text-gray-500 mt-2">Billed {{ isYearly ? 'yearly' : 'monthly' }}</p>
                  <div v-if="isYearly" class="mt-2">
                    <span class="text-green-600 font-semibold">
                      <i class="pi pi-check-circle mr-1"></i>
                      Save ₱{{ unlimitedYearlySavings }} yearly
                    </span>
                  </div>
                </div>

                <!-- Features -->
                <div class="space-y-4 mb-8">
                  <div v-for="feature in unlimitedFeatures" :key="feature" class="flex items-center">
                    <i class="pi pi-check text-green-500 mr-3"></i>
                    <span class="text-gray-700">{{ feature }}</span>
                  </div>
                </div>

                <!-- CTA Button -->
                <Button 
                  label="Start Unlimited Trial" 
                  severity="secondary"
                  class="w-full bg-linear-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 border-none text-white"
                  @click="selectPlan('unlimited')"
                >
                  <template #icon>
                    <i class="pi pi-star mr-2"></i>
                  </template>
                </Button>
              </div>
            </template>
            
            <template #footer>
              <div class="text-center p-4 border-t border-gray-100">
                <p class="text-sm text-gray-500">
                  <i class="pi pi-crown mr-1"></i>
                  Includes all features + priority support
                </p>
              </div>
            </template>
          </Card>
        </div>
      </div>
    </section>

    <!-- Comparison Table -->
    <section class="py-20 px-4 sm:px-6 lg:px-8 bg-gray-50">
      <div class="max-w-5xl mx-auto">
        <h2 class="text-3xl font-bold text-center text-gray-900 mb-12">
          Compare Plans Side by Side
        </h2>
        
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
          <DataTable :value="comparisonFeatures" class="p-datatable-sm">
            <Column field="feature" header="Feature" headerClass="font-bold">
              <template #body="{ data }">
                <span class="font-medium">{{ data.feature }}</span>
              </template>
            </Column>
            <Column header="Simple" headerClass="text-center">
              <template #body="{ data }">
                <div class="flex justify-center">
                  <i v-if="data.simple" class="pi pi-check text-green-500 text-lg"></i>
                  <i v-else class="pi pi-times text-gray-300 text-lg"></i>
                </div>
              </template>
            </Column>
            <Column header="Unlimited" headerClass="text-center">
              <template #body="{ data }">
                <div class="flex justify-center">
                  <i v-if="data.unlimited" class="pi pi-check text-green-500 text-lg"></i>
                  <i v-else class="pi pi-times text-gray-300 text-lg"></i>
                </div>
              </template>
            </Column>
          </DataTable>
        </div>
      </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-20 px-4 sm:px-6 lg:px-8">
      <div class="max-w-4xl mx-auto">
        <h2 class="text-3xl font-bold text-center text-gray-900 mb-12">
          Frequently Asked Questions
        </h2>
        
        <div class="space-y-6">
          <Accordion :activeIndex="0">
            <AccordionTab header="Can I switch plans anytime?">
              <p class="text-gray-600">
                Yes! You can upgrade or downgrade your plan at any time. When upgrading, you'll get immediate access to new features. When downgrading, changes take effect at the end of your billing cycle.
              </p>
            </AccordionTab>
            
            <AccordionTab header="Is there a free trial?">
              <p class="text-gray-600">
                Absolutely! Both plans come with a 14-day free trial. No credit card required to start. You'll get full access to all features during the trial period.
              </p>
            </AccordionTab>
            
            <AccordionTab header="What payment methods do you accept?">
              <p class="text-gray-600">
                We accept all major credit cards (Visa, MasterCard, American Express), PayPal, and bank transfers for annual plans. All payments are processed securely through Stripe.
              </p>
            </AccordionTab>
            
            <AccordionTab header="Can I cancel anytime?">
              <p class="text-gray-600">
                Yes, you can cancel your subscription anytime. There are no long-term contracts. If you cancel, you'll have access until the end of your billing period.
              </p>
            </AccordionTab>
          </Accordion>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 px-4 sm:px-6 lg:px-8 bg-linear-to-r from-blue-600 to-purple-600">
      <div class="max-w-4xl mx-auto text-center">
        <h2 class="text-3xl font-bold text-white mb-6">
          Ready to Transform Your Furniture Business?
        </h2>
        <p class="text-xl text-blue-100 mb-10">
          Join 1,000+ furniture stores already using our platform
        </p>
        
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <Button 
            label="Start Free Trial" 
            severity="secondary"
            class="px-8 py-3 bg-white text-blue-600 font-bold hover:bg-gray-100"
            @click="startFreeTrial"
          >
            <template #icon>
              <i class="pi pi-play mr-2"></i>
            </template>
          </Button>
          
          <Button 
            label="Schedule a Demo" 
            outlined
            class="px-8 py-3 border-2 border-white text-white hover:bg-white/10"
            @click="scheduleDemo"
          >
            <template #icon>
              <i class="pi pi-calendar mr-2"></i>
            </template>
          </Button>
        </div>
        
        <p class="text-blue-200 text-sm mt-8">
          <i class="pi pi-lock mr-1"></i>
          Secure payment • 24/7 support • 30-day money-back guarantee
        </p>
      </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12 px-4 sm:px-6 lg:px-8">
      <div class="max-w-7xl mx-auto">
        <div class="flex flex-col md:flex-row justify-between items-center">
          <div class="mb-6 md:mb-0">
            <div class="text-2xl font-bold mb-2">Furnisync</div>
            <p class="text-gray-400">Professional furniture management solutions</p>
          </div>
          
          <div class="flex space-x-6">
            <a href="#" class="text-gray-400 hover:text-white">
              <i class="pi pi-file text-xl"></i>
            </a>
            <a href="#" class="text-gray-400 hover:text-white">
              <i class="pi pi-book text-xl"></i>
            </a>
            <a href="#" class="text-gray-400 hover:text-white">
              <i class="pi pi-phone text-xl"></i>
            </a>
          </div>
        </div>
        
        <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
          <p>© 2026 Furnisync. All rights reserved.</p>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import TopNav from '../../components/TopNav.vue'
import Card from 'primevue/card'
import Button from 'primevue/button'
import InputSwitch from 'primevue/inputswitch'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Accordion from 'primevue/accordion'
import AccordionTab from 'primevue/accordiontab'

const router = useRouter()
const isYearly = ref(false)

// Pricing
const simpleMonthlyPrice = 1490
const simpleYearlyPrice = 14304 // * 12 * 0.8 (20% discount)
const unlimitedMonthlyPrice = 3500
const unlimitedYearlyPrice = 33600 // * 12 * 0.8

const simplePrice = computed(() => isYearly.value ? simpleYearlyPrice : simpleMonthlyPrice)
const unlimitedPrice = computed(() => isYearly.value ? unlimitedYearlyPrice : unlimitedMonthlyPrice)
const billingPeriod = computed(() => isYearly.value ? 'year' : 'month')

const unlimitedYearlySavings = computed(() => (unlimitedMonthlyPrice * 12) - unlimitedYearlyPrice)

// Features
const simpleFeatures = [
  'Up to 500 furniture items',
  'Basic inventory tracking',
  '2 staff accounts',
  'Email support',
  'Basic reports',
  '1 store location'
]

const unlimitedFeatures = [
  'Unlimited furniture items',
  'Advanced inventory management',
  'Unlimited staff accounts',
  'Priority phone & email support',
  'Advanced analytics & reports',
  'Multiple store locations',
  'Custom API access',
  '3D model integration',
  'Bulk import/export',
  'Custom branding'
]

// Comparison table
const comparisonFeatures = ref([
  { feature: 'Furniture Items', simple: 'Up to 500', unlimited: 'Unlimited' },
  { feature: 'Staff Accounts', simple: '2', unlimited: 'Unlimited' },
  { feature: 'Store Locations', simple: '1', unlimited: 'Multiple' },
  { feature: 'Inventory Tracking', simple: true, unlimited: true },
  { feature: 'Sales Analytics', simple: 'Basic', unlimited: 'Advanced' },
  { feature: '3D Model Integration', simple: false, unlimited: true },
  { feature: 'Custom API Access', simple: false, unlimited: true },
  { feature: 'Priority Support', simple: false, unlimited: true },
  { feature: 'Bulk Operations', simple: false, unlimited: true },
  { feature: 'Custom Branding', simple: false, unlimited: true }
])

// Methods
const toggleBillingCycle = () => {
  console.log(`Switched to ₱{isYearly.value ? 'yearly' : 'monthly'} billing`)
}

const selectPlan = (plan: string) => {
  console.log(`Selected ${plan} plan`)
  if (plan === 'unlimited') {
    // Redirect to signup with plan parameter
    router.push({ 
      path: '/register', 
      query: { plan: 'unlimited' } 
    })
  } else {
    router.push({ 
      path: '/register', 
      query: { plan: 'simple' } 
    })
  }
}

const startFreeTrial = () => {
  router.push('/register')
}

const scheduleDemo = () => {
  // Open calendar scheduling or redirect
  window.open('https://calendly.com/furnimanage/demo', '_blank')
}
</script>

<style scoped>
:deep(.p-card) {
  border-radius: 1rem;
}

:deep(.p-card .p-card-header) {
  padding-bottom: 0;
}

:deep(.p-card .p-card-content) {
  padding: 1.5rem;
}

:deep(.p-button) {
  padding: 0.75rem 1.5rem;
  font-weight: 600;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
  background-color: #f9fafb;
  font-weight: 700;
}

:deep(.p-accordion .p-accordion-header .p-accordion-header-link) {
  padding: 1.25rem;
  font-weight: 600;
}
</style>