<template>
    <MainAppLayout title="Warehouse Monitoring" subtitle="Warehouse GR Monitoring">
        <div class="flex flex-col justify-center bg-gray-50">
            <h1 class="flex justify-center text-xl font-bold bg-white">Warehouse Performance Monitoring</h1>

            <!-- Error Alert -->
            <div v-if="error" class="mx-4 my-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800">
                {{ error }}
            </div>

            <div class="flex flex-col justify-center items-center mx-4 my-4">
                <!-- Filters Section -->
                <div class="bg-white rounded-lg shadow p-4 mb-6 w-full">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Select Date</label>
                            <input type="date" v-model="localFilters.date" :min="minDate" :max="maxDate"
                                @change="onDateChange" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm
               focus:outline-none focus:ring-2 focus:ring-blue-500" />
                            <p v-if="isWeekendSelected" class="text-xs text-red-600 mt-1">
                                Weekend is disabled — please select a weekday.
                            </p>
                        </div>

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

                <!-- Data Cards Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 w-full">
                    <!-- Caution Card -->
                    <div class="bg-white rounded-lg shadow p-4 flex justify-center">
                        <div v-if="isLoading" class="text-center py-8">
                            <div
                                class="animate-spin inline-block w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full">
                            </div>
                        </div>
                        <div v-else>
                            <CautionOverdueLeaderboard size="compact" :limit="5" :autoRefresh="false"
                                :showViewAll="true" viewAllUrl="/general-report/leaderboard"
                                :initialLeaderboard="cautionData.leaderboard || []"
                                :initialStatistics="cautionData.statistics || {}"
                                :initialPagination="cautionData.pagination || {}" class="mb-3" />
                            <ShortageOverdueLeaderboard size="compact" :limit="5" :autoRefresh="false"
                                :showViewAll="true" viewAllUrl="/general-report/leaderboard"
                                :initialLeaderboard="shortageData.leaderboard || []"
                                :initialStatistics="shortageData.statistics || {}"
                                :initialPagination="shortageData.pagination || {}" />
                        </div>
                    </div>

                    <!-- Shortage Card -->
                    <div class="bg-white rounded-lg shadow p-4 flex justify-center">
                        <div v-if="isLoading" class="text-center py-8">
                            <div
                                class="animate-spin inline-block w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full">
                            </div>
                        </div>
                        <div v-else>
                            <StatusChangeContent :showViewAll="true" size="compact"
                                :initialStatusChangeData="statusChangeData.data || []"
                                :initialStatistics="statusChangeData.statistics || {}" :initialPagination="statusChangeData.pagination || {}
                                    " />
                            <StatusBarChart :chartData="statusBarChartData" />


                        </div>
                    </div>

                    <!-- Recovery Days Card -->
                    <div class="bg-white rounded-lg shadow p-4">
                        <div v-if="isLoading" class="text-center py-8">
                            <div
                                class="animate-spin inline-block w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full">
                            </div>
                        </div>
                        <div v-else>
                            <RecoveryDaysReportContent size="compact" :limit="5" :showChart="true"
                                :initialRecoveryData="recoveryData.data || []"
                                :initialStatistics="recoveryData.statistics || {}"
                                :initialTrendData="recoveryData.trendData || []"
                                :initialPagination="recoveryData.pagination || {}" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainAppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { debounce, filter } from 'lodash'
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

const isLoading = ref(false)
const error = ref(null)

const statusBarChartData = ref([]);

const localFilters = ref({
    date: props.filters.date || '',
    month: props.filters.month || '',
    usage: props.filters.usage || '',
    location: props.filters.location || '',
    gentani: props.filters.gentani || ''
})

// All data states
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
const today = new Date().toISOString().split("T")[0]
const minDate = ref("2020-01-01")     // adjust as needed
const maxDate = ref(today)

const isWeekendSelected = ref(false)

const onDateChange = () => {
    const selected = new Date(localFilters.value.date)
    const day = selected.getDay() // 0 = Sunday, 6 = Saturday

    if (day === 0 || day === 6) {
        isWeekendSelected.value = true
        localFilters.value.date = ""     // clear invalid selection
        return
    }

    isWeekendSelected.value = false
    debouncedApplyFilters()
}


// Fetch all dashboard data at once
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

        // Remove undefined params
        Object.keys(params).forEach(key => params[key] === undefined && delete params[key])

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

// Debounced apply filters
const debouncedApplyFilters = debounce(() => {
    fetchDashboardData()
}, 500)

const applyFilters = async () => {
    await fetchDashboardData()
}

const clearFilters = () => {
    localFilters.value = { date: '', month: '', usage: '', location: '', gentani: '' }
    fetchDashboardData()
}

// Fetch data on mount
onMounted(() => {
    fetchDashboardData()
})
</script>
