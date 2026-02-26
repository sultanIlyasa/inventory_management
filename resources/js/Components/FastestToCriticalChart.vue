<template>
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="flex items-center gap-2">
            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-orange-50 text-orange-600">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                    <path d="M12 2l4 8-4 4-4-4 4-8z" stroke="currentColor" stroke-width="2" />
                    <path d="M6 22h12" stroke="currentColor" stroke-width="2" opacity=".35" />
                    <path d="M8 18h8" stroke="currentColor" stroke-width="2" opacity=".5" />
                </svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-gray-900">Top 5 Fastest to Critical</p>
                <p class="text-xs text-gray-500">Mean time to critical materials (days)</p>
            </div>
        </div>

        <div class="mt-3">
            <canvas ref="canvasRef" class="h-56 w-full"></canvas>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import Chart from 'chart.js/auto'

// Each item from shortage leaderboard: { material_number, description, days, current_stock, ... }
type LeaderboardItem = { material_number: string; description: string; days: number; current_stock: number }

const props = defineProps<{
    data?: LeaderboardItem[] | null
    filters?: Record<string, unknown>
}>()

const canvasRef = ref<HTMLCanvasElement | null>(null)
let chartInstance: Chart | null = null

const COLORS = { orange: '#f97316', gray: '#9ca3af' }
const alpha = (hex: string, a: number) => {
    const h = hex.replace('#', '')
    const r = parseInt(h.substring(0, 2), 16)
    const g = parseInt(h.substring(2, 4), 16)
    const b = parseInt(h.substring(4, 6), 16)
    return `rgba(${r},${g},${b},${a})`
}

function truncate(s: string, max = 20) {
    return s.length > max ? s.slice(0, max) + '…' : s
}

function getChartData() {
    const rows = props.data?.slice(0, 5)
    if (rows?.length) {
        return {
            labels: rows.map(x => truncate(x.description ?? x.material_number ?? '')),
            values: rows.map(x => x.days ?? 0),
        }
    }
    // Empty state while loading
    return { labels: [], values: [] }
}

function initChart() {
    if (!canvasRef.value) return
    if (chartInstance) { chartInstance.destroy(); chartInstance = null }
    const { labels, values } = getChartData()
    chartInstance = new Chart(canvasRef.value, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Runway (days)',
                data: values,
                borderRadius: 8,
                backgroundColor: alpha(COLORS.orange, 0.75),
                borderColor: COLORS.orange,
                borderWidth: 1,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: { legend: { display: false }, tooltip: { enabled: true } },
            scales: {
                x: { grid: { display: false }, ticks: { color: COLORS.gray } },
                y: { grid: { color: alpha('#111827', 0.06) }, ticks: { color: COLORS.gray } },
            },
        },
    })
}

onMounted(() => initChart())
onBeforeUnmount(() => { chartInstance?.destroy(); chartInstance = null })
watch(() => props.data, () => { chartInstance?.destroy(); chartInstance = null; initChart() }, { deep: true })
</script>
