<template>
    <MainAppLayout>
        <div class="min-h-screen bg-gray-100 p-8 font-sans">

            <!-- Error notification -->
            <div v-if="error"
                class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex justify-between items-center">
                <span>{{ error }}</span>
                <button @click="error = null" class="text-red-600 hover:text-red-800 font-bold">×</button>
            </div>

            <div class="bg-white rounded-t-xl shadow-sm border-b border-gray-200 mb-6">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Stock Discrepancy Check</h1>
                        <p class="text-gray-500 text-sm mt-1">Warehouse A • Cycle Count #2026-01</p>
                    </div>
                    <div class="flex gap-3">
                        <button @click="downloadTemplate"
                            class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Download Template
                        </button>
                        <label
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 cursor-pointer transition-colors flex items-center gap-2"
                            :class="{ 'opacity-50 cursor-not-allowed': uploading }">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            <input type="file" @change="handleFileUpload" accept=".xlsx,.xls" class="hidden"
                                :disabled="uploading" />
                            {{ uploading ? 'Uploading...' : 'Upload Excel' }}
                        </label>
                        <button @click="syncWithDailyInputs" :disabled="loading"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            Sync
                        </button>
                        <button @click="fetchData" :disabled="loading"
                            class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            {{ loading ? 'Loading...' : 'Refresh' }}
                        </button>
                    </div>
                </div>
                <div
                    class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-gray-200 bg-gray-50/50 rounded-b-xl">

                    <div class="p-4">
                        <div class="mb-3 flex items-center gap-2">
                            <div class="p-1.5 bg-indigo-100 rounded-md text-indigo-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide">Operational Impact
                                (Items)
                            </h3>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-blue p-3 rounded-lg shadow-sm border border-blue-100">
                                <span class="block text-lg text-blue-600 font-bold mb-1">Discrepancy Items (+)</span>
                                <div class="flex items-baseline gap-1">
                                    <span class="text-lg font-bold text-blue-800">{{ statistics.surplusCount }}</span>
                                    <span class="text-xs text-blue-400">items</span>
                                </div>
                            </div>
                            <div class="bg-red p-3 rounded-lg shadow-sm border border-red-100">
                                <span class="block text-lg text-red-600 font-bold mb-1">Discrepancy Items (-)</span>
                                <div class="flex items-baseline gap-1">
                                    <span class="text-lg font-bold text-red-800">{{ statistics.discrepancyCount
                                    }}</span>
                                    <span class="text-xs text-red-400">items</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-4">
                        <div class="mb-3 flex items-center gap-2">
                            <div class="p-1.5 bg-blue-100 rounded-md text-blue-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide">Financial Impact (Value)
                            </h3>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-white p-3 rounded-lg shadow-sm border border-blue-100">
                                <span class="block text-lg text-blue-600 font-bold mb-1">Discrepancy Amount (+)</span>
                                <span class="text-lg font-bold text-gray-800">{{
                                    formatCurrency(statistics.surplusAmount)
                                }}</span>
                            </div>
                            <div class="bg-white p-3 rounded-lg shadow-sm border border-red-100">
                                <span class="block text-lg text-red-600 font-bold mb-1">Discrepancy Amount (-)</span>
                                <span class="text-lg font-bold text-gray-800">{{
                                    formatCurrency(statistics.discrepancyAmount) }}</span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Search Bar -->
            <SearchBar :searchTerm="searchQuery" @update:searchTerm="handleSearchUpdate" @clear="clearSearch" />

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-4 p-4">
                <div class="flex items-center gap-4 flex-wrap">
                    <!-- Location Filter -->
                    <div class="flex items-center gap-2">
                        <label class="text-sm font-semibold text-gray-700">Location:</label>
                        <select v-model="selectedLocation" @change="handleFilterChange"
                            class="border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-500 px-3 py-2">
                            <option value="">All Locations</option>
                            <option v-for="location in locations" :key="location" :value="location">
                                {{ location }}
                            </option>
                        </select>
                    </div>

                    <!-- Usage Filter -->
                    <div class="flex items-center gap-2">
                        <label class="text-sm font-semibold text-gray-700">Usage:</label>
                        <select v-model="selectedUsage" @change="handleFilterChange"
                            class="border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-500 px-3 py-2">
                            <option value="">All Usages</option>
                            <option v-for="usage in usages" :key="usage" :value="usage">
                                {{ usage }}
                            </option>
                        </select>
                    </div>

                    <!-- PIC Filter -->
                    <div class="flex items-center gap-2">
                        <label class="text-sm font-semibold text-gray-700">PIC:</label>
                        <select v-model="selectedPic" @change="handleFilterChange"
                            class="border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-500 px-3 py-2">
                            <option value="">All PICs</option>
                            <option v-for="pic in pics" :key="pic" :value="pic">
                                {{ pic }}
                            </option>
                        </select>
                    </div>


                    <!-- Results Count -->
                    <div class="ml-auto text-sm text-gray-600">
                        Showing {{ items.length }} of {{ pagination.total }} items
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-lg rounded-lg border border-gray-200 overflow-x-auto pb-4">
                <table class="w-full text-sm text-left border-collapse">
                    <thead>
                        <tr
                            class="bg-gray-50/80 text-xs text-gray-600 uppercase font-semibold border-b border-gray-200 leading-tight">
                            <th class="px-4 py-3 min-w-[120px] border-r border-gray-100 align-bottom">Material No.</th>
                            <th class="px-4 py-3 min-w-[200px] border-r border-gray-100 align-bottom">Material Name</th>
                            <th class="px-4 py-3 border-r border-gray-100 align-bottom">S.Loc</th>
                            <th class="px-4 py-3 text-center border-r border-gray-200 align-bottom">Price</th>

                            <th class="px-4 py-3 text-center bg-blue-50/30 border-r border-blue-100 align-bottom">SoH
                                <br><span class="text-[10px] opacity-70">(System)</span>
                            </th>
                            <th class="px-4 py-3 text-center bg-blue-50/30 border-r border-blue-100 align-bottom">Qty
                                <br><span class="text-[10px] opacity-70">(Actual)</span>
                            </th>
                            <th
                                class="px-4 py-3 text-center font-bold text-gray-700 bg-blue-50/30 border-r border-blue-100 align-bottom">
                                Initial<br>Gap</th>

                            <th class="px-4 py-3 text-center text-gray-500 border-r border-gray-200 align-bottom">
                                Time<br>Diff</th>

                            <th
                                class="px-4 py-3 w-28 bg-yellow-50/30 text-yellow-700 border-r border-yellow-100 align-bottom">
                                O/S GR <br><span class="text-[10px]">(+)</span></th>
                            <th
                                class="px-4 py-3 w-28 bg-yellow-50/30 text-yellow-700 border-r border-yellow-100 align-bottom">
                                O/S GI <br><span class="text-[10px]">(-)</span></th>
                            <th
                                class="px-4 py-3 w-28 bg-yellow-50/30 text-yellow-700 border-r border-yellow-100 align-bottom">
                                Error <br><span class="text-[10px]">(Mvmt)</span></th>

                            <th class="px-4 py-3 text-center sticky right-0 bg-gray-50 shadow-sm border-l border-gray-200 align-bottom cursor-pointer hover:bg-gray-100 transition-colors select-none"
                                @click="handleSort('final_qty')">
                                <div class="flex items-center justify-center gap-1">
                                    <span>Final Qty</span>
                                    <div class="flex flex-col">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3"
                                            :class="sortBy === 'final_qty' && sortOrder === 'asc' ? 'text-blue-600' : 'text-gray-400'"
                                            fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M7 14l5-5 5 5z" />
                                        </svg>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 -mt-1"
                                            :class="sortBy === 'final_qty' && sortOrder === 'desc' ? 'text-blue-600' : 'text-gray-400'"
                                            fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M7 10l5 5 5-5z" />
                                        </svg>
                                    </div>
                                </div>
                            </th>
                            <th class="px-4 py-3 text-center sticky right-0 bg-gray-50 shadow-sm border-l border-gray-200 align-bottom cursor-pointer hover:bg-gray-100 transition-colors select-none"
                                @click="handleSort('final_amount')">
                                <div class="flex items-center justify-center gap-1">
                                    <span>Final Amount</span>
                                    <div class="flex flex-col">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3"
                                            :class="sortBy === 'final_amount' && sortOrder === 'asc' ? 'text-blue-600' : 'text-gray-400'"
                                            fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M7 14l5-5 5 5z" />
                                        </svg>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 -mt-1"
                                            :class="sortBy === 'final_amount' && sortOrder === 'desc' ? 'text-blue-600' : 'text-gray-400'"
                                            fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M7 10l5 5 5-5z" />
                                        </svg>
                                    </div>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="loading" class="hover:bg-gray-50">
                            <td colspan="13" class="px-4 py-8 text-center text-gray-500">
                                <div class="flex justify-center items-center">
                                    <svg class="animate-spin h-5 w-5 mr-3 text-blue-500" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    Loading data...
                                </div>
                            </td>
                        </tr>
                        <tr v-else-if="items.length === 0" class="hover:bg-gray-50">
                            <td colspan="13" class="px-4 py-8 text-center text-gray-500">
                                No discrepancy data found. Upload an Excel file or sync to get started.
                            </td>
                        </tr>
                        <tr v-else v-for="item in items" :key="item.id"
                            class="group hover:bg-gray-50 transition-colors relative">

                            <td class="px-4 py-4 font-medium text-gray-900 border-r border-gray-100">{{ item.materialNo
                                }}</td>
                            <td class="px-4  border-r border-gray-100">
                                <div class="text-gray-900 text-base">{{ item.name }}</div>
                                <div class="text-xs text-gray-500 mt-0.5 inline-block bg-gray-100 px-1.5 rounded-sm">{{
                                    item.uom }}</div>
                            </td>
                            <td class="px-2 py-4 text-sm text-gray-600 text-center border-r border-gray-100">{{
                                item.location }}</td>
                            <td class="px-1 py-1 text-right text-gray-700 font-medium border-r border-gray-200">{{
                                formatCurrency(item.price) }}</td>

                            <td
                                class=" text-center border-r border-blue-100 group-hover:bg-blue-50/30 transition-colors relative">
                                <div class="text-sm font-semibold text-gray-700">{{ formatNumber(item.soh) }}</div>
                                <div class="text-[11px] text-gray-400 font-medium mt-1 whitespace-nowrap">
                                    {{ formatCompactTimestamp(item.sohTimestamp) }}
                                </div>
                            </td>

                            <td
                                class="px-1 text-center border-r border-blue-100 group-hover:bg-blue-50/30 transition-colors relative">
                                <div class="text-sm font-bold text-gray-800">{{ formatNumber(item.qtyActual) }}</div>
                                <div class="text-[11px] text-gray-400 font-medium mt-1 whitespace-nowrap">
                                    {{ formatCompactTimestamp(item.qtyActualTimestamp) }}
                                </div>
                            </td>

                            <td class="px-4 py-4 text-center font-mono text-sm font-bold border-r border-blue-100 group-hover:bg-blue-50/30 transition-colors"
                                :class="getGapColor(item.qtyActual - item.soh)">
                                {{ (item.qtyActual - item.soh) > 0 ? '+' : '' }}{{ formatNumber(item.qtyActual -
                                    item.soh) }}
                            </td>

                            <td class="px-4 py-4 text-center border-r border-gray-200">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    {{ formatTimeDiffCeil(item.timeDiff) }} Hr
                                </span>
                            </td>

                            <td
                                class="px-3 py-3 bg-yellow-50/10 border-r border-yellow-100 group-hover:bg-yellow-50/30 transition-colors">
                                <input type="number" min="0" v-model.number="item.outGR"
                                    class="w-full border-gray-300 rounded-md text-right font-medium text-sm focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 shadow-sm px-2 py-1.5 transition-all"
                                    placeholder="0" />
                            </td>

                            <td
                                class="px-3 py-3 bg-yellow-50/10 border-r border-yellow-100 group-hover:bg-yellow-50/30 transition-colors">
                                <input type="number" :value="item.outGI"
                                    @input="item.outGI = -Math.abs($event.target.value)"
                                    class="w-full border-gray-300 rounded-md text-right font-medium text-sm focus:ring-2 focus:ring-red-400 focus:border-red-400 shadow-sm px-2 py-1.5 transition-all text-red-600"
                                    placeholder="0" />
                            </td>
                            <td
                                class="px-3 py-3 bg-yellow-50/10 border-r border-yellow-100 group-hover:bg-yellow-50/30 transition-colors">
                                <input type="number" v-model.number="item.errorMvmt"
                                    class="w-full border-gray-300 rounded-md text-right font-medium text-sm focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 shadow-sm px-2 py-1.5 transition-all"
                                    placeholder="0" />
                            </td>

                            <td
                                class="px-4 py-4 text-center sticky right-0 bg-white border-l border-gray-200 group-hover:bg-gray-50">
                                <div class="inline-block px-3 py-1 rounded-md bg-gray-50 border"
                                    :class="[getGapColor(getFinalStatus(item).val), getFinalStatus(item).val === 0 ? 'border-gray-200' : 'border-current opacity-80']">
                                    <div class="text-sm font-bold">
                                        {{ formatNumber(getFinalStatus(item).val) }}
                                    </div>
                                </div>
                                <div v-if="getFinalStatus(item).val === 0"
                                    class="text-[10px] font-bold text-blue-600 uppercase tracking-wider mt-1">Matched
                                </div>
                                <div v-else class="text-[10px] font-bold text-red-500 uppercase tracking-wider mt-1">
                                    Variance</div>
                            </td>
                            <td class="px-4 py-4 text-right sticky right-0 bg-white border-l border-gray-200 group-hover:bg-gray-50 font-medium"
                                :class="getGapColor(getFinalStatus(item).val * item.price)">
                                {{ formatCurrency(getFinalStatus(item).val * item.price) }}
                            </td>

                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="pagination.last_page > 1" class="bg-white rounded-lg shadow-sm border border-gray-200 mt-4 p-4">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-600">
                        Page {{ pagination.current_page }} of {{ pagination.last_page }}
                    </div>
                    <div class="flex gap-2">
                        <button @click="goToPage(1)" :disabled="pagination.current_page === 1"
                            class="px-3 py-1 border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                            First
                        </button>
                        <button @click="goToPage(pagination.current_page - 1)" :disabled="pagination.current_page === 1"
                            class="px-3 py-1 border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                            Previous
                        </button>

                        <!-- Page numbers -->
                        <template v-for="page in getPageNumbers()" :key="page">
                            <button v-if="page !== '...'" @click="goToPage(page)" :class="[
                                'px-3 py-1 border rounded-md',
                                pagination.current_page === page
                                    ? 'bg-blue-600 text-white border-blue-600'
                                    : 'border-gray-300 hover:bg-gray-50'
                            ]">
                                {{ page }}
                            </button>
                            <span v-else class="px-2 py-1">...</span>
                        </template>

                        <button @click="goToPage(pagination.current_page + 1)"
                            :disabled="pagination.current_page === pagination.last_page"
                            class="px-3 py-1 border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                            Next
                        </button>
                        <button @click="goToPage(pagination.last_page)"
                            :disabled="pagination.current_page === pagination.last_page"
                            class="px-3 py-1 border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                            Last
                        </button>
                    </div>
                    <div class="text-sm text-gray-600">
                        Total: {{ pagination.total }} items
                    </div>
                </div>
            </div>

        </div>
    </MainAppLayout>
