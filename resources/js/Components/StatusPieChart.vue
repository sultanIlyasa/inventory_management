<template>
    <div class="bg-white border rounded-lg shadow-lg p-2 sm:p-3 lg:p-4">
        <!-- Header -->
        <div class="mb-2 sm:mb-3 lg:mb-4">
            <h3 :class="titleClass" class="flex justify-center">{{ title }}</h3>
            <p v-if="!isCompact" class="text-[10px] sm:text-xs text-gray-600 mt-1 text-center">
                Distribution of materials by current status
            </p>
        </div>

        <!-- Chart + Legend Wrapper -->
        <div :class="[
            'w-full flex transition-all duration-300',
            isCompact
                ? 'flex-col sm:flex-row items-center justify-between gap-2 sm:gap-3 lg:gap-4'
                : 'flex-col'
        ]">
            <!-- Chart -->
            <div :class="[
                'flex-1 flex items-center justify-center w-full',
                isCompact ? 'sm:max-w-[60%] md:max-w-[65%]' : 'w-full'
            ]" :style="{ height: chartHeight }">
                <div v-if="loading" class="text-gray-500 text-xs sm:text-sm">Loading...</div>
                <canvas v-else ref="chartCanvas"></canvas>
            </div>

            <!-- Legend / Stats -->
            <div v-if="stats" :class="[
                'w-full transition-all duration-300',
                isCompact
                    ? 'sm:w-[40%] md:w-[35%] flex flex-col gap-1.5 sm:gap-2'
                    : 'mt-3 sm:mt-4 grid grid-cols-2 gap-2 sm:gap-3'
            ]">
                <div v-for="status in statusList" :key="status.name" :class="[
                    'flex items-center justify-between rounded',
                    isCompact ? 'p-1.5 sm:p-2' : 'p-2 sm:p-3'
                ]" :style="{ backgroundColor: status.color + '20' }">
                    <div class="flex items-center gap-1 sm:gap-2 min-w-0">
                        <div :class="[
                            'rounded-full flex-shrink-0',
                            isCompact ? 'w-2 h-2 sm:w-3 sm:h-3' : 'w-3 h-3'
                        ]" :style="{ backgroundColor: status.color }"></div>
                        <span :class="[
                            'font-medium truncate',
                            isCompact ? 'text-[10px] sm:text-xs' : 'text-xs sm:text-sm'
                        ]">{{ status.name }}</span>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <div :class="[
                            'font-bold',
                            isCompact ? 'text-xs sm:text-sm lg:text-base' : 'text-sm sm:text-base lg:text-lg'
                        ]">{{ status.count }}</div>
                        <div :class="[
                            'text-gray-600',
                            isCompact ? 'text-[9px] sm:text-[10px]' : 'text-[10px] sm:text-xs'
                        ]">{{ status.percentage }}%</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- More Info Button (Compact Mode Only) -->
        <div v-if="isCompact" class="flex justify-center mt-2 sm:mt-3">
            <button
                class="bg-blue-500 hover:bg-blue-600 active:bg-blue-700 px-2 sm:px-3 py-1 sm:py-1.5 rounded text-white text-[10px] sm:text-xs lg:text-sm font-medium transition-colors">
                More Info
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { Chart, ArcElement, Tooltip, Legend, PieController } from 'chart.js'
import axios from 'axios'

Chart.register(ArcElement, Tooltip, Legend, PieController)

const props = defineProps({
    size: {
        type: String,
        default: 'FULL',
        validator: (value) => ['COMPACT', 'FULL', 'MINI'].includes(value)
    },
    title: {
        type: String,
        default: 'Material Status Distribution'
    },
    apiUrl: {
        type: String,
        default: '/api/reports/general'
    },
    autoRefresh: {
        type: Boolean,
        default: false
    },
    refreshInterval: {
        type: Number,
        default: 60000
    }
})

const chartCanvas = ref(null)
const chartInstance = ref(null)
const reportData = ref([])
const loading = ref(false)
let intervalId = null

const isCompact = computed(() => props.size === 'COMPACT' || props.size === 'MINI')

const titleClass = computed(() => {
    if (props.size === 'MINI') return 'text-xs sm:text-sm font-bold'
    if (props.size === 'COMPACT') return 'text-sm sm:text-base lg:text-lg font-bold'
    return 'text-base sm:text-lg lg:text-xl xl:text-2xl font-bold'
})

const chartHeight = computed(() => {
    // Responsive heights based on screen size and component size
    if (props.size === 'MINI') {
        return window.innerWidth < 640 ? '150px' : '200px'
    }
    if (props.size === 'COMPACT') {
        if (window.innerWidth < 640) return '200px'
        if (window.innerWidth < 768) return '250px'
        return '300px'
    }
    // FULL size
    if (window.innerWidth < 640) return '250px'
    if (window.innerWidth < 768) return '300px'
    if (window.innerWidth < 1024) return '350px'
    return '400px'
})

