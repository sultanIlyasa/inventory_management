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
                        <th class="px-4 py-3 text-right">SoH</th>
                        <th class="px-4 py-3 w-10"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
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
                Showing <span class="font-medium text-gray-700">{{ inventoryRows.length }}</span> of
                <span class="font-medium text-gray-700">{{ inventoryRows.length }}</span> materials
            </p>
            <div class="flex items-center gap-2">
                <button class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50">Previous</button>
                <button class="rounded-lg bg-blue-600 px-3 py-1.5 text-sm font-semibold text-white">1</button>
                <button class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50">Next</button>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { ref } from 'vue'

defineProps<{
    filters?: Record<string, unknown>
}>()

type InventoryStatus = 'OK' | 'Caution' | 'Shortage'

type InventoryRow = {
    id: string
    material: string
    sku: string
    category: string
    status: InventoryStatus
    overdueDays: number | null
    recoveryDays: number | null
    soh: number
}

// Dummy data retained; future: fetch from API using filters prop
const inventoryRows = ref<InventoryRow[]>([
    { id: 'i1', material: 'Steel Beam I-Type', sku: 'STL-BM-I01', category: 'Structural', status: 'OK', overdueDays: null, recoveryDays: null, soh: 450 },
    { id: 'i2', material: 'Hydraulic Cement', sku: 'HYD-CMT-001', category: 'Concrete', status: 'Shortage', overdueDays: 23, recoveryDays: 15, soh: 120 },
    { id: 'i3', material: 'Ceramic Tiles 60x60', sku: 'CER-TL-6060', category: 'Finishing', status: 'Caution', overdueDays: 8, recoveryDays: 10, soh: 680 },
    { id: 'i4', material: 'Copper Wire 10mm', sku: 'CPR-WR-010', category: 'Electrical', status: 'OK', overdueDays: null, recoveryDays: null, soh: 2400 },
    { id: 'i5', material: 'Glass Panel Tempered', sku: 'GLS-PNL-TMP', category: 'Glazing', status: 'Shortage', overdueDays: 45, recoveryDays: 30, soh: 85 },
    { id: 'i6', material: 'Aluminum Profile', sku: 'ALM-PRF-001', category: 'Framework', status: 'Caution', overdueDays: 12, recoveryDays: 8, soh: 320 },
    { id: 'i7', material: 'Waterproofing Membrane', sku: 'WTR-PRF-MBR', category: 'Protection', status: 'OK', overdueDays: null, recoveryDays: null, soh: 1500 },
    { id: 'i8', material: 'Granite Slab Premium', sku: 'GRN-SLB-PRM', category: 'Stone', status: 'Caution', overdueDays: 5, recoveryDays: 12, soh: 240 },
])

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
