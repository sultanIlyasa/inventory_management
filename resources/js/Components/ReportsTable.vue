<template>
    <div class="overflow-x-auto">
        <table class="w-full border-collapse border border-gray-300">
            <thead>
                <tr class="bg-gray-200">
                    <th class="p-1 sm:p-2 border text-[10px] sm:text-xs md:text-sm">Material Number</th>
                    <th class="p-1 sm:p-2 border bg-yellow-300 text-[10px] sm:text-xs md:text-sm">
                        Material Description
                    </th>
                    <th class="p-1 sm:p-2 border bg-red-400 text-white text-[10px] sm:text-xs md:text-sm">PIC</th>
                    <th class="p-1 sm:p-2 border text-[10px] sm:text-xs md:text-sm">Instock</th>
                    <th class="p-1 sm:p-2 border text-[10px] sm:text-xs md:text-sm">Status</th>
                    <th class="p-1 sm:p-2 border text-[10px] sm:text-xs md:text-sm">Days</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="item in items" :key="item.key" class="text-center hover:bg-gray-50">
                    <!-- Material Number -->
                    <td class="border p-1 sm:p-2 bg-yellow-200 text-[10px] sm:text-xs md:text-sm">
                        {{ item.material_number }}
                    </td>

                    <!-- Description -->
                    <td class="border p-1 sm:p-2 bg-yellow-100 text-[10px] sm:text-xs md:text-sm">
                        {{ item.description }}
                    </td>

                    <!-- PIC -->
                    <td class="border p-1 sm:p-2 bg-red-100 text-[10px] sm:text-xs md:text-sm">
                        {{ item.pic }}
                    </td>
                    <!-- Instock Latest -->
                    <td class="border p-1 sm:p-2 text-[10px] sm:text-xs md:text-sm">
                        {{ item.instock }}
                    </td>
                    <td class="border p-1 sm:p-2 text-[10px] sm:text-xs md:text-sm">
                        <span v-if="item.status === 'CRITICAL'"
                            class=" bg-orange-500 text-yellow-100 px-6 py-2 rounded shadow">{{ item.status }}</span>
                        <span v-else-if="item.status === 'OVERFLOW'"
                            class="bg-blue-100 text-blue-700 px-4 py-2 rounded shadow">{{ item.status }}</span>
                        <span v-else-if="item.status === 'OK'"
                            class="bg-green-100 text-green-700 px-11 py-2 rounded shadow">{{ item.status }}</span>
                        <span v-else-if="item.status === 'SHORTAGE'"
                            class="bg-red-100 text-red-700 px-4 py-2 rounded shadow">{{ item.status }}</span>
                        <span v-else class="px-4 py-2 rounded shadow">{{ item.status }}</span>

                    </td>
                    <td class="border p-1 sm:p-2 text-[10px] sm:text-xs md:text-sm">
                        {{ item.days }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
<script setup>
defineProps({
    items: {
        type: Array,
        required: true,
        default: () => []
    },
})
</script>
