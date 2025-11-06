<template>
    <div class="bg-white border rounded-lg shadow-lg" :class="containerClass">
        <!-- Header -->
        <div class="p-2 sm:p-3 lg:p-4 border-b">
            <div class="text-center sm:text-left">
                <h3 :class="titleClass">Shortage Materials Leaderboard</h3>
                <p v-if="!isCompact" class="text-xs sm:text-sm text-gray-600 mt-1">
                    Materials with longest shortage streaks
                </p>
            </div>
        </div>

        <!-- Summary Cards - Only show in full mode -->
        <div v-if="!isCompact" class="grid grid-cols-2 gap-2 sm:gap-3 lg:gap-4 p-2 sm:p-3 lg:p-4 bg-gray-50">
            <div class="bg-red-100 border-l-2 sm:border-l-4 border-red-600 p-2 sm:p-3 rounded-lg">
                <div class="text-xs sm:text-sm text-red-700 font-semibold">Total Shortage</div>
                <div class="text-lg sm:text-xl lg:text-2xl font-bold text-red-900">
                    {{ currentStatistics.total }}
                    <span class="hidden sm:inline text-sm">Items</span>
                </div>
            </div>
            <div class="bg-orange-100 border-l-2 sm:border-l-4 border-orange-500 p-2 sm:p-3 rounded-lg">
                <div class="text-xs sm:text-sm text-orange-700 font-semibold">Average Streak</div>
                <div class="text-lg sm:text-xl lg:text-2xl font-bold text-orange-900">
                    {{ currentStatistics.average_days }}
                    <span class="hidden sm:inline text-sm">days</span>
                </div>
            </div>
        </div>

        <!-- Compact View (List) -->
        <div v-if="isCompact" class="p-2 sm:p-3">
            <div class="space-y-2">
                <div v-for="(item, index) in limitedItems" :key="item.material_number"
                    class="flex items-center justify-between p-2 sm:p-3 bg-gray-50 rounded hover:bg-gray-100 transition">
                    <div class="flex items-center gap-2 sm:gap-3 flex-1 min-w-0">
                        <span class="text-base sm:text-lg lg:text-xl">{{ getRankEmoji(index) }}</span>
                        <div class="min-w-0 flex-1">
                            <div class="font-semibold text-xs sm:text-sm lg:text-base truncate">
                                {{ item.description }}
                            </div>
                            <div class="text-[10px] sm:text-xs text-gray-600 truncate">
                                {{ item.material_number }}
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 sm:gap-3">
                        <div class="text-right hidden sm:block">
                            <div class="text-xs text-gray-600">Stock</div>
                            <div class="text-sm font-semibold">{{ item.current_stock }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-base sm:text-lg lg:text-xl font-bold" :class="getDaysColor(item.days)">
                                {{ item.days }}
                            </div>
                            <div class="text-[10px] sm:text-xs text-gray-500">days</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- View All Link -->
            <Link v-if="showViewAll && currentPagination.total > limit"
                :href="route('warehouse-monitoring.leaderboard', { tab: 'SHORTAGE' })"
                class="block mt-3 text-center text-xs sm:text-sm text-red-600 hover:text-red-800 font-semibold">
            View All ({{ currentPagination.total }}) →
            </Link>
        </div>

        <!-- Full View (Table) -->
        <div v-else class="overflow-x-auto">
            <table class="min-w-full border-collapse">
                <thead class="bg-gray-100 text-gray-900 uppercase sticky top-0">
                    <tr>
                        <th
                            class="p-1.5 sm:p-2 lg:p-3 border text-left text-[9px] sm:text-[10px] lg:text-xs font-semibold">
                            Material
                        </th>
                        <th
                            class="p-1.5 sm:p-2 lg:p-3 border text-left text-[9px] sm:text-[10px] lg:text-xs font-semibold hidden md:table-cell">
                            Description
                        </th>
                        <th
                            class="p-1.5 sm:p-2 lg:p-3 border text-left text-[9px] sm:text-[10px] lg:text-xs font-semibold hidden md:table-cell">
                            Usage
                        </th>
                        <th
                            class="p-1.5 sm:p-2 lg:p-3 border text-center text-[9px] sm:text-[10px] lg:text-xs font-semibold">
                            Status
                        </th>
                        <th
                            class="p-1.5 sm:p-2 lg:p-3 border text-center text-[9px] sm:text-[10px] lg:text-xs font-semibold">
                            SOH
                        </th>
                        <th
                            class="p-1.5 sm:p-2 lg:p-3 border text-center text-[9px] sm:text-[10px] lg:text-xs font-semibold">
                            Days
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="(item, index) in currentLeaderboard" :key="item.material_number"
                        class="hover:bg-gray-50 transition-colors border-b">
                        <!-- Material Number -->
                        <td class="p-1.5 sm:p-2 lg:p-3 border">
                            <div class="font-semibold text-[10px] sm:text-xs lg:text-sm">
                                {{ item.material_number }}
                            </div>
                            <div
                                class="text-[9px] sm:text-[10px] text-gray-500 md:hidden truncate max-w-[100px] sm:max-w-[150px]">
                                {{ item.description }}
                            </div>
                        </td>

                        <!-- Description -->
                        <td
                            class="p-1.5 sm:p-2 lg:p-3 border text-[10px] sm:text-xs lg:text-sm hidden md:table-cell max-w-[200px] lg:max-w-none truncate">
                            {{ item.description }}
                        </td>
                        <td
                            class="p-1.5 sm:p-2 lg:p-3 border text-[10px] sm:text-xs lg:text-sm hidden md:table-cell max-w-[200px] lg:max-w-none truncate">
                            {{ item.usage }}
                        </td>

                        <!-- Status -->
                        <td class="p-1.5 sm:p-2 lg:p-3 border text-center">
                            <span
                                class="inline-block px-1.5 sm:px-2 py-0.5 sm:py-1 rounded text-[9px] sm:text-[10px] lg:text-xs font-semibold bg-red-600 text-white whitespace-nowrap">
                                {{ item.current_status }}
                            </span>
                        </td>

                        <!-- SOH -->
                        <td
                            class="p-1.5 sm:p-2 lg:p-3 border text-center font-semibold text-[10px] sm:text-xs lg:text-sm">
                            {{ item.current_stock }}
                        </td>

                        <!-- Days -->
                        <td class="p-1.5 sm:p-2 lg:p-3 border text-center">
                            <div class="font-bold text-sm sm:text-base lg:text-lg" :class="getDaysColor(item.days)">
                                {{ item.days }}
                            </div>
                            <div class="text-[9px] sm:text-[10px] text-gray-500">days</div>
                        </td>
                    </tr>

                    <!-- Empty State -->
                    <tr v-if="!currentLeaderboard.length">
                        <td colspan="5" class="p-4 sm:p-6 text-center text-gray-500">
                            <div class="text-2xl sm:text-3xl mb-1">🎉</div>
                            <div class="text-sm sm:text-base font-semibold">All Clear!</div>
                            <div class="text-xs sm:text-sm">No materials in SHORTAGE status</div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination - Only in full mode -->

        <!-- Footer - Refresh button -->
        <div v-if="!hideRefresh" class="p-2 sm:p-3 border-t"
            :class="isCompact ? 'flex justify-center' : 'flex justify-end'">
            <button @click="fetchLeaderboard(currentPagination.current_page)" :disabled="isLoading"
                :class="isCompact ? 'w-full' : 'w-full sm:w-auto'"
                class="px-3 sm:px-4 py-1.5 sm:py-2 bg-red-600 text-white rounded hover:bg-red-700 transition disabled:opacity-50 text-xs sm:text-sm">
                <span v-if="isLoading">Refreshing...</span>
                <span v-else>{{ isCompact ? 'Refresh' : 'Refresh Data' }}</span>
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from "vue";
import { Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import axios from "axios";

const props = defineProps({
    initialLeaderboard: {
        type: Array,
        default: () => []
    },
    initialStatistics: {
        type: Object,
        default: () => ({
            total: 0,
            average_days: 0,
            max_days: 0,
            min_days: 0,
        })
    },
    initialPagination: {
        type: Object,
        default: () => ({
            current_page: 1,
            last_page: 1,
            per_page: 10,
            total: 0,
        })
    },
    size: {
        type: String,
        default: 'full',
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
    autoRefresh: {
        type: Boolean,
        default: false
    },
    refreshInterval: {
        type: Number,
        default: 300000
    },
    hideRefresh: {
        type: Boolean,
        default: false
    },
});

const leaderboard = ref(props.initialLeaderboard);
const statistics = ref(props.initialStatistics);
const pagination = ref(props.initialPagination);
const isLoading = ref(false);
let intervalId = null;

const currentLeaderboard = computed(() => leaderboard.value.length > 0 ? leaderboard.value : props.initialLeaderboard);
const currentStatistics = computed(() => Object.keys(statistics.value).length > 0 ? statistics.value : props.initialStatistics);
const currentPagination = computed(() => pagination.value.current_page > 0 ? pagination.value : props.initialPagination);

const isCompact = computed(() => props.size === 'compact' || props.size === 'mini');

const containerClass = computed(() => {
    if (props.size === 'mini') return 'max-h-80 overflow-hidden'
    if (props.size === 'compact') return 'max-h-96 overflow-y-auto '
    return ''
});

const titleClass = computed(() => {
    if (props.size === 'mini') return 'text-xs sm:text-sm font-bold'
    if (props.size === 'compact') return 'text-sm sm:text-base lg:text-lg font-bold'
    return 'text-base sm:text-lg lg:text-xl xl:text-2xl font-bold'
});

const limitedItems = computed(() => {
    const defaultLimit = props.size === 'mini' ? 3 : props.size === 'compact' ? 5 : 10;
    const itemLimit = props.limit || defaultLimit;
    return currentLeaderboard.value.slice(0, itemLimit);
});

const visiblePages = computed(() => {
    const pages = [];
    const maxVisible = window.innerWidth < 640 ? 3 : 5;
    const current = currentPagination.value.current_page;
    const last = currentPagination.value.last_page;

    let start = Math.max(1, current - Math.floor(maxVisible / 2));
    let end = Math.min(last, start + maxVisible - 1);

    if (end - start + 1 < maxVisible) {
        start = Math.max(1, end - maxVisible + 1);
    }

    for (let i = start; i <= end; i++) {
        pages.push(i);
    }

    return pages;
});

const fetchLeaderboard = async (page = 1) => {
    isLoading.value = true;
    try {
        const params = new URLSearchParams();
        params.append('page', page);

        if (props.limit && isCompact.value) {
            params.append('per_page', props.limit);
        }

        const response = await axios.get(route('warehouse-monitoring.api.shortage') + '?' + params);
        if (response.data.success) {
            leaderboard.value = response.data.leaderboard || [];
            statistics.value = response.data.statistics || {};
            pagination.value = response.data.pagination || {
                current_page: 1,
                last_page: 1,
                per_page: 10,
                total: 0,
            };
        }
    } catch (error) {
        console.error("Error fetching leaderboard:", error);
    } finally {
        isLoading.value = false;
    }
};

const goToPage = (page) => {
    if (page > 0 && page <= currentPagination.value.last_page) {
        fetchLeaderboard(page);
    }
};

const getDaysColor = (days) => {
    if (days >= 15) return "text-red-700";
    if (days >= 7) return "text-red-600";
    return "text-orange-600";
};

const getRankEmoji = (index) => {
    if (index === 0) return '🥇';
    if (index === 1) return '🥈';
    if (index === 2) return '🥉';
    return `#${index + 1}`;
};

onMounted(() => {
    if (props.initialLeaderboard.length === 0) {
        fetchLeaderboard();
    }

    if (props.autoRefresh) {
        intervalId = setInterval(() => {
            fetchLeaderboard(currentPagination.value.current_page);
        }, props.refreshInterval);
    }
});

onUnmounted(() => {
    if (intervalId) {
        clearInterval(intervalId);
    }
});

watch(() => props.size, () => {
    fetchLeaderboard(1);
});

watch(() => props.initialLeaderboard, (newVal) => {
    if (newVal.length > 0) {
        leaderboard.value = newVal;
    }
});

watch(() => props.initialStatistics, (newVal) => {
    if (Object.keys(newVal).length > 0) {
        statistics.value = newVal;
    }
});

watch(() => props.initialPagination, (newVal) => {
    if (newVal.current_page > 0) {
        pagination.value = newVal;
    }
});
</script>
