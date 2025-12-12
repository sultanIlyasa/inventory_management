<template>
    <div class="bg-white border rounded-lg shadow" :class="containerClass">
        <!-- Header -->
        <div class="p-3 sm:p-4 border-b text-center sm:text-left">
            <h3 :class="titleClass">Caution Materials Leaderboard</h3>
            <p v-if="!isCompact" class="text-xs text-gray-600 mt-1">
                Materials with the longest CAUTION streaks
            </p>
        </div>

        <!-- Summary -->
        <div v-if="!isCompact" class="grid grid-cols-2 gap-3 p-3 bg-gray-50">
            <div class="rounded-lg border-l-4 border-orange-500 bg-orange-50 p-3">
                <div class="text-xs text-orange-700 font-semibold">Total Caution</div>
                <div class="text-2xl font-bold text-orange-900">{{ currentStatistics.total }}</div>
            </div>
            <div class="rounded-lg border-l-4 border-yellow-500 bg-yellow-50 p-3">
                <div class="text-xs text-yellow-700 font-semibold">Average Streak</div>
                <div class="text-2xl font-bold text-yellow-900">{{ currentStatistics.average_days }} days</div>
            </div>
        </div>

        <!-- Compact list -->
        <div v-if="isCompact" class="p-2">
            <div class="space-y-2">
                <div v-for="(item, index) in limitedItems" :key="item.material_number"
                    class="flex items-center justify-between rounded-lg bg-gray-50 p-2 hover:bg-gray-100">
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        <span class="text-lg">{{ getRankLabel(index) }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold truncate">{{ item.description }}</p>
                            <p class="text-[11px] text-gray-600 truncate">{{ item.material_number }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-base font-bold" :class="getDaysColor(item.days)">{{ item.days }}</div>
                        <div class="text-[11px] text-gray-500">days</div>
                    </div>
                </div>
            </div>
            <a v-if="showViewAll && currentPagination.total > limitValue" :href="viewAllUrl"
                class="mt-3 block text-center text-xs font-semibold text-orange-600 hover:text-orange-800">
                View All ({{ currentPagination.total }})
            </a>
        </div>

        <!-- Full table -->
        <div v-else class="overflow-x-auto">
            <table class="min-w-full border-collapse text-xs sm:text-sm">
                <thead class="bg-gray-100 text-gray-700 uppercase text-[11px]">
                    <tr>
                        <th class="border p-2 text-left">PIC</th>
                        <th class="border p-2 text-left">Material</th>
                        <th class="border p-2 text-left hidden md:table-cell">Description</th>
                        <th class="border p-2 text-left hidden lg:table-cell">Usage</th>
                        <th class="border p-2 text-center">Status</th>
                        <th class="border p-2 text-center">SOH</th>
                        <th class="border p-2 text-center">Days</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, index) in currentLeaderboard" :key="item.material_number"
                        class="border-b hover:bg-gray-50">
                        <td class="border p-2 hidden md:table-cell truncate">{{ item.pic }}</td>

                        <td class="border p-2">
                            <div class="font-semibold text-xs">{{ item.material_number }}</div>
                            <div class="text-[11px] text-gray-500 md:hidden truncate">{{ item.description }}</div>
                        </td>
                        <td class="border p-2 hidden md:table-cell truncate">{{ item.description }}</td>
                        <td class="border p-2 hidden lg:table-cell">{{ item.usage }}</td>
                        <td class="border p-2 text-center">
                            <span class="inline-flex rounded px-2 py-1 text-xs font-semibold bg-orange-500 text-white">
                                {{ item.current_status }}
                            </span>
                        </td>
                        <td class="border p-2 text-center font-semibold">{{ item.current_stock }}</td>
                        <td class="border p-2 text-center">
                            <div class="text-lg font-bold" :class="getDaysColor(item.days)">{{ item.days }}</div>
                            <div class="text-[11px] text-gray-500">days</div>
                        </td>
                    </tr>
                    <tr v-if="!currentLeaderboard.length">
                        <td colspan="6" class="p-6 text-center text-gray-500">
                            <div class="text-2xl mb-1">No Data</div>
                            <p class="text-sm font-semibold">No materials in CAUTION status</p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div v-if="currentPagination.last_page > 1"
                class="flex flex-col gap-3 border-t p-3 text-sm sm:flex-row sm:items-center sm:justify-between">
                <div class="text-gray-600">
                    Showing {{ startEntry }} to {{ endEntry }} of {{ currentPagination.total }} entries
                </div>
                <div class="flex flex-wrap gap-2">
                    <button class="px-3 py-1.5 rounded border text-sm hover:bg-gray-100 disabled:opacity-50"
                        @click="emitPage(1)" :disabled="currentPagination.current_page === 1">
                        First
                    </button>
                    <button class="px-3 py-1.5 rounded border text-sm hover:bg-gray-100 disabled:opacity-50"
                        @click="emitPage(currentPagination.current_page - 1)"
                        :disabled="currentPagination.current_page === 1">
                        Prev
                    </button>
                    <button v-for="page in visiblePages" :key="page" @click="emitPage(page)"
                        :class="['px-3 py-1.5 rounded border text-sm',
                            currentPagination.current_page === page ? 'bg-orange-500 text-white border-orange-500' : 'hover:bg-gray-100']">
                        {{ page }}
                    </button>
                    <button class="px-3 py-1.5 rounded border text-sm hover:bg-gray-100 disabled:opacity-50"
                        @click="emitPage(currentPagination.current_page + 1)"
                        :disabled="currentPagination.current_page === currentPagination.last_page">
                        Next
                    </button>
                    <button class="px-3 py-1.5 rounded border text-sm hover:bg-gray-100 disabled:opacity-50"
                        @click="emitPage(currentPagination.last_page)"
                        :disabled="currentPagination.current_page === currentPagination.last_page">
                        Last
                    </button>
                </div>
            </div>
        </div>

        <!-- Refresh -->
        <div v-if="!hideRefresh" class="border-t p-3 flex justify-end">
            <button @click="emitRefresh"
                class="rounded bg-orange-500 px-4 py-2 text-xs font-semibold text-white hover:bg-orange-600">
                Refresh Data
            </button>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'



