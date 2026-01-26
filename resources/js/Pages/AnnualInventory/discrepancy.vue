<template>
    <MainAppLayout title="Annual Inventory Discrepancy" subtitle="Manage discrepancy for annual inventory">
        <div class="min-h-screen bg-gray-100 p-4 md:p-8 font-sans">

            <!-- Error notification -->
            <div v-if="error"
                class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex justify-between items-center">
                <span>{{ error }}</span>
                <button @click="error = null" class="text-red-600 hover:text-red-800 font-bold">×</button>
            </div>

            <!-- Success notification -->
            <div v-if="successMessage"
                class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex justify-between items-center">
                <span>{{ successMessage }}</span>
                <button @click="successMessage = null" class="text-green-600 hover:text-green-800 font-bold">×</button>
            </div>

            <!-- Header Card -->
            <div class="bg-white rounded-t-xl shadow-sm border-b border-gray-200 mb-6">
                <div class="p-6 border-b border-gray-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div class="flex items-center gap-4">
                        <button @click="goBack"
                            class="p-2 hover:bg-gray-100 rounded-lg border border-gray-200 transition-colors">
                            <ArrowLeft class="w-5 h-5 text-gray-600" />
                        </button>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800">Annual Inventory Discrepancy</h1>
                            <p class="text-gray-500 text-sm mt-1">PID: {{ pidData?.pid || 'All PIDs' }} • {{ pidData?.location || 'All Locations' }}</p>
                        </div>
                    </div>
                    <div class="flex gap-3 flex-wrap">
                        <button @click="downloadTemplate"
                            class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors flex items-center gap-2">
                            <Download class="w-4 h-4" />
                            Download Template
                        </button>
                        <label
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 cursor-pointer transition-colors flex items-center gap-2"
                            :class="{ 'opacity-50 cursor-not-allowed': uploading }">
                            <Upload class="w-4 h-4" />
                            <input type="file" @change="handleFileUpload" accept=".xlsx,.xls" class="hidden"
                                :disabled="uploading" />
                            {{ uploading ? 'Uploading...' : 'Upload Excel' }}
                        </label>
                        <button @click="saveAllChanges" :disabled="!hasChanges || saving"
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                            <Loader2 v-if="saving" class="w-4 h-4 animate-spin" />
                            <Save v-else class="w-4 h-4" />
                            {{ saving ? 'Saving...' : 'Save All Changes' }}
                        </button>
                        <button @click="fetchData" :disabled="loading"
                            class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
                            {{ loading ? 'Loading...' : 'Refresh' }}
                        </button>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-gray-200 bg-gray-50/50 rounded-b-xl">
                    <div class="p-4">
                        <div class="mb-3 flex items-center gap-2">
                            <div class="p-1.5 bg-indigo-100 rounded-md text-indigo-600">
                                <Package class="w-4 h-4" />
                            </div>
                            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide">Operational Impact (Items)</h3>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-blue-50 p-3 rounded-lg shadow-sm border border-blue-100">
                                <span class="block text-sm text-blue-600 font-bold mb-1">Surplus Items (+)</span>
                                <div class="flex items-baseline gap-1">
                                    <span class="text-lg font-bold text-blue-800">{{ statistics.surplusCount }}</span>
                                    <span class="text-xs text-blue-400">items</span>
                                </div>
                            </div>
                            <div class="bg-red-50 p-3 rounded-lg shadow-sm border border-red-100">
                                <span class="block text-sm text-red-600 font-bold mb-1">Shortage Items (-)</span>
                                <div class="flex items-baseline gap-1">
                                    <span class="text-lg font-bold text-red-800">{{ statistics.shortageCount }}</span>
                                    <span class="text-xs text-red-400">items</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-4">
                        <div class="mb-3 flex items-center gap-2">
                            <div class="p-1.5 bg-blue-100 rounded-md text-blue-600">
                                <DollarSign class="w-4 h-4" />
                            </div>
                            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide">Financial Impact (Value)</h3>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-white p-3 rounded-lg shadow-sm border border-blue-100">
                                <span class="block text-sm text-blue-600 font-bold mb-1">Surplus Amount (+)</span>
                                <span class="text-lg font-bold text-gray-800">{{ formatCurrency(statistics.surplusAmount) }}</span>
                            </div>
                            <div class="bg-white p-3 rounded-lg shadow-sm border border-red-100">
                                <span class="block text-sm text-red-600 font-bold mb-1">Shortage Amount (-)</span>
                                <span class="text-lg font-bold text-gray-800">{{ formatCurrency(statistics.shortageAmount) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-4 p-4">
                <div class="flex flex-col lg:flex-row items-start lg:items-center gap-4">
                    <!-- Search -->
                    <div class="relative flex-1 w-full lg:max-w-md">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                        <input v-model="searchQuery" type="text" placeholder="Search material number or description..."
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm" />
                    </div>

                    <!-- Filters -->
                    <div class="flex flex-wrap items-center gap-4">
                        <!-- Location Filter -->
                        <div class="flex items-center gap-2">
                            <label class="text-sm font-semibold text-gray-700">Location:</label>
                            <select v-model="selectedLocation" @change="handleFilterChange"
                                class="border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-500 px-3 py-2 text-sm">
                                <option value="">All Locations</option>
                                <option v-for="loc in locations" :key="loc" :value="loc">{{ loc }}</option>
                            </select>
                        </div>

                        <!-- PID Filter -->
                        <div class="flex items-center gap-2">
                            <label class="text-sm font-semibold text-gray-700">PID:</label>
                            <select v-model="selectedPID" @change="handleFilterChange"
                                class="border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-500 px-3 py-2 text-sm">
                                <option value="">All PIDs</option>
                                <option v-for="pid in pids" :key="pid.id" :value="pid.id">{{ pid.pid }}</option>
                            </select>
                        </div>

                        <!-- Discrepancy Filter -->
                        <div class="flex items-center gap-2">
                            <label class="text-sm font-semibold text-gray-700">Discrepancy:</label>
                            <select v-model="selectedDiscrepancy" @change="handleFilterChange"
                                class="border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-500 px-3 py-2 text-sm">
                                <option value="">All</option>
                                <option value="surplus">Surplus (+)</option>
                                <option value="shortage">Shortage (-)</option>
                                <option value="match">Match (0)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Results Count -->
                    <div class="ml-auto text-sm text-gray-600">
                        Showing {{ items.length }} of {{ pagination.total }} items
                    </div>
                </div>
            </div>

            <!-- Data Table -->
            <div class="bg-white shadow-lg rounded-lg border border-gray-200 overflow-x-auto pb-4">
                <table class="w-full text-sm text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/80 text-xs text-gray-600 uppercase font-semibold border-b border-gray-200 leading-tight">
                            <th class="px-4 py-3 min-w-[120px] border-r border-gray-100">Material No.</th>
                            <th class="px-4 py-3 min-w-[200px] border-r border-gray-100">Material Name</th>
                            <th class="px-4 py-3 border-r border-gray-100">Rack</th>
                            <th class="px-4 py-3 text-center border-r border-gray-200">Price</th>

                            <th class="px-4 py-3 w-28 bg-green-50/30 text-green-700 border-r border-green-100">
                                SOH<br><span class="text-[10px]">(Editable)</span>
                            </th>
                            <th class="px-4 py-3 text-center bg-blue-50/30 border-r border-blue-100">
                                Actual<br><span class="text-[10px] opacity-70">(Count)</span>
                            </th>
                            <th class="px-4 py-3 text-center font-bold text-gray-700 bg-blue-50/30 border-r border-blue-100">
                                Initial<br>Gap
                            </th>

                            <th class="px-4 py-3 w-28 bg-yellow-50/30 text-yellow-700 border-r border-yellow-100">
                                O/S GR<br><span class="text-[10px]">(+)</span>
                            </th>
                            <th class="px-4 py-3 w-28 bg-yellow-50/30 text-yellow-700 border-r border-yellow-100">
                                O/S GI<br><span class="text-[10px]">(-)</span>
                            </th>
                            <th class="px-4 py-3 w-28 bg-yellow-50/30 text-yellow-700 border-r border-yellow-100">
                                Error<br><span class="text-[10px]">(Mvmt)</span>
                            </th>

                            <th class="px-4 py-3 text-center bg-gray-50 border-l border-gray-200">Final Qty</th>
                            <th class="px-4 py-3 text-center bg-gray-50 border-l border-gray-200">Final Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="loading" class="hover:bg-gray-50">
                            <td colspan="13" class="px-4 py-8 text-center text-gray-500">
                                <div class="flex justify-center items-center">
                                    <Loader2 class="w-5 h-5 mr-3 text-blue-500 animate-spin" />
                                    Loading data...
                                </div>
                            </td>
                        </tr>
                        <tr v-else-if="items.length === 0" class="hover:bg-gray-50">
                            <td colspan="13" class="px-4 py-8 text-center text-gray-500">
                                No items found. Make sure items have been counted first.
                            </td>
                        </tr>
                        <tr v-else v-for="item in items" :key="item.id"
                            class="group hover:bg-gray-50 transition-colors relative"
                            :class="{ 'bg-yellow-50': item._dirty }">

                            <td class="px-4 py-4 font-medium text-gray-900 border-r border-gray-100">
                                {{ item.material_number }}
                            </td>
                            <td class="px-4 py-3 border-r border-gray-100">
                                <div class="text-gray-900 text-sm">{{ item.description }}</div>
                                <div class="text-xs text-gray-500 mt-0.5 inline-block bg-gray-100 px-1.5 rounded-sm">
                                    {{ item.unit_of_measure }}
                                </div>
                            </td>
                            <td class="px-2 py-4 text-sm text-gray-600 text-center border-r border-gray-100">
                                {{ item.rack_address || '-' }}
                            </td>
                            <td class="px-1 py-1 text-right text-gray-700 font-medium border-r border-gray-200">
                                {{ formatCurrency(item.price || 0) }}
                            </td>

                            <!-- Editable SOH -->
                            <td class="px-3 py-3 bg-green-50/10 border-r border-green-100 group-hover:bg-green-50/30 transition-colors">
                                <input type="number" v-model.number="item.soh"
                                    @input="markDirty(item)"
                                    class="w-full border-gray-300 rounded-md text-right font-medium text-sm focus:ring-2 focus:ring-green-400 focus:border-green-400 shadow-sm px-2 py-1.5 transition-all"
                                    placeholder="0" />
                            </td>

                            <td class="px-1 text-center border-r border-blue-100 group-hover:bg-blue-50/30 transition-colors">
                                <div class="text-sm font-bold text-gray-800">{{ formatNumber(item.actual_qty) }}</div>
                                <div v-if="item.counted_at" class="text-[11px] text-gray-400 font-medium mt-1 whitespace-nowrap">
                                    {{ formatCompactTimestamp(item.counted_at) }}
                                </div>
                            </td>

                            <td class="px-4 py-4 text-center font-mono text-sm font-bold border-r border-blue-100 group-hover:bg-blue-50/30 transition-colors"
                                :class="getGapColor(getInitialGap(item))">
                                {{ getInitialGap(item) > 0 ? '+' : '' }}{{ formatNumber(getInitialGap(item)) }}
                            </td>

                            <td class="px-3 py-3 bg-yellow-50/10 border-r border-yellow-100 group-hover:bg-yellow-50/30 transition-colors">
                                <input type="number" min="0" v-model.number="item.outstanding_gr"
                                    @input="markDirty(item)"
                                    class="w-full border-gray-300 rounded-md text-right font-medium text-sm focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 shadow-sm px-2 py-1.5 transition-all"
                                    placeholder="0" />
                            </td>

                            <td class="px-3 py-3 bg-yellow-50/10 border-r border-yellow-100 group-hover:bg-yellow-50/30 transition-colors">
                                <input type="number" :value="item.outstanding_gi "
                                    @input="item.outstanding_gi = -Math.abs($event.target.value); markDirty(item)"
                                    class="w-full border-gray-300 rounded-md text-right font-medium text-sm focus:ring-2 focus:ring-red-400 focus:border-red-400 shadow-sm px-2 py-1.5 transition-all text-red-600"
                                    placeholder="0" />
                            </td>

                            <td class="px-3 py-3 bg-yellow-50/10 border-r border-yellow-100 group-hover:bg-yellow-50/30 transition-colors">
                                <input type="number" v-model.number="item.error_movement"
                                    @input="markDirty(item)"
                                    class="w-full border-gray-300 rounded-md text-right font-medium text-sm focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 shadow-sm px-2 py-1.5 transition-all"
                                    placeholder="0" />
                            </td>

                            <td class="px-4 py-4 text-center bg-white border-l border-gray-200 group-hover:bg-gray-50">
                                <div class="inline-block px-3 py-1 rounded-md bg-gray-50 border"
                                    :class="[getGapColor(getFinalDiscrepancy(item)), getFinalDiscrepancy(item) === 0 ? 'border-gray-200' : 'border-current opacity-80']">
                                    <div class="text-sm font-bold">
                                        {{ formatNumber(getFinalDiscrepancy(item)) }}
                                    </div>
                                </div>
                                <div v-if="getFinalDiscrepancy(item) === 0"
                                    class="text-[10px] font-bold text-green-600 uppercase tracking-wider mt-1">Matched</div>
                                <div v-else class="text-[10px] font-bold text-red-500 uppercase tracking-wider mt-1">Variance</div>
                            </td>

                            <td class="px-4 py-4 text-right bg-white border-l border-gray-200 group-hover:bg-gray-50 font-medium"
                                :class="getGapColor(getFinalDiscrepancy(item) * (item.price || 0))">
                                {{ formatCurrency(getFinalDiscrepancy(item) * (item.price || 0)) }}
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
                            class="px-3 py-1 border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed text-sm">
                            First
                        </button>
                        <button @click="goToPage(pagination.current_page - 1)" :disabled="pagination.current_page === 1"
                            class="px-3 py-1 border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed text-sm">
                            Previous
                        </button>

                        <template v-for="page in getPageNumbers()" :key="page">
                            <button v-if="page !== '...'" @click="goToPage(page)"
                                class="px-3 py-1 border rounded-md text-sm"
                                :class="pagination.current_page === page ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 hover:bg-gray-50'">
                                {{ page }}
                            </button>
                            <span v-else class="px-2 py-1 text-sm">...</span>
                        </template>

                        <button @click="goToPage(pagination.current_page + 1)"
                            :disabled="pagination.current_page === pagination.last_page"
                            class="px-3 py-1 border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed text-sm">
                            Next
                        </button>
                        <button @click="goToPage(pagination.last_page)"
                            :disabled="pagination.current_page === pagination.last_page"
                            class="px-3 py-1 border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed text-sm">
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
import { ref, computed, onMounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Save,
    RefreshCw,
    Search,
    Package,
    DollarSign,
    Loader2,
    Download,
    Upload
} from 'lucide-vue-next';
import axios from 'axios';

// Props
const props = defineProps({
    pid_id: {
        type: [Number, String],
        default: null
    }
});

// State
const items = ref([]);
const pids = ref([]);
const pidData = ref(null);
const statistics = ref({
    surplusCount: 0,
    shortageCount: 0,
    surplusAmount: 0,
    shortageAmount: 0,
});
const pagination = ref({
    current_page: 1,
    per_page: 50,
    total: 0,
    last_page: 1,
});

const selectedPID = ref(props.pid_id || '');
const selectedStatus = ref('');
const selectedDiscrepancy = ref('');
const selectedLocation = ref('');
const searchQuery = ref('');
const loading = ref(false);
const saving = ref(false);
const uploading = ref(false);
const error = ref(null);
const successMessage = ref(null);
const locations = ref([]);

let searchTimeout = null;

// Computed
const hasChanges = computed(() => items.value.some(item => item._dirty));

// Fetch PIDs list for dropdown
const fetchPIDs = async () => {
    try {
        const response = await axios.get('/api/annual-inventory/pids-dropdown');
        if (response.data.success) {
            pids.value = response.data.data;
        }
    } catch (err) {
        console.error('Failed to fetch PIDs:', err);
    }
};

// Fetch locations for filter
const fetchLocations = async () => {
    try {
        const response = await axios.get('/api/annual-inventory/locations');
        if (response.data.success) {
            locations.value = response.data.data;
        }
    } catch (err) {
        console.error('Failed to fetch locations:', err);
    }
};

// Download Excel template
const downloadTemplate = () => {
    window.location.href = '/api/annual-inventory/discrepancy/template';
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
        const response = await axios.post('/api/annual-inventory/discrepancy/import', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        if (response.data.success) {
            successMessage.value = `Excel imported successfully! Updated: ${response.data.updated} items`;
            setTimeout(() => successMessage.value = null, 5000);
            await fetchData(1);
        }
    } catch (err) {
        error.value = 'Failed to upload Excel: ' + (err.response?.data?.message || err.message);
    } finally {
        uploading.value = false;
        event.target.value = '';
    }
};

// Fetch discrepancy data
const fetchData = async (page = 1) => {
    loading.value = true;
    error.value = null;

    try {
        const params = {
            per_page: pagination.value.per_page,
            page: page,
            counted_only: true, // Only show items that have been counted
        };

        if (selectedPID.value) {
            params.pid_id = selectedPID.value;
        }

        if (selectedStatus.value) {
            params.status = selectedStatus.value;
        }

        if (selectedDiscrepancy.value) {
            params.discrepancy_type = selectedDiscrepancy.value;
        }

        if (selectedLocation.value) {
            params.location = selectedLocation.value;
        }

        if (searchQuery.value) {
            params.search = searchQuery.value;
        }

        const response = await axios.get('/api/annual-inventory/discrepancy', { params });

        if (response.data.success) {
            items.value = response.data.data.items.map(item => ({
                ...item,
                _dirty: false,
                _original: {
                    soh: item.soh,
                    outstanding_gr: item.outstanding_gr,
                    outstanding_gi: item.outstanding_gi,
                    error_movement: item.error_movement,
                }
            }));
            statistics.value = response.data.data.statistics;
            pagination.value = response.data.data.pagination;

            // Get PID data if filtered
            if (selectedPID.value && response.data.data.pid) {
                pidData.value = response.data.data.pid;
            } else {
                pidData.value = null;
            }
        }
    } catch (err) {
        error.value = 'Failed to fetch data: ' + (err.response?.data?.message || err.message);
        console.error('Fetch error:', err);
    } finally {
        loading.value = false;
    }
};

// Mark item as dirty
const markDirty = (item) => {
    item._dirty = true;
};

// Save all changes
const saveAllChanges = async () => {
    const dirtyItems = items.value.filter(item => item._dirty);
    if (dirtyItems.length === 0) return;

    saving.value = true;
    error.value = null;

    try {
        const payload = {
            items: dirtyItems.map(item => ({
                id: item.id,
                soh: item.soh || 0,
                outstanding_gr: item.outstanding_gr || 0,
                outstanding_gi: item.outstanding_gi || 0,
                error_movement: item.error_movement || 0,
            }))
        };

        const response = await axios.post('/api/annual-inventory/discrepancy/bulk-update', payload);

        if (response.data.success) {
            successMessage.value = `Successfully updated ${response.data.updated} items`;
            setTimeout(() => successMessage.value = null, 3000);

            // Reset dirty flags
            dirtyItems.forEach(item => {
                item._dirty = false;
                item._original = {
                    soh: item.soh,
                    outstanding_gr: item.outstanding_gr,
                    outstanding_gi: item.outstanding_gi,
                    error_movement: item.error_movement,
                };
            });

            // Refresh to get updated calculations
            await fetchData(pagination.value.current_page);
        }
    } catch (err) {
        error.value = 'Failed to save: ' + (err.response?.data?.message || err.message);
    } finally {
        saving.value = false;
    }
};

// Filter handlers
const handleFilterChange = () => {
    fetchData(1);
};

// Search with debounce
watch(searchQuery, () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        fetchData(1);
    }, 500);
});

