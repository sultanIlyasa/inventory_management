<template>
    <div class="overflow-x-auto">
        <div
            class="w-full my-2 sm:w-auto px-3 py-2 bg-red-100 text-red-700 rounded border text-sm flex items-center justify-center gap-2 hover:bg-red-200 transition">
            <span>Unchecked Items:</span>
            <span class="font-semibold bg-red-600 text-white px-2 py-0.5 rounded">{{ uncheckedCount }}</span>
        </div>
    </div>
    <table class="w-full border-collapse border border-gray-300">
        <thead>
            <tr class="bg-gray-200">
                <th class="p-1 sm:p-2 border text-[10px] sm:text-xs md:text-sm">No</th>
                <th class="p-1 sm:p-2 border text-[10px] sm:text-xs md:text-sm">Material Number</th>
                <th class="p-1 sm:p-2 border bg-yellow-300 text-[10px] sm:text-xs md:text-sm">
                    Material Description
                </th>
                <th class="p-1 sm:p-2 border bg-red-400 text-white text-[10px] sm:text-xs md:text-sm">PIC</th>
                <th class="p-1 sm:p-2 border text-[10px] sm:text-xs md:text-sm">UoM</th>
                <th class="p-1 sm:p-2 border text-[10px] sm:text-xs md:text-sm">Rack Address</th>
                <th class="p-1 sm:p-2 border text-[10px] sm:text-xs md:text-sm">Min Stock</th>
                <th class="p-1 sm:p-2 border text-[10px] sm:text-xs md:text-sm">Max Stock</th>
                <th class="p-1 sm:p-2 border text-[10px] sm:text-xs md:text-sm">Status</th>
                <th class="p-1 sm:p-2 border text-[10px] sm:text-xs md:text-sm">Daily Stock</th>
                <th class="p-1 sm:p-2 border text-[10px] sm:text-xs md:text-sm">Action</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="item in items" :key="item.key" class="text-center hover:bg-gray-50">
                <!-- Number -->
                <td class="border p-1 sm:p-2 font-semibold bg-gray-100 text-[10px] sm:text-xs md:text-sm">
                    {{ item.number }}
                </td>

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
                    {{ item.pic_name }}
                </td>

                <!-- Unit of Measure -->
                <td class="border p-1 sm:p-2 text-[10px] sm:text-xs md:text-sm">
                    {{ item.unit_of_measure }}
                </td>

                <!-- Rack Address -->
                <td class="border p-1 sm:p-2 text-[10px] sm:text-xs md:text-sm">
                    {{ item.rack_address }}
                </td>

                <!-- Min Stock -->
                <td class="border p-1 sm:p-2 text-[10px] sm:text-xs md:text-sm">
                    {{ item.stock_minimum }}
                </td>

                <!-- Max Stock -->
                <td class="border p-1 sm:p-2 text-[10px] sm:text-xs md:text-sm">
                    {{ item.stock_maximum }}
                </td>

                <!-- Status -->
                <td class="border p-1 sm:p-2 text-[10px] sm:text-xs md:text-sm">
                    <span v-if="item.status === 'OK'" class="bg-green-200 font-semibold p-2 rounded-xl">
                        {{ item.status }}</span>
                    <span v-else-if="item.status === 'CRITICAL'" class="bg-yellow-200  font-semibold p-2 rounded-xl"> {{
                        item.status }}</span>
                    <span v-else-if="item.status === 'SHORTAGE'"
                        class="bg-red-500 text-white font-semibold p-2 rounded-xl"> {{ item.status }}</span>
                    <span v-else-if="item.status === 'OVERFLOW'" class="bg-blue-500 font-semibold p-2 rounded-xl">
                        {{ item.status }}</span>
                    <span v-else class="bg-gray-200"> {{ item.status }}</span>


                </td>

                <!-- Daily Stock Input/Display -->
                <td class="border p-1 sm:p-2 text-[10px] sm:text-xs md:text-sm">
                    <div v-if="item.status === 'UNCHECKED'" class="flex items-center gap-1 justify-center">
                        <input v-model.number="item.daily_stock" type="number" min="0" :placeholder="placeholder"
                            class="w-20 px-2 py-1 text-xs border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                            @keypress.enter="handleSubmit(item)" />
                        <button @click="handleSubmit(item)" :disabled="!isValidInput(item.daily_stock)"
                            class="px-2 py-1 bg-blue-600 text-white rounded text-xs hover:bg-bl0ue-700 transition whitespace-nowrap disabled:opacity-50 disabled:cursor-not-allowed">
                            Submit
                        </button>
                    </div>
                    <div v-else>
                        {{ item.daily_stock }}
                    </div>
                </td>

                <!-- Action -->
                <td class="border p-1 sm:p-2 text-[10px] sm:text-xs md:text-sm">
                    <div class="flex flex-col sm:flex-row gap-1 justify-center">
                        <button v-if="item.status !== 'UNCHECKED'" @click="handleDelete(item)"
                            class="px-2 py-1 bg-red-600 text-white rounded text-xs hover:bg-red-700 transition whitespace-nowrap">
                            Delete
                        </button>
                    </div>
                </td>
            </tr>

            <!-- Empty State -->
            <tr v-if="!items.length">
                <td colspan="11" class="border p-4 text-center text-gray-500 text-xs sm:text-sm">
                    {{ emptyMessage }}
                </td>
            </tr>
        </tbody>
    </table>


</template>

<script setup>
defineProps({
    items: {
        type: Array,
        required: true,
        default: () => []
    },
    placeholder: {
        type: String,
        default: 'Stock'
    },
    emptyMessage: {
        type: String,
        default: 'No data available'
    },
    uncheckedCount: {
        type: Number,
        default: 0
    },

})

const emit = defineEmits(['submit', 'delete'])

const isValidInput = (value) => {
    return value !== null && value !== undefined && value !== '' && value >= 0
}

const handleSubmit = (item) => {
    if (!isValidInput(item.daily_stock)) {
        alert(`Please enter a valid stock value for ${item.material_number}`)
        return
    }
    emit('submit', item)
}

const handleDelete = (item) => {
    if (confirm(`Are you sure you want to delete the entry for ${item.material_number}?`)) {
        emit('delete', item)
    }
}
</script>
