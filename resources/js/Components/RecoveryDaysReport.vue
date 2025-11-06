<template>
    <div class="border rounded-lg shadow-lg relative" :class="containerClass">
        <!-- Loading Overlay -->
        <div v-if="isLoading"
            class="absolute inset-0 bg-white bg-opacity-90 z-50 flex items-center justify-center rounded-lg">
            <div class="text-center">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-3"></div>
                <p class="text-sm text-gray-600 font-medium">Loading data...</p>
            </div>
        </div>

        <!-- Header -->
        <div class="p-2 sm:p-3 md:p-4 border-b">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 sm:gap-3">
                <div class="flex-shrink-0">
                    <h3 :class="titleClass">Recovery Days Report</h3>
                    <p v-if="!isCompact" class="text-[10px] sm:text-xs text-gray-600 mt-1">
                        Material recovery performance tracking
                    </p>
                </div>

                <!-- Filter -->
                <div class="flex items-center gap-1.5 sm:gap-2">
                    <select v-model="selectedMonth"
                        class="appearance-none w-24 px-3 py-2 pr-8 bg-white border border-gray-300 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400"
                        :disabled="isLoading">
                        <option v-for="(month, index) in months" :key="index" :value="index + 1">
                            {{ month.substring(0, 3) }}
                        </option>
                    </select>
                    <select v-model="selectedYear"
                        class="appearance-none w-24 px-3 py-2 pr-8 bg-white border border-gray-300 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400"
                        :disabled="isLoading">
                        <option v-for="year in years" :key="year" :value="year">
                            {{ year }}
                        </option>
                    </select>
                    <button @click="fetchReport"
                        class="bg-blue-500 hover:bg-blue-600 text-white rounded transition disabled:opacity-50 disabled:cursor-not-allowed text-[11px] sm:text-xs md:text-sm px-2 sm:px-3 md:px-2 py-1 whitespace-nowrap"
                        :disabled="isLoading">
                        {{ isLoading ? '...' : 'Go' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- STATS -->
        <div class="p-2 sm:p-3 md:p-4 bg-gray-50 border-b">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-1.5 sm:gap-2 md:gap-3">
                <div class="bg-blue-50 p-1.5 sm:p-2 md:p-3 rounded shadow-sm text-center border border-blue-200">
                    <p class="text-[9px] sm:text-[10px] md:text-xs text-gray-500 truncate">Total Recovered</p>
                    <p class="text-sm sm:text-base md:text-lg lg:text-xl font-bold text-blue-600">
                        {{ stats.total_recovered }}
                    </p>
                </div>
                <div class="bg-green-50 p-1.5 sm:p-2 md:p-3 rounded shadow-sm text-center border border-green-200">
                    <p class="text-[9px] sm:text-[10px] md:text-xs text-gray-500 truncate">Avg Days</p>
                    <p class="text-sm sm:text-base md:text-lg lg:text-xl font-bold text-green-600">
                        {{ stats.average_recovery_days }}
                    </p>
                </div>
                <div class="bg-yellow-50 p-1.5 sm:p-2 md:p-3 rounded shadow-sm text-center border border-yellow-200">
                    <p class="text-[9px] sm:text-[10px] md:text-xs text-gray-500 truncate">Fastest</p>
                    <p class="text-sm sm:text-base md:text-lg lg:text-xl font-bold text-yellow-600">
                        {{ stats.fastest_recovery }}
                    </p>
                </div>
                <div class="bg-red-50 p-1.5 sm:p-2 md:p-3 rounded shadow-sm text-center border border-red-200">
                    <p class="text-[9px] sm:text-[10px] md:text-xs text-gray-500 truncate">Slowest</p>
                    <p class="text-sm sm:text-base md:text-lg lg:text-xl font-bold text-red-600">
                        {{ stats.slowest_recovery }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Compact View (List + Chart) -->
        <div v-if="isCompact">
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
                    <div v-if="!report.length" class="text-center py-4 sm:py-6 text-gray-500">
                        <div class="text-xl sm:text-2xl mb-1">📊</div>
                        <div class="text-[10px] sm:text-xs font-semibold">No Data Available</div>
                    </div>
                </div>
            </div>

            <!-- Compact Chart -->
            <div v-if="showChart" class="p-2 sm:p-3 bg-white">
                <h4 class="text-[10px] sm:text-xs font-semibold text-gray-700 mb-2">Monthly Trend</h4>
                <canvas ref="compactChartCanvas" class="max-h-32" style="height: 120px;"></canvas>
            </div>

            <!-- View All Link -->
            <div class="p-2 sm:p-3 border-t">
                <a v-if="showViewAll && report.length > limit" :href="viewAllUrl"
                    class="block text-center text-[11px] sm:text-sm text-blue-600 hover:text-blue-800 font-semibold">
                    View All ({{ report.length }}) →
                </a>
            </div>
        </div>

        <!-- Full View (Table + Chart) -->
        <div v-else>
            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full border-collapse text-sm">
                    <thead class="bg-gray-100 border-b sticky top-0">
                        <tr>
                            <th class="p-2 sm:p-3 border text-left text-xs font-semibold">Material</th>
                            <th class="p-2 sm:p-3 border text-left text-xs font-semibold hidden md:table-cell">
                                Description</th>
                            <th class="p-2 sm:p-3 border text-left text-xs font-semibold">PIC</th>
                            <th class="p-2 sm:p-3 border text-center text-xs font-semibold cursor-pointer hover:bg-gray-200"
                                @click="toggleSort">
                                Recovery Days {{ sortOrder === 'desc' ? '↓' : '↑' }}
                            </th>
                            <th class="p-2 sm:p-3 border text-center text-xs font-semibold">Problem Date</th>
                            <th class="p-2 sm:p-3 border text-center text-xs font-semibold">Recovery Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in sortedReport" :key="item.material_number"
                            class="hover:bg-gray-50 transition border-b">
                            <td class="p-2 sm:p-3 border text-xs">
                                <div class="font-semibold">{{ item.material_number }}</div>
                                <div class="text-[10px] text-gray-500 md:hidden truncate">{{ item.description }}</div>
                            </td>
                            <td class="p-2 sm:p-3 border text-xs hidden md:table-cell">{{ item.description }}</td>
                            <td class="p-2 sm:p-3 border text-xs">{{ item.pic }}</td>
                            <td class="p-2 sm:p-3 border text-center">
                                <span class="font-bold text-base" :class="{
                                    'text-green-600': item.recovery_days <= 3,
                                    'text-yellow-600': item.recovery_days > 3 && item.recovery_days <= 7,
                                    'text-red-600': item.recovery_days > 7,
                                }">
                                    {{ item.recovery_days }}
                                </span>
                            </td>
                            <td class="p-2 sm:p-3 border text-center text-xs text-gray-600">
                                {{ formatDate(item.last_problem_date) }}
                            </td>
                            <td class="p-2 sm:p-3 border text-center text-xs bg-green-50 font-medium">
                                {{ formatDate(item.recovery_date) }}
                            </td>
                        </tr>

                        <!-- Empty State -->
                        <tr v-if="!sortedReport.length">
                            <td colspan="6" class="p-6 text-center text-gray-500">
                                <div class="text-3xl mb-2">📊</div>
                                <div class="text-base font-semibold">No Recovery Data</div>
                                <div class="text-xs">No materials recovered in this period</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Full Chart -->
            <div v-if="showChart" class="p-3 sm:p-4 border-t bg-white">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-base sm:text-lg font-semibold text-gray-700">
                        Monthly Recovery Trend - {{ selectedYear }}
                    </h3>
                </div>
                <canvas ref="fullChartCanvas" class="w-full max-h-44" style="height: 300px;"></canvas>
            </div>
        </div>

        <!-- Footer - Refresh button -->
        <div v-if="!hideRefresh" class="p-3 border-t flex justify-end bg-gray-50">
            <button @click="fetchReport" :disabled="isLoading"
                :class="isCompact ? 'w-full text-xs' : 'w-full sm:w-auto text-sm'"
                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition disabled:opacity-50">
                <span v-if="isLoading">Refreshing...</span>
                <span v-else>Refresh</span>
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from "vue";
import axios from "axios";
import Chart from "chart.js/auto";

// Props
const props = defineProps({
    size: {
        type: String,
        default: 'full',
        validator: (value) => ['compact', 'full'].includes(value)
    },
    limit: {
        type: Number,
        default: 5
    },
    showViewAll: {
        type: Boolean,
        default: true
    },
    viewAllUrl: {
        type: String,
        default: '/recovery-report'
    },
    showChart: {
        type: Boolean,
        default: true
    },
    hideRefresh: {
        type: Boolean,
        default: false
    }
});

// State
const report = ref([]);
const stats = ref({
    total_recovered: 0,
    average_recovery_days: 0,
    fastest_recovery: 0,
    slowest_recovery: 0,
});
const monthlyTrend = ref([]);
const sortOrder = ref("desc");
const isLoading = ref(false);

const compactChartCanvas = ref(null);
const fullChartCanvas = ref(null);
let chartInstance = null;

const months = [
    "January", "February", "March", "April", "May", "June",
    "July", "August", "September", "October", "November", "December"
];
const years = [2024, 2025, 2026];
const selectedMonth = ref(new Date().getMonth() + 1);
const selectedYear = ref(new Date().getFullYear());

// Computed properties
const isCompact = computed(() => props.size === 'compact');

const containerClass = computed(() => {
    if (props.size === 'compact') return 'max-h-[900px] overflow-hidden';
    return 'min-h-[400px]';
});

const titleClass = computed(() => {
    if (props.size === 'compact') return 'text-sm sm:text-base md:text-lg font-bold';
    return 'text-base sm:text-lg md:text-xl lg:text-2xl font-bold';
});

const limitedItems = computed(() => {
    return sortedReport.value.slice(0, props.limit);
});

const sortedReport = computed(() => {
    return [...report.value].sort((a, b) =>
        sortOrder.value === "desc"
            ? b.recovery_days - a.recovery_days
            : a.recovery_days - b.recovery_days
    );
});

const toggleSort = () => {
    sortOrder.value = sortOrder.value === "desc" ? "asc" : "desc";
};

// Chart rendering with Chart.js
const renderChart = () => {
    if (!monthlyTrend.value || !monthlyTrend.value.length) return;

    const validData = monthlyTrend.value.filter(m => m.total_recovered > 0);
    if (!validData.length) return;

    const canvas = isCompact.value ? compactChartCanvas.value : fullChartCanvas.value;
    if (!canvas) return;

    // Destroy existing chart
    if (chartInstance) {
        chartInstance.destroy();
        chartInstance = null;
    }

    const ctx = canvas.getContext('2d');

    // Clear canvas
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    chartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: validData.map(m => months[m.month - 1]),
            datasets: [{
                label: 'Avg Recovery Days',
                data: validData.map(m => m.average_recovery_days),
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
                fill: true,
                pointRadius: isCompact.value ? 2 : 3,
                pointHoverRadius: isCompact.value ? 4 : 5,
                pointBackgroundColor: '#3b82f6',
                pointBorderColor: '#fff',
                pointBorderWidth: 1,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    display: !isCompact.value,
                    position: 'bottom',
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            return `Average: ${context.parsed.y} days`;
                        }
                    }
                },
                zoom: {
                    zoom: {
                        enabled: false
                    },
                    pan: {
                        enabled: false
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        font: {
                            size: isCompact.value ? 9 : 11
                        },
                        callback: function (value) {
                            return value + 'd';
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    ticks: {
                        font: {
                            size: isCompact.value ? 9 : 11
                        }
                    },
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
};

// API fetch
const fetchReport = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get(
            `/api/reports/recovery-days?month=${selectedYear.value}-${String(selectedMonth.value).padStart(2, '0')}`
        );
        report.value = response.data.data || [];
        stats.value = response.data.statistics || {
            total_recovered: 0,
            average_recovery_days: 0,
            fastest_recovery: 0,
            slowest_recovery: 0,
        };

        const trendRes = await axios.get(`/api/reports/recovery-trend?year=${selectedYear.value}`);
        monthlyTrend.value = trendRes.data.data || [];

        await nextTick();
        if (props.showChart) {
            renderChart();
        }
    } catch (error) {
        console.error("Failed to fetch recovery report:", error);
        report.value = [];
        monthlyTrend.value = [];
    } finally {
        isLoading.value = false;
    }
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString("en-US", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    });
};

const formatDateShort = (dateStr) => {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString("en-US", {
        month: 'short',
        day: 'numeric',
    });
};

// Lifecycle
onMounted(fetchReport);

onBeforeUnmount(() => {
    if (chartInstance) {
        chartInstance.destroy();
        chartInstance = null;
    }
});

// Watch for changes
watch(() => props.size, async () => {
    if (chartInstance) {
        chartInstance.destroy();
        chartInstance = null;
    }
    await nextTick();
    if (props.showChart && monthlyTrend.value.length) {
        renderChart();
    }
});

watch(() => props.showChart, async (newVal) => {
    if (newVal) {
        await nextTick();
        renderChart();
    } else {
        if (chartInstance) {
            chartInstance.destroy();
            chartInstance = null;
        }
    }
});
</script>

<style scoped>
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>