</template>

<script setup>
import MainAppLayout from '@/Layouts/MainAppLayout.vue';
import SearchBar from '@/Components/SearchBar.vue';
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

// Props
const props = defineProps({
    locations: {
        type: Array,
        default: () => [],
    },
    pics: {
        type: Array,
        default: () => [],
    },
    usages: {
        type: Array,
        default: () => [],
    },
});

// State
const items = ref([]);
const statistics = ref({
    surplusCount: 0,
    discrepancyCount: 0,
    surplusAmount: 0,
    discrepancyAmount: 0,
});
const pagination = ref({
    current_page: 1,
    per_page: 50,
    total: 0,
    last_page: 1,
});
const selectedLocation = ref('');
const selectedPic = ref('');
const selectedUsage = ref('');
const searchQuery = ref('');
const sortBy = ref(null);
const sortOrder = ref('asc');
const loading = ref(false);
const uploading = ref(false);
const error = ref(null);
let searchTimeout = null;

// Fetch discrepancy data from API
const fetchData = async (page = 1) => {
    loading.value = true;
    error.value = null;

    try {
        const params = {
            per_page: pagination.value.per_page,
            page: page,
        };

        if (selectedLocation.value) {
            params.location = selectedLocation.value;
        }

        if (selectedPic.value) {
            params.pic = selectedPic.value;
        }

        if (selectedUsage.value) {
            params.usage = selectedUsage.value;
        }

        if (searchQuery.value) {
            params.search = searchQuery.value;
        }

        if (sortBy.value) {
            params.sort_by = sortBy.value;
            params.sort_order = sortOrder.value;
        }

        const response = await axios.get('/api/discrepancy', { params });

        if (response.data.success) {
            items.value = response.data.data.items;
            statistics.value = response.data.data.statistics;
            pagination.value = response.data.data.pagination;
        }
    } catch (err) {
        error.value = 'Failed to fetch discrepancy data: ' + (err.response?.data?.message || err.message);
        console.error('Fetch error:', err);
    } finally {
        loading.value = false;
    }
};

