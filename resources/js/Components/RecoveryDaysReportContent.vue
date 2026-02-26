<template>
    <div class="bg-white border rounded-lg shadow-lg p-4 sm:p-6">
        <div class="mb-4">
            <h3 class="text-xl font-bold text-gray-900">Recovery Days Report</h3>
            <p class="text-sm text-gray-600 mt-1">Material recovery performance tracking</p>
        </div>

        <div v-if="!isCompact" class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
            <div class="rounded-lg border border-blue-200 bg-blue-50 p-3 text-center">
                <p class="text-xs text-gray-600 mb-1">Total Recovered</p>
                <p class="text-2xl font-bold text-blue-600">{{ currentStatistics.total_recovered }}</p>
            </div>
            <div class="rounded-lg border border-green-200 bg-green-50 p-3 text-center">
                <p class="text-xs text-gray-600 mb-1">Avg Days</p>
                <p class="text-2xl font-bold text-green-600">{{ currentStatistics.average_recovery_days }}</p>
            </div>
            <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-3 text-center">
                <p class="text-xs text-gray-600 mb-1">Fastest</p>
                <p class="text-2xl font-bold text-yellow-600">{{ currentStatistics.fastest_recovery }}</p>
            </div>
            <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-center">
                <p class="text-xs text-gray-600 mb-1">Slowest</p>
                <p class="text-2xl font-bold text-red-600">{{ currentStatistics.slowest_recovery }}</p>
            </div>
        </div>

        <div v-if="isCompact">
            <div class="max-h-64 overflow-y-auto border-b p-2">
                <div class="space-y-1.5">
                    <div v-for="item in limitedItems" :key="item.material_number"
                        class="flex items-center justify-between rounded bg-gray-50 p-2 hover:bg-gray-100">
                        <div class="flex-1 min-w-0 pr-3">
                            <p class="text-[11px] font-semibold truncate">{{ item.material_number }}</p>
                            <p class="text-[10px] text-gray-600 truncate">{{ item.description }}</p>
                            <p class="text-[10px] text-gray-500 mt-0.5">PIC: {{ item.pic }}</p>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-bold" :class="getDaysColor(item.recovery_days)">
                                {{ item.recovery_days }}
                            </div>
                            <div class="text-[10px] text-gray-500">days</div>
                        </div>
                    </div>
                    <div v-if="!limitedItems.length" class="py-6 text-center text-sm text-gray-500">
                        No recovery data available
                    </div>
                </div>
            </div>

            <div v-if="showChart && currentTrendData.length" class="border-t py-4">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">Monthly Recovery Trend</h4>
                <div class="h-[200px] w-full">
                    <canvas ref="compactChartCanvas"></canvas>
                </div>
            </div>

            <div class="border-t p-2">
                <a v-if="showViewAll && currentPagination.total > limitValue" :href="viewAllUrl"
                    class="block text-center text-xs font-semibold text-blue-600 hover:text-blue-800">
                    View All ({{ currentPagination.total }})
                </a>
            </div>
        </div>

        <div v-else>
            <div class="overflow-x-auto mb-6">
                <table class="min-w-full border-collapse text-sm">
                    <thead class="bg-gray-100 text-[11px] uppercase text-gray-600">
                        <tr>
                            <th class="border p-3 text-left">#</th>
                            <th class="border p-3 text-left">Material</th>
                            <th class="border p-3 text-left hidden md:table-cell">Description</th>
                            <th class="border p-3 text-left">PIC</th>
                            <th class="border p-3 text-center cursor-pointer hover:bg-gray-200" @click="toggleSort">
                                Recovery Days {{ sortOrder === 'desc' ? '↓' : '↑' }}
                            </th>
                            <th class="border p-3 text-center hidden lg:table-cell">Problem Date</th>
                            <th class="border p-3 text-center">Recovery Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in sortedRecoveryData" :key="item.material_number"
                            class="border-b hover:bg-gray-50">
                            <td class="border p-3 text-center text-gray-600">
                                {{ index + 1 }}
                            </td>
                            <td class="border p-3 text-xs">
                                <div class="font-semibold">{{ item.material_number }}</div>
                                <div class="text-[10px] text-gray-500 md:hidden truncate">{{ item.description }}</div>
                            </td>
                            <td class="border p-3 text-xs hidden md:table-cell">{{ item.description }}</td>
                            <td class="border p-3 text-xs">{{ item.pic }}</td>
                            <td class="border p-3 text-center">
                                <span class="text-lg font-bold" :class="getDaysColor(item.recovery_days)">
                                    {{ item.recovery_days }}
                                </span>
                            </td>
                            <td class="border p-3 text-center text-xs text-gray-600 hidden lg:table-cell">
                                {{ formatDate(item.last_problem_date) }}
                            </td>
                            <td class="border p-3 text-center text-xs bg-green-50 font-medium">
                                {{ formatDate(item.recovery_date) }}
                            </td>
                        </tr>
                        <tr v-if="!sortedRecoveryData.length">
                            <td colspan="7" class="p-6 text-center text-gray-500">
                                No recovery data available
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="showChart && currentTrendData.length" class="border-t pt-6 mb-6">
                <h4 class="text-lg font-semibold text-gray-700 mb-4">Monthly Recovery Trend</h4>
                <div class="h-[300px] w-full">
                    <canvas ref="chartCanvas"></canvas>
                </div>
            </div>

            <div v-if="currentPagination.last_page > 1"
                class="flex flex-col gap-3 text-sm sm:flex-row sm:items-center sm:justify-between">
                <div class="text-gray-600">
                    Showing {{ startEntry }} to {{ endEntry }} of {{ currentPagination.total }} entries
                </div>
                <div class="flex flex-wrap gap-2">
                    <button class="px-3 py-1.5 rounded border text-sm hover:bg-gray-100 disabled:opacity-50"
                        @click="emitPage(1)" :disabled="currentPagination.current_page === 1">
                        First
                    </button>
                    <button class="px-3 py-1.5 rounded border text-sm hover:bg-gray-100 disabled:opacity-50"
                        @click="emitPage(currentPagination.current_page - 1)"
                        :disabled="currentPagination.current_page === 1">
                        Prev
                    </button>
                    <button v-for="page in visiblePages" :key="page" @click="emitPage(page)"
                        :class="['px-3 py-1.5 rounded border text-sm',
                            currentPagination.current_page === page ? 'bg-blue-500 text-white border-blue-500' : 'hover:bg-gray-100']">
                        {{ page }}
                    </button>
                    <button class="px-3 py-1.5 rounded border text-sm hover:bg-gray-100 disabled:opacity-50"
                        @click="emitPage(currentPagination.current_page + 1)"
                        :disabled="currentPagination.current_page === currentPagination.last_page">
                        Next
                    </button>
                    <button class="px-3 py-1.5 rounded border text-sm hover:bg-gray-100 disabled:opacity-50"
                        @click="emitPage(currentPagination.last_page)"
                        :disabled="currentPagination.current_page === currentPagination.last_page">
                        Last
                    </button>
                </div>
            </div>
        </div>

        <div v-if="!hideRefresh" class="mt-6 flex justify-end">
            <button @click="emitRefresh"
                class="rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                {{ isCompact ? 'Refresh' : 'Refresh Data' }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import Chart from 'chart.js/auto'
