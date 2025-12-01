<template>
    <MainAppLayout>
        <div class="max-w-7xl mx-auto">

            <!-- Filters Section -->
            <div class="bg-white rounded-lg shadow p-4 mb-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

                    <!-- Month -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Month</label>
                        <input v-model="localFilters.month" type="month" @change="debouncedApplyFilters"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" />
                    </div>

                    <!-- Usage -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Usage</label>
                        <select v-model="localFilters.usage" @change="debouncedApplyFilters"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="">All</option>
                            <option value="DAILY">Daily</option>
                            <option value="WEEKLY">Weekly</option>
                            <option value="MONTHLY">Monthly</option>
                        </select>
                    </div>

                    <!-- Location -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                        <select v-model="localFilters.location" @change="debouncedApplyFilters"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="">All</option>
                            <option value="SUNTER_1">Sunter 1</option>
                            <option value="SUNTER_2">Sunter 2</option>
                        </select>
                    </div>

                    <!-- Gentan -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Gentan-I & Non Gentan-I</label>
                        <select v-model="localFilters.gentani" @change="debouncedApplyFilters"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="">All</option>
                            <option value="GENTAN-I">Gentan-I</option>
                            <option value="NON_GENTAN-I">Non Gentan-I</option>
                        </select>
                    </div>

                    <!-- Buttons -->
                    <div class="flex items-end gap-2">
                        <button @click="applyFilters" :disabled="isLoading"
                            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm disabled:opacity-50">
                            {{ isLoading ? 'Loading…' : 'Apply' }}
                        </button>

                        <button @click="clearFilters" :disabled="isLoading"
                            class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm">
                            Clear
                        </button>
                    </div>
                </div>
            </div>

            <!-- Status Change Content -->
            <div class="bg-white rounded-lg shadow overflow-hidden relative">

                <!-- Loading Overlay -->
                <div v-if="isLoading"
                    class="absolute inset-0 bg-white bg-opacity-70 flex items-center justify-center z-10">
                    <div class="text-center">
                        <div class="animate-spin h-10 w-10 border-b-2 border-blue-600 rounded-full mx-auto mb-3"></div>
                        <p class="text-sm text-gray-600">Loading data...</p>
                    </div>
                </div>

                <!-- Child Component -->
                <StatusChangeContent :initialStatusChangeData="statusChangeData.data" :initialStatistics="statistics"
                    :initialPagination="pagination" :size="'full'" @page-change="handlePageChange"
                    @refresh="applyFilters" />
            </div>

            <StatusBarChart size="full" />
        </div>
    </MainAppLayout>
</template>



<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import MainAppLayout from '@/Layouts/MainAppLayout.vue'
import StatusBarChart from '@/Components/StatusBarChart.vue'
import StatusChangeContent from '@/Components/StatusChangeContent.vue'

/* Props passed from backend */
const props = defineProps({
    statusChangeData: Object,
    statistics: Object,
    filters: { type: Object, default: () => ({}) }
})

/* Reactive State */
const isLoading = ref(false)
const statusChangeData = ref(props.statusChangeData)
const statistics = ref(props.statistics)

const pagination = ref(props.statusChangeData.pagination ?? {
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0
})

/* Local Filters */
const localFilters = ref({
    month: props.filters.month ?? '',
    usage: props.filters.usage ?? '',
    location: props.filters.location ?? '',
    gentani: props.filters.gentani ?? ''
})

/* Debounce helper */
let debounceTimer = null
const debouncedApplyFilters = () => {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(applyFilters, 500)
}

/* Fetch with filters */
const applyFilters = () => {
    if (isLoading.value) return
    isLoading.value = true

    router.get(route('warehouse-monitoring.status-change'), {
        ...localFilters.value,
        page: 1
    }, {
        preserveState: true,
        preserveScroll: true,
        only: ['statusChangeData', 'statistics'],
        onSuccess: (page) => {
            statusChangeData.value = page.props.statusChangeData
            statistics.value = page.props.statistics
            pagination.value = page.props.statusChangeData.pagination
            isLoading.value = false
        },
        onError: () => isLoading.value = false
    })
}

/* Clear filters */
const clearFilters = () => {
    localFilters.value = { month: '', usage: '', location: '', gentani: '' }
    applyFilters()
}

/* Pagination Handler */
const handlePageChange = (page) => {
    if (isLoading.value) return
    isLoading.value = true

    router.get(route('warehouse-monitoring.status-change'), {
        ...localFilters.value,
        page
    }, {
        preserveState: true,
        preserveScroll: true,
        only: ['statusChangeData'],
        onSuccess: (pageData) => {
            statusChangeData.value = pageData.props.statusChangeData
            pagination.value = pageData.props.statusChangeData.pagination
            isLoading.value = false
        },
        onError: () => isLoading.value = false
    })
}
</script>