// Handle filter change (location, usage, or PIC)
const handleFilterChange = () => {
    fetchData(1); // Reset to page 1 when filter changes
};

// Handle search update from SearchBar component
const handleSearchUpdate = (value) => {
    searchQuery.value = value;

    // Debounce search
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    searchTimeout = setTimeout(() => {
        fetchData(1); // Reset to page 1 when search changes
    }, 500); // 500ms debounce
};

// Clear search
const clearSearch = () => {
    searchQuery.value = '';
    fetchData(1);
};

// Handle column sort
const handleSort = (column) => {
    if (sortBy.value === column) {
        // Toggle sort order if clicking the same column
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        // Set new column and default to ascending
        sortBy.value = column;
        sortOrder.value = 'asc';
    }
    fetchData(1); // Reset to page 1 when sorting changes
};

// Handle page change
const goToPage = (page) => {
    if (page >= 1 && page <= pagination.value.last_page) {
        fetchData(page);
    }
};

// Download template
const downloadTemplate = () => {
    window.location.href = '/api/discrepancy/template';
};

// Get page numbers for pagination
const getPageNumbers = () => {
    const pages = [];
    const current = pagination.value.current_page;
    const last = pagination.value.last_page;

    if (last <= 7) {
        // Show all pages if 7 or fewer
        for (let i = 1; i <= last; i++) {
            pages.push(i);
        }
    } else {
        // Always show first page
        pages.push(1);

        if (current > 3) {
            pages.push('...');
        }

        // Show pages around current
        const start = Math.max(2, current - 1);
        const end = Math.min(last - 1, current + 1);

        for (let i = start; i <= end; i++) {
            pages.push(i);
        }

        if (current < last - 2) {
            pages.push('...');
        }

        // Always show last page
        pages.push(last);
    }

    return pages;
};

