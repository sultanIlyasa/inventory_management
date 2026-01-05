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
                                <input type="date" v-model="localFilters.date" :min="minDate" :max="maxDate"
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

const showMobileFilters = ref(false)


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