// Pagination
const goToPage = (page) => {
    if (page >= 1 && page <= pagination.value.last_page) {
        fetchData(page);
    }
};

const getPageNumbers = () => {
    const pages = [];
    const current = pagination.value.current_page;
    const last = pagination.value.last_page;

    if (last <= 7) {
        for (let i = 1; i <= last; i++) pages.push(i);
    } else {
        pages.push(1);
        if (current > 3) pages.push('...');

        const start = Math.max(2, current - 1);
        const end = Math.min(last - 1, current + 1);
        for (let i = start; i <= end; i++) pages.push(i);

        if (current < last - 2) pages.push('...');
        pages.push(last);
    }

    return pages;
};

// Navigation
const goBack = () => {
    router.visit('/annual-inventory');
};

// Calculations
const getInitialGap = (item) => {
    const actual = item.actual_qty || 0;
    const soh = item.soh || 0;
    return actual - soh;
};

const getFinalDiscrepancy = (item) => {
    const initialGap = getInitialGap(item);
    const gr = Number(item.outstanding_gr) || 0;
    const gi = Number(item.outstanding_gi) || 0;
    const err = Number(item.error_movement) || 0;

    // Final = Initial Gap + GR + GI + Error
    // GI is typically negative, GR is positive
    return initialGap + gr + gi + err;
};

// Formatting
const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value || 0);
};

const formatNumber = (value) => {
    if (value === null || value === undefined) return '0';
    return new Intl.NumberFormat('en-US').format(value);
};

const getGapColor = (val) => {
    if (val === 0) return 'text-gray-400';
    if (val > 0) return 'text-blue-600';
    return 'text-red-600';
};

const formatCompactTimestamp = (timestampStr) => {
    if (!timestampStr) return '';
    const date = new Date(timestampStr);
    if (isNaN(date.getTime())) return '';
    return new Intl.DateTimeFormat('en-GB', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
    }).format(date).replace(',', '');
};

// Lifecycle
onMounted(() => {
    fetchPIDs();
    fetchLocations();
    fetchData();
});
</script>
