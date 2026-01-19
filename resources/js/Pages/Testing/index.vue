<template>
    <MainAppLayout>
        <div class="min-h-screen bg-gray-50 p-6 font-sans text-slate-800">

            <div
                class="flex flex-col md:flex-row items-start md:items-center justify-between bg-red-50 border border-red-100 rounded-lg p-3 mb-6 shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="bg-red-600 text-white text-xs font-bold px-3 py-1 rounded shadow-sm">High Risk</span>
                    <h1 class="font-semibold text-red-900 text-sm md:text-base">1 Material at Line-Stop Risk</h1>
                </div>
                <div class="flex items-center gap-4 mt-2 md:mt-0 text-sm text-slate-600">
                    <div class="flex items-center gap-1.5">
                        <AlertCircle class="w-4 h-4 text-red-500" /><span class="font-medium">3 Shortages</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <AlertTriangle class="w-4 h-4 text-orange-500" /><span class="font-medium">3 Cautions</span>
                    </div>
                    <div class="flex items-center gap-1.5 text-slate-400 border-l pl-4 border-slate-200">
                        <Clock class="w-4 h-4" /><span>Updated: {{ lastUpdated }}</span>
                    </div>
                </div>
            </div>
            <!-- Section head escalation overview -->
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">

                <div class="xl:col-span-8 space-y-6">

                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-4 border-b border-slate-100 flex items-center gap-2">
                            <div class="p-1.5 bg-red-50 rounded text-red-600">
                                <AlertCircle class="w-5 h-5" />
                            </div>
                            <h2 class="font-semibold text-slate-800">Problematic Materials - Immediate Action Required
                            </h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-slate-50 text-slate-500 font-medium border-b border-slate-100">
                                    <tr>
                                        <th class="px-4 py-3">Material Name</th>
                                        <th class="px-4 py-3">Status</th>
                                        <th class="px-4 py-3">Severity</th>
                                        <th class="px-4 py-3">Durability Stock</th>
                                        <th class="px-4 py-3">Streak Days</th>
                                        <th class="px-4 py-3">Estimated GR</th>
                                        <th class="px-4 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    <tr v-for="item in materials" :key="item.id"
                                        class="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-4 py-3 font-medium text-slate-700">{{ item.name }}<div
                                                class="text-xs text-slate-400 font-normal">ID: {{ item.id + 2040 }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3"><span
                                                :class="item.status === 'Shortage' ? 'text-red-600 font-medium' : 'text-orange-600 font-medium'">{{
                                                    item.status }}</span></td>
                                        <td class="px-4 py-3"><span
                                                class="px-2.5 py-0.5 rounded text-xs border font-medium inline-block shadow-sm"
                                                :class="item.severityColor">{{ item.severity }}</span></td>
                                        <td class="px-4 py-3"><span class="font-bold text-red-600">{{ item.stock
                                        }}</span><span class="text-xs text-red-400 ml-1">shifts</span></td>
                                        <td class="px-4 py-3 text-red-600 font-medium">{{ item.streak }} Days</td>
                                        <td class="px-4 py-3 text-slate-600">{{ item.grDate }}</td>
                                        <td class="px-4 py-3 text-right"><button
                                                class="text-slate-400 hover:text-slate-600">
                                                <ArrowRight class="w-4 h-4" />
                                            </button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-6">

                            <div
                                class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm relative h-48 flex flex-col">
                                <div class="flex items-center gap-2 mb-2">
                                    <Clock class="w-4 h-4 text-orange-500" />
                                    <h3 class="font-medium text-slate-700">Overdue Days Trend</h3>
                                </div>
                                <div class="flex-1 w-full relative">
                                    <VueApexCharts type="area" height="100%" :options="overdueOptions"
                                        :series="overdueSeries" />
                                </div>
                                <div class="flex justify-between text-xs text-slate-400 mt-1 px-1">
                                    <span>Jan</span><span>Feb</span><span>Mar</span><span>Apr</span><span>May</span><span>Jun</span>
                                </div>
                            </div>

                            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm h-48 flex flex-col">
                                <div class="flex items-center gap-2 mb-2">
                                    <Activity class="w-4 h-4 text-green-500" />
                                    <h3 class="font-medium text-slate-700">Recovery Days Trend</h3>
                                </div>
                                <div class="flex-1 w-full relative">
                                    <VueApexCharts type="area" height="100%" :options="recoveryOptions"
                                        :series="recoverySeries" />
                                </div>
                                <div class="flex justify-between text-xs text-slate-400 mt-1 px-1">
                                    <span>Jan</span><span>Feb</span><span>Mar</span><span>Apr</span><span>May</span><span>Jun</span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-blue-50/50 rounded-xl border border-blue-100 p-1 shadow-sm flex flex-col">
                            <div class="bg-blue-100/50 p-3 rounded-t-lg flex items-center gap-2 text-blue-800">
                                <div class="p-1 bg-blue-600 rounded-full flex items-center justify-center">
                                    <div class="w-2 h-2 bg-white rounded-full animate-pulse"></div>
                                </div>
                                <h3 class="font-semibold">Supervisor Action Plan</h3>
                            </div>
                            <div class="p-4 space-y-4 flex-1">
                                <div>
                                    <div class="flex items-center gap-2 mb-2">
                                        <div class="w-2 h-2 rounded-full bg-red-500"></div>
                                        <h4 class="text-xs font-bold text-red-600 uppercase tracking-wide">Immediate
                                            Action
                                        </h4>
                                    </div>
                                    <div class="bg-white border border-red-100 rounded-lg p-3 shadow-sm">
                                        <div class="font-medium text-slate-800 mb-1">Steel Bearing A-2045</div>
                                        <ul class="text-sm text-slate-600 list-disc list-inside space-y-1">
                                            <li>Escalate to Procurement Lead</li>
                                            <li>Request emergency PO expedite</li>
                                            <li>Check alternative suppliers</li>
                                        </ul>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 mb-2">
                                        <div class="w-2 h-2 rounded-full bg-orange-500"></div>
                                        <h4 class="text-xs font-bold text-orange-600 uppercase tracking-wide">Urgent
                                            (Next 2
                                            Shifts)</h4>
                                    </div>
                                    <div class="bg-white border border-orange-100 rounded-lg p-3 shadow-sm">
                                        <div class="font-medium text-slate-800 mb-1">2 Materials need attention:</div>
                                        <ul class="text-sm text-slate-600 list-disc list-inside space-y-1">
                                            <li>Follow up on pending GR</li>
                                            <li>Monitor consumption rates</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="xl:col-span-4 space-y-6">

                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                        <div class="flex items-center gap-2 mb-2 border-b border-slate-50 pb-2">
                            <div class="text-blue-500">
                                <Clock class="w-4 h-4" />
                            </div>
                            <h3 class="font-medium text-slate-700">Material Status Distribution</h3>
                        </div>
                        <div class="flex flex-col items-center">
                            <div class="w-full h-40">
                                <VueApexCharts type="donut" height="100%" :options="donutOptions"
                                    :series="donutSeries" />
                            </div>
                            <div class="w-full text-sm space-y-2 mt-2">
                                <div class="flex items-center gap-2"><span
                                        class="w-3 h-3 rounded-full bg-emerald-400"></span><span
                                        class="text-slate-600">Ok</span><span class="ml-auto font-medium">144</span>
                                </div>
                                <div class="flex items-center gap-2"><span
                                        class="w-3 h-3 rounded-full bg-orange-500"></span><span
                                        class="text-slate-600">Caution</span><span class="ml-auto font-medium">3</span>
                                </div>
                                <div class="flex items-center gap-2"><span
                                        class="w-3 h-3 rounded-full bg-red-500"></span><span
                                        class="text-slate-600">Shortage</span><span class="ml-auto font-medium">3</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="text-purple-500">
                                <BarChart3 class="w-4 h-4" />
                            </div>
                            <h3 class="font-medium text-slate-700">Status Changes Avg</h3>
                        </div>
                        <div class="h-40 w-full">
                            <VueApexCharts type="bar" height="100%" :options="barOptions" :series="barSeries" />
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="text-orange-500">
                                <Clock class="w-4 h-4" />
                            </div>
                            <div>
                                <h3 class="font-medium text-slate-700">Top 5 Fastest to Critical</h3>
                                <p class="text-xs text-slate-400">Materials with shortest runway (days)</p>
                            </div>
                        </div>
                        <div class="h-64 w-full">
                            <VueApexCharts type="bar" height="100%" :options="top5Options" :series="top5Series" />
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </MainAppLayout>

</template>
<script setup>
import MainAppLayout from '@/Layouts/MainAppLayout.vue';
import { ref } from 'vue';
import VueApexCharts from 'vue3-apexcharts';
import {
    AlertTriangle,
    Clock,
    AlertCircle,
    BarChart3,
    Activity,
    ArrowRight
} from 'lucide-vue-next';

const lastUpdated = "20 July 2025";

// --- TABLE DATA ---
const materials = ref([
    { id: 1, name: "Steel Bearing A-2045", status: "Shortage", severity: "Line-Stop Risk", severityColor: "bg-red-100 text-red-700 border-red-200", stock: 0.5, streak: 4, grDate: "20 July 2025" },
    { id: 2, name: "Hydraulic Seal HX-920", status: "Shortage", severity: "High", severityColor: "bg-orange-100 text-orange-700 border-orange-200", stock: 1.2, streak: 3, grDate: "20 July 2025" },
    { id: 3, name: "Electric Motor EM-5500", status: "Shortage", severity: "High", severityColor: "bg-orange-100 text-orange-700 border-orange-200", stock: 1.5, streak: 1, grDate: "20 July 2025" },
    { id: 4, name: "Filter Cartridge FC-220", status: "Caution", severity: "Medium", severityColor: "bg-yellow-100 text-yellow-700 border-yellow-200", stock: 2.8, streak: 2, grDate: "20 July 2025" }
]);

// --- CHART CONFIGURATIONS ---

// 1. Sparkline: Overdue Days Trend (Red)
const overdueSeries = [{ name: 'Overdue', data: [130, 150, 135, 120, 95, 85] }];
const overdueOptions = {
    chart: { type: 'area', sparkline: { enabled: true }, toolbar: { show: false } },
    stroke: { curve: 'smooth', width: 2, colors: ['#ef4444'] },
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.2, opacityTo: 0, stops: [0, 90, 100] }, colors: ['#ef4444'] },
    tooltip: { fixed: { enabled: false }, x: { show: false }, marker: { show: false } },
    colors: ['#ef4444']
};

