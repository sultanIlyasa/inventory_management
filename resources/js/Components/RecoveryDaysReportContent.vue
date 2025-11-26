<template>
    <div class="bg-white border rounded-lg shadow-lg p-4 sm:p-6">
        <!-- Header -->
        <div class="mb-4">
            <h3 class="text-xl font-bold text-gray-900">Recovery Days Report</h3>
            <p class="text-sm text-gray-600 mt-1">Material recovery performance tracking</p>
        </div>

        <!-- Statistics Cards -->
        <div v-if="!isCompact" class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
            <div class="bg-blue-50 p-3 sm:p-4 rounded-lg shadow-sm text-center border border-blue-200">
                <p class="text-xs text-gray-600 mb-1">Total Recovered</p>
                <p class="text-2xl font-bold text-blue-600">{{ currentStatistics.total_recovered }}</p>
            </div>
            <div class="bg-green-50 p-3 sm:p-4 rounded-lg shadow-sm text-center border border-green-200">
                <p class="text-xs text-gray-600 mb-1">Avg Days</p>
                <p class="text-2xl font-bold text-green-600">{{ currentStatistics.average_recovery_days }}</p>
            </div>
            <div class="bg-yellow-50 p-3 sm:p-4 rounded-lg shadow-sm text-center border border-yellow-200">
                <p class="text-xs text-gray-600 mb-1">Fastest</p>
                <p class="text-2xl font-bold text-yellow-600">{{ currentStatistics.fastest_recovery }}</p>
            </div>
            <div class="bg-red-50 p-3 sm:p-4 rounded-lg shadow-sm text-center border border-red-200">
                <p class="text-xs text-gray-600 mb-1">Slowest</p>
                <p class="text-2xl font-bold text-red-600">{{ currentStatistics.slowest_recovery }}</p>
            </div>
        </div>

        <!-- Compact View (List + Chart) -->
        <div v-if="isCompact" >
            <!-- List -->
            <div class="p-2 sm:p-3 max-h-52 sm:max-h-64 md:max-h-72 overflow-y-auto border-b">
                <div class="space-y-1.5 sm:space-y-2">
                    <div v-for="item in limitedItems" :key="item.material_number"
                        class="flex items-center justify-between p-1.5 sm:p-2 bg-gray-50 rounded hover:bg-gray-100 transition">
                        <div class="flex-1 min-w-0 mr-2 sm:mr-3">
                            <div class="font-semibold text-[10px] sm:text-xs truncate">{{ item.material_number }}</div>
                            <div class="text-[9px] sm:text-[10px] text-gray-600 truncate">{{ item.description }}</div>
                            <div class="text-[8px] sm:text-[10px] text-gray-500 mt-0.5">PIC: {{ item.pic }}</div>
                        </div>
                        <div class="flex items-center gap-2 sm:gap-3">
                            <div class="text-right hidden sm:block">
                                <div class="text-[9px] text-gray-500">Recovery</div>
                                <div class="text-[10px] text-gray-600">{{ formatDateShort(item.recovery_date) }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-base sm:text-lg md:text-xl font-bold" :class="{
                                    'text-green-600': item.recovery_days <= 3,
                                    'text-yellow-600': item.recovery_days > 3 && item.recovery_days <= 7,
                                    'text-red-600': item.recovery_days > 7,
                                }">
                                    {{ item.recovery_days }}
                                </div>
                                <div class="text-[8px] sm:text-[9px] text-gray-500">days</div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-if="!limitedItems.length" class="text-center py-4 sm:py-6 text-gray-500">
                        <div class="text-xl sm:text-2xl mb-1">📊</div>
                        <div class="text-[10px] sm:text-xs font-semibold">No Data Available</div>
                    </div>
                </div>
            </div>

            <!-- Compact Chart -->
            <div v-if="showChart && currentTrendData && currentTrendData.length" class="border-t pt-4 pb-2">
                <h4 class="text-sm sm:text-base font-semibold text-gray-700 mb-3">Monthly Recovery Trend</h4>
                <div class="w-full h-[200px]">
                    <canvas ref="compactChartCanvas"></canvas>
                </div>
            </div>

            <!-- View All Link -->
            <div class="p-2 sm:p-3 border-t">
                <a v-if="showViewAll && currentPagination.total > limit" :href="viewAllUrl"
                    class="block text-center text-[11px] sm:text-sm text-blue-600 hover:text-blue-800 font-semibold">
                    View All ({{ currentPagination.total }}) →
                </a>
            </div>
        </div>

        <!-- Full View -->
        <div v-if="!isCompact">
            <!-- Data Table -->
            <div class="overflow-x-auto mb-6">
                <table class="min-w-full border-collapse text-sm">
                    <thead class="bg-gray-100 border-b sticky top-0">
                        <tr>
                            <th class="p-3 border text-left text-xs font-semibold">#</th>
                            <th class="p-3 border text-left text-xs font-semibold">Material</th>
                            <th class="p-3 border text-left text-xs font-semibold hidden md:table-cell">Description</th>
                            <th class="p-3 border text-left text-xs font-semibold">PIC</th>
                            <th class="p-3 border text-center text-xs font-semibold cursor-pointer hover:bg-gray-200"
                                @click="toggleSort">
                                Recovery Days {{ sortOrder === 'desc' ? '↓' : '↑' }}
                            </th>
                            <th class="p-3 border text-center text-xs font-semibold hidden lg:table-cell">Problem Date
                            </th>
                            <th class="p-3 border text-center text-xs font-semibold">Recovery Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in currentRecoveryData" :key="item.material_number"
                            class="hover:bg-gray-50 transition border-b">
                            <td class="p-3 border text-center text-gray-600 font-medium">
                                {{ (currentPagination.current_page - 1) * currentPagination.per_page + index + 1 }}
                            </td>
                            <td class="p-3 border text-xs">
                                <div class="font-semibold">{{ item.material_number }}</div>
                                <div class="text-[10px] text-gray-500 md:hidden truncate">{{ item.description }}</div>
                            </td>
                            <td class="p-3 border text-xs hidden md:table-cell">{{ item.description }}</td>
                            <td class="p-3 border text-xs">{{ item.pic }}</td>
                            <td class="p-3 border text-center">
                                <span class="font-bold text-lg" :class="{
                                    'text-green-600': item.recovery_days <= 3,
                                    'text-yellow-600': item.recovery_days > 3 && item.recovery_days <= 7,
                                    'text-red-600': item.recovery_days > 7,
                                }">
                                    {{ item.recovery_days }}
                                </span>
                            </td>
                            <td class="p-3 border text-center text-xs text-gray-600 hidden lg:table-cell">
                                {{ formatDate(item.last_problem_date) }}
                            </td>
                            <td class="p-3 border text-center text-xs bg-green-50 font-medium">
                                {{ formatDate(item.recovery_date) }}
                            </td>
                        </tr>

                        <!-- Empty State -->
                        <tr v-if="!currentRecoveryData.length">
                            <td colspan="7" class="p-6 text-center text-gray-500">
                                <div class="text-3xl mb-2">📊</div>
                                <div class="text-base font-semibold">No Recovery Data</div>
                                <div class="text-xs">No materials recovered in this period</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Trend Chart -->
            <div v-if="showChart && currentTrendData && currentTrendData.length" class="border-t pt-6 mb-6">
                <h4 class="text-lg font-semibold text-gray-700 mb-4">Monthly Recovery Trend</h4>
                <div class="w-full h-[300px]">
                    <canvas ref="chartCanvas"></canvas>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="currentPagination.last_page > 1"
                class="flex flex-col sm:flex-row items-center justify-between gap-3 mb-6">
                <div class="text-sm text-gray-600">
                    Showing {{ (currentPagination.current_page - 1) * currentPagination.per_page + 1 }}
                    to {{ Math.min(currentPagination.current_page * currentPagination.per_page, currentPagination.total)
                    }}
                    of {{ currentPagination.total }} entries
                </div>

                <div class="flex gap-2">
                    <button @click="goToPage(1)" :disabled="currentPagination.current_page === 1"
                        class="px-3 py-2 text-sm border rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition">
                        First
                    </button>
                    <button @click="goToPage(currentPagination.current_page - 1)"
                        :disabled="currentPagination.current_page === 1"
                        class="px-3 py-2 text-sm border rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition">
                        Prev
                    </button>

                    <button v-for="page in visiblePages" :key="page" @click="goToPage(page)" :class="[
                        'px-3 py-2 text-sm border rounded transition',
                        currentPagination.current_page === page
                            ? 'bg-blue-500 text-white'
                            : 'hover:bg-gray-100'
                    ]">
                        {{ page }}
                    </button>

                    <button @click="goToPage(currentPagination.current_page + 1)"
                        :disabled="currentPagination.current_page === currentPagination.last_page"
                        class="px-3 py-2 text-sm border rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition">
                        Next
                    </button>
                    <button @click="goToPage(currentPagination.last_page)"
                        :disabled="currentPagination.current_page === currentPagination.last_page"
                        class="px-3 py-2 text-sm border rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition">
                        Last
                    </button>
                </div>
            </div>
        </div>

        <!-- Refresh Button -->
        <div class="mt-6 flex justify-end">
            <button @click="fetchReport(currentPagination.current_page)" :disabled="isLoading"
                :class="isCompact ? 'w-full' : 'w-full sm:w-auto'"
                class="px-3 sm:px-4 py-1.5 sm:py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition disabled:opacity-50 text-xs sm:text-sm">
                <span v-if="isLoading">Refreshing...</span>
                <span v-else>{{ isCompact ? 'Refresh' : 'Refresh Data' }}</span>
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue'
import Chart from 'chart.js/auto'

