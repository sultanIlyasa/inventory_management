<template>
    <div class="bg-white rounded-lg shadow" :class="containerClass">
        <!-- Header -->
        <div class="p-3 sm:p-4 border-b flex items-center justify-center">
            <div>
                <h3 :class="titleClass">Status Change Frequency</h3>
                <p v-if="!isCompact" class="text-xs text-gray-600 mt-1">
                    How often materials transition from OK to problematic states
                </p>
            </div>
        </div>

        <!-- Summary Stats - Only in full mode -->
        <div v-if="!isCompact && currentStatistics" class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-3 sm:p-4 bg-gray-50">
            <div class="text-center p-3 bg-red-100 rounded-lg">
                <div class="text-sm text-red-700 font-semibold">To Caution</div>
                <div class="text-2xl font-bold text-red-900">{{ currentStatistics.total_to_caution }}</div>
            </div>
            <div class="text-center p-3 bg-orange-100 rounded-lg">
                <div class="text-sm text-orange-700 font-semibold">To Shortage</div>
                <div class="text-2xl font-bold text-orange-900">{{ currentStatistics.total_to_shortage }}</div>
            </div>
            <div class="text-center p-3 bg-blue-100 rounded-lg">
                <div class="text-sm text-blue-700 font-semibold">To Overflow</div>
                <div class="text-2xl font-bold text-blue-900">{{ currentStatistics.total_to_overflow }}</div>
            </div>
            <div class="text-center p-3 bg-green-100 rounded-lg">
                <div class="text-sm text-green-700 font-semibold">Recovered</div>
                <div class="text-2xl font-bold text-green-900">{{ currentStatistics.total_recovered }}</div>
            </div>
        </div>

        <!-- Compact View (List) -->
        <div v-if="isCompact" class="p-1">
            <div class="space-y-2">
                <div v-for="(item, index) in limitedItems" :key="item.material_number"
                    class="flex items-center justify-between p-1 bg-gray-50 rounded hover:bg-gray-100 transition">
                    <div class="flex items-center gap-2 sm:gap-3 flex-1 min-w-0">
                        <span class="text-base sm:text-lg lg:text-xl">{{ index + 1 }}</span>
                        <div class="min-w-0 flex-1">
                            <div class="font-semibold text-xs truncate">{{ item.description }}</div>
                            <div class="text-[10px] sm:text-xs text-gray-600 truncate">{{ item.material_number }}</div>

                        </div>
                    </div>
                    <div class="flex  items-center gap-2">
                        <div class="text-center">
                            <div class="text-xs text-gray-600">Caution</div>
                            <div class="text-xs font-semibold text-orange-600 ">
                                {{ item.frequency_changes.ok_to_caution }}x
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="text-xs text-gray-600">Shortage</div>
                            <div class="text-xs font-semibold  text-red-600">
                                {{ item.frequency_changes.ok_to_shortage }}x
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- View All Link -->
            <a v-if="showViewAll && currentPagination.total > limit" :href="viewAllUrl"
                class="block mt-3 text-center text-sm text-blue-600 hover:text-blue-800 font-semibold">
                View All ({{ currentPagination.total }}) → </a>
        </div>

        <!-- Full View (Table) -->
        <div v-else class="overflow-x-auto">
            <table class="min-w-full text-xs sm:text-sm border-collapse">
                <thead class="bg-gray-100 text-gray-900 uppercase text-[10px] sm:text-xs font-semibold sticky top-0">
                    <tr>
                        <th class="p-2 sm:p-3 border text-left">#</th>
                        <th class="p-2 sm:p-3 border text-left">Material</th>
                        <th class="p-2 sm:p-3 border text-left hidden md:table-cell">Description</th>
                        <th class="p-2 sm:p-3 border text-center bg-red-50">OK → Caution</th>
                        <th class="p-2 sm:p-3 border text-center bg-orange-50">OK → Shortage</th>
                        <th class="p-2 sm:p-3 border text-center bg-blue-50">OK → Overflow</th>
                        <th class="p-2 sm:p-3 border text-center bg-white-50">Current Status</th>
                        <th class="p-2 sm:p-3 border text-center bg-gray-50 font-bold">Total Changes</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, index) in currentStatusChangeData" :key="item.material_number"
                        class="hover:bg-gray-50 transition border-b">
                        <!-- Material Number -->
                        <td class="p-3 border text-center text-gray-600 font-medium">
                            {{ (currentPagination.current_page - 1) * currentPagination.per_page + index + 1 }}
                        </td>
                        <td class="p-2 sm:p-3 border">
                            <div class="font-semibold text-xs sm:text-sm">{{ item.material_number }}</div>
                            <div class="text-[11px] text-gray-500 md:hidden truncate">{{ item.description }}</div>
                        </td>

                        <!-- Description -->
                        <td class="p-2 sm:p-3 border text-xs sm:text-sm hidden md:table-cell">
                            {{ item.description }}
                        </td>

                        <!-- OK → Caution -->
                        <td class="p-2 sm:p-3 border text-center">
                            <div class="text-lg font-bold text-red-600">
                                {{ item.frequency_changes.ok_to_caution }}
                            </div>
                        </td>

                        <!-- OK → Shortage -->
                        <td class="p-2 sm:p-3 border text-center">
                            <div class="text-lg font-bold text-orange-600">
                                {{ item.frequency_changes.ok_to_shortage }}
                            </div>
                        </td>

                        <!-- OK → Overflow -->
                        <td class="p-2 sm:p-3 border text-center">
                            <div class="text-lg font-bold text-blue-600">
                                {{ item.frequency_changes.ok_to_overflow }}
                            </div>
                        </td>


                        <!-- Recovered (to OK) -->
                        <td class="p-2 sm:p-3 border text-center">
                            <span v-if="item.current_status === 'OK'"
                                class="bg-green-200 font-semibold px-2 py-1 rounded-lg text-xs">
                                {{ item.current_status }}
                            </span>
                            <span v-else-if="item.current_status === 'CAUTION'"
                                class="bg-yellow-200 font-semibold px-2 py-1 rounded-lg text-xs">
                                {{ item.current_status }}
                            </span>
                            <span v-else-if="item.current_status === 'SHORTAGE'"
                                class="bg-red-500 text-white font-semibold px-2 py-1 rounded-lg text-xs">
                                {{ item.current_status }}
                            </span>
                            <span v-else-if="item.current_status === 'OVERFLOW'"
                                class="bg-blue-500 text-white font-semibold px-2 py-1 rounded-lg text-xs">
                                {{ item.current_status }}
                            </span>
                        </td>

                        <!-- Total Changes -->
                        <td class="p-2 sm:p-3 border text-center bg-gray-50">
                            <div class="text-xl font-bold text-gray-800">
                                {{ item.frequency_changes.total_from_ok }}
                            </div>
                        </td>
                    </tr>

                    <!-- Empty State -->
                    <tr v-if="!currentStatusChangeData.length">
                        <td colspan="7" class="p-6 text-center text-gray-500">
                            <div class="text-3xl mb-1">📊</div>
                            <div class="text-base font-semibold">No Data Available</div>
                            <div class="text-xs">No status change data found</div>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div v-if="pagination.last_page > 1"
                class="flex flex-col sm:flex-row items-center justify-between gap-3 mb-6 p-3 sm:p-4">
                <div class="text-sm text-gray-600">
                    Showing {{ (pagination.current_page - 1) * pagination.per_page + 1 }}
                    to {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }}
                    of {{ pagination.total }} entries
                </div>

                <div class="flex gap-2">
                    <button @click="goToPage(1)" :disabled="pagination.current_page === 1"
                        class="px-3 py-2 text-sm border rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition">
                        First
                    </button>
                    <button @click="goToPage(pagination.current_page - 1)" :disabled="pagination.current_page === 1"
                        class="px-3 py-2 text-sm border rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition">
                        Prev
                    </button>

                    <button v-for="page in visiblePages" :key="page" @click="goToPage(page)" :class="[
                        'px-3 py-2 text-sm border rounded transition',
                        pagination.current_page === page
                            ? 'bg-blue-500 text-white'
                            : 'hover:bg-gray-100'
                    ]">
                        {{ page }}
                    </button>

                    <button @click="goToPage(pagination.current_page + 1)"
                        :disabled="pagination.current_page === pagination.last_page"
                        class="px-3 py-2 text-sm border rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition">
                        Next
                    </button>
                    <button @click="goToPage(pagination.last_page)"
                        :disabled="pagination.current_page === pagination.last_page"
                        class="px-3 py-2 text-sm border rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition">
                        Last
                    </button>
                </div>
            </div>
        </div>
        <!-- Pagination -->


        <!-- Footer - Refresh button -->
        <div v-if="!hideRefresh" class="p-3 border-t" :class="isCompact ? 'flex justify-center' : 'flex justify-end'">
            <button @click="fetchData" :disabled="isLoading" :class="isCompact ? 'w-full' : 'w-full sm:w-auto'"
                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition disabled:opacity-50 text-xs sm:text-sm">
                <span v-if="isLoading">Loading...</span>
                <span v-else>{{ isCompact ? 'Refresh' : 'Refresh Data' }}</span>
            </button>
        </div>

    </div>

