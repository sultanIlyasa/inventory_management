<template>
    <div :class="[
        'overflow-x-auto my-0 py-0 transform origin-top transition-all duration-300',
        variant === 'COMPACT' ? 'zoom-75 -translate-y-1' : 'zoom-100'
    ]">
        <table :class="[
            'w-full border-collapse border border-gray-300 transition-all duration-300',
            variant === 'COMPACT' ? 'text-[8px] sm:text-[9px]' : 'text-[10px] sm:text-xs md:text-sm'
        ]">
            <thead>
                <tr class="bg-gray-200">
                    <th class="p-1 sm:p-2 border text-sm">Material Number</th>
                    <th class="p-1 sm:p-2 border bg-yellow-300 text-sm">Material Description</th>
                    <th class="p-1 sm:p-2 border bg-red-400 text-white text-sm">PIC</th>
                    <th class="p-1 sm:p-2 border text-sm">SOH</th>
                    <th class="p-2 border text-xs lg:text-sm cursor-pointer hover:bg-gray-300"
                        @click="toggleSort('status')">
                        <div class="flex items-center justify-center gap-1">
                            <span>Status</span>
                            <svg v-if="sortField === 'status'" class="w-4 h-4" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path v-if="sortDirection === 'asc'" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="M5 15l7-7 7 7" />
                                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                            <svg v-else class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                            </svg>
                        </div>
                    </th>
                    <th class="p-1 sm:p-2 border text-sm cursor-pointer hover:bg-gray-300" @click="toggleSort('days')">
                        <div class="flex items-center justify-center gap-1">
                            <span>Overdue Days</span>
                            <svg v-if="sortField === 'days'" class="w-4 h-4" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path v-if="sortDirection === 'asc'" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="M5 15l7-7 7 7" />
                                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                            <svg v-else class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                            </svg>
                        </div>
                    </th>
                    <th class="p-1 sm:p-2 border text-sm cursor-pointer hover:bg-gray-300" @click="toggleSort('last_updated')">
                        <div class="flex items-center justify-center gap-1">
                            <span>Last Updated</span>
                            <svg v-if="sortField === 'last_updated'" class="w-4 h-4" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path v-if="sortDirection === 'asc'" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="M5 15l7-7 7 7" />
                                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                            <svg v-else class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                            </svg>
                        </div>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="item in sortedItems" :key="item.key" class="text-center hover:bg-gray-50">
                    <td class="border p-1 sm:p-2 bg-yellow-200 text-sm text-left font-bold">
                        {{ item.material_number }}
                    </td>
                    <td class="border p-1 sm:p-2 bg-yellow-100 text-sm text-left font-bold" :class="{
                        'text-xs': item.description.length > 15,
                        'text-sm': item.description.length <= 30
                    }">
                        {{ item.description }}
                    </td>
                    <td class="border p-1 sm:p-2 bg-red-100 text-sm" :class="{
                        'text-xs': item.description.length > 15,
                        'text-sm': item.description.length <= 30
                    }">
                        {{ item.pic_name }}
                    </td>
                    <td class="border p-1 sm:p-2 text-sm">{{ item.instock }}</td>
                    <td class="border p-1 sm:p-2 text-sm font-bold">
                        <span v-if="item.status === 'CAUTION'"
                            class="bg-orange-500 text-yellow-100 px-4 py-2 rounded shadow"
                            :class="variant === 'COMPACT' ? 'text-[12px]' : 'text-[10px] sm:text-xs'">
                            {{ item.status }}
                        </span>
                        <span v-else-if="item.status === 'OVERFLOW'"
                            class="bg-gray-300 text-gray-700 px-2 py-2 rounded shadow"
                            :class="variant === 'COMPACT' ? 'text-[12px]' : 'text-[10px] sm:text-xs'">
                            {{ item.status }}
                        </span>
                        <span v-else-if="item.status === 'SHORTAGE'"
                            class="bg-red-600 text-white px-2 py-2 rounded shadow"
                            :class="variant === 'COMPACT' ? 'text-[12px]' : 'text-[10px] sm:text-xs'">
                            {{ item.status }}
                        </span>
                        <span v-else class="px-2 py-2 rounded shadow"
                            :class="variant === 'COMPACT' ? 'text-[12px]' : 'text-[10px] sm:text-xs'">
                            {{ item.status }}
                        </span>
                    </td>
                    <td class="border p-1 sm:p-2 text-sm font-semibold" :class="{
                        'text-red-600': item.days >= 15,
                        'text-orange-600': item.days >= 7 && item.days < 15,
                        'text-yellow-600': item.days < 7,
                    }">
                        {{ item.days }} days
                    </td>
                    <td class="border p-1 sm:p-2 text-sm" :class="{
                        'text-red-600 font-bold': !item.last_updated,
                        'text-gray-700': item.last_updated
                    }">
                        <span v-if="item.last_updated">{{ formatDate(item.last_updated) }}</span>
                        <span v-else class="font-bold">Never Checked</span>
                    </td>
                </tr>

                <!-- Empty State -->
                <tr v-if="!sortedItems.length">
                    <td colspan="7" class="border p-6 text-center text-gray-500">
                        <div class="text-2xl mb-2">📊</div>
                        <div class="text-sm font-semibold">No Data Available</div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    items: {
        type: Array,
        required: true,
        default: () => []
    },
    variant: {
        type: String,
        default: 'FULL'
    },
    sortField: {
        type: String,
        default: 'status'
    },
    sortDirection: {
        type: String,
        default: 'desc'
    }
})

const emit = defineEmits(['sort-change'])

const sortField = computed(() => props.sortField)
const sortDirection = computed(() => props.sortDirection)

const toggleSort = (field) => {
    if (props.sortField === field) {
        // Toggle direction if clicking same field
        const direction = props.sortDirection === 'asc' ? 'desc' : 'asc'
        emit('sort-change', { field, direction })
    } else {
        // Change field and set to descending by default
        emit('sort-change', { field, direction: 'desc' })
    }
}

const formatDate = (dateString) => {
    if (!dateString) return 'Never'

    const date = new Date(dateString)
    const today = new Date()
    const yesterday = new Date(today)
    yesterday.setDate(yesterday.getDate() - 1)

    // Reset time part for comparison
    const dateOnly = new Date(date.getFullYear(), date.getMonth(), date.getDate())
    const todayOnly = new Date(today.getFullYear(), today.getMonth(), today.getDate())
    const yesterdayOnly = new Date(yesterday.getFullYear(), yesterday.getMonth(), yesterday.getDate())

    if (dateOnly.getTime() === todayOnly.getTime()) {
        return 'Today'
    } else if (dateOnly.getTime() === yesterdayOnly.getTime()) {
        return 'Yesterday'
    } else {
        // Format as DD/MM/YYYY
        const day = String(date.getDate()).padStart(2, '0')
        const month = String(date.getMonth() + 1).padStart(2, '0')
        const year = date.getFullYear()
        return `${day}/${month}/${year}`
    }
}

const sortedItems = computed(() => {
    if (!props.items || props.items.length === 0) {
        return []
    }

    return props.items
})
</script>