const isLoading = ref(false)

const props = defineProps({
    initialRecoveryData: {
        type: Array,
        default: () => []
    },
    initialStatistics: {
        type: Object,
        default: () => ({
            total_recovered: 0,
            average_recovery_days: 0,
            fastest_recovery: 0,
            slowest_recovery: 0,
        })
    },
    initialTrendData: {
        type: Array,  // ✅ Changed from Object to Array
        default: () => []  // ✅ Changed from {} to []
    },
    initialPagination: {
        type: Object,
        default: () => ({
            current_page: 1,
            last_page: 1,
            per_page: 10,
            total: 0
        })
    },
    filters: {
        type: Object,
        default: () => ({})
    },
    size: {
        type: String,
        default: 'full',
        validator: (value) => ['compact', 'full', 'mini'].includes(value)
    },
    limit: {
        type: Number,
        default: null
    },
    showChart: {
        type: Boolean,
        default: true
    },
    showViewAll: {
        type: Boolean,
        default: true
    },
    viewAllUrl: {
        type: String,
        default: '/warehouse-monitoring/recovery-days'
    }
})

const recoveryData = ref([...props.initialRecoveryData])
const statistics = ref({ ...props.initialStatistics })
const pagination = ref({ ...props.initialPagination })
const trendData = ref([...props.initialTrendData])  //