const props = defineProps({
    initialLeaderboard: {
        type: Array,
        default: () => []
    },
    initialStatistics: {
        type: Object,
        default: () => ({
            total: 0,
            average_days: 0
        })
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
    size: {
        type: String,
        default: 'full'
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
        default: '/warehouse-monitoring/leaderboard?tab=CAUTION'
    },
    hideRefresh: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['refresh', 'page-change'])

const isCompact = computed(() => props.size === 'compact' || props.size === 'mini')
const containerClass = computed(() => {
    if (props.size === 'mini') return 'max-h-80 overflow-hidden'
    if (props.size === 'compact') return 'max-h-96 overflow-y-auto'
    return ''
})

const titleClass = computed(() => {
    if (props.size === 'mini') return 'text-sm font-bold'
    if (props.size === 'compact') return 'text-base font-semibold'
    return 'text-xl font-bold'
})

const currentLeaderboard = computed(() => props.initialLeaderboard ?? [])
const currentStatistics = computed(() => ({
    total: props.initialStatistics?.total ?? 0,
    average_days: props.initialStatistics?.average_days ?? 0
}))

const currentPagination = computed(() => ({
    ...(props.initialPagination || {})
}))

const defaultLimit = computed(() => props.size === 'mini' ? 3 : props.size === 'compact' ? 5 : 10)
const limitValue = computed(() => props.limit || defaultLimit.value)

const limitedItems = computed(() => currentLeaderboard.value.slice(0, limitValue.value))

const visiblePages = computed(() => {
    const pages = []
    const maxVisible = 5
    const { current_page, last_page } = currentPagination.value

    let start = Math.max(1, current_page - Math.floor(maxVisible / 2))
    let end = Math.min(last_page, start + maxVisible - 1)

    if (end - start + 1 < maxVisible) {
        start = Math.max(1, end - maxVisible + 1)
    }

    for (let i = start; i <= end; i++) pages.push(i)
    return pages
})

const startEntry = computed(() =>
    (currentPagination.value.current_page - 1) * currentPagination.value.per_page + 1
)

const endEntry = computed(() =>
    Math.min(currentPagination.value.current_page * currentPagination.value.per_page, currentPagination.value.total)
)

const getDaysColor = (days) => {
    if (days >= 15) return 'text-red-600'
    if (days >= 7) return 'text-orange-600'
    return 'text-yellow-600'
}

const getRankLabel = (index) => {
    if (index === 0) return '🥇'
    if (index === 1) return '🥈'
    if (index === 2) return '🥉'
    return `#${index + 1}`
}

const emitRefresh = () => emit('refresh')
const emitPage = (page) => {
    if (page < 1 || page > currentPagination.value.last_page) return
    emit('page-change', page)
}
</script>