</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'


const props = defineProps({
    size: {
        type: String,
        default: 'full', // 'compact', 'full', 'mini'
        validator: (value) => ['compact', 'full', 'mini'].includes(value)
    },
    limit: {
        type: Number,
        default: null
    },
    showViewAll: {
        type: Boolean,
        default: true
    },
    viewAllUrl: {
        type: String,
        default: 'warehouse-monitoring/status-change'
    },
    sortBy: {
        type: String,
        default: 'total', // 'total', 'caution', 'shortage', 'overflow'
        validator: (value) => ['total', 'caution', 'shortage', 'overflow'].includes(value)
    },
    hideRefresh: {
        type: Boolean,
        default: false
    },
    autoRefresh: {
        type: Boolean,
        default: false
    },
    refreshInterval: {
        type: Number,
        default: 60000 // 1 minute
    },
    filters: {
        type: Object,
        default: () => ({})
    },
    initialStatusChangeData: {
        type: Array,
        default: () => []

    },
    initialStatistics: {
        type: Object,
        default: () => { }
    },

    initialPagination: {
        type: Object,
        default: () => ({
            current_page: 1,
            last_page: 1,
            per_page: 10,
            total: 0
        })
    },

})

const statusChangeData = ref([...props.initialStatusChangeData])
const statistics = ref({ ...props.initialStatistics })
const pagination = ref({ ...props.initialPagination })
const currentStatusChangeData = computed(() =>
    statusChangeData.value.length > 0 ? statusChangeData.value : props.initialStatusChangeData
)

