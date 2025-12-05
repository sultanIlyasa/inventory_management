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

            <!-- Recovery Days Report Component -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="relative">
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

                    <RecoveryDaysReportContent :recoveryData="recoveryData?.data || []" :statistics="statistics || {}"
                        :trendData="trendData || []" :pagination="recoveryData?.pagination || defaultPagination"
                        size="full" :showChart="true" @refresh="applyFilters" @page-change="handlePageChange" />
                </div>
            </div>
        </div>
    </MainAppLayout>
</template>

<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import MainAppLayout from '@/Layouts/MainAppLayout.vue'
import RecoveryDaysReportContent from '@/Components/RecoveryDaysReportContent.vue'

const props = defineProps({
    recoveryData: {
        type: Object,
        required: true
    },
    statistics: {
        type: Object,
        required: true
    },
    trendData: {
        type: Array,
        default: () => []
    },
    filters: {
        type: Object,
        default: () => ({})
    }
})
const showMobileFilters = ref(false)

const isLoading = ref(false)
const localFilters = ref({
    month: props.filters.month || '',
    usage: props.filters.usage || '',
    location: props.filters.location || '',
    gentani: props.filters.gentani || ''
})

const defaultPagination = {
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0
}

const recoveryData = ref(props.recoveryData)
const statistics = ref(props.statistics)
const trendData = ref(props.trendData)
const pagination = ref(props.recoveryData.pagination || defaultPagination)

// Debounce timer
let debounceTimer = null
const debouncedApplyFilters = () => {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => {
        applyFilters()
    }, 500)
}

const applyFilters = () => {
    if (isLoading.value) return

    isLoading.value = true

    router.get(
        route('warehouse-monitoring.recovery-days'),
        {
            ...localFilters.value,
            page: 1 // Reset to first page on filter change
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['recoveryData', 'statistics', 'trendData'],
            onSuccess: (page) => {
                recoveryData.value = page.props.recoveryData
                statistics.value = page.props.statistics
                trendData.value = page.props.trendData
                pagination.value = page.props.recoveryData.pagination
                isLoading.value = false
            },
            onError: () => {
                isLoading.value = false
            }
        }
    )
}

const clearFilters = () => {
    localFilters.value = { month: '', usage: '', location: '', gentani: '' }
    applyFilters()
}

const handlePageChange = (page) => {
    if (isLoading.value) return

    isLoading.value = true

    router.get(
        route('warehouse-monitoring.recovery-days'),
        {
            ...localFilters.value,
            page
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['recoveryData'],
            onSuccess: (pageData) => {
                recoveryData.value = pageData.props.recoveryData
                pagination.value = pageData.props.recoveryData.pagination
                isLoading.value = false
            },
            onError: () => {
                isLoading.value = false
            }
        }
    )
}

watch(
    () => props.recoveryData,
    (newVal) => {
        if (newVal) {
            recoveryData.value = newVal
            pagination.value = newVal.pagination || defaultPagination
        }
    }
)

watch(
    () => props.statistics,
    (newVal) => {
        if (newVal) {
            statistics.value = newVal
        }
    }
)

watch(
    () => props.trendData,
    (newVal) => {
        if (newVal) {
            trendData.value = newVal
        }
    }
)
</script>
