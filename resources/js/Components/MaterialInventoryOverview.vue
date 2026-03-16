<template>
    <section class="mt-6 rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-900">Material Inventory Overview</h2>
            <p class="mt-1 text-sm text-gray-500">Complete tracking of warehouse materials and their status</p>
        </div>

        <!-- Desktop Table -->
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs font-semibold text-gray-500">
                        <th class="px-4 py-3"><div class="inline-flex items-center gap-2">Material <span class="text-gray-400">↑↓</span></div></th>
                        <th class="px-4 py-3"><div class="inline-flex items-center gap-2">Material Number <span class="text-gray-400">↑↓</span></div></th>
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Overdue Days</th>
                        <th class="px-4 py-3">Recovery Days</th>
                        <th class="px-4 py-3 text-right">Current Stock</th>
                        <th class="px-4 py-3 w-10"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template v-if="isLoading">
                        <tr v-for="n in 5" :key="'sk-' + n" class="animate-pulse">
                            <td class="px-4 py-3"><div class="h-4 bg-gray-200 rounded w-40"></div></td>
                            <td class="px-4 py-3"><div class="h-4 bg-gray-200 rounded w-28"></div></td>
                            <td class="px-4 py-3"><div class="h-4 bg-gray-200 rounded w-20"></div></td>
                            <td class="px-4 py-3"><div class="h-5 bg-gray-200 rounded-full w-16"></div></td>
                            <td class="px-4 py-3"><div class="h-4 bg-gray-200 rounded w-12"></div></td>
                            <td class="px-4 py-3"><div class="h-4 bg-gray-200 rounded w-12"></div></td>
                            <td class="px-4 py-3"><div class="h-4 bg-gray-200 rounded w-16 ml-auto"></div></td>
                            <td class="px-4 py-3"></td>
                        </tr>
                    </template>
                    <template v-else>
                    <tr v-for="row in inventoryRows" :key="row.id" class="hover:bg-gray-50">
                        <td class="px-4 py-3"><div class="text-sm font-medium text-gray-900">{{ row.material }}</div></td>
                        <td class="px-4 py-3"><div class="text-sm text-gray-700">{{ row.sku }}</div></td>
                        <td class="px-4 py-3"><div class="text-sm text-gray-700">{{ row.category }}</div></td>
                        <td class="px-4 py-3">
                            <span :class="statusPill(row.status)"
                                class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold">
                                {{ row.status }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span v-if="row.overdueDays === null" class="text-sm text-gray-500">-</span>
                            <span v-else class="text-sm font-semibold text-red-600">{{ row.overdueDays }} days</span>
                        </td>
                        <td class="px-4 py-3">
                            <span v-if="row.recoveryDays === null" class="text-sm text-gray-500">-</span>
                            <span v-else class="text-sm font-semibold text-blue-600">{{ row.recoveryDays }} days</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <span class="text-sm font-medium text-gray-900">{{ formatNumber(row.soh) }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600" title="More">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none">
                                    <path d="M12 6.5a1.25 1.25 0 110-2.5 1.25 1.25 0 010 2.5zM12 13.25a1.25 1.25 0 110-2.5 1.25 1.25 0 010 2.5zM12 20a1.25 1.25 0 110-2.5 1.25 1.25 0 010 2.5z" fill="currentColor" />
                                </svg>
                            </button>
                        </td>
                    </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Mobile Cards -->
        <div class="md:hidden p-4 space-y-3">
            <div v-for="row in inventoryRows" :key="row.id"
                class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="text-sm font-semibold text-gray-900">{{ row.material }}</div>
                        <div class="mt-0.5 text-xs text-gray-500">{{ row.sku }} • {{ row.category }}</div>
                    </div>
                    <button class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600" title="More">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none">
                            <path d="M12 6.5a1.25 1.25 0 110-2.5 1.25 1.25 0 010 2.5zM12 13.25a1.25 1.25 0 110-2.5 1.25 1.25 0 010 2.5zM12 20a1.25 1.25 0 110-2.5 1.25 1.25 0 010 2.5z" fill="currentColor" />
                        </svg>
                    </button>
                </div>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <span :class="statusPill(row.status)"
                        class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold">
                        {{ row.status }}
                    </span>
                    <span class="text-xs text-gray-500">SoH:</span>
                    <span class="text-xs font-semibold text-gray-900">{{ formatNumber(row.soh) }}</span>
                </div>
                <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                    <div class="rounded-lg border border-gray-200 p-2">
                        <div class="text-gray-500">Overdue</div>
                        <div v-if="row.overdueDays === null" class="font-semibold text-gray-700">-</div>
                        <div v-else class="font-semibold text-red-600">{{ row.overdueDays }} days</div>
                    </div>
                    <div class="rounded-lg border border-gray-200 p-2">
                        <div class="text-gray-500">Recovery</div>
                        <div v-if="row.recoveryDays === null" class="font-semibold text-gray-700">-</div>
                        <div v-else class="font-semibold text-blue-600">{{ row.recoveryDays }} days</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between border-t border-gray-100 p-4">
            <p class="text-sm text-gray-500">
                Showing
                <span class="font-medium text-gray-700">{{ pagination.from ?? 0 }}</span>–<span class="font-medium text-gray-700">{{ pagination.to ?? 0 }}</span>
                of <span class="font-medium text-gray-700">{{ pagination.total }}</span> materials
            </p>
            <div class="flex items-center gap-2">
                <button
                    class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"
                    :disabled="pagination.current_page <= 1"
                    @click="fetchData(pagination.current_page - 1)">
                    Previous
                </button>
                <button
                    v-for="p in visiblePages" :key="p"
                    class="rounded-lg px-3 py-1.5 text-sm font-semibold"
                    :class="p === pagination.current_page ? 'bg-blue-600 text-white' : 'border border-gray-200 bg-white text-gray-700 hover:bg-gray-50'"
                    @click="fetchData(p)">
                    {{ p }}
                </button>
                <button
                    class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"
                    :disabled="pagination.current_page >= pagination.last_page"
                    @click="fetchData(pagination.current_page + 1)">
                    Next
                </button>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { ref, watch, onMounted, computed } from 'vue'
import { buildFilterParams } from '@/utils/filterParams'

const props = defineProps<{
    filters?: Record<string, unknown>
}>()

type InventoryStatus = 'OK' | 'Caution' | 'Shortage'

type InventoryRow = {
    id: number
    material: string
    sku: string
    category: string
    status: InventoryStatus
    overdueDays: number | null
    recoveryDays: number | null
    soh: number
}

type Pagination = {
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number | null
    to: number | null
}

const inventoryRows = ref<InventoryRow[]>([])
const pagination = ref<Pagination>({ current_page: 1, last_page: 1, per_page: 10, total: 0, from: null, to: null })
const isLoading = ref(false)

async function fetchData(page = 1) {
    isLoading.value = true
    try {
        const params = buildFilterParams(props.filters ?? {})
        params.set('page', String(page))
        params.set('per_page', '10')
        const res = await fetch(`/warehouse-monitoring/api/inventory-overview?${params.toString()}`)
        const json = await res.json()
        inventoryRows.value = json.data ?? []
        pagination.value = json.pagination ?? pagination.value
    } finally {
        isLoading.value = false
    }
}

onMounted(() => fetchData(1))
watch(() => props.filters, () => fetchData(1), { deep: true })

const visiblePages = computed(() => {
    const total = pagination.value.last_page
    const cur = pagination.value.current_page
    const start = Math.max(1, cur - 2)
    const end = Math.min(total, start + 4)
    const pages: number[] = []
    for (let i = start; i <= end; i++) pages.push(i)
    return pages
})

const statusPill = (status: InventoryStatus) => {
    switch (status) {
        case 'OK': return 'bg-emerald-50 text-emerald-700'
        case 'Caution': return 'bg-orange-50 text-orange-700'
        case 'Shortage': return 'bg-red-50 text-red-700'
        default: return 'bg-gray-50 text-gray-700'
    }
}

const formatNumber = (n: number) => new Intl.NumberFormat('en-US').format(n)
</script>
