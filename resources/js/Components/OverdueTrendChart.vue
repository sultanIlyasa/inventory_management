<template>
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="flex items-center gap-2">
            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-red-50 text-red-600">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                    <path d="M4 19V5" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    <path d="M4 19h16" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    <path d="M8 15V9" stroke="currentColor" stroke-width="2" opacity=".5" />
                    <path d="M12 15V5" stroke="currentColor" stroke-width="2" opacity=".65" />
                    <path d="M16 15v-5" stroke="currentColor" stroke-width="2" opacity=".8" />
                    <path d="M20 15v-3" stroke="currentColor" stroke-width="2" />
                </svg>
            </div>
            <p class="text-sm font-semibold text-gray-900">Status Count Overview</p>
        </div>
        <div class="mt-3">
            <canvas ref="canvasRef" class="h-40 w-full"></canvas>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import Chart from 'chart.js/auto'

// data = dashboardData.barChart = { statusBarChart: [{status, count}], summary: {...} }
type StatusBar = { status: string; count: number }

const props = defineProps<{
    data?: { statusBarChart?: StatusBar[]; summary?: Record<string, number> } | null
    filters?: Record<string, unknown>
}>()

const canvasRef = ref<HTMLCanvasElement | null>(null)
let chartInstance: Chart | null = null

const STATUS_COLORS: Record<string, string> = {
    SHORTAGE:  '#ef4444',
    CAUTION:   '#f59e0b',
    OK:        '#10b981',
    OVERFLOW:  '#3b82f6',
    UNCHECKED: '#9ca3af',
}

function getChartData() {
    const bars = props.data?.statusBarChart
    if (bars?.length) {
        return {
            labels: bars.map(d => d.status),
            values: bars.map(d => d.count),
            colors: bars.map(d => STATUS_COLORS[d.status] ?? '#9ca3af'),
        }
    }
    return { labels: [] as string[], values: [] as number[], colors: [] as string[] }
}

function initChart() {
    if (!canvasRef.value) return
    if (chartInstance) { chartInstance.destroy(); chartInstance = null }
    const { labels, values, colors } = getChartData()
    chartInstance = new Chart(canvasRef.value, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Materials',
                data: values,
                backgroundColor: colors.map(c => c + 'bf'), // 75% opacity
                borderColor: colors,
                borderWidth: 1,
                borderRadius: 6,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { enabled: true } },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#6b7280', font: { size: 10 } } },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(17,24,39,0.06)' },
                    ticks: { color: '#6b7280', font: { size: 10 }, precision: 0 },
                },
            },
        },
    })
}

onMounted(() => initChart())
onBeforeUnmount(() => { chartInstance?.destroy(); chartInstance = null })
watch(() => props.data, () => { chartInstance?.destroy(); chartInstance = null; initChart() }, { deep: true })
</script>
