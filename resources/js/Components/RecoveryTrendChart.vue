<template>
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="flex items-center gap-2">
            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                    <path d="M4 14l5-5 4 4 7-7" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M20 7v6h-6" stroke="currentColor" stroke-width="2" opacity=".3" />
                </svg>
            </div>
            <p class="text-sm font-semibold text-gray-900">Recovery Days Trend</p>
        </div>
        <div class="mt-3">
            <canvas ref="canvasRef" class="h-40 w-full"></canvas>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import Chart from 'chart.js/auto'

type TrendItem = { month: number; average_recovery_days: number; total_recovered: number }

const props = defineProps<{
    data?: TrendItem[] | null
    filters?: Record<string, unknown>
}>()

const canvasRef = ref<HTMLCanvasElement | null>(null)
let chartInstance: Chart | null = null

const COLORS = { green: '#10b981' }
const alpha = (hex: string, a: number) => {
    const h = hex.replace('#', '')
    const r = parseInt(h.substring(0, 2), 16)
    const g = parseInt(h.substring(2, 4), 16)
    const b = parseInt(h.substring(4, 6), 16)
    return `rgba(${r},${g},${b},${a})`
}

const MONTH_NAMES = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']

function getChartData() {
    const trend = props.data
    if (trend?.length) {
        return {
            labels: trend.map(d => MONTH_NAMES[d.month - 1] ?? String(d.month)),
            values: trend.map(d => d.average_recovery_days),
        }
    }
    // Fallback while data loads
    return {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
        values: [0, 0, 0, 0, 0, 0],
    }
}

function initChart() {
    if (!canvasRef.value) return
    if (chartInstance) { chartInstance.destroy(); chartInstance = null }
    const { labels, values } = getChartData()
    chartInstance = new Chart(canvasRef.value, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Avg Recovery Days',
                data: values,
                tension: 0.35,
                pointRadius: 3,
                borderWidth: 2,
                borderColor: COLORS.green,
                backgroundColor: alpha(COLORS.green, 0.15),
                pointBackgroundColor: COLORS.green,
                fill: true,
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
                    ticks: { color: '#6b7280', font: { size: 10 } },
                },
            },
        },
    })
}

onMounted(() => initChart())
onBeforeUnmount(() => { chartInstance?.destroy(); chartInstance = null })
watch(() => props.data, () => { chartInstance?.destroy(); chartInstance = null; initChart() }, { deep: true })
</script>