// Handle Excel file upload
const handleFileUpload = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    uploading.value = true;
    error.value = null;

    const formData = new FormData();
    formData.append('file', file);

    try {
        const response = await axios.post('/api/discrepancy/import', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        if (response.data.success) {
            const result = response.data.data;
            let message = `Excel imported successfully!\n\nImported: ${result.imported}\nUpdated: ${result.updated}\nSkipped: ${result.skipped}`;

            if (result.errors.length > 0) {
                message += `\n\nErrors:\n${result.errors.join('\n')}`;
            }

            alert(message);
            await fetchData(); // Refresh data
        }
    } catch (err) {
        error.value = 'Failed to upload Excel: ' + (err.response?.data?.message || err.message);
        alert('Failed to upload Excel: ' + (err.response?.data?.message || err.message));
        console.error('Upload error:', err);
    } finally {
        uploading.value = false;
        event.target.value = ''; // Reset file input
    }
};

// Sync with daily inputs
const syncWithDailyInputs = async () => {
    loading.value = true;
    error.value = null;

    try {
        const response = await axios.post('/api/discrepancy/sync');

        if (response.data.success) {
            alert(`Sync completed!\n\nNew records created: ${response.data.data.created}`);
            await fetchData(); // Refresh data
        }
    } catch (err) {
        error.value = 'Failed to sync: ' + (err.response?.data?.message || err.message);
        alert('Failed to sync: ' + (err.response?.data?.message || err.message));
        console.error('Sync error:', err);
    } finally {
        loading.value = false;
    }
};