// 2. Sparkline: Recovery Days Trend (Green)
const recoverySeries = [{ name: 'Recovery', data: [60, 65, 55, 45, 30, 25] }];
const recoveryOptions = {
    chart: { type: 'area', sparkline: { enabled: true }, toolbar: { show: false } },
    stroke: { curve: 'smooth', width: 2, colors: ['#22c55e'] },
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.2, opacityTo: 0, stops: [0, 90, 100] }, colors: ['#22c55e'] },
    colors: ['#22c55e']
};

// 3. Donut: Material Status Distribution
const donutSeries = [144, 3, 3]; // Ok, Caution, Shortage
const donutOptions = {
    chart: { type: 'donut' },
    labels: ['Ok', 'Caution', 'Shortage'],
    colors: ['#34d399', '#f97316', '#ef4444'], // Emerald, Orange, Red
    plotOptions: { pie: { donut: { size: '75%', labels: { show: false } } } },
    dataLabels: { enabled: false },
    legend: { show: false }, // We build a custom legend to match the design exact layout
    stroke: { show: false }
};

// 4. Bar: Status Changes Avg (Purple)
const barSeries = [{ name: 'Changes', data: [3, 5, 4, 7, 6, 4, 8, 6] }];
const barOptions = {
    chart: { type: 'bar', toolbar: { show: false } },
    plotOptions: { bar: { borderRadius: 4, columnWidth: '60%' } },
    dataLabels: { enabled: false },
    xaxis: { categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug'], axisBorder: { show: false }, axisTicks: { show: false }, labels: { style: { colors: '#94a3b8', fontSize: '10px' } } },
    yaxis: { show: false },
    grid: { show: true, strokeDashArray: 4, padding: { left: 0, right: 0 } },
    colors: ['#a855f7'],
    fill: { opacity: 1 }
};

// 5. Horizontal Bar: Top 5 Fastest to Critical
const top5Series = [{ data: [3, 4.5, 5.8, 6.9, 8.0] }];
const top5Options = {
    chart: { type: 'bar', toolbar: { show: false } },
    plotOptions: { bar: { horizontal: true, barHeight: '50%', borderRadius: 4, distributed: true } }, // Distributed for different colors
    colors: ['#dc2626', '#ef4444', '#f97316', '#fb923c', '#fdba74'], // Red -> Orange gradient
    dataLabels: { enabled: false }, // Hide numbers on bars to match design
    xaxis: { categories: ['Steel Bearing', 'Hydraulic Seal', 'Electric Motor', 'Filter Cartridge', 'Gasket Set'], labels: { style: { fontSize: '10px', colors: '#94a3b8' } } },
    yaxis: { labels: { show: true, style: { fontSize: '11px', fontWeight: 500, fontFamily: 'inherit' }, maxWidth: 100 } },
    grid: { xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } }, padding: { left: -10 } },
    legend: { show: false },
    tooltip: { y: { formatter: (val) => val + " days" } }
};

</script>
    