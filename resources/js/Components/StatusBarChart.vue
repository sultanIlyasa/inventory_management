<template>
    <div class="w-full p-4 bg-white rounded-lg shadow">

        <!-- Empty state -->
        <div v-if="!chartData || chartData.length === 0" class="text-gray-600 text-center py-10">
            No status data available.
        </div>

        <!-- Bar chart -->
        <canvas v-else ref="canvas" class="w-full h-64"></canvas>
    </div>
</template>

<script setup>
import { ref, watch, onMounted } from "vue";
import {
    Chart,
    BarController,
    BarElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend,
} from "chart.js";

Chart.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend);

const props = defineProps({
    chartData: {
        type: Array,
        default: () => []
    }
});

const canvas = ref(null);
let barChart = null;

const renderChart = () => {
    if (!canvas.value) return;

    if (barChart) barChart.destroy();

    const dataset = Array.isArray(props.chartData) ? props.chartData : [];

    if (!dataset.length) return;

    barChart = new Chart(canvas.value, {
        type: "bar",
        data: {
            labels: dataset.map(item => item.status),
            datasets: [
                {
                    label: "Count",
                    data: dataset.map(item => item.count),
                }
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
        },
    });
};

// Re-render when data changes
watch(
    () => props.chartData,
    () => renderChart(),
    { deep: true }
);

onMounted(renderChart);
</script>
