<template>
    <MainAppLayout>
        <div class="max-w-7xl mx-auto">
            <!-- Filters Section -->
            <div class="bg-white rounded-lg shadow p-4 mb-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Month</label>
                        <input v-model="localFilters.month" type="month"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Usage</label>
                        <select v-model="localFilters.usage"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All</option>
                            <option value="DAILY">Daily</option>
                            <option value="WEEKLY">Weekly</option>
                            <option value="MONTHLY">Monthly</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                        <select v-model="localFilters.location"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All</option>
                            <option value="SUNTER_1">Sunter 1</option>
                            <option value="SUNTER_2">Sunter 2</option>
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <button @click="applyFilters"
                            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium">
                            Apply
                        </button>
                        <button @click="clearFilters"
                            class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm font-medium">
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
                        <button v-for="tab in tabs" :key="tab.id" @click="switchTab(tab.id)" :class="[
                            'flex-1 min-w-[150px] py-4 px-6 text-center border-b-2 font-medium text-sm transition whitespace-nowrap',
                            currentTab === tab.id
                                ? 'border-' + tab.color + '-500 text-' + tab.color + '-600 bg-white'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]">
                            <div class="flex items-center justify-center gap-2">
                                <component :is="tab.icon" class="w-5 h-5" />
                                <span>{{ tab.label }}</span>
                                <span :class="[
                                    'ml-2 px-2 py-1 text-xs rounded-full font-semibold',
                                    currentTab === tab.id
                                        ? 'bg-' + tab.color + '-100 text-' + tab.color + '-700'
                                        : 'bg-gray-100 text-gray-600'
                                ]">
                                    {{ getTabCount(tab.id) }}
                                </span>
                            </div>
                        </button>
                    </nav>
                </div>

                <!-- Tab Content -->
                <div class="p-4 sm:p-6">
                    <!-- ✅ Use v-if instead of v-show for better performance -->
                    <CautionOverdueLeaderboard v-if="currentTab === 'CAUTION'" size="full"
                        :initialLeaderboard="cautionData.leaderboard" :initialStatistics="cautionData.statistics"
                        :initialPagination="cautionData.pagination" :autoRefresh="false" :hideRefresh="false" />

                    <ShortageOverdueLeaderboard v-if="currentTab === 'SHORTAGE'" size="full"
                        :initialLeaderboard="shortageData.leaderboard" :initialStatistics="shortageData.statistics"
                        :initialPagination="shortageData.pagination" :autoRefresh="false" :hideRefresh="false" />
                </div>
            </div>
        </div>
    </MainAppLayout>
</template>

<script setup>
import { ref } from 'vue'
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
const localFilters = ref({
    month: props.filters.month || '',
    usage: props.filters.usage || '',
    location: props.filters.location || ''
})

// ... icons code

const tabs = [
    { id: 'CAUTION', label: 'Caution', color: 'orange' },
    { id: 'SHORTAGE', label: 'Shortage', color: 'red' },
]

const getTabCount = (tabId) => {
    switch (tabId) {
        case 'CAUTION':
            return props.cautionData.statistics?.total || 0
        case 'SHORTAGE':
            return props.shortageData.statistics?.total || 0
        default:
            return 0
    }
}

const switchTab = (tabId) => {
    currentTab.value = tabId
    router.get(route('warehouse-monitoring.leaderboard'), {
        tab: tabId,
        ...localFilters.value
    }, {
        preserveState: true,
        preserveScroll: true,
        only: [tabId.toLowerCase() + 'Data'],  // ✅ Only reload needed data
    })
}

const applyFilters = () => {
    router.get(route('warehouse-monitoring.leaderboard'), {
        tab: currentTab.value,
        ...localFilters.value
    }, {
        preserveState: false,
    })
}

const clearFilters = () => {
    localFilters.value = { month: '', usage: '', location: '' }
    router.get(route('warehouse-monitoring.leaderboard'), {
        tab: currentTab.value
    })
}
</script>
