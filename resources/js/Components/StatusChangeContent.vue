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

        <!-- Summary Stats (Full View Only) -->
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

        <!-- Compact View -->
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

                    <div class="flex items-center gap-2">
                        <div class="text-center">
                            <div class="text-xs text-gray-600">Caution</div>
                            <div class="text-xs font-semibold text-orange-600">{{ item.frequency_changes.ok_to_caution
                            }}x</div>
                        </div>
                        <div class="text-center">
                            <div class="text-xs text-gray-600">Shortage</div>
                            <div class="text-xs font-semibold text-red-600">{{ item.frequency_changes.ok_to_shortage }}x
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- View All -->
            <a v-if="showViewAll && currentPagination.total > limit" :href="viewAllUrl"
                class="block mt-3 text-center text-sm text-blue-600 hover:text-blue-800 font-semibold">
                View All ({{ currentPagination.total }}) →
            </a>
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
                        <th class="p-2 sm:p-3 border text-center">Current Status</th>
                        <th class="p-2 sm:p-3 border text-center bg-gray-50 font-bold">Total Changes</th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="(item, index) in currentStatusChangeData" :key="item.material_number"
                        class="hover:bg-gray-50 transition border-b">
                        <td class="p-3 border text-center text-gray-600 font-medium">
                            {{ (currentPagination.current_page - 1) * currentPagination.per_page + index + 1 }}
                        </td>

                        <td class="p-2 sm:p-3 border">
                            <div class="font-semibold text-xs sm:text-sm">{{ item.material_number }}</div>
                            <div class="text-[11px] text-gray-500 md:hidden truncate">{{ item.description }}</div>
                        </td>

                        <td class="p-2 sm:p-3 border text-xs sm:text-sm hidden md:table-cell">
                            {{ item.description }}
                        </td>

                        <td class="p-2 sm:p-3 border text-center">
                            <div class="text-lg font-bold text-red-600">
                                {{ item.frequency_changes.ok_to_caution }}
                            </div>
                        </td>

                        <td class="p-2 sm:p-3 border text-center">
                            <div class="text-lg font-bold text-orange-600">
                                {{ item.frequency_changes.ok_to_shortage }}
                            </div>
                        </td>

                        <td class="p-2 sm:p-3 border text-center">
                            <div class="text-lg font-bold text-blue-600">
                                {{ item.frequency_changes.ok_to_overflow }}
                            </div>
                        </td>

                        <td class="p-2 sm:p-3 border text-center">
                            <span v-if="item.current_status === 'OK'"
                                class="bg-green-200 px-2 py-1 rounded-lg text-xs font-semibold">
                                OK
                            </span>
                            <span v-else-if="item.current_status === 'CAUTION'"
                                class="bg-orange-400 px-2 py-1 rounded-lg text-xs font-semibold">
                                CAUTION
                            </span>
                            <span v-else-if="item.current_status === 'SHORTAGE'"
                                class="bg-red-500 text-white px-2 py-1 rounded-lg text-xs font-semibold">
                                SHORTAGE
                            </span>
                            <span v-else-if="item.current_status === 'OVERFLOW'"
                                class="bg-blue-500 text-white px-2 py-1 rounded-lg text-xs font-semibold">
                                OVERFLOW
                            </span>
                        </td>

                        <td class="p-2 sm:p-3 border text-center bg-gray-50">
                            <div class="text-xl font-bold text-gray-800">
                                {{ item.frequency_changes.total_from_ok }}
                            </div>
                        </td>
                    </tr>

                    <tr v-if="!currentStatusChangeData.length">
                        <td colspan="8" class="p-6 text-center text-gray-500">
                            <div class="text-3xl mb-1">📊</div>
                            <div class="text-base font-semibold">No Data Available</div>
                            <div class="text-xs">No status change data found</div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div v-if="currentPagination.last_page > 1"
                class="flex flex-col sm:flex-row items-center justify-between gap-3 mb-6 p-3 sm:p-4">

                <div class="text-sm text-gray-600">
                    Showing {{ startEntry }} to {{ endEntry }} of {{ currentPagination.total }} entries
                </div>

                <div class="flex gap-2">
                    <button @click="emitPage(1)" :disabled="currentPagination.current_page === 1"
                        class="pagination-btn">First</button>
                    <button @click="emitPage(currentPagination.current_page - 1)"
                        :disabled="currentPagination.current_page === 1" class="pagination-btn">Prev</button>

                    <button v-for="page in visiblePages" :key="page" @click="emitPage(page)"
                        :class="['px-3 py-2 text-sm border rounded transition', currentPagination.current_page === page ? 'bg-blue-500 text-white' : 'hover:bg-gray-100']">
                        {{ page }}
                    </button>

                    <button @click="emitPage(currentPagination.current_page + 1)"
                        :disabled="currentPagination.current_page === currentPagination.last_page"
                        class="pagination-btn">
                        Next
                    </button>
                    <button @click="emitPage(currentPagination.last_page)"
                        :disabled="currentPagination.current_page === currentPagination.last_page"
                        class="pagination-btn">
                        Last
                    </button>
                </div>
            </div>
        </div>

        <!-- Footer Refresh -->
        <div v-if="!hideRefresh" class="p-3 border-t" :class="isCompact ? 'flex justify-center' : 'flex justify-end'">
            <button @click="emitRefresh"
                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition text-xs sm:text-sm">
                {{ isCompact ? 'Refresh' : 'Refresh Data' }}
            </button>
        </div>
    </div>
</template>



<script setup>
import { computed } from 'vue'

const props = defineProps({
    size: { type: String, default: 'full' },
    limit: { type: Number, default: null },
    showViewAll: { type: Boolean, default: true },
    viewAllUrl: { type: String, default: 'warehouse-monitoring/status-change' },
    hideRefresh: { type: Boolean, default: false },
    initialStatusChangeData: { type: Array, default: () => [] },
    initialStatistics: { type: Object, default: () => ({}) },
    initialPagination: {
        type: Object,
        default: () => ({
            current_page: 1,
            last_page: 1,
            per_page: 10,
            total: 0
        })
    }
})

const emit = defineEmits(['refresh', 'page-change'])

/* Computed display helpers */
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

const currentStatusChangeData = computed(() => props.initialStatusChangeData)
const currentStatistics = computed(() => props.initialStatistics)
const currentPagination = computed(() => props.initialPagination)

const defaultLimit = computed(() =>
    props.size === 'mini' ? 3 :
        props.size === 'compact' ? 5 :
            10
)

const limitedItems = computed(() => {
    const limit = props.limit || defaultLimit.value
    return currentStatusChangeData.value.slice(0, limit)
})

const visiblePages = computed(() => {
    const pages = []
    const maxVisible = 5
    const current = currentPagination.value.current_page
    const last = currentPagination.value.last_page

    let start = Math.max(1, current - Math.floor(maxVisible / 2))
    let end = Math.min(last, start + maxVisible - 1)

    if (end - start < maxVisible - 1) {
        start = Math.max(1, last - maxVisible + 1)
    }

    for (let i = start; i <= end; i++) pages.push(i)
    return pages
})

/* Pagination helpers */
const startEntry = computed(() =>
    (currentPagination.value.current_page - 1) * currentPagination.value.per_page + 1
)

const endEntry = computed(() =>
    Math.min(currentPagination.value.current_page * currentPagination.value.per_page, currentPagination.value.total)
)

/* Emitters */
const emitPage = (p) => emit('page-change', p)
const emitRefresh = () => emit('refresh')
</script>