const currentStatistics = computed(() =>
    statistics.value.total_recovered !== undefined ? statistics.value : props.initialStatistics
)
const currentPagination = computed(() =>
    pagination.value.current_page > 0 ? pagination.value : props.initialPagination
)

const materials = ref([])
const isLoading = ref(false)
let intervalId = null
const emit = defineEmits(['refresh', 'page-change'])

// Computed properties
const isCompact = computed(() => props.size === 'compact' || props.size === 'mini')

const containerClass = computed(() => {
    if (props.size === 'mini') return 'max-h-80 overflow-hidden'
    if (props.size === 'compact') return 'max-h-96 overflow-y-auto'
    return ''
})

const titleClass = computed(() => {
    if (props.size === 'mini') return 'flex justify-center text-sm font-bold'
    if (props.size === 'compact') return 'flex justify-center text-base font-bold'
    return 'text-xl font-bold'
})

const limitedItems = computed(() => {
    const defaultLimit = props.size === 'mini' ? 3 : props.size === 'compact' ? 5 : 10
    const itemLimit = props.limit || defaultLimit
    return currentStatusChangeData.value.slice(0, itemLimit)
})

const totalChanges = computed(() => {
    return materials.value.reduce((sum, m) => sum + m.frequency_changes.total_from_ok, 0)
})


const visiblePages = computed(() => {
    const pages = []
    const maxVisible = 5
    const current = currentPagination.value.current_page
    const last = currentPagination.value.last_page

    let start = Math.max(1, current - Math.floor(maxVisible / 2))
    let end = Math.min(last, start + maxVisible - 1)

    if (end - start + 1 < maxVisible) {
        start = Math.max(1, end - maxVisible + 1)
    }

    for (let i = start; i <= end; i++) {
        pages.push(i)
    }

    return pages
})

const goToPage = (page) => {
    if (page >= 1 && page <= currentPagination.value.last_page) {
        emit('page-change', page)
    }
}



const fetchData = async (page = 1) => {
    isLoading.value = true;
    try {
        const params = {
            page: page,
        }
        if (props.limit && isCompact.value) {
            params.per_page = props.limit
        }
        const response = await axios.get(route('warehouse-monitoring.status-change'), { params });
        if (response.data.success) {
            statusChangeData.value = response.data.statusChangeData || [],
                statistics.value = response.data.statistics || {},
                pagination.value = response.data.pagination || {}
        }

    } catch (error) {
        console.error("Failed to fetch recovery report:", error);

    } finally {
        isLoading.value = false;
    }
};

</script>
