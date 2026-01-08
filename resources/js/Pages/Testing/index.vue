<template>
    <MainAppLayout>

        <div class="">
            <section>
                <div class="">
                        
                </div>
            </section>
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

// Handle location filter change
const handleLocationChange = () => {
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
    return new Intl.NumberFormat('id-ID').format(value);
};

// Color for the initial gap column
const getGapColor = (val) => {
    if (val === 0) return 'text-gray-300';
    if (val > 0) return 'text-green-600'; // Surplus
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
            class: 'bg-green-100 text-green-700 border-green-200',
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
