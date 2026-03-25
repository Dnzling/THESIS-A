<template>
    <!-- Unified Carousel with New Arrival, Trending, and Most Viewed -->
    <div class="carousel-section">
        <Carousel :value="carouselItems" :numVisible="1" :numScroll="1" :responsiveOptions="responsiveOptions"
            class="custom-carousel">
            <template #item="slotProps">
                <div class="relative h-52 md:h-80 rounded-2xl md:rounded-3xl overflow-hidden shadow-lg">
                    <img :src="slotProps.data.image" :alt="slotProps.data.title"
                        class="w-full h-full object-cover transition-opacity duration-500" />
                    <div class="absolute inset-0 bg-linear-to-r from-black/70 to-transparent flex items-center">
                        <div class="pl-4 pr-3 md:pl-16 text-white">
                            <span :class="[
                      'inline-block px-3 py-1 rounded-full text-xs font-bold uppercase tracking-widest mb-4 backdrop-blur-md',
                      slotProps.data.badgeClass
                    ]">
                                {{ slotProps.data.badge }}
                            </span>
                            <h1 class="text-2xl sm:text-3xl md:text-6xl font-extrabold tracking-tight">{{
                                slotProps.data.title }}</h1>
                            <p class="mt-2 md:mt-4 text-sm md:text-lg text-slate-200 max-w-md">{{ slotProps.data.description
                                }}</p>
                            <Button @click="navigateTo(slotProps.data.route)" :label="slotProps.data.buttonText"
                                :severity="slotProps.data.title === 'New Arrival' ? 'info' : slotProps.data.title === 'Trending' ? 'warn' : 'help'"
                                :class="[ 
        'mt-4 md:mt-6 px-6 md:px-8 py-2 md:py-3',
        slotProps.data.buttonClass
      ]" />
                        </div>
                    </div>
                </div>
            </template>
        </Carousel>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import Carousel from 'primevue/carousel'

const router = useRouter()

// Responsive options for carousel
const responsiveOptions = ref([
  {
    breakpoint: '1400px',
    numVisible: 1,
    numScroll: 1
  },
  {
    breakpoint: '1199px',
    numVisible: 1,
    numScroll: 1
  },
  {
    breakpoint: '767px',
    numVisible: 1,
    numScroll: 1
  }
])

// Carousel items combining New Arrival, Trending, and Most Viewed
const carouselItems = ref([
  {
    id: 1,
    image: 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1900&q=80',
    title: 'New Arrival',
    badge: 'New Collection 2026',
    badgeClass: 'bg-sky-500/20 text-sky-300',
    description: 'Discover the latest furniture that blends comfort with contemporary aesthetics.',
    buttonText: 'Shop New Arrivals',
    buttonClass: 'bg-sky-500 hover:bg-sky-600',
    route: 'ecommerce.new-arrival.view'
  },
  {
    id: 2,
    image: 'https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?auto=format&fit=crop&w=1900&q=80',
    title: 'Trending',
    badge: 'Hottest Picks',
    badgeClass: 'bg-orange-500/20 text-orange-300',
    description: 'Most popular furniture pieces loved by our customers this season.',
    buttonText: 'View Trending',
    buttonClass: 'bg-orange-500 hover:bg-orange-600',
    route: 'ecommerce.trending.view'
  },
  {
    id: 3,
    image: 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?auto=format&fit=crop&w=1900&q=80',
    title: 'Most Viewed',
    badge: 'Customer Favorites',
    badgeClass: 'bg-purple-500/20 text-purple-300',
    description: 'The most popular furniture pieces our customers can\'t stop looking at.',
    buttonText: 'Explore Most Viewed',
    buttonClass: 'bg-purple-500 hover:bg-purple-600',
    route: 'ecommerce.most-viewed.view'
  }
])

// Navigation function
const navigateTo = (route) => {
  router.push({ name: route })
}
</script>

<style scoped>
.carousel-section {
    margin-bottom: 2rem;
}

:deep(.p-carousel .p-carousel-content) {
    position: relative;
}

:deep(.p-carousel .p-carousel-prev),
:deep(.p-carousel .p-carousel-next) {
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    color: white;
    border-radius: 50%;
    width: 2rem;
    height: 2rem;
    transition: all 0.3s ease;
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    z-index: 10;
}

:deep(.p-carousel .p-carousel-prev) {
    left: 1rem;
}

:deep(.p-carousel .p-carousel-next) {
    right: 1rem;
}

:deep(.p-carousel .p-carousel-prev:hover),
:deep(.p-carousel .p-carousel-next:hover) {
    background: rgba(0, 0, 0, 0.7);
    transform: translateY(-50%) scale(1.1);
}

:deep(.p-carousel .p-carousel-indicators) {
    margin-top: 1rem;
}

:deep(.p-carousel .p-carousel-indicator button) {
    background-color: #d1d5db;
    transition: all 0.3s ease;
    width: 0.5rem;
    height: 0.5rem;
    border-radius: 50%;
}

:deep(.p-carousel .p-carousel-indicator.p-highlight button) {
    width: 1.5rem;
    border-radius: 0.25rem;
}

:deep(.p-carousel .p-carousel-indicator:first-child.p-highlight button) {
    background-color: #0ea5e9;
}

:deep(.p-carousel .p-carousel-indicator:nth-child(2).p-highlight button) {
    background-color: #f97316;
}

:deep(.p-carousel .p-carousel-indicator:nth-child(3).p-highlight button) {
    background-color: #a855f7;
}
</style>