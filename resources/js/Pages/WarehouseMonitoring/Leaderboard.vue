<template>
    <MainAppLayout>
        <div class="max-w-7xl mx-auto">
            <!-- Filters Section -->
            <div class=" mb-6">
                <button
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm "
                    @click="showMobileFilters = !showMobileFilters">
                    <span>{{ showMobileFilters ? 'Hide Filters' : 'Show Filters' }}</span>
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 4h18M4 8h16M6 12h12m-8 4h4m-6 4h8" />
                    </svg>
                </button>
                <section class="w-full">
                    <div
                        :class="['w-full rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100 sm:p-6', showMobileFilters ? 'block' : 'hidden']">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Select Date</label>
                                <input type="date" v-model="localFilters.date" 
                                    @change="onDateChange"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200" />
                                <p v-if="isWeekendSelected" class="mt-1 text-xs text-red-600">
                                    Weekend is disabled – please select a weekday.
                                </p>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Month</label>
                                <input v-model="localFilters.month" type="month" @change="debouncedApplyFilters"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200" />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">PIC</label>
                                <select v-model="localFilters.pic_name" @change="debouncedApplyFilters"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                                    <option value="">All PICs</option>
                                    <option v-for="pic in uniquePICs" :key="pic" :value="pic">{{ pic }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Usage</label>
                                <select v-model="localFilters.usage" @change="debouncedApplyFilters"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                                    <option value="">All</option>
                                    <option value="DAILY">Daily</option>
                                    <option value="WEEKLY">Weekly</option>
                                    <option value="MONTHLY">Monthly</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Location</label>
                                <select v-model="localFilters.location" @change="debouncedApplyFilters"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                                    <option value="">All</option>
                                    <option value="SUNTER_1">Sunter 1</option>
                                    <option value="SUNTER_2">Sunter 2</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Gentan-I & Non
                                    Gentan-I</label>
                                <select v-model="localFilters.gentani" @change="debouncedApplyFilters"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                                    <option value="">All</option>
                                    <option value="GENTAN-I">Gentan-I</option>
                                    <option value="NON_GENTAN-I">Non Gentan-I</option>
                                </select>
                            </div>
                            <div class="flex flex-wrap items-end gap-3 sm:col-span-2 xl:col-span-1">
                                <button @click="applyFilters" :disabled="isLoading"
                                    class="flex-1 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-md transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60">
                                    {{ isLoading ? 'Loading...' : 'Apply' }}
                                </button>
                                <button @click="clearFilters" :disabled="isLoading"
                                    class="flex-1 rounded-lg border border-gray-200 bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-200 disabled:cursor-not-allowed disabled:opacity-60">
                                    Clear
                                </button>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Tabs Container -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <!-- Tab Headers -->
                <div class="border-b border-gray-200 bg-gray-50">
                    <nav class="flex -mb-px overflow-x-auto">
                        <button v-for="tab in tabs" :key="tab.id" @click="switchTab(tab.id)" :disabled="isLoading"
                            :class="[
                                'flex-1 min-w-[150px] py-4 px-6 text-center border-b-2 font-medium text-sm transition whitespace-nowrap disabled:opacity-50',
                                currentTab === tab.id
                                    ? `border-${tab.color}-500 text-${tab.color}-600 bg-white`
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            ]">
                            <div class="flex items-center justify-center gap-2">
                                <span>{{ tab.label }}</span>
                                <span :class="[
                                    'ml-2 px-2 py-1 text-xs rounded-full font-semibold',
                                    currentTab === tab.id
                                        ? `bg-${tab.color}-100 text-${tab.color}-700`
                                        : 'bg-gray-100 text-gray-600'
                                ]">
                                    {{ getTabCount(tab.id) }}
                                </span>
                            </div>
                        </button>
                    </nav>
                </div>

                <!-- Tab Content -->
                <div class="p-4 sm:p-6 relative">
                    <!-- Loading Overlay -->
                    <div v-if="isLoading"
                        class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-10">
                        <div class="text-center">
                            <div
                                class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-3">
                            </div>
                            <p class="text-sm text-gray-600 font-medium">Loading data...</p>
                        </div>
                    </div>

                    <!-- CAUTION Tab -->
                    <div v-show="currentTab === 'CAUTION'">
                        <CautionOverdueLeaderboard size="full" :initialLeaderboard="cautionData?.data || []"
                            :initialStatistics="cautionData?.statistics || {}"
                            :initialPagination="cautionData?.pagination || {}" :viewAllUrl="viewAllUrls.CAUTION"
                            @page-change="(page) => handlePageChange('CAUTION', page)"
                            @refresh="() => handleRefresh('CAUTION')" />
                    </div>

                    <!-- SHORTAGE Tab -->
                    <div v-show="currentTab === 'SHORTAGE'">
                        <ShortageOverdueLeaderboard size="full" :initialLeaderboard="shortageData?.data || []"
                            :initialStatistics="shortageData?.statistics || {}"
                            :initialPagination="shortageData?.pagination || {}" :viewAllUrl="viewAllUrls.SHORTAGE"
                            @page-change="(page) => handlePageChange('SHORTAGE', page)"
                            @refresh="() => handleRefresh('SHORTAGE')" />
                    </div>
                </div>
            </div>
        </div>
    </MainAppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import MainAppLayout from '@/Layouts/MainAppLayout.vue'