const stats = computed(() => {
    if (!reportData.value || reportData.value.length === 0) {
        return null
    }

    const statusCounts = {
        // OK: 0,
        CAUTION: 0,
        SHORTAGE: 0,
        OVERFLOW: 0,
        UNCHECKED: 0,
    }

    reportData.value.forEach(material => {
        const status = material.current_status
        if (statusCounts.hasOwnProperty(status)) {
            statusCounts[status]++
        }
    })

    const total = reportData.value.length

    return {
        // OK: {
        //     count: statusCounts.OK,
        //     percentage: total > 0 ? ((statusCounts.OK / total) * 100).toFixed(1) : 0
        // },
        CAUTION: {
            count: statusCounts.CAUTION,
            percentage: total > 0 ? ((statusCounts.CAUTION / total) * 100).toFixed(1) : 0
        },
        SHORTAGE: {
            count: statusCounts.SHORTAGE,
            percentage: total > 0 ? ((statusCounts.SHORTAGE / total) * 100).toFixed(1) : 0
        },
        OVERFLOW: {
            count: statusCounts.OVERFLOW,
            percentage: total > 0 ? ((statusCounts.OVERFLOW / total) * 100).toFixed(1) : 0
        },
        UNCHECKED: {
            count: statusCounts.UNCHECKED,
            percentage: total > 0 ? ((statusCounts.UNCHECKED / total) * 100).toFixed() : 0
        }
    }
})

const statusList = computed(() => {
    if (!stats.value) return []

    return [
        // {
        //     name: 'OK',
        //     count: stats.value.OK.count,
        //     percentage: stats.value.OK.percentage,
        //     color: '#10b981'
        // },
        {
            name: 'CAUTION',
            count: stats.value.CAUTION.count,
            percentage: stats.value.CAUTION.percentage,
            color: '#f59e0b'
        },
        {
            name: 'SHORTAGE',
            count: stats.value.SHORTAGE.count,
            percentage: stats.value.SHORTAGE.percentage,
            color: '#ef4444'
        },
        {
            name: 'OVERFLOW',
            count: stats.value.OVERFLOW.count,
            percentage: stats.value.OVERFLOW.percentage,
            color: '#6366f1'
        },
        {
            name: 'UNCHECKED',
            count: stats.value.UNCHECKED.count,
            percentage: stats.value.UNCHECKED.percentage,
            color: '#A9A9A9'
        }
    ]
})

const fetchData = async () => {
    loading.value = true
    try {
        const res = await axios.get(props.apiUrl)
        reportData.value = res.data.data || []
    } catch (error) {
        console.error('Failed to load data:', error)
        reportData.value = []
    } finally {
        loading.value = false
    }
}

const createChart = () => {
    if (!chartCanvas.value || !stats.value) return

    if (chartInstance.value) {
        chartInstance.value.destroy()
    }

    const ctx = chartCanvas.value.getContext('2d')

    // Responsive font sizes
    const isMobile = window.innerWidth < 640
    const isTablet = window.innerWidth >= 640 && window.innerWidth < 1024

    let legendFontSize = 12
    let tooltipFontSize = 12

    if (isMobile) {
        legendFontSize = props.size === 'MINI' ? 9 : 10
        tooltipFontSize = 10
    } else if (isTablet) {
        legendFontSize = props.size === 'COMPACT' ? 10 : 11
        tooltipFontSize = 11
    }

    chartInstance.value = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: [
                // 'OK',
                'CAUTION',
                'SHORTAGE',
                'OVERFLOW',
                'UNCHECKED'],
            datasets: [{
                data: [
                    // stats.value.OK.count,
                    stats.value.CAUTION.count,
                    stats.value.SHORTAGE.count,
                    stats.value.OVERFLOW.count,
                    stats.value.UNCHECKED.count

                ],
                backgroundColor: [
                    // '#10b981',
                    '#f59e0b',
                    '#ef4444',
                    '#6366f1',
                    '#A9A9A9'

                ],
                borderColor: '#ffffff',
                borderWidth: isMobile ? 1 : 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false, // Always hide built-in legend, we have custom one
                },
                tooltip: {
                    enabled: true,
                    titleFont: {
                        size: tooltipFontSize
                    },
                    bodyFont: {
                        size: tooltipFontSize
                    },
                    padding: isMobile ? 8 : 12,
                    callbacks: {
                        label: (context) => {
                            const label = context.label || ''
                            const value = context.parsed || 0
                            const total = context.dataset.data.reduce((a, b) => a + b, 0)
                            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0
                            return `${label}: ${value} (${percentage}%)`
                        }
                    }
                }
            }
        }
    })
}

// Handle window resize
const handleResize = () => {
    nextTick(() => {
        createChart()
    })
}

watch(stats, () => {
    nextTick(() => {
        createChart()
    })
})

onMounted(async () => {
    await fetchData()
    await nextTick()
    createChart()

    // Add resize listener
    window.addEventListener('resize', handleResize)

    if (props.autoRefresh) {
        intervalId = setInterval(fetchData, props.refreshInterval)
    }
})

onUnmounted(() => {
    if (chartInstance.value) {
        chartInstance.value.destroy()
    }

    window.removeEventListener('resize', handleResize)

    if (intervalId) {
        clearInterval(intervalId)
    }
})
</script>
