<template>
    <MainAppLayout title="Warehouse Monitoring" subtitle="Warehouse GR Monitoring">
        <div class="min-h-screen bg-gray-50">
            <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <header class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">Dashboard Overview</p>
                        <h1 class="text-2xl font-semibold text-gray-900">Warehouse Performance Monitoring</h1>
                        <p class="mt-1 text-sm text-gray-500">Track caution, shortage, status changes, and recovery health.</p>
                    </div>
                    <button class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm lg:hidden"
                        @click="showMobileFilters = !showMobileFilters">
                        <span>{{ showMobileFilters ? 'Hide Filters' : 'Show Filters' }}</span>
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 4h18M4 8h16M6 12h12m-8 4h4m-6 4h8" />
                        </svg>
                    </button>
                </header>

                <div v-if="error" class="mt-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    {{ error }}
                </div>

                <div class="mt-6 flex flex-col items-center gap-6">
                    <section class="w-full">
                        <div :class="['w-full rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100 sm:p-6', showMobileFilters ? 'block' : 'hidden lg:block']">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
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
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Gentan-I & Non Gentan-I</label>
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
                                        {{ isLoading ? 'Loading...' : 'Apply Filters' }}
                                    </button>
                                    <button @click="clearFilters" :disabled="isLoading"
                                        class="flex-1 rounded-lg border border-gray-200 bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-200 disabled:cursor-not-allowed disabled:opacity-60">
                                        Clear
                                    </button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="grid w-full gap-6 lg:grid-cols-3">
                        <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100 lg:p-5">
                            <header class="mb-4 flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-orange-500">Risk Overview</p>
                                    <h2 class="text-lg font-semibold text-gray-900">Caution & Shortage</h2>
                                </div>
                                <span v-if="isLoading" class="text-xs text-gray-400">Updating…</span>
                            </header>
                            <div v-if="isLoading" class="flex flex-col items-center justify-center py-10 text-gray-500">
                                <div class="h-8 w-8 animate-spin rounded-full border-4 border-blue-200 border-t-blue-600"></div>
                                <p class="mt-3 text-sm font-medium">Loading leaderboards</p>
                            </div>
                            <div v-else class="space-y-4">
                                <CautionOverdueLeaderboard size="compact" :limit="5" :showViewAll="true"
                                    :viewAllUrl="leaderboardUrls.caution" :initialLeaderboard="cautionData.leaderboard || []"
                                    :initialStatistics="cautionData.statistics || {}"
                                    :initialPagination="cautionData.pagination || {}" hideRefresh
                                    @refresh="fetchDashboardData" />
                                <ShortageOverdueLeaderboard size="compact" :limit="5" :showViewAll="true"
                                    :viewAllUrl="leaderboardUrls.shortage" :initialLeaderboard="shortageData.leaderboard || []"
                                    :initialStatistics="shortageData.statistics || {}"
                                    :initialPagination="shortageData.pagination || {}" hideRefresh
                                    @refresh="fetchDashboardData" />
                            </div>
                        </div>

                        <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100 lg:p-5">
                            <header class="mb-4 flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-500">Status Changes</p>
                                    <h2 class="text-lg font-semibold text-gray-900">Status Change Tracker</h2>
                                </div>
                                <span v-if="isLoading" class="text-xs text-gray-400">Updating…</span>
                            </header>
                            <div v-if="isLoading" class="flex flex-col items-center justify-center py-10 text-gray-500">
                                <div class="h-8 w-8 animate-spin rounded-full border-4 border-blue-200 border-t-blue-600"></div>
                                <p class="mt-3 text-sm font-medium">Fetching status updates</p>
                            </div>
                            <div v-else class="space-y-4">
                                <StatusChangeContent :showViewAll="true" size="compact"
                                    :initialStatusChangeData="statusChangeData.data || []"
                                    :initialStatistics="statusChangeData.statistics || {}"
                                    :initialPagination="statusChangeData.pagination || {}" hideRefresh
                                    @refresh="fetchDashboardData" />
                                <div class="rounded-xl border border-gray-100 p-3">
                                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Material Distribution</p>
                                    <StatusBarChart :chartData="statusBarChartData" />
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100 lg:p-5">
                            <header class="mb-4 flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-500">Recovery</p>
                                    <h2 class="text-lg font-semibold text-gray-900">Recovery Days Insight</h2>
                                </div>
                                <span v-if="isLoading" class="text-xs text-gray-400">Updating…</span>
                            </header>
                            <div v-if="isLoading" class="flex flex-col items-center justify-center py-10 text-gray-500">
                                <div class="h-8 w-8 animate-spin rounded-full border-4 border-blue-200 border-t-blue-600"></div>
                                <p class="mt-3 text-sm font-medium">Loading recovery data</p>
                            </div>
                            <div v-else>
                                <RecoveryDaysReportContent size="compact" :limit="5" :showChart="true"
                                    :recoveryData="recoveryData.data || []"
                                    :statistics="recoveryData.statistics || {}"
                                    :trendData="recoveryData.trendData || []"
                                    :pagination="recoveryData.pagination || {}" hideRefresh
                                    @refresh="fetchDashboardData" />
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </MainAppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { debounce } from 'lodash'
import { route } from 'ziggy-js'
import axios from 'axios'
import CautionOverdueLeaderboard from '@/Components/CautionOverdueLeaderboard.vue'
import RecoveryDaysReportContent from '@/Components/RecoveryDaysReportContent.vue'
import ShortageOverdueLeaderboard from '@/Components/ShortageOverdueLeaderboard.vue'
import StatusChangeContent from '@/Components/StatusChangeContent.vue'
import StatusBarChart from '@/Components/StatusBarChart.vue'
import MainAppLayout from '@/Layouts/MainAppLayout.vue'

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({})
    }
})