import { buildFilterParams } from '@/utils/filterParams'

const props = defineProps({
    recoveryData: {
        type: Array,
        default: () => []
    },
    statistics: {
        type: Object,
        default: () => ({
            total_recovered: 0,
            average_recovery_days: 0,
            fastest_recovery: 0,
            slowest_recovery: 0
        })
    },
    trendData: {
        type: Array,
        default: () => []
    },
    pagination: {
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
        default: null
    },
    size: {
        type: String,
        default: 'full'
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
    },
    hideRefresh: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['refresh', 'page-change'])

// Self-fetch mode when filters prop is provided
const isSelfFetch = computed(() => props.filters !== null)

const localRecoveryData = ref([])
const localStatistics = ref({
    total_recovered: 0,
    average_recovery_days: 0,
    fastest_recovery: 0,
    slowest_recovery: 0
})
const localTrendData = ref([])
const localPagination = ref({ current_page: 1, last_page: 1, per_page: 10, total: 0 })

async function fetchData(page = 1) {
    if (!isSelfFetch.value) return
    try {
        const params = buildFilterParams({ ...props.filters, page })
        const res = await fetch(`/warehouse-monitoring/api/recovery-days?${params}`)
        if (!res.ok) throw new Error(`HTTP ${res.status}`)
        const json = await res.json()
        localRecoveryData.value = json.recoveryData ?? json.data ?? []
        localStatistics.value = json.statistics ?? localStatistics.value
        localTrendData.value = json.trendData ?? json.trend_data ?? []
        localPagination.value = json.pagination ?? localPagination.value
    } catch {
        // silent fail
    }
}

watch(() => props.filters, (f) => {
    if (f !== null) fetchData(1)
}, { deep: true, immediate: true })

const sortOrder = ref('desc')
const chartCanvas = ref(null)
const compactChartCanvas = ref(null)
let chartInstance = null

const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']

const isCompact = computed(() => props.size === 'compact' || props.size === 'mini')
const currentRecoveryData = computed(() =>
    isSelfFetch.value ? localRecoveryData.value : (props.recoveryData || [])
)
const sortedRecoveryData = computed(() => {
    const items = [...currentRecoveryData.value]
    return items.sort((a, b) => sortOrder.value === 'desc'
        ? (b.recovery_days ?? 0) - (a.recovery_days ?? 0)
        : (a.recovery_days ?? 0) - (b.recovery_days ?? 0)
    )
})

const currentStatistics = computed(() => {
    const s = isSelfFetch.value ? localStatistics.value : props.statistics
    return {
        total_recovered: s?.total_recovered ?? 0,
        average_recovery_days: s?.average_recovery_days ?? 0,
        fastest_recovery: s?.fastest_recovery ?? 0,
        slowest_recovery: s?.slowest_recovery ?? 0
    }
})

const currentTrendData = computed(() =>
    isSelfFetch.value ? localTrendData.value : (props.trendData || [])
)
const currentPagination = computed(() =>
    isSelfFetch.value ? localPagination.value : { ...(props.pagination || {}) }
)

const defaultLimit = computed(() => (props.size === 'mini' ? 3 : props.size === 'compact' ? 5 : 10))
const limitValue = computed(() => props.limit || defaultLimit.value)
const limitedItems = computed(() => sortedRecoveryData.value.slice(0, limitValue.value))

const visiblePages = computed(() => {
    const pages = []
    const maxVisible = 5
    const { current_page, last_page } = currentPagination.value

    let start = Math.max(1, current_page - Math.floor(maxVisible / 2))
    let end = Math.min(last_page, start + maxVisible - 1)

    if (end - start + 1 < maxVisible) {
        start = Math.max(1, end - maxVisible + 1)
    }

    for (let i = start; i <= end; i++) pages.push(i)
    return pages
})

const startEntry = computed(() =>
    (currentPagination.value.current_page - 1) * currentPagination.value.per_page + 1
)

const endEntry = computed(() =>
    Math.min(currentPagination.value.current_page * currentPagination.value.per_page, currentPagination.value.total)
)

const toggleSort = () => {
    sortOrder.value = sortOrder.value === 'desc' ? 'asc' : 'desc'
}

const emitRefresh = () => {
    if (isSelfFetch.value) fetchData(1)
    else emit('refresh')
}

const emitPage = (page) => {
    if (page < 1 || page > currentPagination.value.last_page) return
    if (isSelfFetch.value) fetchData(page)
    else emit('page-change', page)
}

const formatDate = (dateStr) => {
    if (!dateStr) return '-'
    return new Date(dateStr).toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric' })
}

const getDaysColor = (days) => {
    if (days <= 3) return 'text-green-600'
    if (days <= 7) return 'text-yellow-600'
    return 'text-red-600'
}

const destroyChart = () => {
    if (chartInstance) {
        chartInstance.destroy()
        chartInstance = null
    }
}

const renderChart = async () => {
    if (!props.showChart || !currentTrendData.value.length) return

    await nextTick()
    const canvas = isCompact.value ? compactChartCanvas.value : chartCanvas.value
    if (!canvas) return

    destroyChart()

    const validData = currentTrendData.value.filter(item => item.total_recovered > 0)
    if (!validData.length) return

    chartInstance = new Chart(canvas.getContext('2d'), {
        type: 'line',
        data: {
            labels: validData.map(item => months[item.month - 1]),
            datasets: [{
                label: 'Avg Recovery Days',
                data: validData.map(item => item.average_recovery_days),
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.15)',
                tension: 0.4,
                fill: true,
                pointRadius: 3,
                pointHoverRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: true, position: 'top' }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Days' }
                }
            }
        }
    })
}

onMounted(() => {
    renderChart()
})

onBeforeUnmount(() => {
    destroyChart()
})

watch([() => props.trendData, () => props.size, () => props.showChart], () => {
    destroyChart()
    renderChart()
})
</script>
