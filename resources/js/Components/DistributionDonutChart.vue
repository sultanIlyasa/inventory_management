<template>
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="flex items-center gap-2">
            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                    <path d="M4 19V5" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    <path d="M4 19h16" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    <path d="M8 16V8" stroke="currentColor" stroke-width="2" opacity=".5" />
                    <path d="M12 16V6" stroke="currentColor" stroke-width="2" opacity=".6" />
                    <path d="M16 16v-4" stroke="currentColor" stroke-width="2" opacity=".7" />
                </svg>
            </div>
            <p class="text-sm font-semibold text-gray-900">Material Status Distribution</p>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-3">
            <div class="h-48">
                <canvas ref="canvasRef" class="h-full w-full"></canvas>
            </div>

            <div class="grid grid-cols-3 gap-2 text-center">
                <div class="rounded-lg border border-gray-200 p-2">
                    <div class="text-xs text-gray-500">Ok</div>
                    <div class="text-sm font-semibold text-gray-900">{{ distribution.ok }}</div>
                </div>
                <div class="rounded-lg border border-gray-200 p-2">
                    <div class="text-xs text-gray-500">Caution</div>
                    <div class="text-sm font-semibold text-gray-900">{{ distribution.caution }}</div>
                </div>
                <div class="rounded-lg border border-gray-200 p-2">
                    <div class="text-xs text-gray-500">Shortage</div>
                    <div class="text-sm font-semibold text-gray-900">{{ distribution.shortage }}</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, onBeforeUnmount, watch } from 'vue'
import Chart from 'chart.js/auto'

// data = dashboardData.barChart = { statusBarChart: [...], summary: { OK, CAUTION, SHORTAGE, OVERFLOW, UNCHECKED } }
const props = defineProps<{
    data?: { summary?: Record<string, number>; statusBarChart?: { status: string; count: number }[] } | null
    filters?: Record<string, unknown>
}>()

const canvasRef = ref<HTMLCanvasElement | null>(null)
let chartInstance: Chart | null = null

const COLORS = { green: '#10b981', amber: '#f59e0b', red: '#ef4444' }

const distribution = computed(() => {
    const s = props.data?.summary
    return {
        ok: s?.OK ?? 0,
        caution: s?.CAUTION ?? 0,
        shortage: s?.SHORTAGE ?? 0,
    }
})

function initChart() {
    if (!canvasRef.value) return
    if (chartInstance) { chartInstance.destroy(); chartInstance = null }
    chartInstance = new Chart(canvasRef.value, {
        type: 'doughnut',
        data: {
            labels: ['Ok', 'Caution', 'Shortage'],
            datasets: [{
                data: [distribution.value.ok, distribution.value.caution, distribution.value.shortage],
                borderWidth: 0,
                backgroundColor: [COLORS.green, COLORS.amber, COLORS.red],
                hoverOffset: 6,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: { legend: { position: 'bottom' } },
        },
    })
}

onMounted(() => initChart())
onBeforeUnmount(() => { chartInstance?.destroy(); chartInstance = null })
watch(() => props.data, () => { chartInstance?.destroy(); chartInstance = null; initChart() }, { deep: true })
</script>