const emit = defineEmits(['refresh', 'page-change'])
const sortOrder = ref('desc')
const chartCanvas = ref(null)
const compactChartCanvas = ref(null)  //
let chartInstance = null

const months = [
    "Jan", "Feb", "Mar", "Apr", "May", "Jun",
    "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"
]

// Computed properties
const isCompact = computed(() => props.size === 'compact' || props.size === 'mini')

const currentRecoveryData = computed(() =>
    recoveryData.value.length > 0 ? recoveryData.value : props.initialRecoveryData
)

const currentStatistics = computed(() =>
    statistics.value.total_recovered !== undefined ? statistics.value : props.initialStatistics
)

const currentTrendData = computed(() =>
    trendData.value.length > 0 ? trendData.value : props.initialTrendData
)

const currentPagination = computed(() =>
    pagination.value.current_page > 0 ? pagination.value : props.initialPagination
)

const limitedItems = computed(() => {
    const defaultLimit = props.size === 'mini' ? 3 : props.size === 'compact' ? 5 : 10
    const itemLimit = props.limit || defaultLimit
    return currentRecoveryData.value.slice(0, itemLimit)
})

const visiblePages = computed(() => {
    const pages = []
    const maxVisible = 5
    const current = currentPagination.value.current_page
    const last = currentPagination.value.last_page

    let start = Math.max(1, current - Math.floor(maxVisible / 2))
    let end = Math.min(last, start + maxVisible - 1)

    if (end - start + 1 < maxVisible) {
        start = Math.max(1, end - maxVisible + 1)
    }

    for (let i = start; i <= end; i++) {
        pages.push(i)
    }

    return pages
})

