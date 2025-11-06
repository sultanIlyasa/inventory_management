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
                    <th class="p-1 sm:p-2 border text-sm">Status</th>
                    <th class="p-1 sm:p-2 border text-sm">Overdue</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="item in filteredItems" :key="item.key" class="text-center hover:bg-gray-50">
                    <td class="border p-1 sm:p-2 bg-yellow-200 text-sm text-left font-bold">{{ item.material_number }}
                    </td>
                    <td class="border p-1 sm:p-2 bg-yellow-100 text-sm text-left font-bold" :class="{
                        'text-xs ': item.description.length > 15,
                        'text-sm': item.description.length <= 30
                    }">{{ item.description }}</td>
                    <td class="border p-1 sm:p-2 bg-red-100 text-sm" :class="{
                        'text-xs': item.description.length > 15,
                        'text-sm': item.description.length <= 30
                    }">{{ item.pic_name }}</td>
                    <td class="border p-1 sm:p-2 text-sm">{{ item.instock }}</td>
                    <td class="border p-1 sm:p-2 text-sm font-bold">
                        <span v-if="item.status === 'CAUTION'"
                            class="bg-orange-500 text-yellow-100 px-4 py-2 rounded shadow"
                            :class="variant === 'COMPACT' ? 'text-[12px]' : 'text-[10px] sm:text-xs'">{{ item.status
                            }}</span>

                        <span v-else-if="item.status === 'OVERFLOW'"
                            class="bg-gray-300 text-gray-700 px-2 py-2 rounded shadow"
                            :class="variant === 'COMPACT' ? 'text-[12px]' : 'text-[10px] sm:text-xs'">{{ item.status
                            }}</span>

                        <span v-else-if="item.status === 'SHORTAGE'"
                            class="bg-red-100 text-red-700 px-2 py-2 rounded shadow"
                            :class="variant === 'COMPACT' ? 'text-[12px]' : 'text-[10px] sm:text-xs'">{{ item.status
                            }}</span>
                        <span v-else class="px-2 py-2 rounded shadow"
                            :class="variant === 'COMPACT' ? 'text-[12px]' : 'text-[10px] sm:text-xs'">{{ item.status
                            }}</span>
                    </td>
                    <td class="border p-1 sm:p-2 text-sm">{{ item.days }} days</td>
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
    }
})


const filteredItems = computed(() => {
    return props.items
})
</script>
