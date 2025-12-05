<template>
    <div class="p-4 space-y-4">

        <!-- COLLAPSIBLE FILTERS -->
        <div class="bg-white border rounded-lg shadow-sm p-3">
            <button class="w-full flex items-center justify-between font-medium text-gray-700 text-sm"
                @click="showFilters = !showFilters">
                <div class="flex items-center gap-2">
                    <span>Filters</span>
                    <span v-if="activeFilterCount > 0"
                        class="px-1.5 py-0.5 bg-blue-100 text-blue-700 rounded-full text-xs">
                        {{ activeFilterCount }}
                    </span>
                </div>

                <span class="transition-transform text-xs" :class="showFilters ? 'rotate-180' : ''">
                    ▼
                </span>
            </button>

            <!-- FILTER BODY -->
            <transition name="slide">
                <div v-if="showFilters" class="mt-3 space-y-3">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">

                        <div>
                            <label class="text-xs font-medium">Month</label>
                            <input type="month" v-model="filters.month"
                                class="w-full border rounded-md px-2 py-1.5 text-xs" />
                        </div>

                        <div>
                            <label class="text-xs font-medium">Usage</label>
                            <select v-model="filters.usage" class="w-full border rounded-md px-2 py-1.5 text-xs">
                                <option value="">All</option>
                                <option value="DAILY">Daily</option>
                                <option value="WEEKLY">Weekly</option>
                                <option value="MONTHLY">Monthly</option>
                            </select>
                        </div>

                        <div>
                            <label class="text-xs font-medium">Location</label>
                            <select v-model="filters.location" class="w-full border rounded-md px-2 py-1.5 text-xs">
                                <option value="">All</option>
                                <option value="SUNTER_1">Sunter 1</option>
                                <option value="SUNTER_2">Sunter 2</option>
                            </select>
                        </div>

                        <div>
                            <label class="text-xs font-medium">Gentani</label>
                            <select v-model="filters.gentani" class="w-full border rounded-md px-2 py-1.5 text-xs">
                                <option value="">All</option>
                                <option value="GENTAN-I">Gentan-I</option>
                                <option value="NON_GENTAN-I">Non Gentan-I</option>
                            </select>
                        </div>

                    </div>

                    <div class="flex justify-end gap-2">
                        <button class="px-3 py-1.5 bg-blue-600 text-white rounded-md text-xs" @click="applyFilters">
                            Apply
                        </button>
                        <button class="px-3 py-1.5 bg-gray-200 rounded-md text-xs" @click="clearFilters">
                            Clear
                        </button>
                    </div>
                </div>
            </transition>
        </div>

        <!-- KPI Charts -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            <!-- Pie Chart -->
            <div class="bg-white p-4 border rounded-lg shadow-sm">
                <h2 class="text-sm font-semibold mb-2">Shortage Distribution</h2>
                <PieChart :chartData="pieChartData" height="160" />
            </div>

            <!-- Bar Chart -->
            <div class="bg-white p-4 border rounded-lg shadow-sm">
                <h2 class="text-sm font-semibold mb-2">Overdue vs Recovery</h2>
                <BarChart :chartData="barChartData" height="160" />
            </div>

        </div>

        <!-- Trend Line -->
        <div class="bg-white p-4 border rounded-lg shadow-sm">
            <h2 class="text-sm font-semibold mb-2">Overdue vs Recovery Trend</h2>
            <LineChart :chartData="lineChartData" height="180" />
        </div>

        <!-- Weekly Chart -->
        <div class="bg-white p-4 border rounded-lg shadow-sm">
            <h2 class="text-sm font-semibold mb-2">Status Changes (Weekly)</h2>
            <BarChart :chartData="statusChangeData" height="180" />
        </div>

    </div>
</template>

<script setup>
import { ref, computed } from "vue";
import { Pie, Bar, Line } from "vue-chartjs";
import {
    Chart as ChartJS,
    ArcElement,
    LineElement,
    PointElement,
    BarElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend
} from "chart.js";

ChartJS.register(
    ArcElement, LineElement, PointElement,
    BarElement, CategoryScale, LinearScale,
    Tooltip, Legend
);

const showFilters = ref(true);

const filters = ref({
    month: "",
    usage: "",
    location: "",
    gentani: ""
});

const activeFilterCount = computed(() =>
    Object.values(filters.value).filter(v => v).length
);

const applyFilters = () => console.log(filters.value);
const clearFilters = () => filters.value = { month: "", usage: "", location: "", gentani: "" };

// Dummy KPI Data
const critical = 23;
const caution = 47;
const avgOverdue = 85;
const avgRecovery = 118;

// Chart Data
const pieChartData = ref({
    labels: ["Critical", "Caution"],
    datasets: [{ data: [critical, caution], backgroundColor: ["#ef4444", "#f59e0b"] }]
});

const barChartData = ref({
    labels: ["Avg Overdue Days", "Avg Recovery Days"],
    datasets: [{
        label: "Days",
        data: [avgOverdue, avgRecovery],
        backgroundColor: ["#60a5fa", "#34d399"]
    }]
});

const lineChartData = ref({
    labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun"],
    datasets: [
        { label: "Overdue", data: [140, 155, 135, 128, 95, 80], borderColor: "#ef4444", fill: false },
        { label: "Recovery", data: [90, 95, 110, 100, 95, 120], borderColor: "#22c55e", fill: false }
    ]
});

const statusChangeData = ref({
    labels: ["W1", "W2", "W3", "W4"],
    datasets: [{ label: "Changes", data: [22, 33, 28, 45], backgroundColor: "#a855f7" }]
});

// Inline Chart Components
const PieChart = { props: ["chartData", "height"], components: { Pie }, template: `<Pie :data="chartData" :height="height" />` };
const BarChart = { props: ["chartData", "height"], components: { Bar }, template: `<Bar :data="chartData" :height="height" />` };
const LineChart = { props: ["chartData", "height"], components: { Line }, template: `<Line :data="chartData" :height="height" />` };
</script>

<style scoped>
.slide-enter-active,
.slide-leave-active {
    transition: all 0.25s ease;
}

.slide-enter-from,
.slide-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