// Fetch data on component mount
onMounted(() => {
    fetchData();
});

// 2. LOGIC FUNCTIONS

// Format number to currency (IDR)
const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
};
const formatNumber = (value) => {
    if (value === null || value === undefined) return '0';
    // uses 'id-ID' for dots (1.200), change to 'en-US' for commas (1,200)
    return new Intl.NumberFormat('en-US').format(value);
};

// Color for the initial gap column
const getGapColor = (val) => {
    if (val === 0) return 'text-gray-300';
    if (val > 0) return 'text-blue-600'; // Surplus
    return 'text-red-600'; // Loss
};


const formatCompactTimestamp = (timestampStr) => {
    if (!timestampStr) return 'N/A';
    const date = new Date(timestampStr);
    if (isNaN(date.getTime())) return 'Invalid Date';
    return new Intl.DateTimeFormat('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
    }).format(date).replace(',', '');
};


const formatTimeDiffCeil = (hours) => {
    if (hours === null || hours === undefined || hours < 0) return '0.0';
    const step = 0.5;
    const rounded = Math.ceil(hours / step) * step;
    return rounded.toFixed(1);
};

// Core Logic: Calculate if the explanation matches the gap
const getFinalStatus = (item) => {
    // Logic:
    // Initial Gap = Actual - SoH
    // Explanation = +Outstanding GR - Outstanding GI + Error
    // Final Variance = Initial Gap - Explanation

    // Example:
    // SoH: 100, Actual: 90. Gap: -10.
    // We found an Outstanding GI of 10.
    // Explanation = 0 - 10 + 0 = -10.
    // Final = -10 - (-10) = 0. (MATCH)

    const initialGap = item.qtyActual - item.soh;
    // Ensure inputs are treated as numbers (Vue v-model.number sometimes needs help with empty strings)
    const gr = Number(item.outGR) || 0;
    const gi = Number(item.outGI) || 0;
    const err = Number(item.errorMvmt) || 0;

    // Depending on your business logic, usually:
    // We want to see if (System + GR - GI) == Actual
    // Or: Gap + (GR - GI + Err) == 0 ?
    // Let's use: Remaining = (Actual) - (SoH + GR - GI + Err)

    const explainedSystem = gr + gi + err;
    const finalVariance = initialGap + explainedSystem;

    if (finalVariance === 0) {
        return {
            label: 'MATCH',
            class: 'bg-blue-100 text-blue-700 border-blue-200',
            val: 0
        };
    } else {
        return {
            label: 'UNMATCHED',
            class: 'bg-red-100 text-red-700 border-red-200',
            val: finalVariance
        };
    }
};

</script>