import CautionOverdueLeaderboard from '@/Components/CautionOverdueLeaderboard.vue'
import ShortageOverdueLeaderboard from '@/Components/ShortageOverdueLeaderboard.vue'
import Filterbar from '@/Components/Filterbar.vue'

const props = defineProps({
    cautionData: {
        type: Object,
        required: true
    },
    shortageData: {
        type: Object,
        required: true
    },
    activeTab: {
        type: String,
        default: 'CAUTION'
    },
    filters: {
        type: Object,
        default: () => ({})
    }
})

// Use computed to always get fresh props
const cautionData = computed(() => props.cautionData)
const shortageData = computed(() => props.shortageData)

const currentTab = ref(props.activeTab)
const isLoading = ref(false)
const showMobileFilters = ref(false)

const localFilters = ref({
    date: props.filters.date || '',
    month: props.filters.month || '',
    usage: props.filters.usage || '',
    location: props.filters.location || '',
    gentani: props.filters.gentani || '',
    pic_name: props.filters.pic_name || ''
})

const tabs = [
    { id: 'CAUTION', label: 'Caution', color: 'orange' },
    { id: 'SHORTAGE', label: 'Shortage', color: 'red' },
]

const viewAllUrls = {
    CAUTION: route('warehouse-monitoring.leaderboard', { tab: 'CAUTION' }),
    SHORTAGE: route('warehouse-monitoring.leaderboard', { tab: 'SHORTAGE' })
}

const uniquePICs = [
    "ADE N", "AKBAR", "ANWAR", "BAHTIYAR", "DEDHI",
    "EKA S", "EKO P", "FAHRI", "IBNU", "IRPANDI",
    "IRVAN", "MIKS", "RACHMAT", "ZAINAL A."
]

const usages = ['DAILY', 'WEEKLY', 'MONTHLY']

const getTabCount = (tabId) => {
    switch (tabId) {
        case 'CAUTION':
            return cautionData.value?.statistics?.total || 0
        case 'SHORTAGE':
            return shortageData.value?.statistics?.total || 0
        default:
            return 0
    }
}

const switchTab = (tabId) => {
    if (isLoading.value || currentTab.value === tabId) return
    currentTab.value = tabId

    // Update URL without reload using Inertia
    router.get(
        route('warehouse-monitoring.leaderboard'),
        {
            tab: tabId,
            ...localFilters.value,
            page: 1  // Reset to page 1 when switching tabs
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['cautionData', 'shortageData', 'activeTab'],
            onStart: () => { isLoading.value = true },
            onFinish: () => { isLoading.value = false }
        }
    )
}

const applyFilters = () => {
    if (isLoading.value) return

    router.get(
        route('warehouse-monitoring.leaderboard'),
        {
            tab: currentTab.value,
            ...localFilters.value,
            page: 1  // Reset to page 1 when applying filters
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['cautionData', 'shortageData'],
            onStart: () => { isLoading.value = true },
            onFinish: () => { isLoading.value = false }
        }
    )
}

const clearFilters = () => {
    localFilters.value = {
        date: '',
        month: '',
        usage: '',
        location: '',
        gentani: '',
        pic_name: ""
    }
    applyFilters()
}

/**
 * Handle page change from child component
 *
 */
const handlePageChange = (tab, page) => {
    if (isLoading.value) return

    console.log(`Changing to page ${page} for ${tab}`) // Debug log

    router.get(
        route('warehouse-monitoring.leaderboard'),
        {
            tab: tab,
            ...localFilters.value,
            page: page,  // ← THIS IS THE KEY! Pass the page number
            per_page: 10
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['cautionData', 'shortageData'],  // Only reload data
            onStart: () => {
                isLoading.value = true
                console.log('Starting page load...') // Debug
            },
            onSuccess: (page) => {
                console.log('Page loaded successfully', page.props) // Debug
                currentTab.value = tab
            },
            onFinish: () => {
                isLoading.value = false
                console.log('Page load finished') // Debug
            },
            onError: (errors) => {
                console.error('Error loading page:', errors) // Debug
            }
        }
    )
}

/**
 * Handle refresh from child component
 */
const handleRefresh = (tab) => {
    const currentPage = tab === 'CAUTION'
        ? (cautionData.value?.pagination?.current_page || 1)
        : (shortageData.value?.pagination?.current_page || 1)

    handlePageChange(tab, currentPage)
}
</script>