// Methods
const toggleSort = () => {
    sortOrder.value = sortOrder.value === 'desc' ? 'asc' : 'desc'
}

const goToPage = (page) => {
    if (page >= 1 && page <= currentPagination.value.last_page) {
        emit('page-change', page)
    }
}

const formatDate = (dateStr) => {
    if (!dateStr) return '-'
    const date = new Date(dateStr)
    return date.toLocaleDateString('en-US', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    })
}

const formatDateShort = (dateStr) => {
    if (!dateStr) return '-'
    const date = new Date(dateStr)
    return date.toLocaleDateString("en-US", {
        month: 'short',
        day: 'numeric',
    })
}

const fetchReport = async (page = 1) => {
    isLoading.value = true;
    try {
        const params = {
            page: page,
        }
        if (props.limit && isCompact.value) {
            params.per_page = props.limit
        }
        const response = await axios.get(route('warehouse-monitoring.api.recovery-days'), { params });
        if (response.data.success) {
            recoveryData.value = response.data.recoveryData || [],
                statistics.value = response.data.statistics || {},
                trendData.value = response.data.trendData || {}
        }
        await nextTick();
        if (props.showChart) {
            renderChart();
        }
    } catch (error) {
        console.error("Failed to fetch recovery report:", error);

    } finally {
        isLoading.value = false;
    }
};
const renderChart = () => {
    // ✅ Fixed: Use correct ref based on view mode
    const canvas = isCompact.value ? compactChartCanvas.value : chartCanvas.value

    if (!canvas || !currentTrendData.value || !Array.isArray(currentTrendData.value) || currentTrendData.value.length === 0) {
    }

    const validData = currentTrendData.value.filter(m => m.total_recovered > 0)
    if (!validData.length) {
        console.log('No valid trend data')
        return
    }

    if (chartInstance) {
        chartInstance.destroy()
        chartInstance = null
    }

    const ctx = canvas.getContext('2d')

    chartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: validData.map(m => months[m.month - 1]),
            datasets: [{
                label: 'Avg Recovery Days',
                data: validData.map(m => m.average_recovery_days),
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59,130,246,0.1)',
                tension: 0.4,
                fill: true,
                pointRadius: 4,
                pointHoverRadius: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                },
                tooltip: {
                    enabled: true,
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Days'
                    }
                }
            }
        }
    })

    console.log('Chart rendered successfully')
}

// Lifecycle
onMounted(async () => {
    console.log('Component mounted', {
        hasInitialData: props.initialRecoveryData.length > 0,
        hasTrendData: props.initialTrendData?.length > 0,
        showChart: props.showChart,
        isCompact: isCompact.value
    })

    if (props.showChart && props.initialTrendData?.length > 0) {
        await nextTick()
        renderChart()
    }
})

onBeforeUnmount(() => {
    if (chartInstance) {
        chartInstance.destroy()
        chartInstance = null
    }
})

// Watchers
watch(() => props.size, async () => {
    if (chartInstance) {
        chartInstance.destroy()
        chartInstance = null
    }
    await nextTick()
    if (props.showChart && currentTrendData.value.length) {
        renderChart()
    }
})

watch(() => props.showChart, async (newVal) => {
    if (newVal) {
        await nextTick()
        renderChart()
    } else {
        if (chartInstance) {
            chartInstance.destroy()
            chartInstance = null
        }
    }
})

watch(() => props.initialRecoveryData, (newVal) => {
    if (newVal && newVal.length > 0) {
        recoveryData.value = [...newVal]
    }
}, { deep: false })

watch(() => props.initialStatistics, (newVal) => {
    if (newVal && newVal.total_recovered !== undefined) {
        statistics.value = { ...newVal }
    }
}, { deep: false })

watch(() => props.initialPagination, (newVal) => {
    if (newVal && newVal.current_page > 0) {
        pagination.value = { ...newVal }
    }
}, { deep: false })

watch(() => props.initialTrendData, async (newVal) => {
    console.log('Trend data changed:', newVal)
    if (newVal && Array.isArray(newVal) && newVal.length > 0) {
        trendData.value = [...newVal]
        await nextTick()
        if (props.showChart) {
            renderChart()
        }
    }
}, { deep: false })
</script>
