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
                        <Filterbar v-model:selectedPIC="localFilters.pic_name" :picOptions="uniquePICs"
                            v-model:selectedLocation="localFilters.location" v-model:selectedUsage="localFilters.usage"
                            :usageOptions="usages" v-model:selectedGentani="localFilters.gentani" />
                        <button @click="applyFilters" :disabled="isLoading"
                            class="flex-1 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-md transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60">
                            {{ isLoading ? 'Loading...' : 'Apply' }}
                        </button>
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

                    <!-- Keep both components mounted but toggle visibility -->
                    <div :class="{ 'hidden': currentTab !== 'CAUTION' }">
                        <CautionOverdueLeaderboard v-if="cautionData" size="full"
                            :initialLeaderboard="cautionData.leaderboard || []"
                            :initialStatistics="cautionData.statistics || {}"
                            :initialPagination="cautionData.pagination || {}" :viewAllUrl="viewAllUrls.CAUTION"
                            @page-change="page => handlePageChange('CAUTION', page)"
                            @refresh="() => handleRefresh('CAUTION')" />
                    </div>

                    <div :class="{ 'hidden': currentTab !== 'SHORTAGE' }">
                        <ShortageOverdueLeaderboard v-if="shortageData" size="full"
                            :initialLeaderboard="shortageData.leaderboard || []"
                            :initialStatistics="shortageData.statistics || {}"
                            :initialPagination="shortageData.pagination || {}" :viewAllUrl="viewAllUrls.SHORTAGE"
                            @page-change="page => handlePageChange('SHORTAGE', page)"
                            @refresh="() => handleRefresh('SHORTAGE')" />
                    </div>
                </div>
            </div>
        </div>
    </MainAppLayout>
</template>

<script setup>
import { ref, watch } from 'vue'
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

const currentTab = ref(props.activeTab)
const isLoading = ref(false)
const localFilters = ref({
    date: props.filters.date || '',
    month: props.filters.month || '',
    usage: props.filters.usage || '',
    location: props.filters.location || '',
    gentani: props.filters.gentani || '',
    pic_name: props.filters.pic_name || ''
})

// Cache for data
const cautionData = ref(props.cautionData)
const shortageData = ref(props.shortageData)

watch(
    () => props.cautionData,
    (newVal) => {
        if (newVal) cautionData.value = newVal
    }
)

watch(
    () => props.shortageData,
    (newVal) => {
        if (newVal) shortageData.value = newVal
    }
)
const showMobileFilters = ref(false)

const tabs = [
    { id: 'CAUTION', label: 'Caution', color: 'orange' },
    { id: 'SHORTAGE', label: 'Shortage', color: 'red' },
]

const viewAllUrls = {
    CAUTION: route('warehouse-monitoring.leaderboard', { tab: 'CAUTION' }),
    SHORTAGE: route('warehouse-monitoring.leaderboard', { tab: 'SHORTAGE' })
}

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

// Debounce timer
let debounceTimer = null
const debouncedApplyFilters = () => {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => {
        applyFilters()
    }, 500) // Wait 500ms after user stops typing/selecting
}

const switchTab = (tabId) => {
    if (isLoading.value || currentTab.value === tabId) return

    currentTab.value = tabId

    // Update URL without reload
    const url = new URL(window.location)
    url.searchParams.set('tab', tabId)
    window.history.pushState({}, '', url)
}

const applyFilters = () => {
    if (isLoading.value) return

    isLoading.value = true

    router.get(
        route('warehouse-monitoring.leaderboard'),
        {
            tab: currentTab.value,
            ...localFilters.value,
            page: 1
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['cautionData', 'shortageData'], // Only reload data, not entire page
            onSuccess: (page) => {
                // Update local data refs
                cautionData.value = page.props.cautionData
                shortageData.value = page.props.shortageData
                isLoading.value = false
            },
            onError: () => {
                isLoading.value = false
            }
        }
    )

}

const uniquePICs = [
    "ADE N", "AKBAR", "ANWAR", "BAHTIYAR", "DEDHI",
    "EKA S", "EKO P", "FAHRI", "IBNU", "IRPANDI",
    "IRVAN", "MIKS", "RACHMAT", "ZAINAL A."
];

const usages = ['DAILY', 'WEEKLY', 'MONTHLY']

const clearFilters = () => {
    localFilters.value = { date: '', month: '', usage: '', location: '', gentani: '', pic_name: "" }
    applyFilters()
}

const handlePageChange = (tab, page) => {
    fetchTabData(tab, page)
}

const handleRefresh = (tab) => {
    const currentPage = tab === 'CAUTION'
        ? (cautionData.value?.pagination?.current_page || 1)
        : (shortageData.value?.pagination?.current_page || 1)
    fetchTabData(tab, currentPage)
}

const fetchTabData = (tab, page = 1) => {
    if (isLoading.value) return
    isLoading.value = true

    const resourceKey = tab === 'CAUTION' ? 'cautionData' : 'shortageData'

    router.get(
        route('warehouse-monitoring.leaderboard'),
        {
            tab,
            ...localFilters.value,
            page
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: [resourceKey],
            onSuccess: (pageData) => {
                if (tab === 'CAUTION') {
                    cautionData.value = pageData.props.cautionData
                } else {
                    shortageData.value = pageData.props.shortageData
                }

                currentTab.value = tab
                isLoading.value = false
            },
            onError: () => {
                isLoading.value = false
            }
        }
    )
}
watch(
    () => props.activeTab,
    (newVal) => {
        if (newVal) currentTab.value = newVal
    }
)

</script>