const showMobileFilters = ref(false)
const leaderboardUrls = {
    caution: route('warehouse-monitoring.leaderboard', { tab: 'CAUTION' }),
    shortage: route('warehouse-monitoring.leaderboard', { tab: 'SHORTAGE' })
}

const isLoading = ref(false)
const error = ref(null)
const statusBarChartData = ref([])

const localFilters = ref({
    date: props.filters.date || '',
    month: props.filters.month || '',
    usage: props.filters.usage || '',
    location: props.filters.location || '',
    gentani: props.filters.gentani || ''
})

const cautionData = ref({
    leaderboard: [],
    statistics: {},
    pagination: {}
})

const shortageData = ref({
    leaderboard: [],
    statistics: {},
    pagination: {}
})

const recoveryData = ref({
    data: [],
    statistics: {},
    pagination: {},
    trendData: []
})

const statusChangeData = ref({
    data: [],
    statistics: {},
    pagination: {}
})

const today = new Date().toISOString().split('T')[0]
const minDate = ref('2020-01-01')
const maxDate = ref(today)
const isWeekendSelected = ref(false)

const onDateChange = () => {
    const selected = new Date(localFilters.value.date)
    const day = selected.getDay()

    if (day === 0 || day === 6) {
        isWeekendSelected.value = true
        localFilters.value.date = ''
        return
    }

    isWeekendSelected.value = false
    debouncedApplyFilters()
}

const fetchDashboardData = async () => {
    isLoading.value = true
    error.value = null

    try {
        const params = {
            month: localFilters.value.month || undefined,
            usage: localFilters.value.usage || undefined,
            date: localFilters.value.date || undefined,
            location: localFilters.value.location || undefined,
            gentani: localFilters.value.gentani || undefined,
            per_page: 5
        }

        Object.keys(params).forEach((key) => params[key] === undefined && delete params[key])

        const response = await axios.get(route('warehouse-monitoring.api.dashboard'), { params })

        if (response.data.success) {
            cautionData.value = response.data.data.caution || {}
            shortageData.value = response.data.data.shortage || {}
            recoveryData.value = response.data.data.recovery || {}
            statusChangeData.value = response.data.data.statusChange || {}
            const chart = response.data.data.barChart?.statusBarChart || []
            statusBarChartData.value = Array.isArray(chart) ? chart : []
        }
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to fetch dashboard data'
        console.error('Error fetching dashboard:', err)
    } finally {
        isLoading.value = false
    }
}

const debouncedApplyFilters = debounce(() => {
    fetchDashboardData()
}, 500)

const applyFilters = () => {
    fetchDashboardData()
}

const clearFilters = () => {
    localFilters.value = { date: '', month: '', usage: '', location: '', gentani: '' }
    fetchDashboardData()
}

onMounted(() => {
    fetchDashboardData()
})
</script>
