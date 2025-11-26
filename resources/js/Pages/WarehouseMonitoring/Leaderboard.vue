<template>
    <MainAppLayout>
        <div class="max-w-7xl mx-auto">
            <!-- Filters Section -->
            <div class="bg-white rounded-lg shadow p-4 mb-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Month</label>
                        <input v-model="localFilters.month" type="month" @change="debouncedApplyFilters"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Usage</label>
                        <select v-model="localFilters.usage" @change="debouncedApplyFilters"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All</option>
                            <option value="DAILY">Daily</option>
                            <option value="WEEKLY">Weekly</option>
                            <option value="MONTHLY">Monthly</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                        <select v-model="localFilters.location" @change="debouncedApplyFilters"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All</option>
                            <option value="SUNTER_1">Sunter 1</option>
                            <option value="SUNTER_2">Sunter 2</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Gentan-I & Non Gentan-I</label>
                        <select v-model="localFilters.gentani" @change="debouncedApplyFilters"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All</option>
                            <option value="GENTAN-I">Gentan-I</option>
                            <option value="NON_GENTAN-I">Non Gentan-I</option>
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <button @click="applyFilters" :disabled="isLoading"
                            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium disabled:opacity-50">
                            {{ isLoading ? 'Loading...' : 'Apply' }}
                        </button>
                        <button @click="clearFilters" :disabled="isLoading"
                            class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm font-medium disabled:opacity-50">
                            Clear
                        </button>
                    </div>
                </div>
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
                            :initialLeaderboard="cautionData.leaderboard" :initialStatistics="cautionData.statistics"
                            :initialPagination="cautionData.pagination" :autoRefresh="false" :hideRefresh="false" />
                    </div>

                    <div :class="{ 'hidden': currentTab !== 'SHORTAGE' }">
                        <ShortageOverdueLeaderboard v-if="shortageData" size="full"
                            :initialLeaderboard="shortageData.leaderboard" :initialStatistics="shortageData.statistics"
                            :initialPagination="shortageData.pagination" :autoRefresh="false" :hideRefresh="false" />
                    </div>
                </div>
            </div>
        </div>
    </MainAppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import MainAppLayout from '@/Layouts/MainAppLayout.vue'
import CautionOverdueLeaderboard from '@/Components/CautionOverdueLeaderboard.vue'
import ShortageOverdueLeaderboard from '@/Components/ShortageOverdueLeaderboard.vue'

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
    gentani: props.filters.gentani || ''
})

// Cache for data
const cautionData = ref(props.cautionData)
const shortageData = ref(props.shortageData)

const tabs = [
    { id: 'CAUTION', label: 'Caution', color: 'orange' },
    { id: 'SHORTAGE', label: 'Shortage', color: 'red' },
]

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
            ...localFilters.value
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

const clearFilters = () => {
    localFilters.value = { date: '', month: '', usage: '', location: '', gentani: '' }
    applyFilters()
}
</script>
